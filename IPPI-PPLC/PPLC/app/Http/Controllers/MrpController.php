<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Material;
use App\Models\MrpDemand;
use App\Models\MrpResult;
use App\Models\MrpRun;
use App\Models\PurchaseOrderItem;
use App\Models\MaterialStock;
use App\Models\StorageLocation;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MrpController extends Controller
{
    public function index()
    {
        $runs    = MrpRun::with('runBy')->latest()->paginate(15);
        $demands = MrpDemand::with('material')->where('is_active', true)->orderBy('id')->get();
        return view('mrp.index', compact('runs', 'demands'));
    }

    public function downloadDemandTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Template
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Demand Import');

        $headers = ['Material Code', 'Order Quantity', 'Customer Name', 'Notes'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:D1');

        // Example row
        $sheet->setCellValue('A2', 'FP-00001');
        $sheet->setCellValue('B2', 500);
        $sheet->setCellValue('C2', 'PT Contoh Customer');
        $sheet->setCellValue('D2', 'Order April 2026');
        ExcelService::applyDataStyle($spreadsheet, 'A2:D2', true);

        // Notes
        $notes = [
            4 => 'CATATAN PENGISIAN:',
            5 => '- Kolom A (Material Code): wajib diisi. Harus kode FP atau WIP yang aktif di sistem (lihat Sheet "Ref Material").',
            6 => '- Kolom B (Order Quantity): wajib diisi. Angka > 0. MRP akan mengeksplosi ke bahan baku via BOM.',
            7 => '- Kolom C (Customer Name): opsional.',
            8 => '- Kolom D (Notes): opsional.',
            9 => '- Hapus baris contoh (baris 2) sebelum upload.',
        ];
        foreach ($notes as $row => $text) {
            $sheet->setCellValue("A{$row}", $text);
            $sheet->mergeCells("A{$row}:D{$row}");
        }
        ExcelService::applyNoteStyle($spreadsheet, 'A4:D9');

        foreach (['A' => 22, 'B' => 18, 'C' => 28, 'D' => 35] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Sheet 2: Ref Material FP/WIP
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Ref Material');
        $spreadsheet->setActiveSheetIndex(1);

        $refHeaders = ['Kode Material', 'Nama Material', 'Tipe', 'Satuan'];
        foreach ($refHeaders as $i => $h) {
            $ref->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:D1');

        $mats = Material::where('status', 'Aktif')->whereIn('tipe', ['FP', 'WIP'])->orderBy('kode')->get();
        foreach ($mats as $i => $m) {
            $row = $i + 2;
            $ref->setCellValue("A{$row}", $m->kode);
            $ref->setCellValue("B{$row}", $m->nama);
            $ref->setCellValue("C{$row}", $m->tipe);
            $ref->setCellValue("D{$row}", $m->uom);
            ExcelService::applyDataStyle($spreadsheet, "A{$row}:D{$row}", $i % 2 === 0);
        }

        foreach (['A' => 18, 'B' => 35, 'C' => 8, 'D' => 10] as $col => $width) {
            $ref->getColumnDimension($col)->setWidth($width);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return ExcelService::download($spreadsheet, 'mrp_demand_template_' . date('Ymd') . '.xlsx');
    }

    public function importDemands(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $path        = $request->file('excel_file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        $errors   = [];
        $imported = 0;

        foreach (array_slice($rows, 1) as $rowNum => $row) {
            $code = trim((string) ($row[0] ?? ''));
            $qty  = trim((string) ($row[1] ?? ''));

            if ($code === '' && $qty === '') continue;

            $lineLabel = 'Baris ' . ($rowNum + 2);

            if ($code === '') {
                $errors[] = "{$lineLabel}: Material Code kosong.";
                continue;
            }
            if (!is_numeric($qty) || (float) $qty <= 0) {
                $errors[] = "{$lineLabel}: Order Quantity tidak valid ('{$qty}').";
                continue;
            }

            $material = Material::where('kode', $code)->where('status', 'Aktif')->first();
            if (!$material) {
                $errors[] = "{$lineLabel}: Material '{$code}' tidak ditemukan atau tidak aktif.";
                continue;
            }
            if (!in_array($material->tipe, ['FP', 'WIP'])) {
                $errors[] = "{$lineLabel}: Material '{$code}' bukan FP/WIP (tipe: {$material->tipe}). Hanya FP/WIP yang boleh di-input sebagai demand.";
                continue;
            }

            MrpDemand::create([
                'material_id'    => $material->id,
                'order_quantity' => (float) $qty,
                'customer_name'  => trim((string) ($row[2] ?? '')) ?: null,
                'notes'          => trim((string) ($row[3] ?? '')) ?: null,
                'is_active'      => true,
            ]);
            $imported++;
        }

        if ($imported === 0 && empty($errors)) {
            return back()->with('error', 'File tidak berisi data (semua baris kosong).');
        }

        $msg = "{$imported} demand berhasil diimpor.";
        if (!empty($errors)) {
            $shown = array_slice($errors, 0, 5);
            $extra = count($errors) > 5 ? ' ... (dan ' . (count($errors) - 5) . ' baris lainnya)' : '';
            $msg  .= ' Terdapat ' . count($errors) . ' baris error: ' . implode('; ', $shown) . $extra;
        }

        return back()->with($imported > 0 ? 'success' : 'error', $msg);
    }

    public function destroyDemand($id)
    {
        $mrpDemand = MrpDemand::findOrFail($id);
        $mrpDemand->delete();
        return back()->with('success', 'Demand dihapus.');
    }

    public function clearDemands()
    {
        MrpDemand::where('is_active', true)->delete();
        return back()->with('success', 'Semua demand aktif dihapus.');
    }

    public function run(Request $request)
    {
        $demands = MrpDemand::with('material')->where('is_active', true)->get();
        if ($demands->isEmpty()) {
            return back()->with('error', 'Belum ada demand. Import file Excel demand terlebih dahulu.');
        }

        DB::transaction(function () use ($demands) {
            $mrpRun = MrpRun::create([
                'run_date' => now(),
                'run_by'   => auth()->id() ?: (User::first()->id ?? User::create([
                    'name' => 'Operator',
                    'email' => 'operator@example.com',
                    'password' => bcrypt('password123'),
                ])->id),
                'status'   => 'completed',
            ]);

            // Step 1: Multi-level BOM Explosion
            $gross = [];
            foreach ($demands as $demand) {
                $this->explodeBom($demand->material_id, (float) $demand->order_quantity, $gross);
            }

            // Step 2: Effective available stock per RM
            $availableStock = [];
            $scrapLocationIds = StorageLocation::where('is_scrap', true)->pluck('id')->toArray();
            $allStocks = MaterialStock::when(!empty($scrapLocationIds), fn($q) =>
                    $q->whereNotIn('storage_location_id', $scrapLocationIds)
                )->get()
                ->groupBy('material_id')
                ->map(fn($rows) => $rows->sum(fn($s) => (float) $s->qty));

            foreach ($allStocks as $matId => $stockQty) {
                if ($stockQty > 0) {
                    $this->explodeStock((int) $matId, $stockQty, $availableStock);
                }
            }

            // Step 3: Open PO qty per material (Status 'Approved' and 'Partially Received' represent open POs)
            $openPoQtys = PurchaseOrderItem::whereHas('purchaseOrder', fn($q) =>
                    $q->whereIn('status', ['approved', 'Approved', 'partially_received', 'Partially Received'])
                )->get()
                ->groupBy('material_id')
                ->map(fn($items) =>
                    $items->sum(fn($i) => max(0, (float) $i->qty - (float) $i->qty_received))
                );

            // Step 4: Build MRP result per RM
            foreach ($gross as $materialId => $grossQty) {
                $material = Material::find($materialId);
                if (!$material) continue;

                $currentStock = (float) ($availableStock[$materialId] ?? 0);
                $openPo       = (float) ($openPoQtys[$materialId] ?? 0);

                // Net = Gross − Stok Efektif − Sisa PO
                $net = max(0, $grossQty - $currentStock - $openPo);

                // Safety stock +20%
                $safetyQty  = $net * 0.20;
                $withSafety = $net + $safetyQty;

                // Round up to qty_case
                $qpc = (float) ($material->qty_case ?? 0);
                if ($qpc > 0) {
                    $recommended = ceil($withSafety / $qpc) * $qpc;
                } else {
                    $recommended = ceil($withSafety);
                }

                $recType = $material->tipe === 'RM' ? 'purchase' : 'production';

                MrpResult::create([
                    'mrp_run_id'           => $mrpRun->id,
                    'material_id'          => $materialId,
                    'current_stock'        => $currentStock,
                    'required_quantity'    => $grossQty,
                    'gross_requirement'    => $grossQty,
                    'open_po_qty'          => $openPo,
                    'net_requirement'      => $net,
                    'safety_stock_qty'     => $safetyQty,
                    'qty_per_case'         => $qpc,
                    'shortage_quantity'    => max(0, $withSafety - $currentStock),
                    'recommendation_type'  => $recType,
                    'recommended_quantity' => $recommended,
                    'recommended_date'     => now()->addDays(7)->toDateString(),
                ]);
            }
        });

        return redirect()->route('mrp.index')->with('success', 'MRP Run berhasil dijalankan.');
    }

    public function show($id)
    {
        $mrpRun = MrpRun::findOrFail($id);
        $mrpRun->load('results.material', 'runBy');
        return view('mrp.show', compact('mrpRun'));
    }

    public function destroy($id)
    {
        $mrpRun = MrpRun::findOrFail($id);
        $mrpRun->results()->delete();
        $mrpRun->delete();
        return redirect()->route('mrp.index')->with('success', 'MRP Run berhasil dihapus.');
    }

    public function exportExcel($id)
    {
        $mrpRun = MrpRun::findOrFail($id);
        $mrpRun->load('results.material', 'runBy');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil MRP');

        // Title
        $sheet->setCellValue('A1', 'HASIL MRP RUN — ' . $mrpRun->created_at->format('d M Y H:i'));
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('A2', 'Dijalankan oleh: ' . ($mrpRun->runBy->name ?? '-'));
        $sheet->mergeCells('A2:L2');
        ExcelService::applyNoteStyle($spreadsheet, 'A2:L2');

        // Headers
        $headers = [
            'Kode Material', 'Nama Material', 'Satuan',
            'Gross Req.', 'Stok Tersedia', 'Sisa PO',
            'Net Req.', 'Safety 20%', 'Total+Safety',
            'Qty/Case', 'Order ke Vendor', 'Rekomendasi',
        ];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '3', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A3:L3');
        $sheet->getRowDimension(3)->setRowHeight(20);

        $sorted = $mrpRun->results->sortBy(fn($r) => $r->recommendation_type === 'purchase' ? 0 : 1);
        foreach ($sorted->values() as $idx => $result) {
            $row = $idx + 4;
            $withSafety = (float) $result->net_requirement + (float) $result->safety_stock_qty;
            $sheet->setCellValue("A{$row}", $result->material->kode ?? '');
            $sheet->setCellValue("B{$row}", $result->material->nama ?? '');
            $sheet->setCellValue("C{$row}", $result->material->uom ?? '');
            $sheet->setCellValue("D{$row}", (float) $result->gross_requirement);
            $sheet->setCellValue("E{$row}", (float) $result->current_stock);
            $sheet->setCellValue("F{$row}", (float) $result->open_po_qty);
            $sheet->setCellValue("G{$row}", (float) $result->net_requirement);
            $sheet->setCellValue("H{$row}", (float) $result->safety_stock_qty);
            $sheet->setCellValue("I{$row}", $withSafety);
            $sheet->setCellValue("J{$row}", (float) $result->qty_per_case > 0 ? (float) $result->qty_per_case : '-');
            $sheet->setCellValue("K{$row}", (float) $result->recommended_quantity);
            $sheet->setCellValue("L{$row}", $result->recommendation_type === 'purchase' ? 'Buat PO' : 'Produksi');
            ExcelService::applyDataStyle($spreadsheet, "A{$row}:L{$row}", $idx % 2 === 0);
        }

        foreach (['A' => 16, 'B' => 32, 'C' => 8, 'D' => 12, 'E' => 12,
                  'F' => 10, 'G' => 10, 'H' => 10, 'I' => 12, 'J' => 10, 'K' => 14, 'L' => 12] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $filename = 'mrp_run_' . $mrpRun->created_at->format('Ymd_Hi') . '.xlsx';
        return ExcelService::download($spreadsheet, $filename);
    }

    public function exportPdf($id)
    {
        ini_set('memory_limit', '256M');
        $mrpRun = MrpRun::findOrFail($id);
        $mrpRun->load('results.material', 'runBy');
        $results = $mrpRun->results->sortBy(fn($r) => $r->recommendation_type === 'purchase' ? 0 : 1)->values();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('mrp.pdf', compact('mrpRun', 'results'))
            ->setPaper('a3', 'landscape');
        return $pdf->download('mrp_run_' . $mrpRun->created_at->format('Ymd_Hi') . '.pdf');
    }

    public function exportListPdf()
    {
        ini_set('memory_limit', '256M');
        $runs = MrpRun::with('runBy')->withCount('results')->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('mrp.pdf-list', compact('runs'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('mrp_runs_' . date('Ymd') . '.pdf');
    }

    private function explodeBom(int $materialId, float $qty, array &$gross, array $visited = []): void
    {
        if (in_array($materialId, $visited)) {
            return;
        }

        $material = Material::find($materialId);
        if (!$material) return;

        if ($material->tipe === 'RM') {
            $gross[$materialId] = ($gross[$materialId] ?? 0) + $qty;
            return;
        }

        $bom = Bom::with('items')
            ->where('material_id', $materialId)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$bom || $bom->items->isEmpty()) {
            $gross[$materialId] = ($gross[$materialId] ?? 0) + $qty;
            return;
        }

        $multiplier = $qty / max(0.001, (float) $bom->base_quantity);
        $chain      = [...$visited, $materialId];

        foreach ($bom->items as $item) {
            $this->explodeBom($item->material_id, (float) $item->quantity * $multiplier, $gross, $chain);
        }
    }

    private function explodeStock(int $materialId, float $qty, array &$availableStock, array $visited = []): void
    {
        if (in_array($materialId, $visited)) {
            return;
        }

        $material = Material::find($materialId);
        if (!$material) return;

        if ($material->tipe === 'RM') {
            $availableStock[$materialId] = ($availableStock[$materialId] ?? 0) + $qty;
            return;
        }

        $bom = Bom::with('items')
            ->where('material_id', $materialId)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$bom || $bom->items->isEmpty()) {
            $availableStock[$materialId] = ($availableStock[$materialId] ?? 0) + $qty;
            return;
        }

        $multiplier = $qty / max(0.001, (float) $bom->base_quantity);
        $chain      = [...$visited, $materialId];

        foreach ($bom->items as $item) {
            $this->explodeStock($item->material_id, (float) $item->quantity * $multiplier, $availableStock, $chain);
        }
    }
}
