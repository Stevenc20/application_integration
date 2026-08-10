<?php

namespace App\Http\Controllers\Qa\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ItemCheck;
use App\Models\LembarInspeksi;
use App\Models\Qpr;
use App\Models\QprAction;
use Carbon\Carbon;

class QualityDashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->query('bulan', now()->format('m'));
        $tahun = $request->query('tahun', now()->format('Y'));
        $hasFilter = $request->filled('bulan') || $request->filled('tahun');

        // ── ItemCheck dalam periode ──
        $query = ItemCheck::with('masterTemplate');
        if ($hasFilter) {
            if ($request->filled('bulan')) $query->whereMonth('tanggal', $bulan);
            if ($request->filled('tahun')) $query->whereYear('tanggal', $tahun);
        }
        $allData = $query->get();

        $totalLi = $allData->count();
        $totalProduksi = $allData->sum('total_produksi');
        $totalOk = $allData->where('judgement', 'OK')->count();
        $totalNg = $allData->where('judgement', 'NG')->count();
        $ngRate = $totalLi > 0 ? round(($totalNg / $totalLi) * 100, 2) : 0;
        $totalRepair = $allData->sum('repair');
        $totalReject = $allData->sum('reject');

        // ── Status ItemCheck ──
        $statusBreakdown = [
            'finished'  => $allData->whereIn('status', ['finished', 'approved', 'locked'])->count(),
            'progress'  => $allData->whereIn('status', ['in_progress', 'waiting_gl', 'waiting_foreman', 'waiting_supervisor', 'waiting_qc_approval', 'ready_for_qc', 'revision'])->count(),
            'pending'   => $allData->whereNotIn('status', ['finished', 'approved', 'locked', 'in_progress', 'waiting_gl', 'waiting_foreman', 'waiting_supervisor', 'waiting_qc_approval', 'ready_for_qc', 'revision'])->count(),
        ];

        // ── Breakdown per shift ──
        $perShift = [
            'Shift 1 (Pagi)'   => $allData->whereIn('shift', ['1', 'Shift 1', 'Shift Pagi', 'Pagi'])->count(),
            'Shift 2 (Malam)'  => $allData->whereIn('shift', ['2', 'Shift 2', 'Shift Malam', 'Malam', 'Shift Sore', 'Sore'])->count(),
        ];

        // ── Top Part ──
        $perPartRaw = $allData->groupBy(function ($item) {
            return $item->masterTemplate ? $item->masterTemplate->part_name : 'Unknown';
        })->map->count()->sortDesc();
        $topParts = $perPartRaw->take(5);

        // ── Agregasi Defect ──
        $defects = [];
        foreach ($allData as $item) {
            if ($item->judgement !== 'NG' || empty($item->ng_details)) continue;
            $ng = $item->ng_details;
            if (is_string($ng)) $ng = json_decode($ng, true);
            if (!is_array($ng)) continue;
            foreach ($ng as $detail) {
                if (is_array($detail) && !empty($detail['problems'])) {
                    $probs = is_array($detail['problems']) ? $detail['problems'] : [$detail['problems']];
                    foreach ($probs as $p) {
                        if (!empty($p)) $defects[$p] = ($defects[$p] ?? 0) + 1;
                    }
                } elseif (is_array($detail) && !empty($detail['problem'])) {
                    $p = $detail['problem'];
                    if (!empty($p)) $defects[$p] = ($defects[$p] ?? 0) + 1;
                }
            }
        }
        arsort($defects);
        $topDefects = array_slice($defects, 0, 5, true);

        // ── Trend mingguan ──
        $trendMingguan = [
            'Minggu 1' => ['OK' => 0, 'NG' => 0],
            'Minggu 2' => ['OK' => 0, 'NG' => 0],
            'Minggu 3' => ['OK' => 0, 'NG' => 0],
            'Minggu 4' => ['OK' => 0, 'NG' => 0],
            'Minggu 5' => ['OK' => 0, 'NG' => 0],
        ];
        foreach ($allData as $item) {
            if (!$item->tanggal) continue;
            $day = Carbon::parse($item->tanggal)->day;
            $week = min(ceil($day / 7), 5);
            $j = $item->judgement;
            if ($j === 'OK' || $j === 'NG') {
                $trendMingguan["Minggu {$week}"][$j]++;
            }
        }

        // ── QPR dalam periode ──
        $qprQuery = Qpr::with('actions');
        if ($hasFilter) {
            if ($request->filled('bulan')) $qprQuery->whereMonth('tanggal', $bulan);
            if ($request->filled('tahun')) $qprQuery->whereYear('tanggal', $tahun);
        }
        $qprs = $qprQuery->get();

        $qprOpen = $qprs->filter(function ($q) {
            return !in_array(strtolower($q->status), ['close', 'finished', 'selesai']);
        });
        $qprClosed = $qprs->count() - $qprOpen->count();
        $qprWaitingAction = $qprs->filter(function ($q) {
            return str_contains(strtolower((string) $q->status), 'action') || in_array(strtolower($q->status), ['open', 'progress', 'draft']);
        })->count();
        $qprWaitingVerif = $qprs->filter(function ($q) {
            return str_contains(strtolower((string) $q->status), 'verif') || str_contains(strtolower((string) $q->status), 'a3');
        })->count();

        $recentItemChecks = ItemCheck::with(['masterTemplate', 'operator'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $recentQprs = Qpr::orderBy('created_at', 'desc')->limit(6)->get();

        $bulanLabel = $this->bulanLabel($bulan);

        return view('qa.dashboard.index', compact(
            'bulan', 'tahun', 'bulanLabel',
            'totalLi', 'totalProduksi', 'totalOk', 'totalNg', 'ngRate', 'totalRepair', 'totalReject',
            'statusBreakdown', 'perShift', 'topParts', 'topDefects', 'trendMingguan',
            'qprs', 'qprOpen', 'qprClosed', 'qprWaitingAction', 'qprWaitingVerif',
            'recentItemChecks', 'recentQprs'
        ));
    }

    public function defectMonitoring(Request $request)
    {
        $bulan = $request->query('bulan', now()->format('m'));
        $tahun = $request->query('tahun', now()->format('Y'));

        $query = ItemCheck::with('masterTemplate')
            ->whereYear('tanggal', $tahun);
        if (!empty($bulan)) $query->whereMonth('tanggal', $bulan);
        $allData = $query->get();

        $totalLi = $allData->count();
        $totalNg = $allData->where('judgement', 'NG')->count();
        $ngRate = $totalLi > 0 ? round(($totalNg / $totalLi) * 100, 2) : 0;

        $defects = [];
        foreach ($allData as $item) {
            if ($item->judgement !== 'NG' || empty($item->ng_details)) continue;
            $ng = $item->ng_details;
            if (is_string($ng)) $ng = json_decode($ng, true);
            if (!is_array($ng)) continue;
            foreach ($ng as $detail) {
                if (is_array($detail) && !empty($detail['problems'])) {
                    $probs = is_array($detail['problems']) ? $detail['problems'] : [$detail['problems']];
                    foreach ($probs as $p) {
                        if (!empty($p)) $defects[$p] = ($defects[$p] ?? 0) + 1;
                    }
                } elseif (is_array($detail) && !empty($detail['problem'])) {
                    $p = $detail['problem'];
                    if (!empty($p)) $defects[$p] = ($defects[$p] ?? 0) + 1;
                }
            }
        }
        arsort($defects);
        $totalCases = array_sum($defects);

        $defectRows = [];
        foreach ($defects as $name => $count) {
            $defectRows[] = [
                'name'       => $name,
                'count'      => $count,
                'percentage' => $totalCases > 0 ? round(($count / $totalCases) * 100, 1) : 0,
            ];
        }

        $bulanLabel = $this->bulanLabel($bulan);

        return view('qa.dashboard.defect-monitoring', compact(
            'bulan', 'tahun', 'bulanLabel',
            'totalLi', 'totalNg', 'ngRate', 'totalCases', 'defectRows'
        ));
    }

    public function rejectAnalysis(Request $request)
    {
        $bulan = $request->query('bulan', now()->format('m'));
        $tahun = $request->query('tahun', now()->format('Y'));

        $query = ItemCheck::with('masterTemplate')
            ->whereYear('tanggal', $tahun);
        if (!empty($bulan)) $query->whereMonth('tanggal', $bulan);
        $allData = $query->get();

        $totalProduksi = $allData->sum('total_produksi');
        $totalReject = $allData->sum('reject');
        $totalRepair = $allData->sum('repair');
        $rejectRate = $totalProduksi > 0 ? round(($totalReject / $totalProduksi) * 100, 2) : 0;
        $repairRate = $totalProduksi > 0 ? round(($totalRepair / $totalProduksi) * 100, 2) : 0;

        // Agregasi reject/repair per part
        $partReject = [];
        foreach ($allData as $item) {
            if ((int) $item->reject <= 0 && (int) $item->repair <= 0) continue;
            $partName = $item->masterTemplate ? $item->masterTemplate->part_name : ($item->masterTemplate ? $item->masterTemplate->part_no : 'Unknown');
            $key = $item->masterTemplate
                ? trim($item->masterTemplate->part_name . ' (' . $item->masterTemplate->part_no . ')')
                : 'Unknown';
            if (!isset($partReject[$key])) $partReject[$key] = ['part' => $key, 'reject' => 0, 'repair' => 0];
            $partReject[$key]['reject'] += (int) $item->reject;
            $partReject[$key]['repair'] += (int) $item->repair;
        }
        usort($partReject, function ($a, $b) {
            return ($b['reject'] + $b['repair']) <=> ($a['reject'] + $a['repair']);
        });
        $partReject = array_slice($partReject, 0, 10);

        // Trend reject/repair per minggu
        $trend = [
            'Minggu 1' => ['reject' => 0, 'repair' => 0],
            'Minggu 2' => ['reject' => 0, 'repair' => 0],
            'Minggu 3' => ['reject' => 0, 'repair' => 0],
            'Minggu 4' => ['reject' => 0, 'repair' => 0],
            'Minggu 5' => ['reject' => 0, 'repair' => 0],
        ];
        foreach ($allData as $item) {
            if (!$item->tanggal) continue;
            $day = Carbon::parse($item->tanggal)->day;
            $week = min(ceil($day / 7), 5);
            $trend["Minggu {$week}"]['reject'] += (int) $item->reject;
            $trend["Minggu {$week}"]['repair'] += (int) $item->repair;
        }

        $bulanLabel = $this->bulanLabel($bulan);

        return view('qa.dashboard.reject-analysis', compact(
            'bulan', 'tahun', 'bulanLabel',
            'totalProduksi', 'totalReject', 'totalRepair', 'rejectRate', 'repairRate',
            'partReject', 'trend'
        ));
    }

    private function bulanLabel($bulan)
    {
        $map = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];
        return $map[$bulan] ?? $bulan;
    }
}
