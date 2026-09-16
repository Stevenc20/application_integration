<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LembarInspeksi;
use Carbon\Carbon;

class ArchiveOldInspeksi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'li:archive-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Soft archive Lembar Inspeksi yang umurnya > 2 bulan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses soft archive untuk Lembar Inspeksi...');

        // Cari data yang tgl_bulan (atau created_at) < 2 bulan lalu
        $twoMonthsAgo = Carbon::now()->subMonths(2);

        $items = LembarInspeksi::whereNull('archived_at')
            ->where(function($q) use ($twoMonthsAgo) {
                $q->where('tgl_bulan', '<', $twoMonthsAgo)
                  ->orWhere(function($q2) use ($twoMonthsAgo) {
                      $q2->whereNull('tgl_bulan')->where('created_at', '<', $twoMonthsAgo);
                  });
            })
            ->get();

        $count = $items->count();

        if ($count === 0) {
            $this->info('Tidak ada data yang perlu diarsipkan saat ini.');
            return;
        }

        foreach ($items as $item) {
            $item->archived_at = now();
            $item->archive_reason = 'Auto archive setelah 2 bulan';
            // Hindari trigger event updated_at standar jika mau, atau biarkan saja
            $item->save();
        }

        $this->info("Berhasil mengarsipkan {$count} Lembar Inspeksi.");
    }
}
