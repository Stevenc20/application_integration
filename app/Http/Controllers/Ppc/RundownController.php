<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RundownStock;

class RundownController extends Controller
{
    public function index(Request $request)
    {
        $search         = trim($request->get('search', ''));
        $filterCustomer = $request->get('customer', '');
        $filterProses   = $request->get('proses', '');
        $filterRemarks  = $request->get('remarks', '');
        $filterType     = $request->get('type_of_part', '');
        $filterMovement = $request->get('stock_movement', '');
        $sortBy         = $request->get('sort', 'no');
        $sortDir        = $request->get('dir', 'asc');

        $allowed = ['no','job_no','part_number','customer','proses','source',
                    'pcs_day','stock_fg','strength','remarks','stock_sap',
                    'stock_diff','accuracy','stock_movement','type_of_part'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'no';
        if (!in_array($sortDir, ['asc','desc'])) $sortDir = 'asc';

        // Delete any existing SUB-ASSY rows from RundownStock
        RundownStock::where('proses', 'SUB-ASSY')->delete();

        $monthMap = ['JANUARI'=>1,'FEBRUARI'=>2,'MARET'=>3,'APRIL'=>4,
            'MEI'=>5,'JUNI'=>6,'JULI'=>7,'AGUSTUS'=>8,'SEPTEMBER'=>9,
            'OKTOBER'=>10,'NOVEMBER'=>11,'DESEMBER'=>12];
        $sortDateDesc = function($s) use ($monthMap) {
            $parts = explode(' ', trim($s));
            return ($monthMap[strtoupper($parts[1]??'')]??0) * 100 + (int)($parts[0]??0);
        };

        // SYNC: Update SUBCONT rows in RundownStock with latest data from RundownIncoming
        // Use proper numeric date sort (string MAX() is broken for cross-month)
        $latestIncDate = \App\Models\RundownIncoming::pluck('sheet_date')->unique()
            ->sortByDesc($sortDateDesc)->first();
        $latestIncomings = \App\Models\RundownIncoming::where('sheet_date', $latestIncDate)
            ->get()->keyBy(function($item) { return strtoupper(trim($item->job_no)); });

        $subcontStocks = RundownStock::where('proses', 'SUBCONT')->get();
        $seenSubcont = [];
        foreach ($subcontStocks as $rs) {
            $jobNoUpper = strtoupper(trim($rs->job_no));
            if (!isset($latestIncomings[$jobNoUpper]) || isset($seenSubcont[$jobNoUpper])) {
                $rs->delete();
                continue;
            }
            $seenSubcont[$jobNoUpper] = true;
            $inc = $latestIncomings[$jobNoUpper];
            
            $needsUpdate = false;
            if ($rs->job_no !== $inc->job_no) { $rs->job_no = $inc->job_no; $needsUpdate = true; }
            if ($rs->pcs_day != $inc->pcs_day) { $rs->pcs_day = $inc->pcs_day; $needsUpdate = true; }
            if ($rs->stock_fg != $inc->stok_akhir) { $rs->stock_fg = $inc->stok_akhir; $needsUpdate = true; }
            
            $strength = $rs->pcs_day > 0 ? ($rs->stock_fg / $rs->pcs_day) : 0;
            $newMovement = $strength > 0.5 ? 'FAST MOVING' : 'SLOW MOVING';
            if ($rs->stock_movement !== $newMovement) { $rs->stock_movement = $newMovement; $needsUpdate = true; }
            
            if ($needsUpdate) {
                $rs->strength = round($strength, 1);
                $rs->save();
            }
        }

        $maxNo = RundownStock::max('no') ?? 0;

        // INSERT missing SUBCONT
        foreach ($latestIncomings as $jobNoUpper => $inc) {
            if (!isset($seenSubcont[$jobNoUpper])) {
                $strength = $inc->pcs_day > 0 ? ($inc->stok_akhir / $inc->pcs_day) : 0;
                RundownStock::create([
                    'job_no' => $inc->job_no,
                    'proses' => 'SUBCONT',
                    'customer' => $inc->customer ?? '',
                    'source' => $inc->vendor ?? '',
                    'type_of_part' => $inc->category ?? '',
                    'pcs_day' => $inc->pcs_day ?? 0,
                    'stock_fg' => $inc->stok_akhir ?? 0,
                    'strength' => round($strength, 1),
                    'stock_movement' => $strength > 0.5 ? 'FAST MOVING' : 'SLOW MOVING',
                    'no' => ++$maxNo,
                ]);
            }
        }

        // SYNC: Update STAMPING rows in RundownStock with latest data from RundownPress
        // Use proper numeric date sort (string MAX() is broken for cross-month)
        $latestDate = \App\Models\RundownPress::pluck('sheet_date')->unique()
            ->sortByDesc($sortDateDesc)->first();
        $latestPress = \App\Models\RundownPress::where('sheet_date', $latestDate)
            ->get()->keyBy(function($item) { return strtoupper(trim($item->job_no)); });

        $stampingStocks = RundownStock::where('proses', 'STAMPING')->get();
        $seenStamping = [];
        foreach ($stampingStocks as $rs) {
            $jobNoUpper = strtoupper(trim($rs->job_no));
            if (!isset($latestPress[$jobNoUpper]) || isset($seenStamping[$jobNoUpper])) {
                $rs->delete();
                continue;
            }
            $seenStamping[$jobNoUpper] = true;
            $press = $latestPress[$jobNoUpper];
            
            $needsUpdate = false;
            if ($rs->job_no !== $press->job_no) { $rs->job_no = $press->job_no; $needsUpdate = true; }
            if ($rs->pcs_day != $press->pcs_day) { $rs->pcs_day = $press->pcs_day; $needsUpdate = true; }
            if ($rs->stock_fg != $press->stok_akhir) { $rs->stock_fg = $press->stok_akhir; $needsUpdate = true; }
            
            $strength = $rs->pcs_day > 0 ? ($rs->stock_fg / $rs->pcs_day) : 0;
            $newMovement = $strength > 0.5 ? 'FAST MOVING' : 'SLOW MOVING';
            if ($rs->stock_movement !== $newMovement) { $rs->stock_movement = $newMovement; $needsUpdate = true; }
            
            if ($needsUpdate) {
                $rs->strength = round($strength, 1);
                $rs->save();
            }
        }

        // INSERT missing STAMPING
        foreach ($latestPress as $jobNoUpper => $press) {
            if (!isset($seenStamping[$jobNoUpper])) {
                $strength = $press->pcs_day > 0 ? ($press->stok_akhir / $press->pcs_day) : 0;
                
                $customer = '';
                if ($press->kap > 0) $customer = 'ADM KAP';
                elseif ($press->sap > 0) $customer = 'ADM SAP';
                elseif ($press->gkd > 0) $customer = 'GKD';
                elseif ($press->iami > 0) $customer = 'IAMI';
                elseif ($press->gmo > 0) $customer = 'GMO';

                RundownStock::create([
                    'job_no' => $press->job_no,
                    'part_number' => $press->tipe ?? '',
                    'proses' => 'STAMPING',
                    'customer' => $customer,
                    'source' => $press->vendor ?? '',
                    'pcs_day' => $press->pcs_day ?? 0,
                    'stock_fg' => $press->stok_akhir ?? 0,
                    'strength' => round($strength, 1),
                    'stock_movement' => $strength > 0.5 ? 'FAST MOVING' : 'SLOW MOVING',
                    'no' => ++$maxNo,
                ]);
            }
        }

        // RAW MATERIAL sync — disabled for now (BOM data not ready for production use)
        // To re-enable, uncomment the block below
        RundownStock::where('proses', 'RAW MATERIAL')->delete();

        $query = RundownStock::query()->where('proses', '!=', 'SUB-ASSY');

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('job_no',       'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('source',      'like', "%{$search}%")
                  ->orWhere('customer',    'like', "%{$search}%");
            });
        }

        if ($filterCustomer) $query->where('customer',       $filterCustomer);
        if ($filterProses)   $query->where('proses',         $filterProses);
        if ($filterRemarks)  $query->where('remarks',        $filterRemarks);
        if ($filterType)     $query->where('type_of_part',   $filterType);
        if ($filterMovement) $query->where('stock_movement', $filterMovement);

        $query->orderBy($sortBy, $sortDir);

        // Dropdown filter values
        $allCustomer   = RundownStock::where('proses', '!=', 'SUB-ASSY')->distinct()->orderBy('customer')->pluck('customer')->filter();
        $allProses = [
            'STAMPING' => 'Stamping (inhouse)',
            'SUBCONT'  => 'Subcont (Vendor)',
        ];
        $allRemarks    = RundownStock::where('proses', '!=', 'SUB-ASSY')->distinct()->orderBy('remarks')->pluck('remarks')->filter();
        $allTypeOfPart = RundownStock::where('proses', '!=', 'SUB-ASSY')->where('type_of_part', '!=', 'Raw Material')->distinct()->orderBy('type_of_part')->pluck('type_of_part')->filter()->values()->toArray();
        $allMovement   = RundownStock::where('proses', '!=', 'SUB-ASSY')->distinct()->orderBy('stock_movement')->pluck('stock_movement')->filter();

        // Summary stats (ikut filter aktif)
        $total        = (clone $query)->count();
        $overStock    = (clone $query)->where('strength', '>', 2)->count();
        $limitedStock = (clone $query)->where('strength', '>', 0)->where('strength', '<=', 2)->count();
        $zeroStock    = (clone $query)->where('strength', '<=', 0)->count();

        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = RundownStock::where('proses', '!=', 'SUB-ASSY')->count() > 0;

        $materialsData = [];
        $materialsData = [];

        return view('rundown', compact(
            'items', 'total', 'perPage', 'hasData',
            'search', 'filterCustomer', 'filterProses', 'filterRemarks', 'filterType', 'filterMovement',
            'sortBy', 'sortDir',
            'allCustomer', 'allProses', 'allRemarks', 'allTypeOfPart', 'allMovement',
            'total', 'overStock', 'limitedStock', 'zeroStock', 'materialsData'
        ));
    }
}