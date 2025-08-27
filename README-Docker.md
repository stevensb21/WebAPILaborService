# Запуск проекта в Docker

Этот документ описывает, как запустить Laravel проект в Docker окружении.

## Предварительные требования

- Docker
- Docker Compose

## Быстрый старт

### 1. Настройка окружения

```bash
# Сделайте скрипт исполняемым и запустите его
chmod +x docker-setup.sh
./docker-setup.sh
```

### 2. Запуск контейнеров

```bash
# Сборка и запуск всех сервисов
docker-compose up -d --build
```

### 3. Инициализация базы данных

```bash
# Выполнение миграций
docker-compose exec laravel-app php artisan migrate

# Создание символической ссылки для storage
docker-compose exec laravel-app php artisan storage:link

# Опционально: заполнение базы тестовыми данными
docker-compose exec laravel-app php artisan db:seed
```

### 4. Доступ к приложению

Откройте браузер и перейдите по адресу: **http://localhost:8081**

## Структура сервисов

- **laravel-app** (PHP 8.2 + FPM) - основное приложение Laravel
- **laravel-db** (PostgreSQL 17) - база данных
- **laravel-nginx** (Nginx) - веб-сервер

## Полезные команды

### Управление контейнерами

```bash
# Остановка всех контейнеров
docker-compose down

# Просмотр логов
docker-compose logs -f laravel-app

# Вход в контейнер Laravel
docker-compose exec laravel-app bash

# Перезапуск конкретного сервиса
docker-compose restart laravel-app
```

### Работа с Laravel

```bash
# Выполнение Artisan команд
docker-compose exec laravel-app php artisan list

# Очистка кэша
docker-compose exec laravel-app php artisan cache:clear
docker-compose exec laravel-app php artisan config:clear
docker-compose exec laravel-app php artisan route:clear

# Создание нового контроллера
docker-compose exec laravel-app php artisan make:controller TestController

# Просмотр маршрутов
docker-compose exec laravel-app php artisan route:list
```

### Работа с базой данных

```bash
# Подключение к базе данных
docker-compose exec laravel-db psql -U laravel -d laravel

# Создание резервной копии
docker-compose exec laravel-db pg_dump -U laravel laravel > backup.sql

# Восстановление из резервной копии
docker-compose exec -T laravel-db psql -U laravel laravel < backup.sql
```

## Конфигурация

### Переменные окружения

Основные настройки находятся в файле `.env`:

- `DB_HOST=laravel-db` - хост базы данных
- `DB_PORT=5432` - порт базы данных
- `DB_DATABASE=laravel` - имя базы данных
- `DB_USERNAME=laravel` - пользователь базы данных
- `DB_PASSWORD=secret` - пароль базы данных
- `APP_URL=http://localhost:8081` - URL приложения

### Порты

- `8081` - веб-сервер (Nginx)
- `5432` - база данных (PostgreSQL)

## Устранение неполадок

### Проблемы с правами доступа

```bash
# Исправление прав доступа
docker-compose exec laravel-app chown -R www-data:www-data /var/www/html
docker-compose exec laravel-app chmod -R 775 storage bootstrap/cache
```

### Проблемы с базой данных

```bash
# Проверка подключения к базе
docker-compose exec laravel-app php artisan tinker
# В tinker: DB::connection()->getPdo();

# Сброс базы данных
docker-compose exec laravel-app php artisan migrate:fresh --seed
```

### Проблемы с кэшем

```bash
# Очистка всех кэшей
docker-compose exec laravel-app php artisan optimize:clear
```

## Разработка

### Добавление новых пакетов

```bash
# Установка PHP пакетов
docker-compose exec laravel-app composer require package-name

# Установка NPM пакетов (если используется)
docker-compose exec laravel-app npm install package-name
```

### Изменение конфигурации

После изменения конфигурации Laravel перезапустите контейнер:

```bash
docker-compose restart laravel-app
```

## Продакшн

Для продакшн окружения рекомендуется:

1. Изменить `APP_ENV=production`
2. Отключить `APP_DEBUG=false`
3. Настроить SSL сертификаты
4. Использовать внешнюю базу данных
5. Настроить мониторинг и логирование
