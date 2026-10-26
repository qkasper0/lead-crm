<?php

/** @var yii\web\View $this */
/** @var app\models\LeadSearch $search */
/** @var yii\data\ActiveDataProvider $provider */
/** @var app\models\User $user */

use app\models\Lead;
use app\models\LeadSearch;
use app\models\User;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Лиды';
$managers = $user->isAdmin() ? (['none' => 'Не назначен'] + User::managerList()) : [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $user->isAdmin() ? 'Все лиды' : 'Мои лиды' ?></h1>
    <?php if ($user->isAdmin()): ?>
        <a class="btn btn-primary btn-sm" href="<?= Url::to(['create']) ?>">+ Добавить лид</a>
    <?php endif; ?>
</div>

<form method="get" action="<?= Url::to(['index']) ?>" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Поиск</label>
            <input class="form-control form-control-sm" name="q" value="<?= Html::encode($search->q) ?>" placeholder="Имя, телефон или ID">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Статус</label>
            <?= Html::dropDownList('status', $search->status, Lead::STATUSES, ['class' => 'form-select form-select-sm', 'prompt' => 'Все']) ?>
        </div>
        <?php if ($user->isAdmin()): ?>
            <div class="col-md-2">
                <label class="form-label small">Менеджер</label>
                <?= Html::dropDownList('manager_id', $search->manager_id, $managers, ['class' => 'form-select form-select-sm', 'prompt' => 'Все']) ?>
            </div>
        <?php endif; ?>
        <div class="col-md-2">
            <label class="form-label small">Источник</label>
            <?= Html::dropDownList('source', $search->source, LeadSearch::sourceList($user), ['class' => 'form-select form-select-sm', 'prompt' => 'Все']) ?>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Создан</label>
            <div class="input-group input-group-sm">
                <input type="date" class="form-control" name="date_from" value="<?= Html::encode($search->date_from) ?>">
                <input type="date" class="form-control" name="date_to" value="<?= Html::encode($search->date_to) ?>">
            </div>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-sm btn-primary">Применить</button>
            <a class="btn btn-sm btn-link" href="<?= Url::to(['index']) ?>">Сбросить</a>
        </div>
    </div>
</form>

<?= GridView::widget([
    'dataProvider' => $provider,
    'tableOptions' => ['class' => 'table table-hover align-middle bg-white mb-0'],
    'options' => ['class' => 'card'],
    'layout' => "<div class=\"table-responsive\">{items}</div>\n<div class=\"card-footer d-flex justify-content-between align-items-center\">{summary}{pager}</div>",
    'summaryOptions' => ['class' => 'small text-secondary'],
    'emptyText' => 'Лидов не найдено.',
    'pager' => [
        'options' => ['class' => 'pagination pagination-sm mb-0'],
        'linkContainerOptions' => ['class' => 'page-item'],
        'linkOptions' => ['class' => 'page-link'],
        'disabledListItemSubTagOptions' => ['class' => 'page-link'],
    ],
    'columns' => [
        ['attribute' => 'id', 'headerOptions' => ['style' => 'width: 70px']],
        [
            'attribute' => 'name',
            'format' => 'raw',
            'value' => fn(Lead $l) => Html::a(Html::encode($l->name), ['view', 'id' => $l->id]),
        ],
        'phone',
        'source',
        [
            'attribute' => 'status',
            'format' => 'raw',
            'value' => fn(Lead $l) => Html::tag('span', Html::encode($l->getStatusLabel()), ['class' => 'badge status-' . $l->status]),
        ],
        [
            'attribute' => 'manager_id',
            'visible' => $user->isAdmin(),
            'value' => fn(Lead $l) => $l->manager?->name ?? '—',
        ],
        ['attribute' => 'created_at', 'format' => 'datetime'],
    ],
]) ?>
