<?php

namespace App\Http\Controllers;

use App\Models\MaterialStock;
use App\Models\StorageLocation;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsIssueItem;
use App\Exports\MaterialStockExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockOverviewController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $locationId = $request->get('location_id');
        $status = $request->get('status');
        $minStockOnly = $request->has('min_stock');

        $query = MaterialStock::with(['material', 'storageLocation']);

        if ($search !== '') {
            $query->whereHas('material', function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($locationId && $locationId !== 'Semua Lokasi') {
            $query->where('storage_location_id', $locationId);
        }

        // Get all matching stocks
        $allStocks = $query->get();

        // Apply dynamic status filter
        if ($status && $status !== 'Semua Status') {
            $allStocks = $allStocks->filter(function($row) use ($status) {
                $qty = $row->qty;
                $min = $row->material->min_stok ?? 0;
                
                if ($qty <= 0) {
                    $rowStatus = 'Habis';
                } elseif ($qty <= $min) {
                    $rowStatus = 'Rendah';
                } else {
                    $rowStatus = 'Normal';
                }
                
                return $rowStatus === $status;
            });
        }

        // Apply min stock filter
        if ($minStockOnly) {
            $allStocks = $allStocks->filter(function($row) {
                $qty = $row->qty;
                $min = $row->material->min_stok ?? 0;
                return $qty <= $min;
            });
        }

        // Convert collection back to paginator or pass directly
        // Since we only have a dozen or few dozen materials, passing directly is excellent,
        // or we can paginate using array helper:
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageItems = $allStocks->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $stocks = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $allStocks->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
        );
        $stocks->appends($request->query());

        $locations = StorageLocation::orderBy('nama', 'asc')->get();

        // Get recent mutations for the "Riwayat Mutasi" modal popup
        $grItems = GoodsReceiptItem::with(['goodsReceipt.storageLocation', 'material'])
            ->whereHas('goodsReceipt', function($q) {
                $q->where('status', 'posted');
            })
            ->get()
            ->map(function($item) {
                return [
                    'tanggal' => $item->goodsReceipt->tanggal_terima,
                    'tipe' => 'MASUK (GR)',
                    'dokumen' => $item->goodsReceipt->no_gr,
                    'lokasi' => $item->goodsReceipt->storageLocation->nama ?? '-',
                    'kode' => $item->material->kode ?? '-',
                    'nama' => $item->material->nama ?? '-',
                    'qty' => '+' . number_format($item->qty, 3),
                    'color' => '#15803d'
                ];
            });

        $giItems = GoodsIssueItem::with(['goodsIssue.storageLocation', 'material'])
            ->get()
            ->map(function($item) {
                return [
                    'tanggal' => $item->goodsIssue->tanggal_issue,
                    'tipe' => 'KELUAR (GI)',
                    'dokumen' => $item->goodsIssue->no_gi,
                    'lokasi' => $item->goodsIssue->storageLocation->nama ?? '-',
                    'kode' => $item->material->kode ?? '-',
                    'nama' => $item->material->nama ?? '-',
                    'qty' => '-' . number_format($item->qty, 3),
                    'color' => '#ef4444'
                ];
            });

        $mutations = $grItems->concat($giItems)->sortByDesc('tanggal')->take(50);

        return view('stock_overviews.index', compact('stocks', 'search', 'locationId', 'status', 'minStockOnly', 'locations', 'mutations'));
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        $locationId = $request->get('location_id');
        $status = $request->get('status');
        $minStockOnly = $request->has('min_stock');

        return Excel::download(
            new MaterialStockExport($search, $locationId, $status, $minStockOnly), 
            'stock_overview_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
