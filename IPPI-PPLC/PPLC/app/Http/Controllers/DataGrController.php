<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataGr;

class DataGrController extends Controller
{
    public function index(Request $request)
    {
        $search       = trim($request->get('search', ''));
        $filterVendor = $request->get('vendor_name', '');
        $filterStatus = $request->get('gr_status', '');
        $sortBy       = $request->get('sort', 'id');
        $sortDir      = $request->get('dir', 'desc');

        $allowed = [
            'id', 'gr_status', 'po_number', 'job_number', 'material', 'vendor_name',
            'qty', 'dn_number', 'kanban_number', 'gr_number_edn', 'dn_date', 'gr_date', 'gr_number_sap'
        ];
        if (!in_array($sortBy, $allowed)) $sortBy = 'id';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $query = DataGr::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                  ->orWhere('material', 'like', "%{$search}%")
                  ->orWhere('job_number', 'like', "%{$search}%")
                  ->orWhere('po_number', 'like', "%{$search}%")
                  ->orWhere('dn_number', 'like', "%{$search}%")
                  ->orWhere('gr_number_sap', 'like', "%{$search}%")
                  ->orWhere('gr_number_edn', 'like', "%{$search}%");
            });
        }

        if ($filterVendor) {
            $query->where('vendor_name', $filterVendor);
        }
        if ($filterStatus) {
            $query->where('gr_status', $filterStatus);
        }

        $query->orderBy($sortBy, $sortDir);

        // Filters options
        $allVendors  = DataGr::distinct()->orderBy('vendor_name')->pluck('vendor_name')->filter();
        $allStatuses = DataGr::distinct()->orderBy('gr_status')->pluck('gr_status')->filter();

        // Calculate statistics
        $totalItems  = (clone $query)->count();
        $totalQty    = (clone $query)->sum('qty');
        $successCount = (clone $query)->where('gr_status', 'like', '%Success%')->count();
        $failCount   = $totalItems - $successCount;

        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = DataGr::count() > 0;

        return view('data_gr', compact(
            'items', 'totalItems', 'perPage', 'hasData',
            'search', 'filterVendor', 'filterStatus',
            'sortBy', 'sortDir',
            'allVendors', 'allStatuses',
            'totalQty', 'successCount', 'failCount'
        ));
    }
}
