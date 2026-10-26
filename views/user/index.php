<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $provider */
/** @var array $openLeads */

use app\models\User;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Пользователи';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Пользователи</h1>
    <a class="btn btn-primary btn-sm" href="<?= Url::to(['create']) ?>">+ Добавить</a>
</div>

<?= GridView::widget([
    'dataProvider' => $provider,
    'tableOptions' => ['class' => 'table table-hover align-middle bg-white mb-0'],
    'options' => ['class' => 'card'],
    'layout' => "<div class=\"table-responsive\">{items}</div>",
    'columns' => [
        [
            'attribute' => 'name',
            'label' => 'Имя',
            'format' => 'raw',
            'value' => fn(User $u) => Html::a(Html::encode($u->name), ['update', 'id' => $u->id]),
        ],
        ['attribute' => 'email', 'label' => 'Email'],
        ['attribute' => 'role', 'label' => 'Роль', 'value' => fn(User $u) => $u->getRoleLabel()],
        [
            'label' => 'Открытых лидов',
            'value' => fn(User $u) => $u->isAdmin() ? '—' : (int)($openLeads[$u->id] ?? 0),
        ],
        [
            'attribute' => 'is_active',
            'label' => 'Статус',
            'format' => 'raw',
            'value' => fn(User $u) => $u->is_active
                ? '<span class="badge text-bg-success">Активен</span>'
                : '<span class="badge text-bg-secondary">Отключён</span>',
        ],
    ],
]) ?>
