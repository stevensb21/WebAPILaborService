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
        // Добавляем индексы для таблицы people
        Schema::table('people', function (Blueprint $table) {
            $table->index('full_name', 'idx_people_full_name');
            $table->index('position', 'idx_people_position');
            $table->index('phone', 'idx_people_phone');
            $table->index('status', 'idx_people_status');
        });

        // Добавляем индексы для таблицы people_certificates
        Schema::table('people_certificates', function (Blueprint $table) {
            $table->index('status', 'idx_people_certificates_status');
            $table->index('certificate_id', 'idx_people_certificates_certificate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем индексы для таблицы people
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_full_name');
            $table->dropIndex('idx_people_position');
            $table->dropIndex('idx_people_phone');
            $table->dropIndex('idx_people_status');
        });

        // Удаляем индексы для таблицы people_certificates
        Schema::table('people_certificates', function (Blueprint $table) {
            $table->dropIndex('idx_people_certificates_status');
            $table->dropIndex('idx_people_certificates_certificate_id');
        });
    }
};
