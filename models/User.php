<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Пользователь CRM.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password_hash
 * @property string $auth_key
 * @property string $role
 * @property bool $is_active
 * @property int $created_at
 * @property string|null $api_token_hash
 * @property string|null $api_token_hint
 * @property int|null $api_token_created_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';

    public const ROLES = [
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_MANAGER => 'Менеджер',
    ];

    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->created_at = time();
            $this->auth_key = Yii::$app->security->generateRandomString();
        }
        return parent::beforeSave($insert);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function getRoleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /** Активные менеджеры для выпадающих списков: [id => имя]. */
    public static function managerList(): array
    {
        return self::find()
            ->select(['name', 'id'])
            ->where(['role' => self::ROLE_MANAGER, 'is_active' => true])
            ->orderBy('name')
            ->indexBy('id')
            ->column();
    }

    /**
     * Выпустить новый API-токен (старый перестаёт работать).
     * Возвращает токен открытым текстом — показать его можно только сейчас.
     */
    public function generateApiToken(): string
    {
        $token = 'crm_' . Yii::$app->security->generateRandomString(40);
        $this->api_token_hash = hash('sha256', $token);
        $this->api_token_hint = substr($token, 0, 8) . '…';
        $this->api_token_created_at = time();
        $this->save(false, ['api_token_hash', 'api_token_hint', 'api_token_created_at']);
        return $token;
    }

    public function revokeApiToken(): void
    {
        $this->api_token_hash = null;
        $this->api_token_hint = null;
        $this->api_token_created_at = null;
        $this->save(false, ['api_token_hash', 'api_token_hint', 'api_token_created_at']);
    }

    public function hasApiToken(): bool
    {
        return $this->api_token_hash !== null;
    }

    /** Активный пользователь по API-токену. */
    public static function findByApiToken(string $token): ?self
    {
        if ($token === '' || !str_starts_with($token, 'crm_')) {
            return null;
        }
        return self::findOne(['api_token_hash' => hash('sha256', $token), 'is_active' => true]);
    }

    // IdentityInterface

    public static function findIdentity($id)
    {
        return self::findOne(['id' => $id, 'is_active' => true]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }
}
