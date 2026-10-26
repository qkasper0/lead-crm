<?php

namespace app\models;

use yii\base\Model;

/**
 * Форма создания и редактирования пользователя (только для админа).
 */
class UserForm extends Model
{
    public $name;
    public $email;
    public $role = User::ROLE_MANAGER;
    public $is_active = true;
    public $password;

    private User $user;

    public function __construct(?User $user = null, $config = [])
    {
        $this->user = $user ?? new User();
        if (!$this->user->isNewRecord) {
            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->role = $this->user->role;
            $this->is_active = (bool)$this->user->is_active;
        }
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['name', 'email'], 'trim'],
            [['name', 'email', 'role'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['email'], 'filter', 'filter' => 'mb_strtolower'],
            [['email'], 'unique', 'targetClass' => User::class, 'targetAttribute' => 'email',
                'filter' => fn($q) => $this->user->isNewRecord ? null : $q->andWhere(['<>', 'id', $this->user->id]),
                'message' => 'Пользователь с таким email уже есть.'],
            [['role'], 'in', 'range' => array_keys(User::ROLES)],
            [['is_active'], 'boolean'],
            [['password'], 'required', 'when' => fn() => $this->user->isNewRecord],
            [['password'], 'string', 'min' => 8],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Имя',
            'email' => 'Email',
            'role' => 'Роль',
            'is_active' => 'Активен',
            'password' => $this->user->isNewRecord ? 'Пароль' : 'Новый пароль (оставьте пустым, чтобы не менять)',
        ];
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $u = $this->user;
        $u->name = $this->name;
        $u->email = $this->email;
        $u->role = $this->role;
        $u->is_active = (bool)$this->is_active;
        if ($this->password !== null && $this->password !== '') {
            $u->setPassword($this->password);
        }
        return $u->save(false);
    }
}
