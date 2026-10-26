<?php

namespace app\controllers;

use app\models\Lead;
use app\models\LeadComment;
use app\models\LeadSearch;
use app\models\User;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class LeadController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // Создание вручную и переназначение — только админ.
                    [
                        'actions' => ['create', 'assign'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => fn() => $this->currentUser()->isAdmin(),
                    ],
                    ['actions' => ['index', 'view', 'update', 'status', 'comment'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['status' => ['post'], 'comment' => ['post'], 'assign' => ['post']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $user = $this->currentUser();
        $search = new LeadSearch();
        $provider = $search->search(Yii::$app->request->queryParams, $user);

        return $this->render('index', [
            'search' => $search,
            'provider' => $provider,
            'user' => $user,
        ]);
    }

    public function actionView(int $id): string
    {
        $lead = $this->findModel($id);
        return $this->render('view', [
            'lead' => $lead,
            'user' => $this->currentUser(),
            'comment' => new LeadComment(),
        ]);
    }

    public function actionCreate()
    {
        $lead = new Lead(['source' => 'manual', 'status' => Lead::STATUS_NEW]);
        if ($lead->load(Yii::$app->request->post()) && $lead->save()) {
            Yii::$app->session->setFlash('success', 'Лид создан.');
            return $this->redirect(['view', 'id' => $lead->id]);
        }
        return $this->render('form', ['lead' => $lead, 'user' => $this->currentUser()]);
    }

    /** Редактирование контактных данных. Статус и менеджер меняются отдельными действиями. */
    public function actionUpdate(int $id)
    {
        $lead = $this->findModel($id);
        $post = Yii::$app->request->post('Lead');
        if ($post !== null) {
            $lead->setAttributes(array_intersect_key($post, array_flip(['name', 'phone', 'source'])));
            if ($lead->save()) {
                Yii::$app->session->setFlash('success', 'Изменения сохранены.');
                return $this->redirect(['view', 'id' => $lead->id]);
            }
        }
        return $this->render('form', ['lead' => $lead, 'user' => $this->currentUser()]);
    }

    public function actionStatus(int $id)
    {
        $lead = $this->findModel($id);
        try {
            $lead->changeStatus((string)Yii::$app->request->post('status'), $this->currentUser());
            Yii::$app->session->setFlash('success', 'Статус изменён: ' . $lead->getStatusLabel() . '.');
        } catch (Exception $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['view', 'id' => $lead->id]);
    }

    public function actionAssign(int $id)
    {
        $lead = $this->findModel($id);
        $managerId = Yii::$app->request->post('manager_id');
        $lead->manager_id = $managerId === '' || $managerId === null ? null : (int)$managerId;
        if ($lead->save(true, ['manager_id', 'updated_at'])) {
            Yii::$app->session->setFlash('success', 'Ответственный обновлён.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $lead->getFirstErrors()));
        }
        return $this->redirect(['view', 'id' => $lead->id]);
    }

    public function actionComment(int $id)
    {
        $lead = $this->findModel($id);
        $comment = $lead->addComment((string)Yii::$app->request->post('text', ''), $this->currentUser()->id);
        if ($comment->hasErrors()) {
            Yii::$app->session->setFlash('error', implode(' ', $comment->getFirstErrors()));
        }
        return $this->redirect(['view', 'id' => $lead->id, '#' => 'comments']);
    }

    /**
     * Лид с проверкой доступа. Чужой лид для менеджера — 404, а не 403:
     * не раскрываем, что такой ID существует (защита от IDOR).
     */
    protected function findModel(int $id): Lead
    {
        $lead = LeadSearch::scopedQuery($this->currentUser())->andWhere(['lead.id' => $id])->one();
        if (!$lead) {
            throw new NotFoundHttpException('Лид не найден.');
        }
        return $lead;
    }

    private function currentUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException();
        }
        return $user;
    }
}
