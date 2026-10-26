<?php

/** @var yii\web\View $this */
/** @var app\models\Lead $lead */
/** @var app\models\User $user */

use app\models\Lead;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $lead->name;
$fmt = Yii::$app->formatter;
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <a class="small text-decoration-none" href="<?= Url::to(['index']) ?>">← К списку</a>
        <h1 class="h3 mb-1"><?= Html::encode($lead->name) ?> <span class="text-secondary fs-6">#<?= $lead->id ?></span></h1>
        <span class="badge status-<?= $lead->status ?>"><?= Html::encode($lead->getStatusLabel()) ?></span>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?= Url::to(['update', 'id' => $lead->id]) ?>">Редактировать</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-5 text-secondary fw-normal">Телефон</dt>
                <dd class="col-7"><a href="tel:<?= Html::encode($lead->phone) ?>"><?= Html::encode($lead->phone) ?></a></dd>
                <dt class="col-5 text-secondary fw-normal">Источник</dt>
                <dd class="col-7"><?= Html::encode($lead->source) ?></dd>
                <dt class="col-5 text-secondary fw-normal">Ответственный</dt>
                <dd class="col-7"><?= Html::encode($lead->manager?->name ?? 'Не назначен') ?></dd>
                <dt class="col-5 text-secondary fw-normal">Создан</dt>
                <dd class="col-7"><?= $fmt->asDatetime($lead->created_at) ?></dd>
                <dt class="col-5 text-secondary fw-normal">Обновлён</dt>
                <dd class="col-7 mb-0"><?= $fmt->asDatetime($lead->updated_at) ?></dd>
            </dl>
        </div></div>

        <?php $next = $lead->allowedStatuses($user); ?>
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6">Сменить статус</h2>
            <?php if ($next): ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($next as $status): ?>
                        <?= Html::beginForm(['status', 'id' => $lead->id], 'post') ?>
                        <?= Html::hiddenInput('status', $status) ?>
                        <?= Html::submitButton(Html::encode(Lead::STATUSES[$status]), ['class' => 'btn btn-sm btn-status-' . $status]) ?>
                        <?= Html::endForm() ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-secondary small mb-0">Лид закрыт. Вернуть его в работу может администратор.</p>
            <?php endif; ?>
        </div></div>

        <?php if ($user->isAdmin()): ?>
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6">Ответственный менеджер</h2>
                <?= Html::beginForm(['assign', 'id' => $lead->id], 'post', ['class' => 'd-flex gap-2']) ?>
                <?= Html::dropDownList('manager_id', $lead->manager_id, User::managerList(), ['class' => 'form-select form-select-sm', 'prompt' => 'Не назначен']) ?>
                <?= Html::submitButton('Назначить', ['class' => 'btn btn-sm btn-primary']) ?>
                <?= Html::endForm() ?>
            </div></div>
        <?php endif; ?>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3" id="comments"><div class="card-body">
            <h2 class="h6">Комментарии</h2>
            <?= Html::beginForm(['comment', 'id' => $lead->id], 'post', ['class' => 'mb-3']) ?>
            <textarea class="form-control mb-2" name="text" rows="2" placeholder="Например: перезвонить после 15:00" required></textarea>
            <?= Html::submitButton('Добавить', ['class' => 'btn btn-sm btn-primary']) ?>
            <?= Html::endForm() ?>
            <?php foreach ($lead->comments as $c): ?>
                <div class="border-top pt-2 mt-2">
                    <div class="small text-secondary">
                        <?= Html::encode($c->user?->name ?? 'Система') ?> · <?= $fmt->asDatetime($c->created_at) ?>
                    </div>
                    <div><?= nl2br(Html::encode($c->text)) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$lead->comments): ?>
                <p class="text-secondary small mb-0">Комментариев пока нет.</p>
            <?php endif; ?>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6">История изменений</h2>
            <div class="table-responsive">
                <table class="table table-sm small mb-0">
                    <thead><tr><th>Когда</th><th>Кто</th><th>Что</th><th>Было</th><th>Стало</th></tr></thead>
                    <tbody>
                    <?php foreach ($lead->history as $h): ?>
                        <tr>
                            <td class="text-nowrap"><?= $fmt->asDatetime($h->created_at) ?></td>
                            <td><?= Html::encode($h->user?->name ?? 'Система') ?></td>
                            <td><?= Html::encode($h->getFieldLabel()) ?></td>
                            <td><?= Html::encode($h->field === 'created' ? '' : $h->readable($h->old_value)) ?></td>
                            <td><?= Html::encode($h->field === 'created' ? 'источник: ' . $h->new_value : $h->readable($h->new_value)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
