<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=mysql;dbname=crm',
    'username' => getenv('DB_USER') ?: 'crm',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => YII_ENV_PROD,
];
