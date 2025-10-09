# Обзор всех API эндпоинтов

## ⚠️ ВАЖНО: Различие между удалением человека и файлов

### 🔴 Удаление ВСЕЙ ЗАПИСИ о человеке
```bash
DELETE /api/people/{id}
```
**Что делает:** Полностью удаляет человека из базы данных со всеми его данными и файлами.

**Пример:**
```bash
curl -X DELETE \
  -H "Authorization: Bearer YOUR_TOKEN" \
  http://labor.tetrakom-crm-miniapp.ru/api/people/527
```

---

### ✅ Удаление только ФАЙЛОВ (человек остается)

#### Удалить файл "Все удостоверения"
```bash
DELETE /api/people/{id}/certificates-file
```

#### Удалить фото
```bash
DELETE /api/people/{id}/photo
```

#### Удалить паспорт (1 стр)
```bash
DELETE /api/people/{id}/passport-page-1
```

#### Удалить паспорт (5 стр)
```bash
DELETE /api/people/{id}/passport-page-5
```

---

## 📚 Полный список эндпоинтов по категориям

### 👤 Управление людьми

| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| GET | `/api/people` | Получить список всех людей |
| GET | `/api/people/{id}` | Получить информацию о человеке |
| POST | `/api/people` | Создать нового человека |
| POST | `/api/people/bulk` | Массовое создание людей |
| PUT | `/api/people/{id}` | Обновить информацию о человеке |
| **DELETE** | **`/api/people/{id}`** | **⚠️ УДАЛИТЬ ЧЕЛОВЕКА ПОЛНОСТЬЮ** |

---

### 📁 Управление файлами человека

#### Файл "Все удостоверения"
| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| POST | `/api/people/{id}/certificates-file` | Загрузить файл |
| GET | `/api/people/{id}/certificates-file` | Скачать файл |
| DELETE | `/api/people/{id}/certificates-file` | Удалить файл |

#### Фото
| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| POST | `/api/people/{id}/photo` | Загрузить фото |
| DELETE | `/api/people/{id}/photo` | Удалить фото |

#### Паспорт (1 страница)
| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| POST | `/api/people/{id}/passport-page-1` | Загрузить 1 стр паспорта |
| DELETE | `/api/people/{id}/passport-page-1` | Удалить 1 стр паспорта |

#### Паспорт (5 страница)
| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| POST | `/api/people/{id}/passport-page-5` | Загрузить 5 стр паспорта |
| DELETE | `/api/people/{id}/passport-page-5` | Удалить 5 стр паспорта |

---

### 📜 Управление сертификатами

| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| GET | `/api/certificates` | Получить список всех сертификатов |
| GET | `/api/certificates/{id}` | Получить информацию о сертификате |
| POST | `/api/certificates` | Создать новый сертификат |
| PUT | `/api/certificates/{id}` | Обновить сертификат |
| DELETE | `/api/certificates/{id}` | Удалить сертификат |

---

### 🔗 Связь человека с сертификатами

| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| POST | `/api/people-certificates` | Назначить сертификат человеку |
| PUT | `/api/people-certificates/{id}` | Обновить связь |
| DELETE | `/api/people-certificates/{id}` | Удалить связь по ID связи |
| DELETE | `/api/people-certificates/people/{peopleId}/certificate/{certificateId}` | Удалить связь по ID человека и сертификата |

---

### 📊 Отчеты

| Метод | Эндпоинт | Описание |
|-------|----------|----------|
| GET | `/api/reports/expired-certificates` | Просроченные сертификаты |
| GET | `/api/reports/expiring-soon` | Истекающие скоро |
| GET | `/api/reports/people-status` | Статус людей |

---

## 💡 Примеры использования

### Пример 1: Удалить только файл удостоверений (человек остается)
```bash
curl -X DELETE \
  -H "Authorization: Bearer YOUR_TOKEN" \
  http://labor.tetrakom-crm-miniapp.ru/api/people/527/certificates-file
```

### Пример 2: Удалить всего человека (все данные)
```bash
curl -X DELETE \
  -H "Authorization: Bearer YOUR_TOKEN" \
  http://labor.tetrakom-crm-miniapp.ru/api/people/527
```

### Пример 3: Загрузить новый файл удостоверений
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "certificates_file=@/path/to/file.pdf" \
  http://labor.tetrakom-crm-miniapp.ru/api/people/527/certificates-file
```

---

## 🔒 Аутентификация

Все эндпоинты требуют API токен:
```
Authorization: Bearer YOUR_API_TOKEN
```

**Создать токен:** http://labor.tetrakom-crm-miniapp.ru/api-tokens

---

## 📖 Дополнительные ресурсы

- **Полная документация:** http://labor.tetrakom-crm-miniapp.ru/api-docs
- **Руководство по файлам:** `API_FILES_GUIDE.md`
- **Управление токенами:** http://labor.tetrakom-crm-miniapp.ru/api-tokens

