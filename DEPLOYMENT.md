# 🚀 Развертывание на сервере

Этот документ описывает процесс развертывания WebAPI Labor Service на продакшн сервере.

## Предварительные требования

### На сервере должно быть установлено:
- Docker
- Docker Compose
- Git
- SSL сертификаты (для HTTPS)

### Рекомендуемые характеристики сервера:
- **CPU**: 2+ ядра
- **RAM**: 4+ GB
- **Диск**: 20+ GB свободного места
- **ОС**: Ubuntu 20.04+ / CentOS 8+ / Debian 11+

## Подготовка сервера

### 1. Установка Docker и Docker Compose

```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Установка Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Создание рабочей директории

```bash
mkdir -p /var/www/webapi-labor
cd /var/www/webapi-labor
```

## Развертывание через Git

### 1. Клонирование репозитория

```bash
git clone https://github.com/your-username/WebAPILaborService.git .
```

### 2. Настройка переменных окружения

```bash
# Копирование примера конфигурации
cp env.production.example .env

# Редактирование конфигурации
nano .env
```

**Важные настройки в .env:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secure_password
```

### 3. Настройка SSL сертификатов

```bash
# Создание директории для SSL
mkdir -p ssl

# Копирование ваших сертификатов
cp /path/to/your/cert.pem ssl/
cp /path/to/your/key.pem ssl/
```

### 4. Запуск развертывания

```bash
# Сделать скрипт исполняемым
chmod +x deploy.sh

# Запуск развертывания
./deploy.sh
```

## Ручное развертывание

Если автоматический скрипт не подходит, выполните команды вручную:

```bash
# 1. Остановка существующих контейнеров
docker-compose -f docker-compose.prod.yml down

# 2. Сборка и запуск
docker-compose -f docker-compose.prod.yml up -d --build

# 3. Ожидание запуска базы данных
sleep 15

# 4. Выполнение миграций
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan migrate --force

# 5. Создание символической ссылки
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan storage:link

# 6. Генерация ключа приложения
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan key:generate --force

# 7. Оптимизация для продакшн
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan view:cache

# 8. Установка прав доступа
docker-compose -f docker-compose.prod.yml exec -T laravel-app chown -R www-data:www-data /var/www/html
docker-compose -f docker-compose.prod.yml exec -T laravel-app chmod -R 775 storage bootstrap/cache
```

## Обновление приложения

### 1. Получение обновлений

```bash
git pull origin main
```

### 2. Пересборка и перезапуск

```bash
# Остановка контейнеров
docker-compose -f docker-compose.prod.yml down

# Пересборка с новым кодом
docker-compose -f docker-compose.prod.yml up -d --build

# Выполнение миграций (если есть новые)
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan migrate --force

# Очистка кэша
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan optimize:clear
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan view:cache
```

## Мониторинг и логирование

### Просмотр логов

```bash
# Логи всех сервисов
docker-compose -f docker-compose.prod.yml logs -f

# Логи конкретного сервиса
docker-compose -f docker-compose.prod.yml logs -f laravel-app
docker-compose -f docker-compose.prod.yml logs -f laravel-nginx
docker-compose -f docker-compose.prod.yml logs -f laravel-db
```

### Проверка статуса

```bash
# Статус контейнеров
docker-compose -f docker-compose.prod.yml ps

# Использование ресурсов
docker stats
```

## Резервное копирование

### Создание резервной копии базы данных

```bash
# Создание бэкапа
docker-compose -f docker-compose.prod.yml exec -T laravel-db pg_dump -U $DB_USERNAME $DB_DATABASE > backup_$(date +%Y%m%d_%H%M%S).sql

# Восстановление из бэкапа
docker-compose -f docker-compose.prod.yml exec -T laravel-db psql -U $DB_USERNAME $DB_DATABASE < backup_file.sql
```

### Резервное копирование файлов

```bash
# Копирование storage директории
tar -czf storage_backup_$(date +%Y%m%d_%H%M%S).tar.gz storage/

# Копирование .env файла
cp .env .env.backup
```

## Безопасность

### Рекомендации по безопасности:

1. **Измените пароли по умолчанию**
2. **Используйте HTTPS**
3. **Настройте файрвол**
4. **Регулярно обновляйте систему**
5. **Мониторьте логи на предмет подозрительной активности**

### Настройка файрвола (Ubuntu)

```bash
# Установка UFW
sudo apt install ufw

# Настройка правил
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

## Устранение неполадок

### Проблемы с подключением к базе данных

```bash
# Проверка подключения
docker-compose -f docker-compose.prod.yml exec laravel-app php artisan tinker
# В tinker: DB::connection()->getPdo();
```

### Проблемы с правами доступа

```bash
# Исправление прав
docker-compose -f docker-compose.prod.yml exec laravel-app chown -R www-data:www-data /var/www/html
docker-compose -f docker-compose.prod.yml exec laravel-app chmod -R 775 storage bootstrap/cache
```

### Проблемы с SSL

```bash
# Проверка SSL сертификатов
openssl x509 -in ssl/cert.pem -text -noout
```

### Очистка кэша

```bash
# Очистка всех кэшей
docker-compose -f docker-compose.prod.yml exec laravel-app php artisan optimize:clear
```

## Полезные команды

```bash
# Вход в контейнер Laravel
docker-compose -f docker-compose.prod.yml exec laravel-app bash

# Выполнение Artisan команд
docker-compose -f docker-compose.prod.yml exec laravel-app php artisan list

# Просмотр маршрутов
docker-compose -f docker-compose.prod.yml exec laravel-app php artisan route:list

# Очистка неиспользуемых образов
docker system prune -a

# Просмотр использования диска
docker system df
```

## Автоматическое развертывание (CI/CD)

Для автоматического развертывания можно настроить GitHub Actions или GitLab CI:

### GitHub Actions пример (.github/workflows/deploy.yml)

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Deploy to server
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.KEY }}
          script: |
            cd /var/www/webapi-labor
            git pull origin main
            ./deploy.sh
```

## Контакты и поддержка

При возникновении проблем:
1. Проверьте логи: `docker-compose -f docker-compose.prod.yml logs -f`
2. Убедитесь, что все переменные окружения настроены правильно
3. Проверьте, что порты 80 и 443 открыты
4. Убедитесь, что SSL сертификаты корректны
