<?php

/**
 * Загружает настройки из файла .env в корне проекта (KEY=VALUE, строки с # — комментарии).
 * Нужен для хостингов без переменных окружения (cPanel). Уже заданные переменные
 * окружения (Docker, сервер) имеют приоритет и не перезаписываются.
 */
(static function (string $file): void {
    if (!is_file($file) || !is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
})(dirname(__DIR__) . '/.env');
