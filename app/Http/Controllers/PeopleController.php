<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\People;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = People::with(['certificates' => function($q) {
                $q->withPivot('assigned_date', 'certificate_number', 'status', 'notes');
            }]);

            // Фильтрация
            if ($request->filled('search_fio')) {
                $query->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($request->search_fio) . '%']);
            }

            if ($request->filled('search_position')) {
                $query->whereRaw('LOWER(position) LIKE ?', ['%' . strtolower($request->search_position) . '%']);
            }

            if ($request->filled('search_phone')) {
                $query->whereRaw('LOWER(phone) LIKE ?', ['%' . strtolower($request->search_phone) . '%']);
            }

            if ($request->filled('search_status')) {
                $query->whereRaw('LOWER(status) LIKE ?', ['%' . strtolower($request->search_status) . '%']);
            }

            // Пагинация
            $perPage = $request->get('per_page', 20);
            $people = $query->paginate($perPage);

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
            Log::error('API People index error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

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
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'position' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'snils' => 'nullable|string|max:20',
                'inn' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->only([
                'full_name', 'position', 'phone', 'snils', 'inn', 'birth_date', 'address', 'status'
            ]);

            $person = People::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Человек успешно добавлен',
                'data' => $person->load('certificates')
            ], 201);

        } catch (\Exception $e) {
            Log::error('API People store error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении человека',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store multiple people at once.
     */
    public function storeBulk(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'people' => 'required|array|min:1',
                'people.*.full_name' => 'required|string|max:255',
                'people.*.position' => 'nullable|string|max:255',
                'people.*.phone' => 'nullable|string|max:20',
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
            $errors = [];

            foreach ($request->people as $index => $personData) {
                try {
                    $person = People::create($personData);
                    $createdPeople[] = $person;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'data' => $personData,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Массовое добавление завершено',
                'data' => [
                    'created' => count($createdPeople),
                    'errors' => count($errors),
                    'people' => $createdPeople,
                    'error_details' => $errors
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('API People storeBulk error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при массовом добавлении людей',
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
            $person = People::with(['certificates' => function($q) {
                $q->withPivot('assigned_date', 'certificate_number', 'status', 'notes');
            }])->find($id);

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'Человек не найден'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $person
            ]);

        } catch (\Exception $e) {
            Log::error('API People show error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

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

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|required|string|max:255',
                'position' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'snils' => 'nullable|string|max:20',
                'inn' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->only([
                'full_name', 'position', 'phone', 'snils', 'inn', 'birth_date', 'address', 'status'
            ]);

            $person->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Данные человека успешно обновлены',
                'data' => $person->load('certificates')
            ]);

        } catch (\Exception $e) {
            Log::error('API People update error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении данных человека',
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

            // Удаляем связанные файлы
            if ($person->photo && file_exists(storage_path('app/public/' . $person->photo))) {
                unlink(storage_path('app/public/' . $person->photo));
            }

            if ($person->passport_page_1 && file_exists(storage_path('app/public/' . $person->passport_page_1))) {
                unlink(storage_path('app/public/' . $person->passport_page_1));
            }

            if ($person->passport_page_5 && file_exists(storage_path('app/public/' . $person->passport_page_5))) {
                unlink(storage_path('app/public/' . $person->passport_page_5));
            }

            if ($person->certificates_file && file_exists(storage_path('app/public/' . $person->certificates_file))) {
                unlink(storage_path('app/public/' . $person->certificates_file));
            }

            $person->delete();

            return response()->json([
                'success' => true,
                'message' => 'Человек успешно удален'
            ]);

        } catch (\Exception $e) {
            Log::error('API People destroy error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении человека',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}