<?php
$file = 'app/Http/Controllers/Supervisor/ReportController.php';
$content = file_get_contents($file);

// Fix GSPH plan
$content = str_replace(
    '$lines[$lineName][\'plan_gsph\'] += (float)($plan->gsph ?? $plan->target_gsph ?? 0);',
    '$lines[$lineName][\'plan_gsph\'] += (float)($plan->gsph_item ?? 0);',
    $content
);

// We need a monthly accumulation logic!
// Let's inject a new block before `// Build Section 2 Data`
// We will calculate accum based on the JobMasters and DailyProductions between $startOfMonth and $date

$accumLogic = <<< 'PHP'
        // --- START OF AUTO ACCUMULATION LOGIC ---
        $dateObj = \Carbon\Carbon::parse($date);
        $startOfMonth = $dateObj->copy()->startOfMonth()->toDateString();
        $endOfDate = $dateObj->toDateString();
        
        $accumData = [];
        // To be accurate with lines, we rely on the line filter or general aggregates
        // For simplicity since lines are 'A', 'B', 'C', 'D', we fetch daily productions in this month
        $monthlyPlans = \App\Models\ProductionPlan::whereBetween('plan_date', [$startOfMonth, $endOfDate])
            ->whereIn('row_type', ['job', 'break'])
            ->get();
            
        $monthlyJobNumbers = $monthlyPlans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();
        
        $monthlyJMs = \App\Models\JobMaster::whereIn('job_number', $monthlyJobNumbers)
            ->with(['dailyProduction' => function ($q) use ($startOfMonth, $endOfDate) {
                $q->whereBetween('work_date', [$startOfMonth, $endOfDate]);
            }])->get()->keyBy('job_number');
            
        foreach ($monthlyPlans as $mp) {
            $rawLine = trim($mp->press_name ?? $mp->line_master_id ?? 'UNASSIGNED');
            $lName = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $rawLine)));
            
            if (!isset($accumData[$lName])) {
                $accumData[$lName] = ['ok' => 0, 'repair' => 0, 'reject' => 0];
            }
            
            $jn = trim($mp->job_no ?? '');
            $jm = trim($mp->job_master ?? '');
            $mJobNumber = $jn ? ($jn . '-' . $mp->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $mp->id);
            
            $mjm = $monthlyJMs->get($mJobNumber);
            if ($mjm && $mjm->dailyProduction) {
                $accumData[$lName]['ok'] += (int) $mjm->dailyProduction->actual_ok;
                $accumData[$lName]['repair'] += (int) ($mjm->dailyProduction->actual_repair ?? $mjm->dailyProduction->repair_qty ?? 0);
                $accumData[$lName]['reject'] += (int) ($mjm->dailyProduction->actual_reject ?? $mjm->dailyProduction->reject_qty ?? 0);
            }
        }
        // --- END OF AUTO ACCUMULATION LOGIC ---

        // Build Section 2 Data
PHP;

$content = str_replace('// Build Section 2 Data', $accumLogic, $content);

$section2Logic = <<< 'PHP'
            // Target is generally fixed at 1.2% or 0.02% in Excel
            $actualRepPct = $lData['total_actual'] > 0 ? ($lData['total_repair'] / $lData['total_actual']) * 100 : 0;
            $actualRejPct = $lData['total_actual'] > 0 ? ($lData['total_reject'] / $lData['total_actual']) * 100 : 0;
            
            $accOk = $accumData[$lName]['ok'] ?? 0;
            $accRep = $accumData[$lName]['repair'] ?? 0;
            $accRej = $accumData[$lName]['reject'] ?? 0;
            
            $accumRepPct = $accOk > 0 ? ($accRep / $accOk) * 100 : 0;
            $accumRejPct = $accOk > 0 ? ($accRej / $accOk) * 100 : 0;
            $accumCost   = $accRej * 50000;
            
            $repairData[] = [
                'line_name' => $lName,
                'target' => 1.2,
                'actual' => $actualRepPct,
                'accum' => $accumRepPct,
                'issue' => $lData['total_repair'] > 0 ? $lData['total_repair'].' item(s) diperbaiki' : ''
            ];
            
            $gsphData[] = [
                'line_name' => $lName,
                'target' => 0,
                'plan' => $lData['plan_gsph'],
                'actual' => $lData['actual_gsph'],
                'diff' => $lData['actual_gsph'] - $lData['plan_gsph']
            ];
            
            $rejectData[] = [
                'line_name' => $lName,
                'target' => 0.02,
                'actual' => $actualRejPct,
                'cost' => $lData['total_reject'] * 50000, // Cost formula derived from qty * avg cost
                'accum' => $accumRejPct,
                'accum_cost' => $accumCost,
                'issue' => $lData['total_reject'] > 0 ? $lData['total_reject'].' item(s) reject' : ''
            ];
PHP;

$searchSection2 = <<< 'PHP'
            // Target is generally fixed at 1.2% or 0.02% in Excel
            $actualRepPct = $lData['total_actual'] > 0 ? ($lData['total_repair'] / $lData['total_actual']) * 100 : 0;
            $actualRejPct = $lData['total_actual'] > 0 ? ($lData['total_reject'] / $lData['total_actual']) * 100 : 0;
            
            $repairData[] = [
                'line_name' => $lName,
                'target' => 1.2,
                'actual' => $actualRepPct,
                'accum' => 0,
                'issue' => $lData['total_repair'] > 0 ? $lData['total_repair'].' item(s) diperbaiki' : ''
            ];
            
            $gsphData[] = [
                'line_name' => $lName,
                'target' => 0,
                'plan' => $lData['plan_gsph'],
                'actual' => $lData['actual_gsph'],
                'diff' => $lData['actual_gsph'] - $lData['plan_gsph']
            ];
            
            $rejectData[] = [
                'line_name' => $lName,
                'target' => 0.02,
                'actual' => $actualRejPct,
                'cost' => $lData['total_reject'] * 50000, // Cost formula derived from qty * avg cost
                'accum' => 0,
                'accum_cost' => 0,
                'issue' => $lData['total_reject'] > 0 ? $lData['total_reject'].' item(s) reject' : ''
            ];
PHP;

$content = str_replace($searchSection2, $section2Logic, $content);
file_put_contents($file, $content);
echo "Done replacing.";

