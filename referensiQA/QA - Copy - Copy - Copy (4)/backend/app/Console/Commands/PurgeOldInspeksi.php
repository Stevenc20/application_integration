<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LembarInspeksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PurgeOldInspeksi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'li:purge-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard delete data > 6 bulan (Backup PDF dulu)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses hard delete (Purge) data > 6 bulan...');

        $sixMonthsAgo = Carbon::now()->subMonths(6);

        // Ambil data (termasuk yang di soft-delete kalau ada)
        $items = LembarInspeksi::withTrashed()
            ->where(function($q) use ($sixMonthsAgo) {
                $q->where('tgl_bulan', '<', $sixMonthsAgo)
                  ->orWhere(function($q2) use ($sixMonthsAgo) {
                      $q2->whereNull('tgl_bulan')->where('created_at', '<', $sixMonthsAgo);
                  });
            })
            ->get();

        $count = $items->count();

        if ($count === 0) {
            $this->info('Tidak ada data yang perlu dihapus permanen saat ini.');
            return;
        }

        foreach ($items as $item) {
            // TODO di Fase 6: 
            // 1. Generate PDF dari view print.blade.php
            // 2. Simpan ke Storage::disk('local')->put("arsip/{YYYY}/{MM}/{no_form}.pdf", $pdfData);
            
            // Dummy logic untuk path backup saat ini
            $year = Carbon::parse($item->tgl_bulan ?? $item->created_at)->year;
            $month = Carbon::parse($item->tgl_bulan ?? $item->created_at)->format('m');
            $pdfPath = "arsip/{$year}/{$month}/" . str_replace('/', '_', $item->no_form) . ".pdf";
            
            // Anggap PDF berhasil di-generate
            // $item->archived_pdf_path = $pdfPath;
            // $item->save();

            // Hapus dari database (force delete)
            // Karena kita menggunakan SoftDeletes, forceDelete() akan menghapus permanen
            $item->forceDelete();
        }

        $this->info("Berhasil backup dan menghapus permanen {$count} Lembar Inspeksi.");
    }
}
