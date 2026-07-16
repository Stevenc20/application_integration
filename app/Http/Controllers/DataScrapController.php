<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataScrap;

class DataScrapController extends Controller
{
    public function index(Request $request)
    {
        $search         = trim($request->get('search', ''));
        $filterCustomer = $request->get('customer', '');
        $filterMonth    = $request->get('month', '');
        $filterSource1  = $request->get('sourch_1', '');
        $filterSource2  = $request->get('sourch_2', '');
        $sortBy         = $request->get('sort', 'no');
        $sortDir        = $request->get('dir', 'asc');

        $allowed = [
            'no', 'year', 'month', 'ba_no', 'job_no', 'sourch_1', 'part_number',
            'part_name', 'sourch_2', 'customer', 'qty', 'value', 'total_production', 'reject_rate'
        ];
        if (!in_array($sortBy, $allowed)) $sortBy = 'no';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $query = DataScrap::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('customer', 'like', "%{$search}%")
                  ->orWhere('part_name', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('ba_no', 'like', "%{$search}%")
                  ->orWhere('job_no', 'like', "%{$search}%");
            });
        }

        if ($filterCustomer) {
            $query->where('customer', $filterCustomer);
        }
        if ($filterMonth) {
            $query->where('month', $filterMonth);
        }
        if ($filterSource1) {
            $query->where('sourch_1', $filterSource1);
        }
        if ($filterSource2) {
            $query->where('sourch_2', $filterSource2);
        }

        $query->orderBy($sortBy, $sortDir);

        // Filter values
        $allCustomers = DataScrap::distinct()->orderBy('customer')->pluck('customer')->filter();
        $allMonths    = DataScrap::distinct()->orderBy('month')->pluck('month')->filter();
        $allSources1  = DataScrap::distinct()->orderBy('sourch_1')->pluck('sourch_1')->filter();
        $allSources2  = DataScrap::distinct()->orderBy('sourch_2')->pluck('sourch_2')->filter();

        // Calculate statistics
        $totalItems  = (clone $query)->count();
        $totalQty    = (clone $query)->sum('qty');
        $totalValue  = (clone $query)->sum('value');
        $avgReject   = (clone $query)->avg('reject_rate') ?: 0;

        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = DataScrap::count() > 0;

        return view('data_scrap', compact(
            'items', 'totalItems', 'perPage', 'hasData',
            'search', 'filterCustomer', 'filterMonth', 'filterSource1', 'filterSource2',
            'sortBy', 'sortDir',
            'allCustomers', 'allMonths', 'allSources1', 'allSources2',
            'totalQty', 'totalValue', 'avgReject'
        ));
    }
}
