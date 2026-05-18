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
    die("Error decoding JSON.");
}

// Build a mapping from job_no -> [job_no_finish, type_pallet]
$masterMap = [];
foreach ($result['sheets'] as $sheetName => $sheetData) {
    foreach ($sheetData as $item) {
        $jn = trim($item['job_no']);
        if ($jn && !isset($masterMap[$jn])) {
            $masterMap[$jn] = [
                'finish' => $item['job_no_finish'] ?? '',
                'pallet' => $item['type_pallet'] ?? ''
            ];
        }
    }
}

echo "Ditemukan " . count($masterMap) . " master mapping.\n";

$updated = 0;
DB::transaction(function () use ($masterMap, &$updated) {
    foreach ($masterMap as $jn => $data) {
        $count = SinglePart::where('job_no', $jn)
            ->where(function($q) {
                $q->where('job_no_finish', '')
                  ->orWhereNull('job_no_finish');
            })
            ->update([
                'job_no_finish' => $data['finish'],
                'type_pallet'   => $data['pallet']
            ]);
        $updated += $count;
    }
});

echo "Berhasil memperbarui $updated baris data.";
