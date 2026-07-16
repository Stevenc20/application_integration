<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SmrCustomer;

class SmrCustomerController extends Controller
{
    public function index(Request $request)
    {
        $search         = trim($request->get('search', ''));
        $filterCustomer = $request->get('customer', '');
        $filterMonth    = $request->get('month', '');
        $filterQuarter  = $request->get('quarterly', '');
        $sortBy         = $request->get('sort', 'no');
        $sortDir        = $request->get('dir', 'asc');

        $allowed = [
            'no', 'date', 'month', 'quarterly', 'no_smr', 'job_no', 'part_number', 'part_name',
            'qty_smr', 'total_production', 'cost_rijection', 'rijection_rate', 'customer', 'problem', 'countermeasures'
        ];
        if (!in_array($sortBy, $allowed)) $sortBy = 'no';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $query = SmrCustomer::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('customer', 'like', "%{$search}%")
                  ->orWhere('part_name', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('no_smr', 'like', "%{$search}%")
                  ->orWhere('job_no', 'like', "%{$search}%")
                  ->orWhere('problem', 'like', "%{$search}%");
            });
        }

        if ($filterCustomer) {
            $query->where('customer', $filterCustomer);
        }
        if ($filterMonth) {
            $query->where('month', $filterMonth);
        }
        if ($filterQuarter) {
            $query->where('quarterly', $filterQuarter);
        }

        $query->orderBy($sortBy, $sortDir);

        // Filter values
        $allCustomers  = SmrCustomer::distinct()->orderBy('customer')->pluck('customer')->filter();
        $allMonths     = SmrCustomer::distinct()->orderBy('month')->pluck('month')->filter();
        $allQuarters   = SmrCustomer::distinct()->orderBy('quarterly')->pluck('quarterly')->filter();

        // Calculate statistics
        $totalItems       = (clone $query)->count();
        $totalQtySMR      = (clone $query)->sum('qty_smr');
        $totalCostReject  = (clone $query)->sum('cost_rijection');
        $avgRejectRate    = (clone $query)->avg('rijection_rate') ?: 0;

        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = SmrCustomer::count() > 0;

        return view('smr_customer', compact(
            'items', 'totalItems', 'perPage', 'hasData',
            'search', 'filterCustomer', 'filterMonth', 'filterQuarter',
            'sortBy', 'sortDir',
            'allCustomers', 'allMonths', 'allQuarters',
            'totalQtySMR', 'totalCostReject', 'avgRejectRate'
        ));
    }
}
