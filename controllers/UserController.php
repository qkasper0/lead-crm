<?php

namespace app\controllers;

use app\models\Lead;
use app\models\User;
use app\models\UserForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Управление пользователями — только администратор.
 */
class UserController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => fn() => Yii::$app->user->identity instanceof User
                        && Yii::$app->user->identity->isAdmin(),
                ]],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => User::find(),
            'sort' => ['defaultOrder' => ['role' => SORT_ASC, 'name' => SORT_ASC]],
            'pagination' => ['pageSize' => 50],
        ]);
        $openLeads = Lead::find()
            ->select(['cnt' => 'COUNT(*)', 'manager_id'])
            ->where(['not in', 'status', Lead::FINAL_STATUSES])
            ->andWhere(['not', ['manager_id' => null]])
            ->groupBy('manager_id')
            ->indexBy('manager_id')
            ->column();

        return $this->render('index', ['provider' => $provider, 'openLeads' => $openLeads]);
    }

    public function actionCreate()
    {
        $form = new UserForm();
        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            Yii::$app->session->setFlash('success', 'Пользователь создан.');
            return $this->redirect(['index']);
        }
        return $this->render('form', ['form' => $form]);
    }

    public function actionUpdate(int $id)
    {
        $user = User::findOne($id) ?? throw new NotFoundHttpException('Пользователь не найден.');
        $form = new UserForm($user);
        if ($form->load(Yii::$app->request->post())) {
            // Админ не может случайно лишить прав или отключить сам себя.
            if ((int)$user->id === (int)Yii::$app->user->id) {
                $form->role = User::ROLE_ADMIN;
                $form->is_active = true;
            }
            if ($form->save()) {
                Yii::$app->session->setFlash('success', 'Изменения сохранены.');
                return $this->redirect(['index']);
            }
        }
        return $this->render('form', ['form' => $form]);
    }
}
