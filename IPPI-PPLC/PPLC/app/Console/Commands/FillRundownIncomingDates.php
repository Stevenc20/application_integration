<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RundownIncoming;
use Carbon\Carbon;

class FillRundownIncomingDates extends Command
{
    protected $signature   = 'rundownincoming:fill-dates {--from= : Start date Y-m-d} {--to= : End date Y-m-d}';
    protected $description = 'Carry-over data rundown incoming ke semua tanggal yang kosong';

    // Format bulan Indonesia
    private $months = [
        1  => 'JANUARI',  2  => 'FEBRUARI', 3  => 'MARET',
        4  => 'APRIL',    5  => 'MEI',       6  => 'JUNI',
        7  => 'JULI',     8  => 'AGUSTUS',   9  => 'SEPTEMBER',
        10 => 'OKTOBER',  11 => 'NOVEMBER',  12 => 'DESEMBER',
    ];

    // Peta semua typo ke nama yang benar
    private $typoMap = [
        'APRL'     => 'APRIL',
        'APRRIL'   => 'APRIL',
        'FEBUARI'  => 'FEBRUARI',
        'PEBRUARI' => 'FEBRUARI',
        'NOPEMBER' => 'NOVEMBER',
        'DESEMBER' => 'DESEMBER',
    ];

    public function handle()
    {
        // === STEP 1: Fix typo bulan ===
        $this->info('🔧 Memperbaiki typo nama bulan...');
        $fixed = 0;
        foreach ($this->typoMap as $wrong => $correct) {
            $rows = RundownIncoming::where('sheet_date', 'LIKE', "% {$wrong}")->get();
            foreach ($rows as $row) {
                $correctDate = str_replace(" {$wrong}", " {$correct}", $row->sheet_date);
                // Pastikan tidak duplikat
                $exists = RundownIncoming::where('sheet_date', $correctDate)
                    ->where('job_no', $row->job_no)->exists();
                if (!$exists) {
                    RundownIncoming::where('id', $row->id)->update(['sheet_date' => $correctDate]);
                    $fixed++;
                } else {
                    $row->delete();
                }
            }
        }
        $this->info("   ✓ {$fixed} baris diperbaiki.");

        // === STEP 2: Tentukan rentang tanggal ===
        $allSheets = RundownIncoming::distinct()->orderBy('sheet_date')->pluck('sheet_date');
        if ($allSheets->isEmpty()) {
            $this->error('Tidak ada data di database!');
            return 1;
        }

        // Konversi sheet_date ke Carbon
        $parsed = $this->parseSheetDates($allSheets->toArray());
        if (empty($parsed)) {
            $this->error('Tidak dapat mem-parse format tanggal dari database.');
            return 1;
        }

        $fromOption = $this->option('from');
        $toOption   = $this->option('to');

        $startDate = $fromOption ? Carbon::parse($fromOption) : min($parsed);
        $endDate   = $toOption   ? Carbon::parse($toOption)   : Carbon::now()->endOfMonth();

        $this->info("📅 Rentang: {$startDate->format('d M Y')} → {$endDate->format('d M Y')}");

        // Kumpulkan semua sheet_date yang sudah ada
        $existingSheets = RundownIncoming::distinct()->pluck('sheet_date')->mapWithKeys(function($s) {
            return [$s => true];
        })->toArray();

        // === STEP 3: Loop setiap hari, isi yang kosong ===
        $current = $startDate->copy();
        $filled  = 0;

        while ($current->lte($endDate)) {
            $sheetDate = $this->toSheetDate($current);

            if (!isset($existingSheets[$sheetDate])) {
                // Cari tanggal terakhir sebelum ini yang ada datanya
                $prevDate = $this->findPreviousSheetDate($current, $existingSheets);

                if ($prevDate) {
                    $this->line("   ↪ Isi {$sheetDate} dari {$prevDate}");
                    $this->carryOver($prevDate, $sheetDate);
                    $existingSheets[$sheetDate] = true;
                    $filled++;
                }
            }

            $current->addDay();
        }

        $this->info("✅ Selesai! {$filled} tanggal baru berhasil diisi.");
        return 0;
    }

    private function toSheetDate(Carbon $date): string
    {
        return $date->format('d') . ' ' . $this->months[$date->month];
    }

    private function parseSheetDates(array $sheets): array
    {
        $parsed = [];
        $monthMap = array_flip($this->months);
        foreach ($sheets as $s) {
            $parts = explode(' ', trim($s));
            if (count($parts) < 2) continue;
            $day = (int) $parts[0];
            $monthName = strtoupper($parts[1]);
            if (!isset($monthMap[$monthName])) continue;
            $month = $monthMap[$monthName];
            $year  = Carbon::now()->year;
            if ($month > Carbon::now()->month) $year--;
            try {
                $parsed[] = Carbon::create($year, $month, $day);
            } catch (\Exception $e) {}
        }
        return $parsed;
    }

    private function findPreviousSheetDate(Carbon $date, array $existing): ?string
    {
        $monthMap = array_flip($this->months);
        $check = $date->copy()->subDay();
        for ($i = 0; $i < 60; $i++) {
            $candidate = $this->toSheetDate($check);
            if (isset($existing[$candidate])) return $candidate;
            $check->subDay();
        }
        return null;
    }

    private function carryOver(string $fromSheet, string $toSheet): void
    {
        $oldItems = RundownIncoming::where('sheet_date', $fromSheet)->get();
        if ($oldItems->isEmpty()) return;

        $now      = now();
        $newItems = [];

        foreach ($oldItems as $item) {
            $stockAwal = $item->stok_akhir;
            $stokAkhir = $stockAwal;
            $strength  = $item->pcs_day > 0 ? round($stokAkhir / $item->pcs_day, 4) : 0;
            $status    = $strength <= 1.5 ? 'MINIM' : ($strength >= 3 ? 'OVER' : 'STANDAR');
            $category  = ($item->cycle_issue >= 3) ? 'FAST MOVING' : 'SLOW MOVING';

            $newItems[] = [
                'sheet_date' => $toSheet,
                'no'         => $item->no,
                'job_no'     => $item->job_no,
                'vendor'     => $item->vendor,
                'finish_part'=> $item->finish_part,
                'customer'   => $item->customer,
                'price_pc'   => $item->price_pc,
                'status'     => $status,
                'category'   => $category,
                'cycle_issue'=> $item->cycle_issue,
                'stock_awal' => $stockAwal,
                'assy'       => 0,
                'delivery'   => $item->delivery ?? '',
                'incoming'   => 0,
                'stok_akhir' => $stokAkhir,
                'all_price'  => $item->price_pc * $stokAkhir,
                'pcs_day'    => $item->pcs_day,
                'strength'   => $strength,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($newItems)) {
            RundownIncoming::insert($newItems);
        }
    }
}
