<?php

namespace App\Support;

use App\Models\LineMaster;

class SignatureScopeNormalizer
{
    public static function normalizeLine(string $line): string
    {
        return strtoupper(trim(str_replace(['LINE', 'PRESS', ' '], '', strtoupper($line))));
    }

    public static function normalizeShift(string $shift): string
    {
        $norm = strtoupper(trim($shift));
        if (str_contains($norm, 'PAGI') || $norm === '1' || $norm === 'S1' || $norm === 'SHIFT-1' || str_contains($norm, 'SHIFT 1')) {
            return '1';
        }
        if (str_contains($norm, 'MALAM') || $norm === '2' || $norm === 'S2' || $norm === 'SHIFT-2' || str_contains($norm, 'SHIFT 2')) {
            return '2';
        }
        if (str_contains($norm, 'NON') || $norm === '3') {
            return '3';
        }

        return $norm;
    }

    public static function standardLine(string $rawLine): string
    {
        if ($rawLine === '') {
            return 'Line A';
        }

        $norm = self::normalizeLine($rawLine);
        foreach (LineMaster::pluck('line_name')->unique() as $master) {
            if (self::normalizeLine((string) $master) === $norm) {
                return $master;
            }
        }

        return $rawLine;
    }

    public static function standardShift(string $rawShift): string
    {
        if ($rawShift === '') {
            return 'Shift Pagi';
        }

        $norm = self::normalizeShift($rawShift);
        if ($norm === '1') {
            return 'Shift Pagi';
        }
        if ($norm === '2') {
            return 'Shift Malam';
        }
        if ($norm === '3') {
            return 'Non-Shift';
        }

        return $rawShift;
    }
}