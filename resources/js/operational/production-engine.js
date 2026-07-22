/**
 * Production Engine JS
 * Extracted from input_harian.blade.php
 * Fully restored and syntax-corrected.
 */

// Suppress Chrome extension noise and third-party asynchronous connection/message channel errors
window.addEventListener('unhandledrejection', function (event) {
    const msg = event.reason && (event.reason.message || event.reason.toString());
    if (msg && (msg.includes('message channel closed') || msg.includes('asynchronous response') || msg.includes('extension'))) {
        event.preventDefault();
        event.stopPropagation();
    }
});

window.addEventListener('error', function (event) {
    const msg = event.message || '';
    if (msg && (msg.includes('message channel closed') || msg.includes('asynchronous response') || msg.includes('extension'))) {
        event.preventDefault();
        event.stopPropagation();
        return true; // suppress in console
    }
});

/**
 * ActionRunner — Robust async action lock with safety timeout.
 * Guarantees the lock is always released via try/finally.
 * Provides visual feedback and auto-healing.
 */
window.ActionRunner = {
    _locked: false,
    _timer: null,
    _lockTime: null,

    get locked() { return this._locked; },

    async run(label, fn) {
        if (this._locked) {
            showToast('Proses sebelumnya masih berjalan, harap tunggu...', 'warning');
            return false;
        }
        this._locked = true;
        this._lockTime = Date.now();

        // Safety timeout — auto-release after 30 seconds
        this._timer = setTimeout(() => {
            if (this._locked) {
                this._release('ActionRunner: safety timeout — lock auto-released');
            }
        }, 30000);

        try {
            await fn();
        } catch (e) {
            console.error(`ActionRunner error [${label}]:`, e);
            showToast('Terjadi kesalahan, silakan coba lagi', 'danger');
        } finally {
            this._release();
        }
        return true;
    },

    _release(reason) {
        clearTimeout(this._timer);
        this._locked = false;
        this._lockTime = null;
        if (reason) console.warn(reason);
    }
};

// Heartbeat — detect stuck lock and auto-release
setInterval(() => {
    if (window.ActionRunner._locked && window.ActionRunner._lockTime) {
        const elapsed = Date.now() - window.ActionRunner._lockTime;
        if (elapsed > 30000) {
            window.ActionRunner._release('Heartbeat: stuck lock auto-released after ' + elapsed + 'ms');
        }
    }
}, 10000);

// Unhandled rejection — force release lock
window.addEventListener('unhandledrejection', function (event) {
    if (window.ActionRunner) {
        window.ActionRunner._release('Unhandled rejection — lock released');
    }
});

function notifyLineStatusChange(line = null) {
    try {
        const channel = new BroadcastChannel('line_status');
        channel.postMessage({
            type: 'status-changed',
            timestamp: Date.now(),
            line: line || window.ProductionConfig?.currentLine || null
        });
        channel.close();
    } catch (e) {
        // BroadcastChannel not supported — fallback to polling
    }
}

function formatSeconds(sec) {
    let h = String(Math.floor(sec / 3600)).padStart(2, '0');
    let m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
    let s = String(sec % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

function isJobComplete(job) {
    if (!job || !job.status) return false;
    const s = String(job.status).toLowerCase();
    return s === 'complete' || s === 'finished';
}

function updateTimers() {
    try {
        if (window.freezeTimers) return;
        const now = new Date();
        const runningDowntimes = window.runningDowntimes || {};

        // Update running downtime timer displays (text only, no DOM rebuild)
        for (let key in runningDowntimes) {
            let rd = runningDowntimes[key];
            let diff = Math.floor((now - rd.start) / 1000);
            let timeStr = formatSeconds(diff);

            let btn = document.getElementById(`${rd.btnType}-btn-${rd.jobId}`);
            if (btn) {
                let existing = btn.innerHTML;
                let timeIdx = existing.lastIndexOf('(');
                if (timeIdx > 0) {
                    btn.innerHTML = existing.substring(0, timeIdx + 1) + timeStr + ')';
                }
            }

            let activeTimer = document.getElementById(`active-downtime-timer-${rd.jobId}`);
            if (activeTimer) activeTimer.textContent = timeStr;
        }

        // Update running job timer displays (text only)
        for (let id in jobMasterData) {
            const job = jobMasterData[id];
            if (!job || isJobComplete(job)) continue;

            const isRunning = job.status && job.status.toLowerCase() === 'running';
            const timerEl = document.getElementById('timer-' + id);
            if (!timerEl) continue;

            // ——— BREAK PAUSE ———
            if (job._breakPaused && job._frozenTimer != null) {
                const frozen = formatSeconds(job._frozenTimer);
                timerEl.textContent = frozen;
                const breakTimerEl = document.getElementById('break-overlay-timer');
                if (breakTimerEl) {
                    if (window._autoBreakEndMin != null) {
                        const now = new Date();
                        const nowMin = now.getHours() * 60 + now.getMinutes();
                        const nowSec = now.getSeconds();
                        const remaining = Math.max(0, (window._autoBreakEndMin - nowMin) * 60 - nowSec);
                        breakTimerEl.textContent = formatSeconds(remaining);
                        breakTimerEl.className = remaining <= 0
                            ? 'text-5xl sm:text-6xl font-black text-emerald-500 mt-6 tabular-nums'
                            : 'text-5xl sm:text-6xl font-black text-slate-800 mt-6 tabular-nums';
                    } else {
                        breakTimerEl.textContent = frozen;
                    }
                }
                continue;
            }

            if (isRunning) {
                let jS = job.started_at ? new Date(job.started_at) : null;
                const firstDandori = job.dandori_start ? new Date(job.dandori_start) : null;
                const anchorTime = firstDandori || jS;
                if (anchorTime) {
                    let diffInSeconds = Math.floor((now.getTime() - anchorTime.getTime()) / 1000);
                    timerEl.textContent = formatSeconds(job.base_seconds + Math.max(0, diffInSeconds));
                }
            }
        }

        // Update timeline marker position (for active job)
        const config = window.ProductionConfig;
        if (config.currentActiveId && jobMasterData[config.currentActiveId]) {
            const job = jobMasterData[config.currentActiveId];
            const jS = job.started_at ? new Date(job.started_at) : null;
            const firstDandori = job.dandori_start ? new Date(job.dandori_start) : null;
            
            const historyStarts = Object.values(jobDowntimeHistory[config.currentActiveId] || {}).map(h => {
                const s = h.start || h.start_time;
                return s ? (isNaN(s) ? new Date(s).getTime() : Number(s)) : Infinity;
            });

            const pS_time = Number(job.plan_start);
            const pE_time = Number(job.plan_end);
            const plannedDurationMs = Math.max(pE_time - pS_time, 1000);

            const anchorTime = Math.min(
                jS ? jS.getTime() : Infinity,
                firstDandori ? firstDandori.getTime() : Infinity,
                ...historyStarts
            ) || pS_time;

            if (!isNaN(anchorTime) && anchorTime !== Infinity) {
                const isComplete = isJobComplete(job);
                const jF = job.finished_at ? new Date(job.finished_at) : null;
                const effectiveNow = isComplete ? (jF ? new Date(jF) : new Date(pE_time)) : now;
                const finalEndTime = jF ? new Date(jF) : effectiveNow;
                const expectedFinishTime = anchorTime + plannedDurationMs;

                const tD = Math.max(
                    plannedDurationMs / 1000,
                    (expectedFinishTime - anchorTime) / 1000,
                    (finalEndTime.getTime() - anchorTime) / 1000,
                    1
                );

                const elapsedMs = now.getTime() - anchorTime;
                const pct = Math.min(Math.max(0, (elapsedMs / (tD * 1000)) * 100), 100);

                const marker = document.getElementById('timeline-marker');
                if (marker) {
                    if (isComplete) {
                        marker.style.display = 'none';
                    } else {
                        marker.style.display = 'block';
                        marker.style.left = pct + '%';
                    }
                }
            }

            const clock = document.getElementById('timeline-current-time');
            if (clock) {
                let eH = now.getHours().toString().padStart(2, '0');
                let eM = now.getMinutes().toString().padStart(2, '0');
                clock.textContent = `End: ${eH}:${eM}`;
            }
        }

        // Real-time tooltip duration update for active running downtimes
        const tooltip = document.getElementById('timeline-tooltip');
        if (tooltip && !tooltip.classList.contains('hidden') && window.activeTimelineStart) {
            const durEl = document.getElementById('tooltip-dur');
            const timeEl = document.getElementById('tooltip-time');
            if (durEl && timeEl) {
                const elapsed = Math.floor((now.getTime() - window.activeTimelineStart) / 1000);
                const durStr = elapsed >= 3600
                    ? Math.floor(elapsed / 3600) + 'h ' + Math.floor((elapsed % 3600) / 60) + 'm'
                    : (elapsed >= 60 ? Math.floor(elapsed / 60) + 'm ' + (elapsed % 60) + 's' : elapsed + 's');
                durEl.innerText = durStr;
                const eH = now.getHours().toString().padStart(2, '0');
                const eM = now.getMinutes().toString().padStart(2, '0');
                const eS = now.getSeconds().toString().padStart(2, '0');
                const timeParts = (timeEl.innerText || '').split(' - ');
                if (timeParts.length === 2) {
                    timeEl.innerText = timeParts[0] + ' - ' + eH + ':' + eM + ':' + eS;
                }
            }
        }

        // ——— AUTO BREAK CHECK ———
        _autoBreakTick();

    } catch (e) {
        console.error('updateTimers error:', e);
    }
}

// ——— AUTO BREAK TIME ———
window._autoBreakActive = false;
window._autoBreakDowntimeId = null;
window._autoBreakLastCheck = 0;
window._autoBreakSkipped = false;
window._autoBreakEndMin = null;

function _isInBreakWindow(now) {
    const schedule = window._breakSchedule || [];
    const mins = now.getHours() * 60 + now.getMinutes();
    for (const b of schedule) {
        if (mins >= b.startMin && mins < b.endMin) return b;
    }
    return null;
}

function _clearBreakState(jobId) {
    const job = window.jobMasterData?.[jobId];
    if (job) {
        delete job._breakPaused;
        delete job._frozenTimer;
    }
    window._autoBreakActive = false;
    window._autoBreakDowntimeId = null;
    window._autoBreakSkipped = false;
    window._autoBreakEndMin = null;
    delete window.runningDowntimes?.[`${jobId}_break`];
    window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes || {}).length;
    try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}
    _updateBreakUI(jobId, null, false);
}

function _autoBreakTick() {
    const now = new Date();
    const activeId = window.ProductionConfig?.currentActiveId;
    if (!activeId || !window.ProductionConfig?.currentStatus || window.ProductionConfig.currentStatus === 'none') return;
    if (window.ProductionConfig.isLocked) return;

    const job = window.jobMasterData?.[activeId];
    if (!job || isJobComplete(job)) return;
    if (job.status !== 'running' && job.status !== 'paused') return;

    const breakWindow = _isInBreakWindow(now);
    const alreadyRunningBreak = window.runningDowntimes?.[`${activeId}_break`];

    if (breakWindow && !window._autoBreakActive && !alreadyRunningBreak && !window._autoBreakSkipped) {
        _triggerAutoBreakStart(activeId, breakWindow);
    } else if (!breakWindow && window._autoBreakActive && window._autoBreakDowntimeId) {
        _triggerAutoBreakEnd(activeId);
    } else if (!breakWindow) {
        window._autoBreakSkipped = false;
    }
}

async function _triggerAutoBreakStart(jobId, breakInfo) {
    try {
        const res = await fetch(`/operational/job/${jobId}/downtime/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                jenis_downtime: 'break time',
                problem: breakInfo.label || 'AUTO BREAK',
                penyebab: '-',
                action: '-',
                pic: 'AUTO BREAK'
            })
        }).then(r => r.json());

        if (res.success) {
            window._autoBreakActive = true;
            window._autoBreakDowntimeId = res.downtime.id;
            window._autoBreakEndMin = breakInfo.endMin;

            const startTime = new Date();
            window.runningDowntimes[`${jobId}_break`] = {
                id: res.downtime.id, start: startTime, jobId: jobId,
                btnType: 'break', dtType: 'break time',
                problem: breakInfo.label || 'AUTO BREAK'
            };
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;

            const job = window.jobMasterData[jobId];
            if (job && !job._breakPaused) {
                let currentSeconds = job.base_seconds || 0;
                let jS = job.started_at ? new Date(job.started_at) : null;
                const firstDandori = job.dandori_start ? new Date(job.dandori_start) : null;
                const anchorTime = firstDandori || jS;
                if (anchorTime) {
                    currentSeconds += Math.floor((Date.now() - anchorTime.getTime()) / 1000);
                }
                job._frozenTimer = currentSeconds;
                job._breakPaused = true;
                // Await server pause — sync state before UI update
                try {
                    await fetch(`/operational/job/${jobId}/pause`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
                    });
                } catch (e) {
                    console.warn('AutoBreak pause request failed:', e);
                }
                // Persist break state across page reloads
                try {
                    sessionStorage.setItem('prod_break_state', JSON.stringify({
                        jobId: jobId,
                        frozenTimer: currentSeconds,
                        downtimeId: window._autoBreakDowntimeId,
                        endMin: breakInfo.endMin || null,
                        label: breakInfo.label || 'AUTO BREAK',
                        startedAt: Date.now()
                    }));
                } catch (e) {}
            }

            updateTimeline();
            _updateBreakUI(jobId, breakInfo.label, true);
        }
    } catch (e) {
        console.error('AutoBreak start error:', e);
    }
}

async function _triggerAutoBreakEnd(jobId) {
    try {
        const dtId = window._autoBreakDowntimeId;
        const res = await fetch(`/operational/downtime/${dtId}/finish`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
        }).then(r => r.json());

        if (res.success) {
            delete window.runningDowntimes[`${jobId}_break`];
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;

            if (!window.jobDowntimeHistory[jobId]) window.jobDowntimeHistory[jobId] = [];
            const dt = res.downtime;
            if (dt) {
                window.jobDowntimeHistory[jobId].push({
                    start: new Date(dt.start_time).getTime(),
                    end: dt.finish_time ? new Date(dt.finish_time).getTime() : Date.now(),
                    type: dt.jenis_downtime,
                    id: dt.id
                });
            }

            const job = window.jobMasterData[jobId];
            if (job && job._breakPaused) {
                if (job._frozenTimer != null) {
                    job.base_seconds = job._frozenTimer;
                    job.started_at = new Date().toISOString();
                }
                delete job._breakPaused;
                delete job._frozenTimer;
                job.status = 'running';
                window.ProductionConfig.currentStatus = 'running';
                try {
                    await fetch(`/operational/job/${jobId}/resume`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
                    });
                } catch (e) {
                    console.warn('AutoBreak resume request failed:', e);
                }
            }

            // Clear persisted break state
            try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}

            window._autoBreakActive = false;
            window._autoBreakDowntimeId = null;
            window._autoBreakSkipped = false;
            window._autoBreakEndMin = null;

            updateTimeline();
            _updateBreakUI(jobId, null, false);
        } else {
            _clearBreakState(jobId);
        }
    } catch (e) {
        console.error('AutoBreak end error:', e);
        _clearBreakState(jobId);
    }
}

function _updateBreakUI(jobId, label, isPaused) {
    const statusContainer = document.getElementById('realtime-status-container');
    const statusText = document.getElementById('realtime-status-text');
    const statusPing = document.getElementById('realtime-status-ping');
    const statusDot = document.getElementById('realtime-status-dot');
    const alertBox = document.getElementById('active-downtime-alert-box');
    const alertTitle = document.getElementById('active-downtime-title');
    const workArea = document.getElementById('active-work-area');
    const breakOverlay = document.getElementById('break-overlay');
    const breakLabel = document.getElementById('break-overlay-label');
    const breakTimer = document.getElementById('break-overlay-timer');

    if (isPaused) {
        if (!window._origStatusClasses) {
            window._origStatusClasses = {
                containerClass: statusContainer?.className || '',
                textClass: statusText?.className || '',
                textContent: statusText?.textContent || '',
                pingClass: statusPing?.className || '',
                dotClass: statusDot?.className || ''
            };
        }

        if (statusContainer) statusContainer.className = window._origStatusClasses.containerClass.replace(/bg-emerald-500\/10/g, 'bg-slate-500/10').replace(/border-emerald-500\/20/g, 'border-slate-500/20');
        if (statusText) { statusText.className = window._origStatusClasses.textClass.replace(/text-emerald-400/g, 'text-slate-400'); statusText.textContent = 'BREAK'; }
        if (statusPing) statusPing.className = window._origStatusClasses.pingClass.replace(/bg-emerald-500/g, 'bg-slate-500');
        if (statusDot) statusDot.className = window._origStatusClasses.dotClass.replace(/bg-emerald-500/g, 'bg-slate-500');
        if (alertBox) { alertBox.className = alertBox.className.replace(/hidden/, '').trim() + ' bg-slate-500/10 border-slate-500/30'; }
        if (alertTitle) { alertTitle.className = alertTitle.className.replace(/text-\w+-400/g, 'text-slate-400').replace(/text-\w+-500/g, 'text-slate-400'); alertTitle.textContent = label || 'Break Time'; }

        const bar = document.getElementById('progress-bar');
        if (bar && !window._origBarColor) { window._origBarColor = bar.className; bar.className = bar.className.replace(/bg-blue-500/g, 'bg-slate-500').replace(/from-blue-500/g, 'from-slate-500').replace(/to-blue-400/g, 'to-slate-400'); }

        if (workArea) workArea.classList.add('hidden');
        if (breakOverlay) {
            breakOverlay.classList.remove('hidden');
            if (breakLabel) breakLabel.textContent = label || 'BREAK TIME';
        }

    } else {
        if (window._origStatusClasses) {
            if (statusContainer) statusContainer.className = window._origStatusClasses.containerClass;
            if (statusText) { statusText.className = window._origStatusClasses.textClass; statusText.textContent = window._origStatusClasses.textContent; }
            if (statusPing) statusPing.className = window._origStatusClasses.pingClass;
            if (statusDot) statusDot.className = window._origStatusClasses.dotClass;
            delete window._origStatusClasses;
        }
        if (alertBox && !alertBox.className.includes('hidden')) alertBox.className += ' hidden';
        if (window._origBarColor) { const bar = document.getElementById('progress-bar'); if (bar) bar.className = window._origBarColor; delete window._origBarColor; }

        if (workArea) workArea.classList.remove('hidden');
        if (breakOverlay) breakOverlay.classList.add('hidden');
        updateTimeline();
    }
}

// Mid-break detection on page load — use DB history first, fallback to sessionStorage
(function() {
    const activeId = window.ProductionConfig?.currentActiveId;
    if (!activeId) return;

    // Try DB history first
    const history = window.jobDowntimeHistory?.[activeId] || [];
    for (const h of history) {
        if (!h.end && h.type === 'break time') {
            window._autoBreakActive = true;
            window._autoBreakDowntimeId = h.id;
            const activeBreakWindow = _isInBreakWindow(new Date());
            window._autoBreakEndMin = activeBreakWindow ? activeBreakWindow.endMin : null;
            const job = window.jobMasterData?.[activeId];
            if (job && !job._breakPaused) {
                let currentSeconds = job.base_seconds || 0;
                let jS = job.started_at ? new Date(job.started_at) : null;
                const firstDandori = job.dandori_start ? new Date(job.dandori_start) : null;
                const anchorTime = firstDandori || jS;
                if (anchorTime) {
                    currentSeconds += Math.floor((Date.now() - anchorTime.getTime()) / 1000);
                }
                job._frozenTimer = currentSeconds;
                job._breakPaused = true;
            }
            break;
        }
    }

    // Fallback: check sessionStorage if DB didn't have it but page reloaded mid-break
    if (!window._autoBreakActive) {
        try {
            const saved = JSON.parse(sessionStorage.getItem('prod_break_state') || 'null');
            if (saved && saved.jobId && saved.downtimeId) {
                const job = window.jobMasterData?.[saved.jobId];
                if (job && !job._breakPaused && (job.status === 'running' || job.status === 'paused')) {
                    window._autoBreakActive = true;
                    window._autoBreakDowntimeId = saved.downtimeId;
                    window._autoBreakEndMin = saved.endMin || null;
                    job._frozenTimer = saved.frozenTimer;
                    job._breakPaused = true;

                    const typeMap = { 'break time': 'break' };
                    window.runningDowntimes[`${saved.jobId}_break`] = {
                        id: saved.downtimeId,
                        start: new Date(saved.startedAt),
                        jobId: saved.jobId,
                        btnType: 'break',
                        dtType: 'break time',
                        problem: saved.label || 'AUTO BREAK'
                    };
                    window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;
                    _updateBreakUI(saved.jobId, saved.label, true);
                }
            }
        } catch (e) {}
    }
})();

function updateLostTimeDisplay(jobId) {
    const el = document.getElementById('active-lost-time-display');
    if (!el) return;
    let totalSec = 0;
    const exclude = ['dandori', 'break', 'break time'];
    // From completed history
    const hist = window.jobDowntimeHistory?.[jobId] || [];
    for (const h of hist) {
        if (!exclude.includes((h.type || '').toLowerCase())) {
            const s = Math.floor(((h.end || Date.now()) - h.start) / 1000);
            if (s > 0) totalSec += s;
        }
    }
    // From active running downtimes
    for (const [key, rd] of Object.entries(window.runningDowntimes || {})) {
        if (rd.jobId == jobId && !exclude.includes((rd.dtType || '').toLowerCase())) {
            const s = Math.floor((Date.now() - rd.start.getTime()) / 1000);
            if (s > 0) totalSec += s;
        }
    }
    const m = Math.floor(totalSec / 60);
    const s = totalSec % 60;
    el.innerHTML = (m > 0 ? m + '<span class="text-xs sm:text-sm font-black text-slate-500">m </span>' : '') + s + '<span class="text-xs sm:text-sm font-black text-slate-500">s</span>';
}

function updateTimeline(forceAll = false) {
    try {
        if (window.freezeTimers) return;
        const config = window.ProductionConfig;
        const now = new Date();
        let hasActiveRunningJob = false;

        // if (forceAll) {
        //     console.log("ENGINE STATE:", {
        //         config: config,
        //         jobCount: Object.keys(window.jobMasterData || {}).length,
        //         runningCount: Object.keys(window.runningDowntimes || {}).length,
        //         historyCount: Object.keys(window.jobDowntimeHistory || {}).length
        //     });
        // }

        const runningDowntimes = window.runningDowntimes || {};

        // 2. HIGHLIGHT RUNNING DOWNTIMES
        for (let key in runningDowntimes) {
        let rd = runningDowntimes[key];
        let diff = Math.floor((now - rd.start) / 1000);
        let timeStr = formatSeconds(diff);

        let btn = document.getElementById(`${rd.btnType}-btn-${rd.jobId}`);
        if (btn) {
            btn.innerHTML = `<span class="flex items-center justify-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                </span>
                STOP ${rd.btnType.toUpperCase()} (${timeStr})
            </span>`;
            btn.className = "w-full py-4 rounded-xl bg-red-600 text-white border-red-700 text-xs font-black uppercase animate-pulse scale-105 shadow-lg shadow-red-900/50 transition-all";
        }

        const activeTimer = document.getElementById(`active-downtime-timer-${rd.jobId}`);
        if (activeTimer) {
            activeTimer.textContent = timeStr;
        }
    }

    // 3. PROCESS ALL JOBS FROM CENTRALIZED DATA
    // Skip completed/finished jobs unless forceAll — they don't change
    const activeId = config.currentActiveId;
    for (let id in jobMasterData) {
        const job = jobMasterData[id];
        if (!job) continue;
        if (!forceAll && isJobComplete(job) && id != activeId) continue;
        if (!forceAll && job.status === 'pending' && id != activeId) continue;

        try {
            const isComplete = isJobComplete(job);
            const isRunning = job.status && job.status.toLowerCase() === 'running';

            const pS_time = Number(job.plan_start);
            const pE_time = Number(job.plan_end);
            const plannedDurationMs = Math.max(pE_time - pS_time, 1000); // Guard: min 1s

            const pS = new Date(pS_time);
            const pE = new Date(pE_time);

            const effectiveNow = isComplete ? (job.finished_at ? new Date(job.finished_at) : pE) : now;

            let jS = job.started_at ? new Date(job.started_at) : null;
            const jF = job.finished_at ? new Date(job.finished_at) : null;

            if (!jS && jF && job.base_seconds > 0) {
                jS = new Date(jF.getTime() - (job.base_seconds * 1000));
                job.started_at = jS.getTime();
            }

            const historyStarts = Object.values(jobDowntimeHistory[id] || {}).map(h => {
                const s = h.start || h.start_time;
                return s ? (isNaN(s) ? new Date(s).getTime() : Number(s)) : Infinity;
            });

            const firstDandori = job.dandori_start ? new Date(job.dandori_start) : null;
            const anchorTime = Math.min(
                jS ? jS.getTime() : Infinity,
                firstDandori ? firstDandori.getTime() : Infinity,
                ...historyStarts
            ) || pS_time;

            if (isNaN(anchorTime) || anchorTime === Infinity) {
                continue;
            }

            const finalEndTime = jF ? new Date(jF) : effectiveNow;

            // INDUSTRIAL CALIBRATION: Scale must cover (Actual Start + Quota)
            const expectedFinishTime = anchorTime + plannedDurationMs;

            const tD = Math.max(
                plannedDurationMs / 1000,
                (expectedFinishTime - anchorTime) / 1000,
                (finalEndTime.getTime() - anchorTime) / 1000,
                1
            );

            const visualElapsed = (finalEndTime.getTime() - anchorTime) / 1000;
            const visualPct = Math.min(Math.max(0, (visualElapsed / (plannedDurationMs / 1000)) * 100), 100);

            let elapsed = 0;
            if (jS || firstDandori) {
                let activeStartMs = jS ? jS.getTime()
                    : (firstDandori instanceof Date ? firstDandori.getTime() : new Date(firstDandori).getTime());
                const jobHistory = window.jobDowntimeHistory[id] || [];
                const lastDandoriEntry = [...jobHistory]
                    .filter(h => (h.type || h.jenis_downtime || '').toLowerCase() === 'dandori')
                    .sort((a, b) => {
                        const aEnd = a.end || a.finish_time || a.finished_at || 0;
                        const bEnd = b.end || b.finish_time || b.finished_at || 0;
                        return bEnd - aEnd;
                    })[0];
                if (lastDandoriEntry) {
                    const dandoriEndRaw = lastDandoriEntry.end || lastDandoriEntry.finish_time || lastDandoriEntry.finished_at;
                    if (dandoriEndRaw) {
                        const dandoriEndMs = isNaN(dandoriEndRaw) ? new Date(dandoriEndRaw).getTime() : Number(dandoriEndRaw);
                        if (dandoriEndMs > activeStartMs) activeStartMs = dandoriEndMs;
                    }
                }
                elapsed = (job.base_seconds || 0) + (finalEndTime.getTime() - activeStartMs) / 1000;
            }
            const realPct = (elapsed / (plannedDurationMs / 1000)) * 100;

            // if (isComplete && forceAll) {
            //     console.log(`TRACE [${id}]:`, { jS, jF, anchorTime, tD, pS_time, pE_time, plannedDurationMs, realPct });
            // }

            // Industrial KPI: Production Achievement (Qty vs Plan)
            const totalActual = (job.actual_ok || 0) + (job.actual_repair || 0) + (job.actual_reject || 0);
            const achievementPct = job.target_qty > 0 ? Math.round((totalActual / job.target_qty) * 100) : 0;

            // B. Update Table Row
            const rowPctEl = document.getElementById('pct-' + id);
            if (rowPctEl) {
                const displayProgress = Math.round(realPct);
                if (realPct > 100) {
                    rowPctEl.innerHTML = `<span class="text-red-500 font-black">OVER ${displayProgress}%</span>`;
                } else {
                    rowPctEl.innerText = displayProgress + '%';
                }
            }

            // Calculate segment percentages based on quantities for the stacked progress bar
            const rowOk = parseInt(job.actual_ok) || 0;
            const rowRepair = parseInt(job.actual_repair) || 0;
            const rowReject = parseInt(job.actual_reject) || 0;
            const rowTarget = parseInt(job.target_qty) || 0;

            const rowTotal = rowOk + rowRepair + rowReject;
            const rowDenom = Math.max(rowTarget, rowTotal, 1);

            const rowOkPct = (rowOk / rowDenom) * 100;
            const rowRepairPct = (rowRepair / rowDenom) * 100;
            const rowRejectPct = (rowReject / rowDenom) * 100;

            const rowBarOk = document.getElementById('row-bar-ok-' + id);
            const rowBarRepair = document.getElementById('row-bar-repair-' + id);
            const rowBarReject = document.getElementById('row-bar-reject-' + id);

            if (rowBarOk) rowBarOk.style.width = rowOkPct + '%';
            if (rowBarRepair) rowBarRepair.style.width = rowRepairPct + '%';
            if (rowBarReject) rowBarReject.style.width = rowRejectPct + '%';

            renderSegmentedTimeline('actual-segments-' + id, id, anchorTime, tD, jS, finalEndTime, firstDandori, realPct, plannedDurationMs, pE_time);

            // Update Queue Table Achievement text
            const rowActualText = document.getElementById('row-actual-' + id);
            if (rowActualText) {
                rowActualText.innerText = (job.actual_ok || 0).toLocaleString();
            }
            const rowEfficiencyText = document.getElementById('row-efficiency-' + id);
            if (rowEfficiencyText) {
                const efficiency = job.target_qty > 0 ? ((job.actual_ok || 0) / job.target_qty) * 100 : 0;
                rowEfficiencyText.innerText = efficiency.toFixed(1) + '%';
            }

            // A. Update Active Job Board (Hero)
            if (id == config.currentActiveId) {
                // Update Quick Display
                const activeOkDisplay = document.getElementById('active-actual-display');
                if (activeOkDisplay) activeOkDisplay.innerText = job.actual_ok || 0;

                const activeRepairDisplay = document.getElementById('active-repair-display');
                if (activeRepairDisplay) activeRepairDisplay.innerText = job.actual_repair || 0;

                const activeRejectDisplay = document.getElementById('active-reject-display');
                if (activeRejectDisplay) activeRejectDisplay.innerText = job.actual_reject || 0;

                // Update Start Time display via specific ID
                const startTimeEl = document.getElementById('execution-started-at');
                if (startTimeEl) {
                    const displayTime = jS || firstDandori;
                    if (displayTime) {
                        let sH = displayTime.getHours().toString().padStart(2, '0');
                        let sM = displayTime.getMinutes().toString().padStart(2, '0');
                        startTimeEl.innerText = `Started: ${sH}:${sM}`;
                    }
                }

                const dTimer = document.getElementById('dandori-timer-' + id);
                if (dTimer && firstDandori) {
                    const dS_db = job.dandori_start ? new Date(job.dandori_start).getTime() : firstDandori.getTime();
                    const diff = Math.max(0, Math.floor((effectiveNow.getTime() - dS_db) / 1000));
                    dTimer.innerText = formatSeconds(diff);
                }

                // Update active board stacked bar
                const activeBarOk = document.getElementById('timeline-bar-ok');
                const activeBarRepair = document.getElementById('timeline-bar-repair');
                const activeBarReject = document.getElementById('timeline-bar-reject');

                if (activeBarOk) activeBarOk.style.width = rowOkPct + '%';
                if (activeBarRepair) activeBarRepair.style.width = rowRepairPct + '%';
                if (activeBarReject) activeBarReject.style.width = rowRejectPct + '%';

                const label = document.getElementById('timeline-time-label');
                if (label) {
                    const displayProgress = Math.round(realPct);
                    if (realPct > 100) {
                        label.innerHTML = `<span class="text-red-500 animate-pulse">OVER ${displayProgress}%</span>`;
                        label.classList.remove('text-blue-500');
                        label.classList.add('text-red-500', 'animate-pulse');
                        if (!document.getElementById('overtime-badge')) {
                            const badge = document.createElement('div');
                            badge.id = 'overtime-badge';
                            badge.className = 'text-[8px] font-black text-red-500 uppercase tracking-widest mt-1 animate-bounce';
                            badge.innerText = 'OVERTIME PROCESS';
                            label.parentElement.appendChild(badge);
                        }
                    } else {
                        label.innerText = displayProgress + '%';
                        label.classList.add('text-blue-500');
                        label.classList.remove('text-red-500', 'animate-pulse');
                        const badge = document.getElementById('overtime-badge');
                        if (badge) badge.remove();
                    }
                }

                // Update Achievement (Qty Based with PCS details)
                const liveOk = parseInt(job.actual_ok) || 0;
                const liveRepair = parseInt(job.actual_repair) || 0;
                const liveReject = parseInt(job.actual_reject) || 0;

                const liveTotalActual = liveOk + liveRepair + liveReject;
                const liveAchievementPct = job.target_qty > 0 ? Math.round((liveTotalActual / job.target_qty) * 100) : 0;

                const achievementEl = document.getElementById('active-achievement-display');
                if (achievementEl) {
                    achievementEl.innerText = liveAchievementPct + '%';
                    achievementEl.className = `text-2xl sm:text-3xl font-black tracking-tighter tabular-nums ${liveAchievementPct >= 100 ? 'text-green-400' : 'text-yellow-400'}`;
                }
                const achievementPcsEl = document.getElementById('active-achievement-pcs');
                if (achievementPcsEl) {
                    achievementPcsEl.innerText = `(${liveTotalActual} / ${job.target_qty} PCS)`;
                }

                const clock = document.getElementById('timeline-current-time');
                if (clock) {
                    let endTimeDate = effectiveNow;
                    if (jS && job.tpt > 0) {
                        endTimeDate = new Date(jS.getTime() + (job.tpt * 60 * 1000));
                    } else if (job.plan_end) {
                        endTimeDate = new Date(job.plan_end);
                    }
                    let eH = endTimeDate.getHours().toString().padStart(2, '0');
                    let eM = endTimeDate.getMinutes().toString().padStart(2, '0');
                    clock.innerText = `End: ${eH}:${eM}`;
                }

                renderSegmentedTimeline('timeline-actual-container', id, anchorTime, tD, jS, finalEndTime, firstDandori, realPct, plannedDurationMs, pE_time);

                if (isRunning) {
                    hasActiveRunningJob = true;
                    // Inactivity Warning Check (15 Minutes Alert)
                    const lastInput = window.ProductionConfig.lastInputAt;
                    if (lastInput) {
                        const lastTime = new Date(lastInput).getTime();
                        const nowMs = effectiveNow.getTime();
                        const diffMs = nowMs - lastTime;
                        const diffMinutes = diffMs / (1000 * 60);

                        // idle alert removed
                    }
                }
            }

            // C. Update Timer Display
            const timerEl = document.getElementById('timer-' + id);
            if (timerEl) {
                if (isRunning) {
                    const timerStart = jS || firstDandori || new Date(anchorTime);
                    let diffInSeconds = Math.floor((effectiveNow.getTime() - timerStart.getTime()) / 1000);
                    timerEl.innerText = formatSeconds(job.base_seconds + Math.max(0, diffInSeconds));
                } else {
                    timerEl.innerText = formatSeconds(job.base_seconds);
                }
            }

            // Hide/Disable control buttons for completed jobs
            if (isComplete) {
                const row = document.getElementById('row-' + id);
                if (row) {
                    row.querySelectorAll('button:not(.detail-btn), .quick-input-group, input').forEach(el => {
                        el.style.display = 'none';
                    });
                }
            }

        } catch (e) {
            console.error(`ERROR PROCESSING JOB [${id}]:`, e);
        }
    }

    // 4. DYNAMIC REALTIME STATUS CARD UPDATE
    const statusContainer = document.getElementById('realtime-status-container');
    const statusPing = document.getElementById('realtime-status-ping');
    const statusDot = document.getElementById('realtime-status-dot');
    const statusText = document.getElementById('realtime-status-text');
    const activeJobId = config.currentActiveId;

    if (statusContainer && statusPing && statusDot && statusText && activeJobId) {
        let activeDt = null;
        for (let key in runningDowntimes) {
            if (runningDowntimes[key].jobId == activeJobId) {
                activeDt = runningDowntimes[key];
                break;
            }
        }

        let label = 'PRODUKSI';
        let bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-emerald-500/10 border-emerald-500/20 flex items-center justify-between transition-all duration-300';
        let textClass = 'text-sm font-black text-emerald-400 uppercase tracking-widest';
        let pulseClass = 'bg-emerald-500';

        if (activeDt) {
            const dtType = String(activeDt.dtType || '').toLowerCase();
            if (dtType === 'dandori' && runningDowntimes[`${activeJobId}_firstcheck`]) {
                label = '1ST CHECK';
                bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-purple-500/10 border-purple-500/20 flex items-center justify-between transition-all duration-300';
                textClass = 'text-sm font-black text-purple-400 uppercase tracking-widest';
                pulseClass = 'bg-purple-500';
            } else if (dtType === 'dandori') {
                label = 'DANDORI';
                bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-amber-500/10 border-amber-500/20 flex items-center justify-between transition-all duration-300';
                textClass = 'text-sm font-black text-amber-400 uppercase tracking-widest';
                pulseClass = 'bg-amber-500';
            } else if (dtType === '1st_check') {
                label = '1ST CHECK';
                bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-purple-500/10 border-purple-500/20 flex items-center justify-between transition-all duration-300';
                textClass = 'text-sm font-black text-purple-400 uppercase tracking-widest';
                pulseClass = 'bg-purple-500';
            } else if (dtType === 'break time' || dtType === 'break') {
                label = 'BREAK';
                bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-indigo-500/10 border-indigo-500/20 flex items-center justify-between transition-all duration-300';
                textClass = 'text-sm font-black text-indigo-400 uppercase tracking-widest';
                pulseClass = 'bg-indigo-500';
            } else if (dtType === 'try out' || dtType === 'tryout') {
                label = 'TRY OUT';
                bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-orange-500/10 border-orange-500/20 flex items-center justify-between transition-all duration-300';
                textClass = 'text-sm font-black text-orange-400 uppercase tracking-widest';
                pulseClass = 'bg-orange-500';
            } else {
                label = 'DOWNTIME';
                bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-rose-500/10 border-rose-500/20 flex items-center justify-between transition-all duration-300';
                textClass = 'text-sm font-black text-rose-400 uppercase tracking-widest';
                pulseClass = 'bg-rose-500';
            }
        } else {
            const activeJob = window.jobMasterData ? window.jobMasterData[activeJobId] : null;
            const isStarted = activeJob && activeJob.started_at;
            if (!isStarted) {
                label = 'PENDING';
                bgClass = 'px-4 py-3 mt-3.5 rounded-2xl border bg-slate-800/80 border-slate-700 flex items-center justify-between transition-all duration-300';
                textClass = 'text-sm font-black text-slate-500 uppercase tracking-widest';
                pulseClass = 'bg-slate-600';
            }
        }

        // Apply classes and text content
        statusContainer.className = bgClass;
        statusText.className = textClass;
        statusText.innerText = label;
        statusPing.className = `animate-ping absolute inline-flex h-full w-full rounded-full ${pulseClass} opacity-75`;
        statusDot.className = `relative inline-flex rounded-full h-2 w-2 ${pulseClass}`;

        // Update active downtime alert box
        const alertBox = document.getElementById('active-downtime-alert-box');
        const alertTitle = document.getElementById('active-downtime-title');

        if (alertBox && alertTitle) {
            if (activeDt) {
                const dtType = String(activeDt.dtType || '').toLowerCase();
                let boxBg = 'bg-red-500/10 border-red-500/30';
                let titleColor = 'text-red-500';
                let titleText = 'DOWNTIME';

                if (dtType === 'dandori' && runningDowntimes[`${activeJobId}_firstcheck`]) {
                    boxBg = 'bg-purple-500/10 border-purple-500/30';
                    titleColor = 'text-purple-400';
                    titleText = '1st Check';
                } else if (dtType === 'dandori') {
                    boxBg = 'bg-amber-500/10 border-amber-500/30';
                    titleColor = 'text-amber-500';
                    titleText = 'Dandori (Persiapan)';
                } else if (dtType === '1st_check') {
                    boxBg = 'bg-purple-500/10 border-purple-500/30';
                    titleColor = 'text-purple-400';
                    titleText = '1st Check';
                } else if (dtType === 'break time' || dtType === 'break') {
                    boxBg = 'bg-indigo-500/10 border-indigo-500/30';
                    titleColor = 'text-indigo-400';
                    titleText = 'Break Time';
                } else if (dtType === 'try out' || dtType === 'tryout') {
                    boxBg = 'bg-orange-500/10 border-orange-500/30';
                    titleColor = 'text-orange-400';
                    titleText = 'Try Out';
                } else {
                    titleText = 'DOWNTIME';
                }

                alertBox.className = `${boxBg} border-2 rounded-2xl p-4 text-center shadow-lg transition-all duration-300 flex-shrink-0`;
                alertTitle.className = `text-xs sm:text-sm font-black ${titleColor} uppercase tracking-widest mb-1`;
                alertTitle.innerText = titleText;
                alertBox.classList.remove('hidden');
            } else {
                alertBox.classList.add('hidden');
            }
        }
    }

    if (!hasActiveRunningJob) {
        // idle alert bar removed
    }
    } catch (e) {
        console.error('updateTimeline error:', e);
    }
}

function renderSegmentedTimeline(containerId, jobId, anchor, tD, jS, endTime, firstDandori, nowPctArg = null, plannedDurationArg = null, planEndTimeArg = null) {
    try {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Skip full re-render when hovering a timeline segment to prevent flicker
        if (window.activeTimelineMarkerId && containerId.includes('container')) {
            return;
        }

        const job = jobMasterData[jobId];
        const isComplete = isJobComplete(job);
        const s = job?.status?.toLowerCase();
        const nowPct = nowPctArg !== null ? nowPctArg : (((new Date()).getTime() - anchor) / 1000 / tD) * 100;

        let history = JSON.parse(JSON.stringify(jobDowntimeHistory[jobId] || []));

        const createSegmentHtml = (start, end, anchor, tD, color, label, id, index) => {
            if (end <= start) return '';
            const left = ((start - anchor) / (tD * 1000)) * 100;
            const width = ((end - start) / (tD * 1000)) * 100;
            if (width <= 0) return '';

            const safeWidth = Math.min(width, 100 - left);
            const safeLeft = Math.max(0, Math.min(left, 100));

            const sD = new Date(start);
            const eD = new Date(end);

            const sT = sD.toTimeString().split(' ')[0];
            const eT = eD.toTimeString().split(' ')[0];

            const dur = Math.round((end - start) / 1000);
            const durStr = dur >= 3600 ? Math.floor(dur / 3600) + 'h ' + Math.floor((dur % 3600) / 60) + 'm' : (dur >= 60 ? Math.floor(dur / 60) + 'm ' + (dur % 60) + 's' : dur + 's');

            let headerColor = 'text-blue-400';
            if (color.includes('bg-amber')) headerColor = 'text-amber-500';
            else if (color.includes('bg-orange')) headerColor = 'text-orange-500';
            else if (color.includes('bg-slate')) headerColor = 'text-slate-400';
            else if (color.includes('bg-indigo')) headerColor = 'text-indigo-400';
            else if (color.includes('bg-purple')) headerColor = 'text-purple-400';
            else if (color.includes('bg-red')) headerColor = 'text-red-500';

            const tooltipId = `tt-${id}-${index}`;

            const isActive = window.activeTimelineMarkerId === tooltipId;
            const opacityClass = isActive ? 'opacity-100 z-[60]' : 'opacity-40';

            const safeLabel = encodeURIComponent(label);

            const isRunningSeg = !isComplete && (Math.abs(end - endTime.getTime()) < 1000);

            const segmentHtml = `
                <div class="absolute h-full ${color} cursor-pointer border-r border-white/20 flex items-center justify-center hover:brightness-110 transition-all"
                     style="left: ${safeLeft}%; width: ${safeWidth}%;"
                     data-ttid="${tooltipId}"
                     data-label="${safeLabel}"
                     data-st="${sT}"
                     data-et="${eT}"
                     data-dur="${durStr}"
                     data-hcolor="${headerColor}"
                     ${isRunningSeg ? `data-running-segment="true" data-start="${start}"` : ''}>
                </div>
            `;

            const labelHtml = `
                <div id="${tooltipId}" class="absolute bottom-full -translate-x-1/2 hidden bg-[#151c2c] p-4 rounded-xl shadow-2xl whitespace-nowrap z-[100] mb-3 pointer-events-none border border-white/10 min-w-[200px] flex-col" style="left: ${safeLeft + (safeWidth / 2)}%;">
                    <div class="${headerColor} font-black text-[10px] uppercase tracking-widest mb-1 text-left">${label}</div>
                    <div class="text-white font-bold text-lg mb-2 tabular-nums text-left tracking-tight tooltip-time-range">${sT} - ${eT}</div>
                    <div class="h-[1px] bg-white/10 mb-2"></div>
                    <div class="flex justify-between items-center text-slate-500 font-bold text-[10px] uppercase">
                        <span>Duration</span>
                        <span class="text-white tooltip-duration-text">${durStr}</span>
                    </div>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-[#151c2c]"></div>
                </div>

                <div class="absolute top-full mt-1 -translate-x-1/2 flex flex-col items-center pointer-events-none z-50 ${opacityClass}" id="${tooltipId}-s" style="left: ${safeLeft}%;">
                    <div class="bg-slate-700 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm border border-slate-600">${sT.substring(0, 5)}</div>
                </div>
                <div class="absolute top-full mt-1 -translate-x-1/2 flex flex-col items-center pointer-events-none z-50 ${opacityClass}" id="${tooltipId}-e" style="left: ${safeLeft + safeWidth}%;">
                    <div class="bg-slate-700 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm border border-slate-600">${eT.substring(0, 5)}</div>
                </div>
            `;

            return { segmentHtml, labelHtml };
        };

        const createCalloutHtml = (time, anchor, tD, id, index) => {
            return '';
        };

        const normalizedHistory = history.map(h => {
            const rawStart = h.start || h.start_time;
            const rawEnd = h.end || h.finish_time || h.finished_at || (isComplete ? endTime.getTime() : Date.now());
            const rawType = h.type || h.jenis_downtime;
            const rawProblem = h.problem || '';

            return {
                start: rawStart ? (isNaN(rawStart) ? new Date(rawStart).getTime() : Number(rawStart)) : null,
                end: rawEnd ? (isNaN(rawEnd) ? new Date(rawEnd).getTime() : Number(rawEnd)) : null,
                type: String(rawType || '').trim().toLowerCase(),
                problem: String(rawProblem || '').trim()
            };
        }).filter(h => h.start !== null);

        const runningDowntimes = window.runningDowntimes || {};
        const runningDowntimesForJob = [];
        for (let k in runningDowntimes) {
            if (runningDowntimes[k].jobId == jobId) {
                runningDowntimesForJob.push(runningDowntimes[k]);
            }
        }
        runningDowntimesForJob.sort((a, b) => a.start.getTime() - b.start.getTime());
        const rd = runningDowntimesForJob.length > 0 ? runningDowntimesForJob[0] : null;

        normalizedHistory.sort((a, b) => a.start - b.start);

        let html = '';
        let labelsHtml = '';
        let segmentCount = 0;

        const addSegment = (start, end, color, label) => {
            const res = createSegmentHtml(start, end, anchor, tD, color, label, containerId, segmentCount++);
            if (res) {
                html += res.segmentHtml;
                labelsHtml += res.labelHtml;
            }
        };

        const hasDandori = !!firstDandori;
        const actualStartMs = jS ? (jS instanceof Date ? jS.getTime() : new Date(jS).getTime()) : (job.act_start_ms || null);

        const effectiveActualStart = actualStartMs ||
            (hasDandori ? (firstDandori instanceof Date ? firstDandori.getTime() : new Date(firstDandori).getTime()) : null);

        if (!effectiveActualStart && !hasDandori && normalizedHistory.length === 0) {
            container.innerHTML = '';
            return;
        }

        const firstAnyHistory = normalizedHistory.length ? normalizedHistory[0].start : null;
        let effectiveProductionStart = actualStartMs || effectiveActualStart || firstAnyHistory;

        if (!effectiveProductionStart || isNaN(effectiveProductionStart)) {
            effectiveProductionStart = (effectiveActualStart && !isNaN(effectiveActualStart)) ? effectiveActualStart : anchor;
        }

        if (hasDandori && normalizedHistory.length) {
            const lastDandori = [...normalizedHistory]
                .filter(h => h.type === 'dandori')
                .sort((a, b) => b.start - a.start)[0];
            if (lastDandori && lastDandori.end) {
                effectiveProductionStart = lastDandori.end;
            }
        }

        if (job.base_seconds > 0 && !hasDandori) {
            effectiveProductionStart = actualStartMs - (job.base_seconds * 1000);
        }

        const pD = Number(plannedDurationArg) || 0;
        const planEndTime = Number(planEndTimeArg) || 0;
        const relativeDeadline = effectiveProductionStart + pD;

        const appendProduction = (start, end) => {
            if (end <= start) return;
            if (relativeDeadline && end > relativeDeadline && start < relativeDeadline) {
                addSegment(start, relativeDeadline, 'bg-blue-600', 'Production');
                addSegment(relativeDeadline, end, 'bg-red-600', 'Overtime');
            } else if (relativeDeadline && start >= relativeDeadline) {
                addSegment(start, end, 'bg-red-600', 'Overtime');
            } else {
                addSegment(start, end, 'bg-blue-600', 'Production');
            }
        };

        let earliestActivity = Infinity;
        if (effectiveActualStart) earliestActivity = Math.min(earliestActivity, effectiveActualStart);
        if (hasDandori) earliestActivity = Math.min(earliestActivity, (firstDandori instanceof Date ? firstDandori.getTime() : new Date(firstDandori).getTime()));
        normalizedHistory.forEach(dt => { if (!isNaN(dt.start)) earliestActivity = Math.min(earliestActivity, dt.start); });

        let lastPos = (earliestActivity === Infinity) ? anchor : earliestActivity;

        normalizedHistory.forEach(dt => {
            const start = dt.start;
            let end = dt.end ? dt.end : (isComplete ? endTime.getTime() : null);
            if (!end || isNaN(start) || isNaN(end)) return;

            if (start > lastPos) {
                const dandoriStart = hasDandori ? (firstDandori instanceof Date ? firstDandori.getTime() : new Date(firstDandori).getTime()) : null;
                const hasRunningDandori = runningDowntimesForJob.some(rd => rd.btnType === 'dandori');
                const isInitialDandoriGap = dandoriStart && start > dandoriStart && lastPos === earliestActivity && (hasRunningDandori || !effectiveActualStart || start <= effectiveActualStart);

                if (isInitialDandoriGap) {
                    addSegment(lastPos, start, 'bg-amber-400', 'Dandori');
                    lastPos = start;
                } else if (effectiveActualStart && start > effectiveActualStart) {
                    const segStart = Math.max(lastPos, effectiveActualStart);
                    appendProduction(segStart, start);
                    lastPos = start;
                }
            }

            let color = 'bg-red-500';
            if (dt.type === 'dandori') color = 'bg-amber-400';
            else if (dt.type === 'firstcheck' || dt.type === '1st_check') color = 'bg-purple-500';
            else if (dt.type === 'try out') color = 'bg-orange-500';
            else if (dt.type === 'break time') color = 'bg-slate-500';

            let displayType = dt.type;
            if (dt.type !== 'dandori' && dt.type !== 'firstcheck' && dt.type !== '1st_check' && dt.type !== 'try out' && dt.type !== 'break time') {
                displayType = 'Downtime ' + displayType;
                if (dt.problem && dt.problem !== '-' && dt.problem.toUpperCase() !== 'MENUNGGU PROSES MULAI (IDLE TIME)') {
                    displayType += ': ' + dt.problem;
                }
            }
            addSegment(start, end, color, displayType);
            lastPos = end;
        });

        const finalTime = endTime.getTime();
        if (runningDowntimesForJob.length > 0) {
            runningDowntimesForJob.forEach((rdItem, rdIdx) => {
                const rdStart = Math.max(rdItem.start.getTime(), lastPos);
                if (rdStart > lastPos && effectiveActualStart && rdStart > effectiveActualStart) {
                    const segStart = Math.max(lastPos, effectiveActualStart);
                    appendProduction(segStart, rdStart);
                }

                let color = 'bg-red-600';
                const isLast = rdIdx === runningDowntimesForJob.length - 1;
                let extraClass = isLast ? 'active-growing' : '';
                const typeLower = (rdItem.dtType || "").toLowerCase();
                if (typeLower === 'dandori') color = 'bg-amber-400';
                else if (typeLower === 'firstcheck' || typeLower === '1st_check') { color = 'bg-purple-500'; extraClass = isLast ? 'active-growing' : ''; }
                else if (typeLower === 'try out' || typeLower === 'tryout') color = 'bg-orange-500';
                else if (typeLower === 'break time' || typeLower === 'break') { color = 'bg-slate-500'; extraClass = isLast ? 'active-growing' : ''; }
                let displayType = rdItem.dtType || '';
                if (typeLower !== 'dandori' && typeLower !== 'firstcheck' && typeLower !== '1st_check' && typeLower !== 'try out' && typeLower !== 'tryout' && typeLower !== 'break time' && typeLower !== 'break') {
                    displayType = 'Downtime ' + displayType;
                    if (rdItem.problem && rdItem.problem !== '-' && rdItem.problem.toUpperCase() !== 'MENUNGGU PROSES MULAI (IDLE TIME)') {
                        displayType += ': ' + rdItem.problem;
                    }
                }

                const rdEnd = isLast ? finalTime : runningDowntimesForJob[rdIdx + 1].start.getTime();
                addSegment(rdStart, rdEnd, color + ' ' + extraClass, displayType);
                lastPos = rdEnd;
            });
        } else {
            if (finalTime > lastPos) {
                const segStart = (lastPos < effectiveActualStart) ? effectiveActualStart : lastPos;
                appendProduction(segStart, finalTime);
            }
        }

        if (html === '' && effectiveActualStart) {
            appendProduction(effectiveActualStart, finalTime);
        }

        container.innerHTML = html;
        let labelContainerId = containerId;
        if (containerId.includes('segments')) {
            labelContainerId = containerId.replace('segments', 'labels');
        } else if (containerId.includes('container')) {
            labelContainerId = containerId.replace('container', 'labels');
        }
        const labelContainer = document.getElementById(labelContainerId);
        if (labelContainer && labelContainer !== container) {
            labelContainer.innerHTML = labelsHtml;
        }

        const growing = container?.querySelector('.active-growing');
        if (growing) {
            let head = container.querySelector('.timeline-head');
            if (!head) {
                head = document.createElement('div');
                head.className = 'timeline-head absolute top-0 h-full w-1 bg-white z-50 shadow-[0_0_10px_#fff]';
                container.appendChild(head);
            }
            head.style.left = (parseFloat(growing.style.left) + parseFloat(growing.style.width)) + '%';
        }
    } catch (e) {
        console.error(`CRITICAL ENGINE ERROR [${jobId}]:`, e);
    }
}

window.enqueueJob = async function enqueueJob(id) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window._autoBreakActive) { showToast('Sedang break time, tidak bisa enqueue job.', 'warning'); return; }

    const activeId = window.ProductionConfig.currentActiveId;
    if (activeId && activeId == id) {
        const activeJob = window.jobMasterData?.[activeId];
        if (activeJob && activeJob.status === 'paused') {
            showToast('Item ini sedang break time / dijeda. Tidak bisa enqueue ulang.', 'warning');
            return;
        }
    }
    if (activeId && activeId != id) {
        const activeDtKeys = Object.keys(window.runningDowntimes || {}).filter(k => k.startsWith(activeId + '_'));
        const activeDt = activeDtKeys.length > 0 ? window.runningDowntimes[activeDtKeys[0]] : null;
        const dtLabel = activeDt ? (activeDt.dtType || activeDt.btnType || 'downtime').toUpperCase() : null;

        showConfirm(
            'Item Masih Diproses',
            `Item sebelumnya masih dalam proses${dtLabel ? ' (' + dtLabel + ')' : ''}. Yakin ingin memulai item baru?`,
            function(confirmed) {
                if (!confirmed) return;
                _doEnqueue(id);
            }
        );
        return;
    }

    _doEnqueue(id);
}

async function _doEnqueue(id) {
    await window.ActionRunner.run('Enqueue Job', async () => {
        const res = await fetch(`/operational/job/${id}/enqueue`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
                'Accept': 'application/json'
            }
        }).then(async res => {
            const text = await res.text();
            try { return JSON.parse(text); } catch (e) { throw new Error("Invalid JSON"); }
        });

        if (res.success) {
            showToast('Job berhasil dimasukkan ke antrian!', 'success');
            notifyLineStatusChange(jobMasterData[id]?.line);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(res.message || 'Gagal memasukkan antrian', 'danger');
        }
    });
}

window.jsStartDandori = async function jsStartDandori(jobId) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window._autoBreakActive) { showToast('Sedang break time, tidak bisa start dandori.', 'warning'); return; }
    await window.ActionRunner.run('Start Dandori', async () => {
        const res = await fetch(`/operational/job/${jobId}/dandori/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ date: window.ProductionConfig.currentDate })
        }).then(r => r.json());

        if (res.success) {
            showToast('Dandori dimulai!', 'success');
            const dandoriStart = new Date();

            // Update local state — dandori is now running
            if (jobMasterData[jobId]) {
                jobMasterData[jobId].dandori_start = dandoriStart.toISOString();
                jobMasterData[jobId].status = 'running';
                if (!jobMasterData[jobId].started_at) {
                    jobMasterData[jobId].started_at = dandoriStart.toISOString();
                }
            }
            window.ProductionConfig.currentStatus = 'running';
            window.ProductionConfig.currentDowntimeCount = 1;

            // Server already closed all open downtime (idle etc.) at dandoriStart.
            // Remove ALL running downtimes for this job from local state.
            for (const k in (window.runningDowntimes || {})) {
                if (window.runningDowntimes[k].jobId == jobId) {
                    delete window.runningDowntimes[k];
                }
            }

            // Close local history entries at dandoriStart so they don't
            // span past and cover the timeline.
            const hist = window.jobDowntimeHistory[jobId];
            if (hist) {
                for (let i = 0; i < hist.length; i++) {
                    if (!hist[i].end) {
                        hist[i].end = dandoriStart.getTime();
                    }
                }
            }

            // Add the running dandori to runningDowntimes so the timeline
            // renders an amber dandori segment from now onward.
            const dt = res.downtime;
            if (dt && dt.id) {
                window.runningDowntimes[`${jobId}_dandori`] = {
                    id: dt.id,
                    start: dandoriStart,
                    jobId: jobId,
                    btnType: 'dandori',
                    dtType: 'dandori'
                };
            }

            // Reset the idle/downtime/tryout/break buttons to "start" state
            window.resetDowntimeButtons(jobId);

            updateTimeline();
            notifyLineStatusChange(jobMasterData[jobId]?.line);
            setTimeout(() => location.reload(), 300);
        } else {
            showToast(res.message || 'Gagal memulai dandori', 'danger');
        }
    });
}

window.jsStopDandori = async function jsStopDandori(jobId) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window._autoBreakActive) { showToast('Sedang break time, tidak bisa stop dandori.', 'warning'); return; }
    await window.ActionRunner.run('Stop Dandori', async () => {
        const res = await fetch(`/operational/job/${jobId}/dandori/finish`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
        }).then(r => r.json());

        if (res.success) {
            showToast('Dandori selesai!', 'success');
            const finishTime = new Date();

            if (jobMasterData[jobId]) {
                delete jobMasterData[jobId].dandori_start;
                jobMasterData[jobId].status = 'running';
                // Server sets started_at when dandori finishes → production starts
                if (!jobMasterData[jobId].started_at) {
                    jobMasterData[jobId].started_at = finishTime.toISOString();
                }
            }
            window.ProductionConfig.currentDowntimeCount = 0;

            // Remove dandori from running downtimes
            const rdKey = `${jobId}_dandori`;
            const rd = window.runningDowntimes?.[rdKey];
            if (rd) {
                // Add finished dandori to history so it renders in timeline
                if (!window.jobDowntimeHistory[jobId]) {
                    window.jobDowntimeHistory[jobId] = [];
                }
                window.jobDowntimeHistory[jobId].push({
                    start: rd.start.getTime(),
                    end: finishTime.getTime(),
                    type: 'dandori'
                });
                delete window.runningDowntimes[rdKey];
            }

            // Also clean up 1st check if active
            const fcKey = `${jobId}_firstcheck`;
            const fc = window.runningDowntimes?.[fcKey];
            if (fc) {
                if (!window.jobDowntimeHistory[jobId]) {
                    window.jobDowntimeHistory[jobId] = [];
                }
                window.jobDowntimeHistory[jobId].push({
                    start: fc.start.getTime(),
                    end: finishTime.getTime(),
                    type: '1st_check'
                });
                delete window.runningDowntimes[fcKey];
            }

            // Reset buttons
            window.resetDowntimeButtons(jobId);

            updateTimeline();
            notifyLineStatusChange(jobMasterData[jobId]?.line);
            setTimeout(() => location.reload(), 300);
        } else {
            showToast(res.message || 'Gagal mengakhiri dandori', 'danger');
        }
    });
}

window.jsToggleFirstCheck = function jsToggleFirstCheck(jobId) {
    if (window.runningDowntimes?.[`${jobId}_firstcheck`]) {
        jsStopDandori(jobId);
    } else {
        jsStartFirstCheck(jobId);
    }
}

async function jsStartFirstCheck(jobId) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window._autoBreakActive) { showToast('Sedang break time, tidak bisa start 1st check.', 'warning'); return; }
    await window.ActionRunner.run('Start 1st Check', async () => {
        const res = await fetch(`/operational/job/${jobId}/dandori/first-check/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ date: window.ProductionConfig.currentDate })
        }).then(r => r.json());

        if (res.success) {
            showToast('1st Check dimulai!', 'success');
            const firstCheckStart = new Date();

            if (jobMasterData[jobId]) {
                jobMasterData[jobId].first_check_start = firstCheckStart.toISOString();
            }

            // Track in running downtimes so UI knows it's active
            const d = res.dandori;
            if (d && d.id) {
                window.runningDowntimes[`${jobId}_firstcheck`] = {
                    id: d.id,
                    start: firstCheckStart,
                    jobId: jobId,
                    btnType: 'firstcheck',
                    dtType: '1st_check'
                };
            }

            window.resetDowntimeButtons(jobId);
            updateTimeline();
            setTimeout(() => location.reload(), 300);
        } else {
            showToast(res.message || 'Gagal memulai 1st Check', 'danger');
        }
    });
}

window.jsStopFirstCheck = async function jsStopFirstCheck(jobId) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window._autoBreakActive) { showToast('Sedang break time, tidak bisa stop 1st check.', 'warning'); return; }
    await window.ActionRunner.run('Stop 1st Check', async () => {
        const res = await fetch(`/operational/job/${jobId}/dandori/first-check/finish`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
        }).then(r => r.json());

        if (res.success) {
            showToast('1st Check selesai!', 'success');
            const finishTime = new Date();

            if (jobMasterData[jobId]) {
                delete jobMasterData[jobId].first_check_start;
            }

            // Add to history for timeline rendering
            const rdKey = `${jobId}_firstcheck`;
            const rd = window.runningDowntimes?.[rdKey];
            if (rd) {
                if (!window.jobDowntimeHistory[jobId]) {
                    window.jobDowntimeHistory[jobId] = [];
                }
                window.jobDowntimeHistory[jobId].push({
                    start: rd.start.getTime(),
                    end: finishTime.getTime(),
                    type: '1st_check'
                });
                delete window.runningDowntimes[rdKey];
            }

            window.resetDowntimeButtons(jobId);
            updateTimeline();
            setTimeout(() => location.reload(), 300);
        } else {
            showToast(res.message || 'Gagal mengakhiri 1st Check', 'danger');
        }
    });
}

// ——— DANDORI DOWNTIME (during dandori, tracks downtime without pausing dandori) ———
window.handleDandoriDowntime = function handleDandoriDowntime(jobId) {
    if (window.ActionRunner.locked) return;
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window._autoBreakActive) { showToast('Sedang break time, tidak bisa mulai downtime.', 'warning'); return; }
    const key = `${jobId}_dandori_dt`;
    if (window.runningDowntimes?.[key]) {
        _finishDandoriDowntime(jobId);
    } else {
        _startDandoriDowntime(jobId);
    }
}

async function _startDandoriDowntime(jobId) {
    await window.ActionRunner.run('Start Downtime', async () => {
        // ——— 1. PAUSE DANDORI: finish the active dandori downtime record on server ———
        const dandoriKey = `${jobId}_dandori`;
        const dandoriRd = window.runningDowntimes?.[dandoriKey];
        if (dandoriRd) {
            try {
                await fetch(`/operational/downtime/${dandoriRd.id}/finish`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
                }).then(r => r.json());
                if (!window.jobDowntimeHistory[jobId]) window.jobDowntimeHistory[jobId] = [];
                window.jobDowntimeHistory[jobId].push({
                    start: dandoriRd.start.getTime(), end: Date.now(), type: 'dandori'
                });
                delete window.runningDowntimes[dandoriKey];
            } catch (e) { console.warn('Pause dandori failed:', e); }
        }

        // ——— 2. FREEZE DANDORI TIMER in local state ———
        if (jobMasterData[jobId]) {
            const job = jobMasterData[jobId];
            let anchor = job.dandori_start ? new Date(job.dandori_start) : (job.started_at ? new Date(job.started_at) : null);
            let secs = job.base_seconds || 0;
            if (anchor) secs += Math.floor((Date.now() - anchor.getTime()) / 1000);
            job._frozenTimer = secs;
            job._dandoriPaused = true;
        }

        // ——— 3. START DOWNTIME on server ———
        const res = await fetch(`/operational/job/${jobId}/downtime/start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                jenis_downtime: 'downtime', problem: '-', penyebab: '', action: '',
                pic: window.ProductionConfig.userName || ''
            })
        }).then(r => r.json());

        if (res.success) {
            const startTime = new Date();
            window.runningDowntimes[`${jobId}_dandori_dt`] = {
                id: res.downtime.id, start: startTime, jobId: jobId,
                btnType: 'dandori_dt', dtType: 'downtime'
            };
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;

            // Button → STOP DOWNTIME
            const btn = document.getElementById(`dandori-dt-btn-${jobId}`);
            if (btn) {
                btn.className = btn.className.replace('bg-red-500/10', 'bg-red-500').replace('border-red-500/30', 'border-red-600').replace('text-red-400', 'text-white');
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg> STOP DOWNTIME';
            }

            // ——— 4. UPDATE STATUS BADGE → DOWNTIME ———
            const sc = document.getElementById('realtime-status-container');
            const st = document.getElementById('realtime-status-text');
            const sp = document.getElementById('realtime-status-ping');
            const sd = document.getElementById('realtime-status-dot');
            if (!window._origDandoriStatus) {
                window._origDandoriStatus = {
                    containerClass: sc?.className || '', textClass: st?.className || '',
                    textContent: st?.textContent || '', pingClass: sp?.className || '',
                    dotClass: sd?.className || ''
                };
            }
            if (sc) sc.className = window._origDandoriStatus.containerClass.replace(/bg-amber-500\/10/g, 'bg-rose-500/10').replace(/border-amber-500\/20/g, 'border-rose-500/20');
            if (st) { st.className = window._origDandoriStatus.textClass.replace(/text-amber-400/g, 'text-rose-400'); st.textContent = 'DOWNTIME'; }
            if (sp) sp.className = window._origDandoriStatus.pingClass.replace(/bg-amber-500/g, 'bg-rose-500');
            if (sd) sd.className = window._origDandoriStatus.dotClass.replace(/bg-amber-500/g, 'bg-rose-500');

            // ——— 5. UPDATE ALERT BOX → DOWNTIME ———
            const alertBox = document.getElementById('active-downtime-alert-box');
            const alertTitle = document.getElementById('active-downtime-title');
            if (alertBox) {
                alertBox.className = alertBox.className.replace(/bg-amber-500\/10/g, 'bg-red-500/10').replace(/border-amber-500\/30/g, 'border-red-500/30').replace(/bg-purple-500\/10/g, 'bg-red-500/10').replace(/border-purple-500\/30/g, 'border-red-500/30');
                alertBox.classList.remove('hidden');
            }
            if (alertTitle) {
                alertTitle.className = alertTitle.className.replace(/text-amber-500/g, 'text-red-500').replace(/text-purple-400/g, 'text-red-500');
                alertTitle.textContent = 'DOWNTIME';
            }

            showToast('Downtime dimulai (Dandori dijeda)', 'danger');
            updateTimeline();
            updateLostTimeDisplay(jobId);
            notifyLineStatusChange(jobMasterData[jobId]?.line);
        } else {
            showToast(res.message || 'Gagal memulai downtime', 'danger');
        }
    });
}

async function _finishDandoriDowntime(jobId) {
    await window.ActionRunner.run('Stop Downtime', async () => {
        const key = `${jobId}_dandori_dt`;
        const rd = window.runningDowntimes?.[key];
        if (!rd) return;

        // ——— 1. FINISH DOWNTIME on server ———
        const res = await fetch(`/operational/downtime/${rd.id}/finish`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
        }).then(r => r.json());

        if (res.success) {
            if (!window.jobDowntimeHistory[jobId]) window.jobDowntimeHistory[jobId] = [];
            const dt = res.downtime;
            if (dt) {
                window.jobDowntimeHistory[jobId].push({
                    start: new Date(dt.start_time).getTime(),
                    end: dt.finish_time ? new Date(dt.finish_time).getTime() : Date.now(),
                    type: dt.jenis_downtime
                });
            }
            delete window.runningDowntimes[key];
            window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes).length;

            // Button → DOWNTIME
            const btn = document.getElementById(`dandori-dt-btn-${jobId}`);
            if (btn) {
                btn.className = btn.className.replace('bg-red-500 ', 'bg-red-500/10 ').replace('border-red-600', 'border-red-500/30').replace('text-white', 'text-red-400');
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> DOWNTIME';
            }

            // ——— 2. RESTORE STATUS BADGE → DANDORI ———
            if (window._origDandoriStatus) {
                const sc = document.getElementById('realtime-status-container');
                const st = document.getElementById('realtime-status-text');
                const sp = document.getElementById('realtime-status-ping');
                const sd = document.getElementById('realtime-status-dot');
                if (sc) sc.className = window._origDandoriStatus.containerClass;
                if (st) { st.className = window._origDandoriStatus.textClass; st.textContent = window._origDandoriStatus.textContent; }
                if (sp) sp.className = window._origDandoriStatus.pingClass;
                if (sd) sd.className = window._origDandoriStatus.dotClass;
                delete window._origDandoriStatus;
            }

            // ——— 3. RESTORE ALERT BOX → DANDORI ———
            const alertBox = document.getElementById('active-downtime-alert-box');
            const alertTitle = document.getElementById('active-downtime-title');
            if (alertBox) {
                alertBox.className = alertBox.className.replace(/bg-red-500\/10/g, 'bg-amber-500/10').replace(/border-red-500\/30/g, 'border-amber-500/30');
            }
            if (alertTitle) {
                alertTitle.className = alertTitle.className.replace(/text-red-500/g, 'text-amber-500');
                alertTitle.textContent = 'Dandori (Persiapan)';
            }

            // ——— 4. RESUME DANDORI on server ———
            try {
                const dRes = await fetch(`/operational/job/${jobId}/dandori/start`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ date: window.ProductionConfig.currentDate })
                }).then(r => r.json());
                if (dRes.success && dRes.downtime) {
                    const t = new Date();
                    window.runningDowntimes[`${jobId}_dandori`] = {
                        id: dRes.downtime.id, start: t, jobId: jobId, btnType: 'dandori', dtType: 'dandori'
                    };
                    const job = jobMasterData[jobId];
                    if (job && job._dandoriPaused) {
                        if (job._frozenTimer != null) { job.base_seconds = job._frozenTimer; }
                        job.started_at = t.toISOString();
                        delete job._dandoriPaused;
                        delete job._frozenTimer;
                    }
                }
            } catch (e) { console.warn('Resume dandori failed:', e); }

            showToast('Downtime selesai, Dandori dilanjutkan', 'success');
            updateTimeline();
            updateLostTimeDisplay(jobId);
            notifyLineStatusChange(jobMasterData[jobId]?.line);
        } else {
            showToast(res.message || 'Gagal mengakhiri downtime', 'danger');
        }
    });
}

async function restartJob(id) {
    await window.ActionRunner.run('Restart Job', async () => {
        const res = await fetch(`/operational/job/${id}/start`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
        }).then(r => r.json());

        if (res.success) {
            notifyLineStatusChange(jobMasterData[id]?.line);
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(res.message || 'Gagal memulai ulang', 'danger');
        }
    });
}

window.finishJob = function finishJob(id, jobNumber, jobName) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    window.currentFinishId = id;
    showConfirm('Selesaikan Proses?', 'Apakah Anda yakin item ' + jobNumber + ' sudah selesai?', () => {
        closeConfirmModal();
        const titleEl = document.getElementById('finishJobTitle');
        if (titleEl) titleEl.innerText = jobNumber;
        const nameEl = document.getElementById('finishJobName');
        if (nameEl) nameEl.innerText = jobNumber + ' - ' + jobName;

        const nextSelect = document.getElementById('nextSelect');
        if (nextSelect) {
            nextSelect.innerHTML = `
                <option value="">AUTO – lanjut ke urutan berikutnya</option>
                <option value="FINISH_ONLY" style="color: #2563eb; font-weight: bold;">✅ Selesai Saja (Tanpa Lanjut Otomatis)</option>
                <option value="STOP_SESSION" style="color: #ef4444; font-weight: bold;">🛑 SEMENTARA: STOP SESI</option>
            `;
            // Populate from blade data instead of fetch — instant, no lag
            const currentJob = window.jobMasterData && window.jobMasterData[id];
            const currentLine = currentJob ? (currentJob.line || '').toUpperCase().replace('LINE ', '').replace('PRESS ', '') : '';
            const allJobs = window._pendingJobsData || [];
            const filtered = allJobs.filter(j => {
                if (j.id == id) return false;
                if (!currentLine) return true;
                const jobLine = (j.line || '').toUpperCase().replace('LINE ', '').replace('PRESS ', '');
                return jobLine === currentLine;
            });
            if (filtered.length > 0) {
                const group = document.createElement('optgroup');
                group.label = 'Pilih Item Spesifik';
                filtered.forEach(j => {
                    const opt = document.createElement('option');
                    opt.value = j.id;
                    const statusLabel = (j.status || '').toLowerCase() === 'paused' ? ' ⏸️' : (j.status || '').toLowerCase() === 'running' ? ' 🔄' : '';
                    opt.innerText = (j.job_name || '') + ' - ' + (j.job_number || '') + ' (' + (j.target_qty || 0) + ' pcs)' + statusLabel;
                    group.appendChild(opt);
                });
                nextSelect.appendChild(group);
            }
        }
        const okInput = document.getElementById('active-actual-' + id) || document.getElementById('actual-' + id);
        const repairInput = document.getElementById('active-repair-' + id) || document.getElementById('repair-' + id);
        const rejectInput = document.getElementById('active-reject-' + id) || document.getElementById('reject-' + id);

        const job = (window.jobMasterData && window.jobMasterData[id]) || {};

        const finalOkInput = document.getElementById('final-ok');
        const finalRepairInput = document.getElementById('final-repair');
        const finalRejectInput = document.getElementById('final-reject');

        if (finalOkInput) finalOkInput.value = job.actual_ok || 0;
        if (finalRepairInput) finalRepairInput.value = job.actual_repair || 0;
        if (finalRejectInput) finalRejectInput.value = job.actual_reject || 0;

        autoCloseJobDowntimes(id).then(() => {
            openFinishModal();
        });
    });
}

window.submitFinalJobWithNext = function (stopSession) {
    const nextSelect = document.getElementById('nextSelect');
    if (nextSelect) {
        if (stopSession) {
            nextSelect.value = 'STOP_SESSION';
        } else {
            // If it was STOP_SESSION, revert to default/AUTO (empty string)
            if (nextSelect.value === 'STOP_SESSION') {
                nextSelect.value = '';
            }
        }
    }
    submitFinalJob();
};

function autoCloseJobDowntimes(jobId) {
    const promises = [];
    if (!window.runningDowntimes) return Promise.resolve();
    for (let key in window.runningDowntimes) {
        const rd = window.runningDowntimes[key];
        if (rd.jobId == jobId) {
            promises.push(
                fetch(`/operational/downtime/${rd.id}/finish`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
                }).then(r => r.json()).then(res => {
                    if (res.success) {
                        if (rd.btnType === 'break') _clearBreakState(rd.jobId);
                        delete window.runningDowntimes[key];
                    }
                }).catch(() => {})
            );
        }
    }
    if (promises.length === 0) return Promise.resolve();
    showToast(`Menutup ${promises.length} downtime aktif...`, 'warning');
    return Promise.allSettled(promises).then(() => {
        window.ProductionConfig.currentDowntimeCount = Object.keys(window.runningDowntimes || {}).length;
    });
}

async function submitFinalJob() {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    const id = window.currentFinishId;
    const nextJobId = document.getElementById('nextSelect').value;
    if (!id) return;

    await window.ActionRunner.run('Submit Final Job', async () => {
        const finalOk = parseInt(document.getElementById('final-ok').value) || 0;
        const finalRepair = parseInt(document.getElementById('final-repair').value) || 0;
        const finalReject = parseInt(document.getElementById('final-reject').value) || 0;

        if (finalRepair > 0 || finalReject > 0) {
            showToast('⚠️ Pastikan data Repair & Reject sudah tercatat di history RR', 'warning');
        }

        let jobNo = '';
        const titleEl = document.getElementById('finishJobTitle');
        if (titleEl) {
            jobNo = titleEl.innerText || '';
        }
        if (!jobNo) {
            const rowEl = document.getElementById('row-' + id);
            if (rowEl) {
                jobNo = rowEl.getAttribute('data-job-number') || '';
            }
        }

        const res = await fetch(`/operational/job/${id}/finish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                next_job_id: nextJobId,
                skip_idle: false,
                ok_qty: finalOk,
                repair_qty: finalRepair,
                reject_qty: finalReject
            })
        }).then(r => r.json());

        if (res.success) {
            _clearBreakState(id);
            notifyLineStatusChange(jobMasterData[id]?.line);
            if (jobNo) {
                localStorage.setItem('lkh_completion_toast', jobNo);
            } else {
                localStorage.setItem('lkh_completion_toast', `#${id}`);
            }
            const card = document.getElementById('active-job-card');
            if (card) card.style.display = 'none';
            closeFinishModal();
            if (res.mismatch) {
                const m = res.mismatch;
                showToast(`Item ${m.job_no} tidak tercapai: ${m.actual_qty}/${m.plan_qty} — ${m.recovery_qty} pcs masuk recovery queue`, 'warning', 6000);
            } else {
                showToast('Pekerjaan selesai!', 'success');
            }
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res.message || 'Gagal menyelesaikan', 'danger');
        }
    });
}

window.handleQuickDowntime = function handleQuickDowntime(jobId, btnType, dtType) {
    if (window.ActionRunner.locked) return;
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window._autoBreakActive && btnType !== 'break') { showToast('Sedang break time, tidak bisa mulai downtime lain.', 'warning'); return; }
    var now = Date.now();
    let key = `${jobId}_${btnType}`;
    if (!window._lastDtClick) window._lastDtClick = {};
    if (window._lastDtClick[key] && (now - window._lastDtClick[key]) < 300) {
        let btn = document.getElementById(btnType + '-btn-' + jobId);
        if (btn) { btn.style.opacity = '0.5'; setTimeout(() => { if (btn) btn.style.opacity = ''; }, 200); }
        return;
    }
    window._lastDtClick[key] = now;
    if (runningDowntimes[key]) finishQuickDowntime(jobId, btnType, runningDowntimes[key].id);
    else startQuickDowntime(jobId, btnType, dtType);
}

async function startQuickDowntime(jobId, btnType, dtType) {
    await window.ActionRunner.run('Start ' + btnType, async () => {
        const res = await fetch(`/operational/job/${jobId}/downtime/start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                jenis_downtime: dtType,
                problem: '-',
                penyebab: '',
                action: '',
                pic: window.ProductionConfig.userName || ''
            })
        }).then(r => r.json());

        if (res.success) {
            const startTime = new Date();
            runningDowntimes[`${jobId}_${btnType}`] = { id: res.downtime.id, start: startTime, jobId: jobId, btnType: btnType, dtType: dtType };
            window.ProductionConfig.currentDowntimeCount = Object.keys(runningDowntimes).length;
            showToast(`${btnType.toUpperCase()} started`, 'danger');
            updateTimeline();
            updateLostTimeDisplay(jobId);
            notifyLineStatusChange(jobMasterData[jobId]?.line);

            // ——— BREAK PAUSE ——— Pause the runtime timer and call server pause
            if (btnType === 'break' && jobMasterData[jobId] && !jobMasterData[jobId]._breakPaused) {
                const job = jobMasterData[jobId];
                let jS = job.started_at ? new Date(job.started_at) : null;
                const firstDandori = job.dandori_start ? new Date(job.dandori_start) : null;
                const anchorTime = firstDandori || jS;
                let currentSeconds = job.base_seconds || 0;
                if (anchorTime) {
                    currentSeconds += Math.floor((Date.now() - anchorTime.getTime()) / 1000);
                }
                job._frozenTimer = currentSeconds;
                job._breakPaused = true;
                try {
                    await fetch(`/operational/job/${jobId}/pause`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
                    });
                } catch (e) {}
                // Persist break state for page reload
                try {
                    sessionStorage.setItem('prod_break_state', JSON.stringify({
                        jobId: jobId,
                        frozenTimer: currentSeconds,
                        downtimeId: res.downtime.id,
                        label: dtType.toUpperCase(),
                        startedAt: Date.now()
                    }));
                } catch (e) {}
            }
        }
    });
}

async function finishQuickDowntime(jobId, btnType, dtId) {
    await window.ActionRunner.run('Stop ' + btnType, async () => {
        const res = await fetch(`/operational/downtime/${dtId}/finish`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
        }).then(r => r.json());

        if (res.success) {
            delete runningDowntimes[`${jobId}_${btnType}`];
            window.ProductionConfig.currentDowntimeCount = Object.keys(runningDowntimes).length;
            resetDowntimeButtons(jobId);

            // Add completed downtime to local history so it is not lost visually
            if (!window.jobDowntimeHistory[jobId]) {
                window.jobDowntimeHistory[jobId] = [];
            }
            const dt = res.downtime;
            if (dt) {
                const startTs = new Date(dt.start_time).getTime();
                const endTs = dt.finish_time ? new Date(dt.finish_time).getTime() : new Date().getTime();
                window.jobDowntimeHistory[jobId].push({
                    start: startTs,
                    end: endTs,
                    type: dt.jenis_downtime
                });
            }

            showToast(`${btnType.toUpperCase()} stopped`, 'success');
            updateTimeline();
            updateLostTimeDisplay(jobId);
            notifyLineStatusChange(jobMasterData[jobId]?.line);
            if (btnType === 'downtime') openDowntimeReport(jobId, res.downtime);

            // ——— BREAK RESUME ——— Resume the runtime timer and call server resume
            if (btnType === 'break' && jobMasterData[jobId] && jobMasterData[jobId]._breakPaused) {
                const job = jobMasterData[jobId];
                if (job._frozenTimer != null) {
                    job.base_seconds = job._frozenTimer;
                    job.started_at = new Date().toISOString();
                }
                delete job._breakPaused;
                delete job._frozenTimer;
                try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}
                try {
                    await fetch(`/operational/job/${jobId}/resume`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
                    });
                } catch (e) {}
            }

            // ——— CLEANUP AUTO-BREAK STATE ——— Reset flags & hide overlay
            if (btnType === 'break') {
                if (window._autoBreakActive) {
                    window._autoBreakActive = false;
                    window._autoBreakDowntimeId = null;
                }
                window._autoBreakSkipped = true;
                _updateBreakUI(jobId, null, false);
            }
        }
    });
}

function resetDowntimeButtons(jobId) {
    const configs = [
        { id: 'downtime', label: 'Downtime', bg: 'bg-red-500/10', border: 'border-red-500/30', text: 'text-red-400', hover: 'hover:bg-red-500' },
        { id: 'tryout', label: 'Try Out', bg: 'bg-orange-500/10', border: 'border-orange-500/30', text: 'text-orange-400', hover: 'hover:bg-orange-500' },
        { id: 'break', label: 'Break', bg: 'bg-slate-500/10', border: 'border-slate-500/30', text: 'text-slate-400', hover: 'hover:bg-slate-500' }
    ];
    configs.forEach(c => {
        const btn = document.getElementById(`${c.id}-btn-${jobId}`);
        if (btn) {
            btn.innerHTML = c.label;
            btn.className = `py-2 rounded-lg ${c.bg} border ${c.border} ${c.text} text-[9px] font-black uppercase ${c.hover} hover:text-white transition-all`;
        }
    });
}

window.openDowntimeReport = function openDowntimeReport(jobId, dt) {
    try {
        window.currentDtJobId = jobId;
        const row = document.getElementById('row-' + jobId);
        const dtJobNumber = document.getElementById('dtJobNumber');
        if (row && dtJobNumber) dtJobNumber.innerText = row.getAttribute('data-job-number');

        const dtFormTitle = document.getElementById('dtFormTitle');
        const dtBtnText = document.getElementById('dtBtnText');
        if (dtFormTitle) dtFormTitle.innerText = 'Lengkapi Detail Masalah';
        if (dtBtnText) dtBtnText.innerText = 'Simpan Laporan';

        const formArea = document.getElementById('dtFormTitle')?.closest('.bg-gray-50');
        if (dt && dt.id) {
            const dtEditId = document.getElementById('dtEditId');
            const dtJenis = document.getElementById('dtJenis');
            const dtProblem = document.getElementById('dtProblem');
            const dtPenyebab = document.getElementById('dtPenyebab');
            const dtAction = document.getElementById('dtAction');
            const dtPIC = document.getElementById('dtPIC');
            if (dtEditId) dtEditId.value = dt.id;
            if (dtJenis) dtJenis.value = dt.jenis_downtime;
            if (dtProblem) {
                let problemVal = dt.problem || '';
                if (problemVal.trim() === '-' || problemVal.includes('(Shortcut)')) {
                    problemVal = '';
                }
                dtProblem.value = problemVal;
            }
            if (dtPenyebab) dtPenyebab.value = dt.penyebab || '';
            if (dtAction) dtAction.value = dt.action || '';
            if (dtPIC) dtPIC.value = dt.pic || window.ProductionConfig.userName || '';
            if (formArea) formArea.classList.remove('hidden');
        }

        const modal = document.getElementById('downtimeModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        loadDowntimes(jobId);
    } catch (e) {
        console.error('openDowntimeReport error:', e);
    }
}

function editDowntimeFromIndex(index) {
    const dt = window.currentDowntimesList[index];
    if (dt) {
        // Load the data into form inputs
        document.getElementById('dtEditId').value = dt.id;
        document.getElementById('dtJenis').value = dt.jenis_downtime;

        let problemVal = dt.problem || '';
        if (problemVal.trim() === '-' || problemVal.includes('(Shortcut)')) {
            problemVal = '';
        }
        document.getElementById('dtProblem').value = problemVal;
        document.getElementById('dtPenyebab').value = dt.penyebab || '';
        document.getElementById('dtAction').value = dt.action || '';
        document.getElementById('dtPIC').value = dt.pic || window.ProductionConfig.userName || '';

        // Show the form area
        const formArea = document.getElementById('dtFormTitle').closest('.bg-gray-50');
        if (formArea) formArea.classList.remove('hidden');

        // Change button text to "Update Laporan" or similar
        document.getElementById('dtFormTitle').innerText = 'Edit Detail Masalah';
        document.getElementById('dtBtnText').innerText = 'Simpan Perubahan';
    }
}

function saveDowntime() {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    const id = document.getElementById('dtEditId').value;
    let data = {
        jenis_downtime: document.getElementById('dtJenis').value,
        problem: document.getElementById('dtProblem').value,
        penyebab: document.getElementById('dtPenyebab').value,
        action: document.getElementById('dtAction').value,
        pic: document.getElementById('dtPIC').value
    };
    fetch(`/operational/downtime/${id}/update`, {
        method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken },
        body: JSON.stringify(data)
    }).then(() => {
        showToast('Laporan disimpan', 'success');
        loadDowntimes(window.currentDtJobId);
        const formArea = document.getElementById('dtFormTitle').closest('.bg-gray-50');
        if (formArea) formArea.classList.add('hidden');
        const line = jobMasterData?.[window.currentDtJobId]?.line || window.ProductionConfig?.currentLine;
        if (line) notifyLineStatusChange(line);
    });
}

function deleteDowntime(id) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (!confirm("Hapus catatan downtime?")) return;
    fetch(`/operational/downtime/${id}/delete`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
    }).then(res => res.json()).then(res => {
        if (res.success) {
            showToast('Downtime dihapus', 'success');
            loadDowntimes(window.currentDtJobId);
        }
    });
}

function renderDowntimeTable() {
    const filteredData = window.currentDowntimesList || [];
    const activeFilter = window.dtActiveFilter || 'downtime';
    const displayData = activeFilter === 'all' ? filteredData : filteredData.filter(dt => {
        const type = dt.jenis_downtime.toLowerCase();
        if (activeFilter === 'downtime') {
            return !['try out', 'tryout', 'break time'].some(v => type.includes(v));
        }
        if (activeFilter === 'try out') {
            return type.includes('try out') || type === 'tryout';
        }
        if (activeFilter === 'break time') {
            return type.includes('break time');
        }
        return type === activeFilter;
    });

    const hasMissing = filteredData.some(dt => !dt.problem || dt.problem === '-' || dt.problem.includes('(Shortcut)'));
    const listAlert = document.getElementById('dtListAlertBanner');
    if (listAlert) {
        if (hasMissing) listAlert.classList.remove('hidden');
        else listAlert.classList.add('hidden');
    }

    const body = document.getElementById('downtimeListBody');
    body.innerHTML = displayData.map((dt) => {
        const originalIndex = filteredData.indexOf(dt);
        let start = new Date(dt.start_time).getHours().toString().padStart(2, '0') + ':' + new Date(dt.start_time).getMinutes().toString().padStart(2, '0');
        let end = dt.finish_time ? new Date(dt.finish_time).getHours().toString().padStart(2, '0') + ':' + new Date(dt.finish_time).getMinutes().toString().padStart(2, '0') : '--:--';
        let dur = dt.duration_seconds || 0;
        let durStr = dur >= 60 ? Math.floor(dur / 60) + 'm ' + (dur % 60) + 's' : dur + 's';
        const isMissingDetail = !dt.problem || dt.problem === '-' || dt.problem.includes('(Shortcut)');

        return `<tr class="hover:bg-slate-50 transition-colors ${isMissingDetail ? 'bg-red-50/50' : ''}">
                <td class="px-4 py-3 font-bold uppercase text-[10px] text-slate-700">${['downtime','try out','break time'].includes(dt.jenis_downtime.toLowerCase()) ? '' : dt.jenis_downtime}</td>
                <td class="px-4 py-3 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="font-bold ${isMissingDetail ? 'text-red-600' : 'text-slate-800'}">${dt.problem || '-'}</div>
                        ${isMissingDetail ? `<span class="px-1.5 py-0.5 rounded bg-red-100 text-red-600 font-black text-[8px] uppercase tracking-wider animate-pulse flex items-center gap-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Belum Lengkap
                        </span>` : ''}
                    </div>
                    <div class="text-[10px] text-slate-500 uppercase">${dt.penyebab || '-'}</div>
                </td>
                <td class="px-4 py-3 text-xs font-medium text-slate-600">${dt.pic || '-'}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-600">${start} - ${end}</td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded bg-red-50 text-red-600 font-bold text-[10px]">${durStr}</span></td>
                <td class="px-4 py-3 text-center flex items-center justify-center gap-1">
                    <button onclick="editDowntimeFromIndex(${originalIndex})" class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                </td>
            </tr>`;
    }).join('');
}

function loadDowntimes(jobId) {
    fetch(`/operational/job/${jobId}/downtimes`).then(res => res.json()).then(data => {
        const filteredData = data.filter(dt => dt.jenis_downtime.toLowerCase() !== 'dandori');
        window.currentDowntimesList = filteredData;
        renderDowntimeTable();
    });
}

function filterDowntimeList(type) {
    window.dtActiveFilter = type;
    document.querySelectorAll('.filter-dt-btn').forEach(btn => {
        if (btn.dataset.filter === type) {
            btn.className = 'filter-dt-btn px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all bg-red-600 text-white shadow-sm';
        } else {
            btn.className = 'filter-dt-btn px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all bg-gray-100 text-gray-500 hover:bg-gray-200';
        }
    });
    renderDowntimeTable();
}

function closeDowntimeModal() {
    try {
        const modal = document.getElementById('downtimeModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    } catch (e) {
        console.error('closeDowntimeModal error:', e);
    }
}

function saveJob(id, source) {
    if (window.ActionRunner.locked) return;
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }

    let actualEl = document.getElementById('actual-' + id);
    let activeActualEl = document.getElementById('active-actual-' + id);
    let repairEl = document.getElementById('repair-' + id);
    let activeRepairEl = document.getElementById('active-repair-' + id);
    let rejectEl = document.getElementById('reject-' + id);
    let activeRejectEl = document.getElementById('active-reject-' + id);

    let newActual = (actualEl ? (parseInt(actualEl.value) || 0) : 0) + (activeActualEl ? (parseInt(activeActualEl.value) || 0) : 0);

    if (source !== 'actual') {
        let repairDelta = (repairEl ? (parseInt(repairEl.value) || 0) : 0) + (activeRepairEl ? (parseInt(activeRepairEl.value) || 0) : 0);
        let rejectDelta = (rejectEl ? (parseInt(rejectEl.value) || 0) : 0) + (activeRejectEl ? (parseInt(activeRejectEl.value) || 0) : 0);

        if (repairDelta > 0) {
            window.openRRInputModal(id, 'repair', repairDelta);
            if (repairEl) repairEl.value = '';
            if (activeRepairEl) activeRepairEl.value = '';
            return;
        }

        if (rejectDelta > 0) {
            window.openRRInputModal(id, 'reject', rejectDelta);
            if (rejectEl) rejectEl.value = '';
            if (activeRejectEl) activeRejectEl.value = '';
            return;
        }
    }

    if (newActual < 0) {
        showToast('Nilai OK tidak boleh negatif', 'danger');
        return;
    }
    if (newActual === 0) return;

    let currentActual = jobMasterData[id]?.actual_ok || 0;
    let actualDelta = newActual - currentActual;

    if (jobMasterData[id]) {
        jobMasterData[id].actual_ok = newActual;
        updateTimeline();
    }

    performSave(id, actualDelta, 0, 0);

    // Set row input to current value, clear active board input
    if (actualEl) actualEl.value = jobMasterData[id]?.actual_ok || '';
    if (activeActualEl) activeActualEl.value = '';
}

function saveActiveJob() {
    const id = window.ProductionConfig.currentActiveId;
    if (id) saveJob(id);
}

async function performSave(id, ok, repair, reject) {
    await window.ActionRunner.run('Save Log', async () => {
        let success = false;
        try {
            const data = await fetch(`/operational/job/${id}/save-log`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ ok_qty: ok, repair_qty: repair, reject_qty: reject, date: window.ProductionConfig.currentDate })
            }).then(r => r.json());
            success = data.success;

            if (data.success) {
                showToast('Data Production Log Saved!', 'success');
                window.ProductionConfig.lastInputAt = new Date().toISOString();
                if (data.runtime_seconds != null && jobMasterData[id]) {
                    jobMasterData[id].base_seconds = parseInt(data.runtime_seconds);
                }
                if (data.total_ok != null && jobMasterData[id]) {
                    jobMasterData[id].actual_ok = parseInt(data.total_ok);
                    const el = document.getElementById('active-actual-display');
                    if (el) el.textContent = data.total_ok;
                    const rowInput = document.getElementById('actual-' + id);
                    if (rowInput) rowInput.value = data.total_ok;
                }
                if (data.log) {
                    const grid = document.getElementById('rekam-jejak-grid');
                    if (grid) {
                        const now = new Date().toLocaleTimeString('en-GB', { hour12: false });
                        const card = document.createElement('div');
                        card.className = 'bg-slate-800/30 border border-slate-700/50 p-3 rounded-xl flex items-center justify-between group hover:border-blue-500/50 transition-all';
                        card.innerHTML = '<div><p class="text-[8px] font-black text-slate-500 uppercase leading-none mb-1">' + now + '</p><p class="text-xs font-black text-white leading-none">OK: ' + (data.log.ok || 0) + ' <span class="text-slate-500">|</span> <span class="text-orange-400">R: ' + (data.log.repair || 0) + '</span> <span class="text-slate-500">|</span> <span class="text-red-400">X: ' + (data.log.reject || 0) + '</span></p></div><div class="w-2 h-2 rounded-full bg-blue-500/20 group-hover:bg-blue-500 transition-colors"></div>';
                        grid.prepend(card);
                        while (grid.children.length > 20) {
                            grid.removeChild(grid.lastChild);
                        }
                    }
                }
                notifyLineStatusChange(jobMasterData[id]?.line);
            }
        } finally {
            if (!success && jobMasterData[id]) {
                jobMasterData[id].actual_ok -= ok;
                updateTimeline();
            }
        }
    });
}

function showConfirm(title, text, callback) {
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmText').innerText = text;
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    // Animate content scale & opacity
    setTimeout(() => {
        const content = document.getElementById('confirmContent');
        if (content) {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }
    }, 10);
    document.getElementById('confirmBtn').onclick = callback;
}
function closeConfirmModal() {
    const content = document.getElementById('confirmContent');
    if (content) {
        content.classList.add('scale-95', 'opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
    }
    setTimeout(() => {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }, 150);
}
function openFinishModal() {
    try {
        const modal = document.getElementById('finishModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    } catch (e) {
        console.error('openFinishModal error:', e);
    }
}
function closeFinishModal() {
    try {
        var m = document.getElementById('finishModal');
        if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
    } catch (e) {
        console.error('closeFinishModal error:', e);
    }
}

function showToast(m, t, dur) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    const colorMap = { danger: 'red-600', info: 'blue-600', warning: 'amber-500', success: 'green-600' };
    toast.className = `fixed top-5 right-5 z-[9999] min-w-[260px] px-5 py-3 rounded-xl shadow-2xl text-white font-medium transition-all duration-300 transform bg-${colorMap[t] || 'green-600'}`;
    toast.innerText = m;
    toast.classList.remove('hidden', 'opacity-0');
    const ms = dur || 2500;
    setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.classList.add('hidden'), 300); }, ms);
}

window.toggleCustomSelect = function toggleCustomSelect() {
    const menu = document.getElementById('custom-select-menu');
    if (menu) menu.classList.toggle('hidden');
}

window.selectCustomItem = function selectCustomItem(id, label) {
    const select = document.getElementById('standby-job-select');
    if (select) select.value = id;
    const lbl = document.getElementById('custom-select-label');
    if (lbl) { lbl.innerText = label; lbl.classList.add('text-white', 'font-bold'); }
    toggleCustomSelect();
}

function checkSyncStatus() {
    const config = window.ProductionConfig;
    const id = config.currentActiveId;
    if (!id) return;
    if (window.ActionRunner && window.ActionRunner.locked) return;
    const lastInput = config.lastInputAt;
    if (lastInput) {
        const age = (Date.now() - new Date(lastInput).getTime()) / 1000;
        if (age < 10) return;
    }

    fetch(`/operational/job/${id}/sync?date=${config.currentDate || ''}`, {
        headers: { 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
        const job = jobMasterData[id];
        if (!job) return;

        if (data.status === 'complete' || data.status === 'finished') {
            if (window._autoBreakActive || job._breakPaused) _clearBreakState(id);
            return;
        }

        const serverOk = parseInt(data.qty.actual_ok) || 0;
        const serverRepair = parseInt(data.qty.actual_repair) || 0;
        const serverReject = parseInt(data.qty.actual_reject) || 0;
        if (job.actual_ok !== serverOk) {
            job.actual_ok = serverOk;
            const el = document.getElementById('active-actual-display');
            if (el) el.textContent = serverOk;
            const rowInput = document.getElementById('actual-' + id);
            if (rowInput) rowInput.value = serverOk;
        }
        if (job.actual_repair !== serverRepair) {
            job.actual_repair = serverRepair;
            const el = document.getElementById('active-repair-display');
            if (el) el.textContent = serverRepair;
        }
        if (job.actual_reject !== serverReject) {
            job.actual_reject = serverReject;
            const el = document.getElementById('active-reject-display');
            if (el) el.textContent = serverReject;
        }

        const serverDown = data.downtime;
        const clientBreak = window.runningDowntimes?.[`${id}_break`];

        if (serverDown && serverDown.jenis_downtime === 'break time' && !clientBreak && !window._autoBreakActive) {
            window._autoBreakActive = true;
            window._autoBreakDowntimeId = serverDown.id;
            const serverBreakWindow = _isInBreakWindow(new Date());
            window._autoBreakEndMin = serverBreakWindow ? serverBreakWindow.endMin : null;
            window.runningDowntimes[`${id}_break`] = {
                id: serverDown.id,
                start: new Date(serverDown.start_time),
                jobId: id,
                btnType: 'break',
                dtType: 'break time',
                problem: 'AUTO BREAK'
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
            _updateBreakUI(id, 'BREAK TIME', true);
        } else if (!serverDown && clientBreak && window._autoBreakActive) {
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
            try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}
            if (job) {
                if (job._breakPaused && job._frozenTimer != null) {
                    job.base_seconds = job._frozenTimer;
                    job.started_at = new Date().toISOString();
                }
                delete job._breakPaused;
                delete job._frozenTimer;
                job.status = 'running';
                window.ProductionConfig.currentStatus = 'running';
            }
            _updateBreakUI(id, null, false);
        }

        updateTimeline();
        _saveJobStateToStorage();
    }).catch(() => {});
}

function _saveJobStateToStorage() {
    try {
        const config = window.ProductionConfig;
        const id = config?.currentActiveId;
        if (!id) return;
        const job = window.jobMasterData?.[id];
        if (!job) return;
        localStorage.setItem('prod_active_state', JSON.stringify({
            id: id,
            status: job.status,
            actual_ok: job.actual_ok || 0,
            actual_repair: job.actual_repair || 0,
            actual_reject: job.actual_reject || 0,
            started_at: job.started_at,
            base_seconds: job.base_seconds || 0,
            tpt: job.tpt || 0,
            plan_start: job.plan_start,
            plan_end: job.plan_end,
            dandori_start: job.dandori_start,
            first_dandori_start: job.first_dandori_start,
            line: job.line || '',
            target_qty: job.target_qty || 0,
            ts: Date.now()
        }));
    } catch (e) {}
}

function _restoreJobStateFromStorage() {
    try {
        const raw = localStorage.getItem('prod_active_state');
        if (!raw) return false;
        const cached = JSON.parse(raw);
        if (!cached || !cached.id) return false;
        if (Date.now() - (cached.ts || 0) > 300000) return false;
        const config = window.ProductionConfig;
        if (!config || config.currentActiveId != cached.id) return false;
        const job = window.jobMasterData?.[cached.id];
        if (!job) return false;
        job.status = cached.status || job.status;
        job.actual_ok = cached.actual_ok ?? job.actual_ok;
        job.actual_repair = cached.actual_repair ?? job.actual_repair;
        job.actual_reject = cached.actual_reject ?? job.actual_reject;
        if (cached.started_at) job.started_at = cached.started_at;
        job.base_seconds = cached.base_seconds ?? job.base_seconds;
        if (cached.dandori_start) job.dandori_start = cached.dandori_start;
        if (cached.first_dandori_start) job.first_dandori_start = cached.first_dandori_start;
        return true;
    } catch (e) { return false; }
}

window.stepInput = function (id, amount, jobId = null) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    if (window.ActionRunner.locked) { showToast('Proses sebelumnya masih berjalan, harap tunggu...', 'warning'); return; }
    const input = document.getElementById(id);
    if (input) {
        window.ProductionConfig.lastInputAt = new Date().toISOString(); // Reset inactivity timer!

        const isRepair = id.includes('repair');
        const isReject = id.includes('reject');

        // Repair/Reject: buka modal input detail
        if (isRepair || isReject) {
            const targetJobId = jobId || id.split('-').pop();
            const type = isRepair ? 'repair' : 'reject';
            window.openRRInputModal(targetJobId, type, amount);
        } else {
            const targetJobId = jobId || id.split('-').pop();
            const current = jobMasterData[targetJobId]?.actual_ok || 0;
            const newVal = current + amount;
            if (newVal < 0) {
                showToast('Nilai OK tidak boleh negatif', 'danger');
                if (input) input.value = '';
                return;
            }
            if (amount === 0) {
                if (input) input.value = '';
                return;
            }
            const rowInput = document.getElementById('actual-' + targetJobId);
            if (jobMasterData[targetJobId]) {
                jobMasterData[targetJobId].actual_ok = newVal;
                if (rowInput) rowInput.value = newVal;
                const activeDisplay = document.getElementById('active-actual-display');
                if (activeDisplay) activeDisplay.textContent = newVal;
                updateTimeline();
            }
            performSave(targetJobId, amount, 0, 0);
        }
    }
};

window.manualInput = function (id, jobId = null) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    const input = document.getElementById(id);
    if (input) {
        window.ProductionConfig.lastInputAt = new Date().toISOString(); // Reset inactivity timer!

        const targetJobId = jobId || id.split('-').pop();
        const isRepair = id.includes('repair');
        const isReject = id.includes('reject');
        if (isRepair || isReject) {
            const type = isRepair ? 'repair' : 'reject';
            const val = parseInt(input.value) || 0;
            if (val > 0) {
                window.openRRInputModal(targetJobId, type, val);
                input.value = '';
            }
        } else {
            saveJob(targetJobId, 'actual');
        }
    }
};

window.manualStep = function (id, inputId, jobId) {
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }
    const input = document.getElementById(inputId);
    if (input) {
        const value = parseInt(input.value);
        if (value && value > 0) {
            stepInput(id, value, jobId);
            input.value = '';
        }
    }
};

// Expose functions globally for inline HTML onclick handlers
window.enqueueJob = enqueueJob;
window.jsStartDandori = jsStartDandori;
window.jsStopDandori = jsStopDandori;
window.jsStartFirstCheck = jsStartFirstCheck;
window.jsStopFirstCheck = jsStopFirstCheck;
window.jsToggleFirstCheck = jsToggleFirstCheck;
window.restartJob = restartJob;
window.finishJob = finishJob;
window.submitFinalJob = submitFinalJob;
window.handleQuickDowntime = handleQuickDowntime;
window.startQuickDowntime = startQuickDowntime;
window.finishQuickDowntime = finishQuickDowntime;
window.resetDowntimeButtons = resetDowntimeButtons;
window.openDowntimeReport = openDowntimeReport;
window.saveDowntime = saveDowntime;
window.deleteDowntime = deleteDowntime;
window.loadDowntimes = loadDowntimes;
window.editDowntimeFromIndex = editDowntimeFromIndex;
window.closeDowntimeModal = closeDowntimeModal;
window.filterDowntimeList = filterDowntimeList;
window.saveJob = saveJob;
window.saveActiveJob = saveActiveJob;
window.showConfirm = showConfirm;
window.closeConfirmModal = closeConfirmModal;
window.openFinishModal = openFinishModal;
window.closeFinishModal = closeFinishModal;
window.showToast = showToast;
window.toggleCustomSelect = toggleCustomSelect;
window.selectCustomItem = selectCustomItem;
window.resetTimelineMarkers = function () {
    try {
        window.activeTimelineMarkerId = null;
        document.querySelectorAll('[id$="-s"], [id$="-e"]').forEach(el => {
            el.classList.add('opacity-40');
            el.classList.remove('opacity-100', 'z-[60]');
        });
    } catch (e) {
        console.error("Error resetting markers:", e);
    }
};

window.showTimelineTooltip = function (event, label, sT, eT, durStr, headerColor, tooltipId) {
    try {
        // Reset previous highlights first
        window.resetTimelineMarkers();

        // Save current active marker ID so it persists across 1s re-renders
        window.activeTimelineMarkerId = tooltipId;

        // Set start time for ticking if it is a running segment
        if (event && event.currentTarget && event.currentTarget.dataset && event.currentTarget.dataset.runningSegment === "true") {
            window.activeTimelineStart = Number(event.currentTarget.dataset.start);
        } else {
            window.activeTimelineStart = null;
        }

        const tooltip = document.getElementById('timeline-tooltip');
        if (!tooltip) return;

        const typeEl = document.getElementById('tooltip-type');
        const timeEl = document.getElementById('tooltip-time');
        const durEl = document.getElementById('tooltip-dur');

        if (typeEl) {
            typeEl.innerText = label;
            typeEl.className = `text-[9px] font-black uppercase tracking-widest ${headerColor}`;
        }

        if (timeEl) timeEl.innerText = `${sT} - ${eT}`;
        if (durEl) durEl.innerText = durStr;

        tooltip.classList.remove('hidden');
        tooltip.style.opacity = '1';

        const rect = event.currentTarget.getBoundingClientRect();
        const tooltipWidth = tooltip.offsetWidth;
        const tooltipHeight = tooltip.offsetHeight;

        // Position fixed relative to viewport
        const left = rect.left + (rect.width / 2) - (tooltipWidth / 2);
        const top = rect.top - tooltipHeight - 12;

        tooltip.style.left = Math.max(10, Math.min(left, window.innerWidth - tooltipWidth - 10)) + 'px';
        tooltip.style.top = Math.max(10, top) + 'px';
    } catch (e) {
        console.error("Error showing tooltip:", e);
    }
};

window.hideTimelineTooltip = function () {
    try {
        window.activeTimelineStart = null;
        const tooltip = document.getElementById('timeline-tooltip');
        if (tooltip) {
            tooltip.classList.add('hidden');
            tooltip.style.opacity = '0';
        }
    } catch (e) {
        console.error("Error hiding tooltip:", e);
    }
};

window.showTargetTooltip = function (event, id) {
    try {
        const job = (window.jobMasterData && window.jobMasterData[id]) || {};
        const ok = parseInt(job.actual_ok) || 0;
        const repair = parseInt(job.actual_repair) || 0;
        const reject = parseInt(job.actual_reject) || 0;
        const target = parseInt(job.target_qty) || 0;
        const total = ok + repair + reject;
        const pct = target > 0 ? Math.round((total / target) * 100) : 0;

        const formatTime = (ts) => {
            if (!ts) return '--:--';
            const d = new Date(ts);
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        };

        const label = "Production Target Progress";
        const headerColor = "text-emerald-400 font-black";

        const sT = "Shift Schedule";
        const eT = `${formatTime(job.plan_start)} - ${formatTime(job.plan_end)}`;
        const durStr = `OK: ${ok.toLocaleString()} | Repair: ${repair.toLocaleString()} | Reject: ${reject.toLocaleString()} (Total: ${total.toLocaleString()} / ${target.toLocaleString()} PCS - ${pct}%)`;
        const tooltipId = 'target-tooltip-' + id;

        window.showTimelineTooltip(event, label, sT, eT, durStr, headerColor, tooltipId);
    } catch (e) {
        console.error(e);
    }
};

window.showActiveTargetTooltip = function (event) {
    try {
        const id = window.ProductionConfig.currentActiveId;
        if (!id) return;
        const job = (window.jobMasterData && window.jobMasterData[id]) || {};

        const ok = parseInt(job.actual_ok) || 0;
        const repair = parseInt(job.actual_repair) || 0;
        const reject = parseInt(job.actual_reject) || 0;
        const total = ok + repair + reject;
        const target = parseInt(job.target_qty) || 0;
        const pct = target > 0 ? Math.round((total / target) * 100) : 0;

        const formatTime = (ts) => {
            if (!ts) return '--:--';
            const d = new Date(ts);
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        };

        const label = "Target PPC Schedule";
        const headerColor = "text-emerald-400 font-black";
        const sT = `Plan: ${formatTime(job.plan_start)} - ${formatTime(job.plan_end)}`;
        const eT = `Act: ${formatTime(job.started_at)} - ${formatTime(job.finished_at)}`;
        const durStr = `OK: ${ok.toLocaleString()} | Rep: ${repair.toLocaleString()} | Rej: ${reject.toLocaleString()} | Target: ${target.toLocaleString()} (${pct}%)`;
        const tooltipId = 'active-target-tooltip';

        window.showTimelineTooltip(event, label, sT, eT, durStr, headerColor, tooltipId);
    } catch (e) {
        console.error(e);
    }
};

console.log("PRODUCTION ENGINE LOADED");

function initProductionEngine() {
    try {
        console.log("INITIALIZING PRODUCTION ENGINE...");
        window.freezeTimers = false;

        // Check for persistent LKH completion toast on reload
        const completedItem = localStorage.getItem('lkh_completion_toast');
        if (completedItem) {
            localStorage.removeItem('lkh_completion_toast');
            setTimeout(() => {
                showToast(`Data berhasil dimasukkan ke LKH! Baru update data yang masuk: Item ${completedItem}`, 'success');
            }, 300);
        }

        // Temporary shortcut for testing: Ctrl + Shift + F to freeze/unfreeze timers
        window.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'f') {
                window.freezeTimers = !window.freezeTimers;
                console.log("TIMERS FREEZE TOGGLED:", window.freezeTimers);
                if (window.showToast) {
                    window.showToast(window.freezeTimers ? "TESTING: TIMERS PAUSED (FROZEN)" : "TESTING: TIMERS RESUMED", "warning");
                }
            }
        });

        // Sync running downtimes from history (survives page refresh)
        // so quick-action buttons can stop server-created downtime (e.g. auto-idle).
        if (!window.runningDowntimes) window.runningDowntimes = {};
        (function syncRunningFromHistory() {
            const aid = window.ProductionConfig?.currentActiveId;
            if (!aid || !window.jobDowntimeHistory?.[aid]) return;
            const typeMap = { 'break time': 'break', 'try out': 'tryout', 'dandori': 'dandori' };
            for (const entry of window.jobDowntimeHistory[aid]) {
                if (entry.end || !entry.id) continue;
                const bt = typeMap[String(entry.type).toLowerCase()] || 'downtime';
                const key = `${aid}_${bt}`;
                if (window.runningDowntimes[key]) continue;
                window.runningDowntimes[key] = {
                    id: entry.id,
                    start: new Date(entry.start),
                    jobId: aid,
                    btnType: bt,
                    dtType: entry.type
                };
            }
        })();

        _restoreJobStateFromStorage();
        updateTimeline(true);
        updateTimers();
        setInterval(() => updateTimers(), 1000);
        setInterval(() => updateTimeline(false), 3000);
        setInterval(checkSyncStatus, 8000);

        window.addEventListener('beforeunload', function (e) {
            _saveJobStateToStorage();
            // Persist break state to sessionStorage for page reload recovery
            try {
                const activeId = window.ProductionConfig?.currentActiveId;
                if (activeId && window._autoBreakActive && window._autoBreakDowntimeId) {
                    const job = window.jobMasterData?.[activeId];
                    if (job && job._breakPaused && job._frozenTimer != null) {
                        sessionStorage.setItem('prod_break_state', JSON.stringify({
                            jobId: activeId,
                            frozenTimer: job._frozenTimer,
                            downtimeId: window._autoBreakDowntimeId,
                            endMin: window._autoBreakEndMin,
                            label: 'AUTO BREAK',
                            startedAt: Date.now()
                        }));
                    }
                }
            } catch (e) {}

            if (window.ActionRunner && window.ActionRunner.locked) {
                e.preventDefault();
                e.returnValue = 'Masih ada data yang belum tersimpan. Yakin mau tinggalkan halaman?';
            }
        });

        // Delegated timeline segment hover — replaces inline onmouseover/onmouseout
        document.addEventListener('mouseover', (e) => {
            const seg = e.target.closest('[data-ttid]');
            if (!seg) return;
            const ttid = seg.dataset.ttid;
            window.showTimelineTooltip(
                { currentTarget: seg },
                decodeURIComponent(seg.dataset.label),
                seg.dataset.st, seg.dataset.et, seg.dataset.dur, seg.dataset.hcolor, ttid
            );
            const sEl = document.getElementById(ttid + '-s');
            const eEl = document.getElementById(ttid + '-e');
            if (sEl) { sEl.classList.remove('opacity-40'); sEl.classList.add('opacity-100', 'z-[60]'); }
            if (eEl) { eEl.classList.remove('opacity-40'); eEl.classList.add('opacity-100', 'z-[60]'); }
        });

        document.addEventListener('mouseout', (e) => {
            const seg = e.target.closest('[data-ttid]');
            if (!seg) return;
            window.hideTimelineTooltip();
            if (!('ontouchstart' in window || navigator.maxTouchPoints > 0)) {
                window.resetTimelineMarkers();
            }
        });

        // Responsive UX: Automatically hide tooltip and reset markers when scrolling any container
        window.addEventListener('scroll', () => {
            if (window.hideTimelineTooltip) window.hideTimelineTooltip();
            if (window.resetTimelineMarkers) window.resetTimelineMarkers();
        }, true);
    } catch (e) {
        console.error("FAILED TO INITIALIZE PRODUCTION ENGINE:", e);
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initProductionEngine);
else initProductionEngine();

/* ==========================================================
   REPAIR & REJECT LIST — Loaded dynamically per active job
   ========================================================== */

window.loadRRList = function (jobId) {
    const container = document.getElementById('rr-list-container-' + jobId);
    if (!container) return;

    container.innerHTML = '<div class="col-span-full text-center py-4 text-xs text-slate-600 font-bold animate-pulse">Memuat data...</div>';

    fetch(`/operational/job/${jobId}/repair-reject`)
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('rr-count-badge-' + jobId);
            if (badge) {
                badge.textContent = data.length + ' entri';
                badge.classList.toggle('hidden', data.length === 0);
            }

            if (!data || data.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center py-4 text-xs text-slate-600 font-bold">Belum ada catatan Repair/Reject untuk job ini.</div>';
                return;
            }

            // Limit to at most 4 latest entries to keep the layout super clean
            const visibleData = data.slice(0, 4);

            container.innerHTML = visibleData.map(log => {
                const isRepair = log.type === 'repair';
                const color = isRepair ? 'orange' : 'red';
                return `
                <div class="bg-slate-800/30 border border-${color}-500/20 p-3 rounded-xl flex flex-col gap-1 group hover:border-${color}-500/50 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full ${isRepair ? 'bg-orange-500/20 text-orange-400' : 'bg-red-500/20 text-red-400'} border ${isRepair ? 'border-orange-500/30' : 'border-red-500/30'}">${log.type}</span>
                        <span class="text-[9px] font-mono text-slate-500">${log.time}</span>
                    </div>
                    <p class="text-xs font-black text-white leading-none truncate" title="${log.defect_name}">${log.defect_name}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-[10px] font-black ${isRepair ? 'text-orange-400' : 'text-red-400'}">Qty: ${log.qty_a}</span>
                        <span class="text-[9px] text-slate-500">${log.operator}</span>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(() => {
            container.innerHTML = '<div class="col-span-full text-center py-4 text-xs text-red-500 font-bold">Gagal memuat data R&R.</div>';
        });
};

window.openRRListModal = function (jobId) {
    // Just scroll to and highlight the R&R panel on same page
    const container = document.getElementById('rr-list-container-' + jobId);
    if (container) {
        container.closest('[class*="border-t"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        loadRRList(jobId);
    }
};

// Auto-load R&R list if active job panel exists
document.addEventListener('DOMContentLoaded', () => {
    // Find any rr-list-container on the page and auto-load
    document.querySelectorAll('[id^="rr-list-container-"]').forEach(el => {
        const jobId = el.id.replace('rr-list-container-', '');
        if (jobId) loadRRList(jobId);
    });

    // Initialize Drag & Drop zones
    window.setupRRDragAndDrop();
});

/* ==========================================================
   REPAIR & REJECT INPUT MODAL ACTIONS
   ========================================================== */

window.rrSelectedPartFiles = [];
window.rrSelectedToolingFiles = [];

// Client-side WebP Conversion and Compression
window.compressAndConvertToWebP = function (file, prefix) {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith('image/')) {
            reject(new Error("File is not an image"));
            return;
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                
                // Limit maximum dimension to 1280px to prevent heavy uploads
                let width = img.width;
                let height = img.height;
                const maxDim = 1280;
                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error("Canvas conversion to Blob failed"));
                        return;
                    }
                    const baseName = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                    const newFileName = `${prefix}_${baseName.replace(/[^a-zA-Z0-9_.-]/g, '_')}_${Date.now()}.webp`;
                    const convertedFile = new File([blob], newFileName, {
                        type: 'image/webp',
                        lastModified: Date.now()
                    });
                    resolve(convertedFile);
                }, 'image/webp', 0.75); // 0.75 quality is optimal for compression
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

window.setupRRDragAndDrop = function () {
    ['part', 'tooling'].forEach(cat => {
        const zoneId = cat === 'part' ? 'rrDragZonePart' : 'rrDragZoneTooling';
        const zone = document.getElementById(zoneId);
        if (!zone) return;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        // Add/remove hover styling
        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, () => {
                zone.classList.remove('border-gray-300', 'bg-slate-50/50');
                zone.classList.add('border-red-400', 'bg-slate-100/80');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, () => {
                zone.classList.remove('border-red-400', 'bg-slate-100/80');
                zone.classList.add('border-gray-300', 'bg-slate-50/50');
            }, false);
        });

        // Handle dropped files
        zone.addEventListener('drop', async (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                const targetArray = cat === 'part' ? window.rrSelectedPartFiles : window.rrSelectedToolingFiles;
                
                // Show a loading text or indicator inside preview container
                const containerId = cat === 'part' ? 'rrImagePreviewPart' : 'rrImagePreviewTooling';
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = '<span class="text-[10px] text-orange-500 font-bold uppercase animate-pulse">Memproses & mengompresi gambar...</span>';
                }

                for (let i = 0; i < files.length; i++) {
                    try {
                        const webpFile = await window.compressAndConvertToWebP(files[i], cat);
                        targetArray.push(webpFile);
                    } catch (err) {
                        console.error("Failed to convert dropped image to WebP: ", err);
                        targetArray.push(files[i]);
                    }
                }
                window.renderRRPreviews(cat);
            }
        }, false);
    });
};

window.openRRInputModal = function (jobId, type, qty) {
    try {
        const rrJobId = document.getElementById('rrJobId');
        const rrType = document.getElementById('rrType');
        const rrQty = document.getElementById('rrQty');
        if (rrJobId) rrJobId.value = jobId;
        if (rrType) rrType.value = type;
        if (rrQty) rrQty.value = qty;

        // Customize modal headers based on type
        const modalTitle = document.getElementById('rrModalTitle');
        const modalSubtitle = document.getElementById('rrModalSubtitle');
        const rrModalForm = document.getElementById('rrModalForm');
        const modalFormHeader = rrModalForm ? rrModalForm.previousElementSibling : null;

        if (type === 'repair') {
            if (modalTitle) modalTitle.innerText = 'Lapor Repair';
            if (modalSubtitle) modalSubtitle.innerText = 'Lengkapi data masalah Repair produksi';
            if (modalFormHeader) {
                modalFormHeader.className = 'px-8 py-6 bg-gradient-to-r from-orange-500 to-amber-600 text-white flex items-center justify-between shadow-lg';
            }
        } else {
            if (modalTitle) modalTitle.innerText = 'Lapor Reject';
            if (modalSubtitle) modalSubtitle.innerText = 'Lengkapi data masalah Reject produksi';
            if (modalFormHeader) {
                modalFormHeader.className = 'px-8 py-6 bg-gradient-to-r from-red-600 to-rose-700 text-white flex items-center justify-between shadow-lg';
            }
        }

        // Clear other fields
        const rrDefectName = document.getElementById('rrDefectName');
        const rrArea = document.getElementById('rrArea');
        const rrRootCause = document.getElementById('rrRootCause');
        const rrCountermeasure = document.getElementById('rrCountermeasure');
        if (rrDefectName) rrDefectName.value = '';
        if (rrArea) rrArea.value = '';
        if (rrRootCause) rrRootCause.value = '';
        if (rrCountermeasure) rrCountermeasure.value = '';

        // Clear Part and Tooling fields
        const rrImagesPart = document.getElementById('rrImagesPart');
        const rrCameraPart = document.getElementById('rrCameraPart');
        const rrImagePreviewPart = document.getElementById('rrImagePreviewPart');
        if (rrImagesPart) rrImagesPart.value = '';
        if (rrCameraPart) rrCameraPart.value = '';
        if (rrImagePreviewPart) rrImagePreviewPart.innerHTML = '';
        window.rrSelectedPartFiles = [];

        const rrImagesTooling = document.getElementById('rrImagesTooling');
        const rrCameraTooling = document.getElementById('rrCameraTooling');
        const rrImagePreviewTooling = document.getElementById('rrImagePreviewTooling');
        if (rrImagesTooling) rrImagesTooling.value = '';
        if (rrCameraTooling) rrCameraTooling.value = '';
        if (rrImagePreviewTooling) rrImagePreviewTooling.innerHTML = '';
        window.rrSelectedToolingFiles = [];

        // Set up drag and drop again
        setTimeout(() => {
            window.setupRRDragAndDrop();
        }, 50);

        // Show modal
        const modal = document.getElementById('repairRejectInputModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    } catch (e) {
        console.error('openRRInputModal error:', e);
    }
};

window.closeRRInputModal = function () {
    const modal = document.getElementById('repairRejectInputModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
};

window.previewRRImages = async function (event, category) {
    const files = event.target.files;
    if (!files || files.length === 0) return;
    
    event.target.disabled = true;

    // Show processing indicator
    const containerId = category === 'part' ? 'rrImagePreviewPart' : 'rrImagePreviewTooling';
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = '<span class="text-[10px] text-orange-500 font-bold uppercase animate-pulse">Memproses & mengompresi gambar...</span>';
    }

    const targetArray = category === 'part' ? window.rrSelectedPartFiles : window.rrSelectedToolingFiles;
    for (let i = 0; i < files.length; i++) {
        try {
            const webpFile = await window.compressAndConvertToWebP(files[i], category);
            targetArray.push(webpFile);
        } catch (e) {
            console.error("Failed to convert image to WebP: ", e);
            targetArray.push(files[i]);
        }
    }

    window.renderRRPreviews(category);
    
    event.target.disabled = false;
    event.target.value = '';
};

window.renderRRPreviews = function (category) {
    const containerId = category === 'part' ? 'rrImagePreviewPart' : 'rrImagePreviewTooling';
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '';
    const filesArray = category === 'part' ? window.rrSelectedPartFiles : window.rrSelectedToolingFiles;

    filesArray.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function (e) {
            const div = document.createElement('div');
            div.className = 'relative w-20 h-20 rounded-xl overflow-hidden border-2 border-slate-200 shadow-md group transition-all hover:scale-105';
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover">
                <button type="button" onclick="window.removeRRImage('${category}', ${index})" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-600/90 text-white flex items-center justify-center hover:bg-red-700 transition shadow-md focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
};

window.removeRRImage = function (category, index) {
    if (category === 'part') {
        window.rrSelectedPartFiles.splice(index, 1);
    } else {
        window.rrSelectedToolingFiles.splice(index, 1);
    }
    window.renderRRPreviews(category);
};

window.submitRRModalForm = async function (event) {
    if (event) event.preventDefault();
    if (window.ProductionConfig?.isLocked) { showToast('Shift sudah dikunci.', 'danger'); return; }

    await window.ActionRunner.run('Simpan R&R', async () => {
        const form = document.getElementById('rrModalForm');
        const formData = new FormData(form);

        // Include current viewed date so ProductionService saves to correct DailyProduction
        formData.append('date', window.ProductionConfig.currentDate);

        // Remove original images[] from form data so we append our active selections
        formData.delete('images[]');

        // Append actual selected files from our custom arrays (both part and tooling)
        const allFiles = [...(window.rrSelectedPartFiles || []), ...(window.rrSelectedToolingFiles || [])];
        allFiles.forEach(file => {
            formData.append('images[]', file);
        });

        const rrJobId = document.getElementById('rrJobId').value;
        const rrType = document.getElementById('rrType').value;
        const rrQty = parseInt(document.getElementById('rrQty').value) || 0;
        let success = false;

        try {
            // Optimistic update
            if (rrJobId && jobMasterData[rrJobId] && rrQty > 0) {
                if (rrType === 'repair') {
                    jobMasterData[rrJobId].actual_repair += rrQty;
                } else {
                    jobMasterData[rrJobId].actual_reject += rrQty;
                }
                updateTimeline();
            }

            const resp = await fetch('/operational/repair-reject', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            let data;
            if (resp.ok) {
                data = await resp.json();
            } else {
                const text = await resp.text();
                console.error('R&R server error [' + resp.status + ']:', text);
                showToast('Server error (' + resp.status + '): ' + text.substring(0, 200), 'danger');
                return;
            }

            success = data.success;

            if (data.success) {
                showToast(data.message, 'success');
                closeRRInputModal();
                window.rrSelectedPartFiles = [];
                window.rrSelectedToolingFiles = [];
                if (rrJobId) window.loadRRList(rrJobId);
                notifyLineStatusChange(jobMasterData[rrJobId]?.line);
            } else {
                showToast(data.message || 'Gagal menyimpan data R&R', 'danger');
            }
        } finally {
            // Rollback on failure
            if (!success && rrJobId && jobMasterData[rrJobId] && rrQty > 0) {
                if (rrType === 'repair') {
                    jobMasterData[rrJobId].actual_repair -= rrQty;
                } else {
                    jobMasterData[rrJobId].actual_reject -= rrQty;
                }
                updateTimeline();
            }
        }
    });
};
