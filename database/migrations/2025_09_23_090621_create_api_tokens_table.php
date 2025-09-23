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
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название токена
            $table->string('token', 64)->unique(); // Сам токен
            $table->text('description')->nullable(); // Описание токена
            $table->timestamp('last_used_at')->nullable(); // Последнее использование
            $table->timestamp('expires_at')->nullable(); // Срок действия
            $table->boolean('is_active')->default(true); // Активен ли токен
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
