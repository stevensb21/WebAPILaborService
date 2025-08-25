<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\People;
use App\Models\PeopleCertificate;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Отчет по просроченным сертификатам
     */
    public function expiredCertificates(Request $request)
    {
        try {
            $query = PeopleCertificate::with(['people', 'certificate'])
                ->where('status', 2); // Просроченные

            // Фильтрация по сертификату
            if ($request->filled('certificate_id')) {
                $query->where('certificate_id', $request->certificate_id);
            }

            $expiredCertificates = $query->get();

            $data = $expiredCertificates->map(function ($pc) {
                $assignedDate = Carbon::parse($pc->assigned_date);
                $expiryDate = $assignedDate->copy()->addYears($pc->certificate->expiry_date ?: 1);
                
                return [
                    'person_id' => $pc->people_id,
                    'person_name' => $pc->people->full_name,
                    'person_position' => $pc->people->position,
                    'certificate_id' => $pc->certificate_id,
                    'certificate_name' => $pc->certificate->name,
                    'assigned_date' => $pc->assigned_date,
                    'expiry_date' => $expiryDate->format('Y-m-d'),
                    'days_expired' => $expiryDate->diffInDays(now()),
                    'certificate_number' => $pc->certificate_number,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $data->count(),
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('API Report expiredCertificates error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при формировании отчета',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отчет по скоро истекающим сертификатам
     */
    public function expiringSoon(Request $request)
    {
        try {
            $query = PeopleCertificate::with(['people', 'certificate'])
                ->where('status', 3); // Скоро истекающие

            // Фильтрация по сертификату
            if ($request->filled('certificate_id')) {
                $query->where('certificate_id', $request->certificate_id);
            }

            $expiringCertificates = $query->get();

            $data = $expiringCertificates->map(function ($pc) {
                $assignedDate = Carbon::parse($pc->assigned_date);
                $expiryDate = $assignedDate->copy()->addYears($pc->certificate->expiry_date ?: 1);
                
                return [
                    'person_id' => $pc->people_id,
                    'person_name' => $pc->people->full_name,
                    'person_position' => $pc->people->position,
                    'certificate_id' => $pc->certificate_id,
                    'certificate_name' => $pc->certificate->name,
                    'assigned_date' => $pc->assigned_date,
                    'expiry_date' => $expiryDate->format('Y-m-d'),
                    'days_until_expiry' => $expiryDate->diffInDays(now(), false),
                    'certificate_number' => $pc->certificate_number,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $data->count(),
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('API Report expiringSoon error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при формировании отчета',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отчет по статусам работников
     */
    public function peopleStatus(Request $request)
    {
        try {
            $query = People::query();

            // Фильтрация по статусу
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $people = $query->get();

            $statusCounts = $people->groupBy('status')->map(function ($group) {
                return $group->count();
            });

            $data = [
                'total_people' => $people->count(),
                'status_distribution' => $statusCounts,
                'people_by_status' => $people->groupBy('status')->map(function ($group) {
                    return $group->map(function ($person) {
                        return [
                            'id' => $person->id,
                            'full_name' => $person->full_name,
                            'position' => $person->position,
                            'phone' => $person->phone,
                        ];
                    });
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('API Report peopleStatus error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при формировании отчета',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
