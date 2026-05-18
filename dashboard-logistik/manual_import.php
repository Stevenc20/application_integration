<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SinglePart;
use Illuminate\Support\Facades\DB;

$dataFile = 'd:\\MAMP\\MAMP\\htdocs\\ippi-pplc - Copy\\dashboard-logistik\\storage\\app\\uploads\\NEW Rundown_Incoming_FINISH + SINGLE..xlsx';
$priceFile = 'd:\\MAMP\\MAMP\\htdocs\\ippi-pplc - Copy\\dashboard-logistik\\storage\\app\\uploads\\ADD.xlsx';
$python = 'python';
$scriptPath = __DIR__ . '/read_single_part_monthly.py';

$cmd = escapeshellcmd($python) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($dataFile) . ' ' . escapeshellarg($priceFile);
$output = shell_exec($cmd . ' 2>NUL') ?: shell_exec($cmd . ' 2>&1');
$jsonStart = strpos($output, '{');

if ($jsonStart === false) {
    die("Gagal membaca file Excel.");
}

$result = json_decode(substr($output, $jsonStart), true);
if (!$result || isset($result['error'])) {
    die("Error decoding JSON: " . ($result['error'] ?? 'Unknown'));
}

$now = now();
$imported = 0;

DB::transaction(function () use ($result, $now, &$imported) {
    foreach ($result['sheets'] as $sheetName => $sheetData) {
        // Hapus data lama untuk tanggal ini agar bersih
        SinglePart::where('sheet_date', $sheetName)->delete();
        
        $rows = [];
        foreach ($sheetData as $item) {
            $rows[] = [
                'sheet_date'    => $sheetName,
                'no'            => $item['no'],
                'job_no'        => $item['job_no'],
                'job_no_finish' => $item['job_no_finish'] ?? '',
                'type_pallet'   => $item['type_pallet'] ?? '',
                'vendor'        => $item['vendor'],
                'category'      => $item['category'],
                'customer'      => $item['customer'],
                'price_pc'      => $item['price_pc'],
                'status'        => $item['status'],
                'movement'      => $item['movement'],
                'cycle_issue'   => $item['cycle_issue'],
                'stock_awal'    => $item['stock_awal'],
                'assy'          => $item['assy'],
                'iami'          => $item['iami'] ?? 0,
                'gkd'           => $item['gkd'] ?? 0,
                'sap'           => $item['sap'] ?? 0,
                'kap'           => $item['kap'] ?? 0,
                'gmo'           => $item['gmo'] ?? 0,
                'delivery'      => $item['delivery'] ?? '',
                'incoming'      => $item['incoming'],
                'stok_akhir'    => $item['stok_akhir'],
                'all_price'     => $item['all_price'],
                'pcs_day'       => $item['pcs_day'],
                'strength'      => $item['strength'],
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            SinglePart::insert($chunk);
        }
        $imported += count($rows);
    }
});

echo "Berhasil mengimpor $imported item ke dalam " . count($result['sheets']) . " tanggal.";
