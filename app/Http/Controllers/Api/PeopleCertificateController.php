<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\People;
use App\Models\Certificate;
use App\Models\PeopleCertificate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PeopleCertificateController extends Controller
{
    /**
     * Привязать сертификат к человеку
     */
    public function store(Request $request)
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
            
            Log::info('API PeopleCertificate store request data:', $data);
            
            $validator = Validator::make($data, [
                'people_id' => 'required|integer|exists:people,id',
                'certificate_id' => 'required|integer|exists:certificates,id',
                'assigned_date' => 'required|date',
                'certificate_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
                'certificate_file' => 'nullable|file|max:204800', // 200MB
                // Добавляем поддержку URL файлов
                'certificate_file_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $peopleId = $data['people_id'];
            $certificateId = $data['certificate_id'];

            // Проверяем, существует ли уже связь
            $existingRelation = PeopleCertificate::where('people_id', $peopleId)
                ->where('certificate_id', $certificateId)
                ->first();

            if ($existingRelation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сертификат уже привязан к этому человеку'
                ], 422);
            }

            $certificateData = [
                'people_id' => $peopleId,
                'certificate_id' => $certificateId,
                'assigned_date' => $data['assigned_date'],
                'certificate_number' => $data['certificate_number'] ?? 'Н/Д',
                'notes' => $data['notes'] ?? null,
            ];

            // Автоматически определяем статус на основе срока действия
            $certificate = Certificate::find($certificateId);
            $assignedDate = \Carbon\Carbon::parse($data['assigned_date']);
            $expiryDate = $assignedDate->copy()->addYears($certificate->expiry_date ?: 1);
            $isExpired = $expiryDate->isPast();
            $isExpiringSoon = now()->diffInDays($expiryDate, false) <= 60 && now()->diffInDays($expiryDate, false) > 0;
            
            if ($isExpired) {
                $certificateData['status'] = 2; // Просрочен
            } elseif ($isExpiringSoon) {
                $certificateData['status'] = 3; // Скоро просрочится
            } else {
                $certificateData['status'] = 4; // Действует
            }

            // Обработка файла сертификата (загруженного через форму)
            if ($request->hasFile('certificate_file')) {
                $file = $request->file('certificate_file');
                
                // Создаем директорию, если её нет
                $uploadPath = storage_path('app/public/certificates');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Генерируем уникальное имя файла
                $filename = time() . '_' . $peopleId . '_' . $certificateId . '.' . $file->getClientOriginalExtension();
                $filePath = 'certificates/' . $filename;
                $fullPath = storage_path('app/public/' . $filePath);
                
                // Перемещаем файл
                $file->move(dirname($fullPath), basename($fullPath));
                
                $certificateData['certificate_file'] = $filename;
                $certificateData['certificate_file_original_name'] = $file->getClientOriginalName();
                $certificateData['certificate_file_mime_type'] = $file->getClientMimeType();
                $certificateData['certificate_file_size'] = filesize($fullPath);
            }

            // Обработка файла сертификата по URL
            if (!empty($data['certificate_file_url'])) {
                $url = $data['certificate_file_url'];
                
                try {
                    // Создаем директорию, если её нет
                    $uploadPath = storage_path('app/public/certificates');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    
                    // Генерируем уникальное имя файла
                    $filename = time() . '_' . $peopleId . '_' . $certificateId . '_' . basename($url);
                    $filePath = 'certificates/' . $filename;
                    $fullPath = storage_path('app/public/' . $filePath);
                    
                    // Загружаем файл по URL
                    $fileContent = file_get_contents($url);
                    if ($fileContent !== false) {
                        file_put_contents($fullPath, $fileContent);
                        
                        $certificateData['certificate_file'] = $filename;
                        $certificateData['certificate_file_original_name'] = basename($url);
                        $certificateData['certificate_file_mime_type'] = $this->getMimeType($url);
                        $certificateData['certificate_file_size'] = filesize($fullPath);
                        
                        Log::info("Certificate file downloaded from URL: $url to $filePath");
                    } else {
                        Log::warning("Failed to download certificate file from URL: $url");
                    }
                } catch (\Exception $e) {
                    Log::error("Error downloading certificate file from URL $url: " . $e->getMessage());
                }
            }

            $peopleCertificate = PeopleCertificate::create($certificateData);

            return response()->json([
                'success' => true,
                'message' => 'Сертификат успешно привязан к человеку',
                'data' => $peopleCertificate
            ], 201);

        } catch (\Exception $e) {
            Log::error('API PeopleCertificate store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при привязке сертификата',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновить данные сертификата человека
     */
    public function update(Request $request, string $id)
    {
        try {
            $peopleCertificate = PeopleCertificate::find($id);

            if (!$peopleCertificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Связь сертификата с человеком не найдена'
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
            
            Log::info('API PeopleCertificate update request data:', $data);
            
            $validator = Validator::make($data, [
                'assigned_date' => 'sometimes|required|date',
                'certificate_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
                'certificate_file' => 'nullable|file|max:204800', // 200MB
                // Добавляем поддержку URL файлов
                'certificate_file_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = array_intersect_key($data, array_flip([
                'assigned_date', 'certificate_number', 'notes'
            ]));

            // Обработка файла сертификата (загруженного через форму)
            if ($request->hasFile('certificate_file')) {
                $file = $request->file('certificate_file');
                
                // Удаляем старый файл
                if ($peopleCertificate->certificate_file && file_exists(storage_path('app/public/certificates/' . $peopleCertificate->certificate_file))) {
                    unlink(storage_path('app/public/certificates/' . $peopleCertificate->certificate_file));
                }
                
                // Создаем директорию, если её нет
                $uploadPath = storage_path('app/public/certificates');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Генерируем уникальное имя файла
                $filename = time() . '_' . $peopleCertificate->people_id . '_' . $peopleCertificate->certificate_id . '.' . $file->getClientOriginalExtension();
                $fullPath = storage_path('app/public/certificates/' . $filename);
                
                // Перемещаем файл
                $file->move(dirname($fullPath), basename($fullPath));
                
                $updateData['certificate_file'] = $filename;
                $updateData['certificate_file_original_name'] = $file->getClientOriginalName();
                $updateData['certificate_file_mime_type'] = $file->getClientMimeType();
                $updateData['certificate_file_size'] = filesize($fullPath);
            }

            // Обработка файла сертификата по URL
            if (!empty($data['certificate_file_url'])) {
                $url = $data['certificate_file_url'];
                
                try {
                    // Удаляем старый файл
                    if ($peopleCertificate->certificate_file && file_exists(storage_path('app/public/certificates/' . $peopleCertificate->certificate_file))) {
                        unlink(storage_path('app/public/certificates/' . $peopleCertificate->certificate_file));
                    }
                    
                    // Создаем директорию, если её нет
                    $uploadPath = storage_path('app/public/certificates');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    
                    // Генерируем уникальное имя файла
                    $filename = time() . '_' . $peopleCertificate->people_id . '_' . $peopleCertificate->certificate_id . '_' . basename($url);
                    $fullPath = storage_path('app/public/certificates/' . $filename);
                    
                    // Загружаем файл по URL
                    $fileContent = file_get_contents($url);
                    if ($fileContent !== false) {
                        file_put_contents($fullPath, $fileContent);
                        
                        $updateData['certificate_file'] = $filename;
                        $updateData['certificate_file_original_name'] = basename($url);
                        $updateData['certificate_file_mime_type'] = $this->getMimeType($url);
                        $updateData['certificate_file_size'] = filesize($fullPath);
                        
                        Log::info("Certificate file downloaded from URL: $url to $filename");
                    } else {
                        Log::warning("Failed to download certificate file from URL: $url");
                    }
                } catch (\Exception $e) {
                    Log::error("Error downloading certificate file from URL $url: " . $e->getMessage());
                }
            }

            $peopleCertificate->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Данные сертификата успешно обновлены',
                'data' => $peopleCertificate->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('API PeopleCertificate update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении данных сертификата',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить связь сертификата с человеком
     */
    public function destroy(string $id)
    {
        try {
            $peopleCertificate = PeopleCertificate::find($id);

            if (!$peopleCertificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Связь сертификата с человеком не найдена'
                ], 404);
            }

            // Удаляем файл сертификата
            if ($peopleCertificate->certificate_file && file_exists(storage_path('app/public/certificates/' . $peopleCertificate->certificate_file))) {
                unlink(storage_path('app/public/certificates/' . $peopleCertificate->certificate_file));
            }

            $peopleCertificate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Связь сертификата с человеком успешно удалена'
            ]);

        } catch (\Exception $e) {
            Log::error('API PeopleCertificate destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении связи сертификата',
                'error' => $e->getMessage()
            ], 500);
        }
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
