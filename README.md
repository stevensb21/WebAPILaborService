# WebAPI Labor Service

Laravel API для управления трудовыми ресурсами и сертификатами.

## 🚀 Быстрый старт

### Локальная разработка

```bash
# Клонирование репозитория
git clone https://github.com/your-username/WebAPILaborService.git
cd WebAPILaborService

# Запуск в Docker (Windows)
quick-start.bat

# Или вручную
docker-compose up -d
docker-compose exec laravel-app php artisan migrate
docker-compose exec laravel-app php artisan storage:link
docker-compose exec laravel-app php artisan key:generate
```

### Доступ к приложению

- **Локально**: http://localhost:8081
- **API документация**: http://localhost:8081/api-test.html

## 📋 Функциональность

- Управление персоналом
- Управление сертификатами
- API для интеграции
- Загрузка файлов
- Система отчетов

## 🛠 Технологии

- **Backend**: Laravel 12, PHP 8.2
- **База данных**: PostgreSQL 17
- **Веб-сервер**: Nginx
- **Контейнеризация**: Docker & Docker Compose

## 📚 Документация

- [Быстрый старт](QUICK_START.md)
- [API документация](API_DOCUMENTATION.md)
- [Примеры API](API_EXAMPLES.md)
- [Развертывание на сервере](DEPLOYMENT.md)

## 🚀 Развертывание на сервере

### Подготовка сервера

1. Установите Docker и Docker Compose
2. Клонируйте репозиторий
3. Настройте переменные окружения
4. Добавьте SSL сертификаты

### Автоматическое развертывание

```bash
# Настройка конфигурации
cp env.production.example .env
# Отредактируйте .env файл

# Запуск развертывания
chmod +x deploy.sh
./deploy.sh
```

### Ручное развертывание

```bash
docker-compose -f docker-compose.prod.yml up -d --build
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan storage:link
docker-compose -f docker-compose.prod.yml exec -T laravel-app php artisan key:generate --force
```

## 📁 Структура проекта

```
WebAPILaborService/
├── app/
│   ├── Http/Controllers/Api/     # API контроллеры
│   └── Models/                   # Модели данных
├── database/
│   ├── migrations/               # Миграции БД
│   └── seeders/                  # Сидеры данных
├── routes/
│   └── api.php                   # API маршруты
├── storage/                      # Файлы приложения
├── docker-compose.yml            # Docker Compose (разработка)
├── docker-compose.prod.yml       # Docker Compose (продакшн)
├── Dockerfile                    # Docker образ (разработка)
├── Dockerfile.prod               # Docker образ (продакшн)
├── deploy.sh                     # Скрипт развертывания
└── README.md                     # Документация
```

## 🔧 Управление

### Локальная разработка

```bash
# Запуск
docker-compose up -d

# Остановка
docker-compose down

# Логи
docker-compose logs -f

# Вход в контейнер
docker-compose exec laravel-app bash
```

### Продакшн

```bash
# Запуск
docker-compose -f docker-compose.prod.yml up -d

# Остановка
docker-compose -f docker-compose.prod.yml down

# Логи
docker-compose -f docker-compose.prod.yml logs -f

# Обновление
git pull origin main
docker-compose -f docker-compose.prod.yml up -d --build
```

## 🔒 Безопасность

- HTTPS обязателен в продакшн
- Настройте файрвол
- Регулярно обновляйте систему
- Мониторьте логи

## 📊 Мониторинг

```bash
# Статус контейнеров
docker-compose ps

# Использование ресурсов
docker stats

# Логи приложения
docker-compose logs -f laravel-app
```

## 🆘 Поддержка

При возникновении проблем:

1. Проверьте логи: `docker-compose logs -f`
2. Убедитесь, что все переменные окружения настроены
3. Проверьте права доступа к файлам
4. Очистите кэш: `php artisan optimize:clear`

## 📄 Лицензия

MIT License
