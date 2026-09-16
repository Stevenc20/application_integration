<?php
$f = 'app/Services/ProductionService.php';
$c = file_get_contents($f);
$s = <<< 'PHP'
            $downtime = Downtime::create([
                'job_master_id' => $jobId,
                'jenis_downtime' => $data['jenis_downtime'],
                'problem' => $data['problem'],
                'penyebab' => $data['penyebab'],
                'action' => $data['action'],
                'pic' => $data['pic'],
                'start_time' => $now
            ]);
PHP;
$r = <<< 'PHP'
            $downtime = Downtime::create([
                'job_master_id' => $jobId,
                'jenis_downtime' => $data['jenis_downtime'],
                'source' => $data['source'] ?? null,
                'problem' => $data['problem'],
                'penyebab' => $data['penyebab'],
                'action' => $data['action'],
                'pic' => $data['pic'],
                'start_time' => $now
            ]);
PHP;
$c = str_replace($s, $r, $c);
file_put_contents($f, $c);
echo "Patched ProductionService.php create downtime source\n";
