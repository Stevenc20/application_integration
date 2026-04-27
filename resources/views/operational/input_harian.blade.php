    @extends('layouts.layouts')

    @section('content')
    <div class="p-3 sm:p-4 md:p-6">

        {{-- HEADER --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">

            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                    Input Harian
                </h1>

                <p class="text-sm text-gray-500">
                    {{ now()->format('d F Y') }}
                </p>
            </div>

        </div>


        {{-- MAIN CARD --}}
        <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">

            <div class="p-4 md:p-6 border-b bg-gray-50">
                <h2 class="font-semibold text-gray-800">
                    Input Data Harian
                </h2>
            </div>

            <div class="p-4 md:p-6">

             {{-- FILTER FORM FULL REVISI --}}
        <form method="GET"
        action="{{ route('operational.input_harian') }}"
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">

            {{-- TANGGAL --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Pilih Tanggal
                </label>

                <input
                type="date"
                name="date"
                value="{{ request('date') }}"
                class="w-full border rounded-xl px-4 py-2.5
                focus:ring-2 focus:ring-red-500 focus:outline-none">
            </div>

            {{-- LINE --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Production Line
                </label>

                <select
                name="line"
                class="w-full border rounded-xl px-4 py-2.5
                focus:ring-2 focus:ring-red-500 focus:outline-none">

                    <option value="">Semua Line</option>

                    @foreach($lines as $line)
                    <option value="{{ $line }}"
                    {{ request('line') == $line ? 'selected' : '' }}>
                        {{ $line }}
                    </option>
                    @endforeach

                </select>
            </div>

            {{-- STATUS --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Status Job
                </label>

                <select
                name="status"
                class="w-full border rounded-xl px-4 py-2.5
                focus:ring-2 focus:ring-red-500 focus:outline-none">

                    <option value="">Semua Status</option>

                    <option value="pending"
                    {{ request('status') == 'pending' ? 'selected' : '' }}>
                        Pending
                    </option>

                    <option value="running"
                    {{ request('status') == 'running' ? 'selected' : '' }}>
                        Running
                    </option>

                    <option value="paused"
                    {{ request('status') == 'paused' ? 'selected' : '' }}>
                        Paused
                    </option>

                    <option value="finished"
                    {{ request('status') == 'finished' ? 'selected' : '' }}>
                        Finished
                    </option>

                </select>
            </div>

            {{-- SEARCH JOB --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Cari Job
                </label>

                <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Job number / nama item"
                class="w-full border rounded-xl px-4 py-2.5
                focus:ring-2 focus:ring-red-500 focus:outline-none">
            </div>

            {{-- BUTTON --}}
            <div class="flex items-end gap-2">

                <button
                type="submit"
                class="flex-1 px-4 py-2.5 rounded-xl
                bg-gray-800 hover:bg-black text-white font-medium">

                    Filter

                </button>

                <a href="{{ route('operational.input_harian') }}"
                class="px-4 py-2.5 rounded-xl border
                hover:bg-gray-50 text-sm">

                    Reset

                </a>

            </div>

        </form>


                {{-- TITLE --}}
            <div class="mb-4">
                    <h3 class="font-semibold text-gray-800 text-sm sm:text-base">
                        Item Produksi:
                        {{ request('line') ? request('line') : 'Semua Line' }}
                        | {{ request('date', date('d F Y')) }}
                    </h3>
                </div>

                {{-- TABLE --}}
                <div class="overflow-x-auto rounded-xl border">

                    <table class="min-w-[1300px] w-full text-sm">

                        <thead class="bg-gray-100 text-gray-700">
                            <tr class="text-left">

                                <th class="px-4 py-3">Nama Item</th>
                                <th class="px-4 py-3">Plan Qty</th>
                                <th class="px-4 py-3">Actual Qty</th>
                                <th class="px-4 py-3">Repair</th>
                                <th class="px-4 py-3">Reject</th>
                                <th class="px-4 py-3 text-center">Action</th>
                                <th class="px-4 py-3 text-center">Proses & Next Item</th>
                                <th class="px-4 py-3 text-center">Shortcut Input</th>

                            </tr>
                        </thead>

                    <tbody class="divide-y">

    @forelse($jobs as $job)

   <tr class="hover:bg-gray-50">

    {{-- ITEM --}}
    <td class="px-4 py-4">
        <div class="font-bold text-gray-800">
            {{ $job->job_number }}
        </div>

        <div class="text-xs text-gray-500">
            {{ $job->job_name }}
        </div>

       <div class="mt-1">
    <span
        id="badge-{{ $job->id }}"
        class="status-badge px-2 py-1 text-xs rounded text-white

        @if($job->status == 'running')
            bg-green-600
        @elseif($job->status == 'paused')
            bg-yellow-500
        @elseif($job->status == 'finished')
            bg-gray-600
        @else
            bg-blue-600
        @endif
        "
    >
        {{ strtoupper($job->status ?? 'pending') }}
    </span>
</div>
    </td>

    {{-- PLAN QTY --}}
    <td class="px-4 py-4 font-semibold">
        {{ number_format($job->capacity) }}
    </td>

    {{-- ACTUAL --}}
    <td class="px-4 py-4">
        <input type="number"
        id="actual-{{ $job->id }}"
        value="0"
        min="0"
        class="w-20 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
    </td>

    {{-- REPAIR --}}
    <td class="px-4 py-4">
        <input type="number"
        id="repair-{{ $job->id }}"
        value="0"
        min="0"
        class="w-16 border rounded-lg px-2 py-2 focus:ring-2 focus:ring-yellow-500 focus:outline-none">
    </td>

    {{-- REJECT --}}
    <td class="px-4 py-4">
        <input type="number"
        id="reject-{{ $job->id }}"
        value="0"
        min="0"
        class="w-16 border rounded-lg px-2 py-2 focus:ring-2 focus:ring-red-500 focus:outline-none">
    </td>

    {{-- SAVE --}}
    <td class="px-4 py-4 text-center">
        <button
        onclick="saveJob({{ $job->id }})"
        class="p-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition">
            💾
        </button>
    </td>

    {{-- TIMER + ACTION --}}
    <td class="px-4 py-4 text-center">

        <div class="space-y-2">

            <div id="timer-{{ $job->id }}"
            class="text-sm font-semibold text-gray-700">
                00:00:00
            </div>

           <div class="flex flex-wrap gap-2 justify-center action-buttons">

            <button
            onclick="startJob({{ $job->id }})"
            class="btn-start px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
            Start
            </button>

            <button
            onclick="pauseJob({{ $job->id }})"
            class="btn-pause hidden px-3 py-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white">
            Pause
            </button>

            <button
            onclick="resumeJob({{ $job->id }})"
            class="btn-resume hidden px-3 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white">
            Resume
            </button>

            <button
            onclick="restartJob({{ $job->id }})"
            class="btn-restart hidden px-3 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white">
            Restart
            </button>

            <button
            onclick="finishJob.call(this, {{ $job->id }})"
            class="btn-finish hidden px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white">
            Finish
            </button>

            </div>

        </div>

    </td>

    {{-- SHORTCUT --}}

<td class="px-4 py-4">
    <div class="grid grid-cols-1 gap-2">

        {{-- HANDWORK --}}
        <button
        type="button"
        class="px-3 py-2 rounded-lg border hover:bg-yellow-50 transition">
            Handwork
        </button>

        {{-- DOWNTIME --}}
        <button
        type="button"
        class="px-3 py-2 rounded-lg border hover:bg-red-50 transition">
            Downtime
        </button>

        {{-- DANDORI AUTO DIRECT --}}
       <a href="{{ route('operational.dandori', [
            'job_id' => $job->id,
            'line'   => $job->line,
            'shift'  => 'Shift 1'
            ]) }}"
            class="block text-center px-3 py-2 rounded-lg border
            hover:bg-blue-50 text-blue-700 font-medium transition">
            Dandori
        </a>

        {{-- QCHECK --}}
        <button
        type="button"
        class="px-3 py-2 rounded-lg border hover:bg-green-50 transition">
            Q-Check
        </button>

    </div>
</td>

</tr>

@empty

<tr>
    <td colspan="8" class="text-center py-10 text-gray-400">
        Tidak ada data job ditemukan
    </td>
</tr>

@endforelse

    </tbody>

                    </table>

                </div>

            </div>
        </div>



        {{-- FINISH MODAL --}}
       {{-- FINISH MODAL --}}
<div id="finishModal"
class="fixed inset-0 bg-black/40 hidden z-50 items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl">

        <div class="border-b px-6 py-4 font-semibold text-lg">
            Selesai Proses:
            <span id="finishJobTitle">-</span>
        </div>

        <div class="p-6 space-y-4">

            <p class="text-gray-600">
                Proses untuk item
                <b id="finishJobName">-</b>
                telah selesai.
            </p>

            <div>
                <label class="block text-sm font-medium mb-2">
                    Pilih Item Berikutnya
                </label>

                <select id="nextSelect"
                class="w-full border rounded-xl px-4 py-3">

                    <option value="">
                        AUTO – lanjut ke urutan berikutnya
                    </option>

                </select>

                <p class="text-xs text-gray-500 mt-2">
                    Jika AUTO, sistem pilih urutan berikutnya.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">

                <button
                onclick="goNextProcess()"
                class="flex-1 px-4 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-medium">
                    Lanjutkan Proses
                </button>

                <button onclick="closeFinishModal()"
                class="flex-1 px-4 py-3 rounded-xl border hover:bg-gray-50">
                    Batal / Kembali
                </button>

            </div>

        </div>

    </div>

</div>


    </div>

    <div id="toast"
                class="fixed top-5 right-5 z-[9999] hidden min-w-[260px]
                px-5 py-3 rounded-xl shadow-2xl text-white font-medium">
            </div>

    <script>
    function openFinishModal(){
        document.getElementById('finishModal').classList.remove('hidden');
        document.getElementById('finishModal').classList.add('flex');
    }

    function closeFinishModal(){
        document.getElementById('finishModal').classList.add('hidden');
        document.getElementById('finishModal').classList.remove('flex');
    }

    let currentFinishId = 0;    
    let intervals = {};

   function startJob(id){

    updateUIStatus(id,'running');
    showToast('Job dimulai','info');

    fetch(`/operational/job/${id}/start`,{
    method:'POST',
    headers:{
    'X-CSRF-TOKEN':'{{ csrf_token() }}'
    }
    })
    .then(()=>{
    loadStatus(id);
    })
    .catch(()=>{
    showToast('Gagal start','danger');
    loadStatus(id);
    });

    }

    function pauseJob(id){

    fetch(`/operational/job/${id}/pause`,{
    method:'POST',
    headers:{
    'X-CSRF-TOKEN':'{{ csrf_token() }}'
    }
    })
    .then(()=>{
    clearInterval(intervals[id]);
    loadStatus(id);
    showToast('Timer dihentikan sementara','warning');
    });

    }

    function resumeJob(id){

    updateUIStatus(id,'running'); // langsung ubah tombol

    showToast('Timer dilanjutkan','success');

    fetch(`/operational/job/${id}/resume`,{
    method:'POST',
    headers:{
    'X-CSRF-TOKEN':'{{ csrf_token() }}'
    }
    })
    .then(res=>res.json())
    .then(data=>{

    setTimeout(()=>{
    loadStatus(id);
    },300);

    })
    .catch(()=>{
    showToast('Gagal resume','danger');
    });

    }

    function restartJob(id){

    fetch(`/operational/job/${id}/restart`,{
    method:'POST',
    headers:{
    'X-CSRF-TOKEN':'{{ csrf_token() }}'
    }
    })
    .then(res=>res.json())
    .then(data=>{

    clearInterval(intervals[id]);

    loadStatus(id);

    showToast('Proses di-restart','info');

    })
    .catch(error=>{
    console.log(error);
    showToast('Gagal restart','danger');
    });

    }

    function finishJob(id){

    currentFinishId = id;

    // ambil nama job dari row table
    let row = event.target.closest('tr');

    let jobNumber =
    row.querySelector('td:nth-child(1) .font-bold').innerText;

    let jobName =
    row.querySelector('td:nth-child(1) .text-xs').innerText;

    // isi modal
    document.getElementById('finishJobTitle').innerText = jobNumber;
    document.getElementById('finishJobName').innerText = jobNumber + ' - ' + jobName;

    fetch(`/operational/job/${id}/finish`,{
    method:'POST',
    headers:{
    'X-CSRF-TOKEN':'{{ csrf_token() }}'
    }
    })
    .then(res=>res.json())
    .then(data=>{

    clearInterval(intervals[id]);

    loadStatus(id);

    showToast('Proses selesai','danger');

    loadNextItems(id);

    openFinishModal();

    })
    .catch(error=>{
    console.log(error);
    showToast('Gagal finish process','danger');
    });

    }

    function loadNextItems(id){

    fetch(`/operational/job/${id}/next-list`)
    .then(res=>res.json())
    .then(data=>{

    let html = `
    <option value="">
    AUTO – lanjut ke urutan berikutnya
    </option>
    `;

    data.forEach(job=>{

    html += `
    <option value="${job.id}">
    ${job.job_number} - ${job.job_name}
    </option>
    `;

    });

    document.getElementById('nextSelect').innerHTML = html;

    })
    .catch(error=>{
    console.log(error);
    });
    }

    function loadStatus(id){

    fetch(`/operational/job/${id}/status`)
    .then(res=>res.json())
    .then(data=>{

    if(!data) return;

    // WAJIB PANGGIL INI
    updateUIStatus(id,data.status);

    let baseSeconds = parseInt(data.total_seconds ?? 0);

    document.getElementById('timer-'+id).innerText =
    formatSeconds(baseSeconds);

    clearInterval(intervals[id]);

    if(data.status === 'running'){

    let startTime = new Date(data.start_time);
    let now = new Date();

    let extra = Math.floor((now - startTime)/1000);

    let seconds = baseSeconds + extra;

    document.getElementById('timer-'+id).innerText =
    formatSeconds(seconds);

    intervals[id] = setInterval(()=>{

    seconds++;

    document.getElementById('timer-'+id).innerText =
    formatSeconds(seconds);

    },1000);

    }

    });
    }

    function updateUIStatus(id,status){

    const row = document.getElementById('timer-'+id).closest('tr');

    const wrap = row.querySelector('.action-buttons');

    const startBtn   = wrap.querySelector('.btn-start');
    const pauseBtn   = wrap.querySelector('.btn-pause');
    const resumeBtn  = wrap.querySelector('.btn-resume');
    const restartBtn = wrap.querySelector('.btn-restart');
    const finishBtn  = wrap.querySelector('.btn-finish');

    const badge = document.getElementById('badge-'+id);

    /* hide semua tombol */
    [startBtn,pauseBtn,resumeBtn,restartBtn,finishBtn]
    .forEach(btn=>btn.classList.add('hidden'));

    /* reset badge */
    badge.className =
    'status-badge px-2 py-1 text-xs rounded text-white';

    /* RUNNING */
    if(status == 'running'){

    pauseBtn.classList.remove('hidden');
    restartBtn.classList.remove('hidden');
    finishBtn.classList.remove('hidden');

    badge.innerText = 'RUNNING';
    badge.classList.add('bg-green-600');

    }

    /* PAUSED */
    else if(status == 'paused'){

    resumeBtn.classList.remove('hidden');
    restartBtn.classList.remove('hidden');
    finishBtn.classList.remove('hidden');

    badge.innerText = 'PAUSED';
    badge.classList.add('bg-yellow-500');

    }

    /* FINISHED */
    else if(status == 'finished'){

    finishBtn.classList.remove('hidden');
    finishBtn.innerText = 'Done';

    badge.innerText = 'FINISHED';
    badge.classList.add('bg-gray-600');

    }

    /* PENDING */
    else{

    startBtn.classList.remove('hidden');

    badge.innerText = 'PENDING';
    badge.classList.add('bg-blue-600');

    }

    }

    function formatSeconds(sec){

    let h = String(Math.floor(sec/3600)).padStart(2,'0');
    let m = String(Math.floor((sec%3600)/60)).padStart(2,'0');
    let s = String(sec%60).padStart(2,'0');

    return `${h}:${m}:${s}`;
    }

    @foreach($jobs as $job)
    loadStatus({{ $job->id }});
    @endforeach


    function showToast(message,type='success'){

    const toast = document.getElementById('toast');

    toast.className =
    'fixed top-5 right-5 z-[9999] min-w-[260px] px-5 py-3 rounded-xl shadow-2xl text-white font-medium';

    if(type=='success'){
    toast.classList.add('bg-green-600');
    }

    if(type=='warning'){
    toast.classList.add('bg-yellow-500');
    }

    if(type=='danger'){
    toast.classList.add('bg-red-600');
    }

    if(type=='info'){
    toast.classList.add('bg-blue-600');
    }

    toast.innerText = message;
    toast.classList.remove('hidden');

    setTimeout(()=>{
    toast.classList.add('hidden');
    },2500);

    }

// save
    function saveJob(id){

    let actual = document.getElementById('actual-'+id).value;
    let repair = document.getElementById('repair-'+id).value;
    let reject = document.getElementById('reject-'+id).value;

    fetch(`/operational/job/${id}/save`,{
    method:'POST',
    headers:{
    'Content-Type':'application/json',
    'X-CSRF-TOKEN':'{{ csrf_token() }}',
    'Accept':'application/json'
    },
    body: JSON.stringify({
    actual_qty: actual,
    repair_qty: repair,
    reject_qty: reject
    })
    })
    .then(res => res.json())
    .then(data => {

    if(data.success){
    showToast('Data berhasil disimpan','success');
    }else{
    showToast('Gagal menyimpan','danger');
    }

    })
    .catch(error=>{
    showToast('Terjadi error','danger');
    console.log(error);
    });

    }

    // modal
    function goNextProcess(){

    let nextId = document.getElementById('nextSelect').value;

    fetch(`/operational/job/${currentFinishId}/next-process`,{
    method:'POST',
    headers:{
    'Content-Type':'application/json',
    'Accept':'application/json',
    'X-CSRF-TOKEN':'{{ csrf_token() }}'
    },
    body: JSON.stringify({
    next_id: nextId
    })
    })
    .then(res=>res.json())
    .then(data=>{

    if(data.success){

    showToast(data.message,'success');

    closeFinishModal();

    /* realtime update current */
    updateUIStatus(currentFinishId,'finished');

    /* reload status timer current */
    loadStatus(currentFinishId);

    /* jika ada next job dipilih manual */
    if(nextId){
    updateUIStatus(nextId,'running');
    loadStatus(nextId);
    }

    /* auto refresh semua row TANPA reload page */
    refreshAllRows();

    }else{
    showToast(data.message,'danger');
    }

    })
    .catch(error=>{
    console.log(error);
    showToast('Gagal menjalankan proses berikutnya','danger');
    });

    }
    
    function refreshAllRows(){

    @foreach($jobs as $job)
    loadStatus({{ $job->id }});
    @endforeach

    }
    
    
    </script>
    @endsection


 