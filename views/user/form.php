<?php

/** @var yii\web\View $this */
/** @var app\models\UserForm $form */

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$isNew = $form->getUser()->isNewRecord;
$this->title = $isNew ? 'Новый пользователь' : 'Пользователь: ' . $form->name;
?>
<a class="small text-decoration-none" href="<?= Url::to(['index']) ?>">← К списку</a>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<div class="card" style="max-width: 560px"><div class="card-body">
    <?php $f = ActiveForm::begin(require __DIR__ . '/../_form_config.php') ?>
    <?= $f->field($form, 'name') ?>
    <?= $f->field($form, 'email')->input('email') ?>
    <?= $f->field($form, 'role')->dropDownList(User::ROLES, ['class' => 'form-select']) ?>
    <?= $f->field($form, 'password')->passwordInput(['autocomplete' => 'new-password']) ?>
    <?= $f->field($form, 'is_active', ['options' => ['class' => 'mb-3 form-check']])
        ->checkbox(['class' => 'form-check-input'], false)
        ->label('Активен (может входить в систему)', ['class' => 'form-check-label']) ?>
    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end() ?>
</div></div>
