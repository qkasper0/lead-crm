<?php

/**
 * Проверка бизнес-правил на SQLite в памяти, без внешних зависимостей.
 * Запуск: php tests/run.php
 */

define('YII_DEBUG', true);
define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../migrations/m261026_000001_init.php';
require __DIR__ . '/../migrations/m261026_000002_user_api_token.php';

use app\components\LeadAssigner;
use app\components\LeadIntake;
use app\models\Lead;
use app\models\LeadHistory;
use app\models\LeadSearch;
use app\models\User;

$params = require __DIR__ . '/../config/params.php';
$params['autoAssign'] = true;

new yii\console\Application([
    'id' => 'crm-test',
    'basePath' => dirname(__DIR__),
    'components' => ['db' => ['class' => 'yii\db\Connection', 'dsn' => 'sqlite::memory:']],
    'params' => $params,
]);

ob_start();
foreach ([m261026_000001_init::class, m261026_000002_user_api_token::class] as $class) {
    (new $class(['db' => Yii::$app->db, 'compact' => true]))->up();
}
ob_end_clean();

$passed = 0;
$failed = 0;
function check(string $name, bool $ok): void
{
    global $passed, $failed;
    echo ($ok ? "  ✓ " : "  ✗ ") . $name . PHP_EOL;
    $ok ? $passed++ : $failed++;
}
function makeUser(string $name, string $role): User
{
    $u = new User(['name' => $name, 'email' => strtolower($name) . '@t.test', 'role' => $role, 'is_active' => true]);
    $u->setPassword('secret123');
    $u->save(false);
    return $u;
}

$admin = makeUser('Admin', User::ROLE_ADMIN);
$m1 = makeUser('Manager1', User::ROLE_MANAGER);
$m2 = makeUser('Manager2', User::ROLE_MANAGER);

echo "Телефон\n";
check('нормализация «+38 (050) 111-22-33» → «+380501112233»', Lead::normalizePhone('+38 (050) 111-22-33') === '+380501112233');
$bad = new Lead(['name' => 'X', 'phone' => '12']);
check('слишком короткий телефон не проходит валидацию', !$bad->validate() && $bad->hasErrors('phone'));

echo "Приём лидов с сайта\n";
$r1 = LeadIntake::accept(['name' => 'Анна', 'phone' => '+38 050 111 22 33', 'source' => 'website', 'comment' => 'Хочу консультацию']);
check('лид создан', $r1['ok'] && !$r1['duplicate']);
$lead1 = Lead::findOne($r1['lead_id']);
check('статус нового лида — «Новый»', $lead1->status === Lead::STATUS_NEW);
check('автоназначен первый менеджер', (int)$lead1->manager_id === $m1->id);
$r2 = LeadIntake::accept(['name' => 'Борис', 'phone' => '0672223344']);
check('второй лид ушёл менее загруженному менеджеру', (int)Lead::findOne($r2['lead_id'])->manager_id === $m2->id);
$r3 = LeadIntake::accept(['name' => 'Анна', 'phone' => '+380501112233', 'comment' => 'Ещё раз']);
check('повторная заявка за 24 ч не создаёт новый лид', $r3['duplicate'] && $r3['lead_id'] === $lead1->id);
check('повторная заявка добавила комментарий', count($lead1->comments) === 2);
$r5 = LeadIntake::accept(['name' => 'Анна', 'phone' => '050 111 22 33']);
check('дубль распознаётся и без кода страны', $r5['duplicate'] && $r5['lead_id'] === $lead1->id);
$r4 = LeadIntake::accept(['name' => '', 'phone' => '1']);
check('невалидная заявка отклонена с ошибками', !$r4['ok'] && isset($r4['errors']['name'], $r4['errors']['phone']));

echo "Статусы\n";
try {
    $lead1->changeStatus(Lead::STATUS_SUCCESS, $m1);
    check('переход «Новый → Успех» запрещён', false);
} catch (yii\base\Exception $e) {
    check('переход «Новый → Успех» запрещён', true);
}
$lead1->changeStatus(Lead::STATUS_IN_WORK, $m1);
$lead1->changeStatus(Lead::STATUS_CALLBACK, $m1);
$lead1->changeStatus(Lead::STATUS_SUCCESS, $m1);
check('цепочка «Новый → В работе → Перезвонить → Успех» проходит', $lead1->status === Lead::STATUS_SUCCESS);
check('менеджер не может вернуть закрытый лид', $lead1->allowedStatuses($m1) === []);
check('админ может вернуть закрытый лид в работу', $lead1->allowedStatuses($admin) === [Lead::STATUS_IN_WORK]);

echo "История изменений\n";
$statusChanges = LeadHistory::find()->where(['lead_id' => $lead1->id, 'field' => 'status'])->count();
check('каждая смена статуса записана в историю (3)', (int)$statusChanges === 3);
$lead1->manager_id = $m2->id;
$lead1->save();
$h = LeadHistory::find()->where(['lead_id' => $lead1->id, 'field' => 'manager_id'])->orderBy(['id' => SORT_DESC])->one();
check('смена менеджера записана: было/стало', $h && (int)$h->old_value === $m1->id && (int)$h->new_value === $m2->id);
check('в истории есть создание лида и комментарии',
    LeadHistory::find()->where(['lead_id' => $lead1->id, 'field' => 'created'])->exists()
    && LeadHistory::find()->where(['lead_id' => $lead1->id, 'field' => 'comment'])->count() >= 2);
$lead1->name = $lead1->name;
$before = LeadHistory::find()->where(['lead_id' => $lead1->id])->count();
$lead1->save();
check('сохранение без изменений не пишет лишнюю историю', LeadHistory::find()->where(['lead_id' => $lead1->id])->count() === $before);

echo "Доступы\n";
$ids1 = LeadSearch::scopedQuery($m1)->select('id')->column();
$ids2 = LeadSearch::scopedQuery($m2)->select('id')->column();
$all = LeadSearch::scopedQuery($admin)->select('id')->column();
check('менеджер 1 не видит лидов менеджера 2', array_intersect($ids1, $ids2) === []);
check('менеджер 2 видит лид, переназначенный на него', in_array($lead1->id, $ids2));
check('админ видит все лиды', count($all) === (int)Lead::find()->count());

echo "Поиск и фильтры\n";
$p = (new LeadSearch())->search(['q' => '050 111'], $admin);
check('поиск по части телефона', $p->getTotalCount() === 1);
$p = (new LeadSearch())->search(['q' => 'Бор'], $admin);
check('поиск по имени', $p->getTotalCount() === 1);
$p = (new LeadSearch())->search(['status' => Lead::STATUS_NEW], $admin);
check('фильтр по статусу', $p->getTotalCount() === 1);
$p = (new LeadSearch())->search(['manager_id' => (string)$m1->id], $m2);
check('фильтр по чужому менеджеру не расширяет видимость менеджера', $p->getTotalCount() === count($ids2));

echo "Распределение\n";
// У менеджеров 1 и 2 есть открытые лиды, у нового — ни одного.
(new Lead(['name' => 'Вера', 'phone' => '+380931234567', 'manager_id' => $m1->id]))->save();
$m3 = makeUser('Manager3', User::ROLE_MANAGER);
check('новый менеджер без открытых лидов получает следующий лид', LeadAssigner::pickManager() === $m3->id);
$m3->is_active = false;
$m3->save(false);
check('отключённый менеджер не получает лиды', LeadAssigner::pickManager() !== $m3->id);


echo "API-токены\n";
$token = $m1->generateApiToken();
check('токен выпускается с префиксом crm_', str_starts_with($token, 'crm_') && strlen($token) > 40);
check('в базе хранится хеш, а не сам токен', User::findOne($m1->id)->api_token_hash === hash('sha256', $token));
check('по токену находится владелец', User::findByApiToken($token)?->id === $m1->id);
check('чужой/выдуманный токен не подходит', User::findByApiToken('crm_' . str_repeat('x', 40)) === null);
$old = $token;
$token = $m1->generateApiToken();
check('после перевыпуска старый токен не работает', User::findByApiToken($old) === null && User::findByApiToken($token) !== null);
Lead::$apiActor = $m1;
$r = LeadIntake::accept(['name' => 'Григорий', 'phone' => '+380671231231'], $m1);
Lead::$apiActor = null;
$viaToken = Lead::findOne($r['lead_id']);
check('лид по токену менеджера назначается ему самому', (int)$viaToken->manager_id === $m1->id);
check('источник по умолчанию для API — api', $viaToken->source === 'api');
$h = LeadHistory::find()->where(['lead_id' => $viaToken->id, 'field' => 'created'])->one();
check('в истории автор — владелец токена', (int)$h->user_id === $m1->id);
$r = LeadIntake::accept(['name' => 'Диана', 'phone' => '+380671231232', 'manager_id' => $m2->id], $admin);
check('админ может указать manager_id', (int)Lead::findOne($r['lead_id'])->manager_id === $m2->id);
$r = LeadIntake::accept(['name' => 'Ева', 'phone' => '+380671231233', 'manager_id' => $m2->id], $m1);
check('менеджер не может отдать лид другому через manager_id', (int)Lead::findOne($r['lead_id'])->manager_id === $m1->id);
$m1->is_active = false;
$m1->save(false);
check('токен отключённого пользователя не работает', User::findByApiToken($token) === null);
$m1->revokeApiToken();
check('отозванный токен не работает', User::findOne($m1->id)->hasApiToken() === false);

echo PHP_EOL . "Итого: $passed пройдено, $failed с ошибкой" . PHP_EOL;
exit($failed ? 1 : 0);
