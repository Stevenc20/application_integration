<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\LembarInspeksi;

return new class extends Migration
{
    /**
     * Re-generate semua no_form dengan format baru: LI-YYYY/MM-XXX
     * Dikelompokkan per bulan dari tgl_bulan, dinomori ulang secara berurutan.
     */
    public function up(): void
    {
        // Ambil semua LI, urut per tgl_bulan lalu created_at agar konsisten
        $items = LembarInspeksi::withTrashed()
            ->orderByRaw('YEAR(COALESCE(tgl_bulan, created_at)), MONTH(COALESCE(tgl_bulan, created_at)), created_at ASC')
            ->get(['id', 'tgl_bulan', 'created_at']);

        // Kelompokkan per YYYY/MM
        $grouped = [];
        foreach ($items as $item) {
            $tanggal = $item->tgl_bulan ?? $item->created_at;
            $key = \Carbon\Carbon::parse($tanggal)->format('Y/m');
            $grouped[$key][] = $item;
        }

        // Assign nomor urut per bulan → LI-2026/05-001
        foreach ($grouped as $yearMonth => $records) {
            foreach ($records as $index => $record) {
                $noForm = 'LI-' . $yearMonth . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                DB::table('lembar_inspeksi')
                    ->where('id', $record->id)
                    ->update(['no_form' => $noForm]);
            }
        }
    }

    /**
     * Rollback: kembalikan ke format lama LI/YYYY/MM/XXXX (per tahun)
     */
    public function down(): void
    {
        $items = LembarInspeksi::withTrashed()
            ->orderByRaw('YEAR(created_at), created_at ASC')
            ->get(['id', 'created_at']);

        $yearCount = [];
        foreach ($items as $item) {
            $year = \Carbon\Carbon::parse($item->created_at)->year;
            $yearCount[$year] = ($yearCount[$year] ?? 0) + 1;
            $noForm = 'LI/' . $item->created_at->format('Y/m') . '/' . str_pad($yearCount[$year], 4, '0', STR_PAD_LEFT);
            DB::table('lembar_inspeksi')
                ->where('id', $item->id)
                ->update(['no_form' => $noForm]);
        }
    }
};
