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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('snils')->nullable();
            $table->string('inn')->nullable();
            $table->string('position')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('passport_page_1')->nullable(); // Паспорт 1 страница
            $table->string('passport_page_1_original_name')->nullable(); // Оригинальное имя файла паспорта 1 стр
            $table->string('passport_page_1_mime_type')->nullable(); // MIME тип паспорта 1 стр
            $table->integer('passport_page_1_size')->nullable(); // Размер паспорта 1 стр в байтах
            $table->string('passport_page_5')->nullable(); // Паспорт 5 страница
            $table->string('passport_page_5_original_name')->nullable(); // Оригинальное имя файла паспорта 5 стр
            $table->string('passport_page_5_mime_type')->nullable(); // MIME тип паспорта 5 стр
            $table->integer('passport_page_5_size')->nullable(); // Размер паспорта 5 стр в байтах
            $table->string('photo')->nullable(); // Фото человека
            $table->string('photo_original_name')->nullable(); // Оригинальное имя файла фото
            $table->string('photo_mime_type')->nullable(); // MIME тип фото
            $table->integer('photo_size')->nullable(); // Размер фото в байтах
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
