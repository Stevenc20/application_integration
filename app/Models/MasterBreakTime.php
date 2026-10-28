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
}
