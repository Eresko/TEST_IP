#!/bin/bash


set -e


if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi


if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi


echo "Ожитдаем postgres..."
while ! nc -z ak_techlogistic_postgres 5432; do
  sleep 0.5
done
echo "Postgres готов"


php artisan migrate --force || true
php artisan db:seed --force || true


php artisan swagger:generate || true


exec "$@"
