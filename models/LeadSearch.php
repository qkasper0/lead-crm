<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * Фильтры и поиск по лидам. Здесь же ограничение видимости:
 * менеджер видит только своих лидов.
 */
class LeadSearch extends Model
{
    public $q;
    public $status;
    public $manager_id;
    public $source;
    public $date_from;
    public $date_to;

    public function rules(): array
    {
        return [
            [['q', 'source'], 'trim'],
            [['q', 'source'], 'string', 'max' => 100],
            [['status'], 'in', 'range' => array_keys(Lead::STATUSES)],
            [['manager_id'], 'string'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function formName(): string
    {
        return '';
    }

    /** Базовый запрос с учётом роли. Используется и списком, и дашбордом. */
    public static function scopedQuery(User $user): ActiveQuery
    {
        $query = Lead::find();
        if (!$user->isAdmin()) {
            $query->andWhere(['lead.manager_id' => $user->id]);
        }
        return $query;
    }

    public function search(array $params, User $user): ActiveDataProvider
    {
        $query = self::scopedQuery($user)->with('manager');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 25],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => ['id', 'name', 'status', 'source', 'created_at', 'updated_at', 'manager_id'],
            ],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            $query->andWhere('0=1');
            return $provider;
        }

        if ($this->q !== null && $this->q !== '') {
            $digits = preg_replace('/\D+/', '', $this->q);
            $or = ['or', ['like', 'lead.name', $this->q]];
            if (strlen($digits) >= 3) {
                $or[] = ['like', 'lead.phone', $digits];
            }
            if (ctype_digit($this->q)) {
                $or[] = ['lead.id' => (int)$this->q];
            }
            $query->andWhere($or);
        }

        $query->andFilterWhere(['lead.status' => $this->status]);
        $query->andFilterWhere(['like', 'lead.source', $this->source]);

        // Фильтр по менеджеру доступен только админу; «none» — лиды без ответственного.
        if ($user->isAdmin() && $this->manager_id !== null && $this->manager_id !== '') {
            if ($this->manager_id === 'none') {
                $query->andWhere(['lead.manager_id' => null]);
            } else {
                $query->andWhere(['lead.manager_id' => (int)$this->manager_id]);
            }
        }

        if ($this->date_from) {
            $query->andWhere(['>=', 'lead.created_at', strtotime($this->date_from . ' 00:00:00')]);
        }
        if ($this->date_to) {
            $query->andWhere(['<=', 'lead.created_at', strtotime($this->date_to . ' 23:59:59')]);
        }

        return $provider;
    }

    /** Список источников для фильтра. */
    public static function sourceList(User $user): array
    {
        $values = self::scopedQuery($user)->select('source')->distinct()->orderBy('source')->column();
        return array_combine($values, $values);
    }
}
