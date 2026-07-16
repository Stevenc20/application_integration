<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LineMaster;
use App\Models\LineAssignment;
use Illuminate\Http\Request;

class LineAssignmentController extends Controller
{
    public function index()
    {
        $lines = LineMaster::pluck('line_name')->unique()->toArray();
        if (empty($lines)) {
            $lines = ['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D'];
        }

        $shifts = ['Shift Pagi', 'Shift Malam'];

        $assignments = LineAssignment::with(['leaderUser', 'foremanUser', 'supervisorUser'])->get();

        // Get users grouped by roles
        $leaders = User::where('role', 'like', 'leader%')
            ->orWhereIn('role', ['shearing', 'handwork'])
            ->orderBy('name')
            ->get();

        $foremen = User::where('role', 'foreman')
            ->orderBy('name')
            ->get();

        $supervisors = User::where('role', 'supervisor')
            ->orderBy('name')
            ->get();

        return view('super_admin.line_assignments.index', compact(
            'lines',
            'shifts',
            'assignments',
            'leaders',
            'foremen',
            'supervisors'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'line_name' => 'required|string',
            'shift_name' => 'required|string',
            'leader_user_id' => 'nullable|exists:users,id',
            'foreman_user_id' => 'nullable|exists:users,id',
            'supervisor_user_id' => 'nullable|exists:users,id',
        ]);

        LineAssignment::updateOrCreate(
            [
                'line_name' => $request->line_name,
                'shift_name' => $request->shift_name,
            ],
            [
                'leader_user_id' => $request->leader_user_id,
                'foreman_user_id' => $request->foreman_user_id,
                'supervisor_user_id' => $request->supervisor_user_id,
            ]
        );

        return redirect()->back()->with('success', 'Assignment saved successfully.');
    }

    public function destroy(LineAssignment $assignment)
    {
        $assignment->delete();
        return redirect()->back()->with('success', 'Assignment removed successfully.');
    }
}
