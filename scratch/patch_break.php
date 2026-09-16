<?php

function patchFile($file, $search, $replace, $label = '') {
    $content = file_get_contents($file);
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        file_put_contents($file, $content);
        echo "Patched: $file ($label)\n";
    } else {
        echo "NOT FOUND: $file ($label)\n";
    }
}

// 1. AutoBreakTime.php (Fix pause/resume + total_seconds calculation)
// In AutoBreakTime, it currently does manual session updates.
$autoBreak = 'app/Console/Commands/AutoBreakTime.php';
$abContent = file_get_contents($autoBreak);

// Replace pause logic
$searchPause = <<< 'PHP'
                $session = ProductionSession::where('job_master_id', $job->id)
                    ->whereDate('work_date', $workDate)
                    ->where('status', 'running')
                    ->first();
                if ($session) {
                    $session->update(['status' => 'paused', 'pause_time' => $now]);
                }

                if ($job->status === 'running') {
                    JobMaster::where('id', $job->id)->update(['status' => 'paused']);
                }
PHP;
$replacePause = <<< 'PHP'
                // FIXED: Use production service to properly calculate total_seconds
                app(\App\Services\ProductionService::class)->pauseJob($job->id);
PHP;

if (strpos($abContent, $searchPause) !== false) {
    $abContent = str_replace($searchPause, $replacePause, $abContent);
} else {
    echo "AutoBreakTime Pause NOT FOUND\n";
}

// Replace resume logic
$searchResume = <<< 'PHP'
                $otherActiveDowntime = Downtime::where('job_master_id', $job->id)
                    ->whereNull('finish_time')
                    ->exists();

                if (!$otherActiveDowntime) {
                    $session = ProductionSession::where('job_master_id', $job->id)
                        ->whereDate('work_date', $workDate)
                        ->where('status', 'paused')
                        ->first();
                    if ($session) {
                        $session->update(['status' => 'running']);
                    }

                    JobMaster::where('id', $job->id)->update(['status' => 'running']);
                }
PHP;
$replaceResume = <<< 'PHP'
                $otherActiveDowntime = Downtime::where('job_master_id', $job->id)
                    ->whereNull('finish_time')
                    ->exists();

                if (!$otherActiveDowntime) {
                    // FIXED: Use production service to properly reset start_time for timeline segmenting
                    app(\App\Services\ProductionService::class)->resumeJob($job->id);
                }
PHP;

if (strpos($abContent, $searchResume) !== false) {
    $abContent = str_replace($searchResume, $replaceResume, $abContent);
} else {
    echo "AutoBreakTime Resume NOT FOUND\n";
}
file_put_contents($autoBreak, $abContent);
echo "Patched: $autoBreak\n";


// 2. InputHarianController.php (Protect Dandori from manual break too)
$ctrl = 'app/Http/Controllers/Operational/InputHarianController.php';

patchFile($ctrl, "->where('jenis_downtime', 'break time')", "->whereIn('jenis_downtime', ['break time', 'manual break'])", "Dandori 1");
patchFile($ctrl, "->where('jenis_downtime', 'break time')", "->whereIn('jenis_downtime', ['break time', 'manual break'])", "Dandori 2");


// 3. input_harian.blade.php (Blade server side render of button active states)
$blade = 'resources/views/operational/input_harian.blade.php';
patchFile($blade, "elseif(\$dtTypeLower == 'break time') \$btnType = 'break';", "elseif(in_array(\$dtTypeLower, ['break time', 'manual break'])) \$btnType = 'break';", "Blade btnType");


// 4. production-engine.js (Frontend UI mapping & API payload)
$js = 'resources/js/operational/production-engine.js';
patchFile($js, "const typeMap = { 'break time': 'break', 'try out': 'tryout', 'dandori': 'dandori' };", "const typeMap = { 'break time': 'break', 'manual break': 'break', 'try out': 'tryout', 'dandori': 'dandori' };", "JS typeMap");

// Now we need to modify startQuickDowntime in JS to send 'manual break' if dtType is 'break time' (from UI data-type)
$searchStart = <<< 'JS'
async function startQuickDowntime(jobId, btnType, dtType) {
    await window.ActionRunner.run('Start ' + btnType, async () => {
        const res = await fetch(`/operational/job/${jobId}/downtime/start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                jenis_downtime: dtType,
JS;
$replaceStart = <<< 'JS'
async function startQuickDowntime(jobId, btnType, dtType) {
    await window.ActionRunner.run('Start ' + btnType, async () => {
        // OVERRIDE: Separate manual break from automatic break time
        let payloadType = dtType;
        if (dtType === 'break time') {
            payloadType = 'manual break';
        }

        const res = await fetch(`/operational/job/${jobId}/downtime/start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                jenis_downtime: payloadType,
JS;
patchFile($js, $searchStart, $replaceStart, "JS startQuickDowntime");

// Also check _checkAutoBreak logic. If serverDown is 'break time', it is clientBreak (Auto). If it is 'manual break', it is not!
// In _updateBreakUI or similar?
$searchClientBreak = "const clientBreak = serverDown && serverDownType === 'break time';";
$replaceClientBreak = "const clientBreak = serverDown && serverDownType === 'break time' && window.runningDowntimes[`\${id}_break`]?.dtType !== 'manual break';";
patchFile($js, $searchClientBreak, $replaceClientBreak, "JS clientBreak detector");

// Wait, if manual break is active, `serverDownType` will be `manual break`, so `serverDownType === 'break time'` will be false.
// Therefore `clientBreak` will naturally be false for manual break! That's perfect. No need to change `clientBreak` definition if I just let it be false.
// Let me just revert that JS clientBreak patch in my mind, wait, I haven't run it yet.

