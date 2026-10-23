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
        if (! $user) {
            return false;
        }

        $userRole = strtolower((string) $user->role);

        // Superadmin, Foreman, Supervisor, Management always have broad access
        if (in_array($userRole, ['superadmin', 'foreman', 'supervisor', 'manager', 'kadiv', 'direktur'])) {
            return true;
        }

        $isLeaderRole = str_starts_with($userRole, 'leader') || in_array($userRole, ['leader', 'teamleader', 'group leader']);
        if (! $isLeaderRole) {
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
        if ($normalizedLineLower !== '' && str_contains($userRole, 'leader '.$normalizedLineLower)) {
            $userShift = \App\Models\LineAssignment::where('leader_user_id', $user->id)
                ->pluck('shift_name')
                ->filter()
                ->map(fn ($s) => self::normalizeShift((string) $s))
                ->unique();
            if ($userShift->isEmpty() || $userShift->contains($targetShift)) {
                return true;
            }
        }

        // 4. Default / General Leader fallback: Only when no leader assignment exists at all and user is not assigned elsewhere
        if ($assignments->isEmpty()) {
            $lineHasOtherAssignment = \App\Models\LineAssignment::whereNotNull('leader_user_id')
                ->where('leader_user_id', '!=', $user->id)
                ->get()
                ->contains(function ($a) use ($targetLine, $targetShift) {
                    $aLine = SignatureScopeNormalizer::normalizeLine((string) $a->line_name);
                    $aShift = $a->shift_name ? SignatureScopeNormalizer::normalizeShift((string) $a->shift_name) : '';

                    return $aLine === $targetLine && ($aShift === '' || $aShift === $targetShift);
                });

            if (! $lineHasOtherAssignment && $workDate) {
                $hasSubmissionOnScope = \App\Models\ShiftSubmission::whereIn('line_id', $lineMasterIds)
                    ->whereDate('work_date', $workDate)
                    ->when($numericShift > 0, fn ($q) => $q->where('shift', $numericShift))
                    ->whereNull('cancelled_at')
                    ->exists();
                if ($hasSubmissionOnScope) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find a normalized LineAssignment matching the requested line and shift.
     */
    public static function findAssignmentForScope(string $lineName, string $shiftName): ?\App\Models\LineAssignment
    {
        $targetLine = self::normalizeLine($lineName);
        $targetShift = self::normalizeShift($shiftName);

        if ($targetLine === '') {
            return null;
        }

        $assignments = \App\Models\LineAssignment::with(['leaderUser', 'foremanUser', 'supervisorUser'])->get();

        // First pass: exact normalized line + shift match
        foreach ($assignments as $assignment) {
            $assignLine = self::normalizeLine((string) ($assignment->line_name ?? ''));
            $assignShift = $assignment->shift_name ? self::normalizeShift((string) $assignment->shift_name) : '';
            if ($assignLine === $targetLine && $assignShift !== '' && $assignShift === $targetShift) {
                return $assignment;
            }
        }

        // Second pass: line match with wildcard/empty shift
        foreach ($assignments as $assignment) {
            $assignLine = self::normalizeLine((string) ($assignment->line_name ?? ''));
            $assignShift = $assignment->shift_name ? self::normalizeShift((string) $assignment->shift_name) : '';
            if ($assignLine === $targetLine && $assignShift === '') {
                return $assignment;
            }
        }

        return null;
    }

    /**
     * Resolve deterministic historical/audit signer identity for a role + date + line + shift scope.
     * Never uses auth()->user() so viewing/regenerating LKH or PDF cannot alter identity.
     *
     * @return array{user_id: int|null, name: string, role: string}
     */
    public static function resolveHistoricalSignerIdentity(
        string $sigRole,
        string $workDate,
        string $lineName,
        string $shiftName,
        ?\Carbon\CarbonInterface $signatureTimestamp = null
    ): array {
        $roleKey = strtolower(trim($sigRole));
        $targetLine = self::normalizeLine($lineName);
        $targetShift = self::normalizeShift($shiftName);
        $numericShift = (int) $targetShift;

        // 0. Exact HTTP Audit Trail: NetworkAccessLog on POST signature/save matching role & timestamp
        if (\Illuminate\Support\Facades\Schema::hasTable('network_access_logs')) {
            $logIdentity = self::resolveSignerFromNetworkLog($roleKey, $workDate, $signatureTimestamp);
            if ($logIdentity !== null) {
                return $logIdentity;
            }
        }

        $lineMasterIds = \App\Models\LineMaster::all()->filter(function ($lm) use ($targetLine) {
            return self::normalizeLine((string) $lm->line_name) === $targetLine;
        })->pluck('id');

        $assignment = self::findAssignmentForScope($lineName, $shiftName);
        $seedNames = self::seedDummyNames();

        if ($roleKey === 'teamleader') {
            // 1. Audit trail: ShiftSubmission (lock shift) on that exact line, date, and shift
            if ($lineMasterIds->isNotEmpty() && $workDate !== '') {
                $submission = \App\Models\ShiftSubmission::with('submitter')
                    ->whereIn('line_id', $lineMasterIds)
                    ->whereDate('work_date', $workDate)
                    ->when($numericShift > 0, fn ($q) => $q->where('shift', $numericShift))
                    ->whereNull('cancelled_at')
                    ->latest('submitted_at')
                    ->latest('id')
                    ->first();

                if ($submission && $submission->submitter) {
                    return [
                        'user_id' => (int) $submission->submitter->id,
                        'name' => (string) $submission->submitter->name,
                        'role' => (string) $submission->submitter->role,
                    ];
                }
            }

            // 2. Normalized LineAssignment for that line + shift
            if ($assignment && $assignment->leaderUser) {
                return [
                    'user_id' => (int) $assignment->leaderUser->id,
                    'name' => (string) $assignment->leaderUser->name,
                    'role' => (string) $assignment->leaderUser->role,
                ];
            }

            // 3. Operational Audit Trail on that line + date + shift: Dandori, JobFinishReason, LkhCorrection
            if ($lineMasterIds->isNotEmpty() && $workDate !== '') {
                $plansOnScope = \App\Models\ProductionPlan::whereIn('line_master_id', $lineMasterIds)
                    ->whereDate('plan_date', $workDate)
                    ->get()
                    ->filter(function ($p) use ($targetShift) {
                        if ($targetShift === '') {
                            return true;
                        }
                        $pShift = $p->shift_name ? self::normalizeShift((string) $p->shift_name) : '1';

                        return $pShift === $targetShift;
                    });

                $planIds = $plansOnScope->pluck('id');

                if ($planIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('lkh_corrections')) {
                    $leaderCorrection = \App\Models\LkhCorrection::with('editor')
                        ->whereIn('plan_id', $planIds)
                        ->latest('edited_at')
                        ->get()
                        ->first(function ($c) {
                            $r = strtolower((string) ($c->editor?->role ?? ''));

                            return str_starts_with($r, 'leader') || in_array($r, ['teamleader', 'group leader']);
                        });

                    if ($leaderCorrection && $leaderCorrection->editor) {
                        return [
                            'user_id' => (int) $leaderCorrection->editor->id,
                            'name' => (string) $leaderCorrection->editor->name,
                            'role' => (string) $leaderCorrection->editor->role,
                        ];
                    }
                }

                if ($planIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('job_finish_reasons')) {
                    $finishReason = \App\Models\JobFinishReason::with('user')
                        ->whereIn('production_plan_id', $planIds)
                        ->whereNotNull('user_id')
                        ->latest('id')
                        ->get()
                        ->first(function ($jfr) {
                            $r = strtolower((string) ($jfr->user?->role ?? ''));

                            return str_starts_with($r, 'leader') || in_array($r, ['teamleader', 'group leader']);
                        });

                    if ($finishReason && $finishReason->user) {
                        return [
                            'user_id' => (int) $finishReason->user->id,
                            'name' => (string) $finishReason->user->name,
                            'role' => (string) $finishReason->user->role,
                        ];
                    }
                }
            }

            // 4. Line-specific leader role (prefer real user over seeded dummy user)
            if ($targetLine !== '') {
                $leaderRole = 'leader '.strtolower($targetLine);
                $leaderUsers = \App\Models\User::whereRaw('LOWER(role) = ?', [$leaderRole])
                    ->orderByDesc('id')
                    ->get();
                $realLeader = $leaderUsers->first(fn ($u) => ! in_array($u->name, $seedNames, true));
                if ($realLeader) {
                    return [
                        'user_id' => (int) $realLeader->id,
                        'name' => (string) $realLeader->name,
                        'role' => (string) $realLeader->role,
                    ];
                }
            }

            // 5. General leader role fallback (prefer real user over seeded dummy user)
            $generalLeaders = \App\Models\User::where(function ($q) {
                $q->whereRaw("LOWER(role) LIKE 'leader%'")
                    ->orWhereRaw("LOWER(role) IN ('teamleader', 'group leader')");
            })->orderByDesc('id')->get();
            $realGeneralLeader = $generalLeaders->first(fn ($u) => ! in_array($u->name, $seedNames, true)) ?? $generalLeaders->first();
            if ($realGeneralLeader) {
                return [
                    'user_id' => (int) $realGeneralLeader->id,
                    'name' => (string) $realGeneralLeader->name,
                    'role' => (string) $realGeneralLeader->role,
                ];
            }

            return ['user_id' => null, 'name' => '', 'role' => ''];
        }

        if ($roleKey === 'foreman') {
            // 1. Audit trail: LkhCorrection on that line + date + shift by a foreman
            if ($lineMasterIds->isNotEmpty() && $workDate !== '' && \Illuminate\Support\Facades\Schema::hasTable('lkh_corrections')) {
                $planIds = \App\Models\ProductionPlan::whereIn('line_master_id', $lineMasterIds)
                    ->whereDate('plan_date', $workDate)
                    ->get()
                    ->filter(function ($p) use ($targetShift) {
                        if ($targetShift === '') {
                            return true;
                        }
                        $pShift = $p->shift_name ? self::normalizeShift((string) $p->shift_name) : '1';

                        return $pShift === $targetShift;
                    })
                    ->pluck('id');

                if ($planIds->isNotEmpty()) {
                    $foremanCorrection = \App\Models\LkhCorrection::with('editor')
                        ->whereIn('plan_id', $planIds)
                        ->latest('edited_at')
                        ->get()
                        ->first(fn ($c) => strtolower((string) ($c->editor?->role ?? '')) === 'foreman');

                    if ($foremanCorrection && $foremanCorrection->editor) {
                        return [
                            'user_id' => (int) $foremanCorrection->editor->id,
                            'name' => (string) $foremanCorrection->editor->name,
                            'role' => (string) $foremanCorrection->editor->role,
                        ];
                    }
                }
            }

            // 2. Normalized LineAssignment for that line + shift
            if ($assignment && $assignment->foremanUser) {
                return [
                    'user_id' => (int) $assignment->foremanUser->id,
                    'name' => (string) $assignment->foremanUser->name,
                    'role' => (string) $assignment->foremanUser->role,
                ];
            }

            // 3. Foreman user fallback (prefer real user over seeded 'Dedi Foreman')
            $foremanUsers = \App\Models\User::whereRaw('LOWER(role) = ?', ['foreman'])
                ->orderByDesc('id')
                ->get();
            $foremanUser = $foremanUsers->first(fn ($u) => ! in_array($u->name, $seedNames, true)) ?? $foremanUsers->first();
            if ($foremanUser) {
                return [
                    'user_id' => (int) $foremanUser->id,
                    'name' => (string) $foremanUser->name,
                    'role' => (string) $foremanUser->role,
                ];
            }

            return ['user_id' => null, 'name' => '', 'role' => ''];
        }

        if ($roleKey === 'supervisor') {
            // 1. Normalized LineAssignment for that line + shift
            if ($assignment && $assignment->supervisorUser) {
                return [
                    'user_id' => (int) $assignment->supervisorUser->id,
                    'name' => (string) $assignment->supervisorUser->name,
                    'role' => (string) $assignment->supervisorUser->role,
                ];
            }

            // 2. Supervisor user fallback (prefer real user like Sapriadi over seeded 'Eko Supervisor')
            $supervisorUsers = \App\Models\User::whereRaw('LOWER(role) IN (?, ?)', ['supervisor', 'spv'])
                ->orderByDesc('id')
                ->get();
            $supervisorUser = $supervisorUsers->first(fn ($u) => ! in_array($u->name, $seedNames, true)) ?? $supervisorUsers->first();
            if ($supervisorUser) {
                return [
                    'user_id' => (int) $supervisorUser->id,
                    'name' => (string) $supervisorUser->name,
                    'role' => (string) $supervisorUser->role,
                ];
            }

            return ['user_id' => null, 'name' => '', 'role' => ''];
        }

        return ['user_id' => null, 'name' => '', 'role' => ''];
    }

    /**
     * Resolve locked signer identity from an existing Signature model (or historical fallback if pre-migration).
     *
     * @return array{user_id: int|null, name: string, role: string, signed_at: string|null}
     */
    public static function resolveSignatureIdentity(\App\Models\Signature $signature): array
    {
        $signerUser = $signature->relationLoaded('signer') ? $signature->signer : $signature->signer()->first();
        $name = trim((string) ($signature->signer_name ?? ''));
        $userId = $signature->signed_by_user_id ? (int) $signature->signed_by_user_id : null;
        $role = trim((string) ($signature->signer_role ?? ''));
        $signedAt = $signature->signed_at
            ? $signature->signed_at->toIso8601String()
            : ($signature->created_at ? $signature->created_at->toIso8601String() : null);

        if ($name === '' && $signerUser) {
            $name = (string) $signerUser->name;
            $userId = (int) $signerUser->id;
            $role = $role !== '' ? $role : (string) $signerUser->role;
        }

        $seedNames = self::seedDummyNames();
        if ($name === '' || in_array($name, $seedNames, true)) {
            $historical = self::resolveHistoricalSignerIdentity(
                (string) $signature->role,
                (string) $signature->work_date,
                (string) ($signature->line_name ?? ''),
                (string) ($signature->shift_name ?? ''),
                $signature->updated_at ?? $signature->created_at
            );
            if ($historical['name'] !== '' && (! in_array($historical['name'], $seedNames, true) || $name === '')) {
                $name = $historical['name'];
                $userId = $historical['user_id'] ?? $userId;
                $role = $historical['role'] !== '' ? $historical['role'] : $role;
            }
        }

        return [
            'user_id' => $userId,
            'name' => $name,
            'role' => $role,
            'signed_at' => $signedAt,
        ];
    }

    /**
     * Match exact POST /signature/save request in NetworkAccessLog to identify the user who clicked saveSignature.
     *
     * @return array{user_id: int, name: string, role: string}|null
     */
    private static function resolveSignerFromNetworkLog(
        string $roleKey,
        string $workDate,
        ?\Carbon\CarbonInterface $signatureTimestamp = null
    ): ?array {
        $query = \App\Models\NetworkAccessLog::with('user')
            ->where('method', 'POST')
            ->where('endpoint', 'like', '%signature/save%')
            ->where('response_status', 200)
            ->whereNotNull('user_id');

        if ($signatureTimestamp) {
            $startWindow = $signatureTimestamp->copy()->subSeconds(30);
            $endWindow = $signatureTimestamp->copy()->addSeconds(30);
            $logs = (clone $query)->whereBetween('created_at', [$startWindow, $endWindow])
                ->orderByDesc('created_at')
                ->get();

            foreach ($logs as $log) {
                if ($log->user && self::userMatchesSignatureRole($log->user, $roleKey)) {
                    return [
                        'user_id' => (int) $log->user->id,
                        'name' => (string) $log->user->name,
                        'role' => (string) $log->user->role,
                    ];
                }
            }
        }

        if ($workDate !== '') {
            $logsOnDate = (clone $query)->whereDate('created_at', $workDate)
                ->orderByDesc('created_at')
                ->get();

            foreach ($logsOnDate as $log) {
                if ($log->user && self::userMatchesSignatureRole($log->user, $roleKey)) {
                    return [
                        'user_id' => (int) $log->user->id,
                        'name' => (string) $log->user->name,
                        'role' => (string) $log->user->role,
                    ];
                }
            }
        }

        return null;
    }

    private static function userMatchesSignatureRole(\App\Models\User $user, string $sigRole): bool
    {
        $userRole = strtolower((string) $user->role);

        return match ($sigRole) {
            'teamleader' => str_starts_with($userRole, 'leader') || in_array($userRole, ['leader', 'teamleader', 'group leader'], true),
            'foreman' => $userRole === 'foreman',
            'supervisor' => in_array($userRole, ['supervisor', 'spv'], true),
            default => false,
        };
    }

    /**
     * Known default dummy names from DatabaseSeeder that should never take precedence over real users.
     *
     * @return list<string>
     */
    public static function seedDummyNames(): array
    {
        return [
            'Candra Leader',
            'Fajar Leader A',
            'Guntur Leader B',
            'Dedi Foreman',
            'Eko Supervisor',
        ];
    }
}
