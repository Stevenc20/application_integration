<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\Material;
use App\Models\ProductionOrder;
use App\Models\MaterialStock;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductionOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionOrder::with('material');
        if ($request->status) {
            if ($request->status === 'draft') {
                $query->whereIn('status', ['draft', 'created']);
            } elseif ($request->status === 'goods_issued' || $request->status === 'in_progress') {
                $query->whereIn('status', ['in_progress', 'goods_issued']);
            } elseif ($request->status === 'confirmed' || $request->status === 'completed') {
                $query->whereIn('status', ['completed', 'confirmed']);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('material', fn($m) => $m->where('nama', 'like', "%{$request->search}%"));
            });
        }
        $dateFrom = $request->get('date_from', $request->get('start_date'));
        $dateTo = $request->get('date_to', $request->get('end_date'));
        if ($dateFrom) $query->whereDate('planned_start_date', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('planned_start_date', '<=', $dateTo);
        $orders = $query->latest()->paginate(20)->withQueryString();
        return view('production_orders.index', compact('orders'));
    }

    public function create()
    {
        $materials = Material::where('status', 'Aktif')
            ->whereIn('tipe', ['FP', 'WIP'])
            ->orderBy('kode')->get();
        $boms      = Bom::with('material')->where('status', 'active')->get();
        return view('production_orders.create', compact('materials', 'boms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'planned_start_date'              => 'required|date',
            'planned_end_date'                => 'required|date|after_or_equal:planned_start_date',
            'orders'                          => 'required|array|min:1',
            'orders.*.order_number'           => 'required|string|max:50|distinct',
            'orders.*.material_id'            => 'required|exists:materials,id',
            'orders.*.quantity_planned'       => 'required|numeric|min:0.001',
            'orders.*.notes'                  => 'nullable|string',
            'general_notes'                   => 'nullable|string',
        ]);

        // Validate uniqueness of each order_number against the database
        foreach ($request->orders as $idx => $row) {
            if (ProductionOrder::where('order_number', $row['order_number'])->exists()) {
                return back()->withErrors(["orders.{$idx}.order_number" => "Nomor order '{$row['order_number']}' sudah digunakan."])->withInput();
            }
        }

        DB::transaction(function () use ($request) {
            foreach ($request->orders as $row) {
                // Auto-find active BOM for the material
                $bom = Bom::with('items')
                    ->where('material_id', $row['material_id'])
                    ->where('status', 'active')
                    ->orderByDesc('valid_from')
                    ->first();

                $notes = null;
                if (!empty($row['notes']) || !empty($request->general_notes)) {
                    $notes = implode(' | ', array_filter([$row['notes'] ?? null, $request->general_notes ?? null]));
                }

                $order = ProductionOrder::create([
                    'order_number'       => $row['order_number'],
                    'material_id'        => $row['material_id'],
                    'bom_id'             => $bom?->id,
                    'routing_id'         => null,
                    'quantity_planned'   => $row['quantity_planned'],
                    'quantity_produced'  => 0,
                    'planned_start_date' => $request->planned_start_date,
                    'planned_end_date'   => $request->planned_end_date,
                    'status'             => 'created',
                    'notes'              => $notes,
                    'created_by'         => auth()->id() ?: (User::first()->id ?? User::create([
                        'name' => 'Operator',
                        'email' => 'operator@example.com',
                        'password' => bcrypt('password123'),
                    ])->id),
                ]);

                if ($bom) {
                    $multiplier = $row['quantity_planned'] / $bom->base_quantity;
                    foreach ($bom->items as $item) {
                        $order->components()->create([
                            'material_id'       => $item->material_id,
                            'quantity_required' => $item->quantity * $multiplier,
                            'quantity_issued'   => 0,
                        ]);
                    }
                }
            }
        });

        $count = count($request->orders);
        return redirect()->route('production_orders.index')->with('success', "{$count} Production Order berhasil dibuat.");
    }

    public function show($id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        $productionOrder->load('material', 'bom', 'components.material', 'components.storageLocation', 'createdBy');
        $locations = StorageLocation::orderBy('kode')->get();

        // Confirm destination: cari gudang berdasarkan tipe_material dari material yang diproduksi
        $warehouseByType  = $locations->groupBy('tipe_material')->map->first();
        $defaultFgLocation = $warehouseByType->get($productionOrder->material->tipe)
                             ?? $locations->last();

        // Stock available per component – lookup by tipe_material
        $componentStocks = [];
        foreach ($productionOrder->components as $comp) {
            $location = $warehouseByType->get($comp->material->tipe);
            $stock    = $location
                ? MaterialStock::where('material_id', $comp->material_id)->where('storage_location_id', $location->id)->first()
                : null;
            $componentStocks[$comp->id] = [
                'location_code' => $location?->kode ?? '-',
                'available'     => $stock ? (float) $stock->qty : 0,
            ];
        }

        // Max confirmable qty = minimum ratio (issued/required) × planned across all components
        $maxConfirmQty = (float) $productionOrder->quantity_planned;
        foreach ($productionOrder->components as $comp) {
            if ((float) $comp->quantity_required > 0) {
                $ratio         = (float) $comp->quantity_issued / (float) $comp->quantity_required;
                $possible      = round($ratio * (float) $productionOrder->quantity_planned, 3);
                $maxConfirmQty = min($maxConfirmQty, $possible);
            }
        }
        $maxConfirmQty = max(0, $maxConfirmQty);
        $order = $productionOrder;

        return view('production_orders.show', compact(
            'order', 'locations', 'defaultFgLocation', 'componentStocks', 'maxConfirmQty'
        ));
    }

    public function edit($id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        if (!in_array($productionOrder->status, ['created'])) {
            return back()->with('error', 'Production Order tidak dapat diedit.');
        }
        $materials = Material::where('status', 'Aktif')->orderBy('kode')->get();
        $boms      = Bom::with('material')->where('status', 'active')->get();
        return view('production_orders.edit', compact('productionOrder', 'materials', 'boms'));
    }

    public function update(Request $request, $id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        if ($productionOrder->status !== 'created') {
            return back()->with('error', 'Production Order tidak dapat diedit.');
        }
        $request->validate([
            'material_id'        => 'required|exists:materials,id',
            'bom_id'             => 'required|exists:boms,id',
            'quantity_planned'   => 'required|numeric|min:0.001',
            'planned_start_date' => 'required|date',
            'planned_end_date'   => 'required|date|after_or_equal:planned_start_date',
        ]);

        DB::transaction(function () use ($productionOrder, $request) {
            $productionOrder->update($request->only('material_id', 'bom_id', 'quantity_planned', 'planned_start_date', 'planned_end_date', 'notes'));
            
            // Recreate components based on BOM and new quantity
            $productionOrder->components()->delete();
            $bom = Bom::with('items')->find($productionOrder->bom_id);
            if ($bom) {
                $multiplier = $productionOrder->quantity_planned / $bom->base_quantity;
                foreach ($bom->items as $item) {
                    $productionOrder->components()->create([
                        'material_id'       => $item->material_id,
                        'quantity_required' => $item->quantity * $multiplier,
                        'quantity_issued'   => 0,
                    ]);
                }
            }
        });

        return redirect()->route('production_orders.show', $productionOrder->id)->with('success', 'Production Order diperbarui.');
    }

    public function release($id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        if ($productionOrder->status !== 'created') {
            return back()->with('error', 'Hanya Production Order Created yang dapat di-release.');
        }
        $productionOrder->update(['status' => 'released', 'actual_start_date' => now()]);
        return back()->with('success', 'Production Order berhasil di-release.');
    }

    public function bulkRelease(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'exists:production_orders,id']);

        $orders = ProductionOrder::whereIn('id', $request->ids)->where('status', 'created')->get();
        if ($orders->isEmpty()) {
            return back()->with('error', 'Tidak ada Production Order berstatus Created yang dipilih.');
        }

        $now = now();
        foreach ($orders as $order) {
            $order->update(['status' => 'released', 'actual_start_date' => $now]);
        }

        return back()->with('success', $orders->count() . ' Production Order berhasil di-release.');
    }

    public function goodsIssue(Request $request, $id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        if (!in_array($productionOrder->status, ['released', 'in_progress'])) {
            return back()->with('error', 'Production Order harus berstatus Released atau In Progress.');
        }

        $request->validate([
            'quantities'   => 'required|array',
            'quantities.*' => 'nullable|numeric|min:0',
        ]);

        $productionOrder->load('components.material');

        // Lookup gudang berdasarkan tipe_material
        $warehouseByType = StorageLocation::get()->groupBy('tipe_material')->map->first();

        // Pre-validate each submitted qty
        $validationErrors = [];
        foreach ($productionOrder->components as $component) {
            $inputQty = (float) ($request->quantities[$component->id] ?? 0);
            if ($inputQty <= 0) continue;

            $remaining = round((float) $component->quantity_required - (float) $component->quantity_issued, 3);
            if ($inputQty > $remaining + 0.001) {
                $validationErrors[] = "{$component->material->kode}: qty input ({$inputQty}) melebihi sisa yang dibutuhkan (" . number_format($remaining, 3) . ")";
                continue;
            }

            $location = $warehouseByType->get($component->material->tipe);
            if (!$location) continue;

            $stock     = MaterialStock::where('material_id', $component->material_id)->where('storage_location_id', $location->id)->first();
            $available = $stock ? (float) $stock->qty : 0;
            if ($inputQty > $available + 0.001) {
                $validationErrors[] = "{$component->material->kode}: stok {$location->kode} tidak cukup (tersedia: " . number_format($available, 3) . ", diminta: " . number_format($inputQty, 3) . ")";
            }
        }

        if (!empty($validationErrors)) {
            return back()->withErrors(['quantities' => $validationErrors])->withInput();
        }

        $hasAny = collect($request->quantities)->filter(fn($v) => (float) $v > 0)->isNotEmpty();
        if (!$hasAny) {
            return back()->with('error', 'Tidak ada qty yang diinput. Isi minimal satu komponen untuk di-GI.');
        }

        DB::transaction(function () use ($request, $productionOrder, $warehouseByType) {
            $rmLocation = $warehouseByType->get('RM') ?? $warehouseByType->first();

            $gi = GoodsIssue::create([
                'no_gi'               => 'GI-' . strtoupper(uniqid()),
                'tanggal_issue'       => now()->toDateString(),
                'storage_location_id' => $rmLocation?->id,
                'keterangan'          => 'GI for Production Order ' . $productionOrder->order_number,
            ]);

            foreach ($productionOrder->components as $component) {
                $inputQty = (float) ($request->quantities[$component->id] ?? 0);
                if ($inputQty <= 0) continue;

                $location = $warehouseByType->get($component->material->tipe);
                if (!$location) continue;

                GoodsIssueItem::create([
                    'goods_issue_id' => $gi->id,
                    'material_id'    => $component->material_id,
                    'qty'            => $inputQty,
                ]);

                $component->update([
                    'quantity_issued'     => round((float) $component->quantity_issued + $inputQty, 3),
                    'storage_location_id' => $location->id,
                ]);

                $stock  = MaterialStock::where('material_id', $component->material_id)->where('storage_location_id', $location->id)->first();
                if ($stock) {
                    $stock->decrement('qty', $inputQty);
                }
            }

            $productionOrder->update(['status' => 'in_progress']);
        });

        return back()->with('success', 'Goods Issue to Production berhasil diposting.');
    }

    public function confirm(Request $request, $id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        $request->validate([
            'quantity_ok'         => 'required|numeric|min:0',
            'quantity_ng'         => 'required|numeric|min:0',
            'storage_location_id' => 'required|exists:storage_locations,id',
            'actual_start_date'   => 'nullable|date',
            'actual_end_date'     => 'nullable|date',
            'notes'               => 'nullable|string',
        ]);

        $totalConfirmed = $request->quantity_ok + $request->quantity_ng;
        if ($totalConfirmed <= 0) {
            return back()->withErrors(['quantity_ok' => 'Total Qty OK + Qty NG harus lebih dari 0.'])->withInput();
        }

        if (!in_array($productionOrder->status, ['released', 'in_progress'])) {
            return back()->with('error', 'Production Order harus berstatus Released atau In Progress.');
        }

        $productionOrder->load('components.material');

        // Validasi: cek material komponen yang sudah di-GI cukup untuk qty yang dikonfirmasi
        if ($productionOrder->components->isNotEmpty()) {
            $ratio = $productionOrder->quantity_planned > 0
                ? $totalConfirmed / $productionOrder->quantity_planned
                : 1;

            $kurang = [];
            foreach ($productionOrder->components as $component) {
                $requiredForConfirm = round($component->quantity_required * $ratio, 3);
                $issued = (float) $component->quantity_issued;

                if ($issued < $requiredForConfirm - 0.001) {
                    $kurang[] = sprintf(
                        '%s (dibutuhkan: %s %s, sudah GI: %s %s)',
                        $component->material->nama,
                        rtrim(rtrim(number_format($requiredForConfirm, 3, ',', '.'), '0'), ','),
                        $component->material->uom,
                        rtrim(rtrim(number_format($issued, 3, ',', '.'), '0'), ','),
                        $component->material->uom
                    );
                }
            }

            if (!empty($kurang)) {
                $errorBag = ['quantity_ok' => 'Material komponen tidak mencukupi untuk konfirmasi ' . rtrim(rtrim(number_format($totalConfirmed, 3, ',', '.'), '0'), ',') . ' unit. Lakukan Goods Issue terlebih dahulu:'];
                foreach ($kurang as $i => $item) {
                    $errorBag["comp_{$i}"] = $item;
                }
                return back()->withErrors($errorBag)->withInput();
            }
        }

        DB::transaction(function () use ($request, $productionOrder, $totalConfirmed) {
            // 1. Posting GR ke stok FG (hanya qty_ok)
            if ($request->quantity_ok > 0) {
                $stock = MaterialStock::firstOrCreate(
                    ['material_id' => $productionOrder->material_id, 'storage_location_id' => $request->storage_location_id],
                    ['qty' => 0]
                );
                $stock->increment('qty', $request->quantity_ok);
            }

            // 2. Kembalikan sisa material ke stok jika aktual < planned
            $actualRatio = $productionOrder->quantity_planned > 0
                ? $totalConfirmed / $productionOrder->quantity_planned
                : 1;

            if ($actualRatio < 0.9999) {
                foreach ($productionOrder->components as $component) {
                    if ($component->quantity_issued <= 0 || !$component->storage_location_id) continue;

                    $qtyActuallyUsed = round($component->quantity_required * $actualRatio, 3);
                    $qtyReturn = round((float) $component->quantity_issued - $qtyActuallyUsed, 3);
                    if ($qtyReturn < 0.001) continue;

                    $compStock = MaterialStock::firstOrCreate(
                        ['material_id' => $component->material_id, 'storage_location_id' => $component->storage_location_id],
                        ['qty' => 0]
                    );
                    $compStock->increment('qty', $qtyReturn);
                }
            }

            // 3. Update production order
            $productionOrder->update([
                'quantity_produced' => $productionOrder->quantity_produced + $totalConfirmed,
                'quantity_ok'       => $productionOrder->quantity_ok + $request->quantity_ok,
                'quantity_ng'       => $productionOrder->quantity_ng + $request->quantity_ng,
                'status'            => 'completed',
                'actual_start_date' => $request->actual_start_date ?: ($productionOrder->actual_start_date ?: now()),
                'actual_end_date'   => $request->actual_end_date ?: now(),
                'notes'             => $request->notes ?: $productionOrder->notes,
            ]);
        });

        return back()->with('success', 'Konfirmasi produksi berhasil. Stok produk jadi telah diperbarui.');
    }

    public function printLabel($id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        $productionOrder->load('material', 'components.material');
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $barcode   = $generator->getBarcode($productionOrder->order_number, $generator::TYPE_CODE_128, 1, 40);
        return view('production_orders.print', compact('productionOrder', 'barcode'));
    }

    public function print($id)
    {
        return $this->printLabel($id);
    }

    public function printAll()
    {
        $orders = ProductionOrder::with('material')->latest()->get();
        $pdf = Pdf::loadView('production_orders.pdf_all', compact('orders'));
        return $pdf->stream('production_orders.pdf');
    }

    public function destroy($id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        if ($productionOrder->status !== 'created') {
            return back()->with('error', 'Hanya Production Order Created yang dapat dihapus.');
        }
        $productionOrder->delete();
        return redirect()->route('production_orders.index')->with('success', 'Production Order berhasil dihapus.');
    }

    public function cancel($id)
    {
        $productionOrder = ProductionOrder::findOrFail($id);
        if (!in_array($productionOrder->status, ['created', 'released'])) {
            return back()->with('error', 'Hanya Production Order Created atau Released yang dapat dibatalkan.');
        }
        $productionOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Production Order berhasil dibatalkan.');
    }
}
