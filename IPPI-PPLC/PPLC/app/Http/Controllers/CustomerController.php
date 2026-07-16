<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Exports\CustomerExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        
        $query = Customer::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('kode', 'asc')->paginate(15)->appends($request->query());

        return view('customers.index', compact('customers', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:customers,kode',
            'nama' => 'required|string',
            'kontak' => 'nullable|string',
            'email' => 'nullable|email',
            'telepon' => 'nullable|string',
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        Customer::create($validated);

        return redirect()->back()->with('success', 'Customer berhasil ditambahkan.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:customers,id',
            'kode' => 'required|string|unique:customers,kode,' . $request->id,
            'nama' => 'required|string',
            'kontak' => 'nullable|string',
            'email' => 'nullable|email',
            'telepon' => 'nullable|string',
            'status' => 'required|in:Aktif,Tidak Aktif',
        ]);

        $customer = Customer::findOrFail($request->id);
        $customer->update($validated);

        return redirect()->back()->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->back()->with('success', 'Customer berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        return Excel::download(new CustomerExport($search), 'customers_' . now()->format('Ymd_His') . '.xlsx');
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
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT CUSTOMER');
        $sheet->getStyle('A1')->applyFromArray($titleStyle);

        // Row 2: Instructions
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 5. Jangan ubah header. Kolom bertanda * wajib diisi.');
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2:F2')->applyFromArray($instructionStyle);

        // Row 3: Detail Instructions
        $sheet->setCellValue('A3', 'Aktif: Ya atau Tidak');
        $sheet->mergeCells('A3:F3');
        $sheet->getStyle('A3:F3')->applyFromArray($instructionStyle);

        // Row 4: Header
        $sheet->setCellValue('A4', 'Kode *');
        $sheet->setCellValue('B4', 'Nama *');
        $sheet->setCellValue('C4', 'Contact Person');
        $sheet->setCellValue('D4', 'Email');
        $sheet->setCellValue('E4', 'Telepon');
        $sheet->setCellValue('F4', 'Aktif *');
        $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);

        // Row 5: Sample Row 1
        $sheet->setCellValue('A5', 'C-ADM-KAP');
        $sheet->setCellValue('B5', 'Astra Daihatsu Motor (KAP)');
        $sheet->setCellValue('C5', 'Budi Santoso');
        $sheet->setCellValue('D5', 'budi@daihatsu.co.id');
        $sheet->setCellValue('E5', '021-6510300');
        $sheet->setCellValue('F5', 'Ya');

        // Row 6: Sample Row 2
        $sheet->setCellValue('A6', 'C-TMMIN');
        $sheet->setCellValue('B6', 'Toyota Motor Manufacturing Indonesia');
        $sheet->setCellValue('C6', 'Dedi Kurniawan');
        $sheet->setCellValue('D6', 'dedi@toyota.co.id');
        $sheet->setCellValue('E6', '021-6515555');
        $sheet->setCellValue('F6', 'Ya');

        // Row 7: Sample Row 3
        $sheet->setCellValue('A7', 'C-FTI');
        $sheet->setCellValue('B7', 'Fuji Technica Indonesia');
        $sheet->setCellValue('C7', 'Heri Cahyono');
        $sheet->setCellValue('D7', 'heri@fuji.co.id');
        $sheet->setCellValue('E7', '021-8980123');
        $sheet->setCellValue('F7', 'Ya');

        // Autowidth columns
        foreach (range('A', 'F') as $col) {
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
                'Content-Disposition' => 'attachment; filename="template_import_customer.xlsx"',
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

                // Map status (from column F / index 5)
                $rawStatus = trim($row[5] ?? '');
                $status = 'Aktif';
                if (strtolower($rawStatus) === 'tidak' || strtolower($rawStatus) === 'tidak aktif' || strtolower($rawStatus) === 'no') {
                    $status = 'Tidak Aktif';
                } elseif (strtolower($rawStatus) === 'ya' || strtolower($rawStatus) === 'aktif' || strtolower($rawStatus) === 'yes') {
                    $status = 'Aktif';
                }

                Customer::updateOrCreate(
                    ['kode' => trim($row[0])],
                    [
                        'nama' => trim($row[1] ?? ''),
                        'kontak' => trim($row[2] ?? ''),
                        'email' => trim($row[3] ?? ''),
                        'telepon' => trim($row[4] ?? ''),
                        'status' => $status,
                    ]
                );
                $importedCount++;
            }

            return redirect()->back()->with('success', "Berhasil mengimpor $importedCount customer.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function printPdf(Request $request)
    {
        $search = trim($request->get('search', ''));
        
        $query = Customer::query();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('kode', 'asc')->get();

        $dateStr = now()->format('d M Y, H:i') . ' WIB';
        $filterStr = "Semua data";
        if ($search !== '') {
            $filterStr = "Cari: '$search'";
        }

        $pdf = Pdf::loadView('customers.pdf', [
            'customers' => $customers,
            'dateStr' => $dateStr,
            'filterStr' => $filterStr,
        ]);

        $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'customers_' . now()->format('Ymd') . '.pdf';
        $savePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($savePath);

        return $pdf->download($filename);
    }
}
