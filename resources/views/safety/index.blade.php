<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Охрана труда - Управление сертификатами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-container { 
            overflow-x: auto; 
            max-height: 80vh;
            overflow-y: auto;
        }
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #212529 !important;
        }
        .status-1 { background-color: #d4edda; }
        .status-2 { background-color: #f8d7da; }
        .status-3 { background-color: #fff3cd; }
        .status-4 { background-color: #e2e3e5; }
        .certificate-cell { min-width: 150px; text-align: center; vertical-align: top; }
        .filter-section { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .btn-edit { font-size: 0.8rem; padding: 2px 6px; }
        .table td, .table th { vertical-align: top; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fas fa-shield-alt text-primary"></i>
                        Управление сертификатами по охране труда
                    </h1>
                    <div class="d-flex align-items-center">
                        <span class="me-3 text-muted">
                            <i class="fas fa-user"></i> {{ Auth::user()->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> Выйти
                            </button>
                        </form>
                    </div>
                </div>
                <div class="d-flex justify-content-end mb-4">
                                         <div>
                         <a href="{{ route('safety.backup') }}" class="btn btn-outline-secondary me-2">
                             <i class="fas fa-database"></i> Скачать резервную копию
                         </a>
                         <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#certificateOrderModal">
                             <i class="fas fa-sort"></i> Порядок сертификатов
                         </button>
                         <button class="btn btn-success me-2" onclick="showAddPersonModal()">
                             <i class="fas fa-user-plus"></i> Добавить человека
                         </button>
                         <button class="btn btn-info me-2" onclick="showAddCertificateModal()">
                             <i class="fas fa-certificate"></i> Добавить сертификат
                         </button>
                         <a href="{{ route('api-tokens.index') }}" class="btn btn-outline-warning">
                             <i class="fas fa-key"></i> API токены
                         </a>
                     </div>
                </div>

                <!-- Фильтры -->
                <div class="filter-section">
                    <form method="GET" action="{{ route('safety.index') }}" class="row g-3">
                        <div class="col-md-2">
                            <label for="search_fio" class="form-label">ФИО</label>
                            <input type="text" class="form-control" id="search_fio" name="search_fio" 
                                   value="{{ request('search_fio') }}" placeholder="Поиск по ФИО">
                        </div>
                        <div class="col-md-2">
                            <label for="search_position" class="form-label">Должность</label>
                            <input type="text" class="form-control" id="search_position" name="search_position" 
                                   value="{{ request('search_position') }}" placeholder="Поиск по должности">
                        </div>
                        <div class="col-md-2">
                            <label for="search_phone" class="form-label">Телефон</label>
                            <input type="text" class="form-control" id="search_phone" name="search_phone" 
                                   value="{{ request('search_phone') }}" placeholder="Поиск по телефону">
                        </div>
                        <div class="col-md-2">
                            <label for="search_status" class="form-label">Статус работника</label>
                            <input type="text" class="form-control" id="search_status" name="search_status" 
                                   value="{{ request('search_status') }}" placeholder="Поиск по статусу">
                        </div>
                        <div class="col-md-2">
                            <label for="certificate_id" class="form-label">Сертификат</label>
                            <select class="form-select" id="certificate_id" name="certificate_id">
                                <option value="">Все сертификаты</option>
                                @foreach($certificates as $certificate)
                                    <option value="{{ $certificate->id }}" {{ request('certificate_id') == $certificate->id ? 'selected' : '' }}>
                                        {{ $certificate->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="certificate_status" class="form-label">Статус сертификата</label>
                            <select class="form-select" id="certificate_status" name="certificate_status">
                                <option value="">Все статусы</option>
                                <option value="1" {{ request('certificate_status') == '1' ? 'selected' : '' }}>Отсутствует</option>
                                <option value="2" {{ request('certificate_status') == '2' ? 'selected' : '' }}>Просрочен</option>
                                <option value="3" {{ request('certificate_status') == '3' ? 'selected' : '' }}>Скоро просрочится</option>
                                <option value="4" {{ request('certificate_status') == '4' ? 'selected' : '' }}>Действует</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Поиск
                            </button>
                            <a href="{{ route('safety.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Сброс
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Таблица -->
                <div class="table-container">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 50px;">№</th>
                                <th>ФИО</th>
                                <th>Должность</th>
                                <th>Телефон</th>
                                <th>СНИЛС</th>
                                <th>ИНН</th>
                                <th>Дата рождения</th>
                                <th>Примечание</th>
                                <th>Статус</th>
                                                                 @foreach($certificates as $certificate)
                                     <th class="certificate-cell">
                                         <div class="d-flex justify-content-between align-items-start">
                                             <div>
                                                 {{ $certificate->name }}
                                                 @if($certificate->expiry_date)
                                                     <br>
                                                     <small class="text-primary-light fw-bold">
                                                         <i class="fas fa-calendar-alt"></i> 
                                                         {{ $certificate->expiry_date }} {{ $certificate->expiry_date == 1 ? 'год' : ($certificate->expiry_date < 5 ? 'года' : 'лет') }}
                                                     </small>
                                                 @endif
                                                 @if($certificate->description)
                                                     <br>
                                                     <a href="#" class="text-decoration-none small text-primary" 
                                                        onclick="showCertificateDescription({{ $certificate->id }})"
                                                        title="Показать описание"
                                                        style="cursor: pointer;">
                                                         <i class="fas fa-info-circle"></i> Описание
                                                     </a>
                                                 @endif
                                             </div>
                                             <div class="d-flex gap-1">
                                                 <button class="btn btn-sm btn-outline-warning" 
                                                     onclick="showEditCertificateModal({{ $certificate->id }}, {{ json_encode($certificate->name) }}, {{ json_encode($certificate->description) }}, {{ $certificate->expiry_date }})"
                                                     title="Редактировать сертификат">
                                                     <i class="fas fa-edit"></i>
                                                 </button>
                                                 <button class="btn btn-sm btn-outline-danger" 
                                                     onclick="deleteCertificate({{ $certificate->id }}, {{ json_encode($certificate->name) }})"
                                                     title="Удалить сертификат">
                                                     <i class="fas fa-trash"></i>
                                                 </button>
                                             </div>
                                         </div>
                                     </th>
                                 @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($people as $index => $person)
                                <tr data-person-id="{{ $person->id }}">
                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                                                         <td>
                                         <div class="d-flex align-items-center">
                                             @if($person->photo)
                                                 <img src="{{ route('safety.photo', basename($person->photo)) }}" 
                                                      alt="Фото {{ $person->full_name }}" 
                                                      class="rounded-circle me-2" 
                                                      style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                                      data-bs-toggle="modal" 
                                                      data-bs-target="#photoModal" 
                                                      data-photo-url="{{ route('safety.photo', basename($person->photo)) }}"
                                                      data-person-name="{{ $person->full_name }}">
                                             @else
                                                 <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                      style="width: 60px; height: 60px;">
                                                     <i class="fas fa-user text-white" style="font-size: 24px;"></i>
                                                 </div>
                                             @endif
                                             <div>
                                                 <strong>{{ $person->full_name }}</strong>
                                                 <div class="small text-muted">
                                                     @if($person->photo)
                                                         <i class="fas fa-image"></i> Фото загружено
                                                     @endif
                                                                                                           @if($person->passport_page_1 || $person->passport_page_5)
                                                          <br><i class="fas fa-id-card"></i> Паспорт: 
                                                          @if($person->passport_page_1)
                                                              <a href="{{ route('safety.passport', basename($person->passport_page_1)) }}" 
                                                                 class="text-decoration-none" title="Скачать 1 страницу паспорта">
                                                                  <i class="fas fa-download"></i> 1 стр
                                                              </a>
                                                          @endif
                                                          @if($person->passport_page_1 && $person->passport_page_5)
                                                              |
                                                          @endif
                                                          @if($person->passport_page_5)
                                                              <a href="{{ route('safety.passport', basename($person->passport_page_5)) }}" 
                                                                 class="text-decoration-none" title="Скачать 5 страницу паспорта">
                                                                  <i class="fas fa-download"></i> 5 стр
                                                              </a>
                                                          @endif
                                                      @endif
                                                      
                                                      @if($person->certificates_file)
                                                          <br><i class="fas fa-certificate"></i> 
                                                          <a href="{{ route('safety.certificates-file', basename($person->certificates_file)) }}" 
                                                             class="text-decoration-none" title="Скачать файл со всеми удостоверениями">
                                                              <i class="fas fa-file-pdf text-danger"></i> Все удостоверения
                                                          </a>
                                                      @endif
                                                 </div>
                                                                                                   <div class="mt-1">
                                                      <button class="btn btn-sm btn-outline-warning me-1" 
                                                              onclick="showEditPersonModal({{ $person->id }}, {{ json_encode($person->full_name) }}, {{ json_encode($person->position) }}, {{ json_encode($person->phone) }}, {{ json_encode($person->snils) }}, {{ json_encode($person->inn) }}, {{ json_encode($person->birth_date ? \Carbon\Carbon::parse($person->birth_date)->format('Y-m-d') : '') }}, {{ json_encode($person->address) }}, {{ json_encode($person->status) }})">
                                                          <i class="fas fa-edit"></i> Редактировать
                                                      </button>
                                                      <button class="btn btn-sm btn-outline-danger" 
                                                              onclick="deletePerson({{ $person->id }}, {{ json_encode($person->full_name) }})">
                                                          <i class="fas fa-trash"></i> Удалить
                                                      </button>
                                                  </div>
                                             </div>
                                         </div>
                                     </td>
                                    <td>{{ $person->position }}</td>
                                    <td>{{ $person->phone }}</td>
                                    <td>{{ $person->snils }}</td>
                                    <td>{{ $person->inn }}</td>
                                    <td>{{ $person->birth_date ? \Carbon\Carbon::parse($person->birth_date)->format('d.m.Y') : '-' }}</td>
                                    <td title="{{ $person->address ?: 'Нет примечания' }}">
                                        {{ $person->address ? Str::limit($person->address, 30) : '-' }}
                                    </td>
                                    <td>
                                        @if($person->status)
                                            <span class="badge bg-primary">{{ $person->status }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    
                                    @foreach($certificates as $certificate)
                                        @php
                                            $personCertificate = $person->certificates->where('id', $certificate->id)->first();
                                            $pivot = $personCertificate ? $personCertificate->pivot : null;
                                        @endphp
                                        <td class="certificate-cell status-{{ $pivot ? $pivot->status : '4' }}" data-certificate-id="{{ $certificate->id }}">
                                            @if($pivot)
                                                <div class="mb-1">
                                                    @php
                                                        $assignedDate = $pivot->assigned_date instanceof \Carbon\Carbon ? $pivot->assigned_date : \Carbon\Carbon::parse($pivot->assigned_date);
                                                        $expiryDate = $assignedDate->copy()->addYears($certificate->expiry_date ?: 1);
                                                        $isExpired = $expiryDate->isPast();
                                                        $isExpiringSoon = now()->diffInDays($expiryDate, false) <= 60 && now()->diffInDays($expiryDate, false) > 0; // 2 месяца = 60 дней, но только если дата в будущем
                                                        
                                                        if ($isExpired) {
                                                            $status = 2; // Просрочен
                                                            $statusClass = 'danger';
                                                            $statusText = 'Просрочен';
                                                        } elseif ($isExpiringSoon) {
                                                            $status = 3; // Скоро просрочится
                                                            $statusClass = 'warning';
                                                            $statusText = 'Скоро просрочится';
                                                        } else {
                                                            $status = 4; // Действует
                                                            $statusClass = 'success';
                                                            $statusText = 'Действует';
                                                        }
                                                    @endphp
                                                    <span class="badge bg-{{ $statusClass }}">
                                                        {{ $statusText }}
                                                    </span>
                                                </div>
                                                <div class="small">
                                                    <div>Выдан: {{ $pivot->assigned_date instanceof \Carbon\Carbon ? $pivot->assigned_date->format('d.m.Y') : \Carbon\Carbon::parse($pivot->assigned_date)->format('d.m.Y') }}</div>
                                                    <div>Номер: {{ $pivot->certificate_number }}</div>
                                                    @php
                                                        $assignedDate = $pivot->assigned_date instanceof \Carbon\Carbon ? $pivot->assigned_date : \Carbon\Carbon::parse($pivot->assigned_date);
                                                        $expiryDate = $assignedDate->copy()->addYears($certificate->expiry_date ?: 1);
                                                        $isExpired = $expiryDate->isPast();
                                                    @endphp
                                                    <div class="{{ $isExpired ? 'text-danger' : 'text-success' }}">
                                                        <i class="fas fa-calendar-alt"></i> 
                                                        Действует до: {{ $expiryDate->format('d.m.Y') }}
                                                        @if($isExpired)
                                                            <i class="fas fa-exclamation-triangle text-danger"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                 @if($pivot->notes)
                                                     <div class="small text-muted" title="{{ $pivot->notes }}">
                                                         <i class="fas fa-sticky-note"></i> {{ Str::limit($pivot->notes, 20) }}
                                                     </div>
                                                 @endif
                                                 
                                                 @if($pivot->certificate_file)
                                                     <div class="small">
                                                         <a href="{{ route('safety.certificate-view', [$person->id, $certificate->id]) }}" 
                                                            class="btn btn-sm btn-outline-info me-1" 
                                                            title="Просмотреть файл сертификата"
                                                            target="_blank">
                                                             <i class="fas fa-eye"></i> Просмотр
                                                         </a>
                                                         <a href="{{ route('safety.certificate-file', [$person->id, $certificate->id]) }}" 
                                                            class="text-decoration-none text-primary" 
                                                            title="Скачать файл сертификата">
                                                             <i class="fas fa-download"></i> 
                                                         </a>
                                                     </div>
                                                 @endif
                                            @else
                                                <span class="badge bg-secondary">Отсутствует</span>
                                            @endif
                                            
                                            <div class="d-flex gap-1 mt-1">
                                                <button class="btn btn-sm btn-outline-primary btn-edit" 
                                                    onclick="editCertificate({{ $person->id }}, {{ $certificate->id }}, {{ json_encode($pivot ? ($pivot->assigned_date instanceof \Carbon\Carbon ? $pivot->assigned_date->format('Y-m-d') : \Carbon\Carbon::parse($pivot->assigned_date)->format('Y-m-d')) : '') }}, {{ json_encode($pivot ? $pivot->certificate_number : '') }}, {{ json_encode($pivot ? $pivot->notes : '') }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                                @if($pivot)
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deletePersonCertificate({{ $person->id }}, {{ $certificate->id }}, {{ json_encode($person->full_name) }}, {{ json_encode($certificate->name) }})"
                                                            title="Удалить сертификат">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 8 + $certificates->count() }}" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> Данные не найдены
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать сертификат</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm">
                    <div class="modal-body">
                        <input type="hidden" id="peopleId" name="people_id">
                        <input type="hidden" id="certificateId" name="certificate_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Статус</label>
                            <div class="form-control-plaintext">
                                <small class="text-muted">Статус определяется автоматически на основе срока действия</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="assigned_date" class="form-label">Дата выдачи</label>
                            <input type="date" class="form-control" id="assigned_date" name="assigned_date">
                        </div>
                        
                        <div class="mb-3">
                            <label for="certificate_number" class="form-label">Номер сертификата</label>
                            <input type="text" class="form-control" id="certificate_number" name="certificate_number">
                        </div>
                        
                        <div class="mb-3">
                            <label for="certificate_file" class="form-label">Файл сертификата</label>
                            <input type="file" class="form-control" id="certificate_file" name="certificate_file" accept=".pdf">
                            <small class="form-text text-muted">Поддерживаемые форматы: PDF. Максимальный размер: 200MB</small>
                        </div>
                         
                         <div class="mb-3">
                             <label for="notes" class="form-label">Примечания</label>
                             <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500"></textarea>
                         </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления человека -->
    <div class="modal fade" id="addPersonModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить нового человека</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPersonForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">ФИО *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="position" class="form-label">Должность</label>
                                    <input type="text" class="form-control" id="position" name="position">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Телефон</label>
                                    <input type="text" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label for="snils" class="form-label">СНИЛС</label>
                                    <input type="text" class="form-control" id="snils" name="snils">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="inn" class="form-label">ИНН</label>
                                    <input type="text" class="form-control" id="inn" name="inn">
                                </div>
                                <div class="mb-3">
                                    <label for="birth_date" class="form-label">Дата рождения</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date">
                                </div>
                                                                 <div class="mb-3">
                                     <label for="address" class="form-label">Примечание</label>
                                     <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                 </div>
                                 <div class="mb-3">
                                     <label for="status" class="form-label">Статус работника</label>
                                     <input type="text" class="form-control" id="status" name="status" placeholder="Введите статус работника">
                                 </div>
                                 <div class="mb-3">
                                     <label for="photo" class="form-label">Фотография</label>
                                     <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                     <small class="form-text text-muted">Поддерживаемые форматы: JPEG, PNG, JPG, GIF. Максимальный размер: 2MB</small>
                                 </div>
                                 <div class="mb-3">
                                     <label for="passport_page_1" class="form-label">Паспорт (1 страница)</label>
                                     <input type="file" class="form-control" id="passport_page_1" name="passport_page_1" accept=".pdf,image/*">
                                     <small class="form-text text-muted">Поддерживаемые форматы: PDF, JPEG, PNG, JPG. Максимальный размер: 5MB</small>
                                 </div>
                                 <div class="mb-3">
                                     <label for="passport_page_5" class="form-label">Паспорт (5 страница)</label>
                                     <input type="file" class="form-control" id="passport_page_5" name="passport_page_5" accept=".pdf,image/*">
                                     <small class="form-text text-muted">Поддерживаемые форматы: PDF, JPEG, PNG, JPG. Максимальный размер: 5MB</small>
                                 </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-success">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления сертификата -->
    <div class="modal fade" id="addCertificateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить новый сертификат</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCertificateForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cert_name" class="form-label">Название сертификата *</label>
                            <input type="text" class="form-control" id="cert_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="cert_description" class="form-label">Описание</label>
                            <textarea class="form-control" id="cert_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="cert_expiry_date" class="form-label">Срок действия (лет)</label>
                            <input type="number" class="form-control" id="cert_expiry_date" name="expiry_date_years" min="1" max="10">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-info">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
         </div>

     <!-- Модальное окно редактирования человека -->
     <div class="modal fade" id="editPersonModal" tabindex="-1">
         <div class="modal-dialog modal-lg">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Редактировать человека</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                 </div>
                 <form id="editPersonForm">
                     <div class="modal-body">
                         <input type="hidden" id="edit_person_id" name="person_id">
                         <div class="row">
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="edit_full_name" class="form-label">ФИО *</label>
                                     <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                                 </div>
                                 <div class="mb-3">
                                     <label for="edit_position" class="form-label">Должность</label>
                                     <input type="text" class="form-control" id="edit_position" name="position">
                                 </div>
                                 <div class="mb-3">
                                     <label for="edit_phone" class="form-label">Телефон</label>
                                     <input type="text" class="form-control" id="edit_phone" name="phone">
                                 </div>
                                 <div class="mb-3">
                                     <label for="edit_snils" class="form-label">СНИЛС</label>
                                     <input type="text" class="form-control" id="edit_snils" name="snils">
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="edit_inn" class="form-label">ИНН</label>
                                     <input type="text" class="form-control" id="edit_inn" name="inn">
                                 </div>
                                 <div class="mb-3">
                                     <label for="edit_birth_date" class="form-label">Дата рождения</label>
                                     <input type="date" class="form-control" id="edit_birth_date" name="birth_date">
                                 </div>
                                 <div class="mb-3">
                                     <label for="edit_address" class="form-label">Примечание</label>
                                     <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                                 </div>
                                 <div class="mb-3">
                                     <label for="edit_status" class="form-label">Статус работника</label>
                                     <input type="text" class="form-control" id="edit_status" name="status" placeholder="Введите статус работника">
                                 </div>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-md-4">
                                 <div class="mb-3">
                                     <label for="edit_photo" class="form-label">Фотография</label>
                                     <input type="file" class="form-control" id="edit_photo" name="photo" accept="image/*">
                                     <small class="form-text text-muted">Поддерживаемые форматы: JPEG, PNG, JPG, GIF. Максимальный размер: 2MB</small>
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-3">
                                     <label for="edit_passport_page_1" class="form-label">Паспорт (1 страница)</label>
                                     <input type="file" class="form-control" id="edit_passport_page_1" name="passport_page_1" accept=".pdf,image/*">
                                     <small class="form-text text-muted">Поддерживаемые форматы: PDF, JPEG, PNG, JPG. Максимальный размер: 5MB</small>
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-3">
                                     <label for="edit_passport_page_5" class="form-label">Паспорт (5 страница)</label>
                                     <input type="file" class="form-control" id="edit_passport_page_5" name="passport_page_5" accept=".pdf,image/*">
                                     <small class="form-text text-muted">Поддерживаемые форматы: PDF, JPEG, PNG, JPG. Максимальный размер: 5MB</small>
                                 </div>
                             </div>
                         </div>
                         
                         <!-- Секция для загрузки файла со всеми удостоверениями -->
                         <div class="row">
                             <div class="col-12">
                                 <div class="mb-3">
                                     <label for="edit_certificates_file" class="form-label">Файл со всеми удостоверениями (PDF)</label>
                                     <input type="file" class="form-control" id="edit_certificates_file" 
                                            name="certificates_file" accept=".pdf">
                                     <small class="form-text text-muted">Поддерживаемый формат: PDF. Максимальный размер: 200MB</small>
                                 </div>
                                 
                                 <!-- Отображение текущих файлов -->
                                 <div id="currentFilesSection" class="mb-3" style="display: none;">
                                     <h6 class="text-primary">Прикрепленные файлы:</h6>
                                     <div id="currentFilesList"></div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                         <button type="submit" class="btn btn-warning">Сохранить изменения</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>

     <!-- Модальное окно просмотра фотографии -->
     <div class="modal fade" id="photoModal" tabindex="-1">
         <div class="modal-dialog modal-xl">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Фотография</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                 </div>
                 <div class="modal-body text-center">
                     <img id="photoModalImage" src="" alt="" class="img-fluid" style="max-height: 80vh; max-width: 100%;">
                     <p id="photoModalName" class="mt-3 text-muted fs-5"></p>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                 </div>
             </div>
         </div>
     </div>

     <!-- Модальное окно описания сертификата -->
     <div class="modal fade" id="certificateDescriptionModal" tabindex="-1">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Описание сертификата</h5>
                     <button type="button" class="btn-close" onclick="closeCertificateDescriptionModal()"></button>
                 </div>
                 <div class="modal-body">
                     <h6 id="certificateName" class="text-primary mb-3"></h6>
                     <p id="certificateDescription" class="text-muted"></p>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" onclick="closeCertificateDescriptionModal()">Закрыть</button>
                 </div>
             </div>
         </div>
     </div>

     <!-- Модальное окно редактирования сертификата -->
     <div class="modal fade" id="editCertificateModal" tabindex="-1">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Редактировать сертификат</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                 </div>
                 <form id="editCertificateForm">
                     <div class="modal-body">
                         <input type="hidden" id="edit_certificate_id" name="certificate_id">
                         <div class="mb-3">
                             <label for="edit_cert_name" class="form-label">Название сертификата *</label>
                             <input type="text" class="form-control" id="edit_cert_name" name="name" required>
                         </div>
                         <div class="mb-3">
                             <label for="edit_cert_description" class="form-label">Описание</label>
                             <textarea class="form-control" id="edit_cert_description" name="description" rows="3"></textarea>
                         </div>
                         <div class="mb-3">
                             <label for="edit_cert_expiry_date" class="form-label">Срок действия (лет)</label>
                             <input type="number" class="form-control" id="edit_cert_expiry_date" name="expiry_date" min="1" max="10">
                         </div>
                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                         <button type="submit" class="btn btn-warning">Сохранить изменения</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>

    <!-- Модальное окно для изменения порядка сертификатов -->
    @include('safety.certificate-order-modal')

    <!-- Модальное окно объединения файлов сертификатов -->
    <div class="modal fade" id="mergeCertificatesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить к файлу сертификатов</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="mergeCertificatesForm">
                    <div class="modal-body">
                        <input type="hidden" id="merge_person_id" name="person_id">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Новый PDF файл будет добавлен в начало существующего файла сертификатов.
                        </div>
                        
                        <div class="mb-3">
                            <label for="merge_new_file" class="form-label">Новый PDF файл *</label>
                            <input type="file" class="form-control" id="merge_new_file" name="new_file" accept=".pdf" required>
                            <small class="form-text text-muted">Поддерживаемый формат: PDF. Максимальный размер: 200MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Добавить к файлу
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Универсальная навигация по полям ввода в модальных окнах стрелками вверх/вниз
        document.addEventListener('DOMContentLoaded', function() {
            // Находим все модальные окна
            const modals = document.querySelectorAll('.modal');
            
            modals.forEach(modal => {
                modal.addEventListener('shown.bs.modal', function() {
                    // Получаем все интерактивные поля в текущем модальном окне
                    const fields = modal.querySelectorAll('input:not([type="hidden"]):not([type="file"]), select, textarea');
                    const fieldArray = Array.from(fields).filter(f => !f.disabled && !f.readOnly);
                    
                    if (fieldArray.length === 0) return;
                    
                    // Устанавливаем фокус на первое поле при открытии модального окна
                    setTimeout(() => {
                        fieldArray[0].focus();
                    }, 100);
                    
                    // Обработчик для навигации стрелками
                    const handleArrowNavigation = function(e) {
                        // Проверяем, что фокус находится внутри этого модального окна
                        if (!modal.contains(document.activeElement)) return;
                        
                        const currentField = document.activeElement;
                        const currentIndex = fieldArray.indexOf(currentField);
                        
                        if (currentIndex === -1) return;
                        
                        // Стрелка вниз - переход к следующему полю
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            const nextIndex = (currentIndex + 1) % fieldArray.length;
                            fieldArray[nextIndex].focus();
                        }
                        
                        // Стрелка вверх - переход к предыдущему полю
                        if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            const prevIndex = (currentIndex - 1 + fieldArray.length) % fieldArray.length;
                            fieldArray[prevIndex].focus();
                        }
                    };
                    
                    // Добавляем обработчик для этого модального окна
                    modal.addEventListener('keydown', handleArrowNavigation);
                    
                    // Удаляем обработчик при закрытии модального окна
                    modal.addEventListener('hidden.bs.modal', function cleanup() {
                        modal.removeEventListener('keydown', handleArrowNavigation);
                        modal.removeEventListener('hidden.bs.modal', cleanup);
                    });
                });
            });
        });
    </script>
    <script>
                          function showCertificateDescription(certificateId) {
             console.log('Opening certificate description for ID:', certificateId);
             
             const nameElement = document.getElementById('certificateName');
             const descElement = document.getElementById('certificateDescription');
             const modalElement = document.getElementById('certificateDescriptionModal');
             
             if (!nameElement || !descElement || !modalElement) {
                 console.error('Modal elements not found');
                 alert('Ошибка: элементы модального окна не найдены');
                 return;
             }
             
             // Показываем индикатор загрузки
             nameElement.textContent = 'Загрузка...';
             descElement.textContent = '';
             
             try {
                 // Открываем модальное окно
                 if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                     const modal = new bootstrap.Modal(modalElement);
                     modal.show();
                 } else {
                     // Альтернативный способ - показать модальное окно напрямую
                     modalElement.style.display = 'block';
                     modalElement.classList.add('show');
                     document.body.classList.add('modal-open');
                     
                     // Добавить backdrop
                     const backdrop = document.createElement('div');
                     backdrop.className = 'modal-backdrop fade show';
                     backdrop.id = 'certificateDescriptionBackdrop';
                     document.body.appendChild(backdrop);
                     
                     // Добавить обработчик клика на backdrop для закрытия
                     backdrop.addEventListener('click', closeCertificateDescriptionModal);
                     
                     // Добавить обработчик клавиши Escape
                     const escapeHandler = function(event) {
                         if (event.key === 'Escape') {
                             closeCertificateDescriptionModal();
                             document.removeEventListener('keydown', escapeHandler);
                         }
                     };
                     document.addEventListener('keydown', escapeHandler);
                 }
                 
                 // Получаем данные сертификата через AJAX
                 fetch(`/safety/certificate/${certificateId}/description`)
                     .then(response => {
                         if (!response.ok) {
                             throw new Error(`HTTP error! status: ${response.status}`);
                         }
                         return response.json();
                     })
                     .then(data => {
                         if (data.success) {
                             nameElement.textContent = data.certificate.name;
                             descElement.textContent = data.certificate.description;
                         } else {
                             nameElement.textContent = 'Ошибка';
                             descElement.textContent = data.message || 'Не удалось загрузить описание';
                         }
                     })
                     .catch(error => {
                         console.error('Error fetching certificate description:', error);
                         nameElement.textContent = 'Ошибка';
                         descElement.textContent = 'Не удалось загрузить описание сертификата';
                     });
                     
             } catch (error) {
                 console.error('Error showing modal:', error);
                 alert('Ошибка при открытии модального окна: ' + error.message);
             }
         }

         function closeCertificateDescriptionModal() {
             const modalElement = document.getElementById('certificateDescriptionModal');
             const backdrop = document.getElementById('certificateDescriptionBackdrop');
             
             if (modalElement) {
                 modalElement.style.display = 'none';
                 modalElement.classList.remove('show');
                 document.body.classList.remove('modal-open');
             }
             
             // Удаляем все backdrop элементы
             const allBackdrops = document.querySelectorAll('.modal-backdrop');
             allBackdrops.forEach(backdrop => {
                 // Удаляем обработчики событий перед удалением
                 backdrop.removeEventListener('click', closeCertificateDescriptionModal);
                 backdrop.remove();
             });
             
             // Также удаляем конкретный backdrop если он есть
             if (backdrop) {
                 backdrop.removeEventListener('click', closeCertificateDescriptionModal);
                 backdrop.remove();
             }
             
             // Убираем overflow: hidden с body если он был добавлен
             document.body.style.overflow = '';
             document.body.style.paddingRight = '';
             
             // Удаляем обработчик клавиши Escape
             document.removeEventListener('keydown', function(event) {
                 if (event.key === 'Escape') {
                     closeCertificateDescriptionModal();
                 }
             });
         }

         function editCertificate(peopleId, certificateId, assignedDate, certificateNumber, notes) {
             document.getElementById('peopleId').value = peopleId;
             document.getElementById('certificateId').value = certificateId;
             document.getElementById('assigned_date').value = assignedDate;
             document.getElementById('certificate_number').value = certificateNumber;
             document.getElementById('notes').value = notes;
             
             new bootstrap.Modal(document.getElementById('editModal')).show();
         }

        function showAddPersonModal() {
            new bootstrap.Modal(document.getElementById('addPersonModal')).show();
        }

                 function showAddCertificateModal() {
             new bootstrap.Modal(document.getElementById('addCertificateModal')).show();
         }

         function showEditPersonModal(id, fullName, position, phone, snils, inn, birthDate, address, status) {
             document.getElementById('edit_person_id').value = id;
             document.getElementById('edit_full_name').value = fullName;
             document.getElementById('edit_position').value = position;
             document.getElementById('edit_phone').value = phone;
             document.getElementById('edit_snils').value = snils;
             document.getElementById('edit_inn').value = inn;
             document.getElementById('edit_birth_date').value = birthDate;
             document.getElementById('edit_address').value = address;
             document.getElementById('edit_status').value = status || '';
             
             // Загружаем информацию о файлах
             loadPersonFiles(id);
             
             new bootstrap.Modal(document.getElementById('editPersonModal')).show();
         }

         // Загрузить информацию о файлах человека
         function loadPersonFiles(personId) {
             // Используем веб-маршрут (не требует API токена)
             fetch(`/safety/person-files/${personId}`, {
                 headers: {
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     'Content-Type': 'application/json',
                     'Accept': 'application/json'
                 }
             })
             .then(response => {
                 if (!response.ok) {
                     throw new Error(`HTTP error! status: ${response.status}`);
                 }
                 return response.json();
             })
             .then(data => {
                 if (data.success) {
                     displayCurrentFiles(data.data);
                 } else {
                     console.error('Error loading person files:', data.message);
                     displayCurrentFiles({});
                 }
             })
             .catch(error => {
                 console.error('Error loading person files:', error);
                 // Показываем заглушку если не удалось загрузить
                 displayCurrentFiles({});
             });
         }

         // Отобразить текущие файлы
         function displayCurrentFiles(person) {
             console.log('Displaying current files:', person);
             
             const filesSection = document.getElementById('currentFilesSection');
             const filesList = document.getElementById('currentFilesList');
             
             let filesHtml = '';
             let hasFiles = false;

             // Фото
             if (person.photo) {
                 hasFiles = true;
                 filesHtml += `
                     <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                         <div class="d-flex align-items-center">
                             <i class="fas fa-image text-primary me-2"></i>
                             <span>Фото: ${person.photo_original_name || person.photo}</span>
                         </div>
                         <div>
                             <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${person.id}, 'photo')">
                                 <i class="fas fa-trash"></i>
                             </button>
                         </div>
                     </div>
                 `;
             }

             // Паспорт 1 страница
             if (person.passport_page_1) {
                 hasFiles = true;
                 filesHtml += `
                     <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                         <div class="d-flex align-items-center">
                             <i class="fas fa-id-card text-info me-2"></i>
                             <span>Паспорт (1 стр): ${person.passport_page_1_original_name || person.passport_page_1}</span>
                         </div>
                         <div>
                             <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${person.id}, 'passport_page_1')">
                                 <i class="fas fa-trash"></i>
                             </button>
                         </div>
                     </div>
                 `;
             }

             // Паспорт 5 страница
             if (person.passport_page_5) {
                 hasFiles = true;
                 filesHtml += `
                     <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                         <div class="d-flex align-items-center">
                             <i class="fas fa-id-card text-info me-2"></i>
                             <span>Паспорт (5 стр): ${person.passport_page_5_original_name || person.passport_page_5}</span>
                         </div>
                         <div>
                             <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${person.id}, 'passport_page_5')">
                                 <i class="fas fa-trash"></i>
                             </button>
                         </div>
                     </div>
                 `;
             }

             // Файл со всеми удостоверениями
             if (person.certificates_file) {
                 hasFiles = true;
                 // Показываем более понятное имя файла
                 const displayName = person.certificates_file_original_name || person.certificates_file;
                 const cleanName = displayName.replace(/_certificates\.pdf$/, '').replace(/_/g, ' ');
                 
                 filesHtml += `
                     <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                         <div class="d-flex align-items-center">
                             <i class="fas fa-certificate text-warning me-2"></i>
                             <span>Удостоверения: ${cleanName}</span>
                         </div>
                         <div>
                             <button class="btn btn-sm btn-outline-success me-1" onclick="showMergeCertificatesModal(${person.id})" title="Добавить к существующему файлу">
                                 <i class="fas fa-plus"></i>
                             </button>
                             <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${person.id}, 'certificates_file')">
                                 <i class="fas fa-trash"></i>
                             </button>
                         </div>
                     </div>
                 `;
             }

             if (hasFiles) {
                 filesSection.style.display = 'block';
                 filesList.innerHTML = filesHtml;
             } else {
                 filesSection.style.display = 'none';
             }
         }

         // Удалить файл
         function deleteFile(personId, fileType) {
             if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
                 return;
             }

             const endpoints = {
                 'photo': `/safety/delete-photo/${personId}`,
                 'passport_page_1': `/safety/delete-passport-page-1/${personId}`,
                 'passport_page_5': `/safety/delete-passport-page-5/${personId}`,
                 'certificates_file': `/safety/delete-certificates-file/${personId}`
             };

             fetch(endpoints[fileType], {
                 method: 'DELETE',
                 headers: {
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     'Content-Type': 'application/json'
                 }
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     alert(data.message);
                     // Перезагружаем список файлов
                     loadPersonFiles(personId);
                     // Обновляем страницу для отображения изменений
                     setTimeout(() => {
                         location.reload();
                     }, 1000);
                 } else {
                     alert('Ошибка: ' + data.message);
                 }
             })
             .catch(error => {
                 console.error('Error deleting file:', error);
                 alert('Ошибка при удалении файла');
             });
         }

         // Получить API токен из мета-тега или localStorage
         function getApiToken() {
             // Пробуем получить из localStorage
             let token = localStorage.getItem('api_token');
             if (token) {
                 console.log('Using token from localStorage:', token.substring(0, 10) + '...');
                 return token;
             }
             
             // Используем токен, переданный с сервера
             @if(isset($apiToken))
                 console.log('Using server token:', '{{ $apiToken }}'.substring(0, 10) + '...');
                 return '{{ $apiToken }}';
             @else
                 console.log('Using fallback test token');
                 return 'test-token';
             @endif
         }

         // Показать модальное окно объединения файлов сертификатов
         function showMergeCertificatesModal(personId) {
             document.getElementById('merge_person_id').value = personId;
             document.getElementById('merge_new_file').value = '';
             new bootstrap.Modal(document.getElementById('mergeCertificatesModal')).show();
         }

         // Обработчик формы объединения файлов
         document.getElementById('mergeCertificatesForm').addEventListener('submit', function(e) {
             e.preventDefault();
             
             const personId = document.getElementById('merge_person_id').value;
             const fileInput = document.getElementById('merge_new_file');
             
             if (!fileInput.files[0]) {
                 alert('Пожалуйста, выберите PDF файл');
                 return;
             }
             
             const formData = new FormData();
             formData.append('new_file', fileInput.files[0]);
             
             // Показываем индикатор загрузки
             const submitBtn = this.querySelector('button[type="submit"]');
             const originalText = submitBtn.innerHTML;
             submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Объединение...';
             submitBtn.disabled = true;
             
             const token = getApiToken();
             console.log('Making merge request with token:', token.substring(0, 10) + '...');
             
             fetch(`/api/people/${personId}/certificates-file/merge`, {
                 method: 'POST',
                 headers: {
                     'Authorization': 'Bearer ' + token,
                 },
                 body: formData
             })
             .then(response => {
                 console.log('Merge response status:', response.status);
                 if (!response.ok) {
                     return response.json().then(errorData => {
                         throw new Error(`HTTP ${response.status}: ${errorData.message || 'Unknown error'}`);
                     });
                 }
                 return response.json();
             })
             .then(data => {
                 console.log('Merge response data:', data);
                 if (data.success) {
                     alert('Файлы успешно объединены!');
                     // Закрываем модальное окно
                     bootstrap.Modal.getInstance(document.getElementById('mergeCertificatesModal')).hide();
                     // Обновляем информацию о файлах
                     console.log('Reloading person files after merge...');
                     loadPersonFiles(personId);
                 } else {
                     alert('Ошибка: ' + data.message);
                 }
             })
             .catch(error => {
                 console.error('Error merging files:', error);
                 alert('Ошибка при объединении файлов: ' + error.message);
             })
             .finally(() => {
                 // Восстанавливаем кнопку
                 submitBtn.innerHTML = originalText;
                 submitBtn.disabled = false;
             });
         });

         function showEditCertificateModal(id, name, description, expiryDate) {
             document.getElementById('edit_certificate_id').value = id;
             document.getElementById('edit_cert_name').value = name;
             document.getElementById('edit_cert_description').value = description || '';
             document.getElementById('edit_cert_expiry_date').value = expiryDate || '';
             
             new bootstrap.Modal(document.getElementById('editCertificateModal')).show();
         }

         function deletePerson(id, fullName) {
             if (confirm(`Вы уверены, что хотите удалить человека "${fullName}"? Это действие нельзя отменить.`)) {
                 fetch(`/safety/delete-person/${id}`, {
                     method: 'DELETE',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     }
                 })
                 .then(response => {
                    console.log('Response status:', response.status);
                     if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(`HTTP error! status: ${response.status}, message: ${errorData.message || 'Unknown error'}`);
                        });
                     }
                     return response.json();
                 })
                 .then(data => {
                    console.log('Response data:', data);
                     if (data.success) {
                        alert('Человек успешно удален');
                        // Принудительное обновление страницы с очисткой кэша
                        window.location.reload(true);
                     } else {
                         alert(data.message || 'Произошла ошибка при удалении человека');
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     alert('Произошла ошибка при удалении человека: ' + error.message);
                 });
             }
         }

         function deleteCertificate(id, certificateName) {
             if (confirm(`Вы уверены, что хотите удалить сертификат "${certificateName}"? Это удалит весь столбец сертификата для всех людей. Это действие нельзя отменить.`)) {
                 fetch(`/safety/delete-certificate/${id}`, {
                     method: 'DELETE',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     }
                 })
                 .then(response => {
                     if (!response.ok) {
                         throw new Error(`HTTP error! status: ${response.status}`);
                     }
                     return response.json();
                 })
                 .then(data => {
                     if (data.success) {
                         location.reload();
                     } else {
                         alert(data.message || 'Произошла ошибка при удалении сертификата');
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     alert('Произошла ошибка при удалении сертификата: ' + error.message);
                 });
             }
         }

         function deletePersonCertificate(personId, certificateId, personName, certificateName) {
             if (confirm(`Вы уверены, что хотите удалить сертификат "${certificateName}" у человека "${personName}"? Это действие нельзя отменить.`)) {
                 fetch(`/safety/delete-person-certificate/${personId}/${certificateId}`, {
                     method: 'DELETE',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     }
                 })
                 .then(response => {
                     if (!response.ok) {
                         throw new Error(`HTTP error! status: ${response.status}`);
                     }
                     return response.json();
                 })
                 .then(data => {
                     if (data.success) {
                         // Обновляем только нужную ячейку сертификата
                         updateCertificateCellAfterDelete(personId, certificateId);
                     } else {
                         alert(data.message || 'Произошла ошибка при удалении сертификата');
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     alert('Произошла ошибка при удалении сертификата: ' + error.message);
                 });
             }
         }

         // Функция для обновления ячейки сертификата после удаления
         function updateCertificateCellAfterDelete(personId, certificateId) {
             // Находим строку человека
             const personRow = document.querySelector(`tr[data-person-id="${personId}"]`);
             if (!personRow) return;
             
             // Находим ячейку сертификата
             const certificateCell = personRow.querySelector(`td[data-certificate-id="${certificateId}"]`);
             if (!certificateCell) return;
             
             // Обновляем содержимое ячейки на "Отсутствует"
             certificateCell.className = 'certificate-cell status-1';
             certificateCell.innerHTML = `
                 <span class="badge bg-secondary">Отсутствует</span>
                 <div class="d-flex gap-1 mt-1">
                     <button class="btn btn-sm btn-outline-primary btn-edit" 
                             onclick="editCertificate(${personId}, ${certificateId}, '', '', '')">
                         <i class="fas fa-edit"></i>
                     </button>
                 </div>
             `;
         }

                 document.getElementById('editForm').addEventListener('submit', function(e) {
             e.preventDefault();
             
             const formData = new FormData(this);
             const peopleId = formData.get('people_id');
             const certificateId = formData.get('certificate_id');
             
                              fetch(`/safety/update-certificate/${peopleId}/${certificateId}`, {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                     },
                     body: formData
                 })
             .then(response => {
                 if (!response.ok) {
                     throw new Error(`HTTP error! status: ${response.status}`);
                 }
                 return response.json();
             })
             .then(data => {
                 if (data.success) {
                     // Обновляем только нужную ячейку сертификата
                     updateCertificateCell(peopleId, certificateId, data.data);
                     // Закрываем модальное окно
                     bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                 } else {
                     alert(data.message || 'Произошла ошибка при сохранении сертификата');
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 let errorMessage = 'Произошла ошибка при сохранении: ' + error.message;
                 
                 if (error.message.includes('413')) {
                     errorMessage = 'Файл слишком большой. Максимальный размер: 200MB. Попробуйте уменьшить размер файла или сжать PDF.';
                 } else if (error.message.includes('422')) {
                     errorMessage = 'Ошибка валидации. Проверьте правильность заполнения полей.';
                 }
                 
                 alert(errorMessage);
             });
          });

         document.getElementById('editPersonForm').addEventListener('submit', function(e) {
             e.preventDefault();
             
             const formData = new FormData(this);
             const personId = formData.get('person_id');
             
             fetch(`/safety/update-person/${personId}`, {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                 },
                 body: formData
             })
             .then(response => {
                 if (!response.ok) {
                     throw new Error(`HTTP error! status: ${response.status}`);
                 }
                 return response.json();
             })
             .then(data => {
                 if (data.success) {
                     // Обновляем только нужную строку человека
                     updatePersonRow(personId, data.data);
                     // Закрываем модальное окно
                     bootstrap.Modal.getInstance(document.getElementById('editPersonModal')).hide();
                 } else {
                     let errorMessage = data.message || 'Произошла ошибка при обновлении человека';
                     if (data.errors) {
                         errorMessage += '\n\nДетали ошибок:\n';
                         for (let field in data.errors) {
                             errorMessage += field + ': ' + data.errors[field].join(', ') + '\n';
                         }
                     }
                     alert(errorMessage);
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 alert('Произошла ошибка при обновлении человека: ' + error.message);
             });
         });

         document.getElementById('addPersonForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/safety/store-person', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Произошла ошибка при добавлении человека');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при добавлении человека: ' + error.message);
            });
        });

        document.getElementById('addCertificateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const expiryYears = formData.get('expiry_date_years');
            
            const requestData = {
                name: formData.get('name'),
                description: formData.get('description')
            };
            
            if (expiryYears && expiryYears > 0) {
                requestData.expiry_date = parseInt(expiryYears);
            }
            
            fetch('/safety/store-certificate', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Произошла ошибка при добавлении сертификата');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при добавлении сертификата: ' + error.message);
                         });
         });

        document.getElementById('editCertificateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const certificateId = formData.get('certificate_id');
            
            const requestData = {
                name: formData.get('name'),
                description: formData.get('description'),
                expiry_date: formData.get('expiry_date') ? parseInt(formData.get('expiry_date')) : null
            };
            
            fetch(`/safety/update-certificate-info/${certificateId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Произошла ошибка при обновлении сертификата');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении сертификата: ' + error.message);
            });
        });

         // Обработка просмотра фотографии
         document.addEventListener('DOMContentLoaded', function() {
             const photoModal = document.getElementById('photoModal');
             if (photoModal) {
                 photoModal.addEventListener('show.bs.modal', function(event) {
                     const button = event.relatedTarget;
                     const photoUrl = button.getAttribute('data-photo-url');
                     const personName = button.getAttribute('data-person-name');
                     
                     const modalImage = document.getElementById('photoModalImage');
                     const modalName = document.getElementById('photoModalName');
                     
                     modalImage.src = photoUrl;
                     modalImage.alt = 'Фото ' + personName;
                     modalName.textContent = personName;
                 });
             }
             
            // Автоматическое обновление формы при изменении фильтров (исключаем search_fio)
            const filterForm = document.querySelector('.filter-section form');
            const filterInputs = filterForm.querySelectorAll('select, input[type="text"]:not(#search_fio)');
            
            // Отключаем авто-сабмит на изменение
            filterInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // авто-сабмит отключен — фильтры применяются только по кнопке
                });
            });
             
            // Для полей поиска убираем авто-сабмит (поиск только по кнопке)
            const searchInputs = ['search_position', 'search_phone', 'search_status'];
             searchInputs.forEach(inputId => {
                 const input = document.getElementById(inputId);
                 if (input) {
                     input.addEventListener('input', function() {
                        // авто-сабмит отключен — фильтры применяются только по кнопке
                     });
                 }
             });

            // Применение фильтров только по кнопке
            const applyBtn = document.getElementById('applyFiltersBtn');
            if (applyBtn && filterForm) {
                applyBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    filterForm.submit();
                });
            }
         });

         // Функция для обновления ячейки сертификата
         function updateCertificateCell(peopleId, certificateId, data) {
             // Находим строку человека
             const personRow = document.querySelector(`tr[data-person-id="${peopleId}"]`);
             if (!personRow) return;
             
             // Находим ячейку сертификата
             const certificateCell = personRow.querySelector(`td[data-certificate-id="${certificateId}"]`);
             if (!certificateCell) return;
             
             // Если дата не указана или равна 01.01.2000, показываем "Отсутствует"
             if (!data.assigned_date || data.assigned_date === '2000-01-01') {
                 certificateCell.className = 'certificate-cell status-1';
                 certificateCell.innerHTML = `
                     <span class="badge bg-secondary">Отсутствует</span>
                     <div class="d-flex gap-1 mt-1">
                         <button class="btn btn-sm btn-outline-primary btn-edit" 
                                 onclick="editCertificate(${peopleId}, ${certificateId}, '', '${data.certificate_number || ''}', '${data.notes || ''}')">
                             <i class="fas fa-edit"></i>
                         </button>
                     </div>
                 `;
                 return;
             }
             
             // Обновляем содержимое ячейки
             const assignedDate = new Date(data.assigned_date);
             const expiryDate = new Date(assignedDate);
             expiryDate.setFullYear(expiryDate.getFullYear() + (data.expiry_date || 1));
             
             const isExpired = expiryDate < new Date();
             const isExpiringSoon = !isExpired && (expiryDate - new Date()) <= (60 * 24 * 60 * 60 * 1000);
             
             let statusClass, statusText, statusNumber;
             if (isExpired) {
                 statusClass = 'danger';
                 statusText = 'Просрочен';
                 statusNumber = '2';
             } else if (isExpiringSoon) {
                 statusClass = 'warning';
                 statusText = 'Скоро просрочится';
                 statusNumber = '3';
             } else {
                 statusClass = 'success';
                 statusText = 'Действует';
                 statusNumber = '4';
             }
             
             certificateCell.className = `certificate-cell status-${statusNumber}`;
             certificateCell.innerHTML = `
                 <div class="mb-1">
                     <span class="badge bg-${statusClass}">${statusText}</span>
                 </div>
                 <div class="small">
                     <div>Выдан: ${assignedDate.toLocaleDateString('ru-RU')}</div>
                     <div>Номер: ${data.certificate_number || 'Н/Д'}</div>
                     <div class="${isExpired ? 'text-danger' : 'text-success'}">
                         <i class="fas fa-calendar-alt"></i> 
                         Действует до: ${expiryDate.toLocaleDateString('ru-RU')}
                         ${isExpired ? '<i class="fas fa-exclamation-triangle text-danger"></i>' : ''}
                     </div>
                 </div>
                 ${data.notes ? `<div class="small text-muted" title="${data.notes}"><i class="fas fa-sticky-note"></i> ${data.notes.length > 20 ? data.notes.substring(0, 20) + '...' : data.notes}</div>` : ''}
                 ${data.certificate_file ? `
                     <div class="small">
                         <a href="/safety/certificate-view/${peopleId}/${certificateId}" class="btn btn-sm btn-outline-info me-1" title="Просмотреть файл сертификата" target="_blank">
                             <i class="fas fa-eye"></i> Просмотр
                         </a>
                         <a href="/safety/certificate-file/${peopleId}/${certificateId}" class="text-decoration-none text-primary" title="Скачать файл сертификата">
                             <i class="fas fa-download"></i> 
                         </a>
                     </div>
                 ` : ''}
                 <div class="d-flex gap-1 mt-1">
                     <button class="btn btn-sm btn-outline-primary btn-edit" 
                             onclick="editCertificate(${peopleId}, ${certificateId}, '${data.assigned_date}', '${data.certificate_number || ''}', '${data.notes || ''}')">
                     <i class="fas fa-edit"></i>
                 </button>
                     <button class="btn btn-sm btn-outline-danger" 
                             onclick="deletePersonCertificate(${peopleId}, ${certificateId}, '', '')"
                             title="Удалить сертификат">
                         <i class="fas fa-trash"></i>
                     </button>
                 </div>
             `;
         }

         // Функция для обновления строки человека
         function updatePersonRow(personId, data) {
             // Находим строку человека
             const personRow = document.querySelector(`tr[data-person-id="${personId}"]`);
             if (!personRow) return;
             
             // Находим ячейки по содержимому заголовков
             const getCellByHeader = (headerText) => {
                 const headers = document.querySelectorAll('thead th');
                 let columnIndex = -1;
                 headers.forEach((th, index) => {
                     if (th.textContent.trim() === headerText) {
                         columnIndex = index;
                     }
                 });
                 return columnIndex >= 0 ? personRow.cells[columnIndex] : null;
             };
             
             // Обновляем ячейку с ФИО
             const nameCell = getCellByHeader('ФИО');
             if (nameCell) {
                 const birthDate = data.birth_date ? new Date(data.birth_date).toLocaleDateString('ru-RU') : '-';
                 nameCell.innerHTML = `
                     <div class="d-flex align-items-center">
                         ${data.photo ? `
                             <img src="/safety/photo/${data.photo.split('/').pop()}" alt="Фото ${data.full_name}" class="rounded-circle me-2" style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#photoModal" data-photo-url="/safety/photo/${data.photo.split('/').pop()}" data-person-name="${data.full_name}">
                         ` : `
                             <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                 <i class="fas fa-user text-white" style="font-size: 24px;"></i>
                             </div>
                         `}
                         <div>
                             <strong>${data.full_name}</strong>
                             <div class="small text-muted">
                                 ${data.photo ? '<i class="fas fa-image"></i> Фото загружено<br>' : ''}
                                 ${data.passport_page_1 || data.passport_page_5 ? '<i class="fas fa-id-card"></i> Паспорт: ' : ''}
                                 ${data.passport_page_1 ? `<a href="/safety/passport/${data.passport_page_1.split('/').pop()}" class="text-decoration-none" title="Скачать 1 страницу паспорта"><i class="fas fa-download"></i> 1 стр</a>` : ''}
                                 ${data.passport_page_1 && data.passport_page_5 ? ' | ' : ''}
                                 ${data.passport_page_5 ? `<a href="/safety/passport/${data.passport_page_5.split('/').pop()}" class="text-decoration-none" title="Скачать 5 страницу паспорта"><i class="fas fa-download"></i> 5 стр</a>` : ''}
                                 ${data.certificates_file ? `<br><i class="fas fa-certificate"></i> <a href="/safety/certificates-file/${data.certificates_file.split('/').pop()}" class="text-decoration-none" title="Скачать файл со всеми удостоверениями" onclick="console.log('Download URL:', '/safety/certificates-file/${data.certificates_file.split('/').pop()}')"><i class="fas fa-file-pdf text-danger"></i> Все удостоверения</a>` : ''}
                             </div>
                             <div class="mt-1">
                                 <button class="btn btn-sm btn-outline-warning me-1" onclick="showEditPersonModal(${data.id}, '${data.full_name}', '${data.position || ''}', '${data.phone || ''}', '${data.snils || ''}', '${data.inn || ''}', '${data.birth_date || ''}', '${data.address || ''}', '${data.status || ''}')">
                                     <i class="fas fa-edit"></i> Редактировать
                                 </button>
                                 <button class="btn btn-sm btn-outline-danger" onclick="deletePerson(${data.id}, '${data.full_name}')">
                                     <i class="fas fa-trash"></i> Удалить
                                 </button>
                             </div>
                         </div>
                     </div>
                 `;
             }
             
             // Обновляем остальные ячейки по заголовкам
             const positionCell = getCellByHeader('Должность');
             if (positionCell) positionCell.textContent = data.position || '';
             
             const phoneCell = getCellByHeader('Телефон');
             if (phoneCell) phoneCell.textContent = data.phone || '';
             
             const snilsCell = getCellByHeader('СНИЛС');
             if (snilsCell) snilsCell.textContent = data.snils || '';
             
             const innCell = getCellByHeader('ИНН');
             if (innCell) innCell.textContent = data.inn || '';
             
             const birthDateCell = getCellByHeader('Дата рождения');
             if (birthDateCell) birthDateCell.textContent = data.birth_date ? new Date(data.birth_date).toLocaleDateString('ru-RU') : '-';
             
             const addressCell = getCellByHeader('Примечание');
             if (addressCell) {
                 addressCell.title = data.address ? data.address : 'Нет примечания';
                 addressCell.innerHTML = data.address ? (data.address.length > 30 ? data.address.substring(0, 30) + '...' : data.address) : '-';
             }
             
             const statusCell = getCellByHeader('Статус');
             if (statusCell) statusCell.innerHTML = data.status ? `<span class="badge bg-primary">${data.status}</span>` : '<span class="text-muted">-</span>';
         }


     </script>
 </body>
</html>

<style>
    
</style>