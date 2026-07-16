<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Exports\VendorExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $query = Vendor::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $vendors = $query->orderBy('kode', 'asc')->paginate(15)->appends($request->query());

        return view('vendors.index', compact('vendors', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:vendors,kode',
            'nama' => 'required|string',
            'tipe' => 'required|string',
            'alamat' => 'nullable|string',
            'kontak' => 'nullable|string',
            'email' => 'nullable|email',
            'telepon' => 'nullable|string',
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        Vendor::create($validated);

        return redirect()->back()->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:vendors,id',
            'kode' => 'required|string|unique:vendors,kode,' . $request->id,
            'nama' => 'required|string',
            'tipe' => 'required|string',
            'alamat' => 'nullable|string',
            'kontak' => 'nullable|string',
            'email' => 'nullable|email',
            'telepon' => 'nullable|string',
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        $vendor = Vendor::findOrFail($request->id);
        $vendor->update($validated);

        return redirect()->back()->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return redirect()->back()->with('success', 'Vendor berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        return Excel::download(new VendorExport($search), 'vendors_' . now()->format('Ymd_His') . '.xlsx');
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
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT VENDOR');
        $sheet->getStyle('A1')->applyFromArray($titleStyle);

        // Row 2: Instructions
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 5. Jangan ubah header. Kolom bertanda * wajib diisi.');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2:H2')->applyFromArray($instructionStyle);

        // Row 3: Detail Instructions
        $sheet->setCellValue('A3', 'Tipe Vendor: coil_center | process | general   |  Aktif: Ya atau Tidak');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3:H3')->applyFromArray($instructionStyle);

        // Row 4: Header
        $sheet->setCellValue('A4', 'Kode *');
        $sheet->setCellValue('B4', 'Nama *');
        $sheet->setCellValue('C4', 'Tipe Vendor *');
        $sheet->setCellValue('D4', 'Contact Person');
        $sheet->setCellValue('E4', 'Email');
        $sheet->setCellValue('F4', 'Telepon');
        $sheet->setCellValue('G4', 'Alamat');
        $sheet->setCellValue('H4', 'Aktif *');
        $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);

        // Row 5: Sample Row 1
        $sheet->setCellValue('A5', 'VND 001');
        $sheet->setCellValue('B5', 'PT Sumber Makmur');
        $sheet->setCellValue('C5', 'coil_center');
        $sheet->setCellValue('D5', 'Budi Santoso');
        $sheet->setCellValue('E5', 'budi@sumber.com');
        $sheet->setCellValue('F5', '021 5551234');
        $sheet->setCellValue('G5', 'Jl. Industri No.1, Jakarta');
        $sheet->setCellValue('H5', 'Ya');

        // Row 6: Sample Row 2
        $sheet->setCellValue('A6', 'VND-002');
        $sheet->setCellValue('B6', 'CV Proses Jaya');
        $sheet->setCellValue('C6', 'process');
        $sheet->setCellValue('D6', '-');
        $sheet->setCellValue('E6', '-');
        $sheet->setCellValue('F6', '-');
        $sheet->setCellValue('G6', '-');
        $sheet->setCellValue('H6', 'Ya');

        // Row 7: Sample Row 3
        $sheet->setCellValue('A7', 'VND-003');
        $sheet->setCellValue('B7', 'UD Umum Sejahtera');
        $sheet->setCellValue('C7', 'general');
        $sheet->setCellValue('D7', '-');
        $sheet->setCellValue('E7', '-');
        $sheet->setCellValue('F7', '-');
        $sheet->setCellValue('G7', '-');
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
                'Content-Disposition' => 'attachment; filename="template_import_vendor.xlsx"',
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
                // If it's a new template, instructions are rows 1-3 and header is row 4. Skip first 4 rows.
                // However, let's make it robust: if the header has "Kode *" or "TEMPLATE IMPORT VENDOR" or similar, skip.
                if ($index < 4) continue;
                if (empty($row[0]) || trim($row[0]) === 'Kode *') continue; 

                // Map tipe
                $rawTipe = trim($row[2] ?? '');
                $tipe = 'Process / Makloon';
                if ($rawTipe === 'coil_center') {
                    $tipe = 'Coil Center (Supplier Bahan Baku)';
                } elseif ($rawTipe === 'process') {
                    $tipe = 'Process / Makloon';
                } elseif ($rawTipe === 'general') {
                    $tipe = 'General';
                } elseif (!empty($rawTipe)) {
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

                Vendor::updateOrCreate(
                    ['kode' => trim($row[0])],
                    [
                        'nama' => trim($row[1] ?? ''),
                        'tipe' => $tipe,
                        'kontak' => !empty($row[3]) && $row[3] !== '-' ? trim($row[3]) : null,
                        'email' => !empty($row[4]) && $row[4] !== '-' ? trim($row[4]) : null,
                        'telepon' => !empty($row[5]) && $row[5] !== '-' ? trim($row[5]) : null,
                        'status' => $status,
                    ]
                );
                $importedCount++;
            }

            return redirect()->back()->with('success', "Berhasil mengimpor $importedCount vendor.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function printPdf(Request $request)
    {
        $search = trim($request->get('search', ''));
        $query = Vendor::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $vendors = $query->orderBy('kode', 'asc')->get();

        $dateStr = now()->format('d M Y, H:i') . ' WIB';
        $filterStr = $search !== '' ? "Cari: '$search'" : "Semua data";

        $pdf = Pdf::loadView('vendors.pdf', [
            'vendors' => $vendors,
            'dateStr' => $dateStr,
            'filterStr' => $filterStr,
        ]);

        // Save a copy to storage/app/uploads/vendors_YYYYMMDD.pdf
        $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'vendors_' . now()->format('Ymd') . '.pdf';
        $savePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($savePath);

        return $pdf->download($filename);
    }
}
