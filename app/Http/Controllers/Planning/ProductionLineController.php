<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\LineMaster;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductionLineController extends Controller
{
    /**
     * Display production lines with summary stats.
     */
    public function index(Request $request)
    {
        $query = LineMaster::query();

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('line_name', 'like', "%{$q}%")
                   ->orWhere('line_code', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        $lines = $query->withCount([
            'productionPlans as total_plans',
            'productionPlans as active_plans' => fn($q) => $q->where('status', 'approved'),
        ])->orderBy('line_code')->paginate(12);

        // Stats summary
        $stats = [
            'total'       => LineMaster::count(),
            'active'      => LineMaster::where('status', 'active')->count(),
            'maintenance' => LineMaster::where('status', 'maintenance')->count(),
            'inactive'    => LineMaster::where('status', 'inactive')->count(),
        ];

        return view('supervisor.planning.production_line', compact('lines', 'stats'));
    }

    /**
     * Store a new production line.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'line_code'     => 'required|string|max:20|unique:line_masters,line_code',
            'line_name'     => 'required|string|max:255',
            'capacity'      => 'required|integer|min:0',
            'machine_count' => 'nullable|integer|min:0',
            'shift'         => 'required|in:Shift 1,Shift 2,Semua',
            'description'   => 'nullable|string',
            'status'        => 'required|in:active,inactive,maintenance',
        ], [
            'line_code.unique' => 'Kode line sudah digunakan.',
            'line_code.required' => 'Kode line wajib diisi.',
            'line_name.required' => 'Nama line wajib diisi.',
            'capacity.required'  => 'Kapasitas wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $line = LineMaster::create($validator->validated());
        return response()->json([
            'success' => true,
            'message' => "Line {$line->line_name} berhasil ditambahkan.",
            'line'    => $line,
        ]);
    }

    /**
     * Show a single line (for edit modal).
     */
    public function show($id)
    {
        $line = LineMaster::findOrFail($id);
        return response()->json(['success' => true, 'line' => $line]);
    }

    /**
     * Update a production line.
     */
    public function update(Request $request, $id)
    {
        $line = LineMaster::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'line_code'     => "required|string|max:20|unique:line_masters,line_code,{$id}",
            'line_name'     => 'required|string|max:255',
            'capacity'      => 'required|integer|min:0',
            'machine_count' => 'nullable|integer|min:0',
            'shift'         => 'required|in:Shift 1,Shift 2,Semua',
            'description'   => 'nullable|string',
            'status'        => 'required|in:active,inactive,maintenance',
        ], [
            'line_code.unique' => 'Kode line sudah digunakan oleh line lain.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $line->update($validator->validated());
        return response()->json([
            'success' => true,
            'message' => "Line {$line->line_name} berhasil diperbarui.",
        ]);
    }

    /**
     * Toggle status (quick action from table).
     */
    public function toggleStatus(Request $request, $id)
    {
        $line = LineMaster::findOrFail($id);
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['active', 'inactive', 'maintenance'])) {
            return response()->json(['success' => false, 'message' => 'Status tidak valid.'], 422);
        }

        $line->update(['status' => $newStatus]);
        return response()->json([
            'success' => true,
            'message' => "Status line diubah ke {$newStatus}.",
        ]);
    }

    /**
     * Soft-delete a production line.
     */
    public function destroy($id)
    {
        $line = LineMaster::findOrFail($id);

        // Cek apakah ada production plan yang aktif
        $activePlans = ProductionLine::where('line_name', $line->line_name)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($activePlans > 0) {
            return response()->json([
                'success' => false,
                'message' => "Line ini masih memiliki {$activePlans} production plan aktif. Selesaikan atau hapus plan terlebih dahulu.",
            ], 422);
        }

        $line->delete();
        return response()->json([
            'success' => true,
            'message' => "Line {$line->line_name} berhasil dihapus.",
        ]);
    }

    /**
     * Get list of active lines (for dropdowns in other modules).
     */
    public function activeLines()
    {
        $lines = LineMaster::active()
            ->select('id', 'line_code', 'line_name', 'capacity', 'shift')
            ->orderBy('line_code')
            ->get();

        return response()->json(['success' => true, 'lines' => $lines]);
    }
}
