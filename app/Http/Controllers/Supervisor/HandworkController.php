<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use App\Models\RepairRejectLog;
use App\Models\RepairRejectImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class HandworkController extends Controller
{
    public function index(Request $request)
    {
        $lines = LineMaster::select('line_name')->distinct()->pluck('line_name');

        $expandedPlanId = $request->input('plan_id');

        $tanggal = $request->input('tanggal', Carbon::today()->format('Y-m-d'));
        $lineName = $request->input('line');
        $shift = $request->input('shift');

        // Auto-fill filter from plan_id if no filter given
        if (!$lineName && !$shift && $expandedPlanId) {
            $plan = ProductionPlan::with('line')->find($expandedPlanId);
            if ($plan) {
                $tanggal = $plan->plan_date;
                $lineName = $plan->line?->line_name;
                $shift = $plan->shift_name ? (str_contains(strtolower($plan->shift_name), 'pagi') ? 'pagi' : 'malam') : null;
            }
        }

        $plans = collect();
        if ($lineName && $shift) {
            $lineMaster = LineMaster::where('line_name', $lineName)->first();
            if ($lineMaster) {
                $shiftName = $shift === 'pagi' ? 'Shift Pagi' : 'Shift Malam';
                $excludeKeywords = [
                    'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN',
                    'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT',
                    'TARGET GSPH', 'GSPH', 'TOTAL PCS',
                    'DELETE PLAN SHIFT 1', 'TOTAL'
                ];
                $plans = ProductionPlan::with('line')
                    ->where('line_master_id', $lineMaster->id)
                    ->where('plan_date', $tanggal)
                    ->where('shift_name', $shiftName)
                    ->where('row_type', 'job')
                    ->where(function ($q) use ($excludeKeywords) {
                        foreach ($excludeKeywords as $kw) {
                            $q->where(\DB::raw('UPPER(TRIM(COALESCE(job_master,job_no)))'), '!=', $kw);
                        }
                    })
                    ->orderBy('jam')
                    ->get();
            }
        }

        return view('supervisor.handwork.index', compact(
            'lines', 'tanggal', 'lineName', 'shift', 'plans', 'expandedPlanId'
        ));
    }

    public function getJobDetail($planId)
    {
        $plan = ProductionPlan::with('line')->findOrFail($planId);

        $jobMasters = JobMaster::where('job_number', 'like', $plan->job_no . '%')->get();
        $jobMasterIds = $jobMasters->pluck('id');

        $logs = RepairRejectLog::with(['images', 'creator'])
            ->whereIn('job_master_id', $jobMasterIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRepair = $logs->where('type', 'repair')->sum('qty_a');
        $totalReject = $logs->where('type', 'reject')->sum('qty_a');

        $items = $logs->map(function ($log) {
            $fotoSebelum = $log->images->where('image_type', 'before')->sortBy('id')->first();
            $fotoSesudah = $log->images->where('image_type', 'after')->sortBy('id')->first();

            return [
                'id'             => $log->id,
                'status'         => $log->type === 'repair' ? 'ok' : 'ng',
                'problem_hw'     => $log->defect_name ?? $log->area_problem ?? '-',
                'qty'            => $log->qty_a,
                'pcs_number'     => $log->pcs_number,
                'operator'       => $log->creator?->name ?? '-',
                'time'           => $log->created_at->format('d-m-Y H:i'),
                'foto_sebelum'   => $fotoSebelum ? asset('uploads/' . $fotoSebelum->image_path) : null,
                'foto_sesudah'   => $fotoSesudah ? asset('uploads/' . $fotoSesudah->image_path) : null,
            ];
        });

        $beforePhotos = $logs->flatMap(fn($l) => $l->images->where('image_type', 'before'))
            ->unique(fn($img) => $img->image_path)
            ->map(fn($img) => asset('uploads/' . $img->image_path))
            ->values()
            ->toArray();

        return response()->json([
            'plan_id'       => $plan->id,
            'job_no'        => $plan->job_master ?? $plan->job_no,
            'part_name'     => $plan->keterangan ?? '',
            'line_name'     => $plan->line->line_name ?? '',
            'total_repair'  => $totalRepair,
            'total_reject'  => $totalReject,
            'items'         => $items,
            'before_photos' => $beforePhotos,
        ]);
    }

    public function storeItem(Request $request)
    {
        \Log::info('Handwork storeItem called', [
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'all_input' => $request->all(),
            'has_file' => $request->hasFile('foto_sesudah'),
            'file_info' => $request->hasFile('foto_sesudah') ? $request->file('foto_sesudah')->getClientOriginalName() : 'none',
            'content_type' => $request->header('Content-Type'),
            'auth_user' => auth()->id(),
        ]);

        $validated = $request->validate([
            'plan_id'        => 'required|exists:production_plans,id',
            'problem_hw'     => 'required|string|max:255',
            'qty'            => 'required|numeric|min:1',
            'status'         => 'required|in:ok,ng',
            'pcs_number'     => 'nullable|string|max:255',
            'foto_sesudah'   => 'nullable|file|max:5120',
        ]);

        $plan = ProductionPlan::findOrFail($validated['plan_id']);
        $jobMaster = JobMaster::where('job_number', 'like', $plan->job_no . '%')->first();

        if (!$jobMaster) {
            return response()->json(['success' => false, 'message' => 'Job Master tidak ditemukan untuk job_no: ' . $plan->job_no], 404);
        }

        $type = $validated['status'] === 'ok' ? 'repair' : 'reject';

        $log = RepairRejectLog::create([
            'job_master_id'  => $jobMaster->id,
            'type'           => $type,
            'defect_name'    => $validated['problem_hw'],
            'qty_a'          => $validated['qty'],
            'qty_b'          => 0,
            'pcs_number'     => $validated['pcs_number'] ?? null,
            'created_by'     => auth()->id(),
        ]);

        if ($request->hasFile('foto_sesudah')) {
            try {
                $file = $request->file('foto_sesudah');
                $safeName = 'after_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $relPath = 'repair_reject/' . $log->id . '/' . $safeName;
                $absPath = public_path('uploads/' . $relPath);
                $dir = dirname($absPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $copied = copy($file->getRealPath(), $absPath);
                if (!$copied) {
                    throw new \RuntimeException("copy() returned false for: $absPath");
                }
                if (!file_exists($absPath)) {
                    throw new \RuntimeException("copy() reported success but file not found at: $absPath");
                }
                chmod($absPath, 0644);
                RepairRejectImage::create([
                    'repair_reject_log_id' => $log->id,
                    'image_path'           => $relPath,
                    'image_type'           => 'after',
                ]);
            } catch (\Throwable $e) {
                \Log::error('HW store image failed: ' . $e->getMessage() . ' | file: ' . ($file->getClientOriginalName() ?? 'unknown') . ' | tmp: ' . ($file->getRealPath() ?? 'N/A'));
            }
        }

        $productionService = app(\App\Services\ProductionService::class);
        $productionService->saveProductionLog($jobMaster->id, [
            'ok_qty'     => 0,
            'repair_qty' => $type === 'repair' ? $validated['qty'] : 0,
            'reject_qty' => $type === 'reject' ? $validated['qty'] : 0,
        ], now()->toDateString());

        return response()->json(['success' => true, 'message' => 'Catatan handwork berhasil disimpan.']);
    }

    public function deleteItem($id)
    {
        $log = RepairRejectLog::with('images')->findOrFail($id);

        foreach ($log->images as $img) {
            $absPath = public_path('uploads/' . $img->image_path);
            if (file_exists($absPath)) {
                unlink($absPath);
            }
            $img->delete();
        }
        $log->delete();

        return response()->json(['success' => true, 'message' => 'Catatan handwork berhasil dihapus.']);
    }
}
