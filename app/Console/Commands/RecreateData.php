<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\People;
use App\Models\Certificate;
use App\Models\PeopleCertificate;
use Illuminate\Support\Facades\DB;

class RecreateData extends Command
{
    protected $signature = 'data:recreate';
    protected $description = 'Recreate data with correct encoding';

    public function handle()
    {
        $this->info('Starting data recreation...');
        
        // Сохраняем связи people_certificates
        $this->info('Backing up people_certificates...');
        $peopleCertificates = PeopleCertificate::all()->toArray();
        
        // Очищаем таблицы
        $this->info('Clearing tables...');
        PeopleCertificate::truncate();
        People::truncate();
        Certificate::truncate();
        
        // Пересоздаем данные с правильной кодировкой
        $this->recreatePeople();
        $this->recreateCertificates();
        $this->restorePeopleCertificates($peopleCertificates);
        
        $this->info('Data recreation completed!');
    }
    
    private function recreatePeople()
    {
        $this->info('Recreating people...');
        
        $peopleData = [
            [
                'full_name' => 'Шагеева Алсу Каюмовна',
                'position' => 'помощник руководителя',
                'phone' => '109-411-499-43',
                'snils' => null,
                'inn' => null,
                'birth_date' => null,
                'address' => null,
                'status' => null
            ],
            [
                'full_name' => 'Мордас Александр Александрович',
                'position' => 'прораб',
                'phone' => '186-767-625-40',
                'snils' => null,
                'inn' => null,
                'birth_date' => '1991-01-01',
                'address' => null,
                'status' => null
            ],
            [
                'full_name' => 'Егоров Андрей Федорович',
                'position' => 'Директор',
                'phone' => '115-101-377-92',
                'snils' => null,
                'inn' => null,
                'birth_date' => '1981-01-01',
                'address' => null,
                'status' => null
            ],
            [
                'full_name' => 'Зиннатуллин Раиль Равилевич',
                'position' => 'прораб',
                'phone' => '152-767-369-92',
                'snils' => null,
                'inn' => null,
                'birth_date' => null,
                'address' => null,
                'status' => null
            ],
            [
                'full_name' => 'John Doe',
                'position' => 'Engineer',
                'phone' => '+1234567890',
                'snils' => null,
                'inn' => null,
                'birth_date' => null,
                'address' => null,
                'status' => null
            ]
        ];
        
        foreach ($peopleData as $data) {
            People::create($data);
        }
    }
    
    private function recreateCertificates()
    {
        $this->info('Recreating certificates...');
        
        $certificatesData = [
            ['name' => 'А1', 'description' => 'Описание', 'expiry_date' => 5],
            ['name' => 'ВИТР (ОТ)', 'description' => 'Описание', 'expiry_date' => 1],
            ['name' => 'ПБО', 'description' => 'Описание', 'expiry_date' => 3],
            ['name' => 'АБГ', 'description' => 'Описание', 'expiry_date' => 3],
            ['name' => 'Первая Помощь', 'description' => 'Описание', 'expiry_date' => 3],
            ['name' => 'ПБИ', 'description' => 'Описание', 'expiry_date' => 3],
            ['name' => 'ЭБ', 'description' => 'Описание', 'expiry_date' => 1],
            ['name' => 'ВЛ (ОТ)', 'description' => 'Описание', 'expiry_date' => 3],
            ['name' => '5.9.3', 'description' => null, 'expiry_date' => 3],
            ['name' => '5.9.4', 'description' => null, 'expiry_date' => 3],
            ['name' => 'Люльки', 'description' => 'Описание', 'expiry_date' => 3],
            ['name' => '5.1.9', 'description' => null, 'expiry_date' => 3],
            ['name' => '5.1.10', 'description' => null, 'expiry_date' => 3],
            ['name' => '5.1.11', 'description' => null, 'expiry_date' => 3],
            ['name' => 'Высота(рабочая, 2гр)', 'description' => 'Описание', 'expiry_date' => 3],
            ['name' => 'Высота(ИТР, 3гр)', 'description' => 'Описание', 'expiry_date' => 5],
            ['name' => 'БСИЗ', 'description' => 'Описание', 'expiry_date' => 3]
        ];
        
        foreach ($certificatesData as $data) {
            Certificate::create($data);
        }
    }
    
    private function restorePeopleCertificates($peopleCertificates)
    {
        $this->info('Restoring people_certificates...');
        
        foreach ($peopleCertificates as $pc) {
            PeopleCertificate::create([
                'people_id' => $pc['people_id'],
                'certificate_id' => $pc['certificate_id'],
                'assigned_date' => $pc['assigned_date'] ?? null,
                'certificate_number' => $pc['certificate_number'] ?? null,
                'status' => $pc['status'] ?? null
            ]);
        }
    }
}
