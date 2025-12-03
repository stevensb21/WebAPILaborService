# Документация структуры базы данных

## Обзор

База данных системы управления охраной труда (WebAPILaborService) построена на PostgreSQL и использует Laravel ORM. Система предназначена для управления информацией о сотрудниках, их удостоверениях и сертификатах.

## ER-диаграмма

```mermaid
erDiagram
    users ||--o{ sessions : "has"
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string remember_token
        timestamps created_at
        timestamps updated_at
    }
    
    sessions {
        string id PK
        bigint user_id FK
        string ip_address
        text user_agent
        longtext payload
        integer last_activity
    }
    
    password_reset_tokens {
        string email PK
        string token
        timestamp created_at
    }
    
    people ||--o{ people_certificates : "has"
    people {
        bigint id PK
        string full_name
        string phone
        string snils
        string inn
        string position
        date birth_date
        text address
        string status
        string passport_page_1
        string passport_page_1_original_name
        string passport_page_1_mime_type
        integer passport_page_1_size
        string passport_page_5
        string passport_page_5_original_name
        string passport_page_5_mime_type
        integer passport_page_5_size
        string photo
        string photo_original_name
        string photo_mime_type
        integer photo_size
        string certificates_file
        string certificates_file_original_name
        string certificates_file_mime_type
        integer certificates_file_size
        timestamps created_at
        timestamps updated_at
    }
    
    certificates ||--o{ people_certificates : "has"
    certificates ||--|| certificate_orders : "has"
    certificates {
        bigint id PK
        string name
        text description
        integer expiry_date
        timestamps created_at
        timestamps updated_at
    }
    
    people_certificates {
        bigint id PK
        bigint people_id FK
        bigint certificate_id FK
        date assigned_date
        text certificate_number
        integer status
        text notes
        string certificate_file
        string certificate_file_original_name
        string certificate_file_mime_type
        integer certificate_file_size
        timestamps created_at
        timestamps updated_at
    }
    
    certificate_orders {
        bigint id PK
        bigint certificate_id FK UK
        integer sort_order
        timestamps created_at
        timestamps updated_at
    }
    
    api_tokens {
        bigint id PK
        string name
        string token UK
        text description
        timestamp last_used_at
        timestamp expires_at
        boolean is_active
        timestamps created_at
        timestamps updated_at
    }
```

## Описание таблиц

### 1. `users` - Пользователи системы

Таблица для хранения учетных записей пользователей веб-интерфейса.

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint (PK) | Уникальный идентификатор пользователя |
| `name` | string | Имя пользователя |
| `email` | string (UNIQUE) | Email адрес (используется для входа) |
| `email_verified_at` | timestamp | Дата подтверждения email |
| `password` | string | Хешированный пароль |
| `remember_token` | string | Токен для "Запомнить меня" |
| `created_at` | timestamp | Дата создания записи |
| `updated_at` | timestamp | Дата последнего обновления |

**Связи:**
- `hasMany` → `sessions` (один пользователь может иметь много сессий)

---

### 2. `sessions` - Сессии пользователей

Таблица для хранения активных сессий пользователей (Laravel Session).

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | string (PK) | Идентификатор сессии |
| `user_id` | bigint (FK, nullable) | Ссылка на пользователя |
| `ip_address` | string(45) | IP адрес пользователя |
| `user_agent` | text | User-Agent браузера |
| `payload` | longtext | Данные сессии (зашифрованы) |
| `last_activity` | integer | Timestamp последней активности |

**Связи:**
- `belongsTo` → `users` (сессия принадлежит пользователю)

**Индексы:**
- `user_id` (index)
- `last_activity` (index)

---

### 3. `password_reset_tokens` - Токены сброса пароля

Таблица для хранения токенов восстановления пароля.

| Поле | Тип | Описание |
|------|-----|----------|
| `email` | string (PK) | Email пользователя |
| `token` | string | Токен для сброса пароля |
| `created_at` | timestamp | Дата создания токена |

---

### 4. `people` - Сотрудники

Основная таблица для хранения информации о сотрудниках.

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint (PK) | Уникальный идентификатор сотрудника |
| `full_name` | string | Полное имя сотрудника |
| `phone` | string (nullable) | Номер телефона |
| `snils` | string (nullable) | СНИЛС |
| `inn` | string (nullable) | ИНН |
| `position` | string (nullable) | Должность |
| `birth_date` | date (nullable) | Дата рождения |
| `address` | text (nullable) | Адрес проживания |
| `status` | string (nullable) | Статус сотрудника |
| `passport_page_1` | string (nullable) | Путь к файлу 1-й страницы паспорта |
| `passport_page_1_original_name` | string (nullable) | Оригинальное имя файла |
| `passport_page_1_mime_type` | string (nullable) | MIME тип файла |
| `passport_page_1_size` | integer (nullable) | Размер файла в байтах |
| `passport_page_5` | string (nullable) | Путь к файлу 5-й страницы паспорта |
| `passport_page_5_original_name` | string (nullable) | Оригинальное имя файла |
| `passport_page_5_mime_type` | string (nullable) | MIME тип файла |
| `passport_page_5_size` | integer (nullable) | Размер файла в байтах |
| `photo` | string (nullable) | Путь к файлу фотографии |
| `photo_original_name` | string (nullable) | Оригинальное имя файла |
| `photo_mime_type` | string (nullable) | MIME тип файла |
| `photo_size` | integer (nullable) | Размер файла в байтах |
| `certificates_file` | string (nullable) | Путь к объединенному файлу всех удостоверений (PDF) |
| `certificates_file_original_name` | string (nullable) | Оригинальное имя файла |
| `certificates_file_mime_type` | string (nullable) | MIME тип файла |
| `certificates_file_size` | integer (nullable) | Размер файла в байтах |
| `created_at` | timestamp | Дата создания записи |
| `updated_at` | timestamp | Дата последнего обновления |

**Связи:**
- `belongsToMany` → `certificates` через `people_certificates` (многие ко многим)

**Особенности:**
- Хранит информацию о документах сотрудника (паспорт, фото)
- Может содержать объединенный PDF файл со всеми удостоверениями
- Поля для файлов хранят путь, оригинальное имя, MIME тип и размер

---

### 5. `certificates` - Типы удостоверений

Справочник типов удостоверений/сертификатов.

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint (PK) | Уникальный идентификатор типа сертификата |
| `name` | string | Название сертификата |
| `description` | text (nullable) | Описание сертификата |
| `expiry_date` | integer (nullable) | Срок действия в днях (например, 365 для годового) |
| `created_at` | timestamp | Дата создания записи |
| `updated_at` | timestamp | Дата последнего обновления |

**Связи:**
- `belongsToMany` → `people` через `people_certificates` (многие ко многим)
- `hasOne` → `certificate_orders` (один к одному, для определения порядка отображения)

**Особенности:**
- `expiry_date` хранится как integer (количество дней), а не как дата
- Каждый тип сертификата может иметь свой срок действия

---

### 6. `people_certificates` - Удостоверения сотрудников

Связующая таблица между сотрудниками и их удостоверениями (many-to-many с дополнительными полями).

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint (PK) | Уникальный идентификатор записи |
| `people_id` | bigint (FK) | Ссылка на сотрудника |
| `certificate_id` | bigint (FK) | Ссылка на тип сертификата |
| `assigned_date` | date (nullable) | Дата выдачи удостоверения |
| `certificate_number` | text | Номер удостоверения |
| `status` | integer (nullable, default: 4) | Статус удостоверения: 1=активный, 2=скоро просрочится, 3=просрочен, 4=отсутствует |
| `notes` | text (nullable) | Примечания |
| `certificate_file` | string (nullable) | Путь к файлу удостоверения (PDF) |
| `certificate_file_original_name` | string (nullable) | Оригинальное имя файла |
| `certificate_file_mime_type` | string (nullable) | MIME тип файла |
| `certificate_file_size` | integer (nullable) | Размер файла в байтах |
| `created_at` | timestamp | Дата создания записи |
| `updated_at` | timestamp | Дата последнего обновления |

**Связи:**
- `belongsTo` → `people` (принадлежит сотруднику)
- `belongsTo` → `certificates` (принадлежит типу сертификата)

**Ограничения:**
- Уникальная комбинация `(people_id, certificate_id)` - один сотрудник не может иметь два одинаковых типа сертификата
- `onDelete('cascade')` - при удалении сотрудника или типа сертификата удаляются связанные записи

**Статусы удостоверений:**
- `1` - Активный (действителен)
- `2` - Скоро просрочится (требует внимания)
- `3` - Просрочен
- `4` - Отсутствует (по умолчанию)

---

### 7. `certificate_orders` - Порядок отображения сертификатов

Таблица для определения порядка отображения типов сертификатов в интерфейсе.

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint (PK) | Уникальный идентификатор записи |
| `certificate_id` | bigint (FK, UNIQUE) | Ссылка на тип сертификата (один к одному) |
| `sort_order` | integer (default: 0) | Порядок сортировки (меньше = выше в списке) |
| `created_at` | timestamp | Дата создания записи |
| `updated_at` | timestamp | Дата последнего обновления |

**Связи:**
- `belongsTo` → `certificates` (принадлежит типу сертификата)

**Ограничения:**
- Уникальный `certificate_id` - каждый тип сертификата может иметь только один порядок
- `onDelete('cascade')` - при удалении типа сертификата удаляется запись о порядке

**Индексы:**
- `sort_order` (index) - для быстрой сортировки

---

### 8. `api_tokens` - API токены

Таблица для хранения токенов доступа к API.

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint (PK) | Уникальный идентификатор токена |
| `name` | string | Название токена (для идентификации) |
| `token` | string(64) (UNIQUE) | Сам токен (64 символа) |
| `description` | text (nullable) | Описание назначения токена |
| `last_used_at` | timestamp (nullable) | Дата и время последнего использования |
| `expires_at` | timestamp (nullable) | Дата истечения токена (null = бессрочный) |
| `is_active` | boolean (default: true) | Активен ли токен |
| `created_at` | timestamp | Дата создания записи |
| `updated_at` | timestamp | Дата последнего обновления |

**Особенности:**
- Токен генерируется случайным образом (64 символа)
- Токен скрыт по умолчанию в сериализации модели
- Методы модели: `isActive()` - проверка активности, `updateLastUsed()` - обновление времени использования

---

## Связи между таблицами

### Many-to-Many

1. **People ↔ Certificates** (через `people_certificates`)
   - Один сотрудник может иметь несколько типов удостоверений
   - Один тип удостоверения может быть присвоен нескольким сотрудникам
   - Дополнительные данные в pivot-таблице: дата выдачи, номер, статус, файл

### One-to-One

1. **Certificates ↔ CertificateOrders**
   - Каждый тип сертификата имеет один порядок отображения
   - Используется для сортировки сертификатов в интерфейсе

### One-to-Many

1. **Users → Sessions**
   - Один пользователь может иметь несколько активных сессий

---

## Индексы и производительность

### Основные индексы:

1. **people_certificates:**
   - Уникальный индекс на `(people_id, certificate_id)`
   - Foreign key индексы на `people_id` и `certificate_id`

2. **certificate_orders:**
   - Индекс на `sort_order` для быстрой сортировки
   - Уникальный индекс на `certificate_id`

3. **sessions:**
   - Индекс на `user_id`
   - Индекс на `last_activity`

4. **users:**
   - Уникальный индекс на `email`

5. **api_tokens:**
   - Уникальный индекс на `token`

---

## Типы данных и ограничения

### Строковые типы:
- `string` - VARCHAR(255) в PostgreSQL
- `text` - TEXT (неограниченная длина)

### Числовые типы:
- `bigint` - BIGINT (64-битное целое)
- `integer` - INTEGER (32-битное целое)

### Дата и время:
- `date` - DATE (только дата)
- `timestamp` - TIMESTAMP (дата и время)
- `integer` для `expiry_date` в `certificates` - хранится как количество дней

### Логические:
- `boolean` - BOOLEAN

---

## Файловое хранилище

Система хранит метаданные о файлах в базе данных, а сами файлы - в файловой системе (Laravel Storage).

### Типы файлов:

1. **Документы сотрудника:**
   - Паспорт (страницы 1 и 5)
   - Фотография
   - Объединенный файл всех удостоверений (PDF)

2. **Удостоверения:**
   - Отдельные файлы удостоверений для каждой связи `people_certificates`

### Метаданные файлов:
- `*_file` - путь к файлу в хранилище
- `*_original_name` - оригинальное имя файла при загрузке
- `*_mime_type` - MIME тип файла
- `*_size` - размер файла в байтах

---

## Бизнес-логика

### Статусы удостоверений:
Статус удостоверения (`people_certificates.status`) определяется на основе:
- `assigned_date` - дата выдачи
- `expiry_date` из `certificates` - срок действия в днях
- Текущей даты

### Порядок сертификатов:
Порядок отображения сертификатов определяется полем `sort_order` в таблице `certificate_orders`. Меньшее значение = выше в списке.

### API аутентификация:
Доступ к API осуществляется через токены из таблицы `api_tokens`. Токен должен быть:
- Активным (`is_active = true`)
- Не истекшим (`expires_at` в будущем или `null`)

---

## Миграции

Все изменения структуры БД отслеживаются через Laravel миграции в директории `database/migrations/`.

### Основные миграции:
1. `2025_08_19_143127_create_people_table.php` - создание таблицы сотрудников
2. `2025_08_19_143147_create_certificates_table.php` - создание справочника сертификатов
3. `2025_08_19_143206_create_people_certificates_table.php` - связующая таблица
4. `2025_08_22_111923_change_expiry_date_to_integer_in_certificates_table.php` - изменение типа `expiry_date`
5. `2025_08_25_064210_add_status_to_people_table.php` - добавление статуса сотрудника
6. `2025_09_22_112647_create_certificate_orders_table.php` - порядок отображения
7. `2025_09_23_090621_create_api_tokens_table.php` - API токены

---

## Модели Laravel

### Основные модели:
- `App\Models\People` - сотрудники
- `App\Models\Certificate` - типы сертификатов
- `App\Models\PeopleCertificate` - связи сотрудник-сертификат
- `App\Models\CertificateOrder` - порядок сертификатов
- `App\Models\User` - пользователи системы
- `App\Models\ApiToken` - API токены

### Relationships:
```php
// People
$people->certificates() // BelongsToMany

// Certificate
$certificate->people() // BelongsToMany
$certificate->order() // HasOne

// PeopleCertificate
$peopleCertificate->people() // BelongsTo
$peopleCertificate->certificate() // BelongsTo
```

---

## Версия документации

**Дата создания:** 2025-11-13  
**Версия БД:** Текущая (PostgreSQL)  
**Версия Laravel:** 11.x


