<?php

namespace Database\Seeders;

use App\Models\People;
use App\Models\Certificate;
use App\Models\PeopleCertificate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PeopleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем людей
        $people = [
            [
                'full_name' => 'Иванов Иван Иванович',
                'position' => 'Инженер по охране труда',
                'phone' => '+7 (999) 123-45-67',
                'snils' => '123-456-789 01',
                'inn' => '123456789012',
                'birth_date' => '1985-03-15',
                'address' => 'г. Москва, ул. Ленина, д. 1, кв. 5',
            ],
            [
                'full_name' => 'Петрова Анна Сергеевна',
                'position' => 'Мастер участка',
                'phone' => '+7 (999) 987-65-43',
                'snils' => '987-654-321 02',
                'inn' => '987654321098',
                'birth_date' => '1990-07-22',
                'address' => 'г. Москва, ул. Пушкина, д. 10, кв. 15',
            ],
        ];

        $createdPeople = [];
        foreach ($people as $personData) {
            $createdPeople[] = People::create($personData);
        }

        // Получаем сертификаты
        $certificates = Certificate::all();

        // Создаем связи между людьми и сертификатами
        if (count($createdPeople) >= 2 && count($certificates) >= 3) {
            // Первый человек получает один сертификат (АБГ)
            PeopleCertificate::create([
                'people_id' => $createdPeople[0]->id,
                'certificate_id' => $certificates->where('name', 'АБГ')->first()->id,
                'assigned_date' => Carbon::now()->subMonths(6),
                'certificate_number' => 'АБГ-2024-001',
                'status' => 1, // Активный
                'notes' => 'Сертификат получен после успешного прохождения обучения',
            ]);

            // Второй человек получает два сертификата (вИТР(ОТ) и Первая помощь)
            PeopleCertificate::create([
                'people_id' => $createdPeople[1]->id,
                'certificate_id' => $certificates->where('name', 'вИТР(ОТ)')->first()->id,
                'assigned_date' => Carbon::now()->subMonths(3),
                'certificate_number' => 'ВИТР-2024-002',
                'status' => 1, // Активный
                'notes' => 'Инструктаж пройден успешно',
            ]);

            PeopleCertificate::create([
                'people_id' => $createdPeople[1]->id,
                'certificate_id' => $certificates->where('name', 'Первая помощь')->first()->id,
                'assigned_date' => Carbon::now()->subMonths(1),
                'certificate_number' => 'ПП-2024-003',
                'status' => 1, // Активный
                'notes' => 'Курс первой помощи пройден с отличием',
            ]);
        }
    }
}
