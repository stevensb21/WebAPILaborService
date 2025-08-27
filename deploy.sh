#!/bin/bash

echo "🚀 Развертывание WebAPI Labor Service на сервере..."

# Проверка наличия Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker не установлен. Установите Docker и попробуйте снова."
    exit 1
fi

# Проверка наличия Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose не установлен. Установите Docker Compose и попробуйте снова."
    exit 1
fi

# Проверка наличия .env файла
if [ ! -f .env ]; then
    echo "📝 Создание .env файла из примера..."
    cp env.production.example .env
    echo "⚠️  Пожалуйста, отредактируйте .env файл с вашими настройками и запустите скрипт снова."
    exit 1
fi

# Остановка существующих контейнеров
echo "🛑 Остановка существующих контейнеров..."
docker-compose -f docker-compose.prod.yml down

# Удаление старых образов
echo "🧹 Очистка старых образов..."
docker system prune -f

# Сборка и запуск контейнеров
echo "🔨 Сборка и запуск контейнеров..."
docker-compose -f docker-compose.prod.yml up -d --build

# Ожидание запуска базы данных
echo "⏳ Ожидание запуска базы данных..."
sleep 15

# Проверка статуса контейнеров
echo "📊 Статус контейнеров:"
docker-compose -f docker-compose.prod.yml ps

# Выполнение миграций
echo "🗄️  Выполнение миграций..."
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan migrate --force

# Создание символической ссылки
echo "🔗 Создание символической ссылки для storage..."
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan storage:link

# Генерация ключа приложения
echo "🔑 Генерация ключа приложения..."
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan key:generate --force

# Очистка и кэширование для продакшн
echo "⚡ Оптимизация для продакшн..."
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan view:cache

# Установка прав доступа
echo "🔐 Установка прав доступа..."
docker-compose -f docker-compose.prod.yml exec -T laravel-app chown -R www-data:www-data /var/www/html
docker-compose -f docker-compose.prod.yml exec -T laravel-app chmod -R 775 storage bootstrap/cache

echo ""
echo "✅ Развертывание завершено!"
echo ""
echo "🌐 Доступ к приложению: https://your-domain.com"
echo "📚 API документация: https://your-domain.com/api-test.html"
echo ""
echo "📋 Полезные команды:"
echo "  - Просмотр логов: docker-compose -f docker-compose.prod.yml logs -f"
echo "  - Остановка: docker-compose -f docker-compose.prod.yml down"
echo "  - Перезапуск: docker-compose -f docker-compose.prod.yml restart"
echo "  - Вход в контейнер: docker-compose -f docker-compose.prod.yml exec laravel-app bash"
