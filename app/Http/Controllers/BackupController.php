<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function download(Request $request)
    {
        $timestamp = now()->format('Ymd_His');
        $sqlFilename = "backup_{$timestamp}.sql";
        $jsonFilename = "backup_{$timestamp}.json";
        $tmpPathSql = storage_path('app/' . $sqlFilename);
        $tmpPathJson = storage_path('app/' . $jsonFilename);

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        // Пытаемся сделать pg_dump, если это PostgreSQL
        if (($config['driver'] ?? null) === 'pgsql') {
            try {
                $host = $config['host'] ?? 'localhost';
                $port = $config['port'] ?? '5432';
                $database = $config['database'] ?? '';
                $username = $config['username'] ?? '';
                $password = $config['password'] ?? '';

                $dsn = "postgresql://{$username}:{$password}@{$host}:{$port}/{$database}";

                $process = new Process([
                    'pg_dump',
                    '--no-owner',
                    '--format=plain',
                    "--dbname={$dsn}"
                ]);
                $process->setTimeout(300);
                // Устанавливаем переменные окружения для pg_dump (на случай, если нужен PGPASSWORD)
                $env = ['PGPASSWORD' => $password];
                $process->setEnv($env);
                $process->run(function ($type, $buffer) use ($tmpPathSql) {
                    file_put_contents($tmpPathSql, $buffer, FILE_APPEND);
                });

                if ($process->isSuccessful() && file_exists($tmpPathSql) && filesize($tmpPathSql) > 0) {
                    return response()->download($tmpPathSql, $sqlFilename)->deleteFileAfterSend(true);
                }
                Log::warning('pg_dump did not produce output, falling back to JSON backup', [
                    'exit_code' => $process->getExitCode(),
                    'error' => $process->getErrorOutput(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('pg_dump failed, falling back to JSON backup', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: JSON dump основных таблиц
        try {
            $people = DB::table('people')->get();
            $certificates = DB::table('certificates')->get();
            $peopleCertificates = DB::table('people_certificates')->get();

            $payload = [
                'exported_at' => now()->toISOString(),
                'people' => $people,
                'certificates' => $certificates,
                'people_certificates' => $peopleCertificates,
            ];

            file_put_contents($tmpPathJson, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return response()->download($tmpPathJson, $jsonFilename)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('Backup fallback failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать резервную копию',
            ], 500);
        }
    }
}


