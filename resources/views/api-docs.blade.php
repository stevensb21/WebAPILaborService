<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Документация API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
    <style>
        .code-block {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .endpoint {
            border-left: 4px solid #0d6efd;
            padding-left: 1rem;
            margin-bottom: 2rem;
        }
        .method-get { border-left-color: #198754; }
        .method-post { border-left-color: #fd7e14; }
        .method-put { border-left-color: #6f42c1; }
        .method-delete { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fas fa-book text-primary"></i>
                        Документация API
                    </h1>
                    <div class="d-flex align-items-center">
                        <span class="me-3 text-muted">
                            <i class="fas fa-user"></i> {{ Auth::user()->name }}
                        </span>
                        <a href="{{ route('safety.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Назад к системе
                        </a>
                        <a href="{{ route('api-tokens.index') }}" class="btn btn-outline-warning me-2">
                            <i class="fas fa-key"></i> Управление токенами
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> Выйти
                            </button>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <!-- Аутентификация -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3><i class="fas fa-shield-alt text-success me-2"></i>Аутентификация</h3>
                            </div>
                            <div class="card-body">
                                <p>Для доступа к API необходимо использовать API токен в заголовке <code>Authorization</code>:</p>
                                <div class="code-block">
Authorization: Bearer YOUR_API_TOKEN
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Важно:</strong> Замените <code>YOUR_API_TOKEN</code> на ваш реальный API токен.
                                </div>
                            </div>
                        </div>

                        <!-- Базовый URL -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3><i class="fas fa-link text-primary me-2"></i>Базовый URL</h3>
                            </div>
                            <div class="card-body">
                                <div class="code-block">
{{ url('/api') }}
                                </div>
                            </div>
                        </div>

                        <!-- Эндпоинты -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3><i class="fas fa-list text-info me-2"></i>Эндпоинты API</h3>
                            </div>
                            <div class="card-body">
                                
                                <!-- Получить список людей -->
                                <div class="endpoint method-get">
                                    <h4><span class="badge bg-success me-2">GET</span>/api/people</h4>
                                    <p>Получить список всех людей с их сертификатами</p>
                                    <div class="code-block">
curl -H "Authorization: Bearer YOUR_API_TOKEN" {{ url('/api/people') }}
                                    </div>
                                    <h6>Ответ:</h6>
                                    <pre><code class="language-json">{
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
}</code></pre>
                                </div>

                                <!-- Получить информацию о человеке -->
                                <div class="endpoint method-get">
                                    <h4><span class="badge bg-success me-2">GET</span>/api/people/{id}</h4>
                                    <p>Получить подробную информацию о конкретном человеке</p>
                                    <div class="code-block">
curl -H "Authorization: Bearer YOUR_API_TOKEN" {{ url('/api/people/1') }}
                                    </div>
                                </div>

                                <!-- Получить список сертификатов -->
                                <div class="endpoint method-get">
                                    <h4><span class="badge bg-success me-2">GET</span>/api/certificates</h4>
                                    <p>Получить список всех сертификатов</p>
                                    <div class="code-block">
curl -H "Authorization: Bearer YOUR_API_TOKEN" {{ url('/api/certificates') }}
                                    </div>
                                </div>

                                <!-- Обновить сертификат человека -->
                                <div class="endpoint method-post">
                                    <h4><span class="badge bg-warning me-2">POST</span>/api/people/{peopleId}/certificates/{certificateId}</h4>
                                    <p>Обновить информацию о сертификате человека</p>
                                    <div class="code-block">
curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "assigned_date": "2024-01-15",
    "certificate_number": "12345",
    "notes": "Примечание"
  }' \
  {{ url('/api/people/1/certificates/1') }}
                                    </div>
                                </div>

                                <hr class="my-5">
                                <h3 class="mb-4"><i class="fas fa-file text-info me-2"></i>Работа с файлами</h3>

                                <!-- Загрузить файл "Все удостоверения" -->
                                <div class="endpoint method-post">
                                    <h4><span class="badge bg-warning me-2">POST</span>/api/people/{id}/certificates-file</h4>
                                    <p>Загрузить файл со всеми удостоверениями для человека</p>
                                    <div class="code-block">
curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "certificates_file=@/path/to/file.pdf" \
  {{ url('/api/people/1/certificates-file') }}
                                    </div>
                                    <h6>Ответ:</h6>
                                    <pre><code class="language-json">{
  "success": true,
  "message": "Файл со всеми удостоверениями успешно загружен",
  "data": {
    "certificates_file": "generated_filename.pdf",
    "certificates_file_original_name": "file.pdf",
    "certificates_file_mime_type": "application/pdf",
    "certificates_file_size": 1024000
  }
}</code></pre>
                                </div>

                                <!-- Скачать файл "Все удостоверения" -->
                                <div class="endpoint method-get">
                                    <h4><span class="badge bg-success me-2">GET</span>/api/people/{id}/certificates-file</h4>
                                    <p>Скачать файл со всеми удостоверениями</p>
                                    <div class="code-block">
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  -o downloaded_file.pdf \
  {{ url('/api/people/1/certificates-file') }}
                                    </div>
                                    <p class="mt-2"><strong>Примечание:</strong> Этот эндпоинт возвращает файл напрямую для скачивания.</p>
                                </div>

                                <!-- Удалить файл "Все удостоверения" -->
                                <div class="endpoint method-delete">
                                    <h4><span class="badge bg-danger me-2">DELETE</span>/api/people/{id}/certificates-file</h4>
                                    <p>Удалить файл со всеми удостоверениями</p>
                                    <div class="code-block">
curl -X DELETE \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  {{ url('/api/people/1/certificates-file') }}
                                    </div>
                                    <h6>Ответ:</h6>
                                    <pre><code class="language-json">{
  "success": true,
  "message": "Файл со всеми удостоверениями успешно удален"
}</code></pre>
                                </div>

                                <!-- Загрузить фото -->
                                <div class="endpoint method-post">
                                    <h4><span class="badge bg-warning me-2">POST</span>/api/people/{id}/photo</h4>
                                    <p>Загрузить фото человека</p>
                                    <div class="code-block">
curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "photo=@/path/to/photo.jpg" \
  {{ url('/api/people/1/photo') }}
                                    </div>
                                </div>

                                <!-- Удалить фото -->
                                <div class="endpoint method-delete">
                                    <h4><span class="badge bg-danger me-2">DELETE</span>/api/people/{id}/photo</h4>
                                    <p>Удалить фото человека</p>
                                    <div class="code-block">
curl -X DELETE \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  {{ url('/api/people/1/photo') }}
                                    </div>
                                </div>

                                <!-- Загрузить паспорт (1 стр) -->
                                <div class="endpoint method-post">
                                    <h4><span class="badge bg-warning me-2">POST</span>/api/people/{id}/passport-page-1</h4>
                                    <p>Загрузить 1 страницу паспорта</p>
                                    <div class="code-block">
curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "passport_page_1=@/path/to/passport1.jpg" \
  {{ url('/api/people/1/passport-page-1') }}
                                    </div>
                                </div>

                                <!-- Удалить паспорт (1 стр) -->
                                <div class="endpoint method-delete">
                                    <h4><span class="badge bg-danger me-2">DELETE</span>/api/people/{id}/passport-page-1</h4>
                                    <p>Удалить 1 страницу паспорта</p>
                                    <div class="code-block">
curl -X DELETE \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  {{ url('/api/people/1/passport-page-1') }}
                                    </div>
                                </div>

                                <!-- Загрузить паспорт (5 стр) -->
                                <div class="endpoint method-post">
                                    <h4><span class="badge bg-warning me-2">POST</span>/api/people/{id}/passport-page-5</h4>
                                    <p>Загрузить 5 страницу паспорта</p>
                                    <div class="code-block">
curl -X POST \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "passport_page_5=@/path/to/passport5.jpg" \
  {{ url('/api/people/1/passport-page-5') }}
                                    </div>
                                </div>

                                <!-- Удалить паспорт (5 стр) -->
                                <div class="endpoint method-delete">
                                    <h4><span class="badge bg-danger me-2">DELETE</span>/api/people/{id}/passport-page-5</h4>
                                    <p>Удалить 5 страницу паспорта</p>
                                    <div class="code-block">
curl -X DELETE \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  {{ url('/api/people/1/passport-page-5') }}
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Коды ответов -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3><i class="fas fa-code text-warning me-2"></i>Коды ответов</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Код</th>
                                                <th>Описание</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><span class="badge bg-success">200</span></td>
                                                <td>Успешный запрос</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-danger">401</span></td>
                                                <td>Неверный или отсутствующий API токен</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-danger">403</span></td>
                                                <td>Доступ запрещен</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-danger">404</span></td>
                                                <td>Ресурс не найден</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-danger">422</span></td>
                                                <td>Ошибка валидации данных</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-danger">500</span></td>
                                                <td>Внутренняя ошибка сервера</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="col-md-4">
                        <!-- Быстрые ссылки -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-rocket text-primary me-2"></i>Быстрые ссылки</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="{{ route('api-tokens.index') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-key me-2"></i>Управление токенами
                                    </a>
                                    <a href="{{ route('api-tokens.create') }}" class="btn btn-outline-success">
                                        <i class="fas fa-plus me-2"></i>Создать токен
                                    </a>
                                    <a href="{{ route('api-examples') }}" class="btn btn-outline-info">
                                        <i class="fas fa-code me-2"></i>Примеры использования
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Примеры использования -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-code text-info me-2"></i>Примеры</h5>
                            </div>
                            <div class="card-body">
                                <h6>JavaScript (fetch)</h6>
                                <div class="code-block" style="font-size: 0.8rem;">
fetch('{{ url('/api/people') }}', {
  headers: {
    'Authorization': 'Bearer YOUR_API_TOKEN'
  }
})
.then(response => response.json())
.then(data => console.log(data));
                                </div>

                                <h6>PHP (cURL)</h6>
                                <div class="code-block" style="font-size: 0.8rem;">
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '{{ url('/api/people') }}');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer YOUR_API_TOKEN'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
                                </div>
                            </div>
                        </div>

                        <!-- Поддержка -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-question-circle text-warning me-2"></i>Поддержка</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">Если у вас возникли вопросы по API:</p>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-envelope me-2"></i>Обратитесь к администратору</li>
                                    <li><i class="fas fa-book me-2"></i>Изучите документацию</li>
                                    <li><i class="fas fa-bug me-2"></i>Проверьте логи ошибок</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
</body>
</html>
