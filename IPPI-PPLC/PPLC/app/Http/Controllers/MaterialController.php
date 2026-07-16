<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Exports\MaterialExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $tipe = trim($request->get('tipe', ''));
        
        $query = Material::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($tipe !== '') {
            $query->where('tipe', $tipe);
        }

        $materials = $query->orderBy('kode', 'asc')->paginate(15)->appends($request->query());

        // Get unique tipe list for the filter dropdown
        $typesList = Material::select('tipe')->distinct()->orderBy('tipe', 'asc')->pluck('tipe')->toArray();

        return view('materials.index', compact('materials', 'search', 'tipe', 'typesList'));
    }

    public function create()
    {
        $vendors = \App\Models\Vendor::where('status', 'Aktif')->orderBy('nama', 'asc')->get();
        return view('materials.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:materials,kode',
            'nama' => 'required|string',
            'tipe' => 'required|string|in:WIP,FP,RM',
            'uom' => 'required|string',
            'qty_case' => 'required|integer|min:0',
            'stok' => 'required|numeric|min:0',
            'min_stok' => 'required|numeric|min:0',
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        Material::create($validated);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:materials,id',
            'kode' => 'required|string|unique:materials,kode,' . $request->id,
            'nama' => 'required|string',
            'tipe' => 'required|string|in:WIP,FP,RM',
            'uom' => 'required|string',
            'qty_case' => 'required|integer|min:0',
            'stok' => 'required|numeric|min:0',
            'min_stok' => 'required|numeric|min:0',
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        $material = Material::findOrFail($request->id);
        $material->update($validated);

        return redirect()->back()->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();

        return redirect()->back()->with('success', 'Material berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        $tipe = $request->get('tipe');
        return Excel::download(new MaterialExport($search, $tipe), 'materials_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        // Style variables
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'name' => 'Calibri'
            ]
        ];

        $instructionStyle = [
            'font' => [
                'italic' => true,
                'size' => 10,
                'name' => 'Calibri'
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC']
            ]
        ];

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
                'name' => 'Calibri'
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'] // Dark Blue
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];

        // Row 1: Title
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT MATERIAL');
        $sheet->getStyle('A1')->applyFromArray($titleStyle);

        // Row 2: Instructions
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 5. Jangan ubah header. Kolom bertanda * wajib diisi.');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2:H2')->applyFromArray($instructionStyle);

        // Row 3: Detail Instructions
        $sheet->setCellValue('A3', 'Tipe Material: WIP | FP | RM   |  Aktif: Ya atau Tidak');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3:H3')->applyFromArray($instructionStyle);

        // Row 4: Header
        $sheet->setCellValue('A4', 'Kode *');
        $sheet->setCellValue('B4', 'Nama *');
        $sheet->setCellValue('C4', 'Tipe *');
        $sheet->setCellValue('D4', 'UoM *');
        $sheet->setCellValue('E4', 'Qty/Case *');
        $sheet->setCellValue('F4', 'Min Stok *');
        $sheet->setCellValue('G4', 'Stok *');
        $sheet->setCellValue('H4', 'Aktif *');
        $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);

        // Row 5: Sample Row 1
        $sheet->setCellValue('A5', 'ISF PH068');
        $sheet->setCellValue('B5', 'PH-068');
        $sheet->setCellValue('C5', 'WIP');
        $sheet->setCellValue('D5', 'PCS');
        $sheet->setCellValue('E5', 91);
        $sheet->setCellValue('F5', 0);
        $sheet->setCellValue('G5', 0);
        $sheet->setCellValue('H5', 'Ya');

        // Row 6: Sample Row 2
        $sheet->setCellValue('A6', 'IFG-P20410215A');
        $sheet->setCellValue('B6', 'PH-144 AC GU');
        $sheet->setCellValue('C6', 'FP');
        $sheet->setCellValue('D6', 'PCS');
        $sheet->setCellValue('E6', 24);
        $sheet->setCellValue('F6', 195);
        $sheet->setCellValue('G6', 0);
        $sheet->setCellValue('H6', 'Ya');

        // Row 7: Sample Row 3
        $sheet->setCellValue('A7', 'DC590.90X0605X0305');
        $sheet->setCellValue('B7', 'GBA-0105');
        $sheet->setCellValue('C7', 'RM');
        $sheet->setCellValue('D7', 'SHT');
        $sheet->setCellValue('E7', 0);
        $sheet->setCellValue('F7', 2500);
        $sheet->setCellValue('G7', 7000);
        $sheet->setCellValue('H7', 'Ya');

        // Autowidth columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="template_import_material.xlsx"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $importedCount = 0;
            foreach ($rows as $index => $row) {
                if ($index < 4) continue; // Skip instructions and header
                if (empty($row[0]) || trim($row[0]) === 'Kode *') continue; 

                // Map tipe
                $rawTipe = strtoupper(trim($row[2] ?? ''));
                $tipe = 'WIP';
                if (in_array($rawTipe, ['WIP', 'FP', 'RM'])) {
                    $tipe = $rawTipe;
                }

                // Map status (from column H / index 7)
                $rawStatus = trim($row[7] ?? '');
                $status = 'Aktif';
                if (strtolower($rawStatus) === 'tidak' || strtolower($rawStatus) === 'tidak aktif' || strtolower($rawStatus) === 'no') {
                    $status = 'Tidak Aktif';
                } elseif (strtolower($rawStatus) === 'ya' || strtolower($rawStatus) === 'aktif' || strtolower($rawStatus) === 'yes') {
                    $status = 'Aktif';
                }

                Material::updateOrCreate(
                    ['kode' => trim($row[0])],
                    [
                        'nama' => trim($row[1] ?? ''),
                        'tipe' => $tipe,
                        'uom' => trim($row[3] ?? 'PCS'),
                        'qty_case' => intval($row[4] ?? 0),
                        'min_stok' => floatval($row[5] ?? 0.0),
                        'stok' => floatval($row[6] ?? 0.0),
                        'status' => $status,
                    ]
                );
                $importedCount++;
            }

            return redirect()->back()->with('success', "Berhasil mengimpor $importedCount material.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function printPdf(Request $request)
    {
        $search = trim($request->get('search', ''));
        $tipe = trim($request->get('tipe', ''));
        
        $query = Material::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($tipe !== '') {
            $query->where('tipe', $tipe);
        }

        $materials = $query->orderBy('kode', 'asc')->get();

        $dateStr = now()->format('d M Y, H:i') . ' WIB';
        $filterStr = "Semua data";
        if ($search !== '' && $tipe !== '') {
            $filterStr = "Cari: '$search', Tipe: '$tipe'";
        } elseif ($search !== '') {
            $filterStr = "Cari: '$search'";
        } elseif ($tipe !== '') {
            $filterStr = "Tipe: '$tipe'";
        }

        $pdf = Pdf::loadView('materials.pdf', [
            'materials' => $materials,
            'dateStr' => $dateStr,
            'filterStr' => $filterStr,
        ]);

        // Save copy to storage/app/uploads/materials_YYYYMMDD.pdf
        $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'materials_' . now()->format('Ymd') . '.pdf';
        $savePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($savePath);

        return $pdf->download($filename);
    }

    public function show($id)
    {
        $material = Material::findOrFail($id);
        
        // Load stock per storage location
        $locations = \App\Models\StorageLocation::orderBy('nama', 'asc')->get();
        
        // Stock history log from Goods Receipts and Goods Issues
        $grItems = \App\Models\GoodsReceiptItem::with('goodsReceipt')
            ->where('material_id', $material->id)
            ->get()
            ->map(function($item) {
                return [
                    'tanggal' => $item->goodsReceipt?->tanggal_receipt
                        ? \Carbon\Carbon::parse($item->goodsReceipt->tanggal_receipt)->format('d/m/Y')
                        : ($item->created_at ? $item->created_at->format('d/m/Y') : '-'),
                    'tipe' => 'GR',
                    'referensi' => $item->goodsReceipt?->no_gr ?? '-',
                    'qty' => (float) $item->qty,
                    'timestamp' => $item->created_at ? $item->created_at->timestamp : 0,
                ];
            });

        $giItems = \App\Models\GoodsIssueItem::with('goodsIssue')
            ->where('material_id', $material->id)
            ->get()
            ->map(function($item) {
                return [
                    'tanggal' => $item->goodsIssue?->tanggal_issue
                        ? \Carbon\Carbon::parse($item->goodsIssue->tanggal_issue)->format('d/m/Y')
                        : ($item->created_at ? $item->created_at->format('d/m/Y') : '-'),
                    'tipe' => 'GI',
                    'referensi' => $item->goodsIssue?->no_gi ?? '-',
                    'qty' => (float) $item->qty,
                    'timestamp' => $item->created_at ? $item->created_at->timestamp : 0,
                ];
            });

        // Combine and calculate running balance
        $movements = $grItems->concat($giItems)->sortBy('timestamp');
        
        $runningStock = 0;
        $movements = $movements->map(function($m) use (&$runningStock) {
            if ($m['tipe'] === 'GR') {
                $runningStock += $m['qty'];
            } else {
                $runningStock -= $m['qty'];
            }
            $m['stok_akhir'] = $runningStock;
            return $m;
        });

        $latestMovements = $movements->reverse()->take(10);

        return view('materials.show', compact('material', 'locations', 'latestMovements'));
    }
}
