<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Certificate::query();

            // Фильтрация по названию (без учета регистра и лишних пробелов)
            if ($request->filled('search_name')) {
                $term = trim(preg_replace('/\s+/', ' ', $request->search_name));
                $query->where('name', 'ILIKE', '%' . $term . '%');
            }

            // Фильтрация по описанию (без учета регистра и лишних пробелов)
            if ($request->filled('search_description')) {
                $term = trim(preg_replace('/\s+/', ' ', $request->search_description));
                $query->where('description', 'ILIKE', '%' . $term . '%');
            }

            // Фильтрация по статусу назначения
            if ($request->filled('assigned')) {
                if ($request->assigned === 'true') {
                    $query->whereHas('people');
                } else {
                    $query->whereDoesntHave('people');
                }
            }

            // Получаем все сертификаты без пагинации
            $certificates = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $certificates
            ]);

        } catch (\Exception $e) {
            Log::error('API Certificate index error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка сертификатов',
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
            
            Log::info('API Certificate store request data:', $data);
            
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'expiry_date' => 'required|integer|min:1|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $certificateData = array_intersect_key($data, array_flip(['name', 'description', 'expiry_date']));
            $certificate = Certificate::create($certificateData);

            return response()->json([
                'success' => true,
                'message' => 'Сертификат успешно добавлен',
                'data' => $certificate
            ], 201);

        } catch (\Exception $e) {
            Log::error('API Certificate store error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении сертификата',
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
            $certificate = Certificate::find($id);

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сертификат не найден'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $certificate
            ]);

        } catch (\Exception $e) {
            Log::error('API Certificate show error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении данных сертификата',
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
            $certificate = Certificate::find($id);

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сертификат не найден'
                ], 404);
            }

            $updateData = $request->all();
            
            // Если данные пустые, пробуем получить из JSON
            if (empty($updateData)) {
                $jsonContent = $request->getContent();
                if (!empty($jsonContent)) {
                    $updateData = json_decode($jsonContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('JSON decode failed', ['error' => json_last_error_msg()]);
                        $updateData = [];
                    }
                }
            }
            
            Log::info('API Certificate update request data:', $updateData);
            
            $validator = Validator::make($updateData, [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:500',
                'expiry_date' => 'sometimes|required|integer|min:1|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $certificateUpdateData = array_intersect_key($updateData, array_flip(['name', 'description', 'expiry_date']));
            $certificate->update($certificateUpdateData);

            return response()->json([
                'success' => true,
                'message' => 'Сертификат успешно обновлен',
                'data' => $certificate
            ]);

        } catch (\Exception $e) {
            Log::error('API Certificate update error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении сертификата',
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
            $certificate = Certificate::find($id);

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сертификат не найден'
                ], 404);
            }

            // Проверяем, есть ли связанные записи
            $relatedCount = $certificate->people()->count();
            if ($relatedCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить сертификат, который используется людьми',
                    'related_count' => $relatedCount
                ], 422);
            }

            $certificate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Сертификат успешно удален'
            ]);

        } catch (\Exception $e) {
            Log::error('API Certificate destroy error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении сертификата',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
