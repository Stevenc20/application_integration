<?php
$f = 'resources/js/operational/production-engine.js';
$c = file_get_contents($f);
$c = str_replace("\r\n", "\n", $c); // Normalize to LF

$s1 = "                jenis_downtime: 'break time',\n                problem: breakInfo.label || 'AUTO BREAK',\n                penyebab: '-',\n                action: '-',\n                pic: 'AUTO BREAK'";
$r1 = "                jenis_downtime: 'break time',\n                source: 'AUTO',\n                problem: breakInfo.label || 'AUTO BREAK',\n                penyebab: '-',\n                action: '-',\n                pic: 'AUTO BREAK'";
$c = str_replace($s1, $r1, $c);

$s2 = <<< 'JS'
        if (serverDown && serverDown.jenis_downtime === 'break time' && !clientBreak && !window._autoBreakActive) {
            if (serverBreakIsAuto) {
                window._autoBreakActive = true;
                window._autoBreakDowntimeId = serverDown.id;
                const serverBreakWindow = _isInBreakWindow(new Date());
                window._autoBreakEndMin = serverBreakWindow ? serverBreakWindow.endMin : null;
            }
            window.runningDowntimes[`${id}_break`] = {
                id: serverDown.id,
                start: new Date(serverDown.start_time),
                jobId: id,
                btnType: 'break',
                dtType: 'break time',
                source: serverDown.source || '',
                problem: serverBreakIsAuto ? 'AUTO BREAK' : (serverDown.problem || '')
            };
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
            if (job && !job._breakPaused) {
                let currentSeconds = job.base_seconds || 0;
                const anchorTime = job.dandori_start ? new Date(job.dandori_start) : (job.started_at ? new Date(job.started_at) : null);
                if (anchorTime) currentSeconds += Math.floor((Date.now() - anchorTime.getTime()) / 1000);
                job._frozenTimer = currentSeconds;
                job._breakPaused = true;
            }
            updateTimeline();
            _updateBreakUI(id, serverBreakIsAuto ? 'BREAK TIME' : 'BREAK', true);
        }
JS;
$r2 = <<< 'JS'
        if (serverDown && serverDown.jenis_downtime === 'break time' && !clientBreak && !window._autoBreakActive) {
            if (serverBreakIsAuto) {
                window._autoBreakActive = true;
                window._autoBreakDowntimeId = serverDown.id;
                const serverBreakWindow = _isInBreakWindow(new Date());
                window._autoBreakEndMin = serverBreakWindow ? serverBreakWindow.endMin : null;
            }
            window.runningDowntimes[`${id}_break`] = {
                id: serverDown.id,
                start: new Date(serverDown.start_time),
                jobId: id,
                btnType: 'break',
                dtType: 'break time',
                source: serverDown.source || '',
                problem: serverBreakIsAuto ? 'AUTO BREAK' : (serverDown.problem || '')
            };
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
            if (job && !job._breakPaused) {
                let currentSeconds = job.base_seconds || 0;
                const anchorTime = job.dandori_start ? new Date(job.dandori_start) : (job.started_at ? new Date(job.started_at) : null);
                if (anchorTime) currentSeconds += Math.floor((Date.now() - anchorTime.getTime()) / 1000);
                job._frozenTimer = currentSeconds;
                job._breakPaused = true;
            }
            updateTimeline();
            if (serverBreakIsAuto) {
                _updateBreakUI(id, 'BREAK TIME', true);
            } else {
                _updateBreakUI(id, null, false);
            }
        }
JS;
$c = str_replace($s2, $r2, $c);

$s3 = <<< 'JS'
        } else if (!serverDown && clientBreak && window._autoBreakActive) {
            // Keep the finished break visible on the timeline before removing it
            if (!window.jobDowntimeHistory[id]) window.jobDowntimeHistory[id] = [];
            if (!window.jobDowntimeHistory[id].some(h => h.id != null && String(h.id) === String(clientBreak.id))) {
                window.jobDowntimeHistory[id].push({
                    start: clientBreak.start.getTime(),
                    end: Date.now(),
                    type: clientBreak.dtType || 'break time',
                    id: clientBreak.id,
                    problem: clientBreak.problem || '',
                    source: clientBreak.source || ''
                });
            }
            delete window.runningDowntimes[`${id}_break`];
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
            if (job) {
                if (job._breakPaused && job._frozenTimer != null) {
                    job.base_seconds = job._frozenTimer;
                    job.started_at = new Date().toISOString();
                }
                delete job._breakPaused;
                delete job._frozenTimer;
                job.status = 'running';
                window.ProductionConfig.currentStatus = 'running';
                try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}
            }
            window._autoBreakActive = false;
            window._autoBreakDowntimeId = null;
            window._autoBreakSkipped = false;
            window._autoBreakEndMin = null;
            updateTimeline();
            _updateBreakUI(id, null, false);
            showToast('Break time selesai, produksi dilanjutkan.', 'success');
        } else if (!serverDown && !clientBreak && window._autoBreakActive) {
            window._autoBreakActive = false;
            window._autoBreakDowntimeId = null;
            window._autoBreakSkipped = false;
            window._autoBreakEndMin = null;
            if (job) {
                job.status = 'running';
                window.ProductionConfig.currentStatus = 'running';
            }
            _updateBreakUI(id, null, false);
        }
JS;
$r3 = <<< 'JS'
        } else if (!serverDown && clientBreak && clientBreak.dtType === 'break time') {
            // Break finished! Remove it regardless of window._autoBreakActive
            if (!window.jobDowntimeHistory[id]) window.jobDowntimeHistory[id] = [];
            if (!window.jobDowntimeHistory[id].some(h => h.id != null && String(h.id) === String(clientBreak.id))) {
                window.jobDowntimeHistory[id].push({
                    start: clientBreak.start.getTime(),
                    end: Date.now(),
                    type: clientBreak.dtType || 'break time',
                    id: clientBreak.id,
                    problem: clientBreak.problem || '',
                    source: clientBreak.source || ''
                });
            }
            delete window.runningDowntimes[`${id}_break`];
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
            if (job) {
                if (job._breakPaused && job._frozenTimer != null) {
                    job.base_seconds = job._frozenTimer;
                    job.started_at = new Date().toISOString();
                }
                delete job._breakPaused;
                delete job._frozenTimer;
                job.status = 'running';
                window.ProductionConfig.currentStatus = 'running';
                try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}
            }
            const wasAutoBreak = window._autoBreakActive || (clientBreak.source === 'AUTO');
            window._autoBreakActive = false;
            window._autoBreakDowntimeId = null;
            window._autoBreakSkipped = false;
            window._autoBreakEndMin = null;
            updateTimeline();
            _updateBreakUI(id, null, false);
            if (wasAutoBreak) {
                showToast('Break time selesai, produksi dilanjutkan.', 'success');
            }
        }
JS;
$c = str_replace($s3, $r3, $c);

$s4 = <<< 'JS'
                window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
                _updateBreakUI(activeId, isAutoBreak ? 'BREAK TIME' : 'BREAK', true);
            }
            break;
JS;
$r4 = <<< 'JS'
                window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
                if (isAutoBreak) {
                    _updateBreakUI(activeId, 'BREAK TIME', true);
                } else {
                    _updateBreakUI(activeId, null, false);
                }
            }
            break;
JS;
$c = str_replace($s4, $r4, $c);

$s5 = <<< 'JS'
                    window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
                    _updateBreakUI(saved.jobId, saved.label, true);
                }
JS;
$r5 = <<< 'JS'
                    window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
                    if (isAutoBreak) {
                        _updateBreakUI(saved.jobId, saved.label, true);
                    } else {
                        _updateBreakUI(saved.jobId, null, false);
                    }
                }
JS;
$c = str_replace($s5, $r5, $c);

file_put_contents($f, $c);
echo "Patched JS properly\n";
