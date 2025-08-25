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
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });
        
        Schema::table('certificates', function (Blueprint $table) {
            $table->integer('expiry_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });
        
        Schema::table('certificates', function (Blueprint $table) {
            $table->date('expiry_date')->nullable();
        });
    }
};
