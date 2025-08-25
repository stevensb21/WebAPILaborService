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
        Schema::create('people_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('people_id')->constrained()->onDelete('cascade');
            $table->foreignId('certificate_id')->constrained()->onDelete('cascade');
            $table->date('assigned_date');
            $table->text('certificate_number');
            $table->integer('status')->default(4)->nullable(); // 1=active, 2=expired, 3=revoked, 4=pending
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['people_id', 'certificate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people_certificates');
    }
};
