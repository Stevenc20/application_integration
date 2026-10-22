<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\ScheduleBackup;
use App\Services\ScheduleBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleBackupController extends Controller
{
    public function __construct(protected ScheduleBackupService $backupService) {}

    public function index(Request $request)
    {
        $date = $request->get('date');
        $type = $request->get('type');

        $query = ScheduleBackup::with(['creator', 'restorer'])
            ->orderByDesc('id');

        if ($date) {
            $query->whereDate('plan_date', $date);
        }
        if ($type) {
            $query->where('backup_type', $type);
        }

        $backups = $query->paginate(20)->withQueryString();

        $availableDates = ProductionPlan::selectRaw('DATE(plan_date) as d')
            ->distinct()
            ->orderByDesc('d')
            ->limit(30)
            ->pluck('d');

        return view('ppc.planning.schedule_backups', [
            'backups' => $backups,
            'date' => $date ?? now()->format('Y-m-d'),
            'type' => $type,
            'selectedDate' => $date,
            'selectedType' => $type,
            'availableDates' => $availableDates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_date' => 'required|date',
            'label' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $backup = $this->backupService->captureSnapshot(
            $validated['plan_date'],
            'manual',
            Auth::id(),
            [
                'label' => $validated['label'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'allow_empty' => false,
            ]
        );

        if (! $backup) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data Production Plan pada tanggal tersebut untuk dibackup.',
                ], 422);
            }

            return back()->with('error', 'Tidak ada data Production Plan pada tanggal tersebut untuk dibackup.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'backup' => $backup,
            ]);
        }

        return redirect()
            ->route('ppc.planning.backups.show', $backup->id)
            ->with('success', "Snapshot backup #{$backup->id} berhasil dibuat.");
    }

    public function show(int $id)
    {
        $backup = ScheduleBackup::with(['creator', 'restorer', 'restoredFrom'])->findOrFail($id);
        $comparison = $this->backupService->compareWithLive($backup);

        return view('ppc.planning.schedule_backup_show', [
            'backup' => $backup,
            'comparison' => $comparison,
            'diff' => $comparison,
        ]);
    }

    public function compare(int $id)
    {
        $backup = ScheduleBackup::findOrFail($id);
        $comparison = $this->backupService->compareWithLive($backup);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'comparison' => $comparison,
            'diff' => $comparison,
        ]);
    }

    public function restore(Request $request, int $id)
    {
        $backup = ScheduleBackup::findOrFail($id);
        $pruneIdle = $request->boolean('prune_unlisted_idle', true);

        $result = $this->backupService->restoreSnapshot($backup, Auth::id(), $pruneIdle);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        }

        $msg = sprintf(
            'Jadwal tanggal %s berhasil di-restore dari Snapshot #%d (%d dipulihkan, %d diperbarui, %d item aktif dilindungi).',
            $result['plan_date'],
            $backup->id,
            $result['restored_count'],
            $result['updated_count'],
            $result['preserved_active_count']
        );

        return redirect()
            ->route('ppc.planning.production_plan', ['date' => $result['plan_date']])
            ->with('success', $msg);
    }
}
