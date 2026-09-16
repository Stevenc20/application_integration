<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use App\Exports\StorageLocationExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StorageLocationController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        
        $query = StorageLocation::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $storageLocations = $query->orderBy('kode', 'asc')->paginate(15)->appends($request->query());

        return view('storage_locations.index', compact('storageLocations', 'search'));
    }

    public function create()
    {
        return view('storage_locations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:storage_locations,kode',
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
            'tipe_material' => 'required|string|in:RM,WIP,FP',
            'is_scrap' => 'required|boolean',
        ]);

        StorageLocation::create($validated);

        return redirect()->back()->with('success', 'Storage Location berhasil ditambahkan.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:storage_locations,id',
            'kode' => 'required|string|unique:storage_locations,kode,' . $request->id,
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
            'tipe_material' => 'required|string|in:RM,WIP,FP',
            'is_scrap' => 'required|boolean',
        ]);

        $location = StorageLocation::findOrFail($request->id);
        $location->update($validated);

        return redirect()->back()->with('success', 'Storage Location berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $location = StorageLocation::findOrFail($id);
        $location->delete();

        return redirect()->back()->with('success', 'Storage Location berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        return Excel::download(new StorageLocationExport($search), 'storage_locations_' . now()->format('Ymd_His') . '.xlsx');
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
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT STORAGE LOCATION');
        $sheet->getStyle('A1')->applyFromArray($titleStyle);

        // Row 2: Instructions
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 5. Jangan ubah header. Kolom bertanda * wajib diisi.');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2:E2')->applyFromArray($instructionStyle);

        // Row 3: Detail Instructions
        $sheet->setCellValue('A3', 'Tipe Material: RM | WIP | FP   |  Scrap: Ya atau Tidak');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3:E3')->applyFromArray($instructionStyle);

        // Row 4: Header
        $sheet->setCellValue('A4', 'Kode *');
        $sheet->setCellValue('B4', 'Nama *');
        $sheet->setCellValue('C4', 'Deskripsi');
        $sheet->setCellValue('D4', 'Tipe Material *');
        $sheet->setCellValue('E4', 'Scrap *');
        $sheet->getStyle('A4:E4')->applyFromArray($headerStyle);

        // Row 5: Sample Row 1
        $sheet->setCellValue('A5', '1101-S-ADM');
        $sheet->setCellValue('B5', 'SCRAP RM ADM');
        $sheet->setCellValue('C5', 'GUDANG SCRAP RM ADM');
        $sheet->setCellValue('D5', 'RM');
        $sheet->setCellValue('E5', 'Ya');

        // Row 6: Sample Row 2
        $sheet->setCellValue('A6', '1101');
        $sheet->setCellValue('B6', 'Gudang IRM');
        $sheet->setCellValue('C6', 'Penyimpanan material RM');
        $sheet->setCellValue('D6', 'RM');
        $sheet->setCellValue('E6', 'Tidak');

        // Row 7: Sample Row 3
        $sheet->setCellValue('A7', '1100');
        $sheet->setCellValue('B7', 'Gudang WIP');
        $sheet->setCellValue('C7', 'Work-in-Process');
        $sheet->setCellValue('D7', 'WIP');
        $sheet->setCellValue('E7', 'Tidak');

        // Autowidth columns
        foreach (range('A', 'E') as $col) {
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
                'Content-Disposition' => 'attachment; filename="template_import_storage_location.xlsx"',
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

                // Map tipe material
                $rawTipe = strtoupper(trim($row[3] ?? ''));
                $tipeMaterial = 'RM';
                if (in_array($rawTipe, ['RM', 'WIP', 'FP'])) {
                    $tipeMaterial = $rawTipe;
                }

                // Map scrap (column E / index 4)
                $rawScrap = trim($row[4] ?? '');
                $isScrap = false;
                if (strtolower($rawScrap) === 'ya' || strtolower($rawScrap) === 'aktif' || strtolower($rawScrap) === 'yes' || strtolower($rawScrap) === 'scrap' || $rawScrap === '1' || strtolower($rawScrap) === 'true') {
                    $isScrap = true;
                }

                StorageLocation::updateOrCreate(
                    ['kode' => trim($row[0])],
                    [
                        'nama' => trim($row[1] ?? ''),
                        'deskripsi' => trim($row[2] ?? ''),
                        'tipe_material' => $tipeMaterial,
                        'is_scrap' => $isScrap,
                    ]
                );
                $importedCount++;
            }

            return redirect()->back()->with('success', "Berhasil mengimpor $importedCount storage location.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function printPdf(Request $request)
    {
        $search = trim($request->get('search', ''));
        
        $query = StorageLocation::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $locations = $query->orderBy('kode', 'asc')->get();

        $dateStr = now()->format('d M Y, H:i') . ' WIB';
        $filterStr = "Semua data";
        if ($search !== '') {
            $filterStr = "Cari: '$search'";
        }

        $pdf = Pdf::loadView('storage_locations.pdf', [
            'locations' => $locations,
            'dateStr' => $dateStr,
            'filterStr' => $filterStr,
        ]);

        $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'storage_locations_' . now()->format('Ymd') . '.pdf';
        $savePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($savePath);

        return $pdf->download($filename);
    }
}
