<?php

namespace app\components;

use app\models\Lead;
use app\models\User;
use Yii;

/**
 * Приём заявки: валидация, поиск дубля, назначение менеджера.
 * Логика отдельно от контроллера, чтобы её можно было проверить тестами.
 *
 * Кто назначается ответственным:
 *  - токен менеджера  → сам этот менеджер;
 *  - токен админа     → manager_id из запроса, если передан, иначе автораспределение;
 *  - токен сайта      → автораспределение (если включено).
 */
class LeadIntake
{
    /**
     * @param User|null $owner владелец персонального токена (null — общий токен сайта)
     * @return array{ok: bool, lead_id?: int, duplicate?: bool, manager_id?: int|null, errors?: array}
     */
    public static function accept(array $data, ?User $owner = null): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $phone = Lead::normalizePhone($data['phone'] ?? '');
        $source = trim((string)($data['source'] ?? '')) ?: ($owner ? 'api' : 'website');
        $comment = trim((string)($data['comment'] ?? ''));
        $authorId = $owner?->id;

        // Дубль: тот же телефон за последние N часов — не создаём новый лид,
        // а добавляем комментарий к существующему, чтобы менеджер увидел повторное обращение.
        // Сравниваем по последним 9 цифрам: «0671234567», «+380671234567» и «380671234567» — один номер.
        $hours = (int)(Yii::$app->params['duplicateWindowHours'] ?? 24);
        $digits = preg_replace('/\D+/', '', $phone);
        $existing = strlen($digits) >= 7 ? Lead::find()
            ->where(['like', 'phone', '%' . substr($digits, -9), false])
            ->andWhere(['>=', 'created_at', time() - $hours * 3600])
            ->orderBy(['id' => SORT_DESC])
            ->one() : null;

        if ($existing) {
            $text = "Повторная заявка ($source)" . ($comment !== '' ? ": $comment" : '.');
            $existing->addComment($text, $authorId);
            return ['ok' => true, 'lead_id' => $existing->id, 'duplicate' => true, 'manager_id' => $existing->manager_id];
        }

        $lead = new Lead([
            'name' => $name,
            'phone' => $phone,
            'source' => mb_substr($source, 0, 100),
            'status' => Lead::STATUS_NEW,
        ]);

        if ($owner && !$owner->isAdmin()) {
            $lead->manager_id = $owner->id;
        } elseif ($owner && $owner->isAdmin() && !empty($data['manager_id'])) {
            $lead->manager_id = (int)$data['manager_id'];
        } elseif (!empty(Yii::$app->params['autoAssign'])) {
            $lead->manager_id = LeadAssigner::pickManager();
        }

        if (!$lead->save()) {
            return ['ok' => false, 'errors' => $lead->getFirstErrors()];
        }
        if ($comment !== '') {
            $lead->addComment('Комментарий из заявки: ' . $comment, $authorId);
        }
        return ['ok' => true, 'lead_id' => $lead->id, 'duplicate' => false, 'manager_id' => $lead->manager_id];
    }
}
