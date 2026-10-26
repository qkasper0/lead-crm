<?php

// Где лежит проект:
//  - стандартно: эта папка — lead-crm/web, проект уровнем выше;
//  - основной домен cPanel: содержимое web/ скопировано в ~/public_html, проект — в ~/lead-crm.
$root = is_dir(__DIR__ . '/../config') ? dirname(__DIR__) : dirname(__DIR__) . '/lead-crm';

require $root . '/config/env.php';

defined('YII_DEBUG') or define('YII_DEBUG', getenv('YII_DEBUG') === '1');
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');

require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';

$config = require $root . '/config/web.php';

(new yii\web\Application($config))->run();
