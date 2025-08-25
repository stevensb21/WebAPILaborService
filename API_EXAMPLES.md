 # Практические примеры использования API

## 📋 Содержание
1. [JavaScript/Node.js](#javascriptnodejs)
2. [Python](#python)
3. [PHP](#php)
4. [cURL](#curl)
5. [Postman](#postman)
6. [React](#react)
7. [Vue.js](#vuejs)

---

## 🟨 JavaScript/Node.js

### Установка зависимостей
```bash
npm install axios
# или
yarn add axios
```

### Базовый класс для работы с API
```javascript
class SafetyAPI {
    constructor(baseURL = 'http://127.0.0.1:8000/api') {
        this.baseURL = baseURL;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
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

    // People API
    async getPeople(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`/people?${queryString}`);
    }

    async getPerson(id) {
        return this.request(`/people/${id}`);
    }

    async createPerson(personData) {
        return this.request('/people', {
            method: 'POST',
            body: JSON.stringify(personData)
        });
    }

    async updatePerson(id, personData) {
        return this.request(`/people/${id}`, {
            method: 'PUT',
            body: JSON.stringify(personData)
        });
    }

    async deletePerson(id) {
        return this.request(`/people/${id}`, {
            method: 'DELETE'
        });
    }

    async createMultiplePeople(peopleArray) {
        return this.request('/people/bulk', {
            method: 'POST',
            body: JSON.stringify({ people: peopleArray })
        });
    }

    // Certificates API
    async getCertificates(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request(`/certificates?${queryString}`);
    }

    async createCertificate(certificateData) {
        return this.request('/certificates', {
            method: 'POST',
            body: JSON.stringify(certificateData)
        });
    }

    // Reports API
    async getExpiredCertificates(certificateId = null) {
        const params = certificateId ? { certificate_id: certificateId } : {};
        const queryString = new URLSearchParams(params).toString();
        return this.request(`/reports/expired-certificates?${queryString}`);
    }

    async getExpiringSoon(certificateId = null) {
        const params = certificateId ? { certificate_id: certificateId } : {};
        const queryString = new URLSearchParams(params).toString();
        return this.request(`/reports/expiring-soon?${queryString}`);
    }

    async getPeopleStatus(status = null) {
        const params = status ? { status } : {};
        const queryString = new URLSearchParams(params).toString();
        return this.request(`/reports/people-status?${queryString}`);
    }
}

// Использование
const api = new SafetyAPI();

// Примеры использования
async function examples() {
    try {
        // Получить список людей
        const people = await api.getPeople({ per_page: 5 });
        console.log('Люди:', people.data);

        // Добавить человека
        const newPerson = await api.createPerson({
            full_name: 'Тестовый Сотрудник',
            position: 'Разработчик',
            phone: '+7-999-000-00-00',
            status: 'Активный'
        });
        console.log('Добавлен:', newPerson.data);

        // Получить отчет по просроченным сертификатам
        const expired = await api.getExpiredCertificates();
        console.log('Просроченных:', expired.total);

    } catch (error) {
        console.error('Ошибка:', error.message);
    }
}

examples();
```

### Пример с Axios
```javascript
import axios from 'axios';

class SafetyAPIAxios {
    constructor(baseURL = 'http://127.0.0.1:8000/api') {
        this.api = axios.create({
            baseURL,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        // Перехватчик ответов
        this.api.interceptors.response.use(
            response => {
                if (response.data.success === false) {
                    throw new Error(response.data.message);
                }
                return response.data;
            },
            error => {
                console.error('API Error:', error.response?.data?.message || error.message);
                throw error;
            }
        );
    }

    // People API
    async getPeople(params = {}) {
        return this.api.get('/people', { params });
    }

    async createPerson(personData) {
        return this.api.post('/people', personData);
    }

    async updatePerson(id, personData) {
        return this.api.put(`/people/${id}`, personData);
    }

    async deletePerson(id) {
        return this.api.delete(`/people/${id}`);
    }

    // Reports API
    async getExpiredCertificates(certificateId = null) {
        const params = certificateId ? { certificate_id: certificateId } : {};
        return this.api.get('/reports/expired-certificates', { params });
    }
}

// Использование
const api = new SafetyAPIAxios();

api.getPeople({ per_page: 10 })
    .then(data => console.log('Люди:', data.data))
    .catch(error => console.error('Ошибка:', error.message));
```

---

## 🐍 Python

### Установка зависимостей
```bash
pip install requests
```

### Класс для работы с API
```python
import requests
import json
from typing import Dict, List, Optional, Any

class SafetyAPI:
    def __init__(self, base_url: str = "http://127.0.0.1:8000/api"):
        self.base_url = base_url
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })

    def _make_request(self, method: str, endpoint: str, **kwargs) -> Dict[str, Any]:
        """Выполняет HTTP запрос к API"""
        url = f"{self.base_url}{endpoint}"
        
        try:
            response = self.session.request(method, url, **kwargs)
            response.raise_for_status()
            
            data = response.json()
            
            if not data.get('success', False):
                raise Exception(data.get('message', 'Unknown error'))
                
            return data
            
        except requests.exceptions.RequestException as e:
            print(f"Request error: {e}")
            raise
        except Exception as e:
            print(f"API error: {e}")
            raise

    # People API
    def get_people(self, **params) -> Dict[str, Any]:
        """Получить список людей"""
        return self._make_request('GET', '/people', params=params)

    def get_person(self, person_id: int) -> Dict[str, Any]:
        """Получить конкретного человека"""
        return self._make_request('GET', f'/people/{person_id}')

    def create_person(self, person_data: Dict[str, Any]) -> Dict[str, Any]:
        """Создать человека"""
        return self._make_request('POST', '/people', json=person_data)

    def update_person(self, person_id: int, person_data: Dict[str, Any]) -> Dict[str, Any]:
        """Обновить человека"""
        return self._make_request('PUT', f'/people/{person_id}', json=person_data)

    def delete_person(self, person_id: int) -> Dict[str, Any]:
        """Удалить человека"""
        return self._make_request('DELETE', f'/people/{person_id}')

    def create_multiple_people(self, people_data: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Создать несколько людей"""
        return self._make_request('POST', '/people/bulk', json={'people': people_data})

    # Certificates API
    def get_certificates(self, **params) -> Dict[str, Any]:
        """Получить список сертификатов"""
        return self._make_request('GET', '/certificates', params=params)

    def create_certificate(self, certificate_data: Dict[str, Any]) -> Dict[str, Any]:
        """Создать сертификат"""
        return self._make_request('POST', '/certificates', json=certificate_data)

    # Reports API
    def get_expired_certificates(self, certificate_id: Optional[int] = None) -> Dict[str, Any]:
        """Получить отчет по просроченным сертификатам"""
        params = {'certificate_id': certificate_id} if certificate_id else {}
        return self._make_request('GET', '/reports/expired-certificates', params=params)

    def get_expiring_soon(self, certificate_id: Optional[int] = None) -> Dict[str, Any]:
        """Получить отчет по скоро истекающим сертификатам"""
        params = {'certificate_id': certificate_id} if certificate_id else {}
        return self._make_request('GET', '/reports/expiring-soon', params=params)

    def get_people_status(self, status: Optional[str] = None) -> Dict[str, Any]:
        """Получить отчет по статусам работников"""
        params = {'status': status} if status else {}
        return self._make_request('GET', '/reports/people-status', params=params)

# Примеры использования
def main():
    api = SafetyAPI()
    
    try:
        # Получить список людей
        people = api.get_people(per_page=5, search_fio='иванов')
        print(f"Найдено людей: {people['pagination']['total']}")
        
        for person in people['data']:
            print(f"- {person['full_name']} ({person['position']})")

        # Создать человека
        new_person = api.create_person({
            'full_name': 'Питонов Питон Питонович',
            'position': 'Программист',
            'phone': '+7-999-555-55-55',
            'status': 'Активный'
        })
        print(f"Создан человек с ID: {new_person['data']['id']}")

        # Создать несколько людей
        multiple_people = api.create_multiple_people([
            {
                'full_name': 'Первый Сотрудник',
                'position': 'Инженер',
                'status': 'Активный'
            },
            {
                'full_name': 'Второй Сотрудник',
                'position': 'Техник',
                'status': 'В отпуске'
            }
        ])
        print(f"Добавлено людей: {multiple_people['data']['created']}")

        # Получить отчет по просроченным сертификатам
        expired = api.get_expired_certificates()
        print(f"Просроченных сертификатов: {expired['total']}")
        
        for cert in expired['data']:
            print(f"- {cert['person_name']}: {cert['certificate_name']} (просрочен на {cert['days_expired']} дней)")

        # Получить отчет по статусам
        status_report = api.get_people_status()
        print(f"Всего работников: {status_report['data']['total_people']}")
        
        for status, count in status_report['data']['status_distribution'].items():
            print(f"- {status}: {count}")

    except Exception as e:
        print(f"Ошибка: {e}")

if __name__ == "__main__":
    main()
```

### Пример с асинхронными запросами (aiohttp)
```python
import aiohttp
import asyncio
from typing import Dict, Any

class AsyncSafetyAPI:
    def __init__(self, base_url: str = "http://127.0.0.1:8000/api"):
        self.base_url = base_url

    async def _make_request(self, session: aiohttp.ClientSession, method: str, endpoint: str, **kwargs) -> Dict[str, Any]:
        """Выполняет асинхронный HTTP запрос"""
        url = f"{self.base_url}{endpoint}"
        
        async with session.request(method, url, **kwargs) as response:
            data = await response.json()
            
            if not data.get('success', False):
                raise Exception(data.get('message', 'Unknown error'))
                
            return data

    async def get_people(self, **params) -> Dict[str, Any]:
        """Асинхронно получить список людей"""
        async with aiohttp.ClientSession() as session:
            return await self._make_request(session, 'GET', '/people', params=params)

    async def create_person(self, person_data: Dict[str, Any]) -> Dict[str, Any]:
        """Асинхронно создать человека"""
        async with aiohttp.ClientSession() as session:
            return await self._make_request(session, 'POST', '/people', json=person_data)

# Использование
async def async_example():
    api = AsyncSafetyAPI()
    
    # Параллельные запросы
    people_task = api.get_people(per_page=10)
    expired_task = api.get_expired_certificates()
    
    people, expired = await asyncio.gather(people_task, expired_task)
    
    print(f"Людей: {people['pagination']['total']}")
    print(f"Просроченных сертификатов: {expired['total']}")

# Запуск
asyncio.run(async_example())
```

---

## 🐘 PHP

### Класс для работы с API
```php
<?php

class SafetyAPI {
    private $baseURL;
    private $headers;

    public function __construct($baseURL = 'http://127.0.0.1:8000/api') {
        $this->baseURL = $baseURL;
        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }

    private function makeRequest($method, $endpoint, $data = null, $params = []) {
        $url = $this->baseURL . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new Exception('HTTP ' . $httpCode . ': ' . ($data['message'] ?? 'Unknown error'));
        }

        if (!$data['success']) {
            throw new Exception($data['message'] ?? 'Unknown error');
        }

        return $data;
    }

    // People API
    public function getPeople($params = []) {
        return $this->makeRequest('GET', '/people', null, $params);
    }

    public function getPerson($id) {
        return $this->makeRequest('GET', "/people/{$id}");
    }

    public function createPerson($personData) {
        return $this->makeRequest('POST', '/people', $personData);
    }

    public function updatePerson($id, $personData) {
        return $this->makeRequest('PUT', "/people/{$id}", $personData);
    }

    public function deletePerson($id) {
        return $this->makeRequest('DELETE', "/people/{$id}");
    }

    public function createMultiplePeople($peopleArray) {
        return $this->makeRequest('POST', '/people/bulk', ['people' => $peopleArray]);
    }

    // Certificates API
    public function getCertificates($params = []) {
        return $this->makeRequest('GET', '/certificates', null, $params);
    }

    public function createCertificate($certificateData) {
        return $this->makeRequest('POST', '/certificates', $certificateData);
    }

    // Reports API
    public function getExpiredCertificates($certificateId = null) {
        $params = $certificateId ? ['certificate_id' => $certificateId] : [];
        return $this->makeRequest('GET', '/reports/expired-certificates', null, $params);
    }

    public function getExpiringSoon($certificateId = null) {
        $params = $certificateId ? ['certificate_id' => $certificateId] : [];
        return $this->makeRequest('GET', '/reports/expiring-soon', null, $params);
    }

    public function getPeopleStatus($status = null) {
        $params = $status ? ['status' => $status] : [];
        return $this->makeRequest('GET', '/reports/people-status', null, $params);
    }
}

// Примеры использования
function main() {
    $api = new SafetyAPI();

    try {
        // Получить список людей
        $people = $api->getPeople(['per_page' => 5, 'search_fio' => 'иванов']);
        echo "Найдено людей: " . $people['pagination']['total'] . "\n";
        
        foreach ($people['data'] as $person) {
            echo "- {$person['full_name']} ({$person['position']})\n";
        }

        // Создать человека
        $newPerson = $api->createPerson([
            'full_name' => 'Пхпов Пхп Пхпович',
            'position' => 'Разработчик',
            'phone' => '+7-999-777-77-77',
            'status' => 'Активный'
        ]);
        echo "Создан человек с ID: " . $newPerson['data']['id'] . "\n";

        // Создать несколько людей
        $multiplePeople = $api->createMultiplePeople([
            [
                'full_name' => 'Первый Сотрудник',
                'position' => 'Инженер',
                'status' => 'Активный'
            ],
            [
                'full_name' => 'Второй Сотрудник',
                'position' => 'Техник',
                'status' => 'В отпуске'
            ]
        ]);
        echo "Добавлено людей: " . $multiplePeople['data']['created'] . "\n";

        // Получить отчет по просроченным сертификатам
        $expired = $api->getExpiredCertificates();
        echo "Просроченных сертификатов: " . $expired['total'] . "\n";
        
        foreach ($expired['data'] as $cert) {
            echo "- {$cert['person_name']}: {$cert['certificate_name']} (просрочен на {$cert['days_expired']} дней)\n";
        }

        // Получить отчет по статусам
        $statusReport = $api->getPeopleStatus();
        echo "Всего работников: " . $statusReport['data']['total_people'] . "\n";
        
        foreach ($statusReport['data']['status_distribution'] as $status => $count) {
            echo "- {$status}: {$count}\n";
        }

    } catch (Exception $e) {
        echo "Ошибка: " . $e->getMessage() . "\n";
    }
}

main();
```

---

## 🔗 cURL

### Базовые команды

#### Получить список людей
```bash
# Простой запрос
curl -X GET "http://127.0.0.1:8000/api/people" \
  -H "Accept: application/json"

# С фильтрацией
curl -X GET "http://127.0.0.1:8000/api/people?search_fio=иванов&per_page=5" \
  -H "Accept: application/json"

# С пагинацией
curl -X GET "http://127.0.0.1:8000/api/people?page=2&per_page=10" \
  -H "Accept: application/json"
```

#### Создать человека
```bash
curl -X POST "http://127.0.0.1:8000/api/people" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Курлов Курл Курлович",
    "position": "Разработчик",
    "phone": "+7-999-888-88-88",
    "status": "Активный"
  }'
```

#### Обновить человека
```bash
curl -X PUT "http://127.0.0.1:8000/api/people/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "position": "Старший разработчик",
    "status": "В отпуске"
  }'
```

#### Удалить человека
```bash
curl -X DELETE "http://127.0.0.1:8000/api/people/1" \
  -H "Accept: application/json"
```

#### Массовое добавление
```bash
curl -X POST "http://127.0.0.1:8000/api/people/bulk" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "people": [
      {
        "full_name": "Первый Сотрудник",
        "position": "Инженер",
        "status": "Активный"
      },
      {
        "full_name": "Второй Сотрудник",
        "position": "Техник",
        "status": "В отпуске"
      }
    ]
  }'
```

#### Получить отчеты
```bash
# Просроченные сертификаты
curl -X GET "http://127.0.0.1:8000/api/reports/expired-certificates" \
  -H "Accept: application/json"

# Скоро истекающие сертификаты
curl -X GET "http://127.0.0.1:8000/api/reports/expiring-soon" \
  -H "Accept: application/json"

# Статусы работников
curl -X GET "http://127.0.0.1:8000/api/reports/people-status" \
  -H "Accept: application/json"
```

### Скрипт для автоматизации
```bash
#!/bin/bash

# Конфигурация
API_BASE="http://127.0.0.1:8000/api"
HEADERS="-H 'Content-Type: application/json' -H 'Accept: application/json'"

# Функции
get_people() {
    curl -s -X GET "$API_BASE/people?per_page=10" $HEADERS | jq '.'
}

create_person() {
    local name="$1"
    local position="$2"
    local phone="$3"
    
    curl -s -X POST "$API_BASE/people" $HEADERS \
        -d "{
            \"full_name\": \"$name\",
            \"position\": \"$position\",
            \"phone\": \"$phone\",
            \"status\": \"Активный\"
        }" | jq '.'
}

get_expired_certificates() {
    curl -s -X GET "$API_BASE/reports/expired-certificates" $HEADERS | jq '.'
}

# Использование
echo "Получаем список людей..."
get_people

echo -e "\nСоздаем нового человека..."
create_person "Тестовый Сотрудник" "Разработчик" "+7-999-000-00-00"

echo -e "\nПолучаем отчет по просроченным сертификатам..."
get_expired_certificates
```

---

## 📮 Postman

### Коллекция для Postman

Создайте новую коллекцию в Postman и добавьте следующие запросы:

#### 1. Получить список людей
- **Method:** GET
- **URL:** `{{base_url}}/people`
- **Headers:** 
  - `Accept: application/json`
- **Query Params:**
  - `per_page: 10`
  - `search_fio: иванов`

#### 2. Создать человека
- **Method:** POST
- **URL:** `{{base_url}}/people`
- **Headers:**
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body (raw JSON):**
```json
{
    "full_name": "Тестовый Сотрудник",
    "position": "Разработчик",
    "phone": "+7-999-000-00-00",
    "status": "Активный"
}
```

#### 3. Обновить человека
- **Method:** PUT
- **URL:** `{{base_url}}/people/{{person_id}}`
- **Headers:**
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body (raw JSON):**
```json
{
    "position": "Старший разработчик",
    "status": "В отпуске"
}
```

#### 4. Удалить человека
- **Method:** DELETE
- **URL:** `{{base_url}}/people/{{person_id}}`
- **Headers:**
  - `Accept: application/json`

#### 5. Массовое добавление
- **Method:** POST
- **URL:** `{{base_url}}/people/bulk`
- **Headers:**
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body (raw JSON):**
```json
{
    "people": [
        {
            "full_name": "Первый Сотрудник",
            "position": "Инженер",
            "status": "Активный"
        },
        {
            "full_name": "Второй Сотрудник",
            "position": "Техник",
            "status": "В отпуске"
        }
    ]
}
```

#### 6. Отчет по просроченным сертификатам
- **Method:** GET
- **URL:** `{{base_url}}/reports/expired-certificates`
- **Headers:**
  - `Accept: application/json`

### Переменные окружения
Создайте переменные окружения в Postman:

- `base_url`: `http://127.0.0.1:8000/api`
- `person_id`: `1` (будет обновляться динамически)

### Тесты для автоматизации
```javascript
// Тест для создания человека
pm.test("Человек создан успешно", function () {
    pm.response.to.have.status(201);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
    pm.expect(response.data.id).to.be.a('number');
    
    // Сохраняем ID для последующих запросов
    pm.environment.set("person_id", response.data.id);
});

// Тест для получения списка людей
pm.test("Список людей получен", function () {
    pm.response.to.have.status(200);
    const response = pm.response.json();
    pm.expect(response.success).to.be.true;
    pm.expect(response.data).to.be.an('array');
    pm.expect(response.pagination).to.have.property('total');
});
```

---

## ⚛️ React

### Компонент для работы с API
```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const API_BASE_URL = 'http://127.0.0.1:8000/api';

const SafetyAPI = () => {
    const [people, setPeople] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [formData, setFormData] = useState({
        full_name: '',
        position: '',
        phone: '',
        status: 'Активный'
    });

    // Получить список людей
    const fetchPeople = async () => {
        setLoading(true);
        try {
            const response = await axios.get(`${API_BASE_URL}/people?per_page=20`);
            if (response.data.success) {
                setPeople(response.data.data);
            }
        } catch (error) {
            setError(error.response?.data?.message || 'Ошибка при загрузке данных');
        } finally {
            setLoading(false);
        }
    };

    // Создать человека
    const createPerson = async (personData) => {
        try {
            const response = await axios.post(`${API_BASE_URL}/people`, personData);
            if (response.data.success) {
                setPeople([...people, response.data.data]);
                setFormData({
                    full_name: '',
                    position: '',
                    phone: '',
                    status: 'Активный'
                });
            }
        } catch (error) {
            setError(error.response?.data?.message || 'Ошибка при создании');
        }
    };

    // Удалить человека
    const deletePerson = async (id) => {
        try {
            const response = await axios.delete(`${API_BASE_URL}/people/${id}`);
            if (response.data.success) {
                setPeople(people.filter(person => person.id !== id));
            }
        } catch (error) {
            setError(error.response?.data?.message || 'Ошибка при удалении');
        }
    };

    // Получить отчет
    const getReport = async () => {
        try {
            const response = await axios.get(`${API_BASE_URL}/reports/expired-certificates`);
            if (response.data.success) {
                console.log('Просроченных сертификатов:', response.data.total);
            }
        } catch (error) {
            setError(error.response?.data?.message || 'Ошибка при получении отчета');
        }
    };

    useEffect(() => {
        fetchPeople();
    }, []);

    const handleSubmit = (e) => {
        e.preventDefault();
        createPerson(formData);
    };

    const handleInputChange = (e) => {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value
        });
    };

    if (loading) return <div>Загрузка...</div>;
    if (error) return <div>Ошибка: {error}</div>;

    return (
        <div>
            <h1>Система управления сертификатами</h1>
            
            {/* Форма добавления */}
            <form onSubmit={handleSubmit}>
                <h2>Добавить человека</h2>
                <input
                    type="text"
                    name="full_name"
                    placeholder="ФИО"
                    value={formData.full_name}
                    onChange={handleInputChange}
                    required
                />
                <input
                    type="text"
                    name="position"
                    placeholder="Должность"
                    value={formData.position}
                    onChange={handleInputChange}
                />
                <input
                    type="text"
                    name="phone"
                    placeholder="Телефон"
                    value={formData.phone}
                    onChange={handleInputChange}
                />
                <select
                    name="status"
                    value={formData.status}
                    onChange={handleInputChange}
                >
                    <option value="Активный">Активный</option>
                    <option value="В отпуске">В отпуске</option>
                    <option value="Уволен">Уволен</option>
                </select>
                <button type="submit">Добавить</button>
            </form>

            {/* Список людей */}
            <div>
                <h2>Список людей</h2>
                <button onClick={getReport}>Получить отчет</button>
                {people.map(person => (
                    <div key={person.id}>
                        <h3>{person.full_name}</h3>
                        <p>Должность: {person.position}</p>
                        <p>Телефон: {person.phone}</p>
                        <p>Статус: {person.status}</p>
                        <button onClick={() => deletePerson(person.id)}>
                            Удалить
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default SafetyAPI;
```

---

## 🟢 Vue.js

### Компонент для работы с API
```vue
<template>
  <div>
    <h1>Система управления сертификатами</h1>
    
    <!-- Форма добавления -->
    <form @submit.prevent="createPerson">
      <h2>Добавить человека</h2>
      <input
        v-model="formData.full_name"
        type="text"
        placeholder="ФИО"
        required
      />
      <input
        v-model="formData.position"
        type="text"
        placeholder="Должность"
      />
      <input
        v-model="formData.phone"
        type="text"
        placeholder="Телефон"
      />
      <select v-model="formData.status">
        <option value="Активный">Активный</option>
        <option value="В отпуске">В отпуске</option>
        <option value="Уволен">Уволен</option>
      </select>
      <button type="submit">Добавить</button>
    </form>

    <!-- Список людей -->
    <div>
      <h2>Список людей</h2>
      <button @click="getReport">Получить отчет</button>
      <div v-if="loading">Загрузка...</div>
      <div v-else-if="error">Ошибка: {{ error }}</div>
      <div v-else>
        <div v-for="person in people" :key="person.id">
          <h3>{{ person.full_name }}</h3>
          <p>Должность: {{ person.position }}</p>
          <p>Телефон: {{ person.phone }}</p>
          <p>Статус: {{ person.status }}</p>
          <button @click="deletePerson(person.id)">Удалить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

const API_BASE_URL = 'http://127.0.0.1:8000/api';

export default {
  name: 'SafetyAPI',
  data() {
    return {
      people: [],
      loading: false,
      error: null,
      formData: {
        full_name: '',
        position: '',
        phone: '',
        status: 'Активный'
      }
    };
  },
  async mounted() {
    await this.fetchPeople();
  },
  methods: {
    async fetchPeople() {
      this.loading = true;
      try {
        const response = await axios.get(`${API_BASE_URL}/people?per_page=20`);
        if (response.data.success) {
          this.people = response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка при загрузке данных';
      } finally {
        this.loading = false;
      }
    },

    async createPerson() {
      try {
        const response = await axios.post(`${API_BASE_URL}/people`, this.formData);
        if (response.data.success) {
          this.people.push(response.data.data);
          this.formData = {
            full_name: '',
            position: '',
            phone: '',
            status: 'Активный'
          };
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка при создании';
      }
    },

    async deletePerson(id) {
      try {
        const response = await axios.delete(`${API_BASE_URL}/people/${id}`);
        if (response.data.success) {
          this.people = this.people.filter(person => person.id !== id);
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка при удалении';
      }
    },

    async getReport() {
      try {
        const response = await axios.get(`${API_BASE_URL}/reports/expired-certificates`);
        if (response.data.success) {
          console.log('Просроченных сертификатов:', response.data.total);
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка при получении отчета';
      }
    }
  }
};
</script>

<style scoped>
form {
  margin-bottom: 20px;
}

input, select, button {
  margin: 5px;
  padding: 8px;
}

button {
  background-color: #007bff;
  color: white;
  border: none;
  cursor: pointer;
}

button:hover {
  background-color: #0056b3;
}
</style>
```

---

## 📝 Заключение

Этот API предоставляет полный набор функций для управления системой сертификатов безопасности:

- ✅ CRUD операции для людей и сертификатов
- ✅ Массовое добавление данных
- ✅ Фильтрация и поиск
- ✅ Пагинация
- ✅ Отчеты
- ✅ Обработка ошибок
- ✅ Поддержка различных языков программирования

API готов к использованию в production среде и может быть легко интегрирован в любую систему.