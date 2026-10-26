<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Раздел «API»: персональные токены и документация.
 * Менеджер управляет своим токеном и видит краткую инструкцию,
 * админ — токенами всех пользователей и полную документацию.
 */
class TokenController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['generate' => ['post'], 'revoke' => ['post']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $me = $this->me();
        return $this->render('index', [
            'me' => $me,
            'users' => $me->isAdmin() ? User::find()->orderBy(['role' => SORT_ASC, 'name' => SORT_ASC])->all() : [],
            'newToken' => Yii::$app->session->getFlash('newToken', null, true),
            'leadsUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/api/create-lead']),
            'meUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/api/me']),
        ]);
    }

    public function actionGenerate(?int $id = null)
    {
        $user = $this->target($id);
        $token = $user->generateApiToken();
        Yii::$app->session->setFlash('newToken', ['user' => $user->name, 'token' => $token]);
        return $this->redirect(['index']);
    }

    public function actionRevoke(?int $id = null)
    {
        $user = $this->target($id);
        $user->revokeApiToken();
        Yii::$app->session->setFlash('success', "Токен пользователя «{$user->name}» отозван.");
        return $this->redirect(['index']);
    }

    /** Свой токен может менять любой; чужой — только админ. */
    private function target(?int $id): User
    {
        $me = $this->me();
        if ($id === null || $id === (int)$me->id) {
            return $me;
        }
        if (!$me->isAdmin()) {
            throw new ForbiddenHttpException('Можно управлять только своим токеном.');
        }
        return User::findOne($id) ?? throw new NotFoundHttpException('Пользователь не найден.');
    }

    private function me(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException();
        }
        return $user;
    }
}
