FROM php:8.4-fpm


RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    netcat-openbsd \
    && docker-php-ext-install pdo_pgsql pgsql sockets bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


COPY . .

# Установка зависимостей PHP
RUN composer install --no-dev --no-interaction --optimize-autoloader


RUN mkdir -p storage/framework/{views,cache,sessions} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache


COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
