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

            // Пагинация
            $perPage = $request->get('per_page', 15);
            $people = $query->with('certificates')->paginate($perPage);
            
            // Загружаем все сертификаты для каждого человека
            $allCertificates = \App\Models\Certificate::all();
            
            // Добавляем все сертификаты к каждому человеку
            $people->getCollection()->transform(function ($person) use ($allCertificates) {
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
                'total' => $people->total(),
                'count' => count($people->items()),
                'search_applied' => $request->filled('search')
            ]);

            return response()->json([
                'success' => true,
                'data' => $people->items(),
                'pagination' => [
                    'current_page' => $people->currentPage(),
                    'last_page' => $people->lastPage(),
                    'per_page' => $people->perPage(),
                    'total' => $people->total(),
                ]
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
            
            $validator = Validator::make($data, [
                'full_name' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'snils' => 'nullable|string|max:20',
                'inn' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|string|max:255',
                'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
                'passport_page1' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'passport_page2' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

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

            // Обработка файлов
            $fileFields = ['photo', 'passport_page1', 'passport_page2'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $fileName = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                    $filePath = $file->storeAs('uploads/people', $fileName, 'public');
                    $personData[$field] = $filePath;
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
                'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
                'passport_page1' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'passport_page2' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
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

            // Обработка файлов
            $fileFields = ['photo', 'passport_page1', 'passport_page2'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    // Удаляем старый файл
                    if ($person->$field && Storage::disk('public')->exists($person->$field)) {
                        Storage::disk('public')->delete($person->$field);
                    }

                    $file = $request->file($field);
                    $fileName = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                    $filePath = $file->storeAs('uploads/people', $fileName, 'public');
                    $personUpdateData[$field] = $filePath;
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
}
