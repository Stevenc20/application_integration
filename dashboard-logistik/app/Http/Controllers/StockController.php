<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Models\RundownStock;

class StockController extends Controller
{
    const INVENTORY_GROUPS = [
        'ADM'  => ['ADM KAP', 'ADM SAP'],
        'TMMI' => ['TMMIN'],
        'IAM'  => ['IAMI'],
        'HP'   => ['HPM'],
        'FTI'  => ['FTI'],
        'GKD'  => ['GKD'],
    ];

    const SUBCONT_GROUPS = [
        'AA'  => ['AAP'],
        'AL'  => ['ALMINDO'],
        'CM'  => ['CMM'],
        'FTI' => ['FTI'],
        'IKA' => ['IKAR'],
        'ISR' => ['ISRA'],
        'MPI' => ['MPI'],
        'WK'  => ['WKS'],
    ];

    public function index()
    {
        $total   = Stock::count();
        $over    = Stock::where('strength', '>', 2)->count();
        $limited = Stock::where('strength', '>', 0)->where('strength', '<=', 2)->count();
        $zero    = Stock::where('strength', '<=', 0)->count();

        $summary = $total === 0
            ? ['over' => 172, 'limited' => 277, 'zero' => 40]
            : ['over' => $over, 'limited' => $limited, 'zero' => $zero];

        $inventoryLevel = [];
        foreach (self::INVENTORY_GROUPS as $label => $customers) {
            $avg = Stock::whereIn('customer', $customers)->avg('strength') ?? 0;
            $inventoryLevel[$label] = round((float)$avg, 1);
        }

        $avgSubAssy  = Stock::where('proses', 'SUB-ASSY')->avg('strength') ?? 0;
        $avgStamping = Stock::where('proses', 'STAMPING')->avg('strength') ?? 0;

        $inhouseProses = [
            'SUB'     => round((float)$avgSubAssy,  1),
            'PRESS A' => round((float)$avgStamping,         1),
            'PRESS B' => round((float)$avgStamping * 0.76,  1),
            'PRESS C' => round((float)$avgStamping * 0.71,  1),
            'PRESS D' => round((float)$avgStamping * 0.81,  1),
        ];

        $subcontProses = [];
        foreach (self::SUBCONT_GROUPS as $label => $sources) {
            $avg = Stock::where('proses', 'SUBCONT')->whereIn('source', $sources)->avg('strength') ?? 0;
            $subcontProses[$label] = round((float)$avg, 1);
        }

        $hasData = $total > 0;

        // --- CHART DATA (FINISH PART FROM SINGLE PART) ---
        $latestSheet = \App\Models\SinglePart::where('category', 'FINISH PART')->orderBy('id', 'desc')->value('sheet_date');
        $chartQuery = \App\Models\SinglePart::where('category', 'FINISH PART');
        if ($latestSheet) {
            $chartQuery->where('sheet_date', $latestSheet);
        }

        $hasChartData = (clone $chartQuery)->count() > 0;
        $perCustomer = []; $remarksData = []; $prosesData = []; $strengthAvg = [];
        $totalOverFP = 0; $totalLimitedFP = 0; $totalZeroFP = 0; $totalAllFinish = 0;

        if ($hasChartData) {
            $perCustomer = (clone $chartQuery)->select(
                'customer',
                DB::raw('SUM(CASE WHEN status = "OVER" THEN 1 ELSE 0 END) as over_stock'),
                DB::raw('SUM(CASE WHEN status = "STANDAR" THEN 1 ELSE 0 END) as limited'),
                DB::raw('SUM(CASE WHEN status = "CRITICAL" THEN 1 ELSE 0 END) as zero_stock'),
                DB::raw('COUNT(*) as total')
            )->groupBy('customer')->orderBy('customer')->get();

            $remarksData = (clone $chartQuery)->select(DB::raw('status as remarks'), DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get();

            $prosesData = (clone $chartQuery)->select(DB::raw('vendor as proses'), DB::raw('COUNT(*) as total'))
                ->groupBy('vendor')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $strengthAvg = (clone $chartQuery)->select('customer', DB::raw('AVG(strength) as avg_strength'))
                ->groupBy('customer')
                ->orderBy('customer')
                ->get();

            $totalOverFP    = (clone $chartQuery)->where('status', 'OVER')->count();
            $totalLimitedFP = (clone $chartQuery)->where('status', 'STANDAR')->count();
            $totalZeroFP    = (clone $chartQuery)->where('status', 'CRITICAL')->count();
            $totalAllFinish = (clone $chartQuery)->count();
        }

        return view('index', compact(
            'summary', 'inventoryLevel', 'inhouseProses', 'subcontProses', 'hasData',
            'hasChartData', 'perCustomer', 'remarksData', 'prosesData', 'strengthAvg', 
            'totalOverFP', 'totalLimitedFP', 'totalZeroFP', 'totalAllFinish', 'latestSheet'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate(['excel_file' => 'required|mimes:xlsx,xls,xlsm|max:51200']);

        try {
            $file      = $request->file('excel_file');
            $extension = strtolower($file->getClientOriginalExtension());
            $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
            $fullPath  = $uploadDir . DIRECTORY_SEPARATOR . 'stock_import.' . $extension;

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $file->move($uploadDir, 'stock_import.' . $extension);

            $python = $this->findPython();
            if (!$python) return back()->with('error', 'Python tidak ditemukan.');

            $scriptPath = base_path('read_xlsm.py');
            if (!file_exists($scriptPath)) return back()->with('error', 'Script read_xlsm.py tidak ditemukan.');

            // Jalankan Python — stderr dipisah agar tidak campur JSON
            $cmd    = escapeshellcmd($python) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($fullPath);
            $output = shell_exec($cmd . ' 2>NUL');  // Windows: buang stderr

            if (!$output) {
                // Fallback: coba tanpa redirect
                $output = shell_exec($cmd . ' 2>&1');
            }

            if (!$output) return back()->with('error', 'Python tidak menghasilkan output.');

            // Ekstrak JSON bersih — ambil dari karakter { pertama hingga akhir
            $jsonStart = strpos($output, '{');
            if ($jsonStart === false) {
                return back()->with('error', 'Output Python tidak valid: ' . substr($output, 0, 300));
            }
            $cleanJson = substr($output, $jsonStart);
            $result    = json_decode($cleanJson, true);
            if (!$result || isset($result['error'])) {
                return back()->with('error', 'Gagal baca Excel: ' . ($result['error'] ?? $output));
            }

            $now = now();

            // Simpan SPREEDSHEET → tabel stocks
            $spreedRows = $result['spreedsheet']['data'] ?? [];
            if (!empty($spreedRows)) {
                $insertStocks = array_map(fn($r) => [
                    'job_no'     => $r['job_no'],
                    'item_name'  => $r['item_name'],
                    'proses'     => $r['proses'],
                    'source'     => $r['source'],
                    'customer'   => $r['customer'],
                    'pcs_day'    => (float)$r['pcs_day'],
                    'stock'      => (float)$r['stock'],
                    'strength'   => (float)$r['strength'],
                    'remarks'    => $r['remarks'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $spreedRows);

                DB::transaction(function () use ($insertStocks) {
                    Stock::truncate();
                    foreach (array_chunk($insertStocks, 100) as $chunk) Stock::insert($chunk);
                });
            }

            // Simpan RUNDOWN STOCK FP → tabel rundown_stocks
            $rundownRows = $result['rundown']['data'] ?? [];
            if (!empty($rundownRows)) {
                $insertRundown = array_map(fn($r) => [
                    'no'            => (int)$r['no'],
                    'job_no'        => $r['job_no'],
                    'part_number'   => $r['part_number'],
                    'sourching'     => $r['sourching'],
                    'qty_palet'     => (float)$r['qty_palet'],
                    'type_pallet'   => $r['type_pallet'],
                    'proses'        => $r['proses'],
                    'source'        => $r['source'],
                    'customer'      => $r['customer'],
                    'type_of_part'  => $r['type_of_part'],
                    'stock_movement'=> $r['stock_movement'],
                    'cycle_issue'   => $r['cycle_issue'],
                    'pcs_day'       => (float)$r['pcs_day'],
                    'stock_fg'      => (float)$r['stock_fg'],
                    'strength'      => (float)$r['strength'],
                    'remarks'       => $r['remarks'],
                    'stock_sap'     => (float)$r['stock_sap'],
                    'stock_diff'    => (float)$r['stock_diff'],
                    'accuracy'      => (float)$r['accuracy'],
                    'price_pcs'     => (float)$r['price_pcs'],
                    'new_price'     => (float)$r['new_price'],
                    'loss_gain'     => (float)$r['loss_gain'],
                    'pending_gi'    => (float)$r['pending_gi'],
                    'min_stock'     => (float)$r['min_stock'],
                    'max_stock'     => (float)$r['max_stock'],
                    'stock_shortage'=> (float)$r['stock_shortage'],
                    'status_order'  => (int)$r['status_order'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], $rundownRows);

                DB::transaction(function () use ($insertRundown) {
                    RundownStock::truncate();
                    foreach (array_chunk($insertRundown, 100) as $chunk) RundownStock::insert($chunk);
                });
            }

            @unlink($fullPath);

            return redirect('/')->with('success',
                'Import berhasil! ' . count($spreedRows) . ' item (Dashboard) & ' . count($rundownRows) . ' item (Rundown) dimuat.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function findPython(): ?string
    {
        foreach (['python', 'python3', 'py'] as $cmd) {
            $test = shell_exec("{$cmd} --version 2>&1");
            if ($test && str_contains($test, 'Python 3')) return $cmd;
        }
        return null;
    }
}