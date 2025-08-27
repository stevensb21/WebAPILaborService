FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        zip \
        gd \
        bcmath \
        opcache

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Создание пользователя www-data если его нет
RUN useradd -m -s /bin/bash www-data || true

# Установка рабочей директории
WORKDIR /var/www/html

# Копирование всего исходного кода
COPY . .

# Установка PHP зависимостей
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Создание необходимых директорий и установка прав
RUN mkdir -p storage/app/public/photos \
    && mkdir -p storage/app/public/passports \
    && mkdir -p storage/app/public/certificates \
    && mkdir -p storage/app/public/uploads/people \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# Генерация ключа приложения (если .env не существует)
RUN if [ ! -f .env ]; then \
        cp .env.example .env && \
        php artisan key:generate --no-interaction; \
    fi

EXPOSE 9000

CMD ["php-fpm"]
