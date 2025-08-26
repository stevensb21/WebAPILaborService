FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev zip unzip git curl \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Создаем необходимые директории и устанавливаем права
RUN mkdir -p /var/www/storage/app/public/photos \
    && mkdir -p /var/www/storage/app/public/passports \
    && mkdir -p /var/www/storage/app/public/certificates \
    && mkdir -p /var/www/storage/app/public/uploads/people \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
