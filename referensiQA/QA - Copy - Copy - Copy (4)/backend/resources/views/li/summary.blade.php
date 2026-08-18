<x-app-layout :pageTitle="$pageTitle">
<div class="lhi-wrap min-h-screen p-3 sm:p-5" x-data="lhiPage()" x-init="init()">
    {{-- Styles --}}
    <style>
        .lhi-wrap { font-family: 'Plus Jakarta Sans', sans-serif; }
        .lhi-wrap * { box-sizing: border-box; }
        .lhi-wrap input:focus { outline: none; }
        .lhi-filter-select { transition: border-color 0.15s, box-shadow 0.15s; }
        .lhi-filter-select:focus { border-color: #3B82F6 !important; box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important; outline: none; }
        .lhi-row-hover:hover { background: #FFFBEB !important; }
        .lhi-btn { transition: all 0.15s ease; }
        .lhi-btn:hover:not(:disabled) { filter: brightness(0.9); transform: translateY(-1px); }
        @keyframes lhiSpin { to { transform: rotate(360deg); } }
        .lhi-toast { animation: lhiToastIn 0.25s ease forwards; }
        @keyframes lhiToastIn { from { opacity:0; transform:translateY(-8px) scale(0.95); } to { opacity:1; transform:translateY(0) scale(1); } }
        @media print {
            .no-print, aside, header { display: none !important; }
            @page { size: A4 landscape; margin: 0; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white !important; padding: 8mm 10mm !important; }
            .lhi-wrap { padding: 0 !important; background: white !important; }
            #print-area { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            main > div { padding: 0 !important; }
        }
    </style>

    {{-- TOAST --}}
    <template x-if="toast">
        <div class="lhi-toast fixed top-5 right-5 z-[9999] flex items-center gap-2 px-5 py-3 rounded-xl shadow-2xl text-sm font-bold border"
             :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
            <span x-text="toast.type === 'success' ? '✅' : '❌'"></span>
            <span x-text="toast.msg"></span>
        </div>
    </template>

    {{-- FILTER BAR --}}
    <div class="no-print max-w-[1160px] mx-auto mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        
        {{-- Title --}}
        <div class="shrink-0">
            <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight leading-none mb-2">Laporan Harian Inspeksi</h1>
            <p class="text-[10px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest flex items-center gap-2">
                <span class="text-red-500">Summary LHI</span>
            </p>
        </div>

        {{-- Filters & Actions --}}
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 lg:ml-auto">
            
            {{-- Filter Group --}}
            <div class="flex flex-wrap items-center gap-2 bg-slate-50/50 p-1.5 rounded-2xl border border-slate-100">
                <input type="date" x-model="filterTanggal" @change="onFilterChange()" class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 focus:border-red-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all cursor-pointer shadow-sm flex-1 sm:flex-none uppercase">
                <select x-model="filterLine" class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 focus:border-red-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all cursor-pointer shadow-sm w-full sm:w-auto min-w-[140px]">
                    <template x-for="l in lineOptions" :key="l">
                        <option :value="l" x-text="l"></option>
                    </template>
                </select>
            </div>

            <div class="hidden lg:block w-px h-8 bg-slate-200 mx-1"></div>

            {{-- Action Group --}}
            <div class="flex items-center gap-3 w-full sm:w-auto">
                {{-- Pagination/Status Info --}}
                <div class="flex items-center bg-white border border-slate-200 rounded-xl h-[42px] px-1 shadow-sm flex-1 sm:flex-none justify-center">
                    <button @click="page = Math.max(1, page - 1)" :disabled="page === 1" class="w-8 h-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-transparent transition-colors font-bold text-lg rounded-lg">‹</button>
                    <div class="px-3 text-[11px] font-black text-slate-600 tracking-wider whitespace-nowrap">
                        <span x-text="loading ? '...' : filteredCount + ' DATA'"></span>
                        <span class="text-slate-300 mx-1">|</span>
                        <span class="text-slate-400">HAL</span> <span x-text="loading ? '-' : page + '/' + totalPages"></span>
                    </div>
                    <button @click="page = Math.min(totalPages, page + 1)" :disabled="page === totalPages" class="w-8 h-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-transparent transition-colors font-bold text-lg rounded-lg">›</button>
                </div>

                {{-- Print Button --}}
                <button @click="window.print()" class="h-[42px] px-6 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-black tracking-wide flex items-center justify-center gap-2 shadow-[0_4px_12px_rgba(30,41,59,0.15)] hover:shadow-[0_6px_16px_rgba(30,41,59,0.25)] hover:-translate-y-0.5 transition-all w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    CETAK
                </button>
            </div>

        </div>
    </div>

    {{-- FORM KERTAS --}}
    <div id="print-area" class="max-w-[1160px] mx-auto bg-white border border-slate-400 p-3 sm:p-6 shadow-2xl overflow-x-auto scroll-hint">
        {{-- Header Fisik --}}
        <div class="text-right text-[11px] font-bold text-black mb-1 uppercase tracking-wide">FISM-PRO-02-36-01</div>
        <div class="border-t-[3px] border-black pt-2 pb-2 flex justify-between items-start text-black">
            
            <!-- Left: Company -->
            <div class="flex-shrink-0">
                <div class="text-[11px] font-bold leading-tight">PT. INTI PANTJA PRESS INDUSTRI</div>
                <div class="text-[11px] font-bold">PRODUKSI DEPARTMENT</div>
            </div>

            <!-- Center: Title -->
            <div class="flex-1 text-center pt-2 px-2">
                <h2 class="text-[15px] font-bold uppercase mb-2 tracking-wide" style="font-family:'Times New Roman', Times, serif">LAPORAN HARIAN INSPEKSI PRESS LINE</h2>
                <div class="inline-flex flex-col items-start text-[11px] font-bold" style="font-family:'Times New Roman', Times, serif">
                    <div class="flex gap-2">
                        <span class="w-16 text-right">LOKASI</span>
                        <span>: <span x-text="filterLine === 'Semua Line' ? '—' : filterLine" class="uppercase"></span></span>
                    </div>
                    <div class="flex gap-2">
                        <span class="w-16 text-right">TANGGAL</span>
                        <span>: <span x-text="firstDate || '—'"></span></span>
                    </div>
                </div>
            </div>

            <!-- Right: Legend & Sigs -->
            <div class="flex-shrink-0 flex justify-end items-start gap-4">
                <!-- Legend -->
                <div class="text-[9px] font-bold pt-3 flex leading-tight whitespace-nowrap" style="font-family:'Times New Roman', Times, serif">
                    <div class="w-6 text-right pr-1">KET</div>
                    <div class="w-2">:</div>
                    <div class="whitespace-nowrap">
                        <div class="flex"><span class="w-8 inline-block">MTR</span><span>= MATERIAL</span></div>
                        <div class="flex"><span class="w-8 inline-block">OPR</span><span>= OPERASI</span></div>
                        <div class="flex"><span class="w-8 inline-block">HDL</span><span>= HANDLING</span></div>
                        <div class="flex"><span class="w-8 inline-block">MSN</span><span>= MESIN</span></div>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="flex border border-black bg-white">
                    @foreach(['approved'=>'APPROVED','checked'=>'CHECKED','prepared'=>'PREPARED'] as $role => $label)
                    <div class="w-[60px] border-r border-black last:border-r-0 flex flex-col">
                        <div class="h-[55px] flex items-center justify-center p-1 relative">
                            <template x-if="ttdData.{{ $role }}">
                                <div class="relative group">
                                    <img :src="ttdData.{{ $role }}" class="max-h-12 max-w-full object-contain">
                                    <button @click="clearTtd('{{ $role }}')" class="no-print absolute -top-4 -right-4 w-5 h-5 bg-red-600 text-white rounded-full text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                                </div>
                            </template>
                            <template x-if="!ttdData.{{ $role }}">
                                <button @click="openSigPad('{{ $role }}')" class="no-print text-[8px] font-black text-blue-600 bg-blue-50 border border-blue-200 px-1 py-1 rounded hover:bg-blue-100 transition-colors">+ TTD</button>
                            </template>
                        </div>
                        <div class="border-t border-black text-[8px] font-bold text-center py-0.5 uppercase">{{ $label }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tabel LHI --}}
        <table class="w-full border-collapse border border-black text-[10px] text-black bg-white" style="font-family:'Times New Roman', Times, serif">
            <thead>
                <tr>
                    <th rowspan="2" class="border border-black px-1 py-1 font-bold w-[3%]">NO.</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-bold w-[8%]">JOB NO.</th>
                    <th rowspan="2" class="border border-black px-2 py-1 font-bold w-[24%]">NAMA PART'S/ SKETCH</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-bold w-[8%]">PROSES</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-bold w-[17%]">PROBLEM</th>
                    <th colspan="5" class="border border-black px-1 py-1 font-bold text-center">PENYEBAB</th>
                    <th colspan="3" class="border border-black px-1 py-1 font-bold text-center tracking-[0.2em]">T O T A L</th>
                </tr>
                <tr>
                    @foreach(['MTR','DIE','OPR','HDL','MSN'] as $h) <th class="border border-black px-1 py-1 font-bold w-[4%]">{{ $h }}</th> @endforeach
                    @foreach(['REPAIR','REJECT','PRODUKSI'] as $h) <th class="border border-black px-1 py-1 font-bold w-[6%]">{{ $h }}</th> @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Skeleton Loading State --}}
                <template x-if="loading">
                    <template x-for="i in Array.from({length: 8})">
                        <tr class="h-[23px] animate-pulse">
                            <td class="border border-black bg-slate-100"></td>
                            <td class="border border-black bg-slate-50"></td>
                            <td class="border border-black bg-slate-100"></td>
                            <td class="border border-black bg-slate-50"></td>
                            <td class="border border-black bg-slate-100"></td>
                            @for($k=0; $k<5; $k++) <td class="border border-black bg-slate-50"></td> @endfor
                            <td class="border border-black bg-slate-100"></td>
                            <td class="border border-black bg-slate-50"></td>
                            <td class="border border-black bg-slate-100"></td>
                        </tr>
                    </template>
                </template>


                {{-- Data Rows --}}
                <template x-if="!loading">
                    <template x-for="(q, idx) in pageData" :key="q.id">
                        <tr class="h-[23px] lhi-row-hover">
                            <td class="border border-black text-center" x-text="(page-1)*20 + idx + 1"></td>
                            <td class="border border-black text-center font-black" x-text="q.job_no || '—'"></td>
                            <td class="border border-black px-2 font-semibold" x-text="q.part_name || '—'"></td>
                            <td class="border border-black p-0 text-center font-black text-slate-800 align-middle">
                                <span x-text="q.computed_proses || q.proses_route || ''"></span>
                            </td>
                            <td class="border border-black text-center font-black p-0 align-middle" 
                                :class="((q.computed_problem || q.qg_judgement) === 'NG' || q.computed_problem) ? 'text-red-600 bg-red-50' : ''">
                                <span x-text="q.computed_problem || q.qg_judgement || 'OK'"></span>
                            </td>
                            
                            {{-- Penyebab Checkboxes --}}
                            <template x-for="k in ['mtr','die','opr','hdl','msn']">
                                <td class="border border-black text-center select-none text-base font-black text-red-600 align-middle" 
                                    x-text="q.computed_causes[k]"></td>
                            </template>

                            <td class="border border-black text-center" x-text="q.repair || ''"></td>
                            <td class="border border-black text-center font-black" :class="q.reject > 0 ? 'text-red-600' : ''" x-text="q.reject || ''"></td>
                            <td class="border border-black text-center font-black" x-text="q.total_produksi || ''"></td>
                        </tr>
                    </template>
                </template>

                {{-- Empty Rows (only when data loaded) --}}
                <template x-if="!loading">
                    <template x-for="i in Array.from({length: Math.max(0, 20 - pageData.length)})">
                        <tr class="h-[23px]">
                            <td class="border border-black border-opacity-30"></td>
                            @for($j=0;$j<12;$j++) <td class="border border-black border-opacity-30"></td> @endfor
                        </tr>
                    </template>
                </template>
            </tbody>

        </table>

        {{-- Catatan --}}
        <div class="mt-2">
            <div class="border border-black p-2 min-h-[40px] text-[10px] font-black">
                CATATAN :
                <textarea x-model="catatan" @input.debounce.500ms="silentSave()" class="no-print block w-full border-none outline-none resize-none mt-1 font-semibold" rows="1" placeholder="Tulis catatan harian di sini..."></textarea>
                <div class="print-only mt-1 whitespace-pre-wrap" x-text="catatan"></div>
            </div>
            <div class="text-right text-[9px] font-black text-slate-400 mt-1 uppercase tracking-widest italic">Laporan Harian Inspeksi Press Line</div>
        </div>
    </div>

    {{-- SIG PAD MODAL --}}
    <div x-show="activePad" x-cloak class="fixed inset-0 z-[10000] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight" x-text="'TTD ' + activePad"></h3>
                <button @click="activePad=null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <canvas id="lhi-sig-canvas" width="600" height="240" class="w-full border-2 border-slate-200 rounded-2xl bg-white touch-none cursor-crosshair" style="height:120px"></canvas>
            <div class="flex gap-3 mt-4">
                <button @click="clearCanvas()" class="flex-1 py-3 border-2 border-slate-200 rounded-2xl text-xs font-bold text-slate-500 hover:bg-slate-50">Hapus</button>
                <button @click="saveTtd()" class="flex-[2] py-3 bg-emerald-600 text-white rounded-2xl text-xs font-black shadow-lg shadow-emerald-600/20">✓ Simpan TTD</button>
            </div>
        </div>
    </div>

</div>

<script>
function lhiPage() {
    return {
        allData: [],
        loading: true,
        saving: false,
        toast: null,
        filterTanggal: new Date().toISOString().slice(0, 10),
        _filterTimeout: null,
        filterLine: 'Semua Line',
        page: 1,
        catatan: '',
        ttdData: { approved: null, checked: null, prepared: null },
        activePad: null,
        sigPad: null,

        lineOptions: ['Semua Line', 'PRESS A', 'PRESS B', 'PRESS C', 'PRESS D'],

        get filteredData() {
            return this.allData.filter(q => {
                const tgl = String(q.tgl_bulan || '').slice(0, 10);
                if (this.filterTanggal && tgl !== this.filterTanggal) return false;
                if (this.filterLine !== 'Semua Line') {
                    const lok = String(q.lokasi || '').toLowerCase();
                    const ln = this.filterLine.replace('Line ', '').toLowerCase();
                    if (!lok.includes(this.filterLine.toLowerCase()) && !lok.includes(ln + '-line') && !lok.includes('line' + ln)) return false;
                }
                return true;
            });
        },

        get filteredCount() { return this.filteredData.length; },
        get totalPages() { return Math.max(1, Math.ceil(this.filteredCount / 20)); },
        get pageData() { return this.filteredData.slice((this.page - 1) * 20, this.page * 20); },
        get firstDate() {
            const d = this.pageData[0]?.tgl_bulan;
            if (!d) return '';
            const [y, m, day] = d.split('T')[0].split('-');
            return `${day}-${m}-${y.slice(2)}`;
        },

        async init() {
            this.loading = true;
            this.page = 1;
            try {
                // Filter tanggal di backend ItemCheck
                const params = new URLSearchParams({
                    tanggal: this.filterTanggal,
                });
                const res = await fetch('/api/item-check/summary?' + params.toString());
                const data = await res.json();
                let rawData = (Array.isArray(data) ? data : data.data || []);

                // Sort ascending by id (urutan kronologis)
                rawData.sort((a, b) => a.id - b.id);

                // Hitung computed_* SEBELUM assign ke allData
                let processedData = rawData.map(q => {
                    q.computed_proses   = '';
                    q.computed_problem  = '';
                    q.computed_causes   = { mtr: '', die: '', opr: '', hdl: '', msn: '' };

                    let ng = q.ng_details;
                    if (typeof ng === 'string') {
                        try { ng = JSON.parse(ng); } catch(e) { ng = null; }
                    }

                    let detailList = [];
                    if (Array.isArray(ng)) {
                        detailList = ng.filter(d => d && typeof d === 'object');
                    } else if (ng && typeof ng === 'object' && Object.keys(ng).length > 0) {
                        detailList = Object.values(ng);
                    }

                    if (detailList.length > 0) {
                        const allProblems = new Set();
                        const allCauses   = new Set();
                        const allProses   = new Set();

                        detailList.forEach(detail => {
                            if (!detail || typeof detail !== 'object') return;
                            if (detail.proses) {
                                String(detail.proses).split(',').forEach(pr => {
                                    const t = pr.trim(); if (t) allProses.add(t);
                                });
                            }
                            const problems = detail.problems || detail.problem || [];
                            (Array.isArray(problems) ? problems : [problems]).forEach(p => {
                                if (p) allProblems.add(String(p));
                            });
                            const causes = detail.causes || detail.penyebab || [];
                            (Array.isArray(causes) ? causes : [causes]).forEach(c => {
                                if (c) allCauses.add(String(c).toLowerCase());
                            });
                        });

                        if (allProses.size > 0)  q.computed_proses  = [...allProses].sort().join(', ');
                        if (allProblems.size > 0) q.computed_problem = [...allProblems].join(', ');
                        allCauses.forEach(c => {
                            if (['mtr','die','opr','hdl','msn'].includes(c)) q.computed_causes[c] = '✓';
                        });
                    }

                    return q;
                });

                // Group Tandem parts by schedule_id
                let groupedData = [];
                let seenSchedules = new Set();

                for (let q of processedData) {
                    if (q.schedule_id) {
                        if (!seenSchedules.has(q.schedule_id)) {
                            seenSchedules.add(q.schedule_id);
                            
                            let tandemItems = processedData.filter(i => i.schedule_id === q.schedule_id);
                            
                            if (tandemItems.length > 1) {
                                let displayItem = JSON.parse(JSON.stringify(tandemItems[0]));
                                
                                // Merge part names (RH/LH)
                                let p1 = tandemItems[0].part_name || '';
                                let p2 = tandemItems[1].part_name || '';
                                if (p1 && p2) {
                                    let p1Base = p1.replace(/RH|LH/gi, '').trim();
                                    let p2Base = p2.replace(/RH|LH/gi, '').trim();
                                    if (p1Base === p2Base && p1Base !== '') {
                                        displayItem.part_name = p1Base + ' RH/LH';
                                    } else {
                                        displayItem.part_name = p1 + ' / ' + p2;
                                    }
                                }
                                
                                // Merge job numbers
                                let j1 = tandemItems[0].job_no || '';
                                let j2 = tandemItems[1].job_no || '';
                                if (j1 && j2) {
                                    if (j1 !== j2) {
                                        if (j1.length === j2.length && j1.slice(0, -2) === j2.slice(0, -2)) {
                                            displayItem.job_no = j1 + '/' + j2.slice(-2);
                                        } else {
                                            displayItem.job_no = j1 + ' / ' + j2;
                                        }
                                    } else {
                                        // j1 === j2 (Keduanya pakai Job No yang sama karena kesalahan input di master/schedule)
                                        // Kita generate otomatis nomor pasangannya. (Misal K4047 jadi K4047/48)
                                        let match = j1.match(/^([a-zA-Z\-_]+)(\d+)$/);
                                        if (match) {
                                            let prefix = match[1];
                                            let numStr = match[2];
                                            let num = parseInt(numStr, 10);
                                            
                                            // Biasanya RH ganjil, LH genap. Jika ganjil maka pasangannya +1, jika genap pasangannya -1.
                                            let nextNum = (num % 2 !== 0) ? num + 1 : num - 1;
                                            
                                            let smaller = Math.min(num, nextNum);
                                            let larger = Math.max(num, nextNum);
                                            
                                            let smallerStr = smaller.toString().padStart(numStr.length, '0');
                                            let largerStr = larger.toString().padStart(numStr.length, '0');
                                            
                                            displayItem.job_no = prefix + smallerStr + '/' + largerStr.slice(-2);
                                        } else {
                                            displayItem.job_no = j1;
                                        }
                                    }
                                }
                                
                                // Aggregate values
                                displayItem.repair = tandemItems.reduce((sum, i) => sum + (parseInt(i.repair) || 0), 0);
                                displayItem.reject = tandemItems.reduce((sum, i) => sum + (parseInt(i.reject) || 0), 0);
                                displayItem.total_produksi = Math.max(...tandemItems.map(i => parseInt(i.total_produksi) || 0));
                                
                                // Merge problems, proses, causes
                                let allProbs = new Set();
                                let allProses = new Set();
                                let mergedCauses = { mtr: '', die: '', opr: '', hdl: '', msn: '' };
                                
                                tandemItems.forEach(i => {
                                    if (i.computed_problem) i.computed_problem.split(', ').forEach(p => allProbs.add(p));
                                    if (i.computed_proses) i.computed_proses.split(', ').forEach(p => allProses.add(p));
                                    Object.keys(i.computed_causes).forEach(k => {
                                        if (i.computed_causes[k]) mergedCauses[k] = '✓';
                                    });
                                });
                                
                                displayItem.computed_problem = Array.from(allProbs).join(', ');
                                displayItem.computed_proses = Array.from(allProses).sort().join(', ');
                                displayItem.computed_causes = mergedCauses;
                                
                                // Merge Judgement
                                displayItem.qg_judgement = tandemItems.some(i => i.qg_judgement === 'NG') ? 'NG' : (tandemItems.some(i => i.qg_judgement === 'OK') ? 'OK' : '');
                                
                                groupedData.push(displayItem);
                            } else {
                                groupedData.push(q);
                            }
                        }
                    } else {
                        groupedData.push(q);
                    }
                }

                this.allData = groupedData;

                this.loadFromLocalStorage();

            } catch (e) { console.error('LHI init error:', e); }
            finally { this.loading = false; }
        },

        // Dipanggil saat filter berubah — debounced 300ms agar tidak spam API
        onFilterChange() {
            clearTimeout(this._filterTimeout);
            this._filterTimeout = setTimeout(() => this.init(), 300);
        },

        loadFromLocalStorage() {
            const lsKey = `lhi_data_${this.filterTanggal}_${this.filterLine.replace(/\s+/g, '_')}`;
            
            const ttdSaved = localStorage.getItem('lhi_ttd');
            if (ttdSaved) this.ttdData = JSON.parse(ttdSaved);
            
            const catSaved = localStorage.getItem('lhi_catatan_' + lsKey);
            this.catatan = catSaved || '';
        },

        silentSave() {
            const lsKey = `lhi_data_${this.filterTanggal}_${this.filterLine.replace(/\s+/g, '_')}`;
            localStorage.setItem('lhi_ttd', JSON.stringify(this.ttdData));
            localStorage.setItem('lhi_catatan_' + lsKey, this.catatan);
        },

        showToast(type, msg) {
            this.toast = { type, msg };
            setTimeout(() => this.toast = null, 3000);
        },

        openSigPad(role) {
            this.activePad = role;
            this.$nextTick(() => {
                const canvas = document.getElementById('lhi-sig-canvas');
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                this.sigPad = {
                    drawing: false,
                    ctx,
                    canvas,
                    start(e) {
                        e.preventDefault();
                        this.drawing = true;
                        const p = this.getPos(e);
                        this.ctx.beginPath();
                        this.ctx.moveTo(p.x, p.y);
                    },
                    move(e) {
                        e.preventDefault();
                        if (!this.drawing) return;
                        const p = this.getPos(e);
                        this.ctx.lineWidth = 2.5;
                        this.ctx.lineCap = 'round';
                        this.ctx.strokeStyle = '#000';
                        this.ctx.lineTo(p.x, p.y);
                        this.ctx.stroke();
                    },
                    stop() { this.drawing = false; },
                    getPos(e) {
                        const r = this.canvas.getBoundingClientRect();
                        const s = e.touches ? e.touches[0] : e;
                        return { x: (s.clientX - r.left) * (this.canvas.width / r.width), y: (s.clientY - r.top) * (this.canvas.height / r.height) };
                    }
                };
                canvas.addEventListener('mousedown', e => this.sigPad.start(e));
                canvas.addEventListener('mousemove', e => this.sigPad.move(e));
                canvas.addEventListener('mouseup', () => this.sigPad.stop());
                canvas.addEventListener('touchstart', e => this.sigPad.start(e));
                canvas.addEventListener('touchmove', e => this.sigPad.move(e));
                canvas.addEventListener('touchend', () => this.sigPad.stop());
            });
        },

        clearCanvas() {
            const canvas = document.getElementById('lhi-sig-canvas');
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        },

        saveTtd() {
            const canvas = document.getElementById('lhi-sig-canvas');
            this.ttdData[this.activePad] = canvas.toDataURL();
            this.activePad = null;
            this.silentSave();
        },

        clearTtd(role) {
            this.ttdData[role] = null;
            this.silentSave();
        }
    }
}
</script>
</x-app-layout>
