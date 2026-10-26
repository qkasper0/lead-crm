<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="card"><div class="card-body">
    <h1 class="h4"><?= Html::encode($name) ?></h1>
    <p class="mb-0 text-secondary"><?= nl2br(Html::encode($message)) ?></p>
</div></div>
