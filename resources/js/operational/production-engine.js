/**
 * Production Engine JS
 * Extracted from input_harian.blade.php
 * Fully restored and syntax-corrected.
 */

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

function updateTimeline(forceAll = false) {
    const config = window.ProductionConfig;
    const now = new Date();

    if (forceAll) {
        console.log("ENGINE STATE:", {
            config: config,
            jobCount: Object.keys(window.jobMasterData || {}).length,
            runningCount: Object.keys(window.runningDowntimes || {}).length,
            historyCount: Object.keys(window.jobDowntimeHistory || {}).length
        });
    }

    const runningDowntimes = window.runningDowntimes || {};

    // 1. RESET ALL BUTTONS TO BASE STATE
    if (Object.keys(runningDowntimes).length > 0 || forceAll) {
        document.querySelectorAll('.dt-btn, .to-btn, .idle-btn, .break-btn').forEach(btn => {
            const parts = btn.id.split('-');
            const bT = parts[0];
            const jId = parts[parts.length - 1];
            if (runningDowntimes[`${jId}_${bT}`]) return;

            if (bT === 'downtime') { btn.innerText = 'DOWNTIME'; btn.className = "dt-btn py-2 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-[9px] font-black uppercase hover:bg-red-500 hover:text-white transition-all"; }
            else if (bT === 'tryout') { btn.innerText = 'TRY OUT'; btn.className = "to-btn py-2 rounded-lg bg-orange-500/10 border border-orange-500/30 text-orange-400 text-[9px] font-black uppercase hover:bg-orange-500 hover:text-white transition-all"; }
            else if (bT === 'idle') { btn.innerText = 'IDLE'; btn.className = "idle-btn py-2 rounded-lg bg-slate-700/50 border border-slate-600 text-slate-300 text-[9px] font-black uppercase hover:bg-slate-600 hover:text-white transition-all"; }
            else if (bT === 'break') { btn.innerText = 'BREAK'; btn.className = "break-btn py-2 rounded-lg bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-[9px] font-black uppercase hover:bg-indigo-500 hover:text-white transition-all"; }
        });
    }

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
            btn.className = "py-2 rounded-lg bg-red-600 text-white border-red-700 text-[9px] font-black uppercase animate-pulse scale-105 shadow-lg shadow-red-900/50 transition-all";
        }
    }

    // 3. PROCESS ALL JOBS FROM CENTRALIZED DATA
    for (let id in jobMasterData) {
        const job = jobMasterData[id];
        if (!job) continue;

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

            const elapsed = (finalEndTime.getTime() - anchorTime) / 1000;
            const realPct = (elapsed / (plannedDurationMs / 1000)) * 100;
            const visualPct = Math.min(Math.max(0, realPct), 100);

            if (isComplete && forceAll) {
                console.log(`TRACE [${id}]:`, { jS, jF, anchorTime, tD, pS_time, pE_time, plannedDurationMs, realPct });
            }

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

            const targetMarker = document.getElementById('row-target-marker-' + id);
            if (targetMarker) {
                targetMarker.style.left = visualPct + '%';
            }

            renderSegmentedTimeline('actual-segments-' + id, id, anchorTime, tD, jS, finalEndTime, firstDandori, realPct, plannedDurationMs);

            // A. Update Active Job Board (Hero)
            if (id == config.currentActiveId) {
                // Update Quick Display
                const activeOkDisplay = document.getElementById('active-actual-display');
                const activeOkInput = document.getElementById('actual-' + id);
                if (activeOkDisplay && activeOkInput) {
                    activeOkDisplay.innerText = activeOkInput.value || 0;
                }

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

                // Update Timeline Marker (The "Now" Indicator)
                const marker = document.getElementById('timeline-marker');
                if (marker) marker.style.left = visualPct + '%';

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

                // Update Achievement (Qty Based)
                const achievementEl = document.getElementById('active-achievement-display');
                if (achievementEl) {
                    achievementEl.innerText = Math.min(100, achievementPct) + '%';
                    achievementEl.className = `text-2xl font-black ${achievementPct >= 100 ? 'text-green-400' : 'text-yellow-400'}`;
                }

                const clock = document.getElementById('timeline-current-time');
                if (clock) {
                    let eH = effectiveNow.getHours().toString().padStart(2, '0');
                    let eM = effectiveNow.getMinutes().toString().padStart(2, '0');
                    clock.innerText = `End: ${eH}:${eM}`;
                }

                renderSegmentedTimeline('timeline-actual-container', id, anchorTime, tD, jS, finalEndTime, firstDandori, realPct);
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
}

function renderSegmentedTimeline(containerId, jobId, anchor, tD, jS, endTime, firstDandori, nowPctArg = null, plannedDurationArg = null) {
    try {
        const container = document.getElementById(containerId);
        if (!container) return;

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

            const headerColor = label.toLowerCase().includes('down') ? 'text-red-500' : 'text-blue-400';
            const tooltipId = `tt-${id}-${index}`;

            const segmentHtml = `
                <div class="absolute h-full ${color} cursor-pointer border-r border-white/20 flex items-center justify-center hover:brightness-110 transition-all"
                     style="left: ${safeLeft}%; width: ${safeWidth}%;"
                     onmouseover="
                        document.getElementById('${tooltipId}')?.classList.remove('hidden'); 
                        document.getElementById('${tooltipId}')?.classList.add('flex-col');
                        document.getElementById('${tooltipId}-s')?.classList.remove('opacity-40');
                        document.getElementById('${tooltipId}-s')?.classList.add('opacity-100', 'z-[60]');
                        document.getElementById('${tooltipId}-e')?.classList.remove('opacity-40');
                        document.getElementById('${tooltipId}-e')?.classList.add('opacity-100', 'z-[60]');
                     "
                     onmouseout="
                        document.getElementById('${tooltipId}')?.classList.add('hidden'); 
                        document.getElementById('${tooltipId}')?.classList.remove('flex-col');
                        document.getElementById('${tooltipId}-s')?.classList.add('opacity-40');
                        document.getElementById('${tooltipId}-s')?.classList.remove('opacity-100', 'z-[60]');
                        document.getElementById('${tooltipId}-e')?.classList.add('opacity-40');
                        document.getElementById('${tooltipId}-e')?.classList.remove('opacity-100', 'z-[60]');
                     ">
                </div>
            `;

            const labelHtml = `
                <div id="${tooltipId}" class="absolute bottom-full -translate-x-1/2 hidden bg-[#151c2c] p-4 rounded-xl shadow-2xl whitespace-nowrap z-[100] mb-3 pointer-events-none border border-white/10 min-w-[200px] flex-col" style="left: ${safeLeft + (safeWidth / 2)}%;">
                    <div class="${headerColor} font-black text-[10px] uppercase tracking-widest mb-1 text-left">${label}</div>
                    <div class="text-white font-bold text-lg mb-2 tabular-nums text-left tracking-tight">${sT} - ${eT}</div>
                    <div class="h-[1px] bg-white/10 mb-2"></div>
                    <div class="flex justify-between items-center text-slate-500 font-bold text-[10px] uppercase">
                        <span>Duration</span>
                        <span class="text-white">${durStr}</span>
                    </div>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-[#151c2c]"></div>
                </div>

                <div class="absolute top-full mt-1 -translate-x-1/2 flex flex-col items-center pointer-events-none z-50 opacity-40" id="${tooltipId}-s" style="left: ${safeLeft}%;">
                    <div class="bg-slate-700 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm border border-slate-600">${sT.substring(0, 5)}</div>
                </div>
                <div class="absolute top-full mt-1 -translate-x-1/2 flex flex-col items-center pointer-events-none z-50 opacity-40" id="${tooltipId}-e" style="left: ${safeLeft + safeWidth}%;">
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

            return {
                start: rawStart ? (isNaN(rawStart) ? new Date(rawStart).getTime() : Number(rawStart)) : null,
                end: rawEnd ? (isNaN(rawEnd) ? new Date(rawEnd).getTime() : Number(rawEnd)) : null,
                type: String(rawType || '').trim().toLowerCase()
            };
        }).filter(h => h.start !== null);

        const runningDowntimes = window.runningDowntimes || {};
        let rd = null;
        for (let k in runningDowntimes) {
            if (runningDowntimes[k].jobId == jobId) {
                rd = runningDowntimes[k];
                break;
            }
        }

        normalizedHistory.sort((a, b) => a.start - b.start);

        let html = '';
        let labelsHtml = '';
        let segmentCount = 0;

        const addSegment = (start, end, color, label) => {
            const res = createSegmentHtml(start, end, anchor, tD, color, label, jobId, segmentCount++);
            if (res) {
                html += res.segmentHtml;
                labelsHtml += res.labelHtml;
            }
        };

        const hasDandori = !!firstDandori;
        const actualStartMs = jS ? (jS instanceof Date ? jS.getTime() : new Date(jS).getTime()) : null;

        const effectiveActualStart = actualStartMs ||
            (hasDandori ? (firstDandori instanceof Date ? firstDandori.getTime() : new Date(firstDandori).getTime()) : null) ||
            (['running', 'complete', 'finished'].includes(s) ? anchor : null);

        if (!effectiveActualStart && !hasDandori && normalizedHistory.length === 0) {
            container.innerHTML = '';
            return;
        }

        const firstProdHistory = normalizedHistory.find(h => h.type !== 'dandori' && h.type !== 'setup')?.start;
        const firstAnyHistory = normalizedHistory.length ? normalizedHistory[0].start : null;
        let effectiveProductionStart = firstProdHistory || actualStartMs || firstAnyHistory || effectiveActualStart;

        if (!effectiveProductionStart || isNaN(effectiveProductionStart)) {
            effectiveProductionStart = (effectiveActualStart && !isNaN(effectiveActualStart)) ? effectiveActualStart : anchor;
        }

        const pD = Number(plannedDurationArg) || 0;
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
                const isInitialDandoriGap = dandoriStart && start > dandoriStart && (!effectiveActualStart || start <= effectiveActualStart);

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
            else if (dt.type === 'try out') color = 'bg-orange-500';
            else if (dt.type === 'idle time') color = 'bg-slate-500';
            else if (dt.type === 'break time') color = 'bg-indigo-500';

            addSegment(start, end, color, dt.type);
            lastPos = end;
        });

        const finalTime = endTime.getTime();
        if (rd) {
            let rdStart = rd.start.getTime();
            if (rdStart > lastPos && effectiveActualStart && rdStart > effectiveActualStart) {
                const segStart = Math.max(lastPos, effectiveActualStart);
                appendProduction(segStart, rdStart);
            }
            let color = 'bg-red-600';
            let extraClass = 'active-growing';
            const typeLower = (rd.dtType || "").toLowerCase();
            if (typeLower === 'dandori') color = 'bg-amber-400';
            else if (typeLower === 'try out' || typeLower === 'tryout') color = 'bg-orange-500';
            else if (typeLower === 'break time' || typeLower === 'break') { color = 'bg-indigo-500'; extraClass = ''; }
            else if (typeLower === 'idle time' || typeLower === 'idle') { color = 'bg-slate-500'; extraClass = ''; }

            addSegment(rdStart, finalTime, color + ' ' + extraClass, rd.dtType);
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
        const labelContainerId = containerId.replace('segments', 'labels');
        const labelContainer = document.getElementById(labelContainerId);
        if (labelContainer) {
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

function enqueueJob(id) {
    if (window.isPerformingAction) return;
    window.isPerformingAction = true;

    fetch(`/operational/job/${id}/enqueue`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
            'Accept': 'application/json'
        }
    })
        .then(async res => {
            const text = await res.text();
            try { return JSON.parse(text); } catch (e) { throw new Error("Invalid JSON"); }
        })
        .then(res => {
            if (res.success) {
                showToast('Job berhasil dimasukkan ke antrian!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Gagal memasukkan antrian', 'danger');
                window.isPerformingAction = false;
            }
        })
        .catch(err => {
            showToast('Kesalahan jaringan / server', 'danger');
            window.isPerformingAction = false;
        });
}

function jsStartDandori(jobId) {
    if (window.isPerformingAction) return;
    window.isPerformingAction = true;

    fetch(`/operational/job/${jobId}/dandori/start`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
            'Accept': 'application/json'
        }
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast('Dandori dimulai!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(res.message || 'Gagal memulai dandori', 'danger');
                window.isPerformingAction = false;
            }
        });
}

function jsStopDandori(jobId) {
    fetch(`/operational/job/${jobId}/dandori/finish`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast('Dandori selesai!', 'success');
                location.reload();
            } else {
                showToast(res.message || 'Gagal mengakhiri dandori', 'danger');
            }
        });
}

function restartJob(id) {
    if (window.isPerformingAction) return;
    window.isPerformingAction = true;
    fetch(`/operational/job/${id}/start`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) location.reload();
            else {
                showToast(res.message || 'Gagal memulai ulang', 'danger');
                window.isPerformingAction = false;
            }
        });
}

function finishJob(id, jobNumber, jobName) {
    window.currentFinishId = id;
    showConfirm('Selesaikan Proses?', 'Apakah Anda yakin item ' + jobNumber + ' sudah selesai?', () => {
        closeConfirmModal();
        const titleEl = document.getElementById('finishJobTitle');
        if (titleEl) titleEl.innerText = jobNumber;
        const nameEl = document.getElementById('finishJobName');
        if (nameEl) nameEl.innerText = jobNumber + ' - ' + jobName;

        const nextSelect = document.getElementById('nextSelect');
        if (nextSelect) {
            nextSelect.innerHTML = '<option value="">AUTO – lanjut ke urutan berikutnya</option>';
            fetch(`/operational/job/${id}/next-list`)
                .then(res => res.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(j => {
                            const opt = document.createElement('option');
                            opt.value = j.id;
                            opt.innerText = j.label;
                            nextSelect.appendChild(opt);
                        });
                    }
                });
        }
        openFinishModal();
    });
}

function submitFinalJob() {
    const config = window.ProductionConfig;
    if (config.currentDowntimeCount > 0) {
        showToast('PERINGATAN: Ada downtime yang masih berjalan!', 'danger');
        return;
    }
    const id = window.currentFinishId;
    const nextJobId = document.getElementById('nextSelect').value;
    if (!id) return;
    window.isPerformingAction = true;

    fetch(`/operational/job/${id}/finish`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.ProductionConfig.csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ next_job_id: nextJobId })
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast('Pekerjaan selesai!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(res.message || 'Gagal menyelesaikan', 'danger');
                window.isPerformingAction = false;
            }
        });
}

function handleQuickDowntime(jobId, btnType, dtType) {
    let key = `${jobId}_${btnType}`;
    if (runningDowntimes[key]) finishQuickDowntime(jobId, btnType, runningDowntimes[key].id);
    else startQuickDowntime(jobId, btnType, dtType);
}

function startQuickDowntime(jobId, btnType, dtType) {
    window.isPerformingAction = true;
    fetch(`/operational/job/${jobId}/downtime/start`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
            jenis_downtime: dtType,
            problem: dtType.toUpperCase() + ' (Shortcut)',
            penyebab: '',
            action: '',
            pic: window.ProductionConfig.userName || ''
        })
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const startTime = new Date();
                runningDowntimes[`${jobId}_${btnType}`] = { id: res.downtime.id, start: startTime, jobId: jobId, btnType: btnType, dtType: dtType };
                showToast(`${btnType.toUpperCase()} started`, 'danger');
                updateTimeline();
            }
        });
}

function finishQuickDowntime(jobId, btnType, dtId) {
    window.isPerformingAction = true;
    fetch(`/operational/downtime/${dtId}/finish`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                delete runningDowntimes[`${jobId}_${btnType}`];
                resetDowntimeButtons(jobId);
                showToast(`${btnType.toUpperCase()} stopped`, 'success');
                updateTimeline();
                if (btnType === 'downtime') openDowntimeReport(jobId, res.downtime);
            }
        });
}

function resetDowntimeButtons(jobId) {
    const configs = [
        { id: 'downtime', label: 'Downtime', bg: 'bg-red-500/10', border: 'border-red-500/30', text: 'text-red-400', hover: 'hover:bg-red-500' },
        { id: 'tryout', label: 'Try Out', bg: 'bg-orange-500/10', border: 'border-orange-500/30', text: 'text-orange-400', hover: 'hover:bg-orange-500' },
        { id: 'idle', label: 'Idle', bg: 'bg-slate-700/50', border: 'border-slate-600', text: 'text-slate-300', hover: 'hover:bg-slate-600' },
        { id: 'break', label: 'Break', bg: 'bg-indigo-500/10', border: 'border-indigo-500/30', text: 'text-indigo-400', hover: 'hover:bg-indigo-500' }
    ];
    configs.forEach(c => {
        const btn = document.getElementById(`${c.id}-btn-${jobId}`);
        if (btn) {
            btn.innerHTML = c.label;
            btn.className = `py-2 rounded-lg ${c.bg} border ${c.border} ${c.text} text-[9px] font-black uppercase ${c.hover} hover:text-white transition-all`;
        }
    });
}

function openDowntimeReport(jobId, dt) {
    window.currentDtJobId = jobId;
    const row = document.getElementById('row-' + jobId);
    if (row) document.getElementById('dtJobNumber').innerText = row.getAttribute('data-job-number');
    const formArea = document.getElementById('dtFormTitle').closest('.bg-gray-50');
    if (dt && dt.id) {
        document.getElementById('dtEditId').value = dt.id;
        document.getElementById('dtJenis').value = dt.jenis_downtime;
        document.getElementById('dtProblem').value = (dt.problem || '').replace('(Shortcut)', '');
        document.getElementById('dtPenyebab').value = dt.penyebab || '';
        document.getElementById('dtAction').value = dt.action || '';
        document.getElementById('dtPIC').value = dt.pic || window.ProductionConfig.userName || '';
        if (formArea) formArea.classList.remove('hidden');
    }
    document.getElementById('downtimeModal').classList.remove('hidden');
    document.getElementById('downtimeModal').classList.add('flex');
    loadDowntimes(jobId);
}

function saveDowntime() {
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
    });
}

function deleteDowntime(id) {
    if (!confirm("Hapus catatan downtime?")) return;
    fetch(`/operational/downtime/${id}/delete`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' }
    }).then(res => res.json()).then(res => {
        if (res.success) { showToast('Downtime dihapus', 'success'); loadDowntimes(window.currentDtJobId); }
    });
}

function loadDowntimes(jobId) {
    fetch(`/operational/job/${jobId}/downtimes`).then(res => res.json()).then(data => {
        const body = document.getElementById('downtimeListBody');
        body.innerHTML = data.map(dt => {
            let start = new Date(dt.start_time).getHours().toString().padStart(2, '0') + ':' + new Date(dt.start_time).getMinutes().toString().padStart(2, '0');
            let end = dt.finish_time ? new Date(dt.finish_time).getHours().toString().padStart(2, '0') + ':' + new Date(dt.finish_time).getMinutes().toString().padStart(2, '0') : '--:--';
            let dur = dt.duration_seconds || 0;
            let durStr = dur >= 60 ? Math.floor(dur / 60) + 'm ' + (dur % 60) + 's' : dur + 's';
            const dtJson = JSON.stringify(dt).replace(/"/g, '&quot;');
            const isMissingDetail = !dt.problem || dt.problem.includes('(Shortcut)');

            return `<tr class="hover:bg-slate-50 transition-colors ${isMissingDetail ? 'bg-red-50/50' : ''}">
                <td class="px-4 py-3 font-bold uppercase text-[10px] text-slate-700">${dt.jenis_downtime}</td>
                <td class="px-4 py-3 text-xs">
                    <div class="font-bold ${isMissingDetail ? 'text-red-600' : 'text-slate-800'}">${dt.problem || '-'}</div>
                    <div class="text-[10px] text-slate-500 uppercase">${dt.penyebab || '-'}</div>
                </td>
                <td class="px-4 py-3 text-xs font-medium text-slate-600">${dt.pic || '-'}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-600">${start} - ${end}</td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded bg-red-50 text-red-600 font-bold text-[10px]">${durStr}</span></td>
                <td class="px-4 py-3 text-center flex items-center justify-center gap-1">
                    <button onclick="openDowntimeReport('${jobId}', ${dtJson})" class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                </td>
            </tr>`;
        }).join('');
    });
}

function closeDowntimeModal() { document.getElementById('downtimeModal').classList.add('hidden'); }

function saveJob(id) {
    if (window.isPerformingAction) return;
    let actual = document.getElementById('actual-' + id).value;
    let repair = document.getElementById('repair-' + id).value;
    let reject = document.getElementById('reject-' + id).value;
    performSave(id, actual, repair, reject);
}

function saveActiveJob() {
    const id = window.ProductionConfig.currentActiveId;
    if (!id) return;
    let actual = document.getElementById('active-actual-' + id).value;
    let repair = document.getElementById('active-repair-' + id).value;
    let reject = document.getElementById('active-reject-' + id).value;
    performSave(id, actual, repair, reject);
}

function performSave(id, ok, repair, reject) {
    window.isPerformingAction = true;
    fetch(`/operational/job/${id}/save-log`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ProductionConfig.csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ ok_qty: ok, repair_qty: repair, reject_qty: reject })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) showToast('Data Production Log Saved!', 'success');
            window.isPerformingAction = false;
        })
        .catch(() => { window.isPerformingAction = false; });
}

function showConfirm(title, text, callback) {
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmText').innerText = text;
    document.getElementById('confirmModal').classList.remove('hidden');
    document.getElementById('confirmModal').classList.add('flex');
    document.getElementById('confirmBtn').onclick = callback;
}
function closeConfirmModal() { document.getElementById('confirmModal').classList.add('hidden'); }
function openFinishModal() { document.getElementById('finishModal').classList.remove('hidden'); document.getElementById('finishModal').classList.add('flex'); }
function closeFinishModal() { document.getElementById('finishModal').classList.add('hidden'); }

function showToast(m, t) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.className = `fixed top-5 right-5 z-[9999] min-w-[260px] px-5 py-3 rounded-xl shadow-2xl text-white font-medium transition-all duration-300 transform bg-${t === 'danger' ? 'red-600' : (t === 'info' ? 'blue-600' : 'green-600')}`;
    toast.innerText = m;
    toast.classList.remove('hidden', 'opacity-0');
    setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.classList.add('hidden'), 300); }, 2500);
}

function toggleCustomSelect() {
    const menu = document.getElementById('custom-select-menu');
    if (menu) menu.classList.toggle('hidden');
}

function selectCustomItem(id, label) {
    const select = document.getElementById('standby-job-select');
    if (select) select.value = id;
    const lbl = document.getElementById('custom-select-label');
    if (lbl) { lbl.innerText = label; lbl.classList.add('text-white', 'font-bold'); }
    toggleCustomSelect();
}

function checkSyncStatus() {
    if (window.isPerformingAction) return;
    fetch('/operational/active-job').then(res => res.json()).then(data => {
        const config = window.ProductionConfig;
        if ((data.running && data.id !== config.currentActiveId) || (!data.running && config.currentActiveId !== null)) location.reload();
    }).catch(() => { });
}

window.stepInput = function (id, amount, jobId = null) {
    const input = document.getElementById(id);
    if (input) {
        input.value = parseInt(input.value || 0) + amount;
        const targetJobId = jobId || id.split('-').pop();
        if (jobMasterData[targetJobId]) {
            if (id.includes('actual')) jobMasterData[targetJobId].actual_ok = parseInt(input.value);
            else if (id.includes('repair')) jobMasterData[targetJobId].actual_repair = parseInt(input.value);
            else if (id.includes('reject')) jobMasterData[targetJobId].actual_reject = parseInt(input.value);
            updateTimeline();
        }
        saveJob(targetJobId);
    }
};

console.log("PRODUCTION ENGINE LOADED");

function initProductionEngine() {
    try {
        console.log("INITIALIZING PRODUCTION ENGINE...");
        updateTimeline(true);
        setInterval(() => updateTimeline(false), 1000);
        setInterval(checkSyncStatus, 10000);
    } catch (e) {
        console.error("FAILED TO INITIALIZE PRODUCTION ENGINE:", e);
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initProductionEngine);
else initProductionEngine();
