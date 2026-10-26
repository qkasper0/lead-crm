<?php

namespace app\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = true;

    private ?User $_user = null;
    private bool $_loaded = false;

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required'],
            [['email'], 'trim'],
            [['email'], 'email'],
            [['rememberMe'], 'boolean'],
            [['password'], 'validatePassword'],
        ];
    }

    public function attributeLabels(): array
    {
        return ['email' => 'Email', 'password' => 'Пароль', 'rememberMe' => 'Запомнить меня'];
    }

    public function validatePassword($attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }
        $user = $this->getUser();
        if (!$user || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'Неверный email или пароль.');
        }
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
    }

    private function getUser(): ?User
    {
        if (!$this->_loaded) {
            $this->_user = User::findOne(['email' => mb_strtolower($this->email), 'is_active' => true]);
            $this->_loaded = true;
        }
        return $this->_user;
    }
}
