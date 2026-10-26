<?php

// Общие настройки ActiveForm под разметку Bootstrap 5.
return [
    'errorCssClass' => 'is-invalid-group',
    'fieldConfig' => [
        'options' => ['class' => 'mb-3'],
        'labelOptions' => ['class' => 'form-label'],
        'inputOptions' => ['class' => 'form-control'],
        'errorOptions' => ['class' => 'invalid-feedback d-block'],
    ],
];
