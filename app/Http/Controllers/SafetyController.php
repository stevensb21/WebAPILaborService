<?php

namespace App\Http\Controllers;

use App\Models\People;
use App\Models\Certificate;
use App\Models\PeopleCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class SafetyController extends Controller
{
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
            
            // Начинаем с базового запроса
            $query = People::with(['certificates' => function($q) {
                $q->withPivot('assigned_date', 'certificate_number', 'status', 'notes', 'certificate_file', 'certificate_file_original_name', 'certificate_file_mime_type', 'certificate_file_size');
            }]);
            
            // Фильтр по ФИО
            if ($request->filled('search_fio')) {
                $query->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($request->search_fio) . '%']);
            }
            
            // Фильтр по должности
            if ($request->filled('search_position')) {
                $query->whereRaw('LOWER(position) LIKE ?', ['%' . strtolower($request->search_position) . '%']);
            }
            
            // Фильтр по телефону
            if ($request->filled('search_phone')) {
                $query->whereRaw('LOWER(phone) LIKE ?', ['%' . strtolower($request->search_phone) . '%']);
            }
            
            // Фильтр по статусу работника
            if ($request->filled('search_status')) {
                $query->whereRaw('LOWER(status) LIKE ?', ['%' . strtolower($request->search_status) . '%']);
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
            
            $people = $query->paginate(20);
            $certificates = Certificate::all();
            $positions = People::distinct()->pluck('position')->filter();
            
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
                'assigned_date' => 'required|date',
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
            
            $data = [
                'status' => $status,
                'assigned_date' => $request->assigned_date,
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

            return response()->json(['success' => true, 'message' => 'Сертификат успешно обновлен']);
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
                $passport1Name = time() . '_passport1_' . $passport1Name;
                
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
                $passport5 = $request->file('passport_page_5');
                $passport5Name = time() . '_passport5_' . $passport5Name;
                
                // Сохраняем файл напрямую без использования storeAs
                $passport5Path = 'passports/' . $passport5Name;
                $fullPath = storage_path('app/public/' . $passport5Path);
                $passport5->move(dirname($fullPath), basename($fullPath));
                
                $data['passport_page_5'] = $passport5Path;
                $data['passport_page_5_original_name'] = $passport5Name;
                $data['passport_page_5_mime_type'] = 'application/pdf';
                $data['passport_page_5_size'] = filesize($fullPath);
            }

            $person = People::create($data);

            return response()->json(['success' => true, 'person' => $person]);
        } catch (\Exception $e) {
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
                'expiry_date' => 'nullable|date',
            ]);

            $data = $request->only(['name', 'description', 'expiry_date']);
            
            // Если expiry_date передается как timestamp, конвертируем в дату
            if (isset($data['expiry_date']) && is_numeric($data['expiry_date'])) {
                $data['expiry_date'] = date('Y-m-d', $data['expiry_date'] / 1000);
            }

            $certificate = Certificate::create($data);

            return response()->json(['success' => true, 'certificate' => $certificate]);
        } catch (\Exception $e) {
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
                'photo' => 'nullable|file|max:2048',
                'passport_page_1' => 'nullable|file|max:5120',
                'passport_page_5' => 'nullable|file|max:5120',
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

            return response()->json(['success' => true, 'person' => $person]);
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
            $person = People::findOrFail($id);
            
            // Удаляем файлы
            if ($person->photo && file_exists(storage_path('app/public/' . $person->photo))) {
                unlink(storage_path('app/public/' . $person->photo));
            }
            
            if ($person->passport_page_1 && file_exists(storage_path('app/public/' . $person->passport_page_1))) {
                unlink(storage_path('app/public/' . $person->passport_page_1));
            }
            
            if ($person->passport_page_5 && file_exists(storage_path('app/public/' . $person->passport_page_5))) {
                unlink(storage_path('app/public/' . $person->passport_page_5));
            }
            
            // Удаляем человека (связанные записи в people_certificates удалятся автоматически благодаря CASCADE)
            $person->delete();
            
            return response()->json(['success' => true, 'message' => 'Человек успешно удален']);
        } catch (\Exception $e) {
            \Log::error('DeletePerson exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Ошибка при удалении человека: ' . $e->getMessage()
            ], 422);
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
}
