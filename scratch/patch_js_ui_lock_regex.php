<?php
$f = 'resources/js/operational/production-engine.js';
$c = file_get_contents($f);

$c = preg_replace(
    "/jenis_downtime:\s*'break time',\s*problem:\s*breakInfo\.label\s*\|\|\s*'AUTO BREAK',\s*penyebab:\s*'-',\s*action:\s*'-',\s*pic:\s*'AUTO BREAK'/s",
    "jenis_downtime: 'break time',\n                source: 'AUTO',\n                problem: breakInfo.label || 'AUTO BREAK',\n                penyebab: '-',\n                action: '-',\n                pic: 'AUTO BREAK'",
    $c
);

$c = preg_replace(
    "/if\s*\(\!serverDown\s*&&\s*clientBreak\s*&&\s*window\._autoBreakActive\)\s*\{\s*\/\/\s*Keep\s*the\s*finished\s*break\s*visible.*?showToast\('Break\s*time\s*selesai,\s*produksi\s*dilanjutkan\.',\s*'success'\);\s*\}\s*else\s*if\s*\(\!serverDown\s*&&\s*\!clientBreak\s*&&\s*window\._autoBreakActive\)\s*\{.*?updateTimeline\(\);\s*\}/s",
    <<< 'JS'
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
        updateTimeline();
JS,
    $c
);

$c = preg_replace(
    "/if\s*\(\!serverDown\s*&&\s*clientBreak\s*&&\s*window\._autoBreakActive\)/s",
    "// THIS SHOULD BE GONE",
    $c
);


file_put_contents($f, $c);
echo "Patched JS 1\n";
