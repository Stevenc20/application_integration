@extends('layouts.app')
@section('content')
<div class="qpr-reg-wrap min-h-screen p-5" x-data="qprRegPage()" x-init="init()">
    {{-- Styles --}}
    <style>
        .qpr-reg-wrap { font-family: 'Plus Jakarta Sans', sans-serif; }
        .qpr-reg-wrap * { box-sizing: border-box; }
        .qpr-reg-wrap input:focus { outline: none; }
        .qpr-reg-wrap input[type="date"]::-webkit-calendar-picker-indicator { opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .qpr-reg-btn { transition: all 0.15s ease; }
        .qpr-reg-btn:hover:not(:disabled) { filter: brightness(0.9); transform: translateY(-1px); }
        @keyframes qprSpin { to { transform: rotate(360deg); } }
        @keyframes qprToastIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
        .qpr-toast { animation: qprToastIn 0.3s ease forwards; }
        @media print {
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 7mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .qpr-reg-wrap input[type="date"]::-webkit-calendar-picker-indicator { display: none !important; }
        }
    </style>

    {{-- TOAST --}}
    <template x-if="toast">
        <div class="qpr-toast fixed top-5 right-5 z-[9999] px-5 py-3 rounded-xl shadow-2xl text-sm font-bold border"
             :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
            <span x-text="toast.type === 'success' ? '✅' : '❌'"></span>
            <span x-text="toast.msg"></span>
        </div>
    </template>

    {{-- FILTER BAR --}}
    <div class="no-print max-w-[1160px] mx-auto mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        
        {{-- Title --}}
        <div class="shrink-0">
            <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight leading-none mb-2">Registrasi QPR</h1>
            <p class="text-[10px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest flex items-center gap-2">
                <span class="text-red-500">Quality Problem Report</span>
            </p>
        </div>

        {{-- Filters & Actions --}}
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 lg:ml-auto">
            
            {{-- Filter Group --}}
            <div class="flex flex-wrap items-center gap-2 bg-slate-50/50 p-1.5 rounded-2xl border border-slate-100">
                <select x-model="filterBulan" class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 focus:border-red-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all cursor-pointer shadow-sm flex-1 sm:flex-none">
                    <option value="">Semua Bulan</option>
                    <template x-for="(label, val) in bulanLabel" :key="val">
                        <option :value="val" x-text="label"></option>
                    </template>
                </select>
                <select x-model="filterTahun" class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 focus:border-red-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all cursor-pointer shadow-sm flex-1 sm:flex-none">
                    <template x-for="y in tahunOptions" :key="y">
                        <option :value="y" x-text="y || 'Semua'"></option>
                    </template>
                </select>
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
                        <span x-text="loading ? '...' : filteredCount + ' QPR'"></span>
                        <span class="text-slate-300 mx-1">|</span>
                        <span class="text-slate-400">HAL</span> <span x-text="loading ? '-' : page + '/' + totalPages"></span>
                    </div>
                    <button @click="page = Math.min(totalPages, page + 1)" :disabled="page === totalPages" class="w-8 h-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-transparent transition-colors font-bold text-lg rounded-lg">›</button>
                </div>

                {{-- Print Button --}}
                <button @click="window.print()" class="h-[42px] px-6 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-black tracking-wide flex items-center justify-center gap-2 shadow-[0_4px_12px_rgba(30,41,59,0.15)] hover:shadow-[0_6px_16px_rgba(30,41,59,0.25)] hover:-translate-y-0.5 transition-all w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="hidden sm:inline">CETAK</span>
                </button>
            </div>
        </div>
    </div>

    {{-- FORMULIR KERTAS --}}
    <div id="print-area" class="max-w-[1160px] mx-auto bg-white border border-slate-400 p-4 shadow-2xl overflow-x-auto">
        <div class="flex justify-between items-center border-b-2 border-black pb-1 mb-1">
            <div class="text-[11px] font-black tracking-tight">PT. INTI PANTJA PRESS INDUSTRI</div>
            <div class="text-[10px] font-black tracking-widest uppercase">FISM-QAD-03-03-03</div>
        </div>

        <div class="text-center mb-1">
            <h2 class="text-sm font-black uppercase tracking-widest" style="font-family:'Sarabun'">Formulir Registrasi Quality Problem Report</h2>
            <div class="flex justify-center gap-12 mt-1 text-[11px] font-bold" style="font-family:'Sarabun'">
                <span><strong>MONTH :</strong> <span class="border-b border-black border-dotted min-w-[80px] inline-block px-2 text-left" x-text="headerMonth"></span></span>
                <span><strong>PAGE :</strong> <span class="border-b border-black border-dotted min-w-[28px] inline-block px-1 text-center" x-text="page"></span></span>
                <span><strong>OF :</strong> <span class="border-b border-black border-dotted min-w-[70px] inline-block px-2 text-left" x-text="filterLine"></span></span>
            </div>
        </div>

        {{-- Legend --}}
        <div class="flex justify-end items-center gap-4 my-2 pr-1 font-bold text-[10px]" style="font-family:'Sarabun'">
            <span>HASIL :</span>
            <template x-for="opt in hasilOptions.filter(o => o.value)">
                <span class="flex items-center gap-1">
                    <span x-html="renderCircleIcon(opt.iconType, 14)"></span>
                    <span x-text="opt.label"></span>
                </span>
            </template>
            <span class="flex items-center gap-1">
                <span x-html="renderCircleIcon('full', 14)"></span>
                <span>OK (KASUS SELESAI)</span>
            </span>
        </div>

        {{-- Tabel --}}
        <table class="w-full border-collapse border border-black text-[10px]" style="font-family:'Sarabun'">
            <thead>
                <tr class="bg-slate-50">
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[3%]">NO.</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[14%]">REPORT NO.</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[8%]">DATE OF ISSUE</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[25%]">PROBLEM DESCRIPTION</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[9%]">INVESTIGATOR</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[8%]">TARGET SELESAI</th>
                    <th colspan="3" class="border border-black px-1 py-1 font-black text-center">VERIFIKASI</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[7%]">HASIL</th>
                    <th rowspan="2" class="border border-black px-1 py-1 font-black w-[7%]">REMARK</th>
                </tr>
                <tr class="bg-slate-50">
                    @foreach(['I','II','III'] as $v) <th class="border border-black px-1 py-1 font-black w-[6%]">{{ $v }}</th> @endforeach
                </tr>
            </thead>
            <tbody>
                <template x-for="(q, idx) in pageData" :key="q.id">
                    <tr class="h-[26px]">
                        <td class="border border-black text-center font-bold" x-text="(page-1)*20 + idx + 1 + '.'"></td>
                        <td class="border border-black text-center p-0 font-bold text-red-600 font-mono text-[9px]" x-text="q.no_qpr || '—'"></td>
                        <!-- DATE OF ISSUE: dari tgl_bulan LI (tanggal item check NG) -->
                        <td class="border border-black text-center" x-text="fmtDate(q.date_of_issue || q.tanggal)"></td>
                        <!-- PROBLEM DESCRIPTION: no_job + semua defect NG -->
                        <td class="border border-black px-2 overflow-hidden text-ellipsis whitespace-nowrap" :title="q.problem_desc" x-text="q.problem_desc || '—'"></td>
                        <!-- INVESTIGATOR: nama yang item check -->
                        <td class="border border-black text-center" x-text="q.investigator_name || getInvestigator(q)"></td>
                        <td class="border border-black p-0">
                            <input type="text" x-model="getEdit(q.id).target_selesai" class="w-full h-full text-center border-none bg-transparent text-[9px] outline-none">
                        </td>
                        <template x-for="k in ['verif_1','verif_2','verif_3']">
                            <td class="border border-black p-0">
                                <input x-model="getEdit(q.id)[k]" class="w-full h-full text-center border-none bg-transparent text-[9px] outline-none">
                            </td>
                        </template>
                        <td class="border border-black p-0 text-center">
                            <div class="flex items-center justify-center gap-1 h-full px-1" title="Diambil otomatis dari PDCA (Action)">
                                <span x-html="renderCircleIcon(getHasilInfo(q.hasil).iconType, 14)"></span>
                                <span class="text-[9px] font-bold" x-text="getHasilInfo(q.hasil).label"></span>
                            </div>
                        </td>
                        <td class="border border-black text-center p-0 relative h-[26px]">
                            <template x-if="q.investigator_sig">
                                <div class="flex items-center justify-center w-full h-full p-0.5" title="TTD Investigator">
                                    <img :src="q.investigator_sig" class="max-h-[22px] max-w-full object-contain mix-blend-multiply">
                                </div>
                            </template>
                            <template x-if="!q.investigator_sig">
                                <span class="text-[8px] text-slate-400 italic">Belum TTD</span>
                            </template>
                        </td>
                    </tr>
                </template>
                <template x-for="(_, i) in Array.from({length: Math.max(0, 20 - pageData.length)})">
                    <tr class="h-[26px]">
                        <td class="border border-black text-center text-slate-300 font-bold" x-text="(pageData.length + i + 1) + '.'"></td>
                        @for($j=0;$j<10;$j++) <td class="border border-black"></td> @endfor
                    </tr>
                </template>
            </tbody>
        </table>

        <div class="flex justify-between mt-1 text-[9px] text-slate-500 italic">
            <span x-text="(filterLine !== 'Semua Line' ? filterLine + ' · ' : '') + headerMonth + ' — Hal ' + page + '/' + totalPages"></span>
            <span>ISM-QAD-03-03-03 Rev.0 ( Pengisian QPR )</span>
        </div>
    </div>

    {{-- MODAL SIG PAD --}}
    <div x-show="openSignFor !== null" x-cloak class="fixed inset-0 z-[10000] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 w-full max-w-sm shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">Tanda Tangan Remark</h3>
                <button @click="openSignFor=null" class="text-slate-400">✕</button>
            </div>
            <canvas id="qpr-sig-canvas" width="600" height="240" class="w-full border-2 border-slate-200 rounded-2xl bg-white touch-none cursor-crosshair" style="height:120px"></canvas>
            <div class="flex gap-3 mt-4">
                <button @click="clearCanvas()" class="flex-1 py-3 border-2 border-slate-200 rounded-2xl text-[11px] font-bold text-slate-500">Hapus</button>
                <button @click="saveTtd()" class="flex-[2] py-3 bg-emerald-600 text-white rounded-2xl text-[11px] font-black">✓ Simpan TTD</button>
            </div>
        </div>
    </div>

</div>

<script>
function qprRegPage() {
    return {
        allData: [],
        loading: true,
        saving: false,
        toast: null,
        filterBulan: String(new Date().getMonth() + 1).padStart(2, '0'),
        filterTahun: String(new Date().getFullYear()),
        filterLine: 'Semua Line',
        page: 1,
        editMap: {},
        openSignFor: null,
        sigPad: null,

        bulanLabel: {'01':'Januari','02':'Februari','03':'Maret','04':'April','05':'Mei','06':'Juni','07':'Juli','08':'Agustus','09':'September','10':'Oktober','11':'November','12':'Desember'},
        lineOptions: ['Semua Line', 'PRESS A', 'PRESS B', 'PRESS C', 'PRESS D'],
        hasilOptions: [
            { value: '',      label: '—',    iconType: 'empty' },
            { value: 'plan',  label: 'PLAN', iconType: 'quarter' },
            { value: 'do',    label: 'DO',   iconType: 'half' },
            { value: 'check', label: 'CHECK',iconType: 'threequarter' },
            { value: 'ok',    label: 'OK',   iconType: 'full' },
        ],

        get tahunOptions() {
            const years = new Set(this.allData.map(q => String(q.tanggal || '').slice(0, 4)).filter(Boolean));
            years.add(String(new Date().getFullYear()));
            return ['', ...Array.from(years).sort((a,b) => b-a)];
        },

        get filteredData() {
            return this.allData.filter(q => {
                // Filter berdasarkan date_of_issue (tanggal item check NG), bukan tanggal buat QPR
                const tgl = String(q.date_of_issue || q.tanggal || '').slice(0, 10);
                if (this.filterBulan && tgl.slice(5, 7) !== this.filterBulan) return false;
                if (this.filterTahun && tgl.slice(0, 4) !== this.filterTahun) return false;
                if (this.filterLine !== 'Semua Line') {
                    const lok = String(q.lokasi || '').toLowerCase();
                    const fl = this.filterLine.toLowerCase();
                    if (!lok.includes(fl) && !lok.includes(fl.replace('line ', ''))) return false;
                }
                return true;
            });
        },

        get filteredCount() { return this.filteredData.length; },
        get totalPages() { return Math.max(1, Math.ceil(this.filteredCount / 20)); },
        get pageData() { return this.filteredData.slice((this.page - 1) * 20, this.page * 20); },
        get headerMonth() {
            // Gunakan bulan dari date_of_issue (tanggal item check), bukan filter saja
            return this.filterBulan ? this.bulanLabel[this.filterBulan] + ' ' + this.filterTahun : this.filterTahun || '—';
        },

        async init() {
            this.loading = true;
            try {
                const res = await fetch('/api/qprs');
                const data = await res.json();
                const raw = (Array.isArray(data) ? data : data.data || []).sort((a,b) => a.id - b.id);
                this.allData = raw;
                
                const initialMap = {};
                raw.forEach(q => {
                    initialMap[q.id] = {
                        target_selesai: q.target_selesai ? q.target_selesai.slice(0, 10) : '',
                        verif_1: q.verif_1 || '',
                        verif_2: q.verif_2 || '',
                        verif_3: q.verif_3 || '',
                    };
                });
                
                const savedDraft = localStorage.getItem('qpr_reg_draft');
                this.editMap = savedDraft ? { ...initialMap, ...JSON.parse(savedDraft) } : initialMap;
            } catch (e) { console.error(e); }
            finally { 
                this.loading = false; 
                if (typeof window.hideSkeleton === 'function') window.hideSkeleton();
            }
        },

        getEdit(id) {
            if (!this.editMap[id]) this.editMap[id] = { target_selesai: '', verif_1: '', verif_2: '', verif_3: '' };
            return this.editMap[id];
        },

        fmtDate(v) {
            if (!v) return '';
            const [y, m, d] = v.split('T')[0].split('-');
            return `${d}.${m}.${y}`;
        },

        getInvestigator(q) {
            try {
                const sigs = typeof q.approval_signatures === 'string' ? JSON.parse(q.approval_signatures) : (Array.isArray(q.approval_signatures) ? q.approval_signatures : []);
                const op = sigs.find(s => s.position === 'operator');
                return op?.nama || q.pic || '';
            } catch { return q.pic || ''; }
        },

        getHasilInfo(val) {
            const map = {
                'p': 'plan', 'plan': 'plan',
                'd': 'do', 'do': 'do',
                'c': 'check', 'check': 'check',
                'a': 'ok', 'ok': 'ok', 'action': 'ok'
            };
            const normalized = map[(val || '').toLowerCase()] || '';
            return this.hasilOptions.find(h => h.value === normalized) || this.hasilOptions[0];
        },

        renderCircleIcon(type, size) {
            const r = size / 2 - 1.2, cx = size / 2, cy = size / 2, color = '#000';
            if (type === 'empty') return ''; // Jangan render icon jika empty

            const paths = {
                quarter: `M ${cx} ${cy} L ${cx} ${cy - r} A ${r} ${r} 0 0 1 ${cx + r} ${cy} Z`,
                half: `M ${cx} ${cy} L ${cx} ${cy - r} A ${r} ${r} 0 0 0 ${cx} ${cy + r} Z`,
                threequarter: `M ${cx} ${cy} L ${cx} ${cy - r} A ${r} ${r} 0 1 1 ${cx - r} ${cy} Z`,
                full: `M ${cx} ${cy - r} A ${r} ${r} 0 1 1 ${cx - 0.001} ${cy - r} Z`,
            };
            return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" style="display:inline-block;vertical-align:middle">
                <circle cx="${cx}" cy="${cy}" r="${r}" fill="white" stroke="${color}" stroke-width="1.4" />
                ${paths[type] ? `<path d="${paths[type]}" fill="${color}" />` : ''}
                ${type === 'full' ? `
                <line x1="${cx}" y1="${cy - r}" x2="${cx}" y2="${cy + r}" stroke="white" stroke-width="0.7" />
                <line x1="${cx - r}" y1="${cy}" x2="${cx + r}" y2="${cy}" stroke="white" stroke-width="0.7" />
                ` : ''}
            </svg>`;
        },

        async handleSaveAll() {
            this.saving = true;
            try {
                const changedIds = Object.keys(this.editMap);
                const promises = changedIds.map(id => {
                    return fetch(`/api/qprs/${id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.editMap[id])
                    });
                });
                await Promise.all(promises);
                localStorage.removeItem('qpr_reg_draft');
                this.showToast('success', 'Semua perubahan berhasil disimpan!');
                this.init();
            } catch (e) { this.showToast('error', 'Gagal: ' + e.message); }
            finally { this.saving = false; }
        },

        showToast(type, msg) {
            this.toast = { type, msg };
            setTimeout(() => this.toast = null, 3000);
        },

        openSignForModal(id) {
            this.openSignFor = id;
            this.$nextTick(() => {
                const canvas = document.getElementById('qpr-sig-canvas');
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
                this.sigPad = {
                    drawing: false, ctx, canvas,
                    start(e) { e.preventDefault(); this.drawing = true; const p = this.getPos(e); this.ctx.beginPath(); this.ctx.moveTo(p.x, p.y); },
                    move(e) { e.preventDefault(); if (!this.drawing) return; const p = this.getPos(e); this.ctx.lineWidth = 2.5; this.ctx.lineCap = 'round'; this.ctx.strokeStyle = '#000'; this.ctx.lineTo(p.x, p.y); this.ctx.stroke(); },
                    stop() { this.drawing = false; },
                    getPos(e) { const r = this.canvas.getBoundingClientRect(); const s = e.touches ? e.touches[0] : e; return { x: (s.clientX-r.left)*(this.canvas.width/r.width), y: (s.clientY-r.top)*(this.canvas.height/r.height) }; }
                };
                canvas.onmousedown = e => this.sigPad.start(e);
                canvas.onmousemove = e => this.sigPad.move(e);
                canvas.onmouseup = () => this.sigPad.stop();
                canvas.ontouchstart = e => this.sigPad.start(e);
                canvas.ontouchmove = e => this.sigPad.move(e);
                canvas.ontouchend = () => this.sigPad.stop();
            });
        },

        clearCanvas() {
            const ctx = document.getElementById('qpr-sig-canvas').getContext('2d');
            ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 600, 240);
        },

        saveTtd() {
            this.editMap[this.openSignFor].remark = document.getElementById('qpr-sig-canvas').toDataURL();
            this.openSignFor = null;
            this.showToast('success', 'TTD Remark disimpan');
        }
    }
}
</script>
@endsection
