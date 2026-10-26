<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Комментарий к лиду.
 *
 * @property int $id
 * @property int $lead_id
 * @property int|null $user_id
 * @property string $text
 * @property int $created_at
 *
 * @property User|null $user
 */
class LeadComment extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%lead_comment}}';
    }

    public function rules(): array
    {
        return [
            [['text'], 'trim'],
            [['text'], 'required', 'message' => 'Комментарий не может быть пустым.'],
            [['text'], 'string', 'max' => 5000],
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
