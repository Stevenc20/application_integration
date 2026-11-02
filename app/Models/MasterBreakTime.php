<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterBreakTime extends Model
{
    protected $fillable = [
        'hari',
        'waktu_mulai',
        'waktu_selesai',
        'type',
        'label',
        'shift',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function durationMinutes(): int
    {
        $start = self::timeToMinutes($this->waktu_mulai);
        $end = self::timeToMinutes($this->waktu_selesai);

        return max(0, $end - $start);
    }

    public static function timeToMinutes(mixed $time): int
    {
        if (!$time) {
            return 0;
        }

        $str = is_string($time) ? $time : (string) $time;
        $str = substr($str, 0, 5);
        $parts = explode(':', $str);

        if (count($parts) < 2) {
            return 0;
        }

        return ((int) $parts[0]) * 60 + (int) $parts[1];
    }

    public static function minutesToTime(int $mins): string
    {
        $mins = $mins % (24 * 60);
        $h = intdiv($mins, 60);
        $m = $mins % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Resolve applicable break schedules for a given date and shift name.
     * This is the single source of truth used by PPC, Input Harian, and Background schedulers.
     */
    public static function getApplicableBreaks(string $date, string $shiftName, ?string $hari = null): \Illuminate\Database\Eloquent\Collection
    {
        $dayKey = self::resolveDayKey($date, $hari);
        $normalizedShift = self::normalizeShiftKey($shiftName);

        return self::where('is_active', true)
            ->where(function ($q) use ($dayKey) {
                $q->where('hari', $dayKey)->orWhere('hari', 'semua');
            })
            ->where(function ($q) use ($normalizedShift) {
                $q->whereNull('shift')
                  ->orWhere('shift', '')
                  ->orWhere('shift', $normalizedShift)
                  ->orWhere('shift', 'LIKE', '%' . $normalizedShift . '%');
            })
            ->orderBy('sort_order')
            ->orderBy('waktu_mulai')
            ->get();
    }

    public static function resolveDayKey(string $date, ?string $hari = null): string
    {
        if ($hari) {
            return self::normalizeDayName($hari);
        }

        $carbonDay = strtolower(\Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd'));
        return self::normalizeDayName($carbonDay);
    }

    public static function normalizeDayName(string $day): string
    {
        $normalized = strtolower(trim($day));
        $normalized = preg_replace('/\s+(pagi|malam|shift\s*(pagi|malam)?)$/i', '', $normalized);
        $enToId = [
            'monday' => 'senin', 'tuesday' => 'selasa', 'wednesday' => 'rabu',
            'thursday' => 'kamis', 'friday' => 'jumat', 'saturday' => 'sabtu', 'sunday' => 'minggu',
        ];
        if (isset($enToId[$normalized])) {
            return $enToId[$normalized];
        }
        if (in_array($normalized, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu', 'semua'], true)) {
            return $normalized;
        }
        return $normalized;
    }

    public static function normalizeShiftKey(string $shiftName): string
    {
        $s = strtolower(trim($shiftName));
        if (str_contains($s, 'malam') || str_contains($s, '2')) {
            return 'Shift Malam';
        }
        return 'Shift Pagi';
    }

    /**
     * Get break schedule with override priority from ProductionPlan (uploaded PPC schedule).
     * If PPC uploaded specific break rows for this date, shift, and line, use those.
     * Otherwise, fallback to MasterBreakTime schedule.
     */
    public static function getBreaksWithPpcOverride(string $date, string $shiftName, ?string $line = null): \Illuminate\Support\Collection
    {
        $normalizedShift = self::normalizeShiftKey($shiftName);

        // Check if there are break rows in production_plans for this date and shift
        $planBreakQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'break')
            ->where(function($q) use ($normalizedShift, $shiftName) {
                $q->where('shift_name', $normalizedShift)
                  ->orWhere('shift_name', 'LIKE', '%' . $normalizedShift . '%')
                  ->orWhere('shift_name', 'LIKE', '%' . $shiftName . '%');
            })
            ->whereNotNull('start_time')
            ->whereNotNull('finish_time');

        if ($line && strtoupper($line) !== 'ALL') {
            $normLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));
            $planBreakQuery->where(function($lq) use ($normLine, $line) {
                $lq->whereRaw("UPPER(TRIM(press_name)) = ?", [strtoupper(trim($line))])
                   ->orWhereRaw("UPPER(press_name) LIKE ?", ["%{$normLine}%"])
                   ->orWhereRaw("REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normLine}%"]);
            });
        }

        $planBreaks = $planBreakQuery->orderBy('start_time')->get();

        if ($planBreaks->isNotEmpty()) {
            return $planBreaks->map(function($pb) {
                $start = substr((string)$pb->start_time, 0, 5);
                $end = substr((string)$pb->finish_time, 0, 5);
                $label = trim((string)($pb->job_master ?: ($pb->job_no ?: 'Istirahat PPC')));
                return (object)[
                    'label' => $label,
                    'type' => 'break',
                    'waktu_mulai' => $start,
                    'waktu_selesai' => $end,
                    'start' => $start,
                    'end' => $end,
                    'startMin' => self::timeToMinutes($start),
                    'endMin' => self::timeToMinutes($end),
                    'source' => 'ppc_plan',
                ];
            });
        }

        // Fallback to MasterBreakTime database
        return self::getApplicableBreaks($date, $shiftName)->map(function($b) {
            $start = substr((string)$b->waktu_mulai, 0, 5);
            $end = substr((string)$b->waktu_selesai, 0, 5);
            return (object)[
                'label' => $b->label,
                'type' => $b->type,
                'waktu_mulai' => $start,
                'waktu_selesai' => $end,
                'start' => $start,
                'end' => $end,
                'startMin' => self::timeToMinutes($start),
                'endMin' => self::timeToMinutes($end),
                'source' => 'master',
            ];
        });
    }
}
