FROM php:8.2-fpm

# system deps
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# xdebug for local test coverage
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# composer (from official composer image)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# copy code
COPY . .

# install php deps (including dev tools like phpunit)
RUN composer install --prefer-dist --no-progress --no-interaction --no-scripts

# permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# caches (optional, ignore failures during dev if env not yet ready)
RUN php artisan config:cache && php artisan route:cache || true

EXPOSE 9000

CMD ["php-fpm"]
