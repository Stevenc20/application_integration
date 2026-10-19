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

    /**
     * Determine if a user is authorized for a specific line, shift, and role.
     */
    public static function canUserAccessScope(?\App\Models\User $user, string $lineName, string $shiftName, ?string $sigRole = null, ?string $workDate = null): bool
    {
        if (!$user) {
            return false;
        }

        $userRole = strtolower((string) $user->role);

        // Superadmin, Foreman, Supervisor, Management always have broad access
        if (in_array($userRole, ['superadmin', 'foreman', 'supervisor', 'manager', 'kadiv', 'direktur'])) {
            return true;
        }

        $isLeaderRole = str_starts_with($userRole, 'leader') || in_array($userRole, ['leader', 'teamleader', 'group leader']);
        if (!$isLeaderRole) {
            return false;
        }

        $targetLine = self::normalizeLine($lineName);
        $targetShift = self::normalizeShift($shiftName);

        // 1. Direct LineAssignment match
        $assignments = \App\Models\LineAssignment::where('leader_user_id', $user->id)->get();
        if ($assignments->isNotEmpty()) {
            foreach ($assignments as $assignment) {
                $assignLine = self::normalizeLine((string) $assignment->line_name);
                $assignShiftRaw = $assignment->shift_name ?? '';
                $assignShift = $assignShiftRaw ? self::normalizeShift($assignShiftRaw) : '';

                if ($assignLine === $targetLine && ($assignShift === '' || $assignShift === $targetShift)) {
                    return true;
                }
            }
        }

        // 2. ShiftSubmission submitter match (User locked / submitted this shift)
        $lineMasterIds = \App\Models\LineMaster::all()->filter(function ($lm) use ($targetLine) {
            return SignatureScopeNormalizer::normalizeLine((string) $lm->line_name) === $targetLine;
        })->pluck('id');

        $submissionQuery = \App\Models\ShiftSubmission::whereIn('line_id', $lineMasterIds)
            ->where('submitted_by', $user->id)
            ->whereNull('cancelled_at');

        if ($workDate) {
            $submissionQuery->whereDate('work_date', $workDate);
        }
        $numericShift = (int) $targetShift;
        if ($numericShift > 0) {
            $submissionQuery->where('shift', $numericShift);
        }

        if ($submissionQuery->exists()) {
            return true;
        }

        // 3. Line-specific role match (e.g. user role 'leader a' matching Line A, or 'leader b' matching Line B)
        $normalizedLineLower = strtolower($targetLine);
        if ($normalizedLineLower !== '' && str_contains($userRole, $normalizedLineLower)) {
            return true;
        }

        // 4. Default / General Leader fallback: If the line doesn't have an exclusive leader assignment to another leader
        $lineHasOtherAssignment = \App\Models\LineAssignment::whereNotNull('leader_user_id')
            ->where('leader_user_id', '!=', $user->id)
            ->get()
            ->contains(function ($a) use ($targetLine, $targetShift) {
                $aLine = SignatureScopeNormalizer::normalizeLine((string) $a->line_name);
                $aShift = $a->shift_name ? SignatureScopeNormalizer::normalizeShift((string) $a->shift_name) : '';
                return $aLine === $targetLine && ($aShift === '' || $aShift === $targetShift);
            });

        if (!$lineHasOtherAssignment) {
            return true;
        }

        return false;
    }
}