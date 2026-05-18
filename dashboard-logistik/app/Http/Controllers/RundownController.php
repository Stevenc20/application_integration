<?php

namespace App\Http\Controllers;

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

        $query = RundownStock::query();

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
        $allCustomer   = RundownStock::distinct()->orderBy('customer')->pluck('customer')->filter();
        $allProses     = RundownStock::distinct()->orderBy('proses')->pluck('proses')->filter();
        $allRemarks    = RundownStock::distinct()->orderBy('remarks')->pluck('remarks')->filter();
        $allTypeOfPart = RundownStock::distinct()->orderBy('type_of_part')->pluck('type_of_part')->filter();
        $allMovement   = RundownStock::distinct()->orderBy('stock_movement')->pluck('stock_movement')->filter();

        // Summary stats (ikut filter aktif)
        $total        = (clone $query)->count();
        $overStock    = (clone $query)->where('strength', '>', 2)->count();
        $limitedStock = (clone $query)->where('strength', '>', 0)->where('strength', '<=', 2)->count();
        $zeroStock    = (clone $query)->where('strength', '<=', 0)->count();

        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = RundownStock::count() > 0;

        return view('rundown', compact(
            'items', 'total', 'perPage', 'hasData',
            'search', 'filterCustomer', 'filterProses', 'filterRemarks', 'filterType', 'filterMovement',
            'sortBy', 'sortDir',
            'allCustomer', 'allProses', 'allRemarks', 'allTypeOfPart', 'allMovement',
            'total', 'overStock', 'limitedStock', 'zeroStock'
        ));
    }
}