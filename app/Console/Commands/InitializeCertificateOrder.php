<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Certificate;
use App\Models\CertificateOrder;

class InitializeCertificateOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:init-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Инициализировать порядок сертификатов для существующих записей';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Инициализация порядка сертификатов...');
        
        // Получаем все сертификаты
        $certificates = Certificate::all();
        
        if ($certificates->isEmpty()) {
            $this->warn('Сертификаты не найдены.');
            return;
        }
        
        $this->info("Найдено сертификатов: {$certificates->count()}");
        
        // Создаем записи порядка для каждого сертификата
        $bar = $this->output->createProgressBar($certificates->count());
        $bar->start();
        
        foreach ($certificates as $index => $certificate) {
            CertificateOrder::updateOrCreate(
                ['certificate_id' => $certificate->id],
                ['sort_order' => $index + 1]
            );
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info('Порядок сертификатов успешно инициализирован!');
        
        // Показываем созданные записи
        $orders = CertificateOrder::with('certificate')->orderBy('sort_order')->get();
        $this->table(
            ['Порядок', 'ID', 'Название сертификата'],
            $orders->map(function ($order) {
                return [
                    $order->sort_order,
                    $order->certificate_id,
                    $order->certificate->name
                ];
            })
        );
    }
}
