#!/bin/bash

echo "🚀 Настройка Docker окружения для Laravel проекта..."

# Создание .env файла если его нет
if [ ! -f .env ]; then
    echo "📝 Создание .env файла..."
    cat > .env << 'EOF'
APP_NAME="WebAPI Labor Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8081

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=laravel-db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOF
    echo "✅ .env файл создан"
else
    echo "ℹ️  .env файл уже существует"
fi

# Создание необходимых директорий
echo "📁 Создание директорий..."
mkdir -p storage/app/public/photos
mkdir -p storage/app/public/passports
mkdir -p storage/app/public/certificates
mkdir -p storage/app/public/uploads/people
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Установка прав доступа
echo "🔐 Установка прав доступа..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo "✅ Настройка завершена!"
echo ""
echo "📋 Следующие шаги:"
echo "1. Запустите: docker-compose up -d"
echo "2. Выполните миграции: docker-compose exec laravel-app php artisan migrate"
echo "3. Создайте символическую ссылку: docker-compose exec laravel-app php artisan storage:link"
echo "4. Откройте http://localhost:8081"
