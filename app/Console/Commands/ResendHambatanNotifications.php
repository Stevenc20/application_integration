<?php

namespace App\Console\Commands;

use App\Http\Controllers\HambatanJalurController;
use App\Models\HambatanJalur;
use Illuminate\Console\Command;

class ResendHambatanNotifications extends Command
{
    protected $signature = 'hambatan:resend-notifications';
    protected $description = 'Kirim ulang notifikasi leader untuk semua LHJ yang sudah pic_signed';

    public function handle(): void
    {
        $controller = app(HambatanJalurController::class);
        $items = HambatanJalur::where('status', 'pic_signed')->get();
        $total = $items->count();
        $sent = 0;

        $this->info("Menemukan {$total} LHJ dengan status pic_signed...");

        foreach ($items as $item) {
            if ($controller->notifyLineLeader($item)) {
                $sent++;
                $this->line("  [OK] LHJ #{$item->id} - {$item->line_name}");
            } else {
                $this->warn("  [SKIP] LHJ #{$item->id} - {$item->line_name} (no leader/foreman)");
            }
        }

        $this->info("Selesai. Notifikasi terkirim: {$sent}/{$total}");
    }
}
