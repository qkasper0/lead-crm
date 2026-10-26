<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Запись истории изменений лида: кто, что и когда изменил.
 *
 * @property int $id
 * @property int $lead_id
 * @property int|null $user_id
 * @property string $field
 * @property string|null $old_value
 * @property string|null $new_value
 * @property int $created_at
 *
 * @property User|null $user
 */
class LeadHistory extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%lead_history}}';
    }

    /**
     * Записать изменение. Если $userId не передан — берём текущего пользователя,
     * в API и консоли это null («Система»).
     */
    public static function log(int $leadId, string $field, $old, $new, ?int $userId = null): void
    {
        $userId ??= Lead::actor()?->id;
        $row = new self([
            'lead_id' => $leadId,
            'user_id' => $userId,
            'field' => $field,
            'old_value' => $old === null ? null : mb_substr((string)$old, 0, 255),
            'new_value' => $new === null ? null : mb_substr((string)$new, 0, 255),
            'created_at' => time(),
        ]);
        $row->save(false);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getFieldLabel(): string
    {
        return Lead::FIELD_LABELS[$this->field] ?? $this->field;
    }

    /** Человекочитаемое значение (статус — названием, менеджер — именем). */
    public function readable(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($this->field === 'status') {
            return Lead::STATUSES[$value] ?? $value;
        }
        if ($this->field === 'manager_id') {
            return User::find()->select('name')->where(['id' => $value])->scalar() ?: "#$value";
        }
        return $value;
    }
}
