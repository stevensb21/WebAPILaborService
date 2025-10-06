<?php
// Проверяем конфигурацию PHP для загрузки файлов
echo "=== PHP Configuration Check ===\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";

echo "\n=== Temporary Directory Check ===\n";
$temp_dir = sys_get_temp_dir();
echo "System temp dir: " . $temp_dir . "\n";
echo "Temp dir writable: " . (is_writable($temp_dir) ? 'Yes' : 'No') . "\n";

// Проверяем права на storage
echo "\n=== Storage Directory Check ===\n";
$storage_path = __DIR__ . '/storage/app/public/certificates';
echo "Storage path: " . $storage_path . "\n";
echo "Storage exists: " . (file_exists($storage_path) ? 'Yes' : 'No') . "\n";
echo "Storage writable: " . (is_writable($storage_path) ? 'Yes' : 'No') . "\n";

// Создаем тестовый файл
echo "\n=== Test File Creation ===\n";
$test_file = $storage_path . '/test.txt';
try {
    file_put_contents($test_file, 'test');
    echo "Test file created: Yes\n";
    unlink($test_file);
    echo "Test file deleted: Yes\n";
} catch (Exception $e) {
    echo "Test file error: " . $e->getMessage() . "\n";
}
?>