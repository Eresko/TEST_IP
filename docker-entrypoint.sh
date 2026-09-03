#!/bin/bash
set -e

if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --no-dev
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# ИСПРАВЛЕНО: Указано верное имя контейнера СУБД из твоего docker-compose.yml
echo "Ожидаем postgres..."
while ! nc -z test_postgres 5432; do
  sleep 0.5
done
echo "Postgres готов"

# Запускаем миграции и сидер (Этап 1)
php artisan migrate --force || true
php artisan db:seed --force || true

# Генерируем документацию API
php artisan swagger:generate || true

# Передаем управление процессу php-fpm
exec "$@"
