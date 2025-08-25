 # 🚀 Быстрый старт с API

## 📋 Предварительные требования

1. **Laravel сервер запущен:**
   ```bash
   cd my-laravel-app
   php artisan serve
   ```

2. **База данных настроена и миграции выполнены:**
   ```bash
   php artisan migrate
   ```

3. **PostgreSQL сервер запущен**

## 🧪 Тестирование API

### Автоматическое тестирование
```bash
cd my-laravel-app
php test_api.php
```

### Ручное тестирование с cURL

#### 1. Получить список людей
```bash
curl -X GET "http://127.0.0.1:8000/api/people" \
  -H "Accept: application/json"
```

#### 2. Создать человека
```bash
curl -X POST "http://127.0.0.1:8000/api/people" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Тестовый Сотрудник",
    "position": "Разработчик",
    "phone": "+7-999-000-00-00",
    "status": "Активный"
  }'
```

#### 3. Получить отчет по просроченным сертификатам
```bash
curl -X GET "http://127.0.0.1:8000/api/reports/expired-certificates" \
  -H "Accept: application/json"
```

## 📚 Документация

- **Полная документация:** `API_DOCUMENTATION.md`
- **Примеры кода:** `API_EXAMPLES.md`

## 🔗 Основные эндпоинты

| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/api/people` | Список людей |
| POST | `/api/people` | Создать человека |
| POST | `/api/people/bulk` | Массовое создание |
| GET | `/api/people/{id}` | Получить человека |
| PUT | `/api/people/{id}` | Обновить человека |
| DELETE | `/api/people/{id}` | Удалить человека |
| GET | `/api/certificates` | Список сертификатов |
| POST | `/api/certificates` | Создать сертификат |
| GET | `/api/reports/expired-certificates` | Просроченные сертификаты |
| GET | `/api/reports/expiring-soon` | Скоро истекающие |
| GET | `/api/reports/people-status` | Статусы работников |

## 🛠️ Интеграция

### JavaScript
```javascript
const response = await fetch('http://127.0.0.1:8000/api/people');
const data = await response.json();
console.log(data.data);
```

### Python
```python
import requests
response = requests.get('http://127.0.0.1:8000/api/people')
data = response.json()
print(data['data'])
```

### PHP
```php
$response = file_get_contents('http://127.0.0.1:8000/api/people');
$data = json_decode($response, true);
print_r($data['data']);
```

## ⚠️ Устранение неполадок

### Ошибка подключения
- Убедитесь, что Laravel сервер запущен: `php artisan serve`
- Проверьте URL: `http://127.0.0.1:8000/api`

### Ошибка базы данных
- Проверьте подключение к PostgreSQL
- Выполните миграции: `php artisan migrate`

### Ошибка 404
- Проверьте правильность URL
- Убедитесь, что маршруты зарегистрированы в `routes/api.php`

### Ошибка 422 (валидация)
- Проверьте формат данных в запросе
- Убедитесь, что обязательные поля заполнены

## 📞 Поддержка

При возникновении проблем:
1. Проверьте логи: `storage/logs/laravel.log`
2. Запустите тесты: `php test_api.php`
3. Проверьте статус сервера: `php artisan serve`

---

*API готов к использованию! 🎉*