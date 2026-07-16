<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PalletMutation;

class PalletMutationController extends Controller
{
    public function index(Request $request)
    {
        $search       = trim($request->get('search', ''));
        $filterVendor = $request->get('vendor', '');
        $filterType   = $request->get('type_pallet', '');
        $sortBy       = $request->get('sort', 'no');
        $sortDir      = $request->get('dir', 'asc');

        $allowed = ['no', 'month', 'vendor', 'type_pallet', 'type', 'initial_stock', 'pallet_in', 'pallet_out', 'final_stock'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'no';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $query = PalletMutation::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('vendor', 'like', "%{$search}%")
                  ->orWhere('type_pallet', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($filterVendor) {
            $query->where('vendor', $filterVendor);
        }
        if ($filterType) {
            $query->where('type_pallet', $filterType);
        }

        $query->orderBy($sortBy, $sortDir);

        // Filter values
        $allVendors = PalletMutation::distinct()->orderBy('vendor')->pluck('vendor')->filter();
        $allTypes   = PalletMutation::distinct()->orderBy('type_pallet')->pluck('type_pallet')->filter();

        // Summary stats
        $totalItems   = (clone $query)->count();
        $totalInitial = (clone $query)->sum('initial_stock');
        $totalIn      = (clone $query)->sum('pallet_in');
        $totalOut     = (clone $query)->sum('pallet_out');
        $totalFinal   = (clone $query)->sum('final_stock');

        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = PalletMutation::count() > 0;

        return view('pallet_mutation', compact(
            'items', 'totalItems', 'perPage', 'hasData',
            'search', 'filterVendor', 'filterType',
            'sortBy', 'sortDir',
            'allVendors', 'allTypes',
            'totalInitial', 'totalIn', 'totalOut', 'totalFinal'
        ));
    }
}
