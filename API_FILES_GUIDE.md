# Руководство по работе с файлами через API

## Базовый URL
```
http://labor.tetrakom-crm-miniapp.ru/api
```

## Аутентификация
Все запросы требуют API токен в заголовке:
```
Authorization: Bearer YOUR_API_TOKEN
```

Создать токен можно здесь: http://labor.tetrakom-crm-miniapp.ru/api-tokens

---

## 📁 Работа с файлом "Все удостоверения"

### 1. Загрузить файл со всеми удостоверениями

**Endpoint:** `POST /api/people/{id}/certificates-file`

**Пример (curl):**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "certificates_file=@/path/to/file.pdf" \
  http://labor.tetrakom-crm-miniapp.ru/api/people/1/certificates-file
```

**Пример (Python):**
```python
import requests

url = "http://labor.tetrakom-crm-miniapp.ru/api/people/1/certificates-file"
headers = {"Authorization": "Bearer YOUR_API_TOKEN"}
files = {"certificates_file": open("/path/to/file.pdf", "rb")}

response = requests.post(url, headers=headers, files=files)
print(response.json())
```

**Ответ:**
```json
{
  "success": true,
  "message": "Файл со всеми удостоверениями успешно загружен",
  "data": {
    "certificates_file": "6789abcd_certificates.pdf",
    "certificates_file_original_name": "file.pdf",
    "certificates_file_mime_type": "application/pdf",
    "certificates_file_size": 1024000
  }
}
```

---

### 2. Скачать файл со всеми удостоверениями

**Endpoint:** `GET /api/people/{id}/certificates-file`

**Пример (curl):**
```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  -o downloaded_certificates.pdf \
  http://labor.tetrakom-crm-miniapp.ru/api/people/1/certificates-file
```

**Пример (Python):**
```python
import requests

url = "http://labor.tetrakom-crm-miniapp.ru/api/people/1/certificates-file"
headers = {"Authorization": "Bearer YOUR_API_TOKEN"}

response = requests.get(url, headers=headers)

if response.status_code == 200:
    with open("downloaded_certificates.pdf", "wb") as f:
        f.write(response.content)
    print("Файл успешно скачан!")
else:
    print(response.json())
```

**Примечание:** Этот эндпоинт возвращает файл напрямую для скачивания.

---

### 3. Удалить файл со всеми удостоверениями

**Endpoint:** `DELETE /api/people/{id}/certificates-file`

**Пример (curl):**
```bash
curl -X DELETE \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  http://labor.tetrakom-crm-miniapp.ru/api/people/1/certificates-file
```

**Пример (Python):**
```python
import requests

url = "http://labor.tetrakom-crm-miniapp.ru/api/people/1/certificates-file"
headers = {"Authorization": "Bearer YOUR_API_TOKEN"}

response = requests.delete(url, headers=headers)
print(response.json())
```

**Ответ:**
```json
{
  "success": true,
  "message": "Файл со всеми удостоверениями успешно удален"
}
```

---

## 📷 Другие файлы

### Фото
- **Загрузить:** `POST /api/people/{id}/photo` (параметр: `photo`)
- **Удалить:** `DELETE /api/people/{id}/photo`

### Паспорт (1 страница)
- **Загрузить:** `POST /api/people/{id}/passport-page-1` (параметр: `passport_page_1`)
- **Удалить:** `DELETE /api/people/{id}/passport-page-1`

### Паспорт (5 страница)
- **Загрузить:** `POST /api/people/{id}/passport-page-5` (параметр: `passport_page_5`)
- **Удалить:** `DELETE /api/people/{id}/passport-page-5`

**Пример загрузки фото:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "photo=@/path/to/photo.jpg" \
  http://labor.tetrakom-crm-miniapp.ru/api/people/1/photo
```

---

## 📊 Коды ответов

| Код | Описание |
|-----|----------|
| 200 | Успешный запрос |
| 201 | Ресурс создан |
| 400 | Неверный запрос |
| 401 | Не авторизован (неверный токен) |
| 404 | Ресурс не найден |
| 413 | Файл слишком большой (максимум 200MB) |
| 422 | Ошибка валидации |
| 500 | Внутренняя ошибка сервера |

---

## 💡 Полезные советы

1. **Размер файлов:** Максимальный размер загружаемого файла — 200MB
2. **Форматы:** Поддерживаются любые форматы файлов (PDF, JPG, PNG, DOC и т.д.)
3. **Токены:** Храните токены в безопасном месте, не публикуйте их в открытом доступе
4. **Тайм-ауты:** Для больших файлов установлен таймаут 300 секунд

---

## 🔗 Дополнительная документация

- Полная документация API: http://labor.tetrakom-crm-miniapp.ru/api-docs
- Управление токенами: http://labor.tetrakom-crm-miniapp.ru/api-tokens
- Примеры использования: http://labor.tetrakom-crm-miniapp.ru/api-examples


