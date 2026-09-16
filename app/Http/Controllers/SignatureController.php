<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use Illuminate\Http\Request;
use App\Models\LineAssignment;
use App\Models\LineMaster;
use App\Models\ProductionPlan;

class SignatureController extends Controller
{
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
        
        $assignment = LineAssignment::where('line_name', $lineName)
            ->where(function($q) use ($shiftName) {
                $q->where('shift_name', $shiftName)
                  ->orWhereNull('shift_name')
                  ->orWhere('shift_name', '');
            })
            ->where(function($q) use ($userId, $sigRole) {
                if ($sigRole === 'teamleader') $q->where('leader_user_id', $userId);
                elseif ($sigRole === 'foreman') $q->where('foreman_user_id', $userId);
                elseif ($sigRole === 'supervisor') $q->where('supervisor_user_id', $userId);
                else $q->where('id', -1);
            })->exists();

        return $assignment;
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

        $signature = Signature::where('role', $role)
            ->where('work_date', $workDate)
            ->where('line_name', $lineName)
            ->where('shift_name', $shiftName)
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

        if (!$this->ownsRole($this->userRole(), $request->role)) {
            return response()->json([
                'error' => 'Anda tidak berhak menandatangani TTD untuk role ini'
            ], 403);
        }

        if (!$this->ownsLineAndShift($request->role, $request->line_name, $request->shift_name)) {
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
                ->where('line_name', $request->line_name)
                ->where('shift_name', $request->shift_name)
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
                'line_name' => $request->line_name,
                'shift_name' => $request->shift_name,
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

        if (!$this->ownsRole($this->userRole(), $request->role)) {
            return response()->json([
                'error' => 'Anda tidak berhak menghapus TTD ini'
            ], 403);
        }

        if (!$this->ownsLineAndShift($request->role, $request->line_name, $request->shift_name)) {
            return response()->json([
                'error' => 'Anda tidak memiliki otorisasi untuk Line dan Shift ini'
            ], 403);
        }

        Signature::where('role', $request->role)
            ->where('work_date', $request->work_date)
            ->where('line_name', $request->line_name)
            ->where('shift_name', $request->shift_name)
            ->delete();

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

        $chain = ['teamleader', 'foreman', 'supervisor'];
        $signedRoles = Signature::whereIn('role', $chain)
            ->where('work_date', $workDate)
            ->where('line_name', $lineName)
            ->where('shift_name', $shiftName)
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
            $lineName = $assignment->line_name ?? LineMaster::where('status', 'active')->value('line_name') ?? 'Line A';
            $shiftName = $assignment->shift_name ?? 'Shift Pagi';

            $hasPlan = ProductionPlan::whereDate('plan_date', $workDate)->exists();
            if (!$hasPlan) continue;

            $signedRoles = Signature::whereIn('role', $chain)
                ->where('work_date', $workDate)
                ->where('line_name', $lineName)
                ->where('shift_name', $shiftName)
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
                    'url'       => route('supervisor.reports.daily_production', ['line' => $lineName, 'shift' => $shiftName, 'date' => $workDate]),
                    'lineName'  => $lineName,
                    'date'      => $workDate,
                ]);
            }
        }

        return response()->json(['pending' => false]);
    }
}
