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
        Schema::table('people', function (Blueprint $table) {
            $table->string('certificates_file')->nullable();
            $table->string('certificates_file_original_name')->nullable();
            $table->string('certificates_file_mime_type')->nullable();
            $table->integer('certificates_file_size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'certificates_file',
                'certificates_file_original_name',
                'certificates_file_mime_type',
                'certificates_file_size'
            ]);
        });
    }
};
