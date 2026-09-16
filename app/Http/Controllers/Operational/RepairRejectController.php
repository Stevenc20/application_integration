<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\RepairRejectLog;
use App\Models\RepairRejectImage;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ShiftSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairRejectController extends Controller
{
    private function guardLockedShift($jobMasterId)
    {
        $jm = JobMaster::find($jobMasterId);
        if (!$jm || !$jm->line) return;

        $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $jm->line)));
        $lineMaster = LineMaster::whereRaw("
            REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
        ", ["%{$normalized}%"])->first();
        if (!$lineMaster) return;

        $shift = request()->header('X-Shift') ?: request('shift', $this->getShift());
        $shiftVal = str_contains(strtoupper($shift), 'MALAM') ? 2 : 1;
        $date = request()->header('X-Date') ?: request('date', now()->toDateString());

        $locked = ShiftSubmission::where([
            'line_id' => $lineMaster->id,
            'work_date' => $date,
            'shift' => $shiftVal,
        ])->exists();

        if ($locked) {
            throw new \Exception('Shift sudah dikunci. Data Repair/Reject tidak dapat diubah.');
        }
    }

    private function getShift()
    {
        $hour = (int) now()->format('H');
        if ($hour >= 7 && $hour < 19) return 'Shift Pagi';
        return 'Shift Malam';
    }

    /**
     * Halaman History Repair & Reject
     */
    public function index(Request $request)
    {
        $query = RepairRejectLog::with(['jobMaster', 'images', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('job_id')) {
            $query->where('job_master_id', $request->job_id);
        }
        if ($request->filled('defect')) {
            $query->where('defect_name', 'like', '%' . $request->defect . '%');
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->filled('line')) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $request->line)));
            $query->whereHas('jobMaster', function ($q) use ($normalized) {
                $q->whereRaw("TRIM(UPPER(REPLACE(REPLACE(UPPER(line), 'PRESS ', ''), 'LINE ', ''))) = ?", [$normalized]);
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Stats
        $totalRepair = RepairRejectLog::where('type', 'repair')->sum('qty_a');
        $totalReject = RepairRejectLog::where('type', 'reject')->sum('qty_a');
        $todayRepair = RepairRejectLog::where('type', 'repair')->whereDate('created_at', today())->sum('qty_a');
        $todayReject = RepairRejectLog::where('type', 'reject')->whereDate('created_at', today())->sum('qty_a');

        return view('operational.repair_reject_history', compact(
            'logs', 'totalRepair', 'totalReject', 'todayRepair', 'todayReject'
        ));
    }


    /**
     * Simpan data Repair / Reject baru
     */
    public function store(Request $request)
    {
        $this->guardLockedShift($request->input('job_master_id'));
        $validated = $request->validate([
            'job_master_id'  => 'required|exists:job_masters,id',
            'type'           => 'required|in:repair,reject',
            'defect_name'    => 'required|string|max:255',
            'qty_a'          => 'required|numeric|min:0',
            'qty_b'         => 'nullable|numeric|min:0',
            'pcs_number'     => 'nullable|string|max:255',
            'sketch_no'      => 'nullable|string|max:100',
            'repair_category'=> 'nullable|string|max:100',
            'area_problem'   => 'nullable|string|max:255',
            'root_cause'     => 'nullable|string',
            'countermeasure' => 'nullable|string',
            'images.*'       => 'nullable|file|max:5120',
        ]);

        $validated['created_by'] = auth()->id();
        $workDate = $request->get('date', now()->toDateString());

        $log = RepairRejectLog::create($validated);

        // Handle image uploads (manual copy — bypass Flysystem/FinfoMimeTypeDetector)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $file) {
                try {
                    $originalName = $file->getClientOriginalName();
                    $safeName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $originalName);
                    $relPath = 'repair_reject/' . $log->id . '/' . $safeName;
                    $absPath = public_path('uploads/' . $relPath);
                    $dir = dirname($absPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    copy($file->getRealPath(), $absPath);
                    if (!file_exists($absPath)) {
                        throw new \RuntimeException("copy() reported success but file not found at: $absPath");
                    }
                    chmod($absPath, 0644);
                    RepairRejectImage::create([
                        'repair_reject_log_id' => $log->id,
                        'image_path'           => $relPath,
                        'image_type'           => 'before',
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('RR store image[' . $i . '] failed: ' . $e->getMessage() . ' | file: ' . ($file->getClientOriginalName() ?? 'unknown') . ' | tmp: ' . ($file->getRealPath() ?? 'N/A'));
                }
            }
        }

        // Sync with ProductionService to update DailyProduction and create ProductionLog
        $productionService = app(\App\Services\ProductionService::class);
        $logData = [
            'ok_qty' => 0,
            'repair_qty' => $validated['type'] === 'repair' ? $validated['qty_a'] : 0,
            'reject_qty' => $validated['type'] === 'reject' ? $validated['qty_a'] : 0,
        ];
        $productionService->saveProductionLog($validated['job_master_id'], $logData, $workDate);

        return response()->json([
            'success' => true,
            'message' => 'Data ' . ucfirst($validated['type']) . ' berhasil disimpan.',
            'log'     => $log->load('images'),
        ]);
    }

    /**
     * Hapus satu log + gambarnya
     */
    public function destroy($id)
    {
        $log = RepairRejectLog::with('images')->findOrFail($id);
        $this->guardLockedShift($log->job_master_id);

        foreach ($log->images as $img) {
            $absPath = public_path('uploads/' . $img->image_path);
            if (file_exists($absPath)) {
                unlink($absPath);
            }
        }
        $log->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Ambil history repair/reject untuk job tertentu (dipakai di input_harian AJAX)
     */
    public function getByJob($jobId)
    {
        $logs = RepairRejectLog::with(['images', 'creator'])
            ->where('job_master_id', $jobId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id'            => $log->id,
                    'type'          => $log->type,
                    'defect_name'   => $log->defect_name,
                    'qty_a'         => $log->qty_a,
                    'qty_b'         => $log->qty_b,
                    'pcs_number'    => $log->pcs_number,
                    'area_problem'  => $log->area_problem,
                    'root_cause'    => $log->root_cause,
                    'countermeasure'=> $log->countermeasure,
                    'operator'      => $log->creator?->name ?? '-',
                    'time'          => $log->created_at->format('H:i'),
                    'date'          => $log->created_at->format('d M Y'),
                    'images'        => $log->images->map(fn($i) => asset('uploads/' . $i->image_path))->toArray(),
                ];
            });

        return response()->json($logs);
    }

    /**
     * Update log repair / reject
     */
    public function update(Request $request, $id)
    {
        $log = RepairRejectLog::findOrFail($id);
        $this->guardLockedShift($log->job_master_id);
        $oldQty = $log->qty_a;

        $validated = $request->validate([
            'defect_name'    => 'required|string|max:255',
            'qty_a'          => 'required|numeric|min:0',
            'qty_b'         => 'nullable|numeric|min:0',
            'pcs_number'     => 'nullable|string|max:255',
            'sketch_no'      => 'nullable|string|max:100',
            'repair_category'=> 'nullable|string|max:100',
            'area_problem'   => 'nullable|string|max:255',
            'root_cause'     => 'nullable|string',
            'countermeasure' => 'nullable|string',
            'images.*'       => 'nullable|file|max:5120',
        ]);

        $log->update($validated);

        // Handle image uploads (manual copy — bypass Flysystem/FinfoMimeTypeDetector)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $file) {
                try {
                    $originalName = $file->getClientOriginalName();
                    $safeName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $originalName);
                    $relPath = 'repair_reject/' . $log->id . '/' . $safeName;
                    $absPath = public_path('uploads/' . $relPath);
                    $dir = dirname($absPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    copy($file->getRealPath(), $absPath);
                    if (!file_exists($absPath)) {
                        throw new \RuntimeException("copy() reported success but file not found at: $absPath");
                    }
                    chmod($absPath, 0644);
                    RepairRejectImage::create([
                        'repair_reject_log_id' => $log->id,
                        'image_path'           => $relPath,
                        'image_type'           => 'before',
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('RR update image[' . $i . '] failed: ' . $e->getMessage() . ' | file: ' . ($file->getClientOriginalName() ?? 'unknown') . ' | tmp: ' . ($file->getRealPath() ?? 'N/A'));
                }
            }
        }

        // Update DailyProduction / ProductionLog with the difference in qty
        $productionService = app(\App\Services\ProductionService::class);
        $qtyDiff = $validated['qty_a'] - $oldQty;
        $workDate = $request->get('date', now()->toDateString());

        if ($qtyDiff != 0) {
            $logData = [
                'ok_qty' => 0,
                'repair_qty' => $log->type === 'repair' ? $qtyDiff : 0,
                'reject_qty' => $log->type === 'reject' ? $qtyDiff : 0,
            ];
            $productionService->saveProductionLog($log->job_master_id, $logData, $workDate);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data ' . ucfirst($log->type) . ' berhasil diperbarui.',
            'log'     => $log->load('images'),
        ]);
    }
}
