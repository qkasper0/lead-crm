<?php

namespace app\controllers;

use app\models\Lead;
use app\models\LeadSearch;
use app\models\LoginForm;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class SiteController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    ['actions' => ['index', 'logout'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['logout' => ['post']]],
        ];
    }

    public function actions(): array
    {
        return ['error' => ['class' => 'yii\web\ErrorAction']];
    }

    /** Дашборд: лиды по статусам и по менеджерам (для менеджера — только свои). */
    public function actionIndex(): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $byStatus = LeadSearch::scopedQuery($user)
            ->select(['cnt' => 'COUNT(*)', 'status'])
            ->groupBy('status')
            ->indexBy('status')
            ->column();
        $statusCounts = [];
        foreach (Lead::STATUSES as $key => $label) {
            $statusCounts[$key] = (int)($byStatus[$key] ?? 0);
        }

        $byManager = [];
        if ($user->isAdmin()) {
            $rows = Lead::find()
                ->select(['manager_id', 'status', 'cnt' => 'COUNT(*)'])
                ->groupBy(['manager_id', 'status'])
                ->asArray()
                ->all();
            $names = User::find()->select('name')->indexBy('id')->column();
            foreach ($rows as $r) {
                $name = $r['manager_id'] ? ($names[$r['manager_id']] ?? '#' . $r['manager_id']) : 'Не назначен';
                $byManager[$name][$r['status']] = (int)$r['cnt'];
            }
            ksort($byManager);
        }

        $todayCount = (int)LeadSearch::scopedQuery($user)
            ->andWhere(['>=', 'created_at', strtotime('today')])
            ->count();

        return $this->render('index', [
            'user' => $user,
            'statusCounts' => $statusCounts,
            'byManager' => $byManager,
            'todayCount' => $todayCount,
        ]);
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $this->layout = 'guest';
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        $model->password = '';
        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
