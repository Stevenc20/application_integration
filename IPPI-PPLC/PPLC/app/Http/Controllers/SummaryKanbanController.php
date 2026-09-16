<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SkmDemand;
use App\Models\SkmOrder;
use App\Models\SkmOrderItem;
use App\Models\StorageLocation;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SummaryKanbanController extends Controller
{
    // ── Kanban cycle constants ────────────────────────────────────────────
    const LT_DAYS      = 3;
    const SS_DAYS      = 2;
    const PROCESS_DAYS = 1;

    // ── Index ─────────────────────────────────────────────────────────────

    public function index()
    {
        $orders  = SkmOrder::with('createdBy')->withCount('items')->latest()->paginate(20);
        $pending = $this->getPendingItems();
        $demands = SkmDemand::with('material')->where('is_active', true)->get();

        $stats = [
            'total'            => SkmOrder::count(),
            'draft'            => SkmOrder::where('status', 'draft')->count(),
            'sent'             => SkmOrder::where('status', 'sent')->count(),
            'partial_received' => SkmOrder::where('status', 'partial_received')->count(),
            'completed'        => SkmOrder::where('status', 'completed')->count(),
            'pending'          => count($pending),
        ];

        return view('summary_kanban.index', compact('orders', 'stats', 'pending', 'demands'));
    }

    // ── Create ────────────────────────────────────────────────────────────

    public function create()
    {
        $pending          = $this->getPendingItems();
        $storageLocations = StorageLocation::where('is_scrap', false)->orderBy('name')->get();

        if (empty($pending)) {
            return redirect()->route('summary_kanban.index')
                ->with('error', 'Tidak ada item yang perlu dipesan saat ini. Semua stok mencukupi atau sudah ada SKM aktif.');
        }

        return view('summary_kanban.create', compact('pending', 'storageLocations'));
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'storage_location_id'    => 'nullable|exists:storage_locations,id',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.material_id'    => 'required|exists:materials,id',
            'items.*.num_cards'      => 'required|integer|min:1',
        ]);

        $scrapIds          = StorageLocation::where('is_scrap', true)->pluck('id');
        $expectedDelivery  = $request->expected_delivery_date;
        $storageLocationId = $request->storage_location_id;

        DB::transaction(function () use ($request, $scrapIds, $expectedDelivery, $storageLocationId) {
            $skm = SkmOrder::create([
                'skm_number' => SkmOrder::generateNumber(),
                'order_date' => $request->order_date,
                'status'     => 'draft',
                'notes'      => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                if (empty($item['material_id'])) continue;

                $material     = Material::find($item['material_id']);
                $kanbanQty    = (float) ($material->qty_per_case ?? 0);
                $numCards     = (int) $item['num_cards'];
                $orderQty     = $kanbanQty * $numCards;
                $currentStock = (float) MaterialStock::where('material_id', $material->id)
                    ->when($scrapIds->isNotEmpty(), fn($q) => $q->whereNotIn('storage_location_id', $scrapIds))
                    ->sum('qty');

                SkmOrderItem::create([
                    'skm_order_id'           => $skm->id,
                    'material_id'            => $material->id,
                    'vendor_id'              => $material->vendor_id,
                    'kanban_qty'             => $kanbanQty,
                    'num_cards'              => $numCards,
                    'order_qty'              => $orderQty,
                    'expected_delivery_date' => $expectedDelivery,
                    'storage_location_id'    => $storageLocationId,
                    'current_stock'          => $currentStock,
                    'min_stock'              => (float) $material->min_stock,
                    'notes'                  => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('summary_kanban.index')->with('success', 'SKM berhasil dibuat.');
    }

    // ── Show ──────────────────────────────────────────────────────────────

    public function show(SkmOrder $skm)
    {
        $skm->load('items.material', 'items.vendor', 'items.storageLocation', 'createdBy', 'purchaseOrders.items');
        return view('summary_kanban.show', compact('skm'));
    }

    // ── Update Status ─────────────────────────────────────────────────────

    public function updateStatus(Request $request, SkmOrder $skm)
    {
        $request->validate(['status' => 'required|in:sent,cancelled']);

        $allowed = [
            'draft'            => ['sent', 'cancelled'],
            'sent'             => ['cancelled'],
            'partial_received' => ['cancelled'],
            'completed'        => [],
            'cancelled'        => [],
        ];

        if (!in_array($request->status, $allowed[$skm->status] ?? [])) {
            return back()->with('error', 'Perubahan status tidak valid.');
        }

        $skm->update(['status' => $request->status]);
        return back()->with('success', 'Status SKM diperbarui menjadi "' . $skm->fresh()->status_label . '".');
    }

    // ── Generate PO ───────────────────────────────────────────────────────

    public function generatePo(SkmOrder $skm)
    {
        if (!in_array($skm->status, ['draft', 'sent', 'partial_received'])) {
            return back()->with('error', 'PO hanya bisa dibuat dari SKM berstatus Draft, Dikirim, atau Diterima Sebagian.');
        }

        $skm->loadMissing('purchaseOrders');
        if ($skm->purchaseOrders->isNotEmpty()) {
            return back()->with('error', 'Purchase Order sudah dibuat dari SKM ini. Tidak bisa generate PO duplikat.');
        }

        $skm->load('items.material', 'items.storageLocation');

        DB::transaction(function () use ($skm) {
            $byVendor = $skm->items->groupBy('vendor_id');

            foreach ($byVendor as $vendorId => $items) {
                if (!$vendorId) continue;

                $totalAmount       = $items->sum(fn($i) => (float) $i->order_qty * (float) ($i->material->standard_price ?? 0));
                $expectedDelivery  = $items->first()?->expected_delivery_date;
                $storageLocationId = $items->first()?->storage_location_id;

                $po = PurchaseOrder::create([
                    'no_po'               => PurchaseOrder::generateNumber(),
                    'skm_order_id'        => $skm->id,
                    'vendor_id'           => $vendorId,
                    'storage_location_id' => $storageLocationId,
                    'tanggal_order'       => $skm->order_date,
                    'estimasi_terima'     => $expectedDelivery ?? $skm->order_date,
                    'status'              => 'Approved',
                    'total_amount'        => $totalAmount,
                    'catatan'             => 'Auto-generated dari ' . $skm->skm_number,
                    'created_by'          => auth()->id(),
                ]);

                foreach ($items as $item) {
                    $unitPrice  = (float) ($item->material->standard_price ?? 0);
                    $totalPrice = $unitPrice * (float) $item->order_qty;
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'material_id'       => $item->material_id,
                        'qty'               => $item->order_qty,
                        'qty_received'      => 0,
                        'unit_price'        => $unitPrice,
                        'total_price'       => $totalPrice,
                    ]);
                }
            }

            if ($skm->status === 'draft') {
                $skm->update(['status' => 'sent']);
            }
        });

        return back()->with('success', 'Purchase Order berhasil dibuat dari SKM ' . $skm->skm_number . '.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(SkmOrder $skm)
    {
        if ($skm->status !== 'draft') {
            return back()->with('error', 'Hanya SKM berstatus Draft yang dapat dihapus.');
        }
        $skm->items()->delete();
        $skm->delete();
        return redirect()->route('summary_kanban.index')->with('success', 'SKM berhasil dihapus.');
    }

    // ── Export Excel ──────────────────────────────────────────────────────

    public function exportExcel(SkmOrder $skm)
    {
        $skm->load('items.material', 'items.vendor', 'createdBy');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SKM');

        $sheet->setCellValue('A1', 'SUMMARY KANBAN MATERIAL — ' . $skm->skm_number);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Tanggal: ' . $skm->order_date->format('d M Y') . '   |   Status: ' . $skm->status_label . '   |   Dibuat oleh: ' . ($skm->createdBy->name ?? '-'));
        $sheet->mergeCells('A2:J2');
        ExcelService::applyNoteStyle($spreadsheet, 'A2:J2');

        $headers = ['#', 'Kode Material', 'Nama Material', 'Satuan', 'Vendor', 'Stok Saat SKM', 'Min. Stok', 'Qty/Kartu', 'Jml Kartu', 'Total Order'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '3', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A3:J3');

        foreach ($skm->items as $idx => $item) {
            $row = $idx + 4;
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $item->material->code ?? '');
            $sheet->setCellValue("C{$row}", $item->material->name ?? '');
            $sheet->setCellValue("D{$row}", $item->material->unit_of_measure ?? '');
            $sheet->setCellValue("E{$row}", $item->vendor->name ?? '-');
            $sheet->setCellValue("F{$row}", (float) $item->current_stock);
            $sheet->setCellValue("G{$row}", (float) $item->min_stock);
            $sheet->setCellValue("H{$row}", (float) $item->kanban_qty);
            $sheet->setCellValue("I{$row}", $item->num_cards);
            $sheet->setCellValue("J{$row}", (float) $item->order_qty);
            ExcelService::applyDataStyle($spreadsheet, "A{$row}:J{$row}", $idx % 2 === 0);
        }

        foreach (['A' => 4, 'B' => 16, 'C' => 32, 'D' => 8, 'E' => 24, 'F' => 14, 'G' => 12, 'H' => 12, 'I' => 10, 'J' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        return ExcelService::download($spreadsheet, 'SKM_' . $skm->skm_number . '_' . $skm->order_date->format('Ymd') . '.xlsx');
    }

    // ── Export PDF ────────────────────────────────────────────────────────

    public function exportPdf(SkmOrder $skm)
    {
        $skm->load('items.material', 'items.vendor', 'items.storageLocation', 'createdBy');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('summary_kanban.pdf', compact('skm'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('SKM_' . $skm->skm_number . '.pdf');
    }

    // ── Demand: Download Template ─────────────────────────────────────────

    public function demandTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SKM Demand');

        $sheet->setCellValue('A1', 'Kode Material (FP/WIP)');
        $sheet->setCellValue('B1', 'Demand Qty (pcs)');
        $sheet->setCellValue('C1', 'Hari Kerja');
        $sheet->setCellValue('D1', 'Periode (misal 2026-06)');
        $sheet->setCellValue('E1', 'Catatan');
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:E1');

        $sheet->setCellValue('A2', 'FP-001');
        $sheet->setCellValue('B2', 5000);
        $sheet->setCellValue('C2', 22);
        $sheet->setCellValue('D2', date('Y-m'));
        $sheet->setCellValue('E2', 'Contoh');
        ExcelService::applyDataStyle($spreadsheet, 'A2:E2', true);

        foreach (['A' => 28, 'B' => 16, 'C' => 14, 'D' => 16, 'E' => 24] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        return ExcelService::download($spreadsheet, 'skm_demand_template_' . date('Ymd') . '.xlsx');
    }

    // ── Demand: Import ────────────────────────────────────────────────────

    public function importDemands(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

        $path        = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        $errors   = [];
        $imported = 0;

        SkmDemand::where('is_active', true)->delete();

        foreach (array_slice($rows, 1) as $rowNum => $row) {
            $code = trim((string) ($row[0] ?? ''));
            $qty  = trim((string) ($row[1] ?? ''));
            if ($code === '' && $qty === '') continue;

            $lineLabel = 'Baris ' . ($rowNum + 2);

            if ($code === '') { $errors[] = "{$lineLabel}: Kode Material kosong."; continue; }
            if (!is_numeric($qty) || (float)$qty <= 0) { $errors[] = "{$lineLabel}: Demand Qty tidak valid."; continue; }

            $workingDays = (int) ($row[2] ?? 22);
            $workingDays = ($workingDays >= 1 && $workingDays <= 31) ? $workingDays : 22;

            $material = Material::where('code', $code)->where('is_active', true)->first();
            if (!$material) { $errors[] = "{$lineLabel}: Material '{$code}' tidak ditemukan."; continue; }
            if (!in_array($material->type, ['FP', 'WIP'])) { $errors[] = "{$lineLabel}: '{$code}' bukan FP/WIP."; continue; }

            SkmDemand::create([
                'material_id'  => $material->id,
                'demand_qty'   => (float)$qty,
                'working_days' => $workingDays,
                'period'       => trim((string)($row[3] ?? '')) ?: date('Y-m'),
                'notes'        => trim((string)($row[4] ?? '')) ?: null,
                'is_active'    => true,
                'created_by'   => auth()->id(),
            ]);
            $imported++;
        }

        if ($imported === 0 && empty($errors)) {
            return back()->with('error', 'File tidak berisi data.');
        }

        $msg = "{$imported} demand berhasil diimpor.";
        if (!empty($errors)) {
            $shown = array_slice($errors, 0, 5);
            $msg  .= ' Error: ' . implode('; ', $shown);
        }

        return back()->with($imported > 0 ? 'success' : 'error', $msg);
    }

    // ── Demand: Clear ─────────────────────────────────────────────────────

    public function clearDemands()
    {
        SkmDemand::where('is_active', true)->delete();
        return back()->with('success', 'Semua demand SKM aktif dihapus.');
    }

    // ── Helper: Pending Items Calculation ────────────────────────────────

    private function getPendingItems(): array
    {
        $scrapIds = StorageLocation::where('is_scrap', true)->pluck('id');

        $openIds = SkmOrderItem::whereHas('skmOrder', fn($q) =>
            $q->whereIn('status', ['draft', 'sent'])
        )->pluck('material_id')->unique()->toArray();

        $demands     = SkmDemand::with('material')->where('is_active', true)->get();
        $rmDemand    = [];
        $workingDays = 22;

        foreach ($demands as $d) {
            $bq = $this->getRmRequirementPerFp($d->material_id);
            foreach ($bq as $rmId => $sheetsPerFp) {
                $rmDemand[$rmId] = ($rmDemand[$rmId] ?? 0) + ((float)$d->demand_qty * $sheetsPerFp);
            }
            $workingDays = (int) $d->working_days ?: 22;
        }

        $outstandingByMat = SkmOrderItem::whereHas('skmOrder', fn($q) =>
            $q->whereIn('status', ['sent', 'partial_received'])
        )->get()->groupBy('material_id')->map(function ($items) {
            return $items->sum(function ($item) {
                $poItems = PurchaseOrderItem::whereHas('purchaseOrder', fn($q) =>
                    $q->where('skm_order_id', $item->skm_order_id)
                      ->whereIn('status', ['approved', 'Approved', 'partially_received', 'Partially Received'])
                )->where('material_id', $item->material_id)->get();

                // PPLC uses qty/qty_received columns
                return $poItems->sum(fn($pi) => max(0, (float)($pi->qty ?? $pi->quantity ?? 0) - (float)($pi->qty_received ?? $pi->quantity_received ?? 0)));
            });
        });

        // PPLC materials table uses: tipe (not type), status='Aktif' (not is_active), qty_case (not qty_per_case)
        // order_method column does NOT exist in PPLC — select all RM materials with qty_case > 0
        $materials = Material::where('tipe', 'RM')
            ->where('status', 'Aktif')
            ->where('qty_case', '>', 0)
            ->with('vendor')
            ->get();

        $pending = [];
        foreach ($materials as $mat) {
            if (in_array($mat->id, $openIds)) continue;

            $qpc = (float) $mat->qty_per_case;

            $currentStock = (float) MaterialStock::where('material_id', $mat->id)
                ->when($scrapIds->isNotEmpty(), fn($q) => $q->whereNotIn('storage_location_id', $scrapIds))
                ->sum('qty');

            $rmSheetDemand = $rmDemand[$mat->id] ?? 0;

            if ($rmSheetDemand > 0) {
                $spd          = $rmSheetDemand / max(1, $workingDays);
                $kanbanPerDay = (int) ceil($spd / $qpc);
                $totalKanban  = $kanbanPerDay * (self::LT_DAYS + self::SS_DAYS + self::PROCESS_DAYS);
            } else {
                $minStock     = (float) $mat->min_stock;
                $totalKanban  = (int) ceil($minStock / $qpc);
                $kanbanPerDay = 0;
            }

            $stockKanban       = (int) floor($currentStock / $qpc);
            $outstandingQty    = (float) ($outstandingByMat[$mat->id] ?? 0);
            $outstandingKanban = (int) floor($outstandingQty / $qpc);
            $orderKanban       = max(0, $totalKanban - $stockKanban - $outstandingKanban);

            if ($orderKanban <= 0) continue;

            $pending[] = [
                'material'           => $mat,
                'current_stock'      => $currentStock,
                'min_stock'          => (float) $mat->min_stock,
                'kanban_qty'         => $qpc,
                'kanban_per_day'     => $kanbanPerDay,
                'total_kanban'       => $totalKanban,
                'stock_kanban'       => $stockKanban,
                'outstanding_kanban' => $outstandingKanban,
                'outstanding_qty'    => $outstandingQty,
                'working_days'       => $workingDays,
                'rm_sheet_demand'    => $rmSheetDemand,
                'num_cards_suggest'  => $orderKanban,
                'order_qty_suggest'  => $orderKanban * $qpc,
                'shortage'           => max(0, $totalKanban * $qpc - $currentStock - $outstandingQty),
            ];
        }

        return $pending;
    }

    private function getRmRequirementPerFp(int $materialId, float $qty = 1.0, array $visited = []): array
    {
        if (in_array($materialId, $visited)) return [];

        $material = Material::find($materialId);
        if (!$material) return [];

        if ($material->tipe === 'RM') {
            return [$materialId => $qty];
        }

        $bom = Bom::with('items')
            ->where('material_id', $materialId)
            ->where('status', 'active')
            ->latest()->first();

        if (!$bom || $bom->items->isEmpty()) {
            return [$materialId => $qty];
        }

        $multiplier = $qty / max(0.001, (float) $bom->base_quantity);
        $chain      = [...$visited, $materialId];
        $result     = [];

        foreach ($bom->items as $item) {
            $sub = $this->getRmRequirementPerFp($item->material_id, (float) $item->quantity * $multiplier, $chain);
            foreach ($sub as $rmId => $sheets) {
                $result[$rmId] = ($result[$rmId] ?? 0) + $sheets;
            }
        }

        return $result;
    }
}
