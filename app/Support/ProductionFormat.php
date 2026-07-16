<?php

namespace App\Support;

/**
 * Global number formatting for manufacturing tables (PPC, Input Harian, LKH).
 */
class ProductionFormat
{
    public static function qty(mixed $value): string
    {
        return number_format((int) round((float) ($value ?? 0)), 0, '.', ',');
    }

    public static function minutes(mixed $value): string
    {
        $totalSeconds = (int) round(abs((float) ($value ?? 0)) * 60);
        $m = intdiv($totalSeconds, 60);
        $s = $totalSeconds % 60;
        return sprintf('%d.%02d', $m, $s);
    }

    public static function ct(mixed $value): string
    {
        $v = (float) ($value ?? 0);
        if ($v <= 0) {
            return '-';
        }

        return number_format($v, 1, '.', ',');
    }

    public static function gsph(mixed $value): string
    {
        return number_format((int) ($value ?? 0), 0, '.', ',');
    }

    public static function percent(mixed $value, int $decimals = 1): string
    {
        return number_format((float) ($value ?? 0), $decimals, '.', ',');
    }
}
