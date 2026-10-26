#!/bin/sh
set -e

# Ждём MySQL и применяем миграции при каждом старте (идемпотентно).
for i in $(seq 1 30); do
    php yii migrate --interactive=0 && break
    echo "MySQL ещё не готов, повтор через 2 с ($i/30)..."
    sleep 2
done

# Первый администратор из переменных окружения (если ещё не создан).
if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    php yii seed/admin "$ADMIN_EMAIL" "$ADMIN_PASSWORD" "${ADMIN_NAME:-Администратор}"
fi

exec "$@"
