
let confirmCallback = null;

function showConfirm(title, text, callback, type = 'warning') {
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmText').innerText = text;
    confirmCallback = callback;
    const modal = document.getElementById('confirmModal');
    const content = document.getElementById('confirmContent');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => { content.classList.remove('scale-95', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); }, 10);
    document.getElementById('confirmBtn').onclick = () => { confirmCallback(); };
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    const content = document.getElementById('confirmContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
}

let runningDowntimes = {};
let currentDtJobId = null;
let currentFinishId = 0;
let intervals = {};

function startJob(id) {
    if (!confirm('Mulai produksi untuk job ini?')) return;
    
    const url = "1".replace(':id', id);
    
    fetch(url, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '1',
            'Accept': 'application/json'
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
    })
    .then(res => {
        if (res.success) {
            showToast('Produksi dimulai', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            alert('Gagal memulai: ' + (res.message || 'Error tidak diketahui'));
        }
    })
    .catch(err => {
        console.error('Error starting job:', err);
        alert('Terjadi kesalahan koneksi saat memulai job.');
    });
}

function stopJob(id) {
    if (!confirm('Selesaikan produksi untuk job ini?')) return;
    
    const url = "1".replace(':id', id);

    fetch(url, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '1',
            'Accept': 'application/json'
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
    })
    .then(res => {
        if (res.success) {
            showToast('Produksi selesai', 'info');
            setTimeout(() => location.reload(), 800);
        } else {
            alert('Gagal mengakhiri: ' + (res.message || 'Error tidak diketahui'));
        }
    })
    .catch(err => {
        console.error('Error finishing job:', err);
        alert('Terjadi kesalahan koneksi saat mengakhiri job.');
    });
}

function openFinishModal(){
    const modal = document.getElementById('finishModal');
    if(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeFinishModal(){
    const modal = document.getElementById('finishModal');
    if(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function restartJob(id){
    fetch(`/operational/job/${id}/restart`,{method:'POST',headers:{'X-CSRF-TOKEN':'1'}})
    .then(res=>res.json())
    .then(data=>{ clearInterval(intervals[id]); loadStatus(id); showToast('Proses di-restart','info'); })
    .catch(error=>{ console.log(error); showToast('Gagal restart','danger'); });
}

function finishJob(id, jobNumber, jobName){
    currentFinishId = id;
    
    showConfirm('Selesaikan Proses?', 'Apakah Anda yakin item ' + jobNumber + ' sudah selesai?', () => {
        closeConfirmModal();
        document.getElementById('finishJobTitle').innerText = jobNumber;
        document.getElementById('finishJobName').innerText = jobNumber + ' - ' + jobName;
        
        fetch(`/operational/job/${id}/finish`,{method:'POST',headers:{'X-CSRF-TOKEN':'1'}})
        .then(res=>res.json())
        .then(data=>{ 
            clearInterval(intervals[id]); 
            loadStatus(id); 
            showToast('Proses selesai','danger'); 
            loadNextItems(id); 
            openFinishModal(); 
            
            if(data.runtime_seconds !== undefined) {
                let min = Math.floor(data.runtime_seconds / 60);
                let sec = data.runtime_seconds % 60;
                let el = document.getElementById('saved-runtime-'+id);
                if(el) el.innerText = min + 'm ' + sec + 's';
            }
            if(typeof window.fetchGlobalTimer === 'function') window.fetchGlobalTimer();
        })
        .catch(error=>{ console.log(error); showToast('Gagal finish process','danger'); });
    });
}

function loadNextItems(id){
    fetch(`/operational/job/${id}/next-list`)
    .then(res=>res.json())
    .then(data=>{
        let html = `<option value="">AUTO – lanjut ke urutan berikutnya</option>`;
        data.forEach(job=>{ html += `<option value="${job.id}">${job.job_number} - ${job.job_name}</option>`; });
        document.getElementById('nextSelect').innerHTML = html;
    })
    .catch(error=>{ console.log(error); });
}

function loadStatus(id){
    fetch(`/operational/job/${id}/status`)
    .then(res=>res.json())
    .then(data=>{
        if(!data) return;
        updateUIStatus(id,data.status);
        let baseSeconds = parseInt(data.total_seconds ?? 0);
        
        clearInterval(intervals[id]);
        
        if(data.status === 'running' && data.start_time){
            let startTime = new Date(data.start_time);
            
            const updateTimer = () => {
                let now = new Date();
                let diffInSeconds = Math.floor((now - startTime) / 1000);
                
                let currentSeconds = baseSeconds + Math.max(0, diffInSeconds);
                document.getElementById('timer-'+id).innerText = formatSeconds(currentSeconds);
            };
            
            updateTimer();
            intervals[id] = setInterval(updateTimer, 1000);
        } else {
            document.getElementById('timer-'+id).innerText = formatSeconds(baseSeconds);
        }
    });
}

function updateUIStatus(id,status){
    const row = document.getElementById('timer-'+id).closest('tr');
    const wrap = row.querySelector('.action-buttons');
    const startBtn   = wrap.querySelector('.btn-start');
    const restartBtn = wrap.querySelector('.btn-restart');
    const finishBtn  = wrap.querySelector('.btn-finish');
    const badge = document.getElementById('badge-'+id);
    
    [startBtn,restartBtn,finishBtn].forEach(btn=>btn.classList.add('hidden'));
    
    badge.className = 'status-badge inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full text-white';
    
    if(status=='running'){
        restartBtn.classList.remove('hidden'); finishBtn.classList.remove('hidden');
        badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-white/70 animate-pulse"></span> RUNNING';
        badge.classList.add('bg-green-500');
    } else if(status=='finished'){
        restartBtn.classList.remove('hidden');
        badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-white/70"></span> FINISHED';
        badge.classList.add('bg-gray-500');
    } else {
        startBtn.classList.remove('hidden');
        badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-white/70"></span> PENDING';
        badge.classList.add('bg-blue-500');
    }
}

function formatSeconds(sec){
    if (sec < 0) sec = 0;
    let h = String(Math.floor(sec/3600)).padStart(2,'0');
    let m = String(Math.floor((sec%3600)/60)).padStart(2,'0');
    let s = String(sec%60).padStart(2,'0');
    return `${h}:${m}:${s}`;
}

loadStatus(1);

function showToast(message,type='success'){
    const toast = document.getElementById('toast');
    toast.className = 'fixed top-5 right-5 z-[9999] min-w-[260px] px-5 py-3 rounded-xl shadow-2xl text-white font-medium transition-all';
    if(type=='success') toast.classList.add('bg-green-600');
    if(type=='warning') toast.classList.add('bg-yellow-500');
    if(type=='danger')  toast.classList.add('bg-red-600');
    if(type=='info')    toast.classList.add('bg-blue-600');
    toast.innerText = message;
    toast.classList.remove('hidden');
    setTimeout(()=>{ toast.classList.add('hidden'); },2500);
}

function saveJob(id){
    let actual = document.getElementById('actual-'+id).value;
    let repair = document.getElementById('repair-'+id).value;
    let reject = document.getElementById('reject-'+id).value;
    fetch(`/operational/job/${id}/save`,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'1','Accept':'application/json'},
        body: JSON.stringify({actual_qty:actual,repair_qty:repair,reject_qty:reject})
    })
    .then(res=>res.json())
    .then(data=>{ 
        if(data.success){ 
            showToast('Data berhasil disimpan','success'); 
            // Update saved runtime display
            let min = Math.floor(data.runtime_seconds / 60);
            let sec = data.runtime_seconds % 60;
            document.getElementById('saved-runtime-'+id).innerText = min + 'm ' + sec + 's';
        } else { 
            showToast('Gagal menyimpan','danger'); 
        } 
    })
    .catch(error=>{ showToast('Terjadi error','danger'); console.log(error); });
}

function goNextProcess(){
    let nextId = document.getElementById('nextSelect').value;
    fetch(`/operational/job/${currentFinishId}/next-process`,{
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'1'},
        body: JSON.stringify({next_id:nextId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            showToast(data.message,'success');
            closeFinishModal();
            updateUIStatus(currentFinishId,'finished');
            loadStatus(currentFinishId);
            if(data.next_id){ 
                updateUIStatus(data.next_id,'running'); 
                loadStatus(data.next_id); 
            } else if(nextId){ 
                updateUIStatus(nextId,'running'); 
                loadStatus(nextId); 
            }
            // Delay sedikit agar refresh data server lebih akurat
            setTimeout(refreshAllRows, 500);
        } else { showToast(data.message,'danger'); }
    })
    .catch(error=>{ console.log(error); showToast('Gagal menjalankan proses berikutnya','danger'); });
}

function refreshAllRows(){
        loadStatus(1);
    }

/*
====================================================
DOWNTIME & TRY OUT LOGIC (QUICK FLOW)
====================================================
*/
// Variables already declared at the top of the script

// Timer to update button labels
setInterval(() => {
    for (let key in runningDowntimes) {
        let rd = runningDowntimes[key];
        let now = new Date();
        let diff = Math.floor((now - rd.start) / 1000);
        let label = formatMS(diff);
        
        let btnId = '';
        if(rd.btnType === 'downtime') btnId = `dt-btn-${rd.jobId}`;
        else if(rd.btnType === 'tryout') btnId = `to-btn-${rd.jobId}`;
        else if(rd.btnType === 'idle') btnId = `idle-btn-${rd.jobId}`;
        else if(rd.btnType === 'break') btnId = `break-btn-${rd.jobId}`;

        let btn = document.getElementById(btnId);
        if(btn) {
            btn.innerHTML = `<span class="flex items-center gap-1 justify-center"><span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> STOP (${label})</span>`;
            btn.classList.add('animate-pulse', 'ring-2', 'ring-offset-1');
        }
    }
}, 1000);

function formatMS(seconds) {
    let m = Math.floor(seconds / 60);
    let s = seconds % 60;
    return `${m}m ${s}s`;
}

function handleQuickDowntime(jobId, btnType, dtType) {
    let key = `${jobId}_${btnType}`;
    
    if (runningDowntimes[key]) {
        // STOP
        finishQuickDowntime(jobId, btnType, runningDowntimes[key].id);
    } else {
        // START
        startQuickDowntime(jobId, btnType, dtType);
    }
}

function startQuickDowntime(jobId, btnType, dtType) {
    fetch(`/operational/job/${jobId}/downtime/start`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '1' },
        body: JSON.stringify({ jenis_downtime: dtType })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            runningDowntimes[`${jobId}_${btnType}`] = {
                id: res.downtime.id,
                start: new Date(),
                jobId: jobId,
                btnType: btnType,
                dtType: dtType
            };
            showToast(`${btnType.toUpperCase()} dimulai`, 'danger');
        }
    });
}

function finishQuickDowntime(jobId, btnType, dbId) {
    fetch(`/operational/downtime/${dbId}/finish`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '1' }
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            delete runningDowntimes[`${jobId}_${btnType}`];
            
            // Reset button UI
            let btnId = '';
            if(btnType === 'downtime') btnId = `dt-btn-${jobId}`;
            else if(btnType === 'tryout') btnId = `to-btn-${jobId}`;
            else if(btnType === 'idle') btnId = `idle-btn-${jobId}`;
            else if(btnType === 'break') btnId = `break-btn-${jobId}`;

            let btn = document.getElementById(btnId);
            if(btn){
                if(btnType === 'downtime') btn.innerHTML = 'Downtime';
                else if(btnType === 'tryout') btn.innerHTML = 'Try Out';
                else if(btnType === 'idle') btn.innerHTML = 'Idle';
                else if(btnType === 'break') btn.innerHTML = 'Break';
                btn.classList.remove('animate-pulse', 'ring-2', 'ring-offset-1');
            }

            showToast(`${btnType.toUpperCase()} selesai`, 'success');
            
            // Open Modal for filling details
            openDowntimeReport(jobId, res.downtime);
        }
    });
}

function openDowntimeReport(jobId, dt) {
    currentDtJobId = jobId;
    
    // Find job number from row
    let row = document.getElementById(`row-${jobId}`);
    let jobNumber = row ? row.getAttribute('data-job-number') : '-';
    
    document.getElementById('dtJobNumber').innerText = jobNumber;
    document.getElementById('dtEditId').value = dt.id;
    document.getElementById('dtJenis').value = dt.jenis_downtime;
    document.getElementById('dtProblem').value = '';
    document.getElementById('dtPenyebab').value = '';
    document.getElementById('dtAction').value = '';
    document.getElementById('dtPIC').value = '';

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
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '1' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showToast('Laporan disimpan', 'success');
            closeDowntimeModal();
            loadDowntimes(currentDtJobId);
        }
    });
}

function loadDowntimes(jobId) {
    fetch(`/operational/job/${jobId}/downtimes`)
        .then(res => res.json())
        .then(data => {
            renderDowntimes(data);
        });
}

function renderDowntimes(list) {
    const body = document.getElementById('downtimeListBody');
    if (list.length === 0) {
        body.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada riwayat</td></tr>`;
        return;
    }

    body.innerHTML = list.map(dt => {
        let isRunning = !dt.finish_time;
        let duration = !isRunning ? formatMS(dt.duration_seconds) : '<span class="text-red-500 animate-pulse font-bold text-[10px]">RUNNING</span>';

        return `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-[10px] font-bold uppercase tracking-wider">${dt.jenis_downtime}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="font-bold text-gray-800">${dt.problem || '-'}</div>
                    <div class="text-[10px] text-gray-500 italic mt-0.5">${dt.penyebab || '-'}</div>
                </td>
                <td class="px-4 py-3 text-xs font-medium text-gray-600">${dt.pic || '-'}</td>
                <td class="px-4 py-3 text-[10px] text-gray-400 font-mono">
                    ${formatDate(dt.start_time)}<br>${dt.finish_time ? formatDate(dt.finish_time) : '...'}
                </td>
                <td class="px-4 py-3 text-center font-bold text-gray-700">${duration}</td>
                <td class="px-4 py-3 text-center">
                    <button onclick="deleteDowntime(${dt.id})" class="text-gray-400 hover:text-red-600 p-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                </td>
            </tr>
        `;
    }).join('');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    let d = new Date(dateStr);
    return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0') + ':' + d.getSeconds().toString().padStart(2, '0');
}

function deleteDowntime(id) {
    if (!confirm('Hapus riwayat ini?')) return;
    fetch(`/operational/downtime/${id}/delete`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '1' }
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showToast('Dihapus', 'info');
            loadDowntimes(currentDtJobId);
        }
    });
}

function closeDowntimeModal() {
    document.getElementById('downtimeModal').classList.add('hidden');
    document.getElementById('downtimeModal').classList.remove('flex');
}

// Auto-sync running states on load
window.addEventListener('DOMContentLoaded', () => {
            updateTimeline();
        setInterval(updateTimeline, 1000);
    
        fetch(`/operational/job/1/downtimes`)
    .then(res => res.json())
    .then(list => {
        let running = list.find(dt => !dt.finish_time);
        if(running){
            let btnType = 'downtime';
            if(running.jenis_downtime === 'try out') btnType = 'tryout';
            else if(running.jenis_downtime === 'idle time') btnType = 'idle';
            else if(running.jenis_downtime === 'break time') btnType = 'break';

            runningDowntimes[`1_${btnType}`] = {
                id: running.id,
                start: new Date(running.start_time),
                jobId: 1,
                btnType: btnType,
                dtType: running.jenis_downtime
            };
        }
    });
    });

function updateTimeline() {
            const planStart = new Date("1");
        const planEnd = new Date("1");
        const jobStartedAt = 1;
        const now = new Date();

        const totalS = (planEnd - planStart) / 1000;
        const elapsedS = (now - planStart) / 1000;

        let activeRD = null;
        for (let key in runningDowntimes) {
            if (runningDowntimes[key].jobId == 1) {
                activeRD = runningDowntimes[key];
                break;
            }
        }

        // Marker Position (Wall clock)
        let markerPercent = (elapsedS / totalS) * 100;
        markerPercent = Math.min(100, Math.max(0, markerPercent));
        const marker = document.getElementById('timeline-marker');
        if (marker) marker.style.left = markerPercent + '%';

        // Production Bar Position (Blue)
        // Only if job has started
        const prodBar = document.getElementById('timeline-production-bar');
        if (prodBar && jobStartedAt) {
            let startOff = ((jobStartedAt - planStart) / 1000 / totalS) * 100;
            let endOff = markerPercent;
            
            // If downtime is active, the blue bar's END should be the START of the downtime
            if (activeRD) {
                endOff = ((activeRD.start - planStart) / 1000 / totalS) * 100;
            }

            let width = endOff - startOff;
            prodBar.style.left = Math.max(0, startOff) + '%';
            prodBar.style.width = Math.max(0, width) + '%';
        }

        // Live Downtime Bar (Red)
        if (activeRD) {
            const liveBar = document.getElementById('live-event-bar');
            if (liveBar) {
                let off = ((activeRD.start - planStart) / 1000 / totalS) * 100;
                let wid = ((now - activeRD.start) / 1000 / totalS) * 100;
                
                liveBar.style.left = off + '%';
                liveBar.style.width = wid + '%';
                liveBar.className = `absolute top-0 h-full z-30 shadow-lg border-x border-white/10 block `;
                
                if (activeRD.dtType === 'try out') liveBar.classList.add('bg-orange-500');
                else if (activeRD.dtType === 'idle time') liveBar.classList.add('bg-slate-500');
                else if (activeRD.dtType === 'break time') liveBar.classList.add('bg-indigo-500');
                else liveBar.classList.add('bg-red-500');
            }
        } else {
            const liveBar = document.getElementById('live-event-bar');
            if (liveBar) liveBar.classList.add('hidden');
        }

        const label = document.getElementById('timeline-time-label');
        if (label) label.innerText = Math.round(markerPercent) + '%';

        const clock = document.getElementById('timeline-current-time');
        if (clock) {
            clock.innerText = now.getHours().toString().padStart(2, '0') + ':' +
                              now.getMinutes().toString().padStart(2, '0') + ':' +
                              now.getSeconds().toString().padStart(2, '0');
        }
    }
// Unified startJob and stopJob moved to top
