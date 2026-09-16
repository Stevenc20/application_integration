<?php
$f = 'app/Services/ProductionService.php';
$c = file_get_contents($f);
$c = str_replace("\r\n", "\n", $c);

// 1. In startDandori, set started_at if null
$s1 = <<< 'PHP'
            $workDate = $workDate ?: now()->toDateString();
            $job = JobMaster::findOrFail($jobId);
            $job->update(['status' => 'running']);
            $this->syncPlanStatus($jobId, 'running');
PHP;
$r1 = <<< 'PHP'
            $workDate = $workDate ?: now()->toDateString();
            $job = JobMaster::findOrFail($jobId);
            $updateData = ['status' => 'running'];
            if (!$job->started_at) {
                $updateData['started_at'] = now();
            }
            $job->update($updateData);
            $this->syncPlanStatus($jobId, 'running');
PHP;
$c = str_replace($s1, $r1, $c);

// 2. In finishDandori, DO NOT overwrite started_at
$s2 = <<< 'PHP'
            if (!$otherActiveDowntime) {
                JobMaster::where('id', $jobId)->update(['started_at' => $now, 'status' => 'running']);
                $this->syncPlanStatus($jobId, 'running');

                $session = ProductionSession::firstOrCreate(
                    ['job_master_id' => $jobId, 'work_date' => now()->toDateString()]
                );
                $session->update(['start_time' => $now, 'status' => 'running']);
            } else {
                JobMaster::where('id', $jobId)->update(['started_at' => $now, 'status' => 'paused']);
                $this->syncPlanStatus($jobId, 'paused');

                $session = ProductionSession::firstOrCreate(
                    ['job_master_id' => $jobId, 'work_date' => now()->toDateString()]
                );
                $session->update(['start_time' => clone $now, 'status' => 'paused', 'pause_time' => clone $now]);
            }
PHP;
$r2 = <<< 'PHP'
            if (!$otherActiveDowntime) {
                JobMaster::where('id', $jobId)->update(['status' => 'running']);
                $this->syncPlanStatus($jobId, 'running');

                $session = ProductionSession::firstOrCreate(
                    ['job_master_id' => $jobId, 'work_date' => now()->toDateString()]
                );
                $session->update(['start_time' => $now, 'status' => 'running']);
            } else {
                JobMaster::where('id', $jobId)->update(['status' => 'paused']);
                $this->syncPlanStatus($jobId, 'paused');

                $session = ProductionSession::firstOrCreate(
                    ['job_master_id' => $jobId, 'work_date' => now()->toDateString()]
                );
                $session->update(['start_time' => clone $now, 'status' => 'paused', 'pause_time' => clone $now]);
            }
PHP;
$c = str_replace($s2, $r2, $c);

file_put_contents($f, $c);
echo "Patched ProductionService.php\n";
