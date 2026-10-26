<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'lead-crm',
    'name' => $params['appName'],
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'timeZone' => getenv('APP_TZ') ?: 'Europe/Kyiv',
    'bootstrap' => ['log'],
    'defaultRoute' => 'site/index',
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_KEY') ?: 'change-me-in-env',
            'parsers' => ['application/json' => 'yii\web\JsonParser'],
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
        ],
        'errorHandler' => ['errorAction' => 'site/error'],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [['class' => 'yii\log\FileTarget', 'levels' => ['error', 'warning']]],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST api/leads' => 'api/create-lead',
                'GET api/me' => 'api/me',
                'OPTIONS api/<any:[\w-]+>' => 'api/options',
                'api-docs' => 'token/index',
                'api-docs/<action:[\w-]+>' => 'token/<action>',
                'login' => 'site/login',
                'logout' => 'site/logout',
                'leads' => 'lead/index',
                'leads/<id:\d+>' => 'lead/view',
                'leads/<id:\d+>/<action:[\w-]+>' => 'lead/<action>',
                'users' => 'user/index',
                'users/<action:[\w-]+>' => 'user/<action>',
            ],
        ],
        'formatter' => [
            'dateFormat' => 'php:d.m.Y',
            'datetimeFormat' => 'php:d.m.Y H:i',
        ],
        // jQuery и Bootstrap — с CDN, без bower-зависимостей на сервере.
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'sourcePath' => null,
                    'baseUrl' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1',
                    'js' => ['jquery.min.js'],
                ],
            ],
        ],
    ],
    'params' => $params,
];

return $config;
