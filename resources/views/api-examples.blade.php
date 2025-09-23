<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Примеры использования API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .code-block {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
        }
        .response-block {
            background-color: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fas fa-code text-primary"></i>
                        Примеры использования API
                    </h1>
                    <div class="d-flex align-items-center">
                        <span class="me-3 text-muted">
                            <i class="fas fa-user"></i> {{ Auth::user()->name }}
                        </span>
                        <a href="{{ route('api-docs') }}" class="btn btn-outline-info me-2">
                            <i class="fas fa-book"></i> Документация
                        </a>
                        <a href="{{ route('api-tokens.index') }}" class="btn btn-outline-warning me-2">
                            <i class="fas fa-key"></i> Токены
                        </a>
                        <a href="{{ route('safety.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Назад
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> Выйти
                            </button>
                        </form>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Важно:</strong> Замените <code>YOUR_API_TOKEN</code> на ваш реальный API токен из раздела "Управление токенами".
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <!-- Получение данных -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4><i class="fas fa-download text-success me-2"></i>Получение данных</h4>
                            </div>
                            <div class="card-body">
                                
                                <h5>1. Получить всех людей</h5>
                                <div class="code-block">curl -H "Authorization: Bearer YOUR_API_TOKEN" 
{{ url('/api/people') }}</div>
                                
                                <h5>2. Получить конкретного человека</h5>
                                <div class="code-block">curl -H "Authorization: Bearer YOUR_API_TOKEN" 
{{ url('/api/people/1') }}</div>
                                
                                <h5>3. Получить компактный список (для ботов)</h5>
                                <div class="code-block">curl -H "Authorization: Bearer YOUR_API_TOKEN" 
{{ url('/api/people/compact') }}</div>
                                
                                <h5>4. Получить все сертификаты</h5>
                                <div class="code-block">curl -H "Authorization: Bearer YOUR_API_TOKEN" 
{{ url('/api/certificates') }}</div>
                                
                                <h5>5. Получить отчеты</h5>
                                <div class="code-block"># Просроченные сертификаты
curl -H "Authorization: Bearer YOUR_API_TOKEN" 
{{ url('/api/reports/expired-certificates') }}

# Сертификаты, истекающие скоро
curl -H "Authorization: Bearer YOUR_API_TOKEN" 
{{ url('/api/reports/expiring-soon') }}

# Статус людей
curl -H "Authorization: Bearer YOUR_API_TOKEN" 
{{ url('/api/reports/people-status') }}</div>
                                
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Отправка данных -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4><i class="fas fa-upload text-warning me-2"></i>Отправка данных</h4>
                            </div>
                            <div class="card-body">
                                
                                <h5>1. Добавить нового человека</h5>
                                <div class="code-block">curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Иванов Иван Иванович",
    "position": "Инженер",
    "phone": "+7-999-123-45-67"
  }' \
{{ url('/api/people') }}</div>
                                
                                <h5>2. Обновить информацию о человеке</h5>
                                <div class="code-block">curl -X PUT \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Петров Петр Петрович",
    "position": "Старший инженер",
    "phone": "+7-999-987-65-43"
  }' \
{{ url('/api/people/1') }}</div>
                                
                                <h5>3. Добавить сертификат человеку</h5>
                                <div class="code-block">curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "people_id": 1,
    "certificate_id": 1,
    "assigned_date": "2024-01-15",
    "certificate_number": "12345",
    "notes": "Примечание"
  }' \
{{ url('/api/people-certificates') }}</div>
                                
                                <h5>4. Обновить сертификат человека</h5>
                                <div class="code-block">curl -X PUT \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "assigned_date": "2024-02-01",
    "certificate_number": "54321",
    "notes": "Обновленное примечание"
  }' \
{{ url('/api/people-certificates/1') }}</div>
                                
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Примеры ответов -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-reply text-info me-2"></i>Примеры ответов</h4>
                    </div>
                    <div class="card-body">
                        
                        <h5>Успешный ответ (200 OK)</h5>
                        <div class="response-block">{
  "success": true,
  "data": [
    {
      "id": 1,
      "full_name": "Иванов Иван Иванович",
      "position": "Инженер",
      "phone": "+7-999-123-45-67",
      "status": 1,
      "certificates": [
        {
          "id": 1,
          "name": "ПБО",
          "assigned_date": "2024-01-15",
          "expiry_date": "2025-01-15",
          "status": 4
        }
      ]
    }
  ]
}</div>
                        
                        <h5>Ошибка аутентификации (401 Unauthorized)</h5>
                        <div class="response-block">{
  "success": false,
  "message": "API токен не предоставлен"
}</div>
                        
                        <h5>Ошибка валидации (422 Unprocessable Entity)</h5>
                        <div class="response-block">{
  "success": false,
  "message": "Ошибка валидации",
  "errors": {
    "full_name": ["Поле имя обязательно для заполнения"],
    "phone": ["Неверный формат телефона"]
  }
}</div>
                        
                    </div>
                </div>

                <!-- JavaScript примеры -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-code text-primary me-2"></i>JavaScript примеры</h4>
                    </div>
                    <div class="card-body">
                        
                        <h5>Получение данных с помощью fetch</h5>
                        <div class="code-block">// Получить всех людей
fetch('{{ url('/api/people') }}', {
  headers: {
    'Authorization': 'Bearer YOUR_API_TOKEN',
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Люди:', data.data);
  } else {
    console.error('Ошибка:', data.message);
  }
})
.catch(error => console.error('Ошибка сети:', error));</div>
                        
                        <h5>Отправка данных</h5>
                        <div class="code-block">// Добавить нового человека
fetch('{{ url('/api/people') }}', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_API_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    full_name: 'Петров Петр Петрович',
    position: 'Инженер',
    phone: '+7-999-123-45-67'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Человек добавлен:', data.data);
  } else {
    console.error('Ошибка:', data.message);
  }
});</div>
                        
                    </div>
                </div>

                <!-- PHP примеры -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-code text-danger me-2"></i>PHP примеры</h4>
                    </div>
                    <div class="card-body">
                        
                        <h5>Получение данных с помощью cURL</h5>
                        <div class="code-block"><?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '{{ url('/api/people') }}');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer YOUR_API_TOKEN',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($data['success']) {
    echo "Люди: " . json_encode($data['data']);
} else {
    echo "Ошибка: " . $data['message'];
}
?></div>
                        
                        <h5>Отправка данных</h5>
                        <div class="code-block"><?php
$data = [
    'full_name' => 'Петров Петр Петрович',
    'position' => 'Инженер',
    'phone' => '+7-999-123-45-67'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '{{ url('/api/people') }}');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer YOUR_API_TOKEN',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if ($result['success']) {
    echo "Человек добавлен: " . json_encode($result['data']);
} else {
    echo "Ошибка: " . $result['message'];
}
?></div>
                        
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
