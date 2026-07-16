<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RundownPress;
use App\Models\ScheduleStamping;

class RundownPressController extends Controller
{
    private array $monthMap = [
        'JANUARI'=>1,'FEBRUARI'=>2,'MARET'=>3,'APRIL'=>4,'MEI'=>5,'JUNI'=>6,
        'JULI'=>7,'AGUSTUS'=>8,'SEPTEMBER'=>9,'OKTOBER'=>10,'NOVEMBER'=>11,'DESEMBER'=>12
    ];
    private array $monthsId = [
        1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL',
        5=>'MEI', 6=>'JUNI', 7=>'JULI', 8=>'AGUSTUS',
        9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER'
    ];

    public function index(Request $request)
    {
        $monthMap  = $this->monthMap;
        $monthsId  = $this->monthsId;

        $rawSheets = RundownPress::distinct()->pluck('sheet_date');
        $availableSheets = $rawSheets->sortBy(function($s) use ($monthMap) {
            $parts = explode(' ', trim($s));
            if (count($parts) < 2) return 0;
            return ($monthMap[strtoupper($parts[1])] ?? 0) * 100 + (int)$parts[0];
        })->values();

        $todaySheet    = now()->format('d') . ' ' . $monthsId[(int)now()->format('m')];
        $selectedSheet = trim($request->get('sheet', $todaySheet));

        // Auto-generate template for missing dates
        if ($selectedSheet !== '' && $availableSheets->isNotEmpty()) {
            $exists = RundownPress::where('sheet_date', $selectedSheet)->exists();
            if (!$exists) {
                $this->generateTemplateForDate($selectedSheet);
                $rawSheets = RundownPress::distinct()->pluck('sheet_date');
                $availableSheets = $rawSheets->sortBy(function($s) use ($monthMap) {
                    $parts = explode(' ', trim($s));
                    if (count($parts) < 2) return 0;
                    return ($monthMap[strtoupper($parts[1])] ?? 0) * 100 + (int)$parts[0];
                })->values();
            }
        }

        $search       = trim($request->get('search', '') ?? '');
        $filterVendor = $request->get('vendor', '');
        $sortBy       = $request->get('sort', 'no');
        $sortDir      = $request->get('dir', 'asc');

        $allowed = ['no','job_no','tipe','vendor','stock_awal','price','incoming','stok_akhir','pcs_day','strength','status'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'no';
        if (!in_array($sortDir, ['asc','desc'])) $sortDir = 'asc';

        $query = RundownPress::where('sheet_date', $selectedSheet);

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('job_no', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('tipe',   'like', "%{$search}%");
            });
        }

        if (!empty($filterVendor)) {
            $query->where('vendor', $filterVendor);
        }

        $query->orderBy($sortBy, $sortDir);

        $allVendors = RundownPress::where('sheet_date', $selectedSheet)
            ->distinct()->orderBy('vendor')->pluck('vendor')->filter();

        $total   = (clone $query)->count();
        $perPage = 50;
        $items   = $query->paginate($perPage)->appends($request->query());
        $hasData = $availableSheets->isNotEmpty();

        $countStandar = RundownPress::where('sheet_date', $selectedSheet)->where('status', 'STANDAR')->count();
        $countOver    = RundownPress::where('sheet_date', $selectedSheet)->where('status', 'OVER')->count();
        $countMinim   = RundownPress::where('sheet_date', $selectedSheet)->where('status', 'CRITICAL')->count();

        return view('rundown_press', compact(
            'items', 'total', 'perPage', 'hasData',
            'availableSheets', 'selectedSheet',
            'search', 'filterVendor', 'sortBy', 'sortDir', 'allVendors',
            'countStandar', 'countOver', 'countMinim'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate(['excel_file' => 'required|extensions:xlsx,xls,xlsm|max:51200']);

        try {
            $file         = $request->file('excel_file');
            $extension    = strtolower($file->getClientOriginalExtension());
            $originalName = $file->getClientOriginalName(); // must read BEFORE move()
            $uploadDir    = storage_path('app/uploads');
            $dataPath     = $uploadDir . '/rundown_press_temp.' . $extension;

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $file->move($uploadDir, 'rundown_press_temp.' . $extension);

            $python = $this->findPython();
            if (!$python) {
                @unlink($dataPath);
                return back()->with('error', 'Python tidak ditemukan di server. Pastikan Python 3 sudah terinstall.');
            }

            $scriptPath = base_path('python/read_rundown_press.py');

            if (!file_exists($scriptPath)) {
                @unlink($dataPath);
                return back()->with('error', 'Script python/read_rundown_press.py tidak ditemukan.');
            }

            // Use proc_open for reliable cross-platform execution (avoids PATH issues)
            $output = $this->runPythonScript($python, $scriptPath, $dataPath, $originalName);

            if ($output === null || $output === '') {
                @unlink($dataPath);
                return back()->with('error', 'Script Python tidak menghasilkan output. Pastikan openpyxl terinstall: pip install openpyxl');
            }

            $jsonStart = strpos($output, '{');
            if ($jsonStart === false) {
                @unlink($dataPath);
                return back()->with('error', 'Output script tidak valid: ' . substr($output, 0, 300));
            }

            $result = json_decode(substr($output, $jsonStart), true);
            if (!$result || isset($result['error'])) {
                @unlink($dataPath);
                $errMsg = $result['error'] ?? 'Unknown error';
                return back()->with('error', 'Error parsing Excel: ' . $errMsg);
            }

            if (empty($result['sheets'])) {
                @unlink($dataPath);
                return back()->with('error', 'Tidak ada data yang ditemukan. Pastikan sheet bernama "RUNDOWN" tersedia.');
            }

            $imported       = 0;
            $now            = now();
            $firstSheetDate = null;

            $monthMap = $this->monthMap;
            $sheetNames = array_keys($result['sheets']);
            usort($sheetNames, function($a, $b) use ($monthMap) {
                $pA = explode(' ', trim($a));
                $pB = explode(' ', trim($b));
                if (count($pA) < 2 || count($pB) < 2) return 0;
                $valA = ($monthMap[strtoupper($pA[1])] ?? 0) * 100 + (int)$pA[0];
                $valB = ($monthMap[strtoupper($pB[1])] ?? 0) * 100 + (int)$pB[0];
                return $valA <=> $valB;
            });

            $runningStock = []; // job_no => last_stok_akhir

            DB::transaction(function () use ($result, $sheetNames, $now, &$imported, &$firstSheetDate, &$runningStock) {
                foreach ($sheetNames as $sheetName) {
                    $sheetData = $result['sheets'][$sheetName];
                    if ($firstSheetDate === null) {
                        $firstSheetDate = $sheetName;
                    }

                    // Hapus data lama untuk tanggal yang sama agar tidak duplikat
                    RundownPress::where('sheet_date', $sheetName)->delete();

                    $rows = [];
                    foreach ($sheetData as $item) {
                        $jobNo = $item['job_no'] ?? null;
                        if (!$jobNo) continue;

                        if (isset($runningStock[$jobNo])) {
                            $stAwal = $runningStock[$jobNo];
                        } else {
                            $stAwal = (float)($item['stock_awal'] ?? 0);
                        }
                        $aProdRaw = $item['actual_prod'] ?? null;
                        $pDay     = (float)($item['plan_day'] ?? 0);
                        $order    = (float)($item['spare_part'] ?? 0);
                        $pcsDay   = (float)($item['pcs_day'] ?? 0);

                        // New Formula: Stok Akhir = Stok Awal + Actual Prod - Order
                        // MDFO (incoming), Plan Day, Plan Night are excluded
                        $actProd = (float)$aProdRaw;
                        $stokAkhir = $stAwal + $actProd - $order;
                        $runningStock[$jobNo] = $stokAkhir;
                        $str     = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;
                        
                        if ($str < 2)      $stts = 'CRITICAL';
                        elseif ($str < 5)  $stts = 'STANDAR';
                        else               $stts = 'OVER';

                        $rows[] = [
                            'sheet_date'   => $sheetName,
                            'no'           => $item['no'] ?? null,
                            'job_no'       => $jobNo,
                            'tipe'         => $item['tipe'] ?? null,
                            'vendor'       => $item['vendor'] ?? null,
                            'update_stock' => $item['update_stock'] ?? null,
                            'stock_awal'   => $stAwal,
                            'price'        => $item['price'] ?? 0,
                            'incoming'     => $item['incoming'] ?? 0,
                            'iami'         => $item['iami'] ?? 0,
                            'spare_part'   => $order,
                            'gkd'          => $item['gkd'] ?? 0,
                            'sap'          => $item['sap'] ?? 0,
                            'kap'          => $item['kap'] ?? 0,
                            'gmo'          => $item['gmo'] ?? 0,
                            'plan_day'     => $pDay,
                            'plan_night'   => $item['plan_night'] ?? 0,
                            'actual_prod'  => $aProdRaw, // Store as is (null if empty in Excel)
                            'stok_akhir'   => $stokAkhir,
                            'pcs_day'      => $pcsDay,
                            'strength'     => $str,
                            'status'       => $stts,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }

                    foreach (array_chunk($rows, 100) as $chunk) {
                        RundownPress::insert($chunk);
                    }
                    $imported += count($rows);
                }
            });

            @unlink($dataPath);

            if ($imported === 0) {
                return back()->with('error', 'Tidak ada data yang berhasil diimport. Pastikan format file Excel sesuai.');
            }

            $successMsg = 'Upload berhasil! ' . $imported . ' item dari ' . count($result['sheets']) . ' tanggal diimport.';

            // Redirect ke tanggal pertama yang diupload agar langsung terlihat datanya
            return redirect()->route('rundown_press.index', ['sheet' => $firstSheetDate])
                             ->with('success', $successMsg);

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Inline update a single field, recalculate stok_akhir, strength, status
     * then cascade to subsequent dates
     */
    public function updateInline(Request $request)
    {
        $request->validate([
            'id'    => 'required|integer',
            'field' => 'required|in:stock_awal,incoming,iami,spare_part,gkd,sap,kap,gmo,pcs_day,plan_day,plan_night,actual_prod',
            'value' => 'nullable|numeric',
        ]);

        $item  = RundownPress::findOrFail($request->id);
        $field = $request->field;
        $value = $request->value !== null ? (float)$request->value : null;

        $item->$field = $value;

        // Hitung working days dan pcs_day otomatis jika field adalah incoming
        if ($field === 'incoming' && $value !== null) {
            $parts = explode(' ', trim($item->sheet_date));
            $monthName = isset($parts[1]) ? strtoupper($parts[1]) : 'MEI';
            $month = $this->monthMap[$monthName] ?? 5;
            $year = $item->created_at ? (int)$item->created_at->format('Y') : (int)date('Y');
            
            $workingDays = $this->getWorkingDaysCount($month, $year);
            $pcsDay = round($value / $workingDays, 4);
            $item->pcs_day = $pcsDay;
        } else {
            $pcsDay = (float) $item->pcs_day;
        }

        // Otomatis isi actual_prod jika field adalah plan_day atau plan_night
        if ($field === 'plan_day' || $field === 'plan_night') {
            $item->actual_prod = (float)$item->plan_day + (float)$item->plan_night;
        }

        // Recalculate
        $stockAwal = (float) $item->stock_awal;
        $sparePart = (float) $item->spare_part;
        $planDay   = (float) $item->plan_day;
        $actualProdRaw = $item->actual_prod;

        // Formula: Stok Akhir = Stok Awal + Actual Prod - Spare Part (Order)
        // MDFO (incoming), Plan Day, Plan Night are excluded from the formula for monitoring only
        $actProd = (float)$actualProdRaw;
        $stokAkhir   = $stockAwal + $actProd - $sparePart;
        $strength    = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;

        if ($strength < 2)      $status = 'CRITICAL';
        elseif ($strength < 5)  $status = 'STANDAR';
        else                    $status = 'OVER';

        $item->stok_akhir = $stokAkhir;
        $item->strength   = $strength;
        $item->status     = $status;
        $item->save();

        // Cascade to subsequent dates
        $this->cascadeUpdates($item->job_no, $item->sheet_date, $item->stok_akhir, $pcsDay);

        // Sync stok_akhir -> plan di Schedule Stamping
        $this->syncStokAkhirToScheduleStamping($item->job_no, $item->sheet_date, $item->stok_akhir);

        return response()->json([
            'success'     => true,
            'stok_akhir'  => $stokAkhir,
            'strength'    => $strength,
            'status'      => $status,
            'pcs_day'     => $pcsDay,
            'actual_prod' => $item->actual_prod,
        ]);
    }

    /**
     * Hitung jumlah hari kerja (Senin - Jumat) tidak termasuk tanggal merah (hari libur nasional)
     */
    private function getWorkingDaysCount(int $month, int $year): int
    {
        $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
        $holidays = $this->getHolidays($year);
        
        $workingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $time = mktime(12, 0, 0, $month, $d, $year);
            $dayOfWeek = (int)date('N', $time); // 1 = Senin, 7 = Minggu
            
            // Hanya Senin - Jumat
            if ($dayOfWeek <= 5) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                if (!in_array($dateStr, $holidays)) {
                    $workingDays++;
                }
            }
        }
        
        return $workingDays > 0 ? $workingDays : 20;
    }

    /**
     * Daftar tanggal merah (hari libur nasional) Indonesia
     */
    private function getHolidays(int $year): array
    {
        if ($year === 2026) {
            return [
                '2026-01-01', // Tahun Baru
                '2026-01-16', // Isra Mi'raj
                '2026-02-17', // Imlek
                '2026-03-19', // Nyepi
                '2026-03-21', // Idul Fitri
                '2026-03-22', // Idul Fitri
                '2026-04-03', // Wafat Yesus Kristus
                '2026-05-01', // Hari Buruh Internasional
                '2026-05-14', // Kenaikan Yesus Kristus
                '2026-05-27', // Hari Raya Idul Adha
                '2026-05-31', // Hari Raya Waisak
                '2026-06-01', // Hari Lahir Pancasila
                '2026-06-16', // Tahun Baru Islam
                '2026-08-17', // Hari Kemerdekaan RI
                '2026-08-25', // Maulid Nabi
                '2026-12-25', // Hari Natal
            ];
        }

        if ($year === 2025) {
            return [
                '2025-01-01',
                '2025-01-27',
                '2025-01-29',
                '2025-03-29',
                '2025-03-31',
                '2025-04-01',
                '2025-04-18',
                '2025-05-01',
                '2025-05-12',
                '2025-05-29',
                '2025-06-01',
                '2025-06-06',
                '2025-06-27',
                '2025-08-17',
                '2025-09-05',
                '2025-12-25',
            ];
        }

        if ($year === 2024) {
            return [
                '2024-01-01',
                '2024-02-08',
                '2024-02-10',
                '2024-03-11',
                '2024-03-29',
                '2024-04-10',
                '2024-04-11',
                '2024-05-01',
                '2024-05-09',
                '2024-05-23',
                '2024-06-01',
                '2024-06-17',
                '2024-07-07',
                '2024-08-17',
                '2024-09-16',
                '2024-12-25',
            ];
        }

        return [
            "$year-01-01",
            "$year-05-01",
            "$year-06-01",
            "$year-08-17",
            "$year-12-25",
        ];
    }

    /**
     * Sync stok_akhir dari RundownPress ke kolom plan di ScheduleStamping.
     * Matching: job_no sama + upload_date mengandung sheet_date (misal "08 MEI" ⊂ "08 MEI 2026").
     */
    private function syncStokAkhirToScheduleStamping(string $jobNo, string $sheetDate, float $stokAkhir): void
    {
        if (!$jobNo) return;

        // Cari semua schedule_stamping yang job_no-nya sama dan upload_date mengandung sheetDate
        // sheetDate format: "08 MEI", upload_date format: "08 MEI 2026"
        $rows = ScheduleStamping::where('job_no', $jobNo)
            ->where('row_type', 'job')
            ->where('upload_date', 'like', $sheetDate . '%')
            ->get();

        if ($rows->isEmpty()) {
            // Fallback: coba semua upload_date yang ada (tidak batasi tanggal)
            $rows = ScheduleStamping::where('job_no', $jobNo)
                ->where('row_type', 'job')
                ->get();
        }

        foreach ($rows as $stamping) {
            $stamping->plan = $stokAkhir;
            $stamping->save();
        }
    }

    /**
     * Sinkronisasi massal: semua job_no di sheet_date tertentu -> plan di Schedule Stamping.
     * Dipanggil via GET /rundown-press/sync-to-stamping?sheet=08+MEI
     */
    public function syncAllToScheduleStamping(Request $request)
    {
        $sheetDate = $request->get('sheet');
        if (!$sheetDate) {
            return response()->json(['error' => 'Parameter sheet diperlukan (contoh: ?sheet=08 MEI)'], 422);
        }

        // Ambil stok_akhir terbaru per job_no untuk sheet_date ini
        // Karena ada 2 record per job_no (PRESS-A dan PRESS-B dst), kita rata-rata atau ambil max?
        // Sesuai kebutuhan: pakai record pertama per job_no (berdasarkan id terkecil)
        $records = RundownPress::where('sheet_date', $sheetDate)
            ->whereNotNull('job_no')
            ->orderBy('id')
            ->get()
            ->groupBy('job_no');

        $synced = 0;
        foreach ($records as $jobNo => $group) {
            if (!$jobNo) continue;
            // Gunakan record pertama untuk mendapatkan stok_akhir
            $stokAkhir = (float) $group->first()->stok_akhir;
            $this->syncStokAkhirToScheduleStamping($jobNo, $sheetDate, $stokAkhir);
            $synced++;
        }

        return response()->json([
            'success' => true,
            'synced'  => $synced,
            'message' => "Berhasil sync {$synced} job ke Schedule Stamping."
        ]);
    }

    /**
     * Cascade stock_awal to subsequent dates for the same job_no
     */
    private function cascadeUpdates(string $jobNo, string $startSheetDate, float $startStokAkhir, float $pcsDay)
    {
        $monthMap = $this->monthMap;

        $currentNum = 0;
        $parts = explode(' ', trim($startSheetDate));
        if (count($parts) >= 2) {
            $currentNum = ($monthMap[strtoupper($parts[1])] ?? 0) * 100 + (int)$parts[0];
        }
        if ($currentNum === 0) return;

        $otherRecords = RundownPress::where('job_no', $jobNo)->get();

        $subsequent = $otherRecords->filter(function($r) use ($monthMap, $currentNum) {
            $p = explode(' ', trim($r->sheet_date));
            if (count($p) < 2) return false;
            return ($monthMap[strtoupper($p[1])] ?? 0) * 100 + (int)$p[0] > $currentNum;
        })->sortBy(function($r) use ($monthMap) {
            $p = explode(' ', trim($r->sheet_date));
            return ($monthMap[strtoupper($p[1])] ?? 0) * 100 + (int)$p[0];
        });

        $prevStokAkhir = $startStokAkhir;
        foreach ($subsequent as $next) {
            $next->stock_awal = $prevStokAkhir;
            $next->pcs_day = $pcsDay; // Cascade the updated pcs_day to future dates!

            $incoming   = (float) $next->incoming;
            $iami       = (float) $next->iami;
            $sparePart  = (float) $next->spare_part;
            $gkd        = (float) $next->gkd;
            $sap        = (float) $next->sap;
            $kap        = (float) $next->kap;
            $gmo        = (float) $next->gmo;
            $nextActualProd = (float)$next->actual_prod;
            $nextPcsDay = $pcsDay; // Use the cascaded pcs_day

            // New Formula: Stok Akhir = Stok Awal + Actual Prod - Order (spare_part)
            // MDFO (incoming), Plan Day, Plan Night are NOT included in the calculation
            $nextStokAkhir  = $prevStokAkhir + $nextActualProd - $sparePart;
            $nextStrength   = $nextPcsDay > 0 ? round($nextStokAkhir / $nextPcsDay, 4) : 0;

            if ($nextStrength < 2)      $nextStatus = 'CRITICAL';
            elseif ($nextStrength < 5)  $nextStatus = 'STANDAR';
            else                        $nextStatus = 'OVER';

            $next->stok_akhir = $nextStokAkhir;
            $next->strength   = $nextStrength;
            $next->status     = $nextStatus;
            $next->save();

            $prevStokAkhir = $nextStokAkhir;
        }
    }

    private function generateTemplateForDate(string $sheetDate): void
    {
        $latestIds = DB::table('rundown_presses')
            ->select(DB::raw('MAX(id) as max_id'))
            ->groupBy('job_no')
            ->pluck('max_id');

        if ($latestIds->isEmpty()) return;

        $templateItems = RundownPress::whereIn('id', $latestIds)->get();
        $newItems = [];
        $now = now();
        $no  = 1;

        foreach ($templateItems as $item) {
            $stockAwal = (float) $item->stok_akhir;
            $pcsDay    = (float) $item->pcs_day;
            $strength  = $pcsDay > 0 ? round($stockAwal / $pcsDay, 4) : 0;

            if ($strength < 2)      $status = 'CRITICAL';
            elseif ($strength < 5)  $status = 'STANDAR';
            else                    $status = 'OVER';

            $newItems[] = [
                'sheet_date'  => $sheetDate,
                'no'          => $no++,
                'job_no'      => $item->job_no,
                'tipe'        => $item->tipe,
                'vendor'      => $item->vendor,
                'update_stock'=> $item->update_stock,
                'stock_awal'  => $stockAwal,
                'price'       => $item->price,
                'incoming'    => 0,
                'iami'        => 0,
                'spare_part'  => 0,
                'gkd'         => 0,
                'sap'         => 0,
                'kap'         => 0,
                'gmo'         => 0,
                'plan_day'    => 0,
                'plan_night'  => 0,
                'actual_prod' => 0,
                'stok_akhir'  => $stockAwal,
                'pcs_day'     => $pcsDay,
                'strength'    => $strength,
                'status'      => $status,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        foreach (array_chunk($newItems, 100) as $chunk) {
            RundownPress::insert($chunk);
        }
    }

    private function findPython(): ?string
    {
        // Try commands via proc_open (more reliable than shell_exec on Windows)
        $candidates = ['python', 'python3', 'py'];

        // Add common Windows absolute paths
        $appData = getenv('LOCALAPPDATA') ?: getenv('APPDATA');
        $userProfile = getenv('USERPROFILE') ?: getenv('HOME');
        $windowsPaths = [];

        foreach (['Python312', 'Python311', 'Python310', 'Python39', 'Python38'] as $ver) {
            $windowsPaths[] = 'C:\\Python' . substr($ver, 6) . '\\python.exe';
            if ($appData) $windowsPaths[] = $appData . '\\Programs\\Python\\' . $ver . '\\python.exe';
            if ($userProfile) $windowsPaths[] = $userProfile . '\\AppData\\Local\\Programs\\Python\\' . $ver . '\\python.exe';
        }

        foreach ($windowsPaths as $path) {
            $cleanPath = str_replace('\\', '\\', $path);
            if (file_exists($cleanPath)) {
                $candidates[] = $cleanPath;
            }
        }

        foreach ($candidates as $cmd) {
            $output = $this->runCommand([$cmd, '--version']);
            if ($output !== null && str_contains($output, 'Python 3')) {
                return $cmd;
            }
        }

        return null;
    }

    /**
     * Run a command using proc_open for cross-platform reliability
     * Returns combined stdout+stderr output, or null on failure
     */
    private function runCommand(array $args): ?string
    {
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open($args, $desc, $pipes);
        if (!is_resource($process)) return null;
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return ($out ?? '') . ($err ?? '');
    }

    /**
     * Run python script using proc_open
     */
    private function runPythonScript(string $python, string $scriptPath, string $filePath, string $originalName): ?string
    {
        $args = [$python, $scriptPath, $filePath, $originalName];
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open($args, $desc, $pipes);
        if (!is_resource($process)) return null;
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return ($out ?? '') . ($err ?? '');
    }
}