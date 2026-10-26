<?php

namespace app\components;

use app\models\Lead;
use app\models\User;

/**
 * Автораспределение: новый лид получает активный менеджер
 * с наименьшим числом открытых лидов (при равенстве — с меньшим ID).
 */
class LeadAssigner
{
    public static function pickManager(): ?int
    {
        $managers = User::find()
            ->select('id')
            ->where(['role' => User::ROLE_MANAGER, 'is_active' => true])
            ->orderBy('id')
            ->column();
        if (!$managers) {
            return null;
        }

        $load = Lead::find()
            ->select(['cnt' => 'COUNT(*)', 'manager_id'])
            ->where(['manager_id' => $managers])
            ->andWhere(['not in', 'status', Lead::FINAL_STATUSES])
            ->groupBy('manager_id')
            ->indexBy('manager_id')
            ->column();

        $best = null;
        $bestLoad = PHP_INT_MAX;
        foreach ($managers as $id) {
            $count = (int)($load[$id] ?? 0);
            if ($count < $bestLoad) {
                $best = (int)$id;
                $bestLoad = $count;
            }
        }
        return $best;
    }
}
