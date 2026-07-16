<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ProductionDataTrash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RecycleBinController extends Controller
{
    private array $tableLabels = [
        'daily_productions' => 'Daily Productions',
        'production_plans' => 'Production Plans',
        'job_masters' => 'Job Masters',
        'downtimes' => 'Downtimes',
        'hambatan_jalur' => 'Hambatan Jalur',
        'repair_reject_logs' => 'Repair & Reject Logs',
        'repair_reject_images' => 'Repair Reject Images',
        'dandoris' => 'Dandori',
        'dandori_sessions' => 'Dandori Sessions',
        'dandori_groups' => 'Dandori Groups',
        'dandori_details' => 'Dandori Details',
        'production_sessions' => 'Production Sessions',
        'production_logs' => 'Production Logs',
        'machine_logs' => 'Machine Logs',
    ];

    public function index()
    {
        $trash = ProductionDataTrash::active()
            ->orderBy('trashed_at', 'desc')
            ->paginate(20);

        $stats = $this->buildStats();

        return view('super_admin.recycle_bin.index', compact('trash', 'stats'));
    }

    public function stats()
    {
        return response()->json($this->buildStats());
    }

    public function restore($id)
    {
        $trash = ProductionDataTrash::active()->findOrFail($id);
        $data = $trash->data;

        try {
            DB::transaction(function () use ($trash, $data) {
                DB::table($trash->original_table)->updateOrInsert(
                    ['id' => $trash->original_id],
                    array_merge($data, ['updated_at' => now()])
                );

                $trash->delete();
            });

            return redirect()->back()->with('success', 'Data berhasil direstore.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal restore: ' . $e->getMessage());
        }
    }

    public function forceDelete($id)
    {
        $trash = ProductionDataTrash::active()->findOrFail($id);

        try {
            $trash->update(['deleted_at' => now()]);
            $trash->delete();

            return redirect()->back()->with('success', 'Data berhasil dihapus permanen.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    public function restoreAll()
    {
        $items = ProductionDataTrash::active()->get();
        $count = 0;

        foreach ($items as $trash) {
            try {
                $data = $trash->data;
                DB::transaction(function () use ($trash, $data) {
                    DB::table($trash->original_table)->updateOrInsert(
                        ['id' => $trash->original_id],
                        array_merge($data, ['updated_at' => now()])
                    );
                    $trash->delete();
                });
                $count++;
            } catch (\Exception) {
                continue;
            }
        }

        return redirect()->back()->with('success', "{$count} data berhasil direstore.");
    }

    private function buildStats(): array
    {
        $totalActive = ProductionDataTrash::active()->count();
        $totalExpired = ProductionDataTrash::expired()->count();

        $perTable = ProductionDataTrash::active()
            ->selectRaw('original_table, COUNT(*) as total')
            ->groupBy('original_table')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'original_table');

        $labels = [];
        $values = [];
        foreach ($perTable as $table => $count) {
            $labels[] = $this->tableLabels[$table] ?? $table;
            $values[] = $count;
        }

        $monthly = ProductionDataTrash::active()
            ->selectRaw("DATE_FORMAT(trashed_at, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        $expiringSoon = ProductionDataTrash::active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>', now())
            ->count();

        return [
            'total_active' => $totalActive,
            'total_expired' => $totalExpired,
            'expiring_soon' => $expiringSoon,
            'chart_labels' => $labels,
            'chart_values' => $values,
            'monthly_labels' => $monthly->keys()->toArray(),
            'monthly_values' => $monthly->values()->toArray(),
            'expired_count' => $totalExpired,
            'active_count' => $totalActive - $totalExpired,
            'table_labels' => $this->tableLabels,
        ];
    }
}
