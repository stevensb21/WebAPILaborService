<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people_certificates', function (Blueprint $table) {
            // Файл удостоверения (PDF)
            $table->string('certificate_file')->nullable(); // Путь к файлу
            $table->string('certificate_file_original_name')->nullable(); // Оригинальное имя файла
            $table->string('certificate_file_mime_type')->nullable(); // MIME тип файла
            $table->integer('certificate_file_size')->nullable(); // Размер файла в байтах
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people_certificates', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_file',
                'certificate_file_original_name', 
                'certificate_file_mime_type',
                'certificate_file_size'
            ]);
        });
    }
};
