<?php

use Carbon\Carbon;

if (! function_exists('user_now')) {
    /**
     * Return current Carbon timestamp in the authenticated user's timezone.
     */
    function user_now(): Carbon
    {
        return Carbon::now('Asia/Jakarta');
    }
}

if (! function_exists('fmt_qty')) {
    /**
     * Format quantity: whole numbers show without decimals,
     * fractional values show with trailing zeros stripped (up to $maxDecimals places).
     * e.g. 300 → "300", 3.25 → "3.25", 3.5 → "3.5"
     */
    function fmt_qty($value, int $maxDecimals = 3): string
    {
        $value = (float) $value;
        if (fmod($value, 1) === 0.0) {
            return number_format((int) $value);
        }
        $formatted = number_format($value, $maxDecimals, '.', ',');
        return rtrim(rtrim($formatted, '0'), '.');
    }
}

if (! function_exists('user_tz_label')) {
    /**
     * Return timezone label.
     */
    function user_tz_label(): string
    {
        return 'WIB';
    }
}
