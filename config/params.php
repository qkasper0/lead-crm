<?php

return [
    'appName' => 'Lead CRM',
    // Общий токен сайта для приёма лидов (POST /api/leads). Персональные токены — в разделе «API».
    'apiToken' => getenv('CRM_API_TOKEN') ?: '',
    // С каких сайтов браузер может вызывать API (CORS): «*» — с любого, или домены через запятую.
    'apiAllowedOrigins' => array_filter(array_map('trim', explode(',', getenv('CRM_API_ORIGINS') ?: '*'))),
    // Автоматически назначать новый лид наименее загруженному менеджеру.
    'autoAssign' => (getenv('CRM_AUTO_ASSIGN') ?: '1') === '1',
    // Окно поиска дублей по телефону, в часах.
    'duplicateWindowHours' => 24,
];
