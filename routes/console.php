<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\RotateMisassignedCertificates;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Явленная регистрация команды ротации сертификатов
Artisan::command('certificates:rotate-misassigned {--apply : Apply changes instead of dry-run}', function () {
    /** @var RotateMisassignedCertificates $cmd */
    $cmd = app(RotateMisassignedCertificates::class);
    return $cmd->handle();
})->purpose('Перекинуть сертификаты у людей со статусом "Не работающий" с поддержкой --apply');
