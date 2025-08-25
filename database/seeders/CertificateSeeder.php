<?php

namespace Database\Seeders;

use App\Models\Certificate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CertificateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $certificates = [
            [
                'name' => 'АБГ',
                'description' => 'Аттестация по безопасности газового хозяйства',
                'expiry_date' => Carbon::now()->addYears(3),
            ],
            [
                'name' => 'вИТР(ОТ)',
                'description' => 'Вводный инструктаж для инженерно-технических работников по охране труда',
                'expiry_date' => Carbon::now()->addYear(),
            ],
            [
                'name' => 'Первая помощь',
                'description' => 'Обучение оказанию первой помощи пострадавшим',
                'expiry_date' => Carbon::now()->addYears(3),
            ],
        ];

        foreach ($certificates as $certificate) {
            Certificate::create($certificate);
        }
    }
}
