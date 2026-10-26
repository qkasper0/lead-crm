<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var User|null $me */
$me = Yii::$app->user->identity;
$route = Yii::$app->controller->id;

$this->beginPage();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title ? $this->title . ' — ' . Yii::$app->name : Yii::$app->name) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= Url::to('@web/css/app.css') ?>">
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<nav class="navbar navbar-expand-md bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= Url::home() ?>"><?= Html::encode(Yii::$app->name) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link <?= $route === 'site' ? 'active' : '' ?>" href="<?= Url::home() ?>">Дашборд</a></li>
                <li class="nav-item"><a class="nav-link <?= $route === 'lead' ? 'active' : '' ?>" href="<?= Url::to(['/lead/index']) ?>">Лиды</a></li>
                <?php if ($me && $me->isAdmin()): ?>
                    <li class="nav-item"><a class="nav-link <?= $route === 'user' ? 'active' : '' ?>" href="<?= Url::to(['/user/index']) ?>">Пользователи</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link <?= $route === 'token' ? 'active' : '' ?>" href="<?= Url::to(['/token/index']) ?>">API</a></li>
            </ul>
            <?php if ($me): ?>
                <span class="navbar-text me-3 small">
                    <?= Html::encode($me->name) ?> · <span class="text-secondary"><?= Html::encode($me->getRoleLabel()) ?></span>
                </span>
                <?= Html::beginForm(['/site/logout'], 'post') ?>
                <?= Html::submitButton('Выйти', ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container pb-5">
    <?php foreach (array_intersect_key(Yii::$app->session->getAllFlashes(), ['success' => 1, 'error' => 1]) as $type => $messages): ?>
        <?php foreach ((array)$messages as $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                <?= Html::encode($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
