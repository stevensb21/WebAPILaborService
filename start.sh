#!/bin/bash

echo "🚀 Запуск WebAPI Labor Service в Docker..."

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

# Настройка окружения
echo "📝 Настройка окружения..."
./docker-setup.sh

# Остановка существующих контейнеров
echo "🛑 Остановка существующих контейнеров..."
docker-compose down

# Сборка и запуск контейнеров
echo "🔨 Сборка и запуск контейнеров..."
docker-compose up -d --build

# Ожидание запуска базы данных
echo "⏳ Ожидание запуска базы данных..."
sleep 10

# Проверка статуса контейнеров
echo "📊 Статус контейнеров:"
docker-compose ps

# Выполнение миграций
echo "🗄️  Выполнение миграций..."
docker-compose exec -T laravel-app php artisan migrate --force

# Создание символической ссылки
echo "🔗 Создание символической ссылки для storage..."
docker-compose exec -T laravel-app php artisan storage:link

# Генерация ключа приложения
echo "🔑 Генерация ключа приложения..."
docker-compose exec -T laravel-app php artisan key:generate --force

# Очистка кэша
echo "🧹 Очистка кэша..."
docker-compose exec -T laravel-app php artisan config:clear
docker-compose exec -T laravel-app php artisan cache:clear

echo ""
echo "✅ Проект успешно запущен!"
echo ""
echo "🌐 Доступ к приложению: http://localhost:8081"
echo "🗄️  База данных: localhost:5432"
echo ""
echo "📋 Полезные команды:"
echo "  - Просмотр логов: docker-compose logs -f"
echo "  - Остановка: docker-compose down"
echo "  - Перезапуск: docker-compose restart"
echo "  - Вход в контейнер: docker-compose exec laravel-app bash"
