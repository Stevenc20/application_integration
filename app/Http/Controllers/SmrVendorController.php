<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SmrVendor;

class SmrVendorController extends Controller
{
    public function index(Request $request)
    {
        $search       = trim($request->get('search', ''));
        $filterVendor = $request->get('vendor', '');
        $filterStatus = $request->get('status_barang', '');
        $filterMonth  = $request->get('month', '');
        $sortBy       = $request->get('sort', 'no');
        $sortDir      = $request->get('dir', 'asc');

        $allowed = ['no', 'month', 'vendor', 'no_smr', 'part_name', 'qty', 'problem', 'tanggal_keluar', 'tanggal_masuk', 'qty_pengganti', 'status_barang'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'no';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $query = SmrVendor::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('vendor', 'like', "%{$search}%")
                  ->orWhere('part_name', 'like', "%{$search}%")
                  ->orWhere('problem', 'like', "%{$search}%")
                  ->orWhere('no_smr', 'like', "%{$search}%");
            });
        }

        if ($filterVendor) {
            $query->where('vendor', $filterVendor);
        }
        if ($filterStatus) {
            $query->where('status_barang', $filterStatus);
        }
        if ($filterMonth) {
            $query->where('month', $filterMonth);
        }

        $query->orderBy($sortBy, $sortDir);

        // Fetch distinct filter option values
        $allVendors  = SmrVendor::distinct()->orderBy('vendor')->pluck('vendor')->filter();
        $allStatuses = SmrVendor::distinct()->orderBy('status_barang')->pluck('status_barang')->filter();
        $allMonths   = SmrVendor::distinct()->orderBy('month')->pluck('month')->filter();

        // Calculate statistics
        $totalItems   = (clone $query)->count();
        $totalQty     = (clone $query)->sum('qty');
        $totalRepl    = (clone $query)->sum('qty_pengganti');
        
        $uncloseCount = (clone $query)->where('status_barang', 'UNCLOSE')->count();
        $closeCount   = (clone $query)->where('status_barang', 'CLOSE')->count();
        
        $closeRate    = $totalItems > 0 ? ($closeCount / $totalItems) * 100 : 0;

        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = SmrVendor::count() > 0;

        return view('smr_vendor', compact(
            'items', 'totalItems', 'perPage', 'hasData',
            'search', 'filterVendor', 'filterStatus', 'filterMonth',
            'sortBy', 'sortDir',
            'allVendors', 'allStatuses', 'allMonths',
            'totalQty', 'totalRepl', 'uncloseCount', 'closeCount', 'closeRate'
        ));
    }
}
