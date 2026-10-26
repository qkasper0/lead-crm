<?php

/** @var yii\web\View $this */
/** @var app\models\User $me */
/** @var app\models\User[] $users */
/** @var array|null $newToken */
/** @var string $leadsUrl */
/** @var string $meUrl */

use app\models\Lead;
use yii\helpers\Html;

$this->title = 'API';
\yii\web\YiiAsset::register($this); // для data-confirm
$fmt = Yii::$app->formatter;
$tokenForExample = $newToken['token'] ?? 'ВАШ_ТОКЕН';
$e = fn(string $s) => Html::encode($s);

$curlCreate = <<<SH
curl -X POST {$leadsUrl} \\
  -H "X-Api-Token: {$tokenForExample}" \\
  -H "Content-Type: application/json" \\
  -d '{"name":"Анна","phone":"+38 050 111 22 33","source":"landing","comment":"Нужна консультация"}'
SH;
$curlMe = "curl {$meUrl} -H \"X-Api-Token: {$tokenForExample}\"";
?>
<h1 class="h3 mb-3">API для приёма лидов</h1>

<?php if ($newToken): ?>
    <div class="alert alert-warning">
        <div class="fw-semibold mb-1">Новый токен для «<?= $e($newToken['user']) ?>» — скопируйте его сейчас, больше он показан не будет:</div>
        <div class="d-flex gap-2 align-items-center">
            <code class="token-box flex-grow-1" id="new-token"><?= $e($newToken['token']) ?></code>
            <button class="btn btn-sm btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-token').innerText); this.innerText='Скопировано'">Копировать</button>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6">Мой токен</h2>
            <?php if ($me->hasApiToken()): ?>
                <p class="mb-2"><code><?= $e($me->api_token_hint) ?></code>
                    <span class="text-secondary small">· выпущен <?= $fmt->asDatetime($me->api_token_created_at) ?></span></p>
            <?php else: ?>
                <p class="text-secondary mb-2">Токен ещё не выпущен.</p>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <?= Html::beginForm(['generate'], 'post') ?>
                <?= Html::submitButton($me->hasApiToken() ? 'Выпустить новый' : 'Выпустить токен', ['class' => 'btn btn-sm btn-primary',
                    'data-confirm' => $me->hasApiToken() ? 'Старый токен перестанет работать. Продолжить?' : null]) ?>
                <?= Html::endForm() ?>
                <?php if ($me->hasApiToken()): ?>
                    <?= Html::beginForm(['revoke'], 'post') ?>
                    <?= Html::submitButton('Отозвать', ['class' => 'btn btn-sm btn-outline-danger', 'data-confirm' => 'Отозвать токен?']) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
            <p class="small text-secondary mt-3 mb-0">
                <?= $me->isAdmin()
                    ? 'Лиды по токену администратора распределяются автоматически или назначаются менеджеру из поля manager_id.'
                    : 'Все лиды, отправленные с вашим токеном, сразу назначаются вам.' ?>
                Токен храните как пароль; в базе хранится только его хеш.
            </p>
        </div></div>

        <?php if ($me->isAdmin()): ?>
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6">Токены пользователей</h2>
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <?= $e($u->name) ?>
                                <div class="small text-secondary"><?= $e($u->getRoleLabel()) ?><?= $u->is_active ? '' : ' · отключён' ?></div>
                            </td>
                            <td class="small"><?= $u->hasApiToken() ? '<code>' . $e($u->api_token_hint) . '</code>' : '<span class="text-secondary">нет</span>' ?></td>
                            <td class="text-end text-nowrap">
                                <?= Html::beginForm(['generate', 'id' => $u->id], 'post', ['class' => 'd-inline']) ?>
                                <?= Html::submitButton($u->hasApiToken() ? 'Новый' : 'Выпустить', ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                <?= Html::endForm() ?>
                                <?php if ($u->hasApiToken()): ?>
                                    <?= Html::beginForm(['revoke', 'id' => $u->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::submitButton('Отозвать', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        <?php endif; ?>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body api-docs">
            <h2 class="h6">Быстрая проверка</h2>
            <p class="small mb-1">Проверить токен:</p>
            <pre><code><?= $e($curlMe) ?></code></pre>
            <p class="small mb-1">Отправить лид:</p>
            <pre><code><?= $e($curlCreate) ?></code></pre>
            <p class="small mb-0">Ответ <code>201</code> — лид создан и появился в списке «<?= $me->isAdmin() ? 'Все лиды' : 'Мои лиды' ?>».</p>
        </div></div>

        <?php if ($me->isAdmin()): ?>
            <div class="card"><div class="card-body api-docs">
                <h2 class="h5 mb-3">Документация API</h2>

                <h3 class="h6">Авторизация</h3>
                <p class="small">Токен передаётся одним из способов: заголовок <code>X-Api-Token: &lt;токен&gt;</code>,
                    заголовок <code>Authorization: Bearer &lt;токен&gt;</code> или поле <code>token</code> в теле запроса.</p>
                <table class="table table-sm small">
                    <thead><tr><th>Токен</th><th>Где взять</th><th>Кому назначается лид</th></tr></thead>
                    <tbody>
                    <tr><td>Менеджера</td><td>Этот раздел</td><td>Самому менеджеру</td></tr>
                    <tr><td>Администратора</td><td>Этот раздел</td><td>Менеджеру из <code>manager_id</code>, иначе автораспределение</td></tr>
                    <tr><td>Общий токен сайта</td><td><code>CRM_API_TOKEN</code> в <code>.env</code></td><td>Автораспределение (наименее загруженный менеджер)</td></tr>
                    </tbody>
                </table>

                <h3 class="h6 mt-4">POST <?= $e(parse_url($leadsUrl, PHP_URL_PATH)) ?> — создать лид</h3>
                <p class="small">Тело — JSON (<code>Content-Type: application/json</code>) или обычная форма (<code>application/x-www-form-urlencoded</code>).</p>
                <table class="table table-sm small">
                    <thead><tr><th>Поле</th><th>Обязательное</th><th>Описание</th></tr></thead>
                    <tbody>
                    <tr><td><code>name</code></td><td>да</td><td>Имя, до 150 символов</td></tr>
                    <tr><td><code>phone</code></td><td>да</td><td>Телефон в любом формате: «+38 (050) 111-22-33» сохранится как «+380501112233». 7–15 цифр</td></tr>
                    <tr><td><code>source</code></td><td>нет</td><td>Источник: landing, instagram, google-ads… По умолчанию <code>api</code> (персональный токен) или <code>website</code> (токен сайта)</td></tr>
                    <tr><td><code>comment</code></td><td>нет</td><td>Комментарий клиента — добавится в карточку лида</td></tr>
                    <tr><td><code>manager_id</code></td><td>нет</td><td>ID менеджера. Учитывается только с токеном администратора</td></tr>
                    <tr><td><code>website</code></td><td>нет</td><td>Honeypot для форм: скрытое поле, должно быть пустым. Если заполнено — заявка молча игнорируется</td></tr>
                    </tbody>
                </table>
                <p class="small"><b>Повторные заявки.</b> Если лид с тем же телефоном пришёл за последние
                    <?= (int)Yii::$app->params['duplicateWindowHours'] ?> ч, новый лид не создаётся: к существующему добавляется комментарий «Повторная заявка».</p>

                <h3 class="h6 mt-4">GET <?= $e(parse_url($meUrl, PHP_URL_PATH)) ?> — проверить токен</h3>
                <p class="small">Возвращает владельца токена и правило назначения лидов.</p>

                <h3 class="h6 mt-4">Ответы</h3>
                <table class="table table-sm small">
                    <thead><tr><th>Код</th><th>Когда</th><th>Тело</th></tr></thead>
                    <tbody>
                    <tr><td>201</td><td>Лид создан</td><td><code>{"ok":true,"lead_id":12,"duplicate":false,"manager_id":3}</code></td></tr>
                    <tr><td>200</td><td>Повторная заявка</td><td><code>{"ok":true,"lead_id":12,"duplicate":true,...}</code></td></tr>
                    <tr><td>401</td><td>Нет токена или он неверный/отозван</td><td><code>{"ok":false,"error":"invalid_token"}</code></td></tr>
                    <tr><td>422</td><td>Ошибка в данных</td><td><code>{"ok":false,"error":"validation","fields":{"phone":"…"}}</code></td></tr>
                    </tbody>
                </table>

                <h3 class="h6 mt-4">Вызов из браузера (форма на любом сайте)</h3>
                <p class="small">API отвечает на CORS-запросы
                    <?= in_array('*', Yii::$app->params['apiAllowedOrigins'], true) ? 'с любых сайтов' : 'с доменов из <code>CRM_API_ORIGINS</code>' ?>.
                    Доступ даёт только токен, cookie не используются. На боевом сайте токен лучше не держать в HTML:
                    форма отправляется на свой бэкенд, а он пересылает заявку в CRM.</p>
<pre><code><?= $e(<<<JS
fetch('{$leadsUrl}', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'X-Api-Token': '{$tokenForExample}' },
  body: JSON.stringify({ name: 'Анна', phone: '+380501112233', source: 'landing' })
}).then(r => r.json()).then(console.log);
JS) ?></code></pre>

                <h3 class="h6 mt-4">PHP</h3>
<pre><code><?= $e(<<<PHP
\$ch = curl_init('{$leadsUrl}');
curl_setopt_array(\$ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['X-Api-Token: {$tokenForExample}'],
    CURLOPT_POSTFIELDS => ['name' => 'Анна', 'phone' => '+380501112233', 'source' => 'landing'],
    CURLOPT_RETURNTRANSFER => true,
]);
\$response = json_decode(curl_exec(\$ch), true);
PHP) ?></code></pre>

                <h3 class="h6 mt-4">Статусы лида</h3>
                <p class="small mb-0">Новый лид всегда получает статус «<?= $e(Lead::STATUSES[Lead::STATUS_NEW]) ?>».
                    Дальше: <?= $e(implode(' → ', [Lead::STATUSES['new'], Lead::STATUSES['in_work'], Lead::STATUSES['callback']])) ?>
                    → <?= $e(Lead::STATUSES['success']) ?> / <?= $e(Lead::STATUSES['rejected']) ?> — меняются в интерфейсе CRM.</p>
            </div></div>
        <?php endif; ?>
    </div>
</div>
