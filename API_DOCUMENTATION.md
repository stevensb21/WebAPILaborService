 # API Документация - Система управления сертификатами безопасности

## 📋 Содержание
1. [Общая информация](#общая-информация)
2. [Аутентификация](#аутентификация)
3. [Коды ответов](#коды-ответов)
4. [People API](#people-api)
5. [Certificates API](#certificates-api)
6. [Reports API](#reports-api)
7. [Примеры использования](#примеры-использования)
8. [Обработка ошибок](#обработка-ошибок)

---

## 🔗 Общая информация

### Базовый URL
```
http://127.0.0.1:8000/api
```

### Формат ответов
Все ответы API возвращаются в формате JSON со следующей структурой:

**Успешный ответ:**
```json
{
    "success": true,
    "data": {...},
    "message": "Операция выполнена успешно"
}
```

**Ответ с ошибкой:**
```json
{
    "success": false,
    "message": "Описание ошибки",
    "error": "Детали ошибки"
}
```

### Заголовки запросов
```http
Content-Type: application/json
Accept: application/json
```

---

## 🔐 Аутентификация

В текущей версии API аутентификация не требуется. Все эндпоинты доступны публично.

---

## 📊 Коды ответов

| Код | Описание |
|-----|----------|
| 200 | Успешный запрос |
| 201 | Ресурс создан |
| 400 | Неверный запрос |
| 404 | Ресурс не найден |
| 422 | Ошибка валидации |
| 500 | Внутренняя ошибка сервера |

---

## 👥 People API

### Получить список людей

**GET** `/api/people`

Получает список всех людей с возможностью фильтрации и пагинации.

#### Параметры запроса

| Параметр | Тип | Описание | Пример |
|----------|-----|----------|--------|
| `search_fio` | string | Поиск по ФИО (регистронезависимый) | `?search_fio=иванов` |
| `search_position` | string | Поиск по должности | `?search_position=инженер` |
| `search_phone` | string | Поиск по телефону | `?search_phone=999` |
| `search_status` | string | Поиск по статусу работника | `?search_status=активный` |
| `per_page` | integer | Количество записей на странице (по умолчанию 20) | `?per_page=10` |
| `page` | integer | Номер страницы | `?page=2` |

#### Пример запроса
```bash
curl --noproxy "*" -X GET "http://127.0.0.1:8000/api/people?search_fio=иванов&per_page=5" \
  -H "Accept: application/json"
```

#### Пример ответа
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "full_name": "Иванов Иван Иванович",
            "position": "Инженер",
            "phone": "+7-999-123-45-67",
            "snils": "123-456-789-01",
            "inn": "123456789012",
            "birth_date": "1990-01-01",
            "address": "г. Москва, ул. Примерная, д. 1",
            "status": "Активный",
            "created_at": "2025-08-25T10:00:00.000000Z",
            "updated_at": "2025-08-25T10:00:00.000000Z",
            "certificates": [
                {
                    "id": 1,
                    "name": "Электробезопасность",
                    "description": "Допуск к работе с электроустановками",
                    "expiry_date": 3,
                    "pivot": {
                        "assigned_date": "2023-06-15",
                        "certificate_number": "ЭБ-001",
                        "status": 4,
                        "notes": "Действующий сертификат"
                    }
                }
            ]
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 5,
        "total": 15
    }
}
```

### Получить конкретного человека

**GET** `/api/people/{id}`

Получает детальную информацию о конкретном человеке.

#### Параметры пути

| Параметр | Тип | Описание |
|----------|-----|----------|
| `id` | integer | ID человека |

#### Пример запроса
```bash
curl --noproxy "*" -X GET "http://127.0.0.1:8000/api/people/1" \
  -H "Accept: application/json"
```

#### Пример ответа
```json
{
    "success": true,
    "data": {
        "id": 1,
        "full_name": "Иванов Иван Иванович",
        "position": "Инженер",
        "phone": "+7-999-123-45-67",
        "snils": "123-456-789-01",
        "inn": "123456789012",
        "birth_date": "1990-01-01",
        "address": "г. Москва, ул. Примерная, д. 1",
        "status": "Активный",
        "photo": "photos/1755693502_w1.jpg",
        "passport_page_1": "passports/1755693502_passport1_doc1.pdf",
        "passport_page_5": "passports/1755693502_passport5_doc5.pdf",
        "certificates_file": "certificates/1755693502_certificates_all.pdf",
        "created_at": "2025-08-25T10:00:00.000000Z",
        "updated_at": "2025-08-25T10:00:00.000000Z",
        "certificates": [
            {
                "id": 1,
                "name": "Электробезопасность",
                "description": "Допуск к работе с электроустановками",
                "expiry_date": 3,
                "pivot": {
                    "assigned_date": "2023-06-15",
                    "certificate_number": "ЭБ-001",
                    "status": 4,
                    "notes": "Действующий сертификат"
                }
            }
        ]
    }
}
```

### Добавить человека

**POST** `/api/people`

Создает нового человека в системе.

#### Тело запроса

| Поле | Тип | Обязательное | Описание | Пример |
|------|-----|--------------|----------|--------|
| `full_name` | string | ✅ | ФИО человека | `"Иванов Иван Иванович"` |
| `position` | string | ❌ | Должность | `"Инженер"` |
| `phone` | string | ❌ | Номер телефона | `"+7-999-123-45-67"` |
| `snils` | string | ❌ | СНИЛС | `"123-456-789-01"` |
| `inn` | string | ❌ | ИНН | `"123456789012"` |
| `birth_date` | date | ❌ | Дата рождения (YYYY-MM-DD) | `"1990-01-01"` |
| `address` | string | ❌ | Адрес | `"г. Москва, ул. Примерная, д. 1"` |
| `status` | string | ❌ | Статус работника | `"Активный"` |

#### Пример запроса
```bash
curl --noproxy "*" -X POST "http://127.0.0.1:8000/api/people" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Петров Петр Петрович",
    "position": "Техник",
    "phone": "+7-999-987-65-43",
    "snils": "987-654-321-09",
    "inn": "987654321098",
    "birth_date": "1985-05-15",
    "address": "г. Санкт-Петербург, пр. Невский, д. 100",
    "status": "Активный"
  }'
```

#### Пример ответа
```json
{
    "success": true,
    "message": "Человек успешно добавлен",
    "data": {
        "id": 2,
        "full_name": "Петров Петр Петрович",
        "position": "Техник",
        "phone": "+7-999-987-65-43",
        "snils": "987-654-321-09",
        "inn": "987654321098",
        "birth_date": "1985-05-15",
        "address": "г. Санкт-Петербург, пр. Невский, д. 100",
        "status": "Активный",
        "created_at": "2025-08-25T11:30:00.000000Z",
        "updated_at": "2025-08-25T11:30:00.000000Z"
    }
}
```

### Массовое добавление людей

**POST** `/api/people/bulk`

Добавляет несколько людей одновременно.

#### Тело запроса

```json
{
    "people": [
        {
            "full_name": "Сидоров Сидор Сидорович",
            "position": "Мастер",
            "phone": "+7-999-111-22-33",
            "status": "Активный"
        },
        {
            "full_name": "Козлов Козел Козлович",
            "position": "Рабочий",
            "phone": "+7-999-444-55-66",
            "status": "В отпуске"
        }
    ]
}
```

#### Пример запроса
```bash
curl --noproxy "*" -X POST "http://127.0.0.1:8000/api/people/bulk" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "people": [
        {
            "full_name": "Сидоров Сидор Сидорович",
            "position": "Мастер",
            "phone": "+7-999-111-22-33",
            "status": "Активный"
        },
        {
            "full_name": "Козлов Козел Козлович",
            "position": "Рабочий",
            "phone": "+7-999-444-55-66",
            "status": "В отпуске"
        }
    ]
}'
```

#### Пример ответа
```json
{
    "success": true,
    "message": "Массовое добавление завершено",
    "data": {
        "created": 2,
        "errors": 0,
        "people": [
            {
                "id": 3,
                "full_name": "Сидоров Сидор Сидорович",
                "position": "Мастер",
                "phone": "+7-999-111-22-33",
                "status": "Активный"
            },
            {
                "id": 4,
                "full_name": "Козлов Козел Козлович",
                "position": "Рабочий",
                "phone": "+7-999-444-55-66",
                "status": "В отпуске"
            }
        ],
        "error_details": []
    }
}
```

### Обновить человека

**PUT** `/api/people/{id}`

Обновляет информацию о существующем человеке.

#### Параметры пути

| Параметр | Тип | Описание |
|----------|-----|----------|
| `id` | integer | ID человека |

#### Тело запроса
Аналогично созданию, но все поля необязательные.

#### Пример запроса
```bash
curl --noproxy "*" -X PUT "http://127.0.0.1:8000/api/people/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "position": "Старший инженер",
    "status": "В отпуске"
  }'
```

#### Пример ответа
```json
{
    "success": true,
    "message": "Данные человека успешно обновлены",
    "data": {
        "id": 1,
        "full_name": "Иванов Иван Иванович",
        "position": "Старший инженер",
        "phone": "+7-999-123-45-67",
        "status": "В отпуске",
        "updated_at": "2025-08-25T12:00:00.000000Z"
    }
}
```

### Удалить человека

**DELETE** `/api/people/{id}`

Удаляет человека и все связанные с ним файлы.

#### Параметры пути

| Параметр | Тип | Описание |
|----------|-----|----------|
| `id` | integer | ID человека |

#### Пример запроса
```bash
curl --noproxy "*" -X DELETE "http://127.0.0.1:8000/api/people/1" \
  -H "Accept: application/json"
```

#### Пример ответа
```json
{
    "success": true,
    "message": "Человек успешно удален"
}
```

---

## 📜 Certificates API

### Получить список сертификатов

**GET** `/api/certificates`

Получает список всех сертификатов с возможностью фильтрации.

#### Параметры запроса

| Параметр | Тип | Описание | Пример |
|----------|-----|----------|--------|
| `search_name` | string | Поиск по названию | `?search_name=электро` |
| `search_description` | string | Поиск по описанию | `?search_description=допуск` |
| `per_page` | integer | Количество записей на странице | `?per_page=10` |

#### Пример запроса
```bash
curl --noproxy "*" -X GET "http://127.0.0.1:8000/api/certificates?search_name=электро" \
  -H "Accept: application/json"
```

#### Пример ответа
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Электробезопасность",
            "description": "Допуск к работе с электроустановками",
            "expiry_date": 3,
            "created_at": "2025-08-25T10:00:00.000000Z",
            "updated_at": "2025-08-25T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 1
    }
}
```

### Получить конкретный сертификат

**GET** `/api/certificates/{id}`

Получает информацию о конкретном сертификате.

#### Пример запроса
```bash
curl --noproxy "*" -X GET "http://127.0.0.1:8000/api/certificates/1" \
  -H "Accept: application/json"
```

### Добавить сертификат

**POST** `/api/certificates`

Создает новый сертификат.

#### Тело запроса

| Поле | Тип | Обязательное | Описание | Пример |
|------|-----|--------------|----------|--------|
| `name` | string | ✅ | Название сертификата | `"Электробезопасность"` |
| `description` | string | ❌ | Описание сертификата | `"Допуск к работе с электроустановками"` |
| `expiry_date` | integer | ✅ | Срок действия в годах (1-10) | `3` |

#### Пример запроса
```bash
curl --noproxy "*" -X POST "http://127.0.0.1:8000/api/certificates" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Пожарная безопасность",
    "description": "Обучение правилам пожарной безопасности",
    "expiry_date": 2
  }'
```

### Обновить сертификат

**PUT** `/api/certificates/{id}`

Обновляет информацию о сертификате.

#### Пример запроса
```bash
curl --noproxy "*" -X PUT "http://127.0.0.1:8000/api/certificates/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "expiry_date": 5
  }'
```

### Удалить сертификат

**DELETE** `/api/certificates/{id}`

Удаляет сертификат (только если он не используется людьми).

#### Пример запроса
```bash
curl --noproxy "*" -X DELETE "http://127.0.0.1:8000/api/certificates/1" \
  -H "Accept: application/json"
```

---

## 📊 Reports API

### Отчет по просроченным сертификатам

**GET** `/api/reports/expired-certificates`

Получает список всех просроченных сертификатов.

#### Параметры запроса

| Параметр | Тип | Описание | Пример |
|----------|-----|----------|--------|
| `certificate_id` | integer | Фильтр по конкретному сертификату | `?certificate_id=1` |

#### Пример запроса
```bash
curl --noproxy "*" -X GET "http://127.0.0.1:8000/api/reports/expired-certificates" \
  -H "Accept: application/json"
```

#### Пример ответа
```json
{
    "success": true,
    "data": [
        {
            "person_id": 1,
            "person_name": "Иванов Иван Иванович",
            "person_position": "Инженер",
            "certificate_id": 1,
            "certificate_name": "Электробезопасность",
            "assigned_date": "2023-06-15",
            "expiry_date": "2024-06-15",
            "days_expired": 433,
            "certificate_number": "ЭБ-001"
        }
    ],
    "total": 1,
    "generated_at": "2025-08-25 12:30:00"
}
```

### Отчет по скоро истекающим сертификатам

**GET** `/api/reports/expiring-soon`

Получает список сертификатов, срок действия которых истекает в течение 2 месяцев.

#### Пример запроса
```bash
curl --noproxy "*" -X GET "http://127.0.0.1:8000/api/reports/expiring-soon" \
  -H "Accept: application/json"
```

#### Пример ответа
```json
{
    "success": true,
    "data": [
        {
            "person_id": 2,
            "person_name": "Петров Петр Петрович",
            "person_position": "Техник",
            "certificate_id": 2,
            "certificate_name": "Пожарная безопасность",
            "assigned_date": "2023-09-15",
            "expiry_date": "2025-10-15",
            "days_until_expiry": 45,
            "certificate_number": "ПБ-002"
        }
    ],
    "total": 1,
    "generated_at": "2025-08-25 12:30:00"
}
```

### Отчет по статусам работников

**GET** `/api/reports/people-status`

Получает статистику по статусам работников.

#### Параметры запроса

| Параметр | Тип | Описание | Пример |
|----------|-----|----------|--------|
| `status` | string | Фильтр по статусу | `?status=Активный` |

#### Пример запроса
```bash
curl --noproxy "*" -X GET "http://127.0.0.1:8000/api/reports/people-status" \
  -H "Accept: application/json"
```

#### Пример ответа
```json
{
    "success": true,
    "data": {
        "total_people": 4,
        "status_distribution": {
            "Активный": 2,
            "В отпуске": 1,
            "Уволен": 1
        },
        "people_by_status": {
            "Активный": [
                {
                    "id": 1,
                    "full_name": "Иванов Иван Иванович",
                    "position": "Инженер",
                    "phone": "+7-999-123-45-67"
                },
                {
                    "id": 2,
                    "full_name": "Петров Петр Петрович",
                    "position": "Техник",
                    "phone": "+7-999-987-65-43"
                }
            ],
            "В отпуске": [
                {
                    "id": 3,
                    "full_name": "Сидоров Сидор Сидорович",
                    "position": "Мастер",
                    "phone": "+7-999-111-22-33"
                }
            ],
            "Уволен": [
                {
                    "id": 4,
                    "full_name": "Козлов Козел Козлович",
                    "position": "Рабочий",
                    "phone": "+7-999-444-55-66"
                }
            ]
        }
    },
    "generated_at": "2025-08-25 12:30:00"
}
```

---

## 💡 Примеры использования

### JavaScript (Fetch API)

#### Получить список людей
```javascript
async function getPeople() {
    try {
        const response = await fetch('http://127.0.0.1:8000/api/people?per_page=10');
        const data = await response.json();
        
        if (data.success) {
            console.log('Люди:', data.data);
            console.log('Пагинация:', data.pagination);
        } else {
            console.error('Ошибка:', data.message);
        }
    } catch (error) {
        console.error('Ошибка запроса:', error);
    }
}
```

#### Добавить человека
```javascript
async function addPerson(personData) {
    try {
        const response = await fetch('http://127.0.0.1:8000/api/people', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(personData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Человек добавлен:', data.data);
        } else {
            console.error('Ошибка:', data.message);
        }
    } catch (error) {
        console.error('Ошибка запроса:', error);
    }
}

// Использование
addPerson({
    full_name: 'Новый Сотрудник',
    position: 'Разработчик',
    phone: '+7-999-000-00-00',
    status: 'Активный'
});
```

#### Массовое добавление
```javascript
async function addMultiplePeople(peopleArray) {
    try {
        const response = await fetch('http://127.0.0.1:8000/api/people/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ people: peopleArray })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`Добавлено: ${data.data.created}`);
            console.log(`Ошибок: ${data.data.errors}`);
        } else {
            console.error('Ошибка:', data.message);
        }
    } catch (error) {
        console.error('Ошибка запроса:', error);
    }
}
```

#### Получить отчет
```javascript
async function getExpiredCertificates() {
    try {
        const response = await fetch('http://127.0.0.1:8000/api/reports/expired-certificates');
        const data = await response.json();
        
        if (data.success) {
            console.log('Просроченных сертификатов:', data.total);
            data.data.forEach(cert => {
                console.log(`${cert.person_name} - ${cert.certificate_name} (просрочен на ${cert.days_expired} дней)`);
            });
        }
    } catch (error) {
        console.error('Ошибка запроса:', error);
    }
}
```

### Python (requests)

#### Получить список людей
```python
import requests
import json

def get_people():
    url = "http://127.0.0.1:8000/api/people"
    params = {
        'per_page': 10,
        'search_fio': 'иванов'
    }
    
    response = requests.get(url, params=params)
    data = response.json()
    
    if data['success']:
        print(f"Найдено людей: {data['pagination']['total']}")
        for person in data['data']:
            print(f"- {person['full_name']} ({person['position']})")
    else:
        print(f"Ошибка: {data['message']}")

get_people()
```

#### Добавить человека
```python
def add_person(person_data):
    url = "http://127.0.0.1:8000/api/people"
    headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
    
    response = requests.post(url, json=person_data, headers=headers)
    data = response.json()
    
    if data['success']:
        print(f"Человек добавлен с ID: {data['data']['id']}")
    else:
        print(f"Ошибка: {data['message']}")

# Использование
person_data = {
    'full_name': 'Питонов Питон Питонович',
    'position': 'Программист',
    'phone': '+7-999-555-55-55',
    'status': 'Активный'
}

add_person(person_data)
```

### PHP (curl --noproxy "*")

#### Получить список людей
```php
<?php

function getPeople() {
    $url = 'http://127.0.0.1:8000/api/people?per_page=10';
    
    $ch = curl --noproxy "*"_init();
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_URL, $url);
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_RETURNTRANSFER, true);
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl --noproxy "*"_exec($ch);
    curl --noproxy "*"_close($ch);
    
    $data = json_decode($response, true);
    
    if ($data['success']) {
        echo "Найдено людей: " . $data['pagination']['total'] . "\n";
        foreach ($data['data'] as $person) {
            echo "- {$person['full_name']} ({$person['position']})\n";
        }
    } else {
        echo "Ошибка: " . $data['message'] . "\n";
    }
}

getPeople();
```

#### Добавить человека
```php
<?php

function addPerson($personData) {
    $url = 'http://127.0.0.1:8000/api/people';
    
    $ch = curl --noproxy "*"_init();
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_URL, $url);
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_POST, true);
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_POSTFIELDS, json_encode($personData));
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_RETURNTRANSFER, true);
    curl --noproxy "*"_setopt($ch, curl --noproxy "*"OPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl --noproxy "*"_exec($ch);
    curl --noproxy "*"_close($ch);
    
    $data = json_decode($response, true);
    
    if ($data['success']) {
        echo "Человек добавлен с ID: " . $data['data']['id'] . "\n";
    } else {
        echo "Ошибка: " . $data['message'] . "\n";
    }
}

// Использование
$personData = [
    'full_name' => 'Пхпов Пхп Пхпович',
    'position' => 'Разработчик',
    'phone' => '+7-999-777-77-77',
    'status' => 'Активный'
];

addPerson($personData);
```

---

## ⚠️ Обработка ошибок

### Код 422 - Ошибка валидации

```json
{
    "success": false,
    "message": "Ошибка валидации",
    "errors": {
        "full_name": [
            "Поле ФИО обязательно для заполнения."
        ],
        "phone": [
            "Поле телефон должно быть строкой."
        ]
    }
}
```

### Код 404 - Ресурс не найден

```json
{
    "success": false,
    "message": "Человек не найден"
}
```

### Код 500 - Внутренняя ошибка сервера

```json
{
    "success": false,
    "message": "Ошибка при получении списка людей",
    "error": "SQLSTATE[42S02]: Base table or view not found"
}
```

### Обработка ошибок в JavaScript

```javascript
async function handleApiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${data.message}`);
        }
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error.message);
        throw error;
    }
}

// Использование
try {
    const result = await handleApiRequest('http://127.0.0.1:8000/api/people');
    console.log('Успех:', result.data);
} catch (error) {
    console.error('Ошибка:', error.message);
}
```

---

## 🔧 Дополнительные возможности

### Фильтрация и поиск

Все эндпоинты списков поддерживают фильтрацию:

```bash
# Поиск по ФИО
GET /api/people?search_fio=иванов

# Поиск по должности
GET /api/people?search_position=инженер

# Комбинированный поиск
GET /api/people?search_fio=иванов&search_status=активный&per_page=5
```

### Пагинация

Все списки поддерживают пагинацию:

```bash
# Первая страница, 10 записей
GET /api/people?per_page=10&page=1

# Вторая страница, 20 записей
GET /api/people?per_page=20&page=2
```

### Сортировка (планируется)

В будущих версиях будет добавлена сортировка:

```bash
# Сортировка по ФИО (по возрастанию)
GET /api/people?sort=full_name&order=asc

# Сортировка по дате создания (по убыванию)
GET /api/people?sort=created_at&order=desc
```

---

## 📞 Поддержка

При возникновении проблем с API:

1. Проверьте логи Laravel: `storage/logs/laravel.log`
2. Убедитесь, что сервер запущен: `php artisan serve`
3. Проверьте подключение к базе данных
4. Убедитесь, что миграции выполнены: `php artisan migrate`

---

*Документация обновлена: 25.08.2025*