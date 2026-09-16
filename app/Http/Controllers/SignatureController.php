<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use Illuminate\Http\Request;
use App\Models\LineAssignment;
use App\Models\ProductionPlan;
use App\Support\SignatureScopeNormalizer;

class SignatureController extends Controller
{
    private function normalizeLine(string $line): string
    {
        return SignatureScopeNormalizer::normalizeLine($line);
    }

    private function normalizeShift(string $shift): string
    {
        return SignatureScopeNormalizer::normalizeShift($shift);
    }

    private function getStandardLineName(string $rawLine): string
    {
        return SignatureScopeNormalizer::standardLine((string) $rawLine);
    }

    private function getStandardShiftName(string $rawShift): string
    {
        return SignatureScopeNormalizer::standardShift((string) $rawShift);
    }

    private function canonicalPair(string $lineName, string $shiftName): array
    {
        return [
            $this->getStandardLineName($lineName),
            $this->getStandardShiftName($shiftName),
        ];
    }

    private function authorizedForScope(string $lineName, string $shiftName): bool
    {
        if ($this->userRole() === 'superadmin') {
            return true;
        }

        $userId = auth()->id();
        [$standardLine, $standardShift] = $this->canonicalPair($lineName, $shiftName);

        $targetLine = $this->normalizeLine($standardLine);
        $targetShift = $this->normalizeShift($standardShift);

        $assignments = LineAssignment::where(function ($q) use ($userId) {
            $q->where('leader_user_id', $userId)
                ->orWhere('foreman_user_id', $userId)
                ->orWhere('supervisor_user_id', $userId);
        })->get();

        foreach ($assignments as $assignment) {
            $assignLine = $this->normalizeLine($assignment->line_name ?? '');
            $assignShiftRaw = $assignment->shift_name ?? '';
            $assignShift = $assignShiftRaw ? $this->normalizeShift($assignShiftRaw) : '';

            if ($assignLine !== $targetLine) {
                continue;
            }

            if ($assignShift === '' || $assignShift === $targetShift) {
                return true;
            }
        }

        return false;
    }

    private function userRole(): string
    {
        return strtolower(auth()->user()?->role ?? '');
    }

    private function ownsRole(string $userRole, string $sigRole): bool
    {
        if ($userRole === 'superadmin') {
            return true;
        }

        return match ($sigRole) {
            'teamleader' => str_starts_with($userRole, 'leader') || in_array($userRole, ['teamleader', 'group leader']),
            'foreman'    => $userRole === 'foreman',
            'supervisor' => $userRole === 'supervisor',
            default      => false,
        };
    }

    private function ownsLineAndShift(string $sigRole, string $lineName, string $shiftName): bool
    {
        $userRole = $this->userRole();
        if ($userRole === 'superadmin') {
            return true;
        }
        
        $userId = auth()->id();
        
        $assignments = LineAssignment::where(function($q) use ($userId, $sigRole) {
            if ($sigRole === 'teamleader') $q->where('leader_user_id', $userId);
            elseif ($sigRole === 'foreman') $q->where('foreman_user_id', $userId);
            elseif ($sigRole === 'supervisor') $q->where('supervisor_user_id', $userId);
        })->get();

        $targetLine = $this->normalizeLine($lineName);
        $targetShift = $this->normalizeShift($shiftName);

        foreach ($assignments as $assignment) {
            $assignLine = $this->normalizeLine($assignment->line_name ?? '');
            $assignShiftRaw = $assignment->shift_name ?? '';
            $assignShift = $assignShiftRaw ? $this->normalizeShift($assignShiftRaw) : '';

            if ($assignLine === $targetLine) {
                if ($assignShift === '' || $assignShift === $targetShift) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get(Request $request)
    {
        $role = $request->query('role');
        $workDate = $request->query('work_date');
        $lineName = $request->query('line_name');
        $shiftName = $request->query('shift_name');

        if (!$role || !$workDate || !$lineName || !$shiftName) {
            return response()->json(['signature' => null]);
        }

        if (!$this->authorizedForScope($lineName, $shiftName)) {
            return response()->json([
                'error' => 'Anda tidak memiliki otorisasi untuk Line dan Shift ini'
            ], 403);
        }

        [$standardLine, $standardShift] = $this->canonicalPair($lineName, $shiftName);

        $signature = Signature::where('role', $role)
            ->where('work_date', $workDate)
            ->where('line_name', $standardLine)
            ->where('shift_name', $standardShift)
            ->first();

        return response()->json([
            'signature' => $signature ? $signature->signature_data : null,
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'role' => 'required|string|in:teamleader,foreman,supervisor',
            'work_date' => 'required|date',
            'line_name' => 'required|string',
            'shift_name' => 'required|string',
            'signature' => 'required|string',
        ]);

        [$standardLine, $standardShift] = $this->canonicalPair($request->line_name, $request->shift_name);

        if (!$this->ownsRole($this->userRole(), $request->role)) {
            return response()->json([
                'error' => 'Anda tidak berhak menandatangani TTD untuk role ini'
            ], 403);
        }

        if (!$this->ownsLineAndShift($request->role, $standardLine, $standardShift)) {
            return response()->json([
                'error' => 'Anda tidak memiliki otorisasi untuk Line dan Shift ini'
            ], 403);
        }

        $chain = ['teamleader', 'foreman', 'supervisor'];
        $currentIndex = array_search($request->role, $chain);

        if ($currentIndex > 0) {
            $prevRole = $chain[$currentIndex - 1];
            $prevSignature = Signature::where('role', $prevRole)
                ->where('work_date', $request->work_date)
                ->where('line_name', $standardLine)
                ->where('shift_name', $standardShift)
                ->first();
                
            if (!$prevSignature) {
                return response()->json([
                    'error' => 'Harap TTD oleh ' . str_replace('_', ' ', ucfirst($prevRole)) . ' terlebih dahulu'
                ], 422);
            }
        }

        Signature::updateOrCreate(
            [
                'role' => $request->role, 
                'work_date' => $request->work_date,
                'line_name' => $standardLine,
                'shift_name' => $standardShift,
            ],
            ['signature_data' => $request->signature]
        );

        return response()->json(['success' => true]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'work_date' => 'required|date',
            'line_name' => 'required|string',
            'shift_name' => 'required|string',
        ]);

        [$standardLine, $standardShift] = $this->canonicalPair($request->line_name, $request->shift_name);

        if (!$this->ownsRole($this->userRole(), $request->role)) {
            return response()->json([
                'error' => 'Anda tidak berhak menghapus TTD ini'
            ], 403);
        }

        if (!$this->ownsLineAndShift($request->role, $standardLine, $standardShift)) {
            return response()->json([
                'error' => 'Anda tidak memiliki otorisasi untuk Line dan Shift ini'
            ], 403);
        }

        $target = Signature::where('role', $request->role)
            ->where('work_date', $request->work_date)
            ->where('line_name', $standardLine)
            ->where('shift_name', $standardShift)
            ->first();

        if (!$target) {
            return response()->json([
                'error' => 'Tanda tangan tidak ditemukan pada scope ini'
            ], 404);
        }

        $target->delete();

        return response()->json(['success' => true]);
    }

    public function status(Request $request)
    {
        $workDate = $request->query('work_date');
        $lineName = $request->query('line_name');
        $shiftName = $request->query('shift_name');
        
        if (!$workDate || !$lineName || !$shiftName) {
            return response()->json([]);
        }

        if (!$this->authorizedForScope($lineName, $shiftName)) {
            return response()->json([
                'error' => 'Anda tidak memiliki otorisasi untuk Line dan Shift ini'
            ], 403);
        }

        [$standardLine, $standardShift] = $this->canonicalPair($lineName, $shiftName);

        $chain = ['teamleader', 'foreman', 'supervisor'];
        $signedRoles = Signature::whereIn('role', $chain)
            ->where('work_date', $workDate)
            ->where('line_name', $standardLine)
            ->where('shift_name', $standardShift)
            ->pluck('role')->toArray();

        $result = [];
        $prevSigned = true;
        foreach ($chain as $role) {
            $signed = in_array($role, $signedRoles);
            $result[$role] = [
                'signed' => $signed,
                'available' => $prevSigned,
            ];
            $prevSigned = $signed;
        }

        return response()->json($result);
    }

    public function pending(Request $request)
    {
        $userRole = $this->userRole();

        $sigRole = null;
        if ($userRole === 'foreman') {
            $sigRole = 'foreman';
        } elseif ($userRole === 'supervisor') {
            $sigRole = 'supervisor';
        } elseif (str_starts_with($userRole, 'leader') || in_array($userRole, ['teamleader', 'group leader'])) {
            $sigRole = 'teamleader';
        }

        if (!$sigRole) {
            return response()->json(['pending' => false]);
        }

        $hour = (int) now()->format('H');
        $workDate = ($hour < 7) ? now()->subDay()->toDateString() : now()->toDateString();

        $userId = auth()->id();
        $assignments = LineAssignment::where(function ($q) use ($userId, $sigRole) {
            if ($sigRole === 'teamleader') $q->where('leader_user_id', $userId);
            elseif ($sigRole === 'foreman') $q->where('foreman_user_id', $userId);
            elseif ($sigRole === 'supervisor') $q->where('supervisor_user_id', $userId);
        })->get();

        if ($assignments->isEmpty()) {
            return response()->json(['pending' => false]);
        }

        $chain = ['teamleader', 'foreman', 'supervisor'];
        $idx = array_search($sigRole, $chain);
        $labels = [
            'teamleader' => 'Team Leader',
            'foreman'    => 'Foreman',
            'supervisor' => 'Supervisor',
        ];

        foreach ($assignments as $assignment) {
            $standardLine = $this->getStandardLineName($assignment->line_name ?? '');
            $standardShift = $this->getStandardShiftName($assignment->shift_name ?? '');

            $targetShift = $this->normalizeShift($standardShift);

            $hasPlan = ProductionPlan::whereDate('plan_date', $workDate)
                ->whereHas('line', function ($q) use ($standardLine) {
                    $q->where('line_name', $standardLine);
                })
                ->where(function ($q) use ($targetShift) {
                    if ($targetShift === '2') {
                        $q->where(function ($w) {
                            $w->whereNull('shift_name')
                                ->orWhereRaw("UPPER(COALESCE(TRIM(shift_name), '')) = ''")
                                ->orWhereRaw("UPPER(TRIM(shift_name)) LIKE '%MALAM%'");
                        });
                    } else {
                        $q->where(function ($w) {
                            $w->whereNull('shift_name')
                                ->orWhereRaw("UPPER(COALESCE(TRIM(shift_name), '')) = ''")
                                ->orWhereRaw("UPPER(TRIM(shift_name)) LIKE '%PAGI%'");
                        });
                    }
                })
                ->exists();

            if (!$hasPlan) continue;

            $signedRoles = Signature::whereIn('role', $chain)
                ->where('work_date', $workDate)
                ->where('line_name', $standardLine)
                ->where('shift_name', $standardShift)
                ->pluck('role')->toArray();

            $prevSigned = true;
            for ($i = 0; $i < $idx; $i++) {
                if (!in_array($chain[$i], $signedRoles)) {
                    $prevSigned = false;
                    break;
                }
            }

            $pending = $prevSigned && !in_array($sigRole, $signedRoles);
            
            if ($pending) {
                return response()->json([
                    'pending'   => true,
                    'role'      => $sigRole,
                    'roleLabel' => $labels[$sigRole],
                    'url'       => route('supervisor.reports.daily_production', ['line' => $standardLine, 'shift' => $standardShift, 'date' => $workDate]),
                    'lineName'  => $standardLine,
                    'date'      => $workDate,
                ]);
            }
        }

        return response()->json(['pending' => false]);
    }
}
