<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

return [
    'id' => 'lead-crm-console',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'timeZone' => getenv('APP_TZ') ?: 'Europe/Kyiv',
    'controllerNamespace' => 'app\commands',
    'components' => [
        'db' => $db,
        'log' => ['targets' => [['class' => 'yii\log\FileTarget', 'levels' => ['error', 'warning']]]],
    ],
    'params' => $params,
];
