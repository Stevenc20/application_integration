<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RundownPress;

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
        $countMinim   = RundownPress::where('sheet_date', $selectedSheet)->whereIn('status', ['MINIM', 'CRITICAL'])->count();

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

            $scriptPath = base_path('read_rundown_press.py');

            if (!file_exists($scriptPath)) {
                @unlink($dataPath);
                return back()->with('error', 'Script read_rundown_press.py tidak ditemukan di root project.');
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

            DB::transaction(function () use ($result, $now, &$imported, &$firstSheetDate) {
                foreach ($result['sheets'] as $sheetName => $sheetData) {
                    if ($firstSheetDate === null) {
                        $firstSheetDate = $sheetName;
                    }

                    // Hapus data lama untuk tanggal yang sama agar tidak duplikat
                    RundownPress::where('sheet_date', $sheetName)->delete();

                    $rows = [];
                    foreach ($sheetData as $item) {
                        $rows[] = [
                            'sheet_date'   => $sheetName,
                            'no'           => $item['no'] ?? null,
                            'job_no'       => $item['job_no'] ?? null,
                            'tipe'         => $item['tipe'] ?? null,
                            'vendor'       => $item['vendor'] ?? null,
                            'update_stock' => $item['update_stock'] ?? null,
                            'stock_awal'   => $item['stock_awal'] ?? 0,
                            'price'        => $item['price'] ?? 0,
                            'incoming'     => $item['incoming'] ?? 0,
                            'iami'         => $item['iami'] ?? 0,
                            'spare_part'   => $item['spare_part'] ?? 0,
                            'gkd'          => $item['gkd'] ?? 0,
                            'sap'          => $item['sap'] ?? 0,
                            'kap'          => $item['kap'] ?? 0,
                            'gmo'          => $item['gmo'] ?? 0,
                            'plan_day'     => $item['plan_day'] ?? 0,
                            'plan_night'   => $item['plan_night'] ?? 0,
                            'actual_prod'  => $item['actual_prod'] ?? 0,
                            'stok_akhir'   => $item['stok_akhir'] ?? 0,
                            'pcs_day'      => $item['pcs_day'] ?? 0,
                            'strength'     => $item['strength'] ?? 0,
                            'status'       => $item['status'] ?? 'STANDAR',
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
            'value' => 'required|numeric',
        ]);

        $item  = RundownPress::findOrFail($request->id);
        $field = $request->field;
        $value = (float) $request->value;

        $item->$field = $value;

        // Recalculate
        $stockAwal = (float) $item->stock_awal;
        $incoming  = (float) $item->incoming;
        $iami      = (float) $item->iami;
        $sparePart = (float) $item->spare_part;
        $gkd       = (float) $item->gkd;
        $sap       = (float) $item->sap;
        $kap       = (float) $item->kap;
        $gmo       = (float) $item->gmo;
        $planDay   = (float) $item->plan_day;
        $planNight = (float) $item->plan_night;
        $actualProd= (float) $item->actual_prod;
        $pcsDay    = (float) $item->pcs_day;

        $totalKeluar = $iami + $sparePart + $gkd + $sap + $kap + $gmo;
        $stokAkhir   = $stockAwal + $incoming + $actualProd - $totalKeluar;
        $strength    = $pcsDay > 0 ? round($stokAkhir / $pcsDay, 4) : 0;

        if ($strength <= 0)     $status = 'CRITICAL';
        elseif ($strength < 1)  $status = 'MINIM';
        elseif ($strength < 2)  $status = 'LIMITED';
        elseif ($strength < 5)  $status = 'STANDAR';
        else                    $status = 'OVER';

        $item->stok_akhir = $stokAkhir;
        $item->strength   = $strength;
        $item->status     = $status;
        $item->save();

        // Cascade to subsequent dates
        $this->cascadeUpdates($item->job_no, $item->sheet_date, $item->stok_akhir, $pcsDay);

        return response()->json([
            'success'    => true,
            'stok_akhir' => $stokAkhir,
            'strength'   => $strength,
            'status'     => $status,
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

            $incoming   = (float) $next->incoming;
            $iami       = (float) $next->iami;
            $sparePart  = (float) $next->spare_part;
            $gkd        = (float) $next->gkd;
            $sap        = (float) $next->sap;
            $kap        = (float) $next->kap;
            $gmo        = (float) $next->gmo;
            $nextPlanDay   = (float) $next->plan_day;
            $nextPlanNight = (float) $next->plan_night;
            $nextActualProd= (float) $next->actual_prod;
            $nextPcsDay = (float) $next->pcs_day;

            $totalKeluar    = $iami + $sparePart + $gkd + $sap + $kap + $gmo;
            $nextStokAkhir  = $prevStokAkhir + $incoming + $nextActualProd - $totalKeluar;
            $nextStrength   = $nextPcsDay > 0 ? round($nextStokAkhir / $nextPcsDay, 4) : 0;

            if ($nextStrength <= 0)     $nextStatus = 'CRITICAL';
            elseif ($nextStrength < 1)  $nextStatus = 'MINIM';
            elseif ($nextStrength < 2)  $nextStatus = 'LIMITED';
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

            if ($strength <= 0)     $status = 'CRITICAL';
            elseif ($strength < 1)  $status = 'MINIM';
            elseif ($strength < 2)  $status = 'LIMITED';
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