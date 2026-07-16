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

        // --- CHART DATA (FINISH PART FROM RUNDOWN STOCK) ---
        $fpQuery = \App\Models\RundownStock::where('type_of_part', 'FINISH PART');
        $hasFpData = $fpQuery->count() > 0;
        
        $fpPerCustomer = [];
        $fpRemarksData = [];
        $fpProsesData = [];
        $fpStrengthAvg = [];
        $totalOverFP = 0;
        $totalLimitedFP = 0;
        $totalZeroFP = 0;
        $totalAllFinish = 0;

        if ($hasFpData) {
            $fpPerCustomer = (clone $fpQuery)->select(
                'customer',
                DB::raw('SUM(CASE WHEN strength > 2 THEN 1 ELSE 0 END) as over_stock'),
                DB::raw('SUM(CASE WHEN strength > 0 AND strength <= 2 THEN 1 ELSE 0 END) as limited'),
                DB::raw('SUM(CASE WHEN strength <= 0 THEN 1 ELSE 0 END) as zero_stock'),
                DB::raw('COUNT(*) as total')
            )->groupBy('customer')->orderBy('customer')->get();

            $fpOverCount = (clone $fpQuery)->where('strength', '>', 2)->count();
            $fpLimitedCount = (clone $fpQuery)->where('strength', '>', 0)->where('strength', '<=', 2)->count();
            $fpZeroCount = (clone $fpQuery)->where('strength', '<=', 0)->count();

            $fpRemarksData = collect([
                (object)['remarks' => 'OVER STOCK', 'total' => $fpOverCount],
                (object)['remarks' => 'LIMITED', 'total' => $fpLimitedCount],
                (object)['remarks' => 'ZERO STOCK', 'total' => $fpZeroCount],
            ]);

            $fpProsesData = (clone $fpQuery)->select(DB::raw('source as proses'), DB::raw('COUNT(*) as total'))
                ->groupBy('source')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $fpStrengthAvg = (clone $fpQuery)->select('customer', DB::raw('AVG(strength) as avg_strength'))
                ->groupBy('customer')
                ->orderBy('customer')
                ->get();

            $totalOverFP    = $fpOverCount;
            $totalLimitedFP = $fpLimitedCount;
            $totalZeroFP    = $fpZeroCount;
            $totalAllFinish = (clone $fpQuery)->count();
        }

        // --- SINGLE PART DATA FROM RUNDOWN STOCK ---
        $spQuery = \App\Models\RundownStock::where('type_of_part', 'SINGLE PART');
        $hasSpData = $spQuery->count() > 0;
        
        $spPerCustomer = [];
        $spRemarksData = [];
        $spProsesData = [];
        $spStrengthAvg = [];
        $totalOverSP = 0;
        $totalLimitedSP = 0;
        $totalZeroSP = 0;
        $totalAllSingle = 0;

        if ($hasSpData) {
            $spPerCustomer = (clone $spQuery)->select(
                'customer',
                DB::raw('SUM(CASE WHEN strength > 2 THEN 1 ELSE 0 END) as over_stock'),
                DB::raw('SUM(CASE WHEN strength > 0 AND strength <= 2 THEN 1 ELSE 0 END) as limited'),
                DB::raw('SUM(CASE WHEN strength <= 0 THEN 1 ELSE 0 END) as zero_stock'),
                DB::raw('COUNT(*) as total')
            )->groupBy('customer')->orderBy('customer')->get();

            $spOverCount = (clone $spQuery)->where('strength', '>', 2)->count();
            $spLimitedCount = (clone $spQuery)->where('strength', '>', 0)->where('strength', '<=', 2)->count();
            $spZeroCount = (clone $spQuery)->where('strength', '<=', 0)->count();

            $spRemarksData = collect([
                (object)['remarks' => 'OVER STOCK', 'total' => $spOverCount],
                (object)['remarks' => 'LIMITED', 'total' => $spLimitedCount],
                (object)['remarks' => 'ZERO STOCK', 'total' => $spZeroCount],
            ]);

            $spProsesData = (clone $spQuery)->select(DB::raw('source as proses'), DB::raw('COUNT(*) as total'))
                ->groupBy('source')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $spStrengthAvg = (clone $spQuery)->select('customer', DB::raw('AVG(strength) as avg_strength'))
                ->groupBy('customer')
                ->orderBy('customer')
                ->get();

            $totalOverSP    = $spOverCount;
            $totalLimitedSP = $spLimitedCount;
            $totalZeroSP    = $spZeroCount;
            $totalAllSingle = (clone $spQuery)->count();
        }

        // --- MATERIAL READINESS PER TODAY ---
        $months = ['','JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        $todayStr = date('d') . ' ' . $months[(int)date('m')] . ' ' . date('Y');
        
        $latestStampingDate = $todayStr;

        $totalStampingPlan = \App\Models\ScheduleStamping::where('upload_date', $latestStampingDate)
            ->where('row_type', 'job')
            ->sum('plan');

        $todayJobs = \App\Models\ScheduleStamping::where('upload_date', $latestStampingDate)
            ->where('row_type', 'job')
            ->get();
        $totalJobsCount = $todayJobs->count();
        
        $todayJobNumbers = $todayJobs->pluck('job_no')->filter()->map(fn($v) => strtoupper(trim($v)))->unique()->toArray();
        
        $readyJobsCount = 0;
        if ($totalJobsCount > 0 && !empty($todayJobNumbers)) {
            $readyJobNumbersInRundown = \App\Models\RundownStock::whereIn(DB::raw('UPPER(TRIM(job_no))'), $todayJobNumbers)
                ->where('strength', '>', 0)
                ->pluck('job_no')
                ->map(fn($v) => strtoupper(trim($v)))
                ->toArray();
                
            $readyJobsCount = $todayJobs->filter(function($job) use ($readyJobNumbersInRundown) {
                return in_array(strtoupper(trim($job->job_no)), $readyJobNumbersInRundown);
            })->count();
        }

        return view('index', compact(
            'summary', 'inventoryLevel', 'inhouseProses', 'subcontProses', 'hasData',
            'latestStampingDate', 'totalStampingPlan', 'readyJobsCount', 'totalJobsCount',
            
            'hasFpData', 'fpPerCustomer', 'fpRemarksData', 'fpProsesData', 'fpStrengthAvg', 
            'totalOverFP', 'totalLimitedFP', 'totalZeroFP', 'totalAllFinish',
            
            'hasSpData', 'spPerCustomer', 'spRemarksData', 'spProsesData', 'spStrengthAvg',
            'totalOverSP', 'totalLimitedSP', 'totalZeroSP', 'totalAllSingle'
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

            $scriptPath = base_path('python/read_xlsm.py');
            if (!file_exists($scriptPath)) return back()->with('error', 'Script python/read_xlsm.py tidak ditemukan.');

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
            $rundownRows = array_values(array_filter($rundownRows, function($r) {
                return strtoupper(trim($r['proses'] ?? '')) !== 'SUB-ASSY';
            }));
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

            // Simpan STOCK PALLET SUBCONT → tabel pallet_mutations
            $palletRows = $result['pallet']['data'] ?? [];
            if (!empty($palletRows)) {
                $insertPallet = array_map(fn($p) => [
                    'no'            => (int)$p['no'],
                    'month'         => $p['month'],
                    'vendor'        => $p['vendor'],
                    'type_pallet'   => $p['type_pallet'],
                    'type'          => $p['type'],
                    'initial_stock' => (int)$p['initial_stock'],
                    'pallet_in'     => (int)$p['pallet_in'],
                    'pallet_out'    => (int)$p['pallet_out'],
                    'final_stock'   => (int)$p['final_stock'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], $palletRows);

                DB::transaction(function () use ($insertPallet) {
                    \App\Models\PalletMutation::truncate();
                    foreach (array_chunk($insertPallet, 100) as $chunk) \App\Models\PalletMutation::insert($chunk);
                });
            }

            // Simpan SMR VENDOR → tabel smr_vendors
            $smrRows = $result['smr_vendor']['data'] ?? [];
            if (!empty($smrRows)) {
                $insertSmr = array_map(fn($s) => [
                    'no'             => (int)$s['no'],
                    'month'          => $s['month'],
                    'vendor'         => $s['vendor'],
                    'no_smr'         => $s['no_smr'],
                    'part_name'      => $s['part_name'],
                    'qty'            => (int)$s['qty'],
                    'problem'        => $s['problem'],
                    'tanggal_keluar' => $s['tanggal_keluar'],
                    'tanggal_masuk'  => $s['tanggal_masuk'],
                    'qty_pengganti'  => (int)$s['qty_pengganti'],
                    'status_barang'  => $s['status_barang'],
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ], $smrRows);

                DB::transaction(function () use ($insertSmr) {
                    \App\Models\SmrVendor::truncate();
                    foreach (array_chunk($insertSmr, 100) as $chunk) \App\Models\SmrVendor::insert($chunk);
                });
            }

            // Simpan SMR CUSTOMER → tabel smr_customers
            $smrCustRows = $result['smr_customer']['data'] ?? [];
            if (!empty($smrCustRows)) {
                $insertSmrCust = array_map(fn($sc) => [
                    'no'               => (int)$sc['no'],
                    'year'             => (int)$sc['year'],
                    'date'             => $sc['date'],
                    'month'            => $sc['month'],
                    'quarterly'        => $sc['quarterly'],
                    'no_smr'           => $sc['no_smr'],
                    'job_no'           => $sc['job_no'],
                    'part_number'      => $sc['part_number'],
                    'part_name'        => $sc['part_name'],
                    'qty_smr'          => (int)$sc['qty_smr'],
                    'total_production' => (int)$sc['total_production'],
                    'cost_rijection'   => (float)$sc['cost_rijection'],
                    'rijection_rate'   => (float)$sc['rijection_rate'],
                    'customer'         => $sc['customer'],
                    'problem'          => $sc['problem'],
                    'countermeasures'  => $sc['countermeasures'],
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ], $smrCustRows);

                DB::transaction(function () use ($insertSmrCust) {
                    \App\Models\SmrCustomer::truncate();
                    foreach (array_chunk($insertSmrCust, 100) as $chunk) \App\Models\SmrCustomer::insert($chunk);
                });
            }

            // Simpan DATA GR → tabel data_grs
            $grRows = $result['data_gr']['data'] ?? [];
            if (!empty($grRows)) {
                $insertGr = array_map(fn($gr) => [
                    'gr_status'     => $gr['gr_status'],
                    'po_number'     => $gr['po_number'],
                    'job_number'    => $gr['job_number'],
                    'material'      => $gr['material'],
                    'vendor_name'   => $gr['vendor_name'],
                    'qty'           => (int)$gr['qty'],
                    'dn_number'     => $gr['dn_number'],
                    'kanban_number' => $gr['kanban_number'],
                    'gr_number_edn' => $gr['gr_number_edn'],
                    'dn_date'       => $gr['dn_date'],
                    'gr_date'       => $gr['gr_date'],
                    'gr_number_sap' => $gr['gr_number_sap'],
                    'sap_message'   => $gr['sap_message'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], $grRows);

                DB::transaction(function () use ($insertGr) {
                    \App\Models\DataGr::truncate();
                    foreach (array_chunk($insertGr, 100) as $chunk) \App\Models\DataGr::insert($chunk);
                });
            }

            // Simpan DATA SCRAP → tabel data_scraps
            $scrapRows = $result['data_scrap']['data'] ?? [];
            if (!empty($scrapRows)) {
                $insertScrap = array_map(fn($scr) => [
                    'no'               => (int)$scr['no'],
                    'year'             => (int)$scr['year'],
                    'month'            => $scr['month'],
                    'ba_no'            => $scr['ba_no'],
                    'job_no'           => $scr['job_no'],
                    'sourch_1'         => $scr['sourch_1'],
                    'part_number'      => $scr['part_number'],
                    'part_name'        => $scr['part_name'],
                    'sourch_2'         => $scr['sourch_2'],
                    'customer'         => $scr['customer'],
                    'qty'              => (int)$scr['qty'],
                    'value'            => (float)$scr['value'],
                    'total_production' => (int)$scr['total_production'],
                    'reject_rate'      => (float)$scr['reject_rate'],
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ], $scrapRows);

                DB::transaction(function () use ($insertScrap) {
                    \App\Models\DataScrap::truncate();
                    foreach (array_chunk($insertScrap, 100) as $chunk) \App\Models\DataScrap::insert($chunk);
                });
            }

            @unlink($fullPath);

            return redirect('/')->with('success',
                'Import berhasil! ' . count($spreedRows) . ' item (Dashboard), ' . count($rundownRows) . ' item (Rundown), ' . count($palletRows) . ' item (Pallet), ' . count($smrRows) . ' item (SMR Vendor), ' . count($smrCustRows) . ' item (SMR Customer), ' . count($grRows) . ' item (Data GR), & ' . count($scrapRows) . ' item (Data Scrap) dimuat.');

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