<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupController extends Controller
{
    public function download(Request $request)
    {
        $timestamp = now()->format('Ymd_His');
        $zipFilename = "backup_{$timestamp}.zip";
        $zipPath = storage_path('app/' . $zipFilename);
        
        // Создаем ZIP архив
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            Log::error('Failed to create ZIP archive', ['path' => $zipPath]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать архив',
            ], 500);
        }

        try {
            // 1. Экспорт данных БД
            $dbData = $this->exportDatabaseData();
            if ($dbData) {
                // Определяем формат данных (SQL или JSON)
                $extension = (strpos($dbData, 'CREATE TABLE') !== false || strpos($dbData, 'INSERT INTO') !== false) ? 'sql' : 'json';
                $zip->addFromString("database.{$extension}", $dbData);
            }

            // 2. Экспорт файлов
            $filesCount = $this->addFilesToZip($zip);
            
            // 3. Добавляем README с описанием структуры
            $readme = $this->generateReadme($filesCount);
            $zip->addFromString('README.txt', $readme);
            
            $zip->close();

            Log::info('Backup created successfully', [
                'zip_file' => $zipPath,
                'files_count' => $filesCount,
                'size' => filesize($zipPath)
            ]);

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
            
        } catch (\Throwable $e) {
            if ($zip->close() === false) {
                @unlink($zipPath);
            }
            Log::error('Backup creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать резервную копию: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Экспорт данных из БД в JSON
     */
    private function exportDatabaseData(): ?string
    {
        try {
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
                    $tmpPathSql = storage_path('app/tmp_backup_' . time() . '.sql');

                    $process = new Process([
                        'pg_dump',
                        '--no-owner',
                        '--format=plain',
                        "--dbname={$dsn}"
                    ]);
                    $process->setTimeout(300);
                    $env = ['PGPASSWORD' => $password];
                    $process->setEnv($env);
                    $process->run(function ($type, $buffer) use ($tmpPathSql) {
                        file_put_contents($tmpPathSql, $buffer, FILE_APPEND);
                    });

                    if ($process->isSuccessful() && file_exists($tmpPathSql) && filesize($tmpPathSql) > 0) {
                        $sqlContent = file_get_contents($tmpPathSql);
                        @unlink($tmpPathSql);
                        return $sqlContent;
                    }
                    @unlink($tmpPathSql);
                    Log::warning('pg_dump did not produce output, falling back to JSON backup');
                } catch (\Throwable $e) {
                    Log::warning('pg_dump failed, falling back to JSON backup', ['error' => $e->getMessage()]);
                }
            }

            // Fallback: JSON dump основных таблиц
            $people = DB::table('people')->get();
            $certificates = DB::table('certificates')->get();
            $peopleCertificates = DB::table('people_certificates')->get();
            $certificateOrders = DB::table('certificate_orders')->get();

            $payload = [
                'exported_at' => now()->toISOString(),
                'people' => $people,
                'certificates' => $certificates,
                'people_certificates' => $peopleCertificates,
                'certificate_orders' => $certificateOrders,
            ];

            return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
        } catch (\Throwable $e) {
            Log::error('Database export failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Добавить все файлы в ZIP архив
     */
    private function addFilesToZip(ZipArchive $zip): int
    {
        $filesCount = 0;
        $storagePath = storage_path('app/public');
        
        try {
            // Файлы из таблицы people
            $people = DB::table('people')->get();
            foreach ($people as $person) {
                // Фото
                if (!empty($person->photo)) {
                    $filePath = $storagePath . '/photos/' . basename($person->photo);
                    if (file_exists($filePath) && is_file($filePath)) {
                        $zip->addFile($filePath, 'files/photos/' . basename($person->photo));
                        $filesCount++;
                    }
                }
                
                // Паспорт страница 1
                if (!empty($person->passport_page_1)) {
                    $filePath = $storagePath . '/passports/' . basename($person->passport_page_1);
                    if (file_exists($filePath) && is_file($filePath)) {
                        $zip->addFile($filePath, 'files/passports/' . basename($person->passport_page_1));
                        $filesCount++;
                    }
                }
                
                // Паспорт страница 5
                if (!empty($person->passport_page_5)) {
                    $filePath = $storagePath . '/passports/' . basename($person->passport_page_5);
                    if (file_exists($filePath) && is_file($filePath)) {
                        $zip->addFile($filePath, 'files/passports/' . basename($person->passport_page_5));
                        $filesCount++;
                    }
                }
                
                // Файл со всеми удостоверениями
                if (!empty($person->certificates_file)) {
                    $filename = basename($person->certificates_file);
                    // Проверяем разные возможные пути
                    $possiblePaths = [
                        $storagePath . '/certificates/' . $filename,
                        $storagePath . '/' . $filename,
                    ];
                    
                    foreach ($possiblePaths as $filePath) {
                        if (file_exists($filePath) && is_file($filePath)) {
                            $zip->addFile($filePath, 'files/certificates/' . $filename);
                            $filesCount++;
                            break;
                        }
                    }
                }
            }
            
            // Файлы из таблицы people_certificates
            $peopleCertificates = DB::table('people_certificates')->get();
            foreach ($peopleCertificates as $pc) {
                if (!empty($pc->certificate_file)) {
                    $filename = basename($pc->certificate_file);
                    // Проверяем разные возможные пути
                    $possiblePaths = [
                        $storagePath . '/certificates/' . $filename,
                        $storagePath . '/' . $filename,
                    ];
                    
                    foreach ($possiblePaths as $filePath) {
                        if (file_exists($filePath) && is_file($filePath)) {
                            $zip->addFile($filePath, 'files/certificates/' . $filename);
                            $filesCount++;
                            break;
                        }
                    }
                }
            }
            
            Log::info('Files added to backup', ['count' => $filesCount]);
            
        } catch (\Throwable $e) {
            Log::error('Error adding files to ZIP', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        
        return $filesCount;
    }

    /**
     * Генерация README файла для архива
     */
    private function generateReadme(int $filesCount): string
    {
        $readme = "═══════════════════════════════════════════════════════════════\n";
        $readme .= "  РЕЗЕРВНАЯ КОПИЯ БАЗЫ ДАННЫХ WebAPILaborService\n";
        $readme .= "═══════════════════════════════════════════════════════════════\n\n";
        $readme .= "Дата создания: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $readme .= "СТРУКТУРА АРХИВА:\n";
        $readme .= "─────────────────────────────────────────────────────────────\n\n";
        $readme .= "1. database.sql или database.json\n";
        $readme .= "   Содержит полный дамп базы данных (SQL) или данные в формате JSON\n\n";
        $readme .= "2. files/ - директория с файлами\n";
        $readme .= "   ├── photos/          - фотографии сотрудников\n";
        $readme .= "   ├── passports/       - страницы паспортов (1 и 5)\n";
        $readme .= "   └── certificates/    - файлы удостоверений\n\n";
        $readme .= "Статистика:\n";
        $readme .= "  - Файлов в архиве: {$filesCount}\n\n";
        $readme .= "ВОССТАНОВЛЕНИЕ:\n";
        $readme .= "─────────────────────────────────────────────────────────────\n\n";
        $readme .= "1. Восстановление базы данных:\n";
        $readme .= "   - Если файл database.sql: выполните pg_restore или psql\n";
        $readme .= "   - Если файл database.json: импортируйте данные через API\n\n";
        $readme .= "2. Восстановление файлов:\n";
        $readme .= "   - Скопируйте содержимое папки files/ в storage/app/public/\n";
        $readme .= "   - Сохраните структуру директорий (photos/, passports/, certificates/)\n\n";
        $readme .= "═══════════════════════════════════════════════════════════════\n";
        
        return $readme;
    }
}


