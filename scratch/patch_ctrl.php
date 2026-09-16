<?php
$f = 'app/Http/Controllers/Operational/InputHarianController.php';
$c = file_get_contents($f);

// Status method
$s1 = <<< 'PHP'
        $downtime = Downtime::select('id', 'jenis_downtime', 'start_time', 'problem', 'pic')
            ->where('job_master_id', $id)
PHP;
$r1 = <<< 'PHP'
        $downtime = Downtime::select('id', 'jenis_downtime', 'source', 'start_time', 'problem', 'pic')
            ->where('job_master_id', $id)
PHP;
$c = str_replace($s1, $r1, $c);

// apiLineData
$s2 = <<< 'PHP'
            'downtime' => $downtime ? [
                'id'             => $downtime->id,
                'jenis_downtime' => $downtime->jenis_downtime,
                'start_time'     => Carbon::parse($downtime->start_time)->toIso8601String(),
                'problem'        => $downtime->problem,
                'pic'            => $downtime->pic
            ] : null
PHP;
$r2 = <<< 'PHP'
            'downtime' => $downtime ? [
                'id'             => $downtime->id,
                'jenis_downtime' => $downtime->jenis_downtime,
                'source'         => $downtime->source,
                'start_time'     => Carbon::parse($downtime->start_time)->toIso8601String(),
                'problem'        => $downtime->problem,
                'pic'            => $downtime->pic
            ] : null
PHP;
$c = str_replace($s2, $r2, $c);

file_put_contents($f, $c);
echo "Patched InputHarianController queries\n";
