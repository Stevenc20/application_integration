<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CutOffService;
use Carbon\Carbon;

class ProcessShiftCutOff extends Command
{
    protected $signature = 'ppc:process-cutoff
                            {--date= : Force specific date (Y-m-d)}
                            {--shift= : Force specific shift (Pagi/Malam)}';

    protected $description = 'Process shift cut-off: create recovery items for unfinished jobs';

    public function handle(CutOffService $cutOffService): int
    {
        $now = Carbon::now();
        $forceShift = $this->option('shift');
        $forceDate = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        if ($forceShift) {
            $shiftName = 'Shift ' . ucfirst(strtolower($forceShift));
            $cutDate = $forceDate ? $forceDate->toDateString() : $now->toDateString();

            if (strtolower($forceShift) === 'malam') {
                $cutDate = $forceDate ? $forceDate->toDateString() : $now->copy()->subDay()->toDateString();
            }

            $this->info("Processing cut-off for {$shiftName} {$cutDate}");
            $stats = $cutOffService->processCutOff($cutDate, $shiftName);
            $this->info("Created {$stats['created']} recovery items, {$stats['skipped']} skipped.");
            return Command::SUCCESS;
        }

        // Auto-detect: cek config shift end DAN line_masters.production_end
        // Press C punya production_end = 22:00 (extended shift), jadi cutoff harus tunggu sampai 22:00
        $current = $now->format('H:i');
        $currentMins = (int) substr($current, 0, 2) * 60 + (int) substr($current, 3, 2);

        // Cari production_end terbesar dari semua line_masters (misal Press C = 22:00)
        $maxProductionEnd = \App\Models\LineMaster::where('status', 'active')
            ->whereNotNull('production_end')
            ->max('production_end');
        $maxEndMins = $maxProductionEnd ? \App\Models\MasterBreakTime::timeToMinutes($maxProductionEnd) : 0;

        $shifts = [
            'Shift Pagi' => [
                'date' => $now->toDateString(),
            ],
            'Shift Malam' => [
                'date' => $now->copy()->subDay()->toDateString(),
            ],
        ];

        foreach ($shifts as $shiftName => $info) {
            $config = config("shift.{$shiftName}");
            if (!$config) continue;

            $endTime = $config['end'];
            $endMins = \App\Models\MasterBreakTime::timeToMinutes($endTime);
            $windowEnd = $endMins + 15;

            // Gunakan waktu ter-late di antara config shift end dan production_end
            $effectiveEndMins = max($endMins, $maxEndMins);
            $effectiveEnd = \App\Models\MasterBreakTime::minutesToTime($effectiveEndMins);
            $effectiveWindowEnd = $effectiveEndMins + 15;

            if ($currentMins >= $effectiveEndMins && $currentMins < $effectiveWindowEnd) {
                $this->info("Processing cut-off for {$shiftName} {$info['date']} (effective end: {$effectiveEnd})");
                $stats = $cutOffService->processCutOff($info['date'], $shiftName);
                $this->info("Created {$stats['created']} recovery items.");
            }
        }

        return Command::SUCCESS;
    }
}
