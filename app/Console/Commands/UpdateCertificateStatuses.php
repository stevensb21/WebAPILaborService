<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PeopleCertificate;
use Carbon\Carbon;

class UpdateCertificateStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update certificate statuses based on assigned dates and expiry periods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating certificate statuses...');
        
        $updatedCount = 0;
        
        PeopleCertificate::with('certificate')->get()->each(function($pc) use (&$updatedCount) {
            $assignedDate = Carbon::parse($pc->assigned_date);
            $expiryDate = $assignedDate->copy()->addYears($pc->certificate->expiry_date ?: 1);
            
            $isExpired = $expiryDate->isPast();
            $isExpiringSoon = now()->diffInDays($expiryDate, false) <= 60 && now()->diffInDays($expiryDate, false) > 0;
            
            if ($isExpired) {
                $status = 2; // Просрочен
            } elseif ($isExpiringSoon) {
                $status = 3; // Скоро просрочится
            } else {
                $status = 4; // Действует
            }
            
            if ($pc->status != $status) {
                $pc->update(['status' => $status]);
                $this->line("Updated {$pc->certificate->name}: status={$status} (expires: {$expiryDate->format('Y-m-d')})");
                $updatedCount++;
            }
        });
        
        $this->info("Updated {$updatedCount} certificates.");
    }
}
