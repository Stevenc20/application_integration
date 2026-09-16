<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ProductionSchedule;
use App\Models\ItemCheck;
use Carbon\Carbon;

class ItemCheckController extends Controller
{
    public function index(Request $request)
    {
        // For development/mocking, if there are no schedules for today, we can seed a dummy one
        $filterDate = $request->query('date');
        $targetDate = $filterDate ? Carbon::parse($filterDate) : Carbon::today();
        
        // Lakukan sinkronisasi jadwal Produksi riil (dari db_integration) otomatis jika melihat hari ini
        if ($targetDate->isToday()) {
            \Illuminate\Support\Facades\Artisan::call('qa:sync-schedule');
        }

        $schedulesQuery = ProductionSchedule::with(['itemChecks'])
                        ->whereDate('tanggal_produksi', $targetDate)
                        ->orderByRaw('ISNULL(row_no), row_no ASC');

        // Ambil item check yang: Belum selesai (in_progress, waiting_*) ATAU Selesai tapi khusus hari ini
        $itemChecksQuery = ItemCheck::with(['masterTemplate', 'schedule', 'operator'])
                        ->where(function ($query) use ($targetDate) {
                            $query->whereNotIn('status', ['finished', 'approved', 'locked'])
                                  ->orWhereDate('created_at', $targetDate);
                        })
                        ->latest();

        $user = auth()->user();
        
        // Filter by assigned line for Operators
        if ($user && $user->role === 'Operator') {
            if (!empty($user->assigned_line) && $user->assigned_line !== 'Semua Line') {
                $schedulesQuery->where('line', 'like', '%' . $user->assigned_line . '%');
                
                // For item checks, filter by their own or their line
                $itemChecksQuery->where(function($q) use ($user) {
                    $q->where('operator_id', $user->id)
                      ->orWhereHas('schedule', function($q2) use ($user) {
                          $q2->where('line', 'like', '%' . $user->assigned_line . '%');
                      });
                });
            }
        }

        $schedules = $schedulesQuery->get();
        $itemChecks = $itemChecksQuery->get();

        // Attach master template id so we can link to it
        foreach ($schedules as $schedule) {
            $templates = $this->findTemplatesForSchedule($schedule);

            $schedule->master_template_id = count($templates) > 0 ? $templates[0]->id : null;
            $schedule->masterTemplate = count($templates) > 0 ? $templates[0] : null;
        }

        // Hitung statistik inspeksi QA yang SEBENARNYA (bukan dari mesin/produksi)
        // Ini membaca isi kolom yang sudah diisi operator dari hasil_visual & hasil_dimensi
        foreach ($itemChecks as $ic) {
            // 1. Hitung sampel yang wajib diperiksa (required samples) dan tentukan validCols
            $totalPcs = $ic->total_produksi > 0 ? $ic->total_produksi : ($ic->schedule && $ic->schedule->actual_qty > 0 ? $ic->schedule->actual_qty : ($ic->schedule && $ic->schedule->target_qty > 0 ? $ic->schedule->target_qty : 0));
            $required = 0;
            $validCols = [];
            
            if ($ic->masterTemplate && is_array($ic->masterTemplate->sampling_cols)) {
                $cols = $ic->masterTemplate->sampling_cols;
                
                if ($totalPcs > 0) {
                    $baseCols = array_filter($cols, function($c) use ($totalPcs) {
                        return $c <= $totalPcs;
                    });
                    if (empty($baseCols) || end($baseCols) != $totalPcs) {
                        $baseCols[] = (int)$totalPcs;
                    }
                    $validCols = array_unique($baseCols);
                    $required = count($validCols);
                } else {
                    $validCols = array_unique($cols);
                    $required = count($validCols);
                }
            } else if ($ic->masterTemplate && $ic->masterTemplate->max_sample > 0) {
                $required = $totalPcs > 0 ? min($totalPcs, $ic->masterTemplate->max_sample) : $ic->masterTemplate->max_sample;
                for($i = 1; $i <= $required; $i++) $validCols[] = $i;
            }
            $ic->required_samples = $required;
            
            // Konversi validCols menjadi array string untuk pencocokan yang aman
            $validColsStr = array_map('strval', $validCols);

            // 2. Hitung statistik inspeksi QA (hanya dari kolom yang valid)
            $samples = [];
            $processData = function($data, $isVisual) use (&$samples, $validColsStr) {
                if (!is_array($data)) return;
                foreach ($data as $key => $val) {
                    if (preg_match('/_(\d+)$/', $key, $matches)) {
                        $col = $matches[1];
                        // Abaikan kolom yang tidak masuk dalam validCols (misal kolom lama/ghost)
                        if (!empty($validColsStr) && !in_array((string)$col, $validColsStr)) {
                            continue;
                        }
                        
                        $strVal = strtolower(trim((string)$val));
                        if ($strVal === '') continue;

                        if (!isset($samples[$col])) {
                            $samples[$col] = ['is_ng' => false, 'has_judgement' => false];
                        }
                        
                        if ($isVisual) {
                            if (in_array($strVal, ['ok', 'ng'])) {
                                $samples[$col]['has_judgement'] = true;
                            }
                            if ($strVal === 'ng') {
                                $samples[$col]['is_ng'] = true;
                            }
                        }
                    }
                }
            };
            $processData($ic->hasil_visual, true);
            $processData($ic->hasil_dimensi, false);

            $validSamples = collect($samples)->where('has_judgement', true);
            $ic->qa_checked = $validSamples->count();
            $ic->qa_ng      = $validSamples->where('is_ng', true)->count();
            $ic->qa_ok      = $ic->qa_checked - $ic->qa_ng;
        }

        return view('item-check.index', compact('schedules', 'itemChecks'));
    }

    public function start(Request $request, $scheduleId)
    {
        $schedule = ProductionSchedule::findOrFail($scheduleId);
        
        // Prevent operators from cheating by doing yesterday's schedule today
        if ($schedule->tanggal_produksi && !Carbon::parse($schedule->tanggal_produksi)->isToday()) {
            return redirect()->back()->with('error', 'Gagal Memulai: Anda tidak dapat memulai inspeksi untuk jadwal yang bukan hari ini. Jika jadwal ini terlewat, PPC akan membuat jadwal baru.');
        }

        $templates = $this->findTemplatesForSchedule($schedule);

        if (count($templates) === 0) {
            return redirect()->back()->with('error', 'Gagal Memulai: Master Template (Standar Inspeksi) untuk Part "' . ($schedule->part_name ?? $schedule->job_no) . '" belum dibuat oleh Admin di menu Master LI.');
        }

        // Validate that Sampling Formula has been filled (Must have BOTH sampling target AND timer target)
        $invalidTemplates = collect($templates)->filter(function($t) {
            $hasSamplingTarget = !empty($t->sampling_cols) || $t->max_sample > 0;
            $hasTimerTarget = $t->tact_time > 0 && $t->ct_dimensi > 0;
            $hasFormula = $hasSamplingTarget && $hasTimerTarget;
            return !$hasFormula;
        });

        if ($invalidTemplates->count() > 0) {
            return redirect()->back()->with('error', 'Gagal Memulai: Sampling Formula (Target / Waktu CT) untuk Part "' . ($schedule->part_name ?? $schedule->job_no) . '" belum diisi di Master Template. Harap hubungi Admin/Leader untuk melengkapi formula agar timer bisa berjalan.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($schedule, $templates) {
            // Cek apakah jadwal ini sudah diambil
            $existingChecks = ItemCheck::with('operator')->where('production_schedule_id', $schedule->id)->lockForUpdate()->get();

            if ($existingChecks->count() > 0) {
                $existingCheck = $existingChecks->first();
                // Jika sudah diambil dan milik sendiri, dan belum di-approve
                if ($existingCheck->operator_id === auth()->id() && in_array($existingCheck->status, ['in_progress', 'waiting_gl', 'waiting_foreman'])) {
                    return redirect()->route('item-check.form', $existingCheck->id);
                }
                
                // Jika milik orang lain atau sudah selesai
                $operatorName = $existingCheck->operator ? $existingCheck->operator->name : 'operator lain';
                return redirect()->back()->with('error', 'Gagal Memulai: Tugas inspeksi ini sudah diambil oleh ' . $operatorName . ' atau sudah diselesaikan.');
            }

            // Jika tidak ada, buat form baru (bisa 1 atau 2 form jika tandem)
            $firstCheckId = null;
            foreach ($templates as $template) {
                $newCheck = ItemCheck::create([
                    'production_schedule_id' => $schedule->id,
                    'lembar_inspeksi_id' => $template->id,
                    'operator_id' => auth()->id(),
                    'tanggal' => $schedule->tanggal_produksi ? Carbon::parse($schedule->tanggal_produksi) : Carbon::today(),
                    'waktu_mulai' => Carbon::now(),
                    'shift' => (stripos($schedule->shift_name ?? '', 'malam') !== false) ? '2' : '1', // Automatically match PPC shift
                    'status' => 'in_progress',
                    'hasil_dimensi' => [],
                    'hasil_visual' => []
                ]);
                if (!$firstCheckId) $firstCheckId = $newCheck->id;
            }

            return redirect()->route('item-check.form', $firstCheckId);
        });
    }

    public function form($id)
    {
        $itemCheck = ItemCheck::with(['masterTemplate', 'schedule', 'operator'])->findOrFail($id);
        
        $tandemCheck = null;
        if ($itemCheck->schedule) {
            $tandemCheck = ItemCheck::with('masterTemplate')
                ->where('production_schedule_id', $itemCheck->schedule->id)
                ->where('id', '!=', $itemCheck->id)
                ->where('operator_id', $itemCheck->operator_id)
                ->whereDate('tanggal', $itemCheck->tanggal)
                ->first();
        }
        
        return view('item-check.form', compact('itemCheck', 'tandemCheck'));
    }

    public function preview(Request $request, $templateId)
    {
        $template = \App\Models\LembarInspeksi::findOrFail($templateId);
        $actualQty = (int) $request->query('actual_qty', 0);
        $scheduleId = $request->query('schedule_id', null);
        
        // Render item-check form without an ItemCheck record (Preview Mode)
        return view('item-check.form', [
            'itemCheck' => null,
            'template' => $template,
            'actualQty' => $actualQty,
            'scheduleId' => $scheduleId
        ]);
    }

    public function print($id)
    {
        $itemCheck = ItemCheck::with(['masterTemplate', 'schedule', 'operator', 'assignedGl', 'assignedForeman'])->findOrFail($id);
        
        return view('item-check.print', compact('itemCheck'));
    }

    private function findTemplatesForSchedule($schedule)
    {
        if (!$schedule) return [];

        $job_no = trim($schedule->job_no ?? '');
        $part_no = trim($schedule->part_no ?? '');
        
        $templates = collect();

        $findOne = function($searchStr) {
            if (empty($searchStr)) return null;
            $searchStr = trim($searchStr);
            
            $variations = [$searchStr];
            if (strpos($searchStr, '-') === false) {
                if (preg_match('/^([a-zA-Z]+)(\d+.*)$/', $searchStr, $m)) {
                    $variations[] = $m[1] . '-' . $m[2];
                }
            } else {
                $variations[] = str_replace('-', '', $searchStr);
            }

            foreach ($variations as $var) {
                $t = \App\Models\LembarInspeksi::where('job_no', $var)->first();
                if ($t) return $t;
                
                $t = \App\Models\LembarInspeksi::where('job_no', 'like', '%' . $var . '%')->first();
                if ($t) return $t;
                
                $t = \App\Models\LembarInspeksi::where('part_no', $var)
                                               ->orWhere('part_name', $var)
                                               ->orWhere('job_no', $var)
                                               ->orWhere('job_no', 'like', '%' . $var . '%')
                                               ->orWhere('part_no', 'like', '%' . $var . '%')
                                               ->first();
                if ($t) return $t;

                if (stripos($var, ' WIP') !== false) {
                    $clean = trim(str_ireplace(' WIP', '', $var));
                    if (!empty($clean)) {
                        $t = \App\Models\LembarInspeksi::where('job_no', $clean)
                                                       ->orWhere('part_no', $clean)
                                                       ->orWhere('part_name', $clean)
                                                       ->orWhere('job_no', 'like', '%' . $clean . '%')
                                                       ->first();
                        if ($t) return $t;
                    }
                }
            }
            return null;
        };

        // Check for Tandem parts using slash ('/')
        $slashStr = strpos($job_no, '/') !== false ? $job_no : (strpos($part_no, '/') !== false ? $part_no : null);
        if ($slashStr) {
            $suffixes = [' FNS', ' WIP', ' FINISH', ' BLK', ' GAL'];
            $foundSuffix = '';
            
            // Ekstrak suffix jika ada, lalu hilangkan dari slashStr untuk memecah angkanya dengan akurat
            foreach ($suffixes as $suf) {
                if (stripos($slashStr, $suf) !== false) {
                    $foundSuffix = $suf;
                    $slashStr = str_ireplace($suf, '', $slashStr);
                    break;
                }
            }
            
            $cleanSlashStr = trim($slashStr);
            $parts = explode('/', $cleanSlashStr);
            if (count($parts) === 2) {
                $p1 = trim($parts[0]);
                $p2 = trim($parts[1]);
                $len2 = strlen($p2);
                
                // Jika p1 cukup panjang, potong karakter di belakangnya sesuai panjang p2
                if (strlen($p1) > $len2) {
                    $p2_full = substr($p1, 0, -$len2) . $p2;
                } else {
                    $p2_full = $p2;
                }
                
                // Gabungkan kembali dengan suffix aslinya
                $final_p1 = $p1 . $foundSuffix;
                $final_p2 = $p2_full . $foundSuffix;
                
                $t1 = $findOne($final_p1);
                $t2 = $findOne($final_p2);
                
                if ($t1) $templates->push($t1);
                if ($t2) $templates->push($t2);
                
                if ($templates->count() > 0) return $templates->unique('id')->values()->all();
            }
        }
        
        $t = $findOne($job_no) ?? $findOne($part_no);
        if ($t) {
            return collect([$t])->all();
        }

        return [];
    }

}
