<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\People;
use App\Models\Certificate;
use App\Models\PeopleCertificate;

class RotateMisassignedCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --apply  Применить изменения (по умолчанию выполняется dry-run)
     *
     * @var string
     */
    protected $signature = 'certificates:rotate-misassigned {--apply : Apply changes instead of dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Перекинуть сертификаты у людей со статусом "Не работающий": ОПП ИТР → Высота(рабочая, 2гр) → Люльки → ОПП ИТР';

    public function handle(): int
    {
        $apply = (bool)$this->option('apply');

        // Ищем ID целевых сертификатов по названию
        $nameA = 'ОПП ИТР';
        $nameB = 'Высота(рабочая, 2гр)';
        $nameC = 'Люльки';

        $certA = Certificate::where('name', $nameA)->first();
        $certB = Certificate::where('name', $nameB)->first();
        $certC = Certificate::where('name', $nameC)->first();

        if (!$certA || !$certB || !$certC) {
            $this->error('Не найдены нужные сертификаты:');
            $this->line("{$nameA}: " . ($certA?->id ?? 'нет'));
            $this->line("{$nameB}: " . ($certB?->id ?? 'нет'));
            $this->line("{$nameC}: " . ($certC?->id ?? 'нет'));
            return self::FAILURE;
        }

        $map = [
            $certA->id => $certB->id, // ОПП ИТР → Высота(рабочая, 2гр)
            $certB->id => $certC->id, // Высота(рабочая, 2гр) → Люльки
            $certC->id => $certA->id, // Люльки → ОПП ИТР
        ];

        $this->info('Начинаю ' . ($apply ? 'ПРИМЕНЕНИЕ' : 'DRY-RUN') . ' ротации сертификатов для статуса "Не работающий"');

        // Берем всех людей со статусом "Не работающий" (убираем лишние пробелы/табуляции)
        $people = People::whereRaw("TRIM(REPLACE(REPLACE(status, '\n', ''), '\r', '')) = ?", ['Не работающий'])->get(['id','full_name']);

        $affectedPeople = 0;
        $affectedLinks = 0;

        foreach ($people as $person) {
            // Выбираем все связи по трем интересующим сертификатам
            $links = PeopleCertificate::where('people_id', $person->id)
                ->whereIn('certificate_id', array_keys($map))
                ->orderBy('certificate_id')
                ->get();

            if ($links->isEmpty()) {
                continue;
            }

            $affectedPeople++;

            // Проводим ротацию внутри транзакции, чтобы избежать конфликтов
            DB::transaction(function () use ($links, $map, $apply, $person, &$affectedLinks) {
                // Карта certificate_id -> запись
                $byCert = $links->keyBy('certificate_id');

                foreach ($byCert as $srcCertId => $pc) {
                    $dstCertId = $map[$srcCertId] ?? null;
                    if (!$dstCertId) {
                        continue;
                    }

                    $msg = sprintf('Person #%d: %s | %d → %d', $person->id, $person->full_name, $srcCertId, $dstCertId);

                    if ($apply) {
                        // Если уже существует запись с целевым сертификатом для этого человека,
                        // удаляем её, чтобы не нарушить уникальный индекс (people_id, certificate_id)
                        $existingDst = PeopleCertificate::where('people_id', $pc->people_id)
                            ->where('certificate_id', $dstCertId)
                            ->first();
                        if ($existingDst) {
                            // При желании можно переносить данные, сейчас — просто удаляем дубликат
                            PeopleCertificate::where('id', $existingDst->id)->delete();
                        }

                        PeopleCertificate::where('id', $pc->id)->update(['certificate_id' => $dstCertId]);
                    }

                    $affectedLinks++;
                    $this->line(($apply ? '[APPLY] ' : '[DRY] ') . $msg);
                }
            });
        }

        $this->info("Итого людей затронуто: {$affectedPeople}, связей изменено: {$affectedLinks}");

        if ($apply) {
            // Очищаем кэш после изменений
            try {
                Cache::flush();
                Artisan::call('view:clear');
                Artisan::call('route:clear');
                Artisan::call('config:clear');
            } catch (\Throwable $e) {
                $this->warn('Ошибка очистки кэша: ' . $e->getMessage());
            }
            $this->info('Кэш очищен.');
        } else {
            $this->info('Это был dry-run. Для применения добавьте флаг --apply');
        }

        return self::SUCCESS;
    }
}


