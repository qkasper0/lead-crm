<?php

/** @var yii\web\View $this */
/** @var app\models\Lead $lead */
/** @var app\models\User $user */

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = $lead->isNewRecord ? 'Новый лид' : 'Редактирование: ' . $lead->name;
?>
<a class="small text-decoration-none" href="<?= Url::to($lead->isNewRecord ? ['index'] : ['view', 'id' => $lead->id]) ?>">← Назад</a>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<div class="card" style="max-width: 560px"><div class="card-body">
    <?php $form = ActiveForm::begin(require __DIR__ . '/../_form_config.php') ?>
    <?= $form->field($lead, 'name') ?>
    <?= $form->field($lead, 'phone')->input('tel') ?>
    <?= $form->field($lead, 'source') ?>
    <?php if ($lead->isNewRecord && $user->isAdmin()): ?>
        <?= $form->field($lead, 'manager_id')->dropDownList(User::managerList(), ['prompt' => 'Не назначен', 'class' => 'form-select']) ?>
    <?php endif; ?>
    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end() ?>
</div></div>
