<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление API токенами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fas fa-key text-primary"></i>
                        Управление API токенами
                    </h1>
                    <div class="d-flex align-items-center">
                        <span class="me-3 text-muted">
                            <i class="fas fa-user"></i> {{ Auth::user()->name }}
                        </span>
                        <a href="{{ route('safety.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Назад к системе
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> Выйти
                            </button>
                        </form>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('new_token'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-key me-2"></i>Новый API токен создан!</h5>
                        <p class="mb-2">Сохраните этот токен в безопасном месте. Он больше не будет показан:</p>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ session('new_token') }}" readonly id="newToken">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToken()">
                                <i class="fas fa-copy"></i> Копировать
                            </button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="{{ route('api-tokens.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Создать новый токен
                    </a>
                    <a href="{{ route('api-docs') }}" class="btn btn-outline-info">
                        <i class="fas fa-book"></i> Документация API
                    </a>
                </div>

                @if($tokens->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Название</th>
                                    <th>Описание</th>
                                    <th>Статус</th>
                                    <th>Последнее использование</th>
                                    <th>Срок действия</th>
                                    <th>Создан</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tokens as $token)
                                    <tr>
                                        <td>
                                            <strong>{{ $token->name }}</strong>
                                        </td>
                                        <td>
                                            @if($token->description)
                                                {{ Str::limit($token->description, 50) }}
                                            @else
                                                <span class="text-muted">Нет описания</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($token->is_active)
                                                <span class="badge bg-success">Активен</span>
                                            @else
                                                <span class="badge bg-secondary">Неактивен</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($token->last_used_at)
                                                {{ $token->last_used_at->format('d.m.Y H:i') }}
                                            @else
                                                <span class="text-muted">Никогда</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($token->expires_at)
                                                {{ $token->expires_at->format('d.m.Y H:i') }}
                                                @if($token->expires_at->isPast())
                                                    <span class="text-danger">(Истек)</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Бессрочно</span>
                                            @endif
                                        </td>
                                        <td>{{ $token->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($token->is_active)
                                                    <form method="POST" action="{{ route('api-tokens.deactivate', $token) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning" 
                                                                onclick="return confirm('Деактивировать токен?')">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('api-tokens.activate', $token) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('api-tokens.destroy', $token) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Удалить токен? Это действие нельзя отменить!')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-key fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">API токены не найдены</h4>
                        <p class="text-muted">Создайте первый API токен для доступа к API</p>
                        <a href="{{ route('api-tokens.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Создать токен
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToken() {
            const tokenInput = document.getElementById('newToken');
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Показать уведомление
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Скопировано!';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }
    </script>
</body>
</html>
