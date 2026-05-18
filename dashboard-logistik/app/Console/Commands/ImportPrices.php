<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SinglePart;
use Illuminate\Support\Facades\DB;

class ImportPrices extends Command
{
    protected $signature   = 'import:prices {file? : Path ke ADD.xlsx (default: storage/app/uploads/ADD.xlsx)}';
    protected $description = 'Import price_pc dari ADD.xlsx ke tabel single_parts dan recalculate all_price';

    public function handle()
    {
        $filePath = $this->argument('file')
            ?? storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ADD.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            $this->line("Pastikan ADD.xlsx ada di: storage/app/uploads/ADD.xlsx");
            return 1;
        }

        $this->info("Membaca file: {$filePath}");

        // Baca ADD.xlsx dengan openpyxl via Python
        $python = $this->findPython();
        if (!$python) {
            $this->error('Python 3 tidak ditemukan.');
            return 1;
        }

        // Inline Python script untuk baca harga, customer, dan cycle issue
        $script = <<<'PYEOF'
import sys, json, openpyxl, warnings
warnings.filterwarnings('ignore')
wb = openpyxl.load_workbook(sys.argv[1], data_only=True)
ws = wb.active
data = {}
for r in range(6, ws.max_row + 1):
    job_no = ws.cell(r, 2).value
    cycle  = ws.cell(r, 3).value
    customer = ws.cell(r, 4).value
    price  = ws.cell(r, 5).value
    if job_no:
        data[str(job_no).strip().upper()] = {
            'price': float(price) if price and str(price).replace('.','').replace(',','').isdigit() else 0,
            'cycle': int(cycle) if cycle and str(cycle).isdigit() else 1,
            'customer': str(customer).strip() if customer else ''
        }
print(json.dumps(data))
PYEOF;

        $tmpScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'read_add_prices.py';
        file_put_contents($tmpScript, $script);

        $cmd    = escapeshellcmd($python) . ' ' . escapeshellarg($tmpScript) . ' ' . escapeshellarg($filePath);
        $output = shell_exec($cmd . ' 2>&1');

        @unlink($tmpScript);

        $jsonStart = strpos($output, '{');
        if ($jsonStart === false) {
            $this->error('Gagal membaca Excel. Output: ' . substr($output, 0, 300));
            return 1;
        }

        $prices = json_decode(substr($output, $jsonStart), true);
        if (!$prices) {
            $this->error('Gagal parse JSON dari Python.');
            return 1;
        }

        $this->info("Ditemukan " . count($prices) . " data di ADD.xlsx");
        // Update semua rows di single_parts
        $updated = 0;
        $notFound = [];

        $allRows = SinglePart::select('id', 'job_no', 'stok_akhir', 'price_pc', 'customer', 'cycle_issue', 'movement')->get();

        DB::transaction(function () use ($allRows, $prices, &$updated, &$notFound) {
            foreach ($allRows as $row) {
                $key = strtoupper(trim($row->job_no));
                if (isset($prices[$key])) {
                    $price    = $prices[$key]['price'];
                    $customer = $prices[$key]['customer'];
                    $cycle    = $prices[$key]['cycle'];
                    
                    $allPrice = ($row->stok_akhir ?? 0) * $price;
                    $movement = 'SLOW MOVING';
                    if ($cycle >= 3) {
                        $movement = 'FAST MOVING';
                    }

                    DB::table('single_parts')
                        ->where('id', $row->id)
                        ->update([
                            'price_pc' => $price, 
                            'all_price' => $allPrice,
                            'customer' => $customer,
                            'cycle_issue' => $cycle,
                            'movement' => $movement
                        ]);
                    $updated++;
                } else {
                    if (!in_array($row->job_no, $notFound)) {
                        $notFound[] = $row->job_no;
                    }
                }
            }
        });

        $this->info("✅ Berhasil update {$updated} baris.");

        if (!empty($notFound)) {
            $this->warn("Job No berikut tidak ditemukan di ADD.xlsx:");
            foreach ($notFound as $j) {
                $this->line("  - {$j}");
            }
        }

        return 0;
    }

    private function findPython(): ?string
    {
        foreach (['python', 'python3', 'py'] as $cmd) {
            $test = shell_exec("{$cmd} --version 2>&1");
            if ($test && str_contains($test, 'Python 3')) return $cmd;
        }
        return null;
    }
}
