<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\RepairRejectLog;
use App\Models\RepairRejectImage;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ShiftSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RepairRejectController extends Controller
{
    private function guardLockedShift(int|string $jobMasterId)
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
        ])->whereNull('cancelled_at')->exists();

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

        // Default tanggal: jika request tidak menyertakan 'date' dan user tidak sedang menekan 'Semua Tanggal' (date='')
        // maka otomatis arahkan / filter ke hari ini (atau tanggal data terbaru jika hari ini kosong)
        $filterDate = null;
        if ($request->has('date')) {
            if ($request->filled('date')) {
                $filterDate = $request->date;
                $query->whereDate('created_at', $filterDate);
            }
        } else {
            // Default pertama kali buka: utamakan hari ini, jika hari ini belum ada data maka pakai tanggal terbaru yang ada
            $today = now()->toDateString();
            $hasTodayData = RepairRejectLog::whereDate('created_at', $today)->exists();
            if ($hasTodayData) {
                $filterDate = $today;
            } else {
                $latestLog = RepairRejectLog::latest('created_at')->first();
                $filterDate = $latestLog ? $latestLog->created_at->toDateString() : $today;
            }
            $query->whereDate('created_at', $filterDate);
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
            'logs', 'totalRepair', 'totalReject', 'todayRepair', 'todayReject', 'filterDate'
        ));
    }


    /**
     * Simpan file foto evidence ke public/uploads dan catat ke repair_reject_images.
     * Melempar ValidationException agar record tidak tersimpan tanpa foto.
     */
    private function saveUploadedImages(RepairRejectLog $log, array $files): int
    {
        $saved = 0;
        $writtenPaths = [];

        try {
            foreach ($files as $file) {
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
                $writtenPaths[] = $absPath;
                chmod($absPath, 0644);
                RepairRejectImage::create([
                    'repair_reject_log_id' => $log->id,
                    'image_path'           => $relPath,
                    'image_type'           => 'before',
                ]);
                $saved++;
            }
        } catch (\Throwable $e) {
            foreach ($writtenPaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            Log::error('RR image upload failed: ' . $e->getMessage() . ' | file: ' . ($file->getClientOriginalName() ?? 'unknown'));
            throw ValidationException::withMessages([
                'images' => 'Foto evidence gagal disimpan. Silakan coba unggah ulang.',
            ]);
        }

        if ($saved === 0) {
            throw ValidationException::withMessages([
                'images' => 'Minimal 1 foto evidence Repair/Reject wajib diunggah.',
            ]);
        }

        return $saved;
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
            'qty_a'          => 'required|numeric|min:1',
            'qty_b'         => 'nullable|numeric|min:0',
            'pcs_number'     => 'nullable|string|max:255',
            'sketch_no'      => 'nullable|string|max:100',
            'repair_category'=> 'nullable|string|max:100',
            'area_problem'   => 'required|string|max:255',
            'root_cause'     => 'required|string',
            'countermeasure' => 'required|string',
            'images'         => 'required|array|min:1',
            'images.*'       => 'required|file|image|max:5120',
        ], [
            'images.required' => 'Minimal 1 foto evidence Repair/Reject wajib diunggah.',
            'images.min'      => 'Minimal 1 foto evidence Repair/Reject wajib diunggah.',
        ]);

        $validated['created_by'] = Auth::id();
        $logData = array_diff_key($validated, ['images' => true]);
        $workDate = $request->get('date', now()->toDateString());

        $log = DB::transaction(function () use ($validated, $logData, $request, $workDate) {
            $log = RepairRejectLog::create($logData);

            // Handle image uploads (manual copy — bypass Flysystem/FinfoMimeTypeDetector)
            $this->saveUploadedImages($log, $request->file('images', []));

            // Sync with ProductionService to update DailyProduction and create ProductionLog
            $productionService = app(\App\Services\ProductionService::class);
            $productionService->saveProductionLog($validated['job_master_id'], [
                'ok_qty' => 0,
                'repair_qty' => $validated['type'] === 'repair' ? $validated['qty_a'] : 0,
                'reject_qty' => $validated['type'] === 'reject' ? $validated['qty_a'] : 0,
            ], $workDate);

            return $log;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data ' . ucfirst($validated['type']) . ' berhasil disimpan.',
            'log'     => $log->load('images'),
        ]);
    }

    /**
     * Hapus satu log + gambarnya
     */
    public function destroy(int|string $id)
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
    public function getByJob(int|string $jobId)
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
    public function update(Request $request, int|string $id)
    {
        $log = RepairRejectLog::findOrFail($id);
        $this->guardLockedShift($log->job_master_id);
        $oldQty = $log->qty_a;

        $validated = $request->validate([
            'defect_name'    => 'required|string|max:255',
            'qty_a'          => 'required|numeric|min:1',
            'qty_b'         => 'nullable|numeric|min:0',
            'pcs_number'     => 'nullable|string|max:255',
            'sketch_no'      => 'nullable|string|max:100',
            'repair_category'=> 'nullable|string|max:100',
            'area_problem'   => 'required|string|max:255',
            'root_cause'     => 'required|string',
            'countermeasure' => 'required|string',
            'images'         => 'nullable|array',
            'images.*'       => 'nullable|file|image|max:5120',
        ]);

        if (!$request->hasFile('images') && $log->images()->count() === 0) {
            throw ValidationException::withMessages([
                'images' => 'Minimal 1 foto evidence Repair/Reject wajib diunggah.',
            ]);
        }

        $logData = array_diff_key($validated, ['images' => true]);

        DB::transaction(function () use ($request, $log, $validated, $logData, $oldQty) {
            $log->update($logData);

            // Required photo evidence is stored by saveUploadedImages()
            if ($request->hasFile('images')) {
                $this->saveUploadedImages($log, $request->file('images', []));
            }

            // Update DailyProduction / ProductionLog with the difference in qty
            $productionService = app(\App\Services\ProductionService::class);
            $qtyDiff = $validated['qty_a'] - $oldQty;
            $workDate = $request->get('date', now()->toDateString());

            if ($qtyDiff != 0) {
                $productionService->saveProductionLog($log->job_master_id, [
                    'ok_qty' => 0,
                    'repair_qty' => $log->type === 'repair' ? $qtyDiff : 0,
                    'reject_qty' => $log->type === 'reject' ? $qtyDiff : 0,
                ], $workDate);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data ' . ucfirst($log->type) . ' berhasil diperbarui.',
            'log'     => $log->fresh()->load('images'),
        ]);
    }
}
