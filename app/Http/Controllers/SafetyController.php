<?php

namespace App\Http\Controllers;

use App\Models\People;
use App\Models\Certificate;
use App\Models\PeopleCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class SafetyController extends Controller
{
    /**
     * Очистить кэш людей - принудительная очистка всех кэшей
     */
    private function clearPeopleCache()
    {
        try {
            // Пытаемся очистить кэш с паттерном
            if (method_exists(\Cache::getStore(), 'getRedis')) {
                $cacheKeys = \Cache::getRedis()->keys('*people_list_*');
                foreach ($cacheKeys as $key) {
                    \Cache::forget(str_replace('laravel_cache:', '', $key));
                }
                \Log::info('Cache keys cleared with pattern', ['keys' => $cacheKeys]);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to clear cache with pattern', ['error' => $e->getMessage()]);
        }
        
        // Очищаем все основные ключи кэша
        \Cache::forget('people_list');
        \Cache::forget('certificates_list');
        \Cache::forget('positions_list');
        
        // Принудительно очищаем весь кэш
        \Cache::flush();
        
        // Дополнительно очищаем кэш конфигурации и маршрутов
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');
        
        \Log::info('All caches cleared after data modification');
    }
    public function __construct()
    {
        // Увеличиваем лимиты загрузки файлов
        ini_set('upload_max_filesize', '200M');
        ini_set('post_max_size', '200M');
        ini_set('max_execution_time', 600);
        ini_set('max_input_time', 600);
        ini_set('memory_limit', '1024M');
    }

        public function index(Request $request)
    {
        try {
            \Log::info('SafetyController::index called', [
                'request_params' => $request->all()
            ]);
            
            // Начинаем с базового запроса без загрузки сертификатов для оптимизации
            $query = People::select('id', 'full_name', 'position', 'phone', 'status', 'address', 'created_at', 'updated_at');
            
            // Фильтр по ФИО (без учета регистра и лишних пробелов)
            if ($request->filled('search_fio')) {
                $searchTerm = trim(preg_replace('/\s+/', ' ', $request->search_fio));
                $query->where('full_name', 'ILIKE', '%' . $searchTerm . '%');
            }
            
            // Фильтр по должности (без учета регистра и лишних пробелов)
            if ($request->filled('search_position')) {
                $searchTerm = trim(preg_replace('/\s+/', ' ', $request->search_position));
                $query->where('position', 'ILIKE', '%' . $searchTerm . '%');
            }
            
            // Фильтр по телефону (без учета регистра и лишних пробелов)
            if ($request->filled('search_phone')) {
                $searchTerm = trim(preg_replace('/\s+/', ' ', $request->search_phone));
                $query->where('phone', 'ILIKE', '%' . $searchTerm . '%');
            }
            
            // Фильтр по статусу работника (без учета регистра и лишних пробелов)
            if ($request->filled('search_status')) {
                $searchTerm = trim(preg_replace('/\s+/', ' ', $request->search_status));
                $query->where('status', 'ILIKE', '%' . $searchTerm . '%');
            }
            
            // Фильтр по статусу сертификата (без указания конкретного сертификата)
            if ($request->filled('certificate_status') && !$request->filled('certificate_id')) {
                $status = $request->certificate_status;
                if ($status == '1') {
                    // Статус "Отсутствует" - люди без сертификатов
                    $query->whereDoesntHave('certificates');
                } else {
                    // Другие статусы - люди с сертификатами определенного статуса
                    $query->whereHas('certificates', function($q) use ($status) {
                        $q->where('people_certificates.status', $status);
                    });
                }
            }
            
            // Комбинированный фильтр: сертификат + статус
            if ($request->filled('certificate_id') && $request->filled('certificate_status')) {
                $certificateId = $request->certificate_id;
                $status = $request->certificate_status;
                if ($status == '1') {
                    // Статус "Отсутствует" для конкретного сертификата
                    $query->whereDoesntHave('certificates', function($q) use ($certificateId) {
                        $q->where('certificates.id', $certificateId);
                    });
                } else {
                    // Другие статусы для конкретного сертификата
                    $query->whereHas('certificates', function($q) use ($certificateId, $status) {
                        $q->where('certificates.id', $certificateId)
                          ->where('people_certificates.status', $status);
                    });
                }
            }
            
            // Создаем ключ кэша на основе параметров запроса
            $cacheKey = 'people_list_' . md5(serialize($request->all()));
            
            // Кэшируем весь результат на 5 минут
            $people = \Cache::remember($cacheKey, 300, function() use ($query) {
            $people = $query->get();
                
                // Загружаем сертификаты для людей отдельно (только если есть люди)
                if ($people->count() > 0) {
                    $people->load(['certificates' => function($q) {
                        $q->select('certificates.id', 'certificates.name', 'certificates.expiry_date')
                          ->withPivot('assigned_date', 'certificate_number', 'status', 'notes', 'certificate_file', 'certificate_file_original_name', 'certificate_file_mime_type', 'certificate_file_size');
                    }]);
                }
                
                return $people;
            });
            
            // Кэшируем сертификаты на 1 час (они редко изменяются)
            $certificates = \Cache::remember('certificates_list', 3600, function() {
                return Certificate::select('certificates.id', 'certificates.name', 'certificates.description', 'certificates.expiry_date')
                    ->leftJoin('certificate_orders', 'certificates.id', '=', 'certificate_orders.certificate_id')
                    ->orderBy('certificate_orders.sort_order', 'asc')
                    ->orderBy('certificates.id', 'asc')
                    ->get();
            });
            
            // Кэшируем должности на 30 минут
            $positions = \Cache::remember('positions_list', 1800, function() {
                return People::whereNotNull('position')
                    ->distinct()
                    ->pluck('position');
            });
            
            \Log::info('Data loaded successfully', [
                'people_count' => $people->count(),
                'certificates_count' => $certificates->count(),
                'positions_count' => $positions->count(),
                'sql_query' => $query->toSql(),
                'sql_bindings' => $query->getBindings()
            ]);

            return view('safety.index', compact('people', 'certificates', 'positions'));
        } catch (\Exception $e) {
            \Log::error('SafetyController::index exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при загрузке страницы: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCertificate(Request $request, $peopleId, $certificateId)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'assigned_date' => 'nullable|date',
                'certificate_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
                'certificate_file' => 'nullable|file|max:204800', // 200MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $peopleCertificate = PeopleCertificate::where('people_id', $peopleId)
                ->where('certificate_id', $certificateId)
                ->first();

            // Автоматически определяем статус на основе срока действия
            $certificate = Certificate::find($certificateId);
            
            // Если дата не указана, устанавливаем статус "Отсутствует"
            if (empty($request->assigned_date)) {
                $status = 1; // Отсутствует
            } else {
            $assignedDate = \Carbon\Carbon::parse($request->assigned_date);
            $expiryDate = $assignedDate->copy()->addYears($certificate->expiry_date ?: 1);
            $isExpired = $expiryDate->isPast();
            $isExpiringSoon = now()->diffInDays($expiryDate, false) <= 60 && now()->diffInDays($expiryDate, false) > 0; // 2 месяца = 60 дней, но только если дата в будущем
            
            if ($isExpired) {
                $status = 2; // Просрочен
            } elseif ($isExpiringSoon) {
                $status = 3; // Скоро просрочится
            } else {
                $status = 4; // Действует
                }
            }
            
            $data = [
                'status' => $status,
                'assigned_date' => $request->assigned_date ?: '2000-01-01',
                'certificate_number' => $request->certificate_number ?: 'Н/Д', // Если пустое, ставим "Н/Д"
                'notes' => $request->notes,
            ];

            // Обработка загрузки файла сертификата
            if ($request->hasFile('certificate_file')) {
                $file = $request->file('certificate_file');
                
                // Удаляем старый файл, если он существует
                if ($peopleCertificate && $peopleCertificate->certificate_file) {
                    $oldFilePath = storage_path('app/public/certificates/' . $peopleCertificate->certificate_file);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                // Создаем директорию, если её нет
                $uploadPath = storage_path('app/public/certificates');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Генерируем уникальное имя файла
                $filename = time() . '_' . $peopleId . '_' . $certificateId . '.' . $file->getClientOriginalExtension();
                
                // Перемещаем файл
                $file->move($uploadPath, $filename);
                
                // Определяем MIME тип
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $extension === 'pdf' ? 'application/pdf' : 'application/octet-stream';
                
                $data['certificate_file'] = $filename;
                $data['certificate_file_original_name'] = $file->getClientOriginalName();
                $data['certificate_file_mime_type'] = $mimeType;
                $filePath = $uploadPath . '/' . $filename;
                $data['certificate_file_size'] = file_exists($filePath) ? filesize($filePath) : 0;
            }

            if (!$peopleCertificate) {
                // Создаем новую запись если её нет
                $data['people_id'] = $peopleId;
                $data['certificate_id'] = $certificateId;
                // Статус уже рассчитан выше и находится в $data['status']
                $peopleCertificate = PeopleCertificate::create($data);
            } else {
                // Обновляем существующую запись
                $peopleCertificate->update($data);
            }

            // Загружаем обновленные данные
            $peopleCertificate = $peopleCertificate->fresh(['certificate']);
            
            // Очищаем кэш при обновлении сертификата
            $this->clearPeopleCache();
            
            return response()->json([
                'success' => true, 
                'message' => 'Сертификат успешно обновлен',
                'data' => [
                    'people_id' => $peopleId,
                    'certificate_id' => $certificateId,
                    'assigned_date' => $peopleCertificate->assigned_date,
                    'certificate_number' => $peopleCertificate->certificate_number,
                    'notes' => $peopleCertificate->notes,
                    'status' => $peopleCertificate->status,
                    'certificate_file' => $peopleCertificate->certificate_file,
                    'expiry_date' => $peopleCertificate->certificate->expiry_date
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('UpdateCertificate exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при обновлении сертификата: ' . $e->getMessage()
            ], 422);
        }
    }

    public function storePerson(Request $request)
    {
        try {
            // Логируем входящие данные для отладки
            \Log::info('SafetyController::storePerson request data:', $request->all());
            \Log::info('SafetyController::storePerson files:', $request->allFiles());
            $request->validate([
                'full_name' => 'required|string|max:255',
                'position' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'snils' => 'nullable|string|max:20',
                'inn' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|string|max:255',
                'photo' => 'nullable|file|max:2048', // Максимум 2MB
                'passport_page_1' => 'nullable|file|max:5120', // Максимум 5MB
                'passport_page_5' => 'nullable|file|max:5120', // Максимум 5MB
            ]);

            $data = $request->only([
                'full_name', 'position', 'phone', 'snils', 'inn', 'birth_date', 'address', 'status'
            ]);

            // Обработка загрузки фото
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                
                // Сохраняем файл напрямую без использования storeAs
                $photoPath = 'photos/' . $photoName;
                $fullPath = storage_path('app/public/' . $photoPath);
                $photo->move(dirname($fullPath), basename($fullPath));
                
                $data['photo'] = $photoPath;
                $data['photo_original_name'] = $photo->getClientOriginalName();
                $data['photo_mime_type'] = $photo->getClientMimeType();
                $data['photo_size'] = filesize($fullPath);
            }

            // Обработка загрузки паспорта 1 страница
            if ($request->hasFile('passport_page_1')) {
                $passport1 = $request->file('passport_page_1');
                $passport1Name = time() . '_passport1_' . $passport1->getClientOriginalName();
                
                // Сохраняем файл напрямую без использования storeAs
                $passport1Path = 'passports/' . $passport1Name;
                $fullPath = storage_path('app/public/' . $passport1Path);
                $passport1->move(dirname($fullPath), basename($fullPath));
                
                $data['passport_page_1'] = $passport1Path;
                $data['passport_page_1_original_name'] = $passport1->getClientOriginalName();
                $data['passport_page_1_mime_type'] = 'application/pdf';
                $data['passport_page_1_size'] = filesize($fullPath);
            }

            // Обработка загрузки паспорта 5 страница
            if ($request->hasFile('passport_page_5')) {
                $passport5 = $request->file('passport_page_5');
                $passport5Name = time() . '_passport5_' . $passport5->getClientOriginalName();
                
                // Сохраняем файл напрямую без использования storeAs
                $passport5Path = 'passports/' . $passport5Name;
                $fullPath = storage_path('app/public/' . $passport5Path);
                $passport5->move(dirname($fullPath), basename($fullPath));
                
                $data['passport_page_5'] = $passport5Path;
                $data['passport_page_5_original_name'] = $passport5->getClientOriginalName();
                $data['passport_page_5_mime_type'] = 'application/pdf';
                $data['passport_page_5_size'] = filesize($fullPath);
            }

            $person = People::create($data);
            
            // Очищаем кэш при добавлении человека
            $this->clearPeopleCache();

            return response()->json(['success' => true, 'person' => $person]);
        } catch (\Exception $e) {
            \Log::error('SafetyController::storePerson error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при создании человека: ' . $e->getMessage()
            ], 422);
        }
    }

    public function storeCertificate(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'expiry_date' => 'nullable|integer|min:1|max:10',
            ]);

            $data = $request->only(['name', 'description']);
            
            // Обработка expiry_date
            if ($request->has('expiry_date') && $request->expiry_date !== null && $request->expiry_date !== '') {
                $data['expiry_date'] = (int) $request->expiry_date;
            }

            $certificate = Certificate::create($data);

            // Очищаем кэш при создании сертификата
            $this->clearPeopleCache();

            return response()->json(['success' => true, 'certificate' => $certificate]);
        } catch (\Exception $e) {
            \Log::error('StoreCertificate exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при создании сертификата: ' . $e->getMessage()
            ], 422);
        }
    }

    public function showPhoto($filename)
    {
        $path = storage_path('app/public/photos/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        $file = file_get_contents($path);
        
        // Определяем MIME-тип по расширению файла
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $type = match(strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream'
        };
        
        return response($file, 200)
            ->header('Content-Type', $type)
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function downloadPassport($filename)
    {
        $path = storage_path('app/public/passports/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        $file = file_get_contents($path);
        
        // Определяем MIME-тип по расширению файла
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $type = match(strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream'
        };
        
        return response($file, 200)
            ->header('Content-Type', $type)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadCertificateFile($peopleId, $certificateId)
    {
        $peopleCertificate = PeopleCertificate::where('people_id', $peopleId)
            ->where('certificate_id', $certificateId)
            ->first();

        if (!$peopleCertificate || !$peopleCertificate->certificate_file) {
            abort(404);
        }

        $path = storage_path('app/public/certificates/' . $peopleCertificate->certificate_file);
        if (!file_exists($path)) {
            abort(404);
        }

        $file = file_get_contents($path);
        return response($file, 200)
            ->header('Content-Type', $peopleCertificate->certificate_file_mime_type ?: 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $peopleCertificate->certificate_file_original_name . '"');
    }

    public function viewCertificateFile($peopleId, $certificateId)
    {
        $peopleCertificate = PeopleCertificate::where('people_id', $peopleId)
            ->where('certificate_id', $certificateId)
            ->first();

        if (!$peopleCertificate || !$peopleCertificate->certificate_file) {
            abort(404);
        }

        $path = storage_path('app/public/certificates/' . $peopleCertificate->certificate_file);
        if (!file_exists($path)) {
            abort(404);
        }

        $file = file_get_contents($path);
        return response($file, 200)
            ->header('Content-Type', $peopleCertificate->certificate_file_mime_type ?: 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $peopleCertificate->certificate_file_original_name . '"');
    }

    public function downloadCertificatesFile($filename)
    {
        $path = storage_path('app/public/certificates/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        $file = file_get_contents($path);
        
        return response($file, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function updatePerson(Request $request, $id)
    {
        try {
            // Логируем входящие данные для отладки
            \Log::info('UpdatePerson request data:', $request->all());
            \Log::info('UpdatePerson files:', $request->allFiles());
            \Log::info('UpdatePerson person_id:', ['id' => $id]);
            
            $validator = \Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'position' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'snils' => 'nullable|string|max:20',
                'inn' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|string|max:255',
                'photo' => 'nullable|file|max:20480', // Увеличиваем до 20MB
                'passport_page_1' => 'nullable|file|max:51200', // Увеличиваем до 50MB
                'passport_page_5' => 'nullable|file|max:51200', // Увеличиваем до 50MB
                'certificates_file' => 'nullable|file|max:204800', // Максимум 200MB
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $person = People::findOrFail($id);
            $data = $request->only([
                'full_name', 'position', 'phone', 'snils', 'inn', 'birth_date', 'address', 'status'
            ]);

            // Обработка загрузки фото
            if ($request->hasFile('photo')) {
                \Log::info('Processing photo upload');
                
                // Удаляем старое фото
                if ($person->photo && file_exists(storage_path('app/public/' . $person->photo))) {
                    unlink(storage_path('app/public/' . $person->photo));
                    \Log::info('Deleted old photo: ' . $person->photo);
                }
                
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                
                // Получаем метаданные до перемещения файла
                $originalName = $photo->getClientOriginalName();
                $mimeType = $photo->getClientMimeType();
                $fileSize = $photo->getSize();
                
                // Создаем директорию, если её нет
                $uploadPath = storage_path('app/public/photos');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Сохраняем файл напрямую без использования storeAs
                $photoPath = 'photos/' . $photoName;
                $fullPath = storage_path('app/public/' . $photoPath);
                $photo->move(dirname($fullPath), basename($fullPath));
                
                \Log::info('Photo saved to: ' . $photoPath);
                \Log::info('Photo full path: ' . $fullPath);
                
                $data['photo'] = $photoPath;
                $data['photo_original_name'] = $originalName;
                $data['photo_mime_type'] = 'image/jpeg'; // Простое значение по умолчанию
                $data['photo_size'] = filesize($fullPath);
            }

            // Обработка загрузки паспорта 1 страница
            if ($request->hasFile('passport_page_1')) {
                // Удаляем старый файл
                if ($person->passport_page_1 && file_exists(storage_path('app/public/' . $person->passport_page_1))) {
                    unlink(storage_path('app/public/' . $person->passport_page_1));
                }
                
                $passport1 = $request->file('passport_page_1');
                $passport1Name = time() . '_passport1_' . $passport1->getClientOriginalName();
                
                // Создаем директорию, если её нет
                $uploadPath = storage_path('app/public/passports');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Сохраняем файл напрямую без использования storeAs
                $passport1Path = 'passports/' . $passport1Name;
                $fullPath = storage_path('app/public/' . $passport1Path);
                $passport1->move(dirname($fullPath), basename($fullPath));
                
                $data['passport_page_1'] = $passport1Path;
                $data['passport_page_1_original_name'] = $passport1Name;
                $data['passport_page_1_mime_type'] = 'application/pdf';
                $data['passport_page_1_size'] = filesize($fullPath);
            }

            // Обработка загрузки паспорта 5 страница
            if ($request->hasFile('passport_page_5')) {
                // Удаляем старый файл
                if ($person->passport_page_5 && file_exists(storage_path('app/public/' . $person->passport_page_5))) {
                    unlink(storage_path('app/public/' . $person->passport_page_5));
                }
                
                $passport5 = $request->file('passport_page_5');
                $passport5Name = time() . '_passport5_' . $passport5->getClientOriginalName();
                
                // Создаем директорию, если её нет
                $uploadPath = storage_path('app/public/passports');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Сохраняем файл напрямую без использования storeAs
                $passport5Path = 'passports/' . $passport5Name;
                $fullPath = storage_path('app/public/' . $passport5Path);
                $passport5->move(dirname($fullPath), basename($fullPath));
                
                $data['passport_page_5'] = $passport5Path;
                $data['passport_page_5_original_name'] = $passport5Name;
                $data['passport_page_5_mime_type'] = 'application/pdf';
                $data['passport_page_5_size'] = filesize($fullPath);
            }

            // Обработка загрузки файла со всеми удостоверениями
            if ($request->hasFile('certificates_file')) {
                \Log::info('Processing certificates file upload');
                
                $certificatesFile = $request->file('certificates_file');
                
                // Проверяем расширение файла
                $extension = strtolower($certificatesFile->getClientOriginalExtension());
                if ($extension !== 'pdf') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Файл должен быть в формате PDF'
                    ], 422);
                }
                
                // Удаляем старый файл, если он существует
                if ($person->certificates_file && file_exists(storage_path('app/public/' . $person->certificates_file))) {
                    unlink(storage_path('app/public/' . $person->certificates_file));
                }
                
                // Создаем директорию, если она не существует
                $uploadPath = storage_path('app/public/certificates');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Генерируем уникальное имя файла
                $filename = time() . '_certificates_' . $person->id . '_' . $certificatesFile->getClientOriginalName();
                $filePath = 'certificates/' . $filename;
                $fullPath = storage_path('app/public/' . $filePath);
                
                // Перемещаем файл
                $certificatesFile->move(dirname($fullPath), basename($fullPath));
                
                // Обновляем данные
                $data['certificates_file'] = $filePath;
                $data['certificates_file_original_name'] = $certificatesFile->getClientOriginalName();
                $data['certificates_file_mime_type'] = 'application/pdf';
                $data['certificates_file_size'] = filesize($fullPath);
                
                \Log::info("Certificates file saved: $filePath");
            }

            \Log::info('Updating person with data:', $data);
            $person->update($data);

            // Очищаем кэш при обновлении человека
            $this->clearPeopleCache();

            // Загружаем обновленные данные
            $person = $person->fresh();
            
            return response()->json([
                'success' => true, 
                'message' => 'Человек успешно обновлен',
                'data' => [
                    'id' => $person->id,
                    'full_name' => $person->full_name,
                    'position' => $person->position,
                    'phone' => $person->phone,
                    'snils' => $person->snils,
                    'inn' => $person->inn,
                    'birth_date' => $person->birth_date,
                    'address' => $person->address,
                    'status' => $person->status,
                    'photo' => $person->photo,
                    'passport_page_1' => $person->passport_page_1,
                    'passport_page_5' => $person->passport_page_5,
                    'certificates_file' => $person->certificates_file
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('UpdatePerson exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при обновлении человека: ' . $e->getMessage()
            ], 422);
        }
    }

    public function deletePerson($id)
    {
        try {
            \Log::info('Attempting to delete person', ['id' => $id]);
            
            $person = People::find($id);
            if (!$person) {
                \Log::warning('Person not found', ['id' => $id]);
                return response()->json([
                    'success' => false, 
                    'message' => 'Человек с ID ' . $id . ' не найден'
                ], 404);
            }
            
            \Log::info('Person found', ['person' => $person->toArray()]);
            
            // Удаляем файлы
            if ($person->photo && file_exists(storage_path('app/public/' . $person->photo))) {
                unlink(storage_path('app/public/' . $person->photo));
                \Log::info('Photo file deleted', ['photo' => $person->photo]);
            }
            
            if ($person->passport_page_1 && file_exists(storage_path('app/public/' . $person->passport_page_1))) {
                unlink(storage_path('app/public/' . $person->passport_page_1));
                \Log::info('Passport page 1 deleted', ['passport_page_1' => $person->passport_page_1]);
            }
            
            if ($person->passport_page_5 && file_exists(storage_path('app/public/' . $person->passport_page_5))) {
                unlink(storage_path('app/public/' . $person->passport_page_5));
                \Log::info('Passport page 5 deleted', ['passport_page_5' => $person->passport_page_5]);
            }
            
            // Удаляем человека (связанные записи в people_certificates удалятся автоматически благодаря CASCADE)
            $person->delete();
            \Log::info('Person deleted successfully', ['id' => $id]);
            
            // Очищаем кэш
            $this->clearPeopleCache();
            \Log::info('Cache cleared after person deletion', ['id' => $id]);
            
            return response()->json(['success' => true, 'message' => 'Человек успешно удален']);
        } catch (\Exception $e) {
            \Log::error('DeletePerson exception:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при удалении человека: ' . $e->getMessage()
            ], 500); // Изменили статус с 422 на 500 для ошибок сервера
        }
    }

    public function deleteCertificate($id)
    {
        try {
            $certificate = Certificate::findOrFail($id);
            
            // Удаляем все связи с людьми (записи в people_certificates)
            $certificate->people()->detach();
            
            // Удаляем сам сертификат
            $certificate->delete();
            
            // Очищаем кэш
            $this->clearPeopleCache();
            
            return response()->json(['success' => true, 'message' => 'Сертификат успешно удален']);
        } catch (\Exception $e) {
            \Log::error('DeleteCertificate exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при удалении сертификата: ' . $e->getMessage()
            ], 422);
        }
    }

    public function getCertificateDescription($id)
    {
        try {
            $certificate = Certificate::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'certificate' => [
                    'name' => $certificate->name,
                    'description' => $certificate->description
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting certificate description: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при получении описания сертификата'
            ], 500);
        }
    }

    public function updateCertificateInfo(Request $request, $id)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'expiry_date' => 'nullable|integer|min:1|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $certificate = Certificate::findOrFail($id);
            
            $certificate->update([
                'name' => $request->name,
                'description' => $request->description,
                'expiry_date' => $request->expiry_date,
            ]);

            // Очищаем кэш при обновлении информации о сертификате
            $this->clearPeopleCache();

            return response()->json(['success' => true, 'message' => 'Сертификат успешно обновлен']);
        } catch (\Exception $e) {
            \Log::error('UpdateCertificateInfo exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при обновлении сертификата: ' . $e->getMessage()
            ], 422);
        }
    }

    public function deletePersonCertificate($peopleId, $certificateId)
    {
        try {
            // Находим связь между человеком и сертификатом
            $peopleCertificate = \App\Models\PeopleCertificate::where('people_id', $peopleId)
                ->where('certificate_id', $certificateId)
                ->first();

            if (!$peopleCertificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Связь между человеком и сертификатом не найдена'
                ], 404);
            }

            // Удаляем файл сертификата, если он существует
            if ($peopleCertificate->certificate_file && file_exists(storage_path('app/public/' . $peopleCertificate->certificate_file))) {
                unlink(storage_path('app/public/' . $peopleCertificate->certificate_file));
            }

            // Удаляем связь
            $peopleCertificate->delete();
            
            // Очищаем кэш при удалении сертификата
            $this->clearPeopleCache();

            return response()->json([
                'success' => true, 
                'message' => 'Сертификат успешно удален у человека'
            ]);
        } catch (\Exception $e) {
            \Log::error('DeletePersonCertificate exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при удалении сертификата: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Показать модальное окно для изменения порядка сертификатов
     */
    public function showCertificateOrderModal()
    {
        $certificates = Certificate::with('order')->get();
        return view('safety.certificate-order-modal', compact('certificates'));
    }

    /**
     * Обновить порядок сертификатов
     */
    public function updateCertificateOrder(Request $request)
    {
        try {
            $certificateIds = $request->input('certificate_ids', []);
            
            foreach ($certificateIds as $index => $certificateId) {
                \App\Models\CertificateOrder::updateOrCreate(
                    ['certificate_id' => $certificateId],
                    ['sort_order' => $index + 1]
                );
            }
            
            // Очищаем кэш
            $this->clearPeopleCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Порядок сертификатов успешно обновлен'
            ]);
        } catch (\Exception $e) {
            \Log::error('UpdateCertificateOrder exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении порядка: ' . $e->getMessage()
            ], 500);
        }
    }
}
