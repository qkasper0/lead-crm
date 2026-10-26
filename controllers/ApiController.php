<?php

namespace app\controllers;

use app\components\LeadIntake;
use app\models\Lead;
use app\models\User;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * REST API для приёма лидов.
 *
 * Авторизация — токен в одном из мест:
 *   заголовок  X-Api-Token: <токен>
 *   заголовок  Authorization: Bearer <токен>
 *   поле       token=<токен>
 *
 * Токены:
 *   персональный (crm_…) — выпускается в разделе «API» для каждого пользователя;
 *   общий токен сайта   — CRM_API_TOKEN из .env (для формы на сайте).
 *
 * Эндпоинты:
 *   POST /api/leads  — создать лид (JSON или form-urlencoded)
 *   GET  /api/me     — проверить токен
 */
class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    /** Владелец персонального токена; null — общий токен сайта. */
    private ?User $owner = null;

    public function beforeAction($action): bool
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->applyCors();

        if ($action->id === 'options') {
            return parent::beforeAction($action);
        }
        if (!$this->authenticate()) {
            Yii::$app->response->statusCode = 401;
            Yii::$app->response->data = [
                'ok' => false,
                'error' => 'invalid_token',
                'message' => 'Передайте действующий токен в заголовке X-Api-Token.',
            ];
            return false;
        }
        Lead::$apiActor = $this->owner;
        return parent::beforeAction($action);
    }

    public function actionOptions(): array
    {
        Yii::$app->response->statusCode = 204;
        return [];
    }

    public function actionMe(): array
    {
        if (!$this->owner) {
            return ['ok' => true, 'token' => 'site', 'assign' => 'auto'];
        }
        return [
            'ok' => true,
            'token' => 'personal',
            'user' => ['id' => $this->owner->id, 'name' => $this->owner->name, 'role' => $this->owner->role],
            'assign' => $this->owner->isAdmin() ? 'manager_id or auto' : 'self',
        ];
    }

    public function actionCreateLead(): array
    {
        $data = Yii::$app->request->post();

        // Honeypot: живой человек это поле не видит и не заполняет.
        if (!empty($data['website'])) {
            return ['ok' => true];
        }

        $result = LeadIntake::accept($data, $this->owner);
        if (!$result['ok']) {
            Yii::$app->response->statusCode = 422;
            return ['ok' => false, 'error' => 'validation', 'fields' => $result['errors']];
        }
        Yii::$app->response->statusCode = $result['duplicate'] ? 200 : 201;
        return $result;
    }

    private function authenticate(): bool
    {
        $request = Yii::$app->request;
        $token = (string)($request->headers->get('X-Api-Token') ?? '');
        if ($token === '') {
            $auth = (string)($request->headers->get('Authorization') ?? '');
            if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
                $token = $m[1];
            }
        }
        if ($token === '') {
            $token = (string)($request->post('token') ?? $request->get('token') ?? '');
        }
        if ($token === '') {
            return false;
        }

        if ($user = User::findByApiToken($token)) {
            $this->owner = $user;
            return true;
        }
        $siteToken = (string)(Yii::$app->params['apiToken'] ?? '');
        return $siteToken !== '' && hash_equals($siteToken, $token);
    }

    /**
     * CORS: API можно вызывать из браузера с любого сайта (CRM_API_ORIGINS=*)
     * или только с перечисленных доменов. Это безопасно: доступ даёт токен, а не cookie.
     */
    private function applyCors(): void
    {
        $origin = Yii::$app->request->headers->get('Origin');
        $allowed = Yii::$app->params['apiAllowedOrigins'] ?? [];
        $headers = Yii::$app->response->headers;

        if (in_array('*', $allowed, true)) {
            $headers->set('Access-Control-Allow-Origin', '*');
        } elseif ($origin && in_array($origin, $allowed, true)) {
            $headers->set('Access-Control-Allow-Origin', $origin);
            $headers->set('Vary', 'Origin');
        } else {
            return;
        }
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Api-Token, Authorization');
        $headers->set('Access-Control-Max-Age', '86400');
    }
}
