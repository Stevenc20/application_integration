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
}
