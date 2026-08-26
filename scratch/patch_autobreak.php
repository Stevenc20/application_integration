<?php
$f = 'app/Console/Commands/AutoBreakTime.php';
$c = file_get_contents($f);

// Change the activeBreak query
$s1 = <<< 'PHP'
        foreach ($runningJobs as $job) {
            $activeBreak = Downtime::where('job_master_id', $job->id)
                ->whereNull('finish_time')
                ->where('jenis_downtime', 'break time')
                ->first();
PHP;
$r1 = <<< 'PHP'
        foreach ($runningJobs as $job) {
            $activeBreak = Downtime::where('job_master_id', $job->id)
                ->whereNull('finish_time')
                ->where('jenis_downtime', 'break time')
                ->where('source', 'AUTO')
                ->first();
PHP;
$c = str_replace($s1, $r1, $c);

// Change downtime create
$s2 = <<< 'PHP'
                $downtime = Downtime::create([
                    'job_master_id' => $job->id,
                    'jenis_downtime' => 'break time',
                    'problem' => $matchedBreak->label ?? 'BREAK TIME',
                    'penyebab' => '-',
                    'action' => '-',
                    'pic' => 'AUTO BREAK',
                    'start_time' => $now,
                ]);
PHP;
$r2 = <<< 'PHP'
                $downtime = Downtime::create([
                    'job_master_id' => $job->id,
                    'jenis_downtime' => 'break time',
                    'source' => 'AUTO',
                    'problem' => $matchedBreak->label ?? 'BREAK TIME',
                    'penyebab' => '-',
                    'action' => '-',
                    'pic' => 'AUTO BREAK',
                    'start_time' => $now,
                ]);
PHP;
$c = str_replace($s2, $r2, $c);

// Remove the pic check since we rely on source
$s3 = <<< 'PHP'
            } elseif (!$inBreakWindow && $activeBreak && ($activeBreak->pic ?? '') === 'AUTO BREAK') {
PHP;
$r3 = <<< 'PHP'
            } elseif (!$inBreakWindow && $activeBreak && $activeBreak->source === 'AUTO') {
PHP;
$c = str_replace($s3, $r3, $c);

file_put_contents($f, $c);
echo "Patched AutoBreakTime.php\n";
