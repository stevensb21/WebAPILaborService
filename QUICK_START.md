# 🚀 Быстрый запуск WebAPI Labor Service

## Предварительные требования

- Docker Desktop
- Docker Compose

## Запуск проекта

### Вариант 1: Автоматический запуск (Windows)
```bash
# Двойной клик на файл или выполнение в командной строке
quick-start.bat
```

### Вариант 2: Ручной запуск
```bash
# 1. Запуск контейнеров
docker-compose up -d

# 2. Выполнение миграций (только при первом запуске)
docker-compose exec laravel-app php artisan migrate

# 3. Создание символической ссылки (только при первом запуске)
docker-compose exec laravel-app php artisan storage:link

# 4. Генерация ключа приложения (только при первом запуске)
docker-compose exec laravel-app php artisan key:generate
```

## Доступ к приложению

- **Веб-интерфейс**: http://localhost:8081
- **API документация**: http://localhost:8081/api-test.html
- **База данных**: localhost:5432

## Полезные команды

```bash
# Просмотр статуса контейнеров
docker-compose ps

# Просмотр логов
docker-compose logs -f

# Остановка проекта
docker-compose down

# Перезапуск
docker-compose restart

# Вход в контейнер Laravel
docker-compose exec laravel-app bash

# Выполнение Artisan команд
docker-compose exec laravel-app php artisan list
```

## Структура проекта

- **laravel-app**: PHP 8.2 + Laravel приложение
- **laravel-db**: PostgreSQL 17 база данных
- **laravel-nginx**: Nginx веб-сервер

## Устранение неполадок

### Проблемы с правами доступа
```bash
docker-compose exec laravel-app chown -R www-data:www-data /var/www/html
```

### Очистка кэша
```bash
docker-compose exec laravel-app php artisan cache:clear
docker-compose exec laravel-app php artisan config:clear
```

### Сброс базы данных
```bash
docker-compose exec laravel-app php artisan migrate:fresh --seed
```

## Остановка проекта

```bash
docker-compose down
```

Для полного удаления с данными:
```bash
docker-compose down -v
```
