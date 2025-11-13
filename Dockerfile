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
    python3 \
    python3-pip \
    && pip3 install --break-system-packages PyPDF2 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        zip \
        gd \
        bcmath \
        opcache

# Python скрипт для объединения PDF уже скопирован выше

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Создание пользователя www-data если его нет
RUN useradd -m -s /bin/bash www-data || true

# Установка рабочей директории
WORKDIR /var/www/html

# Копирование всего исходного кода
COPY . .

# Копирование Python скрипта для объединения PDF
COPY merge_pdf.py /usr/local/bin/merge_pdf.py
RUN chmod +x /usr/local/bin/merge_pdf.py

# Настройка PHP-FPM для работы в Docker сети (слушать на всех интерфейсах)
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i '/^listen.allowed_clients = /d' /usr/local/etc/php-fpm.d/www.conf

# Установка PHP зависимостей
RUN composer install --optimize-autoloader --no-interaction --ignore-platform-reqs

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

# Создание базового .env файла (если не существует)
RUN if [ ! -f .env ]; then \
        echo "APP_NAME=Laravel" > .env && \
        echo "APP_ENV=production" >> .env && \
        echo "APP_KEY=" >> .env && \
        echo "APP_DEBUG=false" >> .env && \
        echo "APP_URL=http://localhost" >> .env && \
        echo "DB_CONNECTION=pgsql" >> .env && \
        echo "DB_HOST=laravel-db" >> .env && \
        echo "DB_PORT=5432" >> .env && \
        echo "DB_DATABASE=laravel" >> .env && \
        echo "DB_USERNAME=laravel" >> .env && \
        echo "DB_PASSWORD=laravel" >> .env && \
        php artisan key:generate --no-interaction; \
    fi

# Настройка PHP для больших файлов
RUN echo "upload_max_filesize = 200M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 200M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 9000

CMD ["php-fpm"]
