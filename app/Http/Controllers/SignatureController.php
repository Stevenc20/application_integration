<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
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
}
