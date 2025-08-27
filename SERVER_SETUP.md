# 🚀 Быстрая настройка сервера

## Шаг 1: Подготовка сервера

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Установка Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Перезагрузка для применения изменений
sudo reboot
```

## Шаг 2: Клонирование проекта

```bash
# Создание директории
mkdir -p /var/www/webapi-labor
cd /var/www/webapi-labor

# Клонирование репозитория
git clone https://github.com/your-username/WebAPILaborService.git .
```

## Шаг 3: Настройка конфигурации

```bash
# Копирование конфигурации
cp env.production.example .env

# Редактирование конфигурации
nano .env
```

**Обязательные настройки в .env:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_DATABASE=webapi_labor
DB_USERNAME=webapi_user
DB_PASSWORD=your_secure_password
```

## Шаг 4: Настройка SSL (опционально)

```bash
# Создание директории для SSL
mkdir -p ssl

# Если у вас есть сертификаты, скопируйте их:
# cp /path/to/cert.pem ssl/
# cp /path/to/key.pem ssl/

# Или создайте самоподписанные сертификаты:
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout ssl/key.pem -out ssl/cert.pem
```

## Шаг 5: Запуск приложения

```bash
# Сделать скрипт исполняемым
chmod +x deploy.sh

# Запуск развертывания
./deploy.sh
```

## Шаг 6: Проверка работы

```bash
# Проверка статуса контейнеров
docker-compose -f docker-compose.prod.yml ps

# Проверка логов
docker-compose -f docker-compose.prod.yml logs -f
```

## Доступ к приложению

- **HTTP**: http://your-server-ip
- **HTTPS**: https://your-domain.com (если настроен SSL)

## Обновление приложения

```bash
# Получение обновлений
git pull origin main

# Перезапуск с новым кодом
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d --build
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan migrate --force
```

## Полезные команды

```bash
# Просмотр логов
docker-compose -f docker-compose.prod.yml logs -f

# Остановка приложения
docker-compose -f docker-compose.prod.yml down

# Вход в контейнер
docker-compose -f docker-compose.prod.yml exec laravel-app bash

# Резервное копирование БД
docker-compose -f docker-compose.prod.yml exec -T laravel-db pg_dump -U $DB_USERNAME $DB_DATABASE > backup.sql
```
