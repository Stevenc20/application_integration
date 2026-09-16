<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Models\StorageLocation;
use App\Models\Material;
use App\Exports\GoodsReceiptExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $vendorId = $request->get('vendor_id');
        $locationId = $request->get('location_id');

        $query = GoodsReceipt::with(['purchaseOrder.vendor', 'storageLocation', 'items.material']);

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('no_gr', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($poQ) use ($search) {
                      $poQ->where('no_po', 'like', "%{$search}%")
                          ->orWhereHas('vendor', function($vQ) use ($search) {
                              $vQ->where('nama', 'like', "%{$search}%");
                          });
                  });
            });
        }

        if ($startDate) {
            $query->whereDate('tanggal_terima', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_terima', '<=', $endDate);
        }
        if ($vendorId && $vendorId !== 'Semua Vendor') {
            $query->whereHas('purchaseOrder', function($poQ) use ($vendorId) {
                $poQ->where('vendor_id', $vendorId);
            });
        }
        if ($locationId && $locationId !== 'Semua Lokasi') {
            $query->where('storage_location_id', $locationId);
        }

        $goodsReceipts = $query->orderBy('no_gr', 'desc')->paginate(15)->appends($request->query());
        $vendors = Vendor::orderBy('nama', 'asc')->get();
        $locations = StorageLocation::orderBy('nama', 'asc')->get();
        
        // Only load purchase orders that are sent or received (eligible for GR)
        $purchaseOrders = PurchaseOrder::with(['vendor', 'items.material'])->get();
        $materials = Material::orderBy('kode', 'asc')->get();

        return view('goods_receipts.index', compact('goodsReceipts', 'search', 'startDate', 'endDate', 'vendorId', 'locationId', 'vendors', 'locations', 'purchaseOrders', 'materials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_gr' => 'required|string|unique:goods_receipts,no_gr',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'tanggal_terima' => 'required|date',
            'storage_location_id' => 'required|exists:storage_locations,id',
            'status' => 'required|in:drafted,posted',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.qty' => 'required|numeric|min:0.001',
        ]);

        try {
            DB::beginTransaction();

            $gr = GoodsReceipt::create([
                'no_gr' => $validated['no_gr'],
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'tanggal_terima' => $validated['tanggal_terima'],
                'storage_location_id' => $validated['storage_location_id'],
                'status' => $validated['status'],
            ]);

            foreach ($validated['items'] as $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'material_id' => $item['material_id'],
                    'qty' => $item['qty'],
                ]);

                // If linked to a PO, increment the received quantity in the PO
                if ($gr->purchase_order_id && $gr->status === 'posted') {
                    PurchaseOrderItem::where('purchase_order_id', $gr->purchase_order_id)
                        ->where('material_id', $item['material_id'])
                        ->increment('qty_received', $item['qty']);
                }

                // If posted, update the stock level in material_stocks
                if ($gr->status === 'posted') {
                    $mStock = \App\Models\MaterialStock::firstOrCreate([
                        'material_id' => $item['material_id'],
                        'storage_location_id' => $gr->storage_location_id,
                    ]);
                    $mStock->increment('qty', $item['qty']);
                }
            }

            // Update PO status and Sync SKM status if linked to PO and posted
            if ($gr->purchase_order_id && $gr->status === 'posted') {
                $po = PurchaseOrder::with('items')->find($gr->purchase_order_id);
                if ($po) {
                    $allReceived = $po->items->every(fn($i) => $i->qty_received >= $i->qty);
                    $anyReceived = $po->items->some(fn($i) => $i->qty_received > 0);
                    if ($allReceived) {
                        $po->update(['status' => 'received']);
                    } elseif ($anyReceived) {
                        $po->update(['status' => 'partially_received']);
                    } else {
                        $po->update(['status' => 'approved']);
                    }

                    if ($po->skm_order_id) {
                        $po->skmOrder?->syncReceivingStatus();
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Goods Receipt berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat Goods Receipt: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $gr = GoodsReceipt::with(['purchaseOrder.vendor', 'storageLocation', 'items.material'])->findOrFail($id);
        
        return response()->json([
            'id' => $gr->id,
            'no_gr' => $gr->no_gr,
            'purchase_order_id' => $gr->purchase_order_id,
            'no_po' => $gr->purchaseOrder->no_po ?? '-',
            'vendor_nama' => $gr->purchaseOrder->vendor->nama ?? '-',
            'tanggal_terima' => $gr->tanggal_terima->format('Y-m-d'),
            'storage_location_id' => $gr->storage_location_id,
            'storage_location_nama' => $gr->storageLocation->nama ?? '-',
            'status' => $gr->status,
            'items' => $gr->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'material_id' => $item->material_id,
                    'material_kode' => $item->material->kode ?? '-',
                    'material_nama' => $item->material->nama ?? '-',
                    'material_uom' => $item->material->uom ?? '-',
                    'qty' => $item->qty,
                ];
            }),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:goods_receipts,id',
            'no_gr' => 'required|string|unique:goods_receipts,no_gr,' . $request->id,
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'tanggal_terima' => 'required|date',
            'storage_location_id' => 'required|exists:storage_locations,id',
            'status' => 'required|in:drafted,posted',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.qty' => 'required|numeric|min:0.001',
        ]);

        try {
            DB::beginTransaction();

            $gr = GoodsReceipt::findOrFail($request->id);
            $oldPoId = $gr->purchase_order_id;

            // Revert previous PO received quantities if PO was linked and GR was posted
            if ($gr->purchase_order_id && $gr->status === 'posted') {
                foreach ($gr->items as $oldItem) {
                    PurchaseOrderItem::where('purchase_order_id', $gr->purchase_order_id)
                        ->where('material_id', $oldItem->material_id)
                        ->decrement('qty_received', $oldItem->qty);
                }
            }

            // Revert stock level in material_stocks if previous was posted
            if ($gr->status === 'posted') {
                foreach ($gr->items as $oldItem) {
                    $mStock = \App\Models\MaterialStock::where('material_id', $oldItem->material_id)
                        ->where('storage_location_id', $gr->storage_location_id)
                        ->first();
                    if ($mStock) {
                        $mStock->decrement('qty', $oldItem->qty);
                    }
                }
            }

            $gr->update([
                'no_gr' => $validated['no_gr'],
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'tanggal_terima' => $validated['tanggal_terima'],
                'storage_location_id' => $validated['storage_location_id'],
                'status' => $validated['status'],
            ]);

            // Rebuild items
            GoodsReceiptItem::where('goods_receipt_id', $gr->id)->delete();

            foreach ($validated['items'] as $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'material_id' => $item['material_id'],
                    'qty' => $item['qty'],
                ]);

                // Increment with new quantity
                if ($gr->purchase_order_id && $gr->status === 'posted') {
                    PurchaseOrderItem::where('purchase_order_id', $gr->purchase_order_id)
                        ->where('material_id', $item['material_id'])
                        ->increment('qty_received', $item['qty']);
                }

                // Update stock level in material_stocks with new quantity
                if ($gr->status === 'posted') {
                    $mStock = \App\Models\MaterialStock::firstOrCreate([
                        'material_id' => $item['material_id'],
                        'storage_location_id' => $gr->storage_location_id,
                    ]);
                    $mStock->increment('qty', $item['qty']);
                }
            }

            // Update PO status and Sync SKM status for old and new POs
            foreach (array_unique(array_filter([$oldPoId, $gr->purchase_order_id])) as $poId) {
                $po = PurchaseOrder::with('items')->find($poId);
                if ($po) {
                    $allReceived = $po->items->every(fn($i) => $i->qty_received >= $i->qty);
                    $anyReceived = $po->items->some(fn($i) => $i->qty_received > 0);
                    if ($allReceived) {
                        $po->update(['status' => 'received']);
                    } elseif ($anyReceived) {
                        $po->update(['status' => 'partially_received']);
                    } else {
                        $po->update(['status' => 'approved']);
                    }

                    if ($po->skm_order_id) {
                        $po->skmOrder?->syncReceivingStatus();
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Goods Receipt berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui Goods Receipt: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $gr = GoodsReceipt::findOrFail($id);

            // Revert PO received quantities if posted
            if ($gr->purchase_order_id && $gr->status === 'posted') {
                foreach ($gr->items as $item) {
                    PurchaseOrderItem::where('purchase_order_id', $gr->purchase_order_id)
                        ->where('material_id', $item->material_id)
                        ->decrement('qty_received', $item->qty);
                }
            }

            // Revert stock level in material_stocks if posted
            if ($gr->status === 'posted') {
                foreach ($gr->items as $item) {
                    $mStock = \App\Models\MaterialStock::where('material_id', $item->material_id)
                        ->where('storage_location_id', $gr->storage_location_id)
                        ->first();
                    if ($mStock) {
                        $mStock->decrement('qty', $item->qty);
                    }
                }
            }

            $poIdToSync = $gr->purchase_order_id;
            $gr->delete();

            // Update PO status and Sync SKM status
            if ($poIdToSync) {
                $po = PurchaseOrder::with('items')->find($poIdToSync);
                if ($po) {
                    $allReceived = $po->items->every(fn($i) => $i->qty_received >= $i->qty);
                    $anyReceived = $po->items->some(fn($i) => $i->qty_received > 0);
                    if ($allReceived) {
                        $po->update(['status' => 'received']);
                    } elseif ($anyReceived) {
                        $po->update(['status' => 'partially_received']);
                    } else {
                        $po->update(['status' => 'approved']);
                    }

                    if ($po->skm_order_id) {
                        $po->skmOrder?->syncReceivingStatus();
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Goods Receipt berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus Goods Receipt: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $vendorId = $request->get('vendor_id');
        $locationId = $request->get('location_id');

        return Excel::download(new GoodsReceiptExport($search, $startDate, $endDate, $vendorId, $locationId), 'goods_receipts_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function printPdf(Request $request)
    {
        $search = trim($request->get('search', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $vendorId = $request->get('vendor_id');
        $locationId = $request->get('location_id');

        $query = GoodsReceipt::with(['purchaseOrder.vendor', 'storageLocation', 'items.material']);

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('no_gr', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($poQ) use ($search) {
                      $poQ->where('no_po', 'like', "%{$search}%")
                          ->orWhereHas('vendor', function($vQ) use ($search) {
                              $vQ->where('nama', 'like', "%{$search}%");
                          });
                  });
            });
        }

        if ($startDate) {
            $query->whereDate('tanggal_terima', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_terima', '<=', $endDate);
        }
        if ($vendorId && $vendorId !== 'Semua Vendor') {
            $query->whereHas('purchaseOrder', function($poQ) use ($vendorId) {
                $poQ->where('vendor_id', $vendorId);
            });
        }
        if ($locationId && $locationId !== 'Semua Lokasi') {
            $query->where('storage_location_id', $locationId);
        }

        $goodsReceipts = $query->orderBy('no_gr', 'desc')->get();

        $dateStr = now()->format('d M Y, H:i') . ' WIB';
        $filterStr = "Semua data";
        
        $filters = [];
        if ($search !== '') $filters[] = "Cari: '$search'";
        if ($startDate) $filters[] = "Mulai: $startDate";
        if ($endDate) $filters[] = "Sampai: $endDate";
        if ($vendorId && $vendorId !== 'Semua Vendor') {
            $vendor = Vendor::find($vendorId);
            if ($vendor) $filters[] = "Vendor: {$vendor->nama}";
        }
        if ($locationId && $locationId !== 'Semua Lokasi') {
            $loc = StorageLocation::find($locationId);
            if ($loc) $filters[] = "Lokasi: {$loc->nama}";
        }
        
        if (count($filters) > 0) {
            $filterStr = implode(' | ', $filters);
        }

        $pdf = Pdf::loadView('goods_receipts.pdf', [
            'goodsReceipts' => $goodsReceipts,
            'dateStr' => $dateStr,
            'filterStr' => $filterStr,
        ]);

        $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'goods_receipts_' . now()->format('Ymd') . '.pdf';
        $savePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($savePath);

        return $pdf->download($filename);
    }
}
