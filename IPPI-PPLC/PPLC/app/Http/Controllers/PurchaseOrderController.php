<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StorageLocation;
use App\Models\Vendor;
use App\Models\MaterialStock as Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with('vendor');
        $query = $this->applyFilters($query, $request);
        
        $pos     = $query->latest()->paginate(20)->withQueryString();
        $vendors = Vendor::where('status', 'Aktif')->orderBy('nama')->get();
        return view('purchase_orders.index', compact('pos', 'vendors'));
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('status')) {
            $statusVal = $request->status;
            if ($statusVal === 'draft') {
                $query->whereIn('status', ['draft', 'Draft']);
            } elseif ($statusVal === 'approved') {
                $query->whereIn('status', ['approved', 'Approved']);
            } elseif ($statusVal === 'partially_received' || $statusVal === 'partial_received') {
                $query->whereIn('status', ['partially_received', 'Partially Received']);
            } elseif ($statusVal === 'received') {
                $query->whereIn('status', ['received', 'Received']);
            } elseif ($statusVal === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'Cancelled']);
            } else {
                $query->where('status', $statusVal);
            }
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('no_po', 'like', "%{$request->search}%")
                  ->orWhereHas('vendor', fn($v) => $v->where('nama', 'like', "%{$request->search}%"));
            });
        }
        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from;
            try {
                $parsed = null;
                foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $fmt) {
                    try {
                        $parsed = \Carbon\Carbon::createFromFormat($fmt, trim($dateFrom))->format('Y-m-d');
                        break;
                    } catch (\Exception $e) {}
                }
                if (!$parsed) {
                    $parsed = \Carbon\Carbon::parse($dateFrom)->format('Y-m-d');
                }
                $query->where('tanggal_order', '>=', $parsed);
            } catch (\Exception $e) {
                $query->where('tanggal_order', '>=', $dateFrom);
            }
        }
        if ($request->filled('date_to')) {
            $dateTo = $request->date_to;
            try {
                $parsed = null;
                foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $fmt) {
                    try {
                        $parsed = \Carbon\Carbon::createFromFormat($fmt, trim($dateTo))->format('Y-m-d');
                        break;
                    } catch (\Exception $e) {}
                }
                if (!$parsed) {
                    $parsed = \Carbon\Carbon::parse($dateTo)->format('Y-m-d');
                }
                $query->where('tanggal_order', '<=', $parsed);
            } catch (\Exception $e) {
                $query->where('tanggal_order', '<=', $dateTo);
            }
        }
        if ($request->filled('f_po_number')) {
            $query->where('no_po', 'like', "%{$request->f_po_number}%");
        }
        if ($request->filled('f_vendor')) {
            $query->whereHas('vendor', fn($v) => $v->where('nama', 'like', "%{$request->f_vendor}%"));
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        return $query;
    }

    public function create()
    {
        $vendors   = Vendor::where('status', 'Aktif')->get();
        $materials = Material::where('status', 'Aktif')->orderBy('tipe')->orderBy('kode')->get();
        $locations = StorageLocation::orderBy('kode')->get();
        return view('purchase_orders.create', compact('vendors', 'materials', 'locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id'              => 'required|exists:vendors,id',
            'storage_location_id'    => 'required|exists:storage_locations,id',
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes'                  => 'nullable|string',
            'items'                          => 'required|array|min:1',
            'items.*.material_id'            => 'required|exists:materials,id',
            'items.*.quantity'               => 'required|numeric|min:0.001',
            'items.*.unit_price'             => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $materialIds = collect($request->items)->pluck('material_id')->filter()->unique()->values();
            $materialsById = Material::whereIn('id', $materialIds)->get()->keyBy('id');
            $vendorId = (int) $request->vendor_id;

            foreach ($request->items as $row) {
                $material = $materialsById[$row['material_id']] ?? null;
                if (!$material) {
                    throw ValidationException::withMessages(['items' => 'Material tidak ditemukan.']);
                }

                $allowedVendors = array_filter([
                    $material->vendor_id         ? (int) $material->vendor_id         : null,
                    $material->process_vendor_id ? (int) $material->process_vendor_id : null,
                ]);
                if (!empty($allowedVendors) && !in_array($vendorId, $allowedVendors)) {
                    throw ValidationException::withMessages([
                        'items' => "Material {$material->kode} tidak terhubung dengan vendor yang dipilih. Pastikan vendor sesuai dengan supply vendor atau process vendor material tersebut.",
                    ]);
                }
            }

            $po = PurchaseOrder::create([
                'no_po'                  => PurchaseOrder::generateNumber(),
                'vendor_id'              => $request->vendor_id,
                'storage_location_id'    => $request->storage_location_id,
                'tanggal_order'          => $request->order_date,
                'estimasi_terima'        => $request->expected_delivery_date,
                'catatan'                => $request->notes,
                'status'                 => 'draft',
                'total_amount'           => 0,
                'created_by'             => Auth::id(),
            ]);

            $total = 0;
            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $total += $lineTotal;
                $po->items()->create([
                    'material_id'            => $item['material_id'],
                    'qty'                    => $item['quantity'],
                    'unit_price'             => $item['unit_price'],
                    'total_price'            => $lineTotal,
                    'expected_delivery_date' => $request->expected_delivery_date ?? null,
                ]);
            }
            $po->update(['total_amount' => $total]);
        });

        return redirect()->route('purchase_orders.index')->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with('vendor', 'storageLocation', 'items.material', 'goodsReceipts.storageLocation', 'createdBy')->findOrFail($id);
        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $canEdit = $purchaseOrder->status === 'draft'
            || ($purchaseOrder->status === 'approved' && $purchaseOrder->skm_order_id !== null);
        if (!$canEdit) {
            return back()->with('error', 'Hanya PO Draft atau PO dari SKM yang berstatus Approved yang dapat diedit.');
        }
        $vendors   = Vendor::where('status', 'Aktif')->get();
        $materials = Material::where('status', 'Aktif')->orderBy('tipe')->orderBy('kode')->get();
        $locations = StorageLocation::orderBy('kode')->get();
        $purchaseOrder->load('items.material');
        return view('purchase_orders.edit', compact('purchaseOrder', 'vendors', 'materials', 'locations'));
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $canEdit = $purchaseOrder->status === 'draft'
            || ($purchaseOrder->status === 'approved' && $purchaseOrder->skm_order_id !== null);
        if (!$canEdit) {
            return back()->with('error', 'Hanya PO Draft atau PO dari SKM yang berstatus Approved yang dapat diedit.');
        }
        $request->validate([
            'po_number'              => 'required|string|max:50|unique:purchase_orders,no_po,' . $purchaseOrder->id,
            'vendor_id'              => 'required|exists:vendors,id',
            'storage_location_id'    => 'required|exists:storage_locations,id',
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'items'                          => 'required|array|min:1',
            'items.*.material_id'            => 'required|exists:materials,id',
            'items.*.quantity'               => 'required|numeric|min:0.001',
            'items.*.unit_price'             => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            $materialIds = collect($request->items)->pluck('material_id')->filter()->unique()->values();
            $materialsById = Material::whereIn('id', $materialIds)->get()->keyBy('id');
            $vendorId = (int) $request->vendor_id;

            foreach ($request->items as $row) {
                $material = $materialsById[$row['material_id']] ?? null;
                if (!$material) {
                    throw ValidationException::withMessages(['items' => 'Material tidak ditemukan.']);
                }

                $allowedVendors = array_filter([
                    $material->vendor_id         ? (int) $material->vendor_id         : null,
                    $material->process_vendor_id ? (int) $material->process_vendor_id : null,
                ]);
                if (!empty($allowedVendors) && !in_array($vendorId, $allowedVendors)) {
                    throw ValidationException::withMessages([
                        'items' => "Material {$material->kode} tidak terhubung dengan vendor yang dipilih. Pastikan vendor sesuai dengan supply vendor atau process vendor material tersebut.",
                    ]);
                }
            }

            $purchaseOrder->items()->delete();
            $total = 0;
            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $total += $lineTotal;
                $purchaseOrder->items()->create([
                    'material_id'            => $item['material_id'],
                    'qty'                    => $item['quantity'],
                    'unit_price'             => $item['unit_price'],
                    'total_price'            => $lineTotal,
                    'expected_delivery_date' => $request->expected_delivery_date ?? null,
                ]);
            }
            $purchaseOrder->update([
                'no_po'                  => $request->po_number,
                'vendor_id'              => $request->vendor_id,
                'storage_location_id'    => $request->storage_location_id,
                'tanggal_order'          => $request->order_date,
                'estimasi_terima'        => $request->expected_delivery_date,
                'catatan'                => $request->notes,
                'total_amount'           => $total,
            ]);
        });

        return redirect()->route('purchase_orders.show', $purchaseOrder->id)->with('success', 'Purchase Order berhasil diperbarui.');
    }

    public function printDetailPdf($id)
    {
        $purchaseOrder = PurchaseOrder::with('vendor', 'storageLocation', 'items.material', 'createdBy')->findOrFail($id);

        // Generate barcode as inline SVG (more reliable in DomPDF than PNG)
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $barcodeSvg = $generator->getBarcode(
            $purchaseOrder->po_number,
            \Picqer\Barcode\BarcodeGeneratorSVG::TYPE_CODE_128,
            2,   // width factor
            50   // height px
        );
        $barcodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($barcodeSvg);

        $pdf = Pdf::loadView('purchase_orders.pdf', compact('purchaseOrder', 'barcodeBase64'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream('PO-' . $purchaseOrder->po_number . '.pdf');
    }

    public function approve($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Hanya PO Draft yang dapat di-approve.');
        }

        // Block manual approve after the expected delivery date has passed
        if ($purchaseOrder->expected_delivery_date && today()->gt($purchaseOrder->expected_delivery_date)) {
            return back()->with('error', 'Tidak dapat approve: tanggal estimasi pengiriman sudah terlewat.');
        }

        $purchaseOrder->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::user()?->name ?: 'Administrator',
        ]);
        return back()->with('success', 'Purchase Order berhasil di-approve.');
    }

    public function cancel($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'PO tidak dapat dibatalkan.');
        }
        $purchaseOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Purchase Order berhasil dibatalkan.');
    }

    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Hanya PO Draft yang dapat dihapus.');
        }
        $purchaseOrder->delete();
        return redirect()->route('purchase_orders.index')->with('success', 'Purchase Order berhasil dihapus.');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Template input ────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Template PO');

        // Instructions row
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT PURCHASE ORDER — Kolom A: Kode Material | B: Qty | C: Harga Satuan | D: Tanggal Order (DD/MM/YYYY, cukup baris pertama) | E: Est. Pengiriman per-item (DD/MM/YYYY, isi tiap baris). Lihat sheet "Daftar Material" untuk referensi kode.');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF92400E']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF8DC']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(18);

        // Header row
        $headers = ['Kode Material *', 'Qty *', 'Harga Satuan *', 'Tanggal Order *', 'Est. Pengiriman *'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}2", $h);
        }
        $sheet->getStyle('A2:E2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample rows — tanggal hari ini dan +7
        $today   = now()->format('d/m/Y');
        $deliver = now()->addDays(7)->format('d/m/Y');
        $samples = [['RM-001', 10, 15000, $today, $deliver], ['RM-002', 5, 25000, $today, $deliver]];
        foreach ($samples as $i => $row) {
            $r = $i + 3;
            $sheet->setCellValue("A{$r}", $row[0]);
            $sheet->setCellValue("B{$r}", $row[1]);
            $sheet->setCellValue("C{$r}", $row[2]);
            $sheet->setCellValue("D{$r}", $row[3]);
            $sheet->setCellValue("E{$r}", $row[4]);
            $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F6FA']],
                'font'    => ['color' => ['argb' => 'FF9CA3AF'], 'italic' => true],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);
        }

        // Number/date formats
        $sheet->getStyle('B3:B1000')->getNumberFormat()->setFormatCode('#,##0.000');
        $sheet->getStyle('C3:C1000')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D3:D1000')->getNumberFormat()->setFormatCode('@'); // teks agar tidak auto-konversi
        $sheet->getStyle('E3:E1000')->getNumberFormat()->setFormatCode('@');

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);

        // ── Sheet 2: Daftar Material (reference) ──────────────────────
        $ref = $spreadsheet->createSheet()->setTitle('Daftar Material');
        $ref->setCellValue('A1', 'Kode Material');
        $ref->setCellValue('B1', 'Nama Material');
        $ref->setCellValue('C1', 'Tipe');
        $ref->setCellValue('D1', 'UoM');
        $ref->setCellValue('E1', 'Harga Standar');
        $ref->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $materials = Material::where('status', 'Aktif')->orderBy('tipe')->orderBy('kode')->get();
        foreach ($materials as $i => $m) {
            $r   = $i + 2;
            $bg  = $i % 2 === 0 ? 'FFFFFFFF' : 'FFF3F6FA';
            $ref->setCellValue("A{$r}", $m->kode);
            $ref->setCellValue("B{$r}", $m->nama);
            $ref->setCellValue("C{$r}", $m->tipe);
            $ref->setCellValue("D{$r}", $m->uom);
            $ref->setCellValue("E{$r}", (float) $m->standard_price);
            $ref->getStyle("A{$r}:E{$r}")->applyFromArray([
                'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);
        }
        foreach (['A' => 16, 'B' => 36, 'C' => 8, 'D' => 8, 'E' => 16] as $col => $w) {
            $ref->getColumnDimension($col)->setWidth($w);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return ExcelService::download($spreadsheet, 'Template_Import_PO.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);

        $path  = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0);
        $rows  = $sheet->toArray(null, true, false, true);

        // Build material lookup: code → {id, name, price}
        $materials = Material::where('status', 'Aktif')
            ->get(['id', 'kode', 'nama', 'standard_price', 'tipe'])
            ->keyBy('kode');

        $items  = [];
        $errors = [];

        // Helper: parse DD/MM/YYYY string or Excel serial → Y-m-d
        $parseDate = function ($val): ?string {
            if ($val === null || $val === '') return null;
            if (is_numeric($val)) {
                // Excel date serial
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $val);
                return $dt ? $dt->format('Y-m-d') : null;
            }
            $v = trim((string) $val);
            if ($v === '') return null;
            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y'] as $fmt) {
                try { return \Carbon\Carbon::createFromFormat($fmt, $v)->format('Y-m-d'); } catch (\Exception $e) {}
            }
            try { return \Carbon\Carbon::parse($v)->format('Y-m-d'); } catch (\Exception $e) {}
            return null;
        };

        // Read header order date from first data row (baris 3)
        $orderDate = null;
        foreach ($rows as $rowNum => $row) {
            if ($rowNum <= 2) continue;
            $orderDate = $parseDate($row['D'] ?? null);
            break;
        }

        foreach ($rows as $rowNum => $row) {
            if ($rowNum <= 2) continue;                          // skip instruction + header rows
            $code  = trim((string) ($row['A'] ?? ''));
            $qty   = (float) ($row['B'] ?? 0);
            $price = (float) ($row['C'] ?? 0);
            $itemDeliveryDate = $parseDate($row['E'] ?? null); // per-item

            if ($code === '' && $qty == 0) continue;             // blank row — skip

            if ($code === '') {
                $errors[] = "Baris {$rowNum}: Kode Material kosong.";
                continue;
            }
            $material = $materials->get($code);
            if (!$material) {
                $errors[] = "Baris {$rowNum}: Kode material '{$code}' tidak ditemukan.";
                continue;
            }
            if ($qty <= 0) {
                $errors[] = "Baris {$rowNum}: Qty harus lebih dari 0 (material {$code}).";
                continue;
            }

            $items[] = [
                'material_id'           => $material->id,
                'material_code'         => $material->kode,
                'material_name'         => $material->nama,
                'material_type'         => $material->tipe,
                'quantity'              => $qty,
                'unit_price'            => $price > 0 ? $price : (float) $material->standard_price,
                'expected_delivery_date'=> $itemDeliveryDate,
            ];
        }

        return response()->json([
            'items'      => $items,
            'errors'     => $errors,
            'order_date' => $orderDate,
        ]);
    }

    public function importCreate(Request $request)
    {
        $request->validate([
            'vendor_id'                    => 'required|exists:vendors,id',
            'storage_location_id'          => 'required|exists:storage_locations,id',
            'order_date'                   => 'required|date',
            'notes'                        => 'nullable|string',
            'groups'                       => 'required|array|min:1',
            'groups.*.delivery_date'       => 'nullable|date',
            'groups.*.items'               => 'required|array|min:1',
            'groups.*.items.*.material_id' => 'required|exists:materials,id',
            'groups.*.items.*.quantity'    => 'required|numeric|min:0.001',
            'groups.*.items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $created = [];

        DB::transaction(function () use ($request, &$created) {
            foreach ($request->groups as $group) {
                $po = PurchaseOrder::create([
                    'no_po'                  => PurchaseOrder::generateNumber(),
                    'vendor_id'              => $request->vendor_id,
                    'storage_location_id'    => $request->storage_location_id,
                    'tanggal_order'          => $request->order_date,
                    'estimasi_terima'        => $group['delivery_date'] ?? null,
                    'catatan'                => $request->notes,
                    'status'                 => 'draft',
                    'total_amount'           => 0,
                    'created_by'             => Auth::id(),
                ]);

                $total = 0;
                foreach ($group['items'] as $item) {
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $total += $lineTotal;
                    $po->items()->create([
                        'material_id'            => $item['material_id'],
                        'qty'                    => $item['quantity'],
                        'unit_price'             => $item['unit_price'],
                        'total_price'            => $lineTotal,
                        'expected_delivery_date' => $group['delivery_date'] ?? null,
                    ]);
                }
                $po->update(['total_amount' => $total]);
                $created[] = ['po_number' => $po->po_number, 'id' => $po->id];
            }
        });

        return response()->json([
            'success'    => true,
            'po_numbers' => $created,
            'redirect'   => route('purchase_orders.index'),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $query = PurchaseOrder::with('vendor', 'items.material');
        $query = $this->applyFilters($query, $request);
        $pos = $query->orderBy('id','desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchase Orders');

        $headers = ['No. PO','Vendor','Tgl Order','Est. Terima','Lokasi','Status','Item Material','Qty Order','Qty Terima'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:I1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $r = 2;
        foreach ($pos as $po) {
            foreach ($po->items as $item) {
                $sheet->setCellValue("A{$r}", $po->po_number);
                $sheet->setCellValue("B{$r}", $po->vendor->nama ?? '-');
                $sheet->setCellValue("C{$r}", $po->order_date->format('d/m/Y'));
                $sheet->setCellValue("D{$r}", $po->expected_delivery_date?->format('d/m/Y') ?? '-');
                $sheet->setCellValue("E{$r}", $po->storageLocation->kode ?? '-');
                $sheet->setCellValue("F{$r}", ucfirst(str_replace('_',' ',$po->status)));
                $sheet->setCellValue("G{$r}", $item->material->kode.' - '.$item->material->nama);
                $sheet->setCellValue("H{$r}", (float)$item->qty);
                $sheet->setCellValue("I{$r}", (float)$item->qty_received);
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:I{$r}", $r % 2 === 0);
                $r++;
            }
            if ($po->items->isEmpty()) {
                $sheet->setCellValue("A{$r}", $po->po_number);
                $sheet->setCellValue("B{$r}", $po->vendor->nama ?? '-');
                $sheet->setCellValue("C{$r}", $po->order_date->format('d/m/Y'));
                $sheet->setCellValue("F{$r}", ucfirst(str_replace('_',' ',$po->status)));
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:I{$r}", $r % 2 === 0);
                $r++;
            }
        }
        foreach (range('A','I') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'purchase_orders_'.date('Ymd').'.xlsx');
    }

    public function printPdf(Request $request)
    {
        $query = PurchaseOrder::with('vendor');
        $query = $this->applyFilters($query, $request);
        $pos = $query->orderBy('id','desc')->get();

        $filters = $request->only(['search','status','date_from','date_to']);

        $pdf = Pdf::loadView('purchase_orders.pdf-list', compact('pos', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('purchase_orders_'.date('Ymd').'.pdf');
    }
}
