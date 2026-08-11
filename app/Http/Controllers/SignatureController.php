<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use Illuminate\Http\Request;

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

    public function get(Request $request)
    {
        $role = $request->query('role');
        $workDate = $request->query('work_date');
        if (!$role || !$workDate) {
            return response()->json(['signature' => null]);
        }

        $signature = Signature::where('role', $role)->where('work_date', $workDate)->first();

        return response()->json([
            'signature' => $signature ? $signature->signature_data : null,
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'role' => 'required|string|in:teamleader,foreman,supervisor',
            'work_date' => 'required|date',
            'signature' => 'required|string',
        ]);

        if (!$this->ownsRole($this->userRole(), $request->role)) {
            return response()->json([
                'error' => 'Anda tidak berhak menandatangani TTD untuk role ini'
            ], 403);
        }

        $chain = ['teamleader', 'foreman', 'supervisor'];
        $currentIndex = array_search($request->role, $chain);

        if ($currentIndex > 0) {
            $prevRole = $chain[$currentIndex - 1];
            $prevSignature = Signature::where('role', $prevRole)->where('work_date', $request->work_date)->first();
            if (!$prevSignature) {
                return response()->json([
                    'error' => 'Harap TTD oleh ' . str_replace('_', ' ', ucfirst($prevRole)) . ' terlebih dahulu'
                ], 422);
            }
        }

        Signature::updateOrCreate(
            ['role' => $request->role, 'work_date' => $request->work_date],
            ['signature_data' => $request->signature]
        );

        return response()->json(['success' => true]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'work_date' => 'required|date',
        ]);

        if (!$this->ownsRole($this->userRole(), $request->role)) {
            return response()->json([
                'error' => 'Anda tidak berhak menghapus TTD ini'
            ], 403);
        }

        Signature::where('role', $request->role)->where('work_date', $request->work_date)->delete();

        return response()->json(['success' => true]);
    }

    public function status(Request $request)
    {
        $workDate = $request->query('work_date');
        if (!$workDate) {
            return response()->json([]);
        }

        $chain = ['teamleader', 'foreman', 'supervisor'];
        $signedRoles = Signature::whereIn('role', $chain)->where('work_date', $workDate)->pluck('role')->toArray();

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

        $hasPlan = \App\Models\ProductionPlan::whereDate('plan_date', $workDate)->exists();
        if (!$hasPlan) {
            return response()->json(['pending' => false]);
        }

        $chain = ['teamleader', 'foreman', 'supervisor'];
        $signedRoles = Signature::whereIn('role', $chain)->where('work_date', $workDate)->pluck('role')->toArray();

        $idx = array_search($sigRole, $chain);
        $prevSigned = true;
        for ($i = 0; $i < $idx; $i++) {
            if (!in_array($chain[$i], $signedRoles)) {
                $prevSigned = false;
                break;
            }
        }

        $pending = $prevSigned && !in_array($sigRole, $signedRoles);
        if (!$pending) {
            return response()->json(['pending' => false]);
        }

        $userId = auth()->id();
        $assignment = \App\Models\LineAssignment::where(function ($q) use ($userId) {
            $q->where('leader_user_id', $userId)
              ->orWhere('foreman_user_id', $userId)
              ->orWhere('supervisor_user_id', $userId);
        })->first();

        $lineName = $assignment?->line_name
            ?? \App\Models\LineMaster::where('status', 'active')->value('line_name')
            ?? 'Line A';
        $shiftName = $assignment?->shift_name ?? 'Shift Pagi';

        $url = route('supervisor.reports.daily_production', [
            'line' => $lineName,
            'shift' => $shiftName,
            'date' => $workDate,
        ]);

        $labels = [
            'teamleader' => 'Team Leader',
            'foreman'    => 'Foreman',
            'supervisor' => 'Supervisor',
        ];

        return response()->json([
            'pending'   => true,
            'role'      => $sigRole,
            'roleLabel' => $labels[$sigRole],
            'url'       => $url,
            'lineName'  => $lineName,
            'date'      => $workDate,
        ]);
    }
}
