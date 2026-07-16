<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RundownIncoming;
use App\Exports\RundownIncomingExport;
use Maatwebsite\Excel\Facades\Excel;

class RundownIncomingController extends Controller
{
    public function index(Request $request)
    {
        // Sort sheets chronologically using Carbon parsing
        $monthMap = [
            'JANUARI'=>1,'FEBRUARI'=>2,'MARET'=>3,'APRIL'=>4,'MEI'=>5,'JUNI'=>6,
            'JULI'=>7,'AGUSTUS'=>8,'SEPTEMBER'=>9,'OKTOBER'=>10,'NOVEMBER'=>11,'DESEMBER'=>12
        ];

        $rawSheets = RundownIncoming::distinct()->pluck('sheet_date');
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
            $exists = RundownIncoming::where('sheet_date', $selectedSheet)->exists();
            if (!$exists) {
                $this->generateTemplateForDate($selectedSheet);
                
                // Refresh available sheets after insertion
                $rawSheets = RundownIncoming::distinct()->pluck('sheet_date');
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

        $query = RundownIncoming::where('sheet_date', $selectedSheet);

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

        $allVendors = RundownIncoming::where('sheet_date', $selectedSheet)
            ->distinct()->orderBy('vendor')->pluck('vendor')->filter();

        // Customer list filtered by current category (so options stay relevant)
        $customerQuery = RundownIncoming::where('sheet_date', $selectedSheet);
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
        // Fetch related single parts grouped by job_no_finish
        $relatedSingleParts = RundownIncoming::where('sheet_date', $selectedSheet)
            ->where('category', 'SINGLE PART')
            ->whereNotNull('job_no_finish')
            ->where('job_no_finish', '!=', '')
            ->get()
            ->groupBy(function($item) {
                return strtoupper(trim($item->job_no_finish));
            });

        return view('rundown_incoming', compact(
            'items', 'total', 'perPage', 'hasData',
            'availableSheets', 'selectedSheet',
            'search', 'filterVendor', 'filterCustomer', 'filterCategory', 'sortBy', 'sortDir', 'allVendors', 'allCustomers',
            'countStandar', 'countOver', 'countMinim', 'relatedSingleParts'
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
            $dataPath  = $uploadDir . DIRECTORY_SEPARATOR . 'rundown_incoming_data.' . $extension;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            // Save uploaded file
            $file->move($uploadDir, 'rundown_incoming_data.' . $extension);

            $python = $this->findPython();
            if (!$python) {
                @unlink($dataPath);
                return back()->with('sp_error', 'Python tidak ditemukan.');
            }

            $scriptPath = base_path('python/read_rundown_incoming_monthly.py');
            if (!file_exists($scriptPath)) {
                @unlink($dataPath);
                return back()->with('sp_error', 'Script python/read_rundown_incoming_monthly.py tidak ditemukan.');
            }

            // Run Python script — ADD.xlsx is optional (pass empty string if not found)
            $pricePath = $uploadDir . DIRECTORY_SEPARATOR . 'ADD.xlsx';
            $priceArg  = file_exists($pricePath) ? escapeshellarg($pricePath) : escapeshellarg('');
            $cmd    = escapeshellcmd($python) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($dataPath) . ' ' . $priceArg;
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
                // Build a lookup map of non-empty job_no_finish and type_pallet from the upload data
                $uploadMasterFields = [];
                foreach ($result['sheets'] as $sName => $sData) {
                    foreach ($sData as $it) {
                        $job = $it['job_no'];
                        $cat = strtoupper(trim($it['category'] ?? ''));
                        if ($cat === 'SINGLE PART') {
                            if (!empty($it['job_no_finish'])) {
                                $uploadMasterFields[$job]['job_no_finish'] = $it['job_no_finish'];
                            }
                            if (!empty($it['type_pallet'])) {
                                $uploadMasterFields[$job]['type_pallet'] = $it['type_pallet'];
                            }
                        }
                    }
                }

                foreach ($result['sheets'] as $sheetName => $sheetData) {
                    // Determine all categories present in this sheet from the uploaded file
                    $categoriesInSheet = collect($sheetData)->pluck('category')->unique()->toArray();
                    
                    if (!empty($categoriesInSheet)) {
                        // Delete existing data for this sheet ONLY for the categories being uploaded
                        RundownIncoming::where('sheet_date', $sheetName)
                                  ->whereIn('category', $categoriesInSheet)
                                  ->delete();
                    }
                    
                    // Insert new data
                    $rows = [];
                    $finishPartJobs = [];
                    $singlePartReferences = [];

                    foreach ($sheetData as $item) {
                        $jobNo = $item['job_no'];
                        $jobNoFinish = $item['job_no_finish'] ?? '';
                        $typePallet = $item['type_pallet'] ?? '';
                        $category = strtoupper(trim($item['category'] ?? ''));

                        if ($category === 'SINGLE PART') {
                            // 1. Fallback from current upload master map
                            if (empty($jobNoFinish) && isset($uploadMasterFields[$jobNo]['job_no_finish'])) {
                                $jobNoFinish = $uploadMasterFields[$jobNo]['job_no_finish'];
                            }
                            if (empty($typePallet) && isset($uploadMasterFields[$jobNo]['type_pallet'])) {
                                $typePallet = $uploadMasterFields[$jobNo]['type_pallet'];
                            }

                            // 2. Fallback from database history
                            if (empty($jobNoFinish) || empty($typePallet)) {
                                $hist = $this->getLatestNonEmptyMasterFields($jobNo);
                                if (empty($jobNoFinish)) {
                                    $jobNoFinish = $hist['job_no_finish'];
                                }
                                if (empty($typePallet)) {
                                    $typePallet = $hist['type_pallet'];
                                }
                            }

                            if (!empty($jobNoFinish)) {
                                $singlePartReferences[strtoupper(trim($jobNoFinish))][] = [
                                    'vendor'      => $item['vendor'] ?? '',
                                    'customer'    => $item['customer'] ?? '',
                                    'type_pallet' => $typePallet,
                                    'pcs_day'     => $item['pcs_day'] ?? 1,
                                ];
                            }
                        } elseif ($category === 'FINISH PART') {
                            // Fallback from database history for FINISH PART
                            if (empty($typePallet) || empty($item['vendor']) || empty($item['customer']) || empty($jobNoFinish)) {
                                $fpHist = RundownIncoming::where('job_no', $jobNo)
                                    ->where('category', 'FINISH PART')
                                    ->orderBy('id', 'desc')
                                    ->get();
                                
                                foreach ($fpHist as $hist) {
                                    if (empty($typePallet) && !empty($hist->type_pallet)) {
                                        $typePallet = $hist->type_pallet;
                                    }
                                    if (empty($item['vendor']) && !empty($hist->vendor)) {
                                        $item['vendor'] = $hist->vendor;
                                    }
                                    if (empty($item['customer']) && !empty($hist->customer)) {
                                        $item['customer'] = $hist->customer;
                                    }
                                    if (empty($jobNoFinish) && !empty($hist->job_no_finish)) {
                                        $jobNoFinish = $hist->job_no_finish;
                                    }
                                    // break if all found
                                    if (!empty($typePallet) && !empty($item['vendor']) && !empty($item['customer']) && !empty($jobNoFinish)) {
                                        break;
                                    }
                                }
                            }
                            
                            // Ultimate fallback for FINISH PART job_no_finish
                            if (empty($jobNoFinish)) {
                                $jobNoFinish = $jobNo;
                            }

                            $finishPartJobs[strtoupper(trim($jobNo))] = true;
                        }


                        $rows[] = [
                            'sheet_date'    => $sheetName,
                            'no'            => $item['no'],
                            'job_no'        => $jobNo,
                            'job_no_finish' => $jobNoFinish,
                            'type_pallet'   => $typePallet,
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

                    // Append virtual/missing Finish Parts to rows so they are stored as real database entries
                    $maxNo = collect($sheetData)->max('no') ?? 0;
                    foreach ($singlePartReferences as $finishJob => $samples) {
                        if (!isset($finishPartJobs[$finishJob])) {
                            $sample = $samples[0];
                            
                            // Calculate price sum from the uploaded sheetData for this finish part
                            $sumPrice = collect($sheetData)
                                ->where('job_no_finish', $finishJob)
                                ->where('category', 'SINGLE PART')
                                ->sum(function($it) {
                                    return (float)($it['price_pc'] ?? 0);
                                });

                            $maxNo++;
                            $rows[] = [
                                'sheet_date'    => $sheetName,
                                'no'            => $maxNo,
                                'job_no'        => $finishJob,
                                'job_no_finish' => $finishJob,
                                'type_pallet'   => $sample['type_pallet'],
                                'vendor'        => $sample['vendor'],
                                'category'      => 'FINISH PART',
                                'customer'      => $sample['customer'],
                                'price_pc'      => $sumPrice,
                                'status'        => 'STANDAR',
                                'movement'      => 'SLOW MOVING',
                                'cycle_issue'   => 1,
                                'stock_awal'    => 0,
                                'assy'          => 0,
                                'iami'          => 0,
                                'gkd'           => 0,
                                'sap'           => 0,
                                'kap'           => 0,
                                'gmo'           => 0,
                                'delivery'      => '',
                                'incoming'      => 0,
                                'stok_akhir'    => 0,
                                'all_price'     => 0,
                                'pcs_day'       => $sample['pcs_day'],
                                'strength'      => 0,
                                'created_at'    => $now,
                                'updated_at'    => $now,
                            ];
                            $finishPartJobs[$finishJob] = true; // prevent duplicates
                        }
                    }

                    $chunks = array_chunk($rows, 100);
                    foreach ($chunks as $chunk) {
                        RundownIncoming::insert($chunk);
                    }
                    
                    $imported += count($rows);

                    // Update all future records for these job_nos with the new master fields (job_no_finish, type_pallet)
                    foreach ($sheetData as $item) {
                        if (!empty($item['job_no_finish']) || !empty($item['type_pallet'])) {
                            RundownIncoming::where('job_no', $item['job_no'])
                                ->where('sheet_date', '!=', $sheetName)
                                ->where(function($q) {
                                    $q->whereNull('job_no_finish')
                                      ->orWhere('job_no_finish', '')
                                      ->orWhereNull('type_pallet')
                                      ->orWhere('type_pallet', '');
                                })
                                ->update([
                                    'job_no_finish' => $item['job_no_finish'] ?? '',
                                    'type_pallet'   => $item['type_pallet'] ?? ''
                                ]);
                        }
                    }
                }
            });

            @unlink($dataPath);
            
            return redirect()->route('rundown_incoming.index')
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

        return Excel::download(new RundownIncomingExport($monthId, $year), "Rundown_Incoming_{$monthId}_{$year}.xlsx");
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        $path = storage_path('app/uploads/NEW Rundown_Incoming_FINISH + SINGLE..xlsx');
        if (!file_exists($path)) {
            $path = storage_path('app/uploads/NEW Rundown_Incoming_FINISH + SINGLE.xlsx');
        }
        if (!file_exists($path)) {
            return back()->with('sp_error', 'File template tidak ditemukan.');
        }
        return response()->download($path, 'Template_Rundown_Incoming.xlsx');
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

        // Check the record exists on the requested date
        $exists = RundownIncoming::where('sheet_date', $sheetDate)
            ->where('job_no', $jobNo)
            ->exists();

        if (!$exists) {
            return back()->with('sp_error', "Job No {$jobNo} tidak ditemukan pada tanggal {$sheetDate}.");
        }

        // Build numeric date for the selected sheet so we can compare chronologically
        $monthMap = [
            'JANUARI'=>1,'FEBRUARI'=>2,'MARET'=>3,'APRIL'=>4,'MEI'=>5,'JUNI'=>6,
            'JULI'=>7,'AGUSTUS'=>8,'SEPTEMBER'=>9,'OKTOBER'=>10,'NOVEMBER'=>11,'DESEMBER'=>12
        ];
        $parseDateNum = function(string $d) use ($monthMap): int {
            $p = explode(' ', trim($d));
            if (count($p) < 2) return 0;
            return ($monthMap[strtoupper($p[1])] ?? 0) * 100 + (int)$p[0];
        };

        $selectedNum = $parseDateNum($sheetDate);

        // Collect all sheet_dates that exist for this job_no
        $allDates = RundownIncoming::where('job_no', $jobNo)
            ->distinct()
            ->pluck('sheet_date');

        // Delete from today onwards (>= selectedNum), keep past dates intact
        $deletedTotal = 0;
        foreach ($allDates as $date) {
            if ($parseDateNum($date) >= $selectedNum) {
                $deletedTotal += RundownIncoming::where('sheet_date', $date)
                    ->where('job_no', $jobNo)
                    ->delete();
            }
        }

        $futureDatesCount = $allDates->filter(fn($d) => $parseDateNum($d) > $selectedNum)->count();
        $msg = "Job No {$jobNo} berhasil dihapus dari tanggal {$sheetDate}";
        if ($futureDatesCount > 0) {
            $msg .= " dan {$futureDatesCount} hari berikutnya";
        }
        $msg .= '.';

        return redirect()->route('rundown_incoming.index', ['sheet' => $sheetDate])
            ->with('sp_success', $msg);
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
            'job_no_finish' => 'nullable|string',
            'type_pallet'   => 'nullable|string',
            'pcs_day'    => 'required|numeric|min:0',
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

        $maxNo = RundownIncoming::where('sheet_date', $request->sheet_date)->max('no') ?? 0;

        $newItem = RundownIncoming::create([
            'sheet_date'  => $request->sheet_date,
            'no'          => $maxNo + 1,
            'job_no'      => strtoupper(trim($request->job_no)),
            'job_no_finish' => strtoupper(trim($request->job_no_finish ?? '')),
            'type_pallet'   => strtoupper(trim($request->type_pallet ?? '')),
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

        $this->cascadeInventoryUpdates($newItem->job_no, $newItem->sheet_date, $newItem->stok_akhir, null, $newItem->job_no_finish, $newItem->type_pallet);

        return redirect()->route('rundown_incoming.index', ['sheet' => $request->sheet_date])
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
            if (count($parts) >= 2) {
                $jobNo = strtoupper(trim($parts[1]));
            } else {
                $jobNo = strtoupper(trim($parts[0]));
            }
            
            if (count($parts) > 1) {
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
        $item = RundownIncoming::where('sheet_date', $sheetDate)
            ->where('job_no', $jobNo)
            ->first();

        if ($item) {
            // Update existing
            $item->incoming += $incomingVal;
        } else {
            // 2. Not found, try to find master data (latest record)
            $master = RundownIncoming::where('job_no', $jobNo)
                ->orderBy('id', 'desc')
                ->first();

            if (!$master) {
                return back()->with('sp_error', "Job No {$jobNo} tidak ditemukan di master data. Silakan gunakan 'Add Job' terlebih dahulu.");
            }

            // Create new record for this sheet
            $maxNo = RundownIncoming::where('sheet_date', $sheetDate)->max('no') ?? 0;
            $stockAwal = ($master->sheet_date !== $sheetDate) ? (float)$master->stok_akhir : (float)$master->stock_awal;

            $jobNoFinish = $master->job_no_finish;
            $typePallet = $master->type_pallet;

            $cat = strtoupper(trim($master->category ?? ''));
            if ($cat === 'SINGLE PART') {
                if (empty($jobNoFinish) || empty($typePallet)) {
                    $hist = $this->getLatestNonEmptyMasterFields($jobNo);
                    if (empty($jobNoFinish)) {
                        $jobNoFinish = $hist['job_no_finish'];
                    }
                    if (empty($typePallet)) {
                        $typePallet = $hist['type_pallet'];
                    }
                }
            }

            $item = new RundownIncoming([
                'sheet_date'    => $sheetDate,
                'no'            => $maxNo + 1,
                'job_no'        => $jobNo,
                'job_no_finish' => $jobNoFinish,
                'type_pallet'   => $typePallet,
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

        return redirect()->route('rundown_incoming.index', ['sheet' => $sheetDate])
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

        $item = RundownIncoming::findOrFail($request->id);
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
            $customerOrderField = $this->getCustomerOrderField($item->customer);
            $customerOrder = (float)($item->$customerOrderField ?? 0);
            $stokAkhir = $stockAwal - $customerOrder + $incoming;
        } else {
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

        $response = [
            'success'    => true,
            'stok_akhir' => $stokAkhir,
            'strength'   => $strength,
            'all_price'  => $allPrice,
            'status'     => $status,
            'price_pc'   => $item->price_pc,
        ];

        // If a single part price changed, recalculate the parent's sum and save it
        if ($category === 'SINGLE PART' && !empty($item->job_no_finish) && $field === 'price_pc') {
            $parent = RundownIncoming::where('sheet_date', $item->sheet_date)
                ->where('job_no', $item->job_no_finish)
                ->where('category', 'FINISH PART')
                ->first();
                
            if ($parent) {
                $sumPrice = RundownIncoming::where('sheet_date', $item->sheet_date)
                    ->where('category', 'SINGLE PART')
                    ->where('job_no_finish', $item->job_no_finish)
                    ->sum('price_pc');
                    
                $parent->price_pc = $sumPrice;
                $parent->all_price = $parent->stok_akhir * $sumPrice;
                $parent->save();
                
                $response['parent'] = [
                    'id' => $parent->id,
                    'price_pc' => $parent->price_pc,
                    'all_price' => $parent->all_price,
                ];
            }
        }

        return response()->json($response);
    }

    private function getCustomerOrderField(?string $customer): string
    {
        $customerUpper = strtoupper(trim($customer ?? ''));
        
        if (str_contains($customerUpper, 'KAP')) return 'kap';
        if (str_contains($customerUpper, 'SAP')) return 'sap';
        if (str_contains($customerUpper, 'IAMI')) return 'iami';
        if (str_contains($customerUpper, 'GKD'))  return 'gkd';
        if (str_contains($customerUpper, 'GMO') || str_contains($customerUpper, 'TMMIN') || str_contains($customerUpper, 'FTI')) return 'gmo';
        
        return 'iami'; // default
    }

    private function findPython(): ?string
    {
        foreach (['python', 'python3', 'py'] as $cmd) {
            $test = shell_exec("{$cmd} --version 2>&1");
            if ($test && str_contains($test, 'Python 3')) return $cmd;
        }
        return null;
    }

    private function getLatestNonEmptyMasterFields($jobNo)
    {
        $finish = RundownIncoming::where('job_no', $jobNo)
            ->where('category', 'SINGLE PART')
            ->whereNotNull('job_no_finish')
            ->where('job_no_finish', '!=', '')
            ->orderBy('id', 'desc')
            ->value('job_no_finish');

        $pallet = RundownIncoming::where('job_no', $jobNo)
            ->where('category', 'SINGLE PART')
            ->whereNotNull('type_pallet')
            ->where('type_pallet', '!=', '')
            ->orderBy('id', 'desc')
            ->value('type_pallet');

        return [
            'job_no_finish' => $finish ?: '',
            'type_pallet'   => $pallet ?: '',
        ];
    }

    private function generateTemplateForDate($sheetDate)
    {
        $latestItemsIds = DB::table('rundown_incomings')
            ->select(DB::raw('MAX(id) as max_id'))
            ->groupBy('job_no')
            ->pluck('max_id');

        if ($latestItemsIds->isEmpty()) {
            return;
        }

        $templateItems = RundownIncoming::whereIn('id', $latestItemsIds)->get();

        $newItems = [];
        $now = now();
        $no = 1;
        
        foreach ($templateItems as $item) {
            $stockAwal = (float)$item->stok_akhir;
            $assy = 0;
            $incoming = 0;
            $stokAkhir = $stockAwal; 
            $pcsDay = (float)$item->pcs_day;
            $strength = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;
            $allPrice = $stokAkhir * (float)$item->price_pc;

            if ($strength < 2) $status = 'CRITICAL';
            elseif ($strength < 5) $status = 'STANDAR';
            else $status = 'OVER';

            $jobNoFinish = $item->job_no_finish;
            $typePallet  = $item->type_pallet;
            $vendor      = $item->vendor;
            $customer    = $item->customer;
            $pricePc     = $item->price_pc;

            $cat = strtoupper(trim($item->category ?? ''));
            if ($cat === 'SINGLE PART') {
                if (empty($jobNoFinish) || empty($typePallet)) {
                    $hist = $this->getLatestNonEmptyMasterFields($item->job_no);
                    if (empty($jobNoFinish)) $jobNoFinish = $hist['job_no_finish'];
                    if (empty($typePallet))  $typePallet  = $hist['type_pallet'];
                }
            } elseif ($cat === 'FINISH PART') {
                // For FINISH PART: if vendor/customer/price_pc/type_pallet/job_no_finish are empty, fetch from DB history
                if (empty($vendor) || empty($customer) || $pricePc == 0 || empty($typePallet) || empty($jobNoFinish)) {
                    $fpHist = RundownIncoming::where('job_no', $item->job_no)
                        ->where('category', 'FINISH PART')
                        ->orderBy('id', 'desc')
                        ->get();
                    
                    foreach ($fpHist as $hist) {
                        if (empty($vendor) && !empty($hist->vendor))        $vendor   = $hist->vendor;
                        if (empty($customer) && !empty($hist->customer))    $customer = $hist->customer;
                        if ($pricePc == 0 && $hist->price_pc > 0)           $pricePc  = $hist->price_pc;
                        if (empty($typePallet) && !empty($hist->type_pallet))$typePallet = $hist->type_pallet;
                        if (empty($jobNoFinish) && !empty($hist->job_no_finish))$jobNoFinish = $hist->job_no_finish;
                        
                        // break early if all are filled
                        if (!empty($vendor) && !empty($customer) && $pricePc > 0 && !empty($typePallet) && !empty($jobNoFinish)) {
                            break;
                        }
                    }
                }
                
                // Ultimate fallback for FINISH PART job_no_finish
                if (empty($jobNoFinish)) {
                    $jobNoFinish = $item->job_no;
                }
            }

            $newItems[] = [
                'sheet_date'    => $sheetDate,
                'no'            => $no++,
                'job_no'        => $item->job_no,
                'job_no_finish' => $jobNoFinish,
                'type_pallet'   => $typePallet,
                'vendor'        => $vendor,
                'category'      => $item->category,
                'customer'      => $customer,
                'price_pc'      => $pricePc,
                'status'        => $status,
                'movement'      => $item->movement,
                'cycle_issue'   => $item->cycle_issue,
                'stock_awal'    => $stockAwal,
                'assy'          => $assy,
                'iami'          => 0,
                'gkd'           => 0,
                'sap'           => 0,
                'kap'           => 0,
                'gmo'           => 0,
                'delivery'      => $item->delivery,
                'incoming'      => $incoming,
                'stok_akhir'    => $stokAkhir,
                'all_price'     => $allPrice,
                'pcs_day'       => $pcsDay,
                'strength'      => $strength,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($newItems, 100) as $chunk) {
            RundownIncoming::insert($chunk);
        }
    }

    private function cascadeInventoryUpdates($jobNo, $startSheetDate, $startStokAkhir, $newPricePc = null, $jobNoFinish = null, $typePallet = null)
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
            $otherRecords = RundownIncoming::where('job_no', $jobNo)->get();

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

                if ($jobNoFinish !== null) $nextRecord->job_no_finish = $jobNoFinish;
                if ($typePallet !== null)  $nextRecord->type_pallet = $typePallet;

                $nextCategory = strtoupper(trim($nextRecord->category ?? ''));
                if ($nextCategory === 'FINISH PART') {
                    $customerOrderField = $this->getCustomerOrderField($nextRecord->customer);
                    $customerOrder = (float)($nextRecord->$customerOrderField ?? 0);
                    $nextStokAkhir = $nextRecord->stock_awal - $customerOrder + $nextRecord->incoming;
                } else {
                    $nextStokAkhir = $nextRecord->stock_awal - $nextRecord->assy + $nextRecord->incoming;
                }
                
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
