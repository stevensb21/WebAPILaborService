@echo off
echo 🚀 Запуск WebAPI Labor Service в Docker...

REM Проверка наличия Docker
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker не установлен. Установите Docker и попробуйте снова.
    pause
    exit /b 1
)

REM Проверка наличия Docker Compose
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker Compose не установлен. Установите Docker Compose и попробуйте снова.
    pause
    exit /b 1
)

REM Создание .env файла если его нет
if not exist .env (
    echo 📝 Создание .env файла...
    (
        echo APP_NAME="WebAPI Labor Service"
        echo APP_ENV=local
        echo APP_KEY=
        echo APP_DEBUG=true
        echo APP_URL=http://localhost:8081
        echo.
        echo LOG_CHANNEL=stack
        echo LOG_DEPRECATIONS_CHANNEL=null
        echo LOG_LEVEL=debug
        echo.
        echo DB_CONNECTION=pgsql
        echo DB_HOST=laravel-db
        echo DB_PORT=5432
        echo DB_DATABASE=laravel
        echo DB_USERNAME=laravel
        echo DB_PASSWORD=secret
        echo.
        echo BROADCAST_DRIVER=log
        echo CACHE_DRIVER=file
        echo FILESYSTEM_DISK=local
        echo QUEUE_CONNECTION=sync
        echo SESSION_DRIVER=file
        echo SESSION_LIFETIME=120
        echo.
        echo MEMCACHED_HOST=127.0.0.1
        echo.
        echo REDIS_HOST=127.0.0.1
        echo REDIS_PASSWORD=null
        echo REDIS_PORT=6379
        echo.
        echo MAIL_MAILER=smtp
        echo MAIL_HOST=mailpit
        echo MAIL_PORT=1025
        echo MAIL_USERNAME=null
        echo MAIL_PASSWORD=null
        echo MAIL_ENCRYPTION=null
        echo MAIL_FROM_ADDRESS="hello@example.com"
        echo MAIL_FROM_NAME=%%APP_NAME%%
        echo.
        echo AWS_ACCESS_KEY_ID=
        echo AWS_SECRET_ACCESS_KEY=
        echo AWS_DEFAULT_REGION=us-east-1
        echo AWS_BUCKET=
        echo AWS_USE_PATH_STYLE_ENDPOINT=false
        echo.
        echo PUSHER_APP_ID=
        echo PUSHER_APP_KEY=
        echo PUSHER_APP_SECRET=
        echo PUSHER_HOST=
        echo PUSHER_PORT=443
        echo PUSHER_SCHEME=https
        echo PUSHER_APP_CLUSTER=mt1
        echo.
        echo VITE_APP_NAME=%%APP_NAME%%
        echo VITE_PUSHER_APP_KEY=%%PUSHER_APP_KEY%%
        echo VITE_PUSHER_HOST=%%PUSHER_HOST%%
        echo VITE_PUSHER_PORT=%%PUSHER_PORT%%
        echo VITE_PUSHER_SCHEME=%%PUSHER_SCHEME%%
        echo VITE_PUSHER_APP_CLUSTER=%%PUSHER_APP_CLUSTER%%
    ) > .env
    echo ✅ .env файл создан
) else (
    echo ℹ️  .env файл уже существует
)

REM Создание необходимых директорий
echo 📁 Создание директорий...
if not exist storage\app\public\photos mkdir storage\app\public\photos
if not exist storage\app\public\passports mkdir storage\app\public\passports
if not exist storage\app\public\certificates mkdir storage\app\public\certificates
if not exist storage\app\public\uploads\people mkdir storage\app\public\uploads\people
if not exist storage\framework\cache mkdir storage\framework\cache
if not exist storage\framework\sessions mkdir storage\framework\sessions
if not exist storage\framework\views mkdir storage\framework\views
if not exist storage\logs mkdir storage\logs
if not exist bootstrap\cache mkdir bootstrap\cache

REM Остановка существующих контейнеров
echo 🛑 Остановка существующих контейнеров...
docker-compose down

REM Сборка и запуск контейнеров
echo 🔨 Сборка и запуск контейнеров...
docker-compose up -d --build

REM Ожидание запуска базы данных
echo ⏳ Ожидание запуска базы данных...
timeout /t 10 /nobreak >nul

REM Проверка статуса контейнеров
echo 📊 Статус контейнеров:
docker-compose ps

REM Выполнение миграций
echo 🗄️  Выполнение миграций...
docker-compose exec -T laravel-app php artisan migrate --force

REM Создание символической ссылки
echo 🔗 Создание символической ссылки для storage...
docker-compose exec -T laravel-app php artisan storage:link

REM Генерация ключа приложения
echo 🔑 Генерация ключа приложения...
docker-compose exec -T laravel-app php artisan key:generate --force

REM Очистка кэша
echo 🧹 Очистка кэша...
docker-compose exec -T laravel-app php artisan config:clear
docker-compose exec -T laravel-app php artisan cache:clear

echo.
echo ✅ Проект успешно запущен!
echo.
echo 🌐 Доступ к приложению: http://localhost:8081
echo 🗄️  База данных: localhost:5432
echo.
echo 📋 Полезные команды:
echo   - Просмотр логов: docker-compose logs -f
echo   - Остановка: docker-compose down
echo   - Перезапуск: docker-compose restart
echo   - Вход в контейнер: docker-compose exec laravel-app bash
echo.
pause
