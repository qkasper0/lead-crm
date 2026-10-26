<?php

namespace app\models;

use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * Лид. Здесь — единственное описание статусов и разрешённых переходов.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $source
 * @property string $status
 * @property int|null $manager_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User|null $manager
 * @property LeadComment[] $comments
 * @property LeadHistory[] $history
 */
class Lead extends ActiveRecord
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_WORK = 'in_work';
    public const STATUS_CALLBACK = 'callback';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_NEW => 'Новый',
        self::STATUS_IN_WORK => 'В работе',
        self::STATUS_CALLBACK => 'Перезвонить',
        self::STATUS_SUCCESS => 'Успех',
        self::STATUS_REJECTED => 'Отказ',
    ];

    /** Разрешённые переходы: из статуса => в какие можно перейти. */
    public const TRANSITIONS = [
        self::STATUS_NEW => [self::STATUS_IN_WORK],
        self::STATUS_IN_WORK => [self::STATUS_CALLBACK, self::STATUS_SUCCESS, self::STATUS_REJECTED],
        self::STATUS_CALLBACK => [self::STATUS_IN_WORK, self::STATUS_SUCCESS, self::STATUS_REJECTED],
        self::STATUS_SUCCESS => [],
        self::STATUS_REJECTED => [],
    ];

    /** Финальные статусы: вернуть в работу может только администратор. */
    public const FINAL_STATUSES = [self::STATUS_SUCCESS, self::STATUS_REJECTED];

    /** Поля, изменения которых пишутся в историю. */
    public const TRACKED = ['name', 'phone', 'source', 'status', 'manager_id'];

    public const FIELD_LABELS = [
        'name' => 'Имя',
        'phone' => 'Телефон',
        'source' => 'Источник',
        'status' => 'Статус',
        'manager_id' => 'Менеджер',
        'comment' => 'Комментарий',
        'created' => 'Лид создан',
    ];

    public static function tableName(): string
    {
        return '{{%lead}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'phone'], 'required'],
            [['name'], 'string', 'max' => 150],
            [['source'], 'string', 'max' => 100],
            [['source'], 'default', 'value' => 'website'],
            [['phone'], 'filter', 'filter' => [self::class, 'normalizePhone']],
            [['phone'], 'match', 'pattern' => '/^\+?\d{7,15}$/', 'message' => 'Укажите корректный номер телефона.'],
            [['status'], 'in', 'range' => array_keys(self::STATUSES)],
            [['manager_id'], 'integer'],
            [['manager_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id',
                'filter' => ['role' => User::ROLE_MANAGER, 'is_active' => true],
                'message' => 'Выберите активного менеджера.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Имя',
            'phone' => 'Телефон',
            'source' => 'Источник',
            'status' => 'Статус',
            'manager_id' => 'Ответственный',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    /** Оставляем только цифры и ведущий «+». */
    public static function normalizePhone($value): string
    {
        $value = trim((string)$value);
        $plus = str_starts_with($value, '+') ? '+' : '';
        return $plus . preg_replace('/\D+/', '', $value);
    }

    public function getManager()
    {
        return $this->hasOne(User::class, ['id' => 'manager_id']);
    }

    public function getComments()
    {
        return $this->hasMany(LeadComment::class, ['lead_id' => 'id'])->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function getHistory()
    {
        return $this->hasMany(LeadHistory::class, ['lead_id' => 'id'])->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Статусы, в которые этот пользователь может перевести лид. */
    public function allowedStatuses(?User $user): array
    {
        $next = self::TRANSITIONS[$this->status] ?? [];
        if ($user && $user->isAdmin() && in_array($this->status, self::FINAL_STATUSES, true)) {
            $next[] = self::STATUS_IN_WORK;
        }
        return $next;
    }

    /**
     * Смена статуса с проверкой перехода. Сохраняет лид (история пишется в afterSave).
     * @throws Exception если переход не разрешён
     */
    public function changeStatus(string $status, ?User $user): void
    {
        if (!in_array($status, $this->allowedStatuses($user), true)) {
            throw new Exception(sprintf(
                'Переход «%s» → «%s» не разрешён.',
                $this->getStatusLabel(),
                self::STATUSES[$status] ?? $status
            ));
        }
        $this->status = $status;
        if (!$this->save()) {
            throw new Exception(implode(' ', $this->getFirstErrors()));
        }
    }

    public function beforeSave($insert): bool
    {
        $now = time();
        if ($insert) {
            $this->created_at = $this->created_at ?: $now;
            $this->status = $this->status ?: self::STATUS_NEW;
        }
        $this->updated_at = $now;
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            LeadHistory::log($this->id, 'created', null, $this->source);
            if ($this->manager_id) {
                LeadHistory::log($this->id, 'manager_id', null, $this->manager_id);
            }
            return;
        }

        foreach (self::TRACKED as $field) {
            if (!array_key_exists($field, $changedAttributes)) {
                continue;
            }
            $old = $changedAttributes[$field];
            $new = $this->getAttribute($field);
            if ((string)$old !== (string)$new) {
                LeadHistory::log($this->id, $field, $old, $new);
            }
        }
    }

    public function addComment(string $text, ?int $userId): LeadComment
    {
        $comment = new LeadComment([
            'lead_id' => $this->id,
            'user_id' => $userId,
            'text' => trim($text),
        ]);
        if ($comment->save()) {
            LeadHistory::log($this->id, 'comment', null, mb_substr($comment->text, 0, 250), $userId);
        }
        return $comment;
    }

    /** Автор изменений при запросе через API по персональному токену. */
    public static ?User $apiActor = null;

    /** Текущий пользователь: вошедший в интерфейс, владелец API-токена или null («Система»). */
    public static function actor(): ?User
    {
        if (self::$apiActor !== null) {
            return self::$apiActor;
        }
        if (Yii::$app instanceof \yii\web\Application && !Yii::$app->user->isGuest) {
            $identity = Yii::$app->user->identity;
            return $identity instanceof User ? $identity : null;
        }
        return null;
    }
}
