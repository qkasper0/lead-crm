<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Вход';
?>
<div class="card shadow-sm">
    <div class="card-body p-4">
        <h1 class="h4 mb-4"><?= Html::encode(Yii::$app->name) ?></h1>
        <?php $form = ActiveForm::begin(require __DIR__ . '/../_form_config.php') ?>
        <?= $form->field($model, 'email')->input('email', ['autofocus' => true]) ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <?= $form->field($model, 'rememberMe', ['options' => ['class' => 'mb-3 form-check']])
            ->checkbox(['class' => 'form-check-input'], false)
            ->label('Запомнить меня', ['class' => 'form-check-label']) ?>
        <?= Html::submitButton('Войти', ['class' => 'btn btn-primary w-100']) ?>
        <?php ActiveForm::end() ?>
    </div>
</div>
