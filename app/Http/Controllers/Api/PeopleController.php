<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\People;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PeopleController extends Controller
{
    /**
     * Display a compact listing of people for bot usage.
     */
    public function compact(Request $request)
    {
        try {
            Log::info('API People compact called', ['request' => $request->all()]);
            
            $query = People::query();

            // Фильтры
            if ($request->filled('search')) {
                $search = trim(preg_replace('/\s+/', ' ', $request->search));
                Log::info('Search filter applied', ['search' => $search]);
                
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'ILIKE', '%' . $search . '%')
                      ->orWhere('position', 'ILIKE', '%' . $search . '%')
                      ->orWhere('phone', 'ILIKE', '%' . $search . '%')
                      ->orWhere('snils', 'ILIKE', '%' . $search . '%')
                      ->orWhere('inn', 'ILIKE', '%' . $search . '%')
                      ->orWhere('address', 'ILIKE', '%' . $search . '%')
                      ->orWhere('status', 'ILIKE', '%' . $search . '%');
                });
            }

            if ($request->filled('position')) {
                $term = trim(preg_replace('/\s+/', ' ', $request->position));
                $query->where('position', 'ILIKE', '%' . $term . '%');
            }

            if ($request->filled('phone')) {
                $term = trim(preg_replace('/\s+/', ' ', $request->phone));
                $query->where('phone', 'ILIKE', '%' . $term . '%');
            }

            if ($request->filled('status')) {
                $term = trim(preg_replace('/\s+/', ' ', $request->status));
                $query->where('status', 'ILIKE', '%' . $term . '%');
            }

            // Получаем всех людей без пагинации для бота
            $people = $query->with('certificates')->get();
            
            // Загружаем все сертификаты
            $allCertificates = \App\Models\Certificate::all();
            
            // Формируем компактный ответ
            $compactPeople = $people->map(function ($person) use ($allCertificates) {
                $assignedCertificateIds = $person->certificates->pluck('id')->toArray();
                
                // Формируем только необходимые поля сертификатов
                $allCertificatesWithStatus = $allCertificates->map(function ($certificate) use ($assignedCertificateIds, $person) {
                    $isAssigned = in_array($certificate->id, $assignedCertificateIds);
                    $assignedData = null;
                    
                    if ($isAssigned) {
                        $pivot = $person->certificates->where('id', $certificate->id)->first()->pivot;
                        $assignedData = [
                            'assigned_date' => $pivot->assigned_date,
                            'status' => $pivot->status
                        ];
                    }
                    
                    return [
                        'id' => $certificate->id,
                        'name' => $certificate->name,
                        'description' => $certificate->description,
                        'assigned_data' => $assignedData
                    ];
                });
                
                return [
                    'id' => $person->id,
                    'full_name' => $person->full_name,
                    'phone' => $person->phone,
                    'snils' => $person->snils,
                    'inn' => $person->inn,
                    'position' => $person->position,
                    'birth_date' => $person->birth_date,
                    'address' => $person->address,
                    'photo' => $person->photo,
                    'all_certificates' => $allCertificatesWithStatus
                ];
            });
            
            Log::info('API People compact result', [
                'total' => $compactPeople->count(),
                'search_applied' => $request->filled('search')
            ]);

            return response()->json([
                'success' => true,
                'data' => $compactPeople
            ]);

        } catch (\Exception $e) {
            Log::error('API People compact error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении компактного списка людей',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            Log::info('API People index called', ['request' => $request->all()]);
            
            $query = People::query();

            // Фильтры
            if ($request->filled('search')) {
                $search = $request->search;
                Log::info('Search filter applied', ['search' => $search]);
                
                // Логируем SQL запрос для отладки
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'ILIKE', '%' . $search . '%')
                      ->orWhere('position', 'ILIKE', '%' . $search . '%')
                      ->orWhere('phone', 'ILIKE', '%' . $search . '%')
                      ->orWhere('snils', 'ILIKE', '%' . $search . '%')
                      ->orWhere('inn', 'ILIKE', '%' . $search . '%')
                      ->orWhere('address', 'ILIKE', '%' . $search . '%')
                      ->orWhere('status', 'ILIKE', '%' . $search . '%');
                });
                
                // Логируем SQL запрос
                Log::info('SQL Query', [
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ]);
            }

            if ($request->filled('position')) {
                $query->where('position', 'ILIKE', '%' . $request->position . '%');
            }

            if ($request->filled('phone')) {
                $query->where('phone', 'ILIKE', '%' . $request->phone . '%');
            }

            if ($request->filled('status')) {
                $query->where('status', 'ILIKE', '%' . $request->status . '%');
            }

            // Получаем всех людей без пагинации
            $people = $query->with('certificates')->get();
            
            // Загружаем все сертификаты для каждого человека
            $allCertificates = \App\Models\Certificate::all();
            
            // Добавляем все сертификаты к каждому человеку
            $people->transform(function ($person) use ($allCertificates) {
                $assignedCertificateIds = $person->certificates->pluck('id')->toArray();
                
                $allCertificatesWithStatus = $allCertificates->map(function ($certificate) use ($assignedCertificateIds, $person) {
                    $isAssigned = in_array($certificate->id, $assignedCertificateIds);
                    $assignedData = null;
                    
                    if ($isAssigned) {
                        $assignedData = $person->certificates->where('id', $certificate->id)->first()->pivot;
                    }
                    
                    return [
                        'id' => $certificate->id,
                        'name' => $certificate->name,
                        'description' => $certificate->description,
                        'expiry_date' => $certificate->expiry_date,
                        'created_at' => $certificate->created_at,
                        'updated_at' => $certificate->updated_at,
                        'is_assigned' => $isAssigned,
                        'assigned_data' => $assignedData ? [
                            'assigned_date' => $assignedData->assigned_date,
                            'certificate_number' => $assignedData->certificate_number,
                            'status' => $assignedData->status,
                            'notes' => $assignedData->notes
                        ] : null
                    ];
                });
                
                $person->all_certificates = $allCertificatesWithStatus;
                return $person;
            });
            
            Log::info('API People index result', [
                'total' => $people->count(),
                'search_applied' => $request->filled('search')
            ]);

            return response()->json([
                'success' => true,
                'data' => $people
            ]);

        } catch (\Exception $e) {
            Log::error('API People index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка людей',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Получаем данные из разных источников
            $data = $request->all();
            
            // Если данные пустые, пробуем получить из JSON
            if (empty($data)) {
                $jsonContent = $request->getContent();
                if (!empty($jsonContent)) {
                    Log::info('Raw JSON content:', ['content' => $jsonContent]);
                    
                    // Пробуем исправить кодировку перед декодированием
                    $fixedContent = mb_convert_encoding($jsonContent, 'UTF-8', 'CP1251');
                    $data = json_decode($fixedContent, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('JSON decode failed after encoding fix', ['error' => json_last_error_msg()]);
                        $data = [];
                    } else {
                        Log::info('JSON decoded successfully after encoding fix');
                    }
                }
            }
            
            Log::info('API People store request data:', $data);
            
            // Отладка: проверяем наличие URL полей
            Log::info('URL fields check:', [
                'photo_url' => $data['photo_url'] ?? 'NOT_SET',
                'passport_page_1_url' => $data['passport_page_1_url'] ?? 'NOT_SET',
                'passport_page_5_url' => $data['passport_page_5_url'] ?? 'NOT_SET',
                'certificates_file_url' => $data['certificates_file_url'] ?? 'NOT_SET'
            ]);
            
            // Кастомная валидация для полей файлов (может быть файл или строка с @)
            $validator = Validator::make($data, [
                'full_name' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'snils' => 'nullable|string|max:20',
                'inn' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|string|max:255',
                // Добавляем поддержку URL файлов (для обратной совместимости)
                'photo_url' => 'nullable|url',
                'passport_page_1_url' => 'nullable|url',
                'passport_page_5_url' => 'nullable|url',
                'certificates_file_url' => 'nullable|url',
            ]);

            // Добавляем кастомные правила для полей файлов
            $fileFields = ['photo', 'passport_page_1', 'passport_page_5', 'certificates_file'];
            foreach ($fileFields as $field) {
                if (isset($data[$field])) {
                    if ($request->hasFile($field)) {
                        // Это файл - добавляем валидацию файла
                        $validator->addRules([$field => 'file|mimes:jpeg,png,jpg,gif,pdf|max:204800']);
                    } elseif (is_string($data[$field]) && strpos($data[$field], '@') === 0) {
                        // Это строка с @ - проверяем, что после @ идет валидный URL
                        $url = substr($data[$field], 1);
                        if (!filter_var($url, FILTER_VALIDATE_URL)) {
                            $validator->addRules([$field => 'required']);
                            $validator->addErrors([$field => ['The URL after @ is not valid.']]);
                        }
                    } else {
                        // Неизвестный формат
                        $validator->addRules([$field => 'required']);
                        $validator->addErrors([$field => ['The field must be a file or a string starting with @ followed by a valid URL.']]);
                    }
                }
            }

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personData = array_intersect_key($data, array_flip([
                'full_name', 'position', 'phone', 'snils', 'inn', 
                'birth_date', 'address', 'status'
            ]));

            // Обработка файлов (загруженных через форму)
            $fileFields = ['photo', 'passport_page_1', 'passport_page_5', 'certificates_file'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    
                    // Создаем директорию, если её нет
                    $uploadPath = storage_path('app/public/' . $this->getUploadPath($field));
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    
                    // Генерируем уникальное имя файла
                    $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                    $filePath = $this->getUploadPath($field) . '/' . $filename;
                    $fullPath = storage_path('app/public/' . $filePath);
                    
                    // Перемещаем файл
                    $file->move(dirname($fullPath), basename($fullPath));
                    
                    $personData[$field] = $filename;
                    $personData[$field . '_original_name'] = $file->getClientOriginalName();
                    $personData[$field . '_mime_type'] = $file->getMimeType();
                    $personData[$field . '_size'] = filesize($fullPath);
                    
                    Log::info("File uploaded: $field", ['filename' => $filename, 'path' => $filePath]);
                }
            }

            // Обработка файлов по URL
            Log::info('Starting URL file processing...');
            
            // Обработка полей с префиксом @ (URL файлы)
            $urlFields = ['photo', 'passport_page_1', 'passport_page_5', 'certificates_file'];
            foreach ($urlFields as $field) {
                if (!empty($data[$field]) && is_string($data[$field]) && strpos($data[$field], '@') === 0) {
                    $url = substr($data[$field], 1); // Убираем префикс @
                    Log::info("Found URL for $field with @ prefix: $url");
                    
                    try {
                        // Создаем директорию, если её нет
                        $uploadPath = storage_path('app/public/' . $this->getUploadPath($field));
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                            Log::info("Created directory: $uploadPath");
                        }
                        
                        // Генерируем уникальное имя файла
                        $filename = time() . '_' . $field . '_' . basename($url);
                        $filePath = $this->getUploadPath($field) . '/' . $filename;
                        $fullPath = storage_path('app/public/' . $filePath);
                        
                        Log::info("Attempting to download file from URL: $url");
                        // Загружаем файл по URL
                        $fileContent = file_get_contents($url);
                        if ($fileContent !== false) {
                            file_put_contents($fullPath, $fileContent);
                            
                            $personData[$field] = $filename;
                            $personData[$field . '_original_name'] = basename($url);
                            $personData[$field . '_mime_type'] = $this->getMimeType($url);
                            $personData[$field . '_size'] = filesize($fullPath);
                            
                            Log::info("File downloaded from URL: $field", ['url' => $url, 'filename' => $filename, 'path' => $filePath]);
                        } else {
                            Log::warning("Failed to download file from URL: $url");
                        }
                    } catch (\Exception $e) {
                        Log::error("Error downloading file from URL $url: " . $e->getMessage());
                    }
                }
            }
            
            // Обработка полей с суффиксом _url (для обратной совместимости)
            $urlFieldsWithSuffix = ['photo_url', 'passport_page_1_url', 'passport_page_5_url', 'certificates_file_url'];
            foreach ($urlFieldsWithSuffix as $urlField) {
                $field = str_replace('_url', '', $urlField);
                Log::info("Processing URL field: $urlField, base field: $field");
                
                if (!empty($data[$urlField])) {
                    $url = $data[$urlField];
                    Log::info("Found URL for $field: $url");
                    
                    try {
                        // Создаем директорию, если её нет
                        $uploadPath = storage_path('app/public/' . $this->getUploadPath($field));
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                            Log::info("Created directory: $uploadPath");
                        }
                        
                        // Генерируем уникальное имя файла
                        $filename = time() . '_' . $field . '_' . basename($url);
                        $filePath = $this->getUploadPath($field) . '/' . $filename;
                        $fullPath = storage_path('app/public/' . $filePath);
                        
                        Log::info("Attempting to download file from URL: $url");
                        // Загружаем файл по URL
                        $fileContent = file_get_contents($url);
                        if ($fileContent !== false) {
                            file_put_contents($fullPath, $fileContent);
                            
                            $personData[$field] = $filename;
                            $personData[$field . '_original_name'] = basename($url);
                            $personData[$field . '_mime_type'] = $this->getMimeType($url);
                            $personData[$field . '_size'] = filesize($fullPath);
                            
                            Log::info("File downloaded from URL: $field", ['url' => $url, 'filename' => $filename, 'path' => $filePath]);
                        } else {
                            Log::warning("Failed to download file from URL: $url");
                        }
                    } catch (\Exception $e) {
                        Log::error("Error downloading file from URL $url: " . $e->getMessage());
                    }
                } else {
                    Log::info("No URL provided for $field");
                }
            }

            $person = People::create($personData);

            return response()->json([
                'success' => true,
                'message' => 'Человек успешно добавлен',
                'data' => $person
            ], 201);

        } catch (\Exception $e) {
            Log::error('API People store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении человека',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store multiple people in storage.
     */
    public function storeBulk(Request $request)
    {
        try {
            $data = $request->all();
            
            // Если данные пустые, пробуем получить из JSON
            if (empty($data)) {
                $jsonContent = $request->getContent();
                if (!empty($jsonContent)) {
                    $data = json_decode($jsonContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('JSON decode failed', ['error' => json_last_error_msg()]);
                        $data = [];
                    }
                }
            }
            
            Log::info('API People storeBulk request data:', $data);
            
            $validator = Validator::make($data, [
                'people' => 'required|array|min:1',
                'people.*.full_name' => 'required|string|max:255',
                'people.*.position' => 'required|string|max:255',
                'people.*.phone' => 'required|string|max:20',
                'people.*.snils' => 'nullable|string|max:20',
                'people.*.inn' => 'nullable|string|max:20',
                'people.*.birth_date' => 'nullable|date',
                'people.*.address' => 'nullable|string|max:500',
                'people.*.status' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $createdPeople = [];
            foreach ($data['people'] as $personData) {
                $person = People::create($personData);
                $createdPeople[] = $person;
            }

            return response()->json([
                'success' => true,
                'message' => 'Люди успешно добавлены',
                'data' => $createdPeople,
                'count' => count($createdPeople)
            ], 201);

        } catch (\Exception $e) {
            Log::error('API People storeBulk error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении людей',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $person = People::with('certificates.certificate')->find($id);

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'Человек не найден'
                ], 404);
            }

            // Загружаем все сертификаты
            $allCertificates = \App\Models\Certificate::all();
            $assignedCertificateIds = $person->certificates->pluck('id')->toArray();
            
            $allCertificatesWithStatus = $allCertificates->map(function ($certificate) use ($assignedCertificateIds, $person) {
                $isAssigned = in_array($certificate->id, $assignedCertificateIds);
                $assignedData = null;
                
                if ($isAssigned) {
                    $assignedData = $person->certificates->where('id', $certificate->id)->first()->pivot;
                }
                
                return [
                    'id' => $certificate->id,
                    'name' => $certificate->name,
                    'description' => $certificate->description,
                    'expiry_date' => $certificate->expiry_date,
                    'created_at' => $certificate->created_at,
                    'updated_at' => $certificate->updated_at,
                    'is_assigned' => $isAssigned,
                    'assigned_data' => $assignedData ? [
                        'assigned_date' => $assignedData->assigned_date,
                        'certificate_number' => $assignedData->certificate_number,
                        'status' => $assignedData->status,
                        'notes' => $assignedData->notes
                    ] : null
                ];
            });
            
            $person->all_certificates = $allCertificatesWithStatus;

            return response()->json([
                'success' => true,
                'data' => $person
            ]);

        } catch (\Exception $e) {
            Log::error('API People show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении данных человека',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $person = People::find($id);

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'Человек не найден'
                ], 404);
            }

            $data = $request->all();
            
            // Если данные пустые, пробуем получить из JSON
            if (empty($data)) {
                $jsonContent = $request->getContent();
                if (!empty($jsonContent)) {
                    $data = json_decode($jsonContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('JSON decode failed', ['error' => json_last_error_msg()]);
                        $data = [];
                    }
                }
            }
            
            Log::info('API People update request data:', $data);
            
            $validator = Validator::make($data, [
                'full_name' => 'sometimes|required|string|max:255',
                'position' => 'sometimes|required|string|max:255',
                'phone' => 'sometimes|required|string|max:20',
                'snils' => 'nullable|string|max:20',
                'inn' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|string|max:255',
                'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:204800', // 200MB
                'passport_page_1' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:204800', // 200MB
                'passport_page_5' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:204800', // 200MB
                'certificates_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:204800', // 200MB
                // Добавляем поддержку URL файлов
                'photo_url' => 'nullable|url',
                'passport_page_1_url' => 'nullable|url',
                'passport_page_5_url' => 'nullable|url',
                'certificates_file_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personUpdateData = array_intersect_key($data, array_flip([
                'full_name', 'position', 'phone', 'snils', 'inn', 
                'birth_date', 'address', 'status'
            ]));

            // Обработка файлов (загруженных через форму)
            $fileFields = ['photo', 'passport_page_1', 'passport_page_5', 'certificates_file'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    // Удаляем старый файл
                    if ($person->$field && file_exists(storage_path('app/public/' . $this->getUploadPath($field) . '/' . $person->$field))) {
                        unlink(storage_path('app/public/' . $this->getUploadPath($field) . '/' . $person->$field));
                    }
                    
                    $file = $request->file($field);
                    
                    // Создаем директорию, если её нет
                    $uploadPath = storage_path('app/public/' . $this->getUploadPath($field));
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    
                    // Генерируем уникальное имя файла
                    $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                    $filePath = $this->getUploadPath($field) . '/' . $filename;
                    $fullPath = storage_path('app/public/' . $filePath);
                    
                    // Перемещаем файл
                    $file->move(dirname($fullPath), basename($fullPath));
                    
                    $personUpdateData[$field] = $filename;
                    $personUpdateData[$field . '_original_name'] = $file->getClientOriginalName();
                    $personUpdateData[$field . '_mime_type'] = $file->getMimeType();
                    $personUpdateData[$field . '_size'] = filesize($fullPath);
                    
                    Log::info("File uploaded: $field", ['filename' => $filename, 'path' => $filePath]);
                }
            }

            // Обработка файлов по URL
            $urlFields = ['photo_url', 'passport_page_1_url', 'passport_page_5_url', 'certificates_file_url'];
            foreach ($urlFields as $urlField) {
                $field = str_replace('_url', '', $urlField);
                if (!empty($data[$urlField])) {
                    $url = $data[$urlField];
                    
                    try {
                        // Удаляем старый файл
                        if ($person->$field && file_exists(storage_path('app/public/' . $this->getUploadPath($field) . '/' . $person->$field))) {
                            unlink(storage_path('app/public/' . $this->getUploadPath($field) . '/' . $person->$field));
                        }
                        
                        // Создаем директорию, если её нет
                        $uploadPath = storage_path('app/public/' . $this->getUploadPath($field));
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                        }
                        
                        // Генерируем уникальное имя файла
                        $filename = time() . '_' . $field . '_' . basename($url);
                        $filePath = $this->getUploadPath($field) . '/' . $filename;
                        $fullPath = storage_path('app/public/' . $filePath);
                        
                        // Загружаем файл по URL
                        $fileContent = file_get_contents($url);
                        if ($fileContent !== false) {
                            file_put_contents($fullPath, $fileContent);
                            
                            $personUpdateData[$field] = $filename;
                            $personUpdateData[$field . '_original_name'] = basename($url);
                            $personUpdateData[$field . '_mime_type'] = $this->getMimeType($url);
                            $personUpdateData[$field . '_size'] = filesize($fullPath);
                            
                            Log::info("File downloaded from URL: $field", ['url' => $url, 'filename' => $filename, 'path' => $filePath]);
                        } else {
                            Log::warning("Failed to download file from URL: $url");
                        }
                    } catch (\Exception $e) {
                        Log::error("Error downloading file from URL $url: " . $e->getMessage());
                    }
                }
            }

            $person->update($personUpdateData);

            return response()->json([
                'success' => true,
                'message' => 'Человек успешно обновлен',
                'data' => $person->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('API People update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении человека',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $person = People::find($id);

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'Человек не найден'
                ], 404);
            }

            // Удаляем файлы
            $fileFields = ['photo', 'passport_page1', 'passport_page2'];
            foreach ($fileFields as $field) {
                if ($person->$field && Storage::disk('public')->exists($person->$field)) {
                    Storage::disk('public')->delete($person->$field);
                }
            }

            $person->delete();

            return response()->json([
                'success' => true,
                'message' => 'Человек успешно удален'
            ]);

        } catch (\Exception $e) {
            Log::error('API People destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении человека',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить путь для загрузки файла
     */
    private function getUploadPath($field)
    {
        $paths = [
            'photo' => 'photos',
            'passport_page_1' => 'passports',
            'passport_page_5' => 'passports',
            'certificates_file' => 'certificates'
        ];
        
        return $paths[$field] ?? 'uploads/people';
    }

    /**
     * Получить MIME тип файла по URL
     */
    private function getMimeType($url)
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Загрузить файл со всеми удостоверениями
     */
    public function uploadCertificatesFile(Request $request, string $id)
    {
        try {
            // Логируем входящий запрос
            Log::info('Upload certificates file request', [
                'person_id' => $id,
                'has_file' => $request->hasFile('certificates_file'),
                'all_files' => $request->allFiles(),
                'request_data' => $request->all()
            ]);

            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            $validator = Validator::make($request->all(), [
                'certificates_file' => 'required|file|mimes:pdf|max:204800', // 200MB
            ]);

            if ($validator->fails()) {
                Log::error('Upload validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Удаляем старый файл
            if ($person->certificates_file && file_exists(storage_path('app/public/certificates/' . $person->certificates_file))) {
                unlink(storage_path('app/public/certificates/' . $person->certificates_file));
            }

            $file = $request->file('certificates_file');
            
            // Логируем информацию о файле
            Log::info('File upload details', [
                'file_exists' => $file ? 'yes' : 'no',
                'is_valid' => $file ? $file->isValid() : 'no file',
                'original_name' => $file ? $file->getClientOriginalName() : 'no file',
                'size' => $file ? $file->getSize() : 'no file',
                'mime_type' => $file ? $file->getMimeType() : 'no file',
                'temp_path' => $file ? $file->getPathname() : 'no file'
            ]);
            
            // Проверяем, что файл действительно загружен
            if (!$file || !$file->isValid()) {
                Log::error('File upload failed', [
                    'file_exists' => $file ? 'yes' : 'no',
                    'is_valid' => $file ? $file->isValid() : 'no file',
                    'error' => $file ? $file->getError() : 'no file'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не был загружен или поврежден'
                ], 422);
            }
            
            $filename = time() . '_certificates_file.' . $file->getClientOriginalExtension();
            $filePath = 'certificates/' . $filename;
            $fullPath = storage_path('app/public/' . $filePath);

            // Создаем директорию
            if (!file_exists(storage_path('app/public/certificates'))) {
                mkdir(storage_path('app/public/certificates'), 0755, true);
            }

            // Используем storeAs вместо move для более надежной загрузки
            $storedPath = $file->storeAs('certificates', $filename, 'public');
            
            if (!$storedPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось сохранить файл'
                ], 500);
            }

            $person->update([
                'certificates_file' => $filename,
                'certificates_file_original_name' => $file->getClientOriginalName(),
                'certificates_file_mime_type' => $file->getMimeType(),
                'certificates_file_size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Файл со всеми удостоверениями успешно загружен',
                'data' => [
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'path' => $storedPath
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Upload certificates file error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить файл со всеми удостоверениями
     */
    /**
     * Объединить PDF файлы (добавить новый файл в начало существующего)
     */
    public function mergeCertificatesFile(Request $request, string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            if (!$person->certificates_file) {
                return response()->json(['success' => false, 'message' => 'У человека нет файла сертификатов для объединения'], 400);
            }

            $validator = Validator::make($request->all(), [
                'new_file' => 'required|file|mimes:pdf|max:204800', // 200MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Получаем пути к файлам
            $relativePath = $person->certificates_file;
            if (strpos($relativePath, 'certificates/') !== 0) {
                $relativePath = 'certificates/' . $relativePath;
            }
            
            $existingFilePath = storage_path('app/public/' . $relativePath);
            $newFile = $request->file('new_file');
            $newFilePath = $newFile->getPathname();

            if (!file_exists($existingFilePath)) {
                return response()->json(['success' => false, 'message' => 'Существующий файл сертификатов не найден'], 404);
            }

            // Создаем временный файл для объединения
            $tempMergedPath = storage_path('app/temp/merged_' . time() . '.pdf');
            
            // Создаем директорию temp если не существует
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Объединяем PDF файлы (новый файл + существующий файл)
            $this->mergePdfFiles([$newFilePath, $existingFilePath], $tempMergedPath);

            // Удаляем старый файл
            unlink($existingFilePath);

            // Перемещаем объединенный файл на место старого
            $newFileName = 'merged_' . time() . '_' . $person->id . '_certificates.pdf';
            $newRelativePath = 'certificates/' . $newFileName;
            $newFilePath = storage_path('app/public/' . $newRelativePath);
            
            rename($tempMergedPath, $newFilePath);

            // Обновляем запись в базе данных
            $person->update([
                'certificates_file' => $newRelativePath,
                'certificates_file_original_name' => $newFileName,
                'certificates_file_mime_type' => 'application/pdf',
                'certificates_file_size' => filesize($newFilePath),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PDF файлы успешно объединены',
                'data' => [
                    'file_name' => $newFileName,
                    'file_size' => filesize($newFilePath),
                    'file_path' => $newRelativePath
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Merge certificates file error: ' . $e->getMessage(), [
                'person_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при объединении файлов',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Объединить несколько PDF файлов в один
     */
    private function mergePdfFiles(array $filePaths, string $outputPath)
    {
        // Используем простой метод объединения через file_get_contents
        // Для более сложной логики можно использовать библиотеку TCPDF или FPDI
        
        $mergedContent = '';
        
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $mergedContent .= $content;
            }
        }
        
        file_put_contents($outputPath, $mergedContent);
    }

    /**
     * Скачать файл со всеми удостоверениями
     */
    public function downloadCertificatesFile(string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            if (!$person->certificates_file) {
                return response()->json(['success' => false, 'message' => 'Файл удостоверений не найден'], 404);
            }

            // Проверяем, есть ли уже префикс certificates/ в пути
            $relativePath = $person->certificates_file;
            if (strpos($relativePath, 'certificates/') !== 0) {
                // Старый формат без префикса - добавляем
                $relativePath = 'certificates/' . $relativePath;
            }
            
            $filePath = storage_path('app/public/' . $relativePath);
            
            if (!file_exists($filePath)) {
                return response()->json(['success' => false, 'message' => 'Физический файл не найден'], 404);
            }

            $originalName = $person->certificates_file_original_name ?? $person->certificates_file;
            $mimeType = $person->certificates_file_mime_type ?? 'application/octet-stream';

            return response()->download($filePath, $originalName, [
                'Content-Type' => $mimeType,
            ]);

        } catch (\Exception $e) {
            Log::error('Download certificates file error: ' . $e->getMessage(), [
                'person_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при скачивании файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCertificatesFile(string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            if ($person->certificates_file) {
                // Проверяем, есть ли уже префикс certificates/ в пути
                $relativePath = $person->certificates_file;
                if (strpos($relativePath, 'certificates/') !== 0) {
                    // Старый формат без префикса - добавляем
                    $relativePath = 'certificates/' . $relativePath;
                }
                
                $filePath = storage_path('app/public/' . $relativePath);
                
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $person->update([
                'certificates_file' => null,
                'certificates_file_original_name' => null,
                'certificates_file_mime_type' => null,
                'certificates_file_size' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Файл со всеми удостоверениями успешно удален'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete certificates file error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузить фото
     */
    public function uploadPhoto(Request $request, string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            $validator = Validator::make($request->all(), [
                'photo' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048', // 2MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Удаляем старое фото
            if ($person->photo && file_exists(storage_path('app/public/photos/' . $person->photo))) {
                unlink(storage_path('app/public/photos/' . $person->photo));
            }

            $file = $request->file('photo');
            $filename = time() . '_photo.' . $file->getClientOriginalExtension();
            $filePath = 'photos/' . $filename;
            $fullPath = storage_path('app/public/' . $filePath);

            // Создаем директорию
            if (!file_exists(storage_path('app/public/photos'))) {
                mkdir(storage_path('app/public/photos'), 0755, true);
            }

            $file->move(dirname($fullPath), basename($fullPath));

            $person->update(['photo' => $filename]);

            return response()->json([
                'success' => true,
                'message' => 'Фото успешно загружено',
                'data' => ['filename' => $filename]
            ]);

        } catch (\Exception $e) {
            Log::error('Upload photo error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке фото',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить фото
     */
    public function deletePhoto(string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            if ($person->photo && file_exists(storage_path('app/public/photos/' . $person->photo))) {
                unlink(storage_path('app/public/photos/' . $person->photo));
            }

            $person->update(['photo' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Фото успешно удалено'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete photo error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении фото',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузить 1 страницу паспорта
     */
    public function uploadPassportPage1(Request $request, string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            $validator = Validator::make($request->all(), [
                'passport_page_1' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Удаляем старый файл
            if ($person->passport_page_1 && file_exists(storage_path('app/public/passports/' . $person->passport_page_1))) {
                unlink(storage_path('app/public/passports/' . $person->passport_page_1));
            }

            $file = $request->file('passport_page_1');
            $filename = time() . '_passport_page_1.' . $file->getClientOriginalExtension();
            $filePath = 'passports/' . $filename;
            $fullPath = storage_path('app/public/' . $filePath);

            // Создаем директорию
            if (!file_exists(storage_path('app/public/passports'))) {
                mkdir(storage_path('app/public/passports'), 0755, true);
            }

            $file->move(dirname($fullPath), basename($fullPath));

            $person->update(['passport_page_1' => $filename]);

            return response()->json([
                'success' => true,
                'message' => '1 страница паспорта успешно загружена',
                'data' => ['filename' => $filename]
            ]);

        } catch (\Exception $e) {
            Log::error('Upload passport page 1 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить 1 страницу паспорта
     */
    public function deletePassportPage1(string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            if ($person->passport_page_1 && file_exists(storage_path('app/public/passports/' . $person->passport_page_1))) {
                unlink(storage_path('app/public/passports/' . $person->passport_page_1));
            }

            $person->update(['passport_page_1' => null]);

            return response()->json([
                'success' => true,
                'message' => '1 страница паспорта успешно удалена'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete passport page 1 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузить 5 страницу паспорта
     */
    public function uploadPassportPage5(Request $request, string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            $validator = Validator::make($request->all(), [
                'passport_page_5' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Удаляем старый файл
            if ($person->passport_page_5 && file_exists(storage_path('app/public/passports/' . $person->passport_page_5))) {
                unlink(storage_path('app/public/passports/' . $person->passport_page_5));
            }

            $file = $request->file('passport_page_5');
            $filename = time() . '_passport_page_5.' . $file->getClientOriginalExtension();
            $filePath = 'passports/' . $filename;
            $fullPath = storage_path('app/public/' . $filePath);

            // Создаем директорию
            if (!file_exists(storage_path('app/public/passports'))) {
                mkdir(storage_path('app/public/passports'), 0755, true);
            }

            $file->move(dirname($fullPath), basename($fullPath));

            $person->update(['passport_page_5' => $filename]);

            return response()->json([
                'success' => true,
                'message' => '5 страница паспорта успешно загружена',
                'data' => ['filename' => $filename]
            ]);

        } catch (\Exception $e) {
            Log::error('Upload passport page 5 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить 5 страницу паспорта
     */
    public function deletePassportPage5(string $id)
    {
        try {
            $person = People::find($id);
            if (!$person) {
                return response()->json(['success' => false, 'message' => 'Человек не найден'], 404);
            }

            if ($person->passport_page_5 && file_exists(storage_path('app/public/passports/' . $person->passport_page_5))) {
                unlink(storage_path('app/public/passports/' . $person->passport_page_5));
            }

            $person->update(['passport_page_5' => null]);

            return response()->json([
                'success' => true,
                'message' => '5 страница паспорта успешно удалена'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete passport page 5 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
