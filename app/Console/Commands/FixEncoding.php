<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\People;
use App\Models\Certificate;
use Illuminate\Support\Facades\DB;

class FixEncoding extends Command
{
    protected $signature = 'fix:encoding';
    protected $description = 'Fix encoding issues in database';

    public function handle()
    {
        $this->info('Starting encoding fix...');
        
        // Принудительно исправляем все записи
        $this->fixAllRecords();
        
        $this->info('Encoding fix completed!');
    }

    private function fixAllRecords()
    {
        $this->info('Fixing encoding using PostgreSQL syntax...');
        
        // Для PostgreSQL используем другой синтаксис
        DB::statement("
            UPDATE people SET 
                full_name = convert_from(convert_to(full_name, 'UTF8'), 'UTF8'),
                position = convert_from(convert_to(position, 'UTF8'), 'UTF8'),
                address = convert_from(convert_to(address, 'UTF8'), 'UTF8'),
                status = convert_from(convert_to(status, 'UTF8'), 'UTF8')
            WHERE full_name IS NOT NULL
        ");
        
        DB::statement("
            UPDATE certificates SET 
                name = convert_from(convert_to(name, 'UTF8'), 'UTF8'),
                description = convert_from(convert_to(description, 'UTF8'), 'UTF8')
            WHERE name IS NOT NULL
        ");
        
        $this->info('PostgreSQL encoding fix completed!');
    }
} 