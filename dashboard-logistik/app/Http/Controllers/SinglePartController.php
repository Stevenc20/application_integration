<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SinglePart;
use App\Exports\SinglePartExport;
use Maatwebsite\Excel\Facades\Excel;

class SinglePartController extends Controller
{
    public function index(Request $request)
    {
        // Sort sheets chronologically using Carbon parsing
        $monthMap = [
            'JANUARI'=>1,'FEBRUARI'=>2,'MARET'=>3,'APRIL'=>4,'MEI'=>5,'JUNI'=>6,
            'JULI'=>7,'AGUSTUS'=>8,'SEPTEMBER'=>9,'OKTOBER'=>10,'NOVEMBER'=>11,'DESEMBER'=>12
        ];

        $rawSheets = SinglePart::distinct()->pluck('sheet_date');
        $availableSheets = $rawSheets->sortBy(function($s) use ($monthMap) {
            $parts = explode(' ', trim($s));
            if (count($parts) < 2) return 0;
            $day = (int)$parts[0];
            $mon = $monthMap[strtoupper($parts[1])] ?? 0;
            return $mon * 100 + $day;
        })->values();

        // Default to today's date (e.g. "29 APRIL" or "01 MEI")
        $monthsId = [
            1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL', 
            5=>'MEI', 6=>'JUNI', 7=>'JULI', 8=>'AGUSTUS', 
            9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER'
        ];
        $todaySheet = now()->format('d') . ' ' . $monthsId[(int)now()->format('m')];
        
        $selectedSheet   = trim($request->get('sheet', $todaySheet));

        // Auto-generate template for missing dates
        if ($selectedSheet !== '') {
            $exists = SinglePart::where('sheet_date', $selectedSheet)->exists();
            if (!$exists) {
                $this->generateTemplateForDate($selectedSheet);
                
                // Refresh available sheets after insertion
                $rawSheets = SinglePart::distinct()->pluck('sheet_date');
                $availableSheets = $rawSheets->sortBy(function($s) use ($monthMap) {
                    $parts = explode(' ', trim($s));
                    if (count($parts) < 2) return 0;
                    $day = (int)$parts[0];
                    $mon = $monthMap[strtoupper($parts[1])] ?? 0;
                    return $mon * 100 + $day;
                })->values();
            }
        }
        $search          = trim($request->get('search', '') ?? '');
        $filterVendor    = $request->get('vendor', '');
        $filterCustomer  = $request->get('customer', '');
        $sortBy          = $request->get('sort', 'no');
        $sortDir         = $request->get('dir', 'asc');

        $allowed = ['no','job_no','vendor','category','customer','price_pc','stock_awal','assy','iami','gkd','sap','kap','gmo','incoming','stok_akhir','pcs_day','strength','status','movement','cycle_issue','all_price'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'no';
        if (!in_array($sortDir, ['asc','desc'])) $sortDir = 'asc';

        $filterCategory = $request->get('category', 'SINGLE PART');

        $query = SinglePart::where('sheet_date', $selectedSheet);

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('job_no',     'like', "%{$search}%")
                  ->orWhere('vendor',    'like', "%{$search}%")
                  ->orWhere('category',  'like', "%{$search}%")
                  ->orWhere('customer',  'like', "%{$search}%");
            });
        }
        if (!empty($filterVendor)) {
            $query->where('vendor', $filterVendor);
        }
        if (!empty($filterCategory) && $filterCategory !== 'ALL') {
            $query->where('category', $filterCategory);
        }
        if (!empty($filterCustomer)) {
            $query->where('customer', $filterCustomer);
        }
        $query->orderBy($sortBy, $sortDir);

        $allVendors = SinglePart::where('sheet_date', $selectedSheet)
            ->distinct()->orderBy('vendor')->pluck('vendor')->filter();

        // Customer list filtered by current category (so options stay relevant)
        $customerQuery = SinglePart::where('sheet_date', $selectedSheet);
        if (!empty($filterCategory) && $filterCategory !== 'ALL') {
            $customerQuery->where('category', $filterCategory);
        }
        $allCustomers = $customerQuery->distinct()->orderBy('customer')->pluck('customer')->filter();

        $total   = (clone $query)->count();
        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = $availableSheets->isNotEmpty();

        // Calculate Status Summaries
        $countStandar = (clone $query)->where('status', 'STANDAR')->count();
        $countOver    = (clone $query)->where('status', 'OVER')->count();
        $countMinim   = (clone $query)->whereIn('status', ['MINIM', 'CRITICAL'])->count();
        
        return view('single_part', compact(
            'items', 'total', 'perPage', 'hasData',
            'availableSheets', 'selectedSheet',
            'search', 'filterVendor', 'filterCustomer', 'filterCategory', 'sortBy', 'sortDir', 'allVendors', 'allCustomers',
            'countStandar', 'countOver', 'countMinim'
        ));
    }

    /**
     * Upload Excel monthly - One upload for entire month
     * Excel contains multiple sheets (one per day)
     */
    public function upload(Request $request)
    {
        $request->validate(['excel_file' => 'required|mimes:xlsx,xls,xlsm|max:51200']);
        
        try {
            $file      = $request->file('excel_file');
            $extension = strtolower($file->getClientOriginalExtension());
            $uploadDir = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads');
            $dataPath  = $uploadDir . DIRECTORY_SEPARATOR . 'single_part_data.' . $extension;
            $pricePath = $uploadDir . DIRECTORY_SEPARATOR . 'ADD.xlsx';
            
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            // Save uploaded file
            $file->move($uploadDir, 'single_part_data.' . $extension);
            
            // Check if price file exists
            if (!file_exists($pricePath)) {
                @unlink($dataPath);
                return back()->with('sp_error', 'File harga (ADD.xlsx) tidak ditemukan di storage/app/uploads/');
            }

            $python = $this->findPython();
            if (!$python) {
                @unlink($dataPath);
                return back()->with('sp_error', 'Python tidak ditemukan.');
            }

            $scriptPath = base_path('read_single_part_monthly.py');
            if (!file_exists($scriptPath)) {
                @unlink($dataPath);
                return back()->with('sp_error', 'Script read_single_part_monthly.py tidak ditemukan.');
            }

            // Run Python script to read all sheets + merge price data
            $cmd    = escapeshellcmd($python) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($dataPath) . ' ' . escapeshellarg($pricePath);
            $output = shell_exec($cmd . ' 2>NUL') ?: shell_exec($cmd . ' 2>&1');
            $jsonStart = strpos($output, '{');
            
            if ($jsonStart === false) {
                @unlink($dataPath);
                return back()->with('sp_error', 'Gagal membaca file Excel. Output: ' . substr($output, 0, 200));
            }

            $result = json_decode(substr($output, $jsonStart), true);
            
            if (!$result || isset($result['error'])) {
                @unlink($dataPath);
                return back()->with('sp_error', 'Error: ' . ($result['error'] ?? 'Unknown error'));
            }

            if (empty($result['sheets'])) {
                @unlink($dataPath);
                return back()->with('sp_error', 'Tidak ada data yang ditemukan di file Excel.');
            }

            $imported = 0;
            $now = now();

            DB::transaction(function () use ($result, $now, &$imported) {
                foreach ($result['sheets'] as $sheetName => $sheetData) {
                    // Determine category of this sheet to avoid deleting other categories
                    $sheetCat = count($sheetData) > 0 ? ($sheetData[0]['category'] ?? 'SINGLE PART') : 'SINGLE PART';
                    
                    // Delete existing data for this sheet ONLY for the same category
                    SinglePart::where('sheet_date', $sheetName)
                              ->where('category', $sheetCat)
                              ->delete();
                    
                    // Insert new data
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
                            'stock_awal'  => $item['stock_awal'],
                            'assy'        => $item['assy'],
                            'iami'        => $item['iami'] ?? 0,
                            'gkd'         => $item['gkd'] ?? 0,
                            'sap'         => $item['sap'] ?? 0,
                            'kap'         => $item['kap'] ?? 0,
                            'gmo'         => $item['gmo'] ?? 0,
                            'delivery'    => $item['delivery'],
                            'incoming'    => $item['incoming'],
                            'stok_akhir'  => $item['stok_akhir'],
                            'all_price'   => $item['all_price'],
                            'pcs_day'     => $item['pcs_day'],
                            'strength'    => $item['strength'],
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                    
                    // Insert in chunks
                    foreach (array_chunk($rows, 100) as $chunk) {
                        SinglePart::insert($chunk);
                    }
                    
                    $imported += count($rows);
                }
            });

            @unlink($dataPath);
            
            return redirect()->route('single_part.index')
                ->with('sp_success', 'Upload berhasil! ' . $imported . ' item dari ' . $result['total_sheets'] . ' hari (sheet) telah diimport.');

        } catch (\Exception $e) {
            return back()->with('sp_error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Export to Excel with Month/Year filter
     */
    public function export(Request $request)
    {
        $monthId = $request->get('month', '');
        $year    = $request->get('year', now()->format('Y'));

        if (empty($monthId)) {
            return back()->with('sp_error', 'Pilih bulan untuk export.');
        }

        return Excel::download(new SinglePartExport($monthId, $year), "Rundown_Incoming_{$monthId}_{$year}.xlsx");
    }

    /**
     * Delete Job No from specific sheet date
     */
    public function deleteJob(Request $request)
    {
        $request->validate([
            'sheet_date' => 'required|string',
            'job_no'     => 'required|string',
        ]);

        $jobNo     = strtoupper(trim($request->job_no));
        $sheetDate = trim($request->sheet_date);

        $deleted = SinglePart::where('sheet_date', $sheetDate)
            ->where('job_no', $jobNo)
            ->delete();

        if ($deleted) {
            return redirect()->route('single_part.index', ['sheet' => $sheetDate])
                ->with('sp_success', "Job No {$jobNo} berhasil dihapus dari tanggal {$sheetDate}.");
        }

        return back()->with('sp_error', "Job No {$jobNo} tidak ditemukan pada tanggal {$sheetDate}.");
    }

    /**
     * Add Job No manually
     */
    public function addJob(Request $request)
    {
        $request->validate([
            'sheet_date' => 'required|string',
            'job_no'     => 'required|string',
            'vendor'     => 'required|string',
            'category'   => 'nullable|string',
            'customer'   => 'nullable|string',
            'price_pc'   => 'nullable|numeric',
            'movement'   => 'nullable|string',
            'stock_awal' => 'nullable|numeric',
            'assy'       => 'nullable|numeric',
            'iami'       => 'nullable|numeric',
            'gkd'        => 'nullable|numeric',
            'sap'        => 'nullable|numeric',
            'kap'        => 'nullable|numeric',
            'gmo'        => 'nullable|numeric',
            'delivery'   => 'nullable|string',
            'pcs_day'    => 'required|numeric|min:0.01',
        ]);

        $stockAwal = (float)($request->stock_awal ?? 0);
        $assy      = (float)($request->assy ?? 0);
        $incoming  = 0;
        $stokAkhir = $stockAwal - $assy + $incoming;
        $pcsDay    = (float)$request->pcs_day;
        $strength  = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;
        $pricePc   = (float)($request->price_pc ?? 0);
        $allPrice  = $stokAkhir * $pricePc;

        if ($strength < 2) $status = 'CRITICAL';
        elseif ($strength < 5) $status = 'STANDAR';
        else $status = 'OVER';

        $maxNo = SinglePart::where('sheet_date', $request->sheet_date)->max('no') ?? 0;

        $newItem = SinglePart::create([
            'sheet_date'  => $request->sheet_date,
            'no'          => $maxNo + 1,
            'job_no'      => strtoupper(trim($request->job_no)),
            'vendor'      => strtoupper(trim($request->vendor)),
            'category'    => trim($request->category ?? ''),
            'customer'    => trim($request->customer ?? ''),
            'price_pc'    => $pricePc,
            'status'      => $status,
            'movement'    => trim($request->movement ?? ''),
            'cycle_issue' => (int)($request->cycle_issue ?? 1),
            'stock_awal'  => $stockAwal,
            'assy'        => $assy,
            'iami'        => (float)($request->iami ?? 0),
            'gkd'         => (float)($request->gkd ?? 0),
            'sap'         => (float)($request->sap ?? 0),
            'kap'         => (float)($request->kap ?? 0),
            'gmo'         => (float)($request->gmo ?? 0),
            'delivery'    => trim($request->delivery ?? ''),
            'incoming'    => $incoming,
            'stok_akhir'  => $stokAkhir,
            'all_price'   => $allPrice,
            'pcs_day'     => $pcsDay,
            'strength'    => $strength,
        ]);

        $this->cascadeInventoryUpdates($newItem->job_no, $newItem->sheet_date, $newItem->stok_akhir);

        return redirect()->route('single_part.index', ['sheet' => $request->sheet_date])
            ->with('sp_success', 'Job No ' . strtoupper($request->job_no) . ' berhasil ditambahkan.');
    }

    /**
     * Add Incoming stock to a Job No
     * If Job No exists on the sheet, update its incoming value.
     * If not, try to find master data and create it.
     */
    public function addIncoming(Request $request)
    {
        $request->validate([
            'sheet_date' => 'required|string',
            'job_no'     => 'required|string',
            'incoming'   => 'required|numeric|min:0',
        ]);

        $jobNoRaw    = trim($request->job_no);
        $incomingVal = (float)$request->incoming;

        // Handle Scanner Input: PartNo|JobNo|Serial|Date|Time|Qty
        if (str_contains($jobNoRaw, '|')) {
            $parts = explode('|', $jobNoRaw);
            // In this format, Job No is the second element (index 1)
            if (count($parts) >= 2) {
                $jobNo = strtoupper(trim($parts[1]));
            } else {
                $jobNo = strtoupper(trim($parts[0]));
            }
            
            if (count($parts) > 1) {
                // Take the last part as qty (handle cases like "30 PC" or "30")
                $lastPart = trim(end($parts));
                if (preg_match('/[0-9.]+/', $lastPart, $matches)) {
                    $incomingVal = (float)$matches[0];
                }
            }
        } else {
            $jobNo = strtoupper($jobNoRaw);
        }

        $sheetDate = trim($request->sheet_date);

        // 1. Try to find existing record on this sheet
        $item = SinglePart::where('sheet_date', $sheetDate)
            ->where('job_no', $jobNo)
            ->first();

        if ($item) {
            // Update existing
            $item->incoming += $incomingVal;
        } else {
            // 2. Not found, try to find master data (latest record)
            $master = SinglePart::where('job_no', $jobNo)
                ->orderBy('id', 'desc')
                ->first();

            if (!$master) {
                return back()->with('sp_error', "Job No {$jobNo} tidak ditemukan di master data. Silakan gunakan 'Add Job' terlebih dahulu.");
            }

            // Create new record for this sheet
            $maxNo = SinglePart::where('sheet_date', $sheetDate)->max('no') ?? 0;
            
            // For stock_awal, if it's a new date, we should ideally get it from previous date's stok_akhir
            // but generateTemplateForDate already handles this. If it's missing here, it's a manual add.
            // We'll use the master's stok_akhir as stock_awal if it's from a different date.
            $stockAwal = ($master->sheet_date !== $sheetDate) ? (float)$master->stok_akhir : (float)$master->stock_awal;

            $item = new SinglePart([
                'sheet_date'    => $sheetDate,
                'no'            => $maxNo + 1,
                'job_no'        => $jobNo,
                'job_no_finish' => $master->job_no_finish,
                'type_pallet'   => $master->type_pallet,
                'vendor'        => $master->vendor,
                'category'      => $master->category,
                'customer'      => $master->customer,
                'price_pc'    => $master->price_pc,
                'status'      => $master->status,
                'movement'    => $master->movement,
                'cycle_issue' => $master->cycle_issue,
                'stock_awal'  => $stockAwal,
                'assy'        => 0,
                'iami'        => 0,
                'gkd'         => 0,
                'sap'         => 0,
                'kap'         => 0,
                'gmo'         => 0,
                'delivery'    => $master->delivery,
                'incoming'    => $incomingVal,
                'pcs_day'     => $master->pcs_day ?: 1,
            ]);
        }

        // Recalculate
        $stockAwal = (float)$item->stock_awal;
        $incoming  = (float)$item->incoming;
        $pcsDay    = (float)$item->pcs_day;
        $category  = strtoupper(trim($item->category ?? ''));

        if ($category === 'FINISH PART') {
            $customerOrderField = $this->getCustomerOrderField($item->customer);
            $customerOrder = (float)($item->$customerOrderField ?? 0);
            $stokAkhir = $stockAwal - $customerOrder + $incoming;
        } else {
            $assy      = (float)$item->assy;
            $stokAkhir = $stockAwal - $assy + $incoming;
        }

        $strength  = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;
        $allPrice  = $stokAkhir * (float)$item->price_pc;

        if ($strength < 2) $status = 'CRITICAL';
        elseif ($strength < 5) $status = 'STANDAR';
        else $status = 'OVER';

        $item->stok_akhir = $stokAkhir;
        $item->strength   = $strength;
        $item->all_price  = $allPrice;
        $item->status     = $status;
        $item->save();

        $this->cascadeInventoryUpdates($item->job_no, $item->sheet_date, $item->stok_akhir, $item->price_pc);

        return redirect()->route('single_part.index', ['sheet' => $sheetDate])
            ->with('sp_success', "Incoming Stock untuk {$jobNo} berhasil ditambahkan sebesar " . number_format($incomingVal, 0) . ".");
    }

    /**
     * Update editable fields: stock_awal, assy, incoming, pcs_day
     * Auto-recalculate: stok_akhir, strength, all_price, status
     */
    public function updateInline(Request $request)
    {
        $request->validate([
            'id'    => 'required|integer',
            'field' => 'required|in:stock_awal,assy,iami,gkd,sap,kap,gmo,incoming,pcs_day,price_pc,cycle_issue',
            'value' => 'required|numeric|min:0',
        ]);

        $item = SinglePart::findOrFail($request->id);
        $field = $request->field;
        $value = (float)$request->value;

        // Update field
        $item->$field = $value;

        // Recalculate based on category
        $stockAwal = (float)$item->stock_awal;
        $incoming  = (float)$item->incoming;
        $pcsDay    = (float)$item->pcs_day;
        $category  = strtoupper(trim($item->category ?? ''));

        if ($category === 'FINISH PART') {
            // Finish Part: stok_akhir = stock_awal - customer_order + incoming
            // Determine which customer order field to use based on customer column
            $customerOrderField = $this->getCustomerOrderField($item->customer);
            $customerOrder = (float)($item->$customerOrderField ?? 0);
            $stokAkhir = $stockAwal - $customerOrder + $incoming;
        } else {
            // Single Part (default): stok_akhir = stock_awal - assy + incoming
            $assy      = (float)$item->assy;
            $stokAkhir = $stockAwal - $assy + $incoming;
        }

        $strength  = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;
        $allPrice  = $stokAkhir * $item->price_pc;

        // Determine status
        if ($strength < 2) $status = 'CRITICAL';
        elseif ($strength < 5) $status = 'STANDAR';
        else $status = 'OVER';

        $item->stok_akhir = $stokAkhir;
        $item->strength   = $strength;
        $item->all_price  = $allPrice;
        $item->status     = $status;
        $item->save();

        $this->cascadeInventoryUpdates($item->job_no, $item->sheet_date, $item->stok_akhir, $item->price_pc);

        return response()->json([
            'success'    => true,
            'stok_akhir' => $stokAkhir,
            'strength'   => $strength,
            'all_price'  => $allPrice,
            'status'     => $status,
            'price_pc'   => $item->price_pc,
        ]);
    }

    /**
     * Map customer name to its order field column
     * FINISH PART customers: ADM KAP → kap, ADM SAP → sap
     * SINGLE PART customers: tidak pakai customer order field
     */
    private function getCustomerOrderField(?string $customer): string
    {
        $customerUpper = strtoupper(trim($customer ?? ''));
        
        // ADM KAP (customer Finish Part) → kolom kap
        if (str_contains($customerUpper, 'KAP')) return 'kap';
        // ADM SAP → kolom sap
        if (str_contains($customerUpper, 'SAP')) return 'sap';
        // Customer lainnya sesuai nama
        if (str_contains($customerUpper, 'IAMI')) return 'iami';
        if (str_contains($customerUpper, 'GKD'))  return 'gkd';
        if (str_contains($customerUpper, 'GMO') || str_contains($customerUpper, 'TMMIN') || str_contains($customerUpper, 'FTI')) return 'gmo';
        
        return 'iami'; // default
    }

    /**
     * Find Python executable
     */
    private function findPython(): ?string
    {
        foreach (['python', 'python3', 'py'] as $cmd) {
            $test = shell_exec("{$cmd} --version 2>&1");
            if ($test && str_contains($test, 'Python 3')) return $cmd;
        }
        return null;
    }

    /**
     * Generate template data for a specific date
     */
    private function generateTemplateForDate($sheetDate)
    {
        // Get the most recent record for each job_no (master list)
        $latestItemsIds = DB::table('single_parts')
            ->select(DB::raw('MAX(id) as max_id'))
            ->groupBy('job_no')
            ->pluck('max_id');

        if ($latestItemsIds->isEmpty()) {
            return;
        }

        $templateItems = SinglePart::whereIn('id', $latestItemsIds)->get();

        $newItems = [];
        $now = now();
        $no = 1;
        
        foreach ($templateItems as $item) {
            $stockAwal = (float)$item->stok_akhir; // Carry over the final stock from previous
            $assy = 0;
            $incoming = 0;
            $stokAkhir = $stockAwal; 
            $pcsDay = (float)$item->pcs_day;
            $strength = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;
            $allPrice = $stokAkhir * (float)$item->price_pc;

            if ($strength < 2) $status = 'CRITICAL';
            elseif ($strength < 5) $status = 'STANDAR';
            else $status = 'OVER';

            $newItems[] = [
                'sheet_date'    => $sheetDate,
                'no'            => $no++,
                'job_no'        => $item->job_no,
                'job_no_finish' => $item->job_no_finish,
                'type_pallet'   => $item->type_pallet,
                'vendor'        => $item->vendor,
                'category'      => $item->category,
                'customer'    => $item->customer,
                'price_pc'    => $item->price_pc,
                'status'      => $status,
                'movement'    => $item->movement,
                'cycle_issue' => $item->cycle_issue,
                'stock_awal'  => $stockAwal,
                'assy'        => $assy,
                'iami'        => 0,
                'gkd'         => 0,
                'sap'         => 0,
                'kap'         => 0,
                'gmo'         => 0,
                'delivery'    => $item->delivery,
                'incoming'    => $incoming,
                'stok_akhir'  => $stokAkhir,
                'all_price'   => $allPrice,
                'pcs_day'     => $pcsDay,
                'strength'    => $strength,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        foreach (array_chunk($newItems, 100) as $chunk) {
            SinglePart::insert($chunk);
        }
    }

    /**
     * Cascade inventory updates to subsequent dates
     * Respects the category formula (Single Part vs Finish Part)
     */
    private function cascadeInventoryUpdates($jobNo, $startSheetDate, $startStokAkhir, $newPricePc = null)
    {
        $monthMap = [
            'JANUARI'=>1,'FEBRUARI'=>2,'MARET'=>3,'APRIL'=>4,'MEI'=>5,'JUNI'=>6,
            'JULI'=>7,'AGUSTUS'=>8,'SEPTEMBER'=>9,'OKTOBER'=>10,'NOVEMBER'=>11,'DESEMBER'=>12
        ];
        
        $currentNumericDate = 0;
        $parts = explode(' ', trim($startSheetDate));
        if (count($parts) >= 2) {
            $currentNumericDate = ($monthMap[strtoupper($parts[1])] ?? 0) * 100 + (int)$parts[0];
        }

        if ($currentNumericDate > 0) {
            $otherRecords = SinglePart::where('job_no', $jobNo)->get();

            $subsequentRecords = $otherRecords->filter(function($record) use ($monthMap, $currentNumericDate) {
                $p = explode(' ', trim($record->sheet_date));
                if (count($p) < 2) return false;
                $numDate = ($monthMap[strtoupper($p[1])] ?? 0) * 100 + (int)$p[0];
                return $numDate > $currentNumericDate;
            })->sortBy(function($record) use ($monthMap) {
                $p = explode(' ', trim($record->sheet_date));
                return ($monthMap[strtoupper($p[1])] ?? 0) * 100 + (int)$p[0];
            });

            $previousStokAkhir = $startStokAkhir;
            foreach ($subsequentRecords as $nextRecord) {
                $nextRecord->stock_awal = $previousStokAkhir;

                // Use category-aware formula
                $nextCategory = strtoupper(trim($nextRecord->category ?? ''));
                if ($nextCategory === 'FINISH PART') {
                    $customerOrderField = $this->getCustomerOrderField($nextRecord->customer);
                    $customerOrder = (float)($nextRecord->$customerOrderField ?? 0);
                    $nextStokAkhir = $nextRecord->stock_awal - $customerOrder + $nextRecord->incoming;
                } else {
                    // Single Part
                    $nextStokAkhir = $nextRecord->stock_awal - $nextRecord->assy + $nextRecord->incoming;
                }
                
                // Propagate new price if price_pc was changed
                if ($newPricePc !== null) {
                    $nextRecord->price_pc = $newPricePc;
                }

                $nextPcsDay = (float)$nextRecord->pcs_day;
                $nextStrength = $nextPcsDay > 0 ? round($nextStokAkhir / $nextPcsDay, 4) : 0;
                $nextAllPrice = $nextStokAkhir * (float)$nextRecord->price_pc;

                if ($nextStrength < 2) $nextStatus = 'CRITICAL';
                elseif ($nextStrength < 5) $nextStatus = 'STANDAR';
                else $nextStatus = 'OVER';

                $nextRecord->stok_akhir = $nextStokAkhir;
                $nextRecord->strength = $nextStrength;
                $nextRecord->all_price = $nextAllPrice;
                $nextRecord->status = $nextStatus;
                $nextRecord->save();
                
                $previousStokAkhir = $nextStokAkhir;
            }
        }
    }
}