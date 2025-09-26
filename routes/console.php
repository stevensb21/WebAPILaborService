<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\People;
use App\Models\Certificate;
use App\Models\PeopleCertificate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan as ArtisanFacade;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Явленная регистрация команды ротации сертификатов (closure-реализация)
Artisan::command('certificates:rotate-misassigned {--apply : Apply changes instead of dry-run}', function () {
    $apply = (bool) $this->option('apply');

    $nameA = 'ОПП ИТР';
    $nameB = 'Высота(рабочая, 2гр)';
    $nameC = 'Люльки';

    $certA = Certificate::where('name', $nameA)->first();
    $certB = Certificate::where('name', $nameB)->first();
    $certC = Certificate::where('name', $nameC)->first();

    if (!$certA || !$certB || !$certC) {
        $this->error('Не найдены нужные сертификаты');
        return 1;
    }

    $map = [
        $certA->id => $certB->id,
        $certB->id => $certC->id,
        $certC->id => $certA->id,
    ];

    $this->info('Начинаю ' . ($apply ? 'ПРИМЕНЕНИЕ' : 'DRY-RUN') . ' ротации сертификатов для статуса "Не работающий"');

    $people = People::whereRaw("TRIM(REPLACE(REPLACE(status, '\n', ''), '\r', '')) = ?", ['Не работающий'])->get(['id','full_name']);

    $affectedPeople = 0;
    $affectedLinks = 0;

    foreach ($people as $person) {
        $links = PeopleCertificate::where('people_id', $person->id)
            ->whereIn('certificate_id', array_keys($map))
            ->orderBy('certificate_id')
            ->get();

        if ($links->isEmpty()) continue;
        $affectedPeople++;

        DB::transaction(function () use ($links, $map, $apply, $person, &$affectedLinks) {
            $byCert = $links->keyBy('certificate_id');
            foreach ($byCert as $srcCertId => $pc) {
                $dstCertId = $map[$srcCertId] ?? null;
                if (!$dstCertId) continue;

                if ($apply) {
                    $existingDst = PeopleCertificate::where('people_id', $pc->people_id)
                        ->where('certificate_id', $dstCertId)
                        ->first();
                    if ($existingDst) {
                        PeopleCertificate::where('id', $existingDst->id)->delete();
                    }
                    PeopleCertificate::where('id', $pc->id)->update(['certificate_id' => $dstCertId]);
                }
                $affectedLinks++;
                $this->line(($apply ? '[APPLY] ' : '[DRY] ') . sprintf('Person #%d: %s | %d → %d', $person->id, $person->full_name, $srcCertId, $dstCertId));
            }
        });
    }

    $this->info("Итого людей затронуто: {$affectedPeople}, связей изменено: {$affectedLinks}");

    if ($apply) {
        try {
            Cache::flush();
            ArtisanFacade::call('view:clear');
            ArtisanFacade::call('route:clear');
            ArtisanFacade::call('config:clear');
        } catch (\Throwable $e) {
            $this->warn('Ошибка очистки кэша: ' . $e->getMessage());
        }
        $this->info('Кэш очищен.');
    } else {
        $this->info('Это был dry-run. Для применения добавьте флаг --apply');
    }

    return 0;
})->purpose('Перекинуть сертификаты у людей со статусом "Не работающий" с поддержкой --apply');

// Прямая команда без опций: сразу применяет изменения
Artisan::command('fix:rotate-apply', function () {
    $apply = true;

    $nameA = 'ОПП ИТР';
    $nameB = 'Высота(рабочая, 2гр)';
    $nameC = 'Люльки';

    $certA = Certificate::where('name', $nameA)->first();
    $certB = Certificate::where('name', $nameB)->first();
    $certC = Certificate::where('name', $nameC)->first();

    if (!$certA || !$certB || !$certC) {
        $this->error('Не найдены нужные сертификаты');
        return 1;
    }

    $map = [
        $certA->id => $certB->id,
        $certB->id => $certC->id,
        $certC->id => $certA->id,
    ];

    $this->info('Начинаю ПРИМЕНЕНИЕ ротации сертификатов для статуса "Не работающий"');

    $people = People::whereRaw("TRIM(REPLACE(REPLACE(status, '\n', ''), '\r', '')) = ?", ['Не работающий'])->get(['id','full_name']);

    $affectedPeople = 0;
    $affectedLinks = 0;

    foreach ($people as $person) {
        $links = PeopleCertificate::where('people_id', $person->id)
            ->whereIn('certificate_id', array_keys($map))
            ->orderBy('certificate_id')
            ->get();

        if ($links->isEmpty()) continue;
        $affectedPeople++;

        DB::transaction(function () use ($links, $map, $person, &$affectedLinks) {
            $byCert = $links->keyBy('certificate_id');
            foreach ($byCert as $srcCertId => $pc) {
                $dstCertId = $map[$srcCertId] ?? null;
                if (!$dstCertId) continue;

                $existingDst = PeopleCertificate::where('people_id', $pc->people_id)
                    ->where('certificate_id', $dstCertId)
                    ->first();
                if ($existingDst) {
                    PeopleCertificate::where('id', $existingDst->id)->delete();
                }
                PeopleCertificate::where('id', $pc->id)->update(['certificate_id' => $dstCertId]);

                $affectedLinks++;
                $this->line('[APPLY] ' . sprintf('Person #%d: %s | %d → %d', $person->id, $person->full_name, $srcCertId, $dstCertId));
            }
        });
    }

    $this->info("Итого людей затронуто: {$affectedPeople}, связей изменено: {$affectedLinks}");

    try {
        Cache::flush();
        ArtisanFacade::call('view:clear');
        ArtisanFacade::call('route:clear');
        ArtisanFacade::call('config:clear');
    } catch (\Throwable $e) {
        $this->warn('Ошибка очистки кэша: ' . $e->getMessage());
    }
    $this->info('Кэш очищен.');

    return 0;
})->purpose('Применить ротацию сертификатов без опций');
