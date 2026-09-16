<x-app-layout pageTitle="Dashboard">

    @if(in_array(auth()->user()->role, ['Supervisor', 'Foreman', 'Leader', 'Group Leader', 'Manager', 'Admin']))
    @php
        $activeLiDocs = collect();
        if(in_array(auth()->user()->role, ['Supervisor', 'Manager', 'Admin', 'Foreman', 'Leader', 'Group Leader'])) {
            
            // 1. Fetch Master Lembar Inspeksi (LI) yang belum approved
            $liDocs = \App\Models\LembarInspeksi::whereNotIn('status', ['published', 'approved', 'locked', 'finished'])
                ->orderBy('updated_at', 'desc')->take(5)->get()->map(function($d) {
                    $step = 1; $label = 'Draft';
                    if (in_array($d->status, ['draft'])) { $step = 1; $label = 'Pembuatan Form'; }
                    elseif ($d->status === 'waiting_foreman') { $step = 2; $label = 'Review Foreman'; }
                    elseif ($d->status === 'waiting_supervisor') { $step = 3; $label = 'Verifikasi Supervisor'; }
                    
                    return [
                        'id' => $d->id,
                        'type' => 'Lembar Inspeksi',
                        'no_form' => $d->no_form ?? 'LI-NEW',
                        'no_job' => $d->job_no ?? '-',
                        'info' => $d->part_name ?? '-',
                        'step' => $step,
                        'statusLabel' => $label,
                        'url' => '/li/' . $d->id . '/edit',
                        'date' => $d->updated_at,
                        'created_at' => $d->created_at,
                    ];
                });

            // 2. Fetch Item Check (IC) yang sedang berjalan
            $icDocs = \App\Models\ItemCheck::with(['masterTemplate'])
                ->whereNotIn('status', ['finished', 'locked'])
                ->orderBy('updated_at', 'desc')->take(5)->get()->map(function($d) {
                    // Step semantics: step = "we are currently AT this step"
                    // 1 = Operator inspecting   → Inspeksi Aktual (Proses)
                    // 2 = GL reviewing/signing  → Review Pengecekan (Proses)
                    // 3 = Foreman signing       → Pengesahan Inspeksi (Proses)
                    // 4 = Done
                    $step = 1; $label = 'Pengecekan Aktual';

                    if (!empty($d->paraf_foreman)) {
                        // GL has signed → GL step done, now waiting for Foreman
                        $step = 3; $label = 'Menunggu Pengesahan Foreman';
                    } elseif ($d->status === 'waiting_qc_approval' || $d->status === 'waiting_gl') {
                        // GL reviewing but not yet signed
                        $step = 2; $label = 'Review Group Leader';
                    }

                    if (!empty($d->paraf_leader)) {
                        // Foreman also signed → all done
                        $step = 4; $label = 'Selesai';
                    }
                    
                    return [
                        'id' => $d->id,
                        'type' => 'Item Check',
                        'no_form' => 'IC-' . str_pad($d->id, 5, '0', STR_PAD_LEFT),
                        'no_job' => $d->masterTemplate->job_no ?? '-',
                        'line' => $d->schedule->line ?? '-',
                        'info' => $d->masterTemplate->part_name ?? '-',
                        'step' => $step,
                        'statusLabel' => $label,
                        'url' => '/item-check/' . $d->id . '/form',
                        'date' => $d->updated_at,
                        'created_at' => $d->created_at,
                    ];
                });

            // 3. Fetch QPR yang belum selesai
            $qprDocs = \App\Models\Qpr::whereNotIn('status', ['Close', 'Closed', 'closed', 'close', 'approved', 'finished'])
                ->orderBy('updated_at', 'desc')->take(5)->get()->map(function($d) {
                    $step = 1; $label = 'Proses Lanjutan';
                    $s = strtolower($d->status ?? '');
                    // 'open' + sudah ada foreman = sudah diteruskan ke GL/FM → step 2
                    if ($s === 'open' && !empty($d->assigned_foreman_id)) { $step = 2; $label = 'Pengecekan Awal (GL/FM)'; }
                    elseif (in_array($s, ['draft', 'open', 'revision'])) { $step = 1; $label = 'Investigasi Temuan'; }
                    elseif (in_array($s, ['pending approval', 'gl approved'])) { $step = 2; $label = 'Pengecekan Awal (GL)'; }
                    elseif (str_contains($s, 'action') || str_contains($s, 'progress') || str_contains($s, 'a3')) { $step = 3; $label = 'Tindakan Seksi Terkait'; }
                    elseif (str_contains($s, 'verif 1')) { $step = 4; $label = 'Verifikasi 1'; }
                    elseif (str_contains($s, 'verif 2')) { $step = 5; $label = 'Verifikasi 2'; }
                    elseif (str_contains($s, 'verif 3')) { $step = 6; $label = 'Verifikasi 3'; }
                    
                    return [
                        'id' => $d->id,
                        'type' => 'QPR',
                        'no_form' => $d->no_qpr ?? ('QPR-' . str_pad($d->id, 5, '0', STR_PAD_LEFT)),
                        'no_job' => $d->no_job ?? '-',
                        'line' => $d->inspeksi?->schedule?->line ?? '-',
                        'info' => $d->nama_part ?? '-',
                        'step' => $step,
                        'statusLabel' => $label,
                        'url' => '/qpr/' . $d->id . '/edit',
                        'date' => $d->updated_at,
                        'created_at' => $d->created_at,
                    ];
                });

            $activeLiDocs = $liDocs->concat($icDocs)->concat($qprDocs)->sortByDesc('date')->values();
        }
    @endphp
    
    {{-- â•â• SUPERVISOR DASHBOARD â•â• --}}
    <div class="mb-4 space-y-4">

        {{-- TOP ROW: BANNER (2/3) + CALENDAR (1/3) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Banner --}}
            <div class="lg:col-span-2 h-full">
                <x-hero-banner />
            </div>
            
            {{-- Calendar --}}
            <div class="lg:col-span-1 h-full">
                <x-calendar-widget />
            </div>
        </div>

        {{-- BOTTOM ROW: SUMMARY (2/3) + GRAPH/ETC (1/3) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4" x-data="dashboardSummary()" x-init="init()">

            {{-- LEFT: SUMMARY (2/3) --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- FLATPICKR FOR BEAUTIFUL DATE PICKER --}}
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
                <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

                {{-- DATE RANGE CONTROLS --}}
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-4 mb-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        {{-- Left Side: Icon & Title --}}
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-700 shadow-md shadow-red-200 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-black text-slate-800 tracking-wide uppercase">Periode</h3>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <button @click="shiftDays(-1)" class="w-4 h-4 flex items-center justify-center rounded bg-slate-100 hover:bg-red-500 text-slate-400 hover:text-white transition-colors" title="Hari Sebelumnya">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <p class="text-[10px] text-slate-400 font-medium leading-tight">
                                        Data aktif: <span class="font-bold text-red-500" x-text="formattedPeriode"></span>
                                    </p>
                                    <button @click="shiftDays(1)" class="w-4 h-4 flex items-center justify-center rounded bg-slate-100 hover:bg-red-500 text-slate-400 hover:text-white transition-colors" title="Hari Berikutnya">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Right Side: Quick Filters --}}
                        <div class="flex flex-row flex-wrap items-center gap-3 md:justify-end flex-1">
                            
                            {{-- Quick Filters --}}
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button @click="activePreset='today'; dateFrom='{{ now()->toDateString() }}'; dateTo='{{ now()->toDateString() }}'; fetch()"
                                        :class="activePreset === 'today' ? 'bg-gradient-to-r from-red-500 to-red-700 text-white shadow-md shadow-red-200 border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-500'"
                                        class="flex items-center justify-center gap-1.5 px-3 py-2 text-[10px] font-bold rounded-xl border transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Hari Ini
                                </button>
                                <button @click="activePreset='7days'; dateFrom='{{ now()->subDays(6)->toDateString() }}'; dateTo='{{ now()->toDateString() }}'; fetch()"
                                        :class="activePreset === '7days' ? 'bg-gradient-to-r from-red-500 to-red-700 text-white shadow-md shadow-red-200 border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-500'"
                                        class="flex items-center justify-center gap-1.5 px-3 py-2 text-[10px] font-bold rounded-xl border transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                    7 Hari
                                </button>
                                <button @click="activePreset='thisMonth'; dateFrom='{{ now()->startOfMonth()->toDateString() }}'; dateTo='{{ now()->toDateString() }}'; fetch()"
                                        :class="activePreset === 'thisMonth' ? 'bg-gradient-to-r from-red-500 to-red-700 text-white shadow-md shadow-red-200 border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-500'"
                                        class="flex items-center justify-center gap-1.5 px-3 py-2 text-[10px] font-bold rounded-xl border transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Bulan Ini
                                </button>
                                
                                {{-- Custom Button with Pop-up --}}
                                <div x-data="{ showCustomModal: false }" class="relative">
                                    <button @click="activePreset='custom'; showCustomModal = !showCustomModal"
                                            @click.away="showCustomModal = false"
                                            :class="activePreset === 'custom' ? 'bg-gradient-to-r from-red-500 to-red-700 text-white shadow-md shadow-red-200 border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:border-red-300 hover:text-red-500'"
                                            class="flex items-center justify-center gap-1.5 px-3 py-2 text-[10px] font-bold rounded-xl border transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                        Custom
                                    </button>

                                    {{-- Custom Date Popup --}}
                                    <div x-show="showCustomModal" x-transition.opacity.duration.200ms
                                         class="absolute right-0 mt-2 p-4 w-[240px] bg-white border border-slate-100 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] rounded-2xl z-50"
                                         style="display: none;">
                                         <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Pilih Rentang Tanggal</p>
                                         <div class="space-y-3">
                                             <div>
                                                 <label class="text-[9px] font-bold text-slate-400 block mb-1">Dari Tanggal</label>
                                                 <input type="text" x-model="dateFrom" 
                                                        x-init="let fp1 = flatpickr($el, { dateFormat: 'Y-m-d', onChange: (d, str) => dateFrom = str }); $watch('dateFrom', v => fp1.setDate(v))"
                                                        class="w-full px-3 py-2 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none cursor-pointer">
                                             </div>
                                             <div>
                                                 <label class="text-[9px] font-bold text-slate-400 block mb-1">Sampai Tanggal</label>
                                                 <input type="text" x-model="dateTo"
                                                        x-init="let fp2 = flatpickr($el, { dateFormat: 'Y-m-d', onChange: (d, str) => dateTo = str }); $watch('dateTo', v => fp2.setDate(v))"
                                                        class="w-full px-3 py-2 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none cursor-pointer">
                                             </div>
                                             <button @click="fetch(); showCustomModal = false" class="w-full bg-red-500 hover:bg-red-600 text-white text-[10px] font-bold py-2.5 rounded-xl transition-colors mt-1">
                                                 Terapkan Filter
                                             </button>
                                         </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


                {{-- LI SUMMARY CARDS --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-4">
                            <!-- <div class="w-12 h-12 rounded-2xl bg-indigo-600 shadow-lg shadow-indigo-200 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div> -->
                            <div>
                                <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Item Check Harian
                                </h2>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">Ringkasan data inspeksi terbaru</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="/item-check" class="text-[11px] px-4 py-2 rounded-full border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition-colors">Lihat Semua</a>
                        </div>
                    </div>

                    {{-- PART STATS GRID --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        <template x-for="stat in partStatsArray" :key="stat.name">
                            <a :href="'/item-check?part_name=' + encodeURIComponent(stat.name)" class="block bg-white rounded-[32px] border border-slate-100/60 p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_12px_40px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 relative group cursor-pointer">
                                
                                {{-- Top Section --}}
                                <div class="flex flex-col">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <h4 class="text-[15px] font-black text-[#1E293B] leading-snug line-clamp-2" x-text="stat.name"></h4>
                                        
                                        <!-- Status Pill -->
                                        <template x-if="stat.ng > 0">
                                            <div class="px-2.5 py-1 bg-rose-50 border border-rose-100 rounded-full flex items-center gap-1.5 shadow-sm animate-pulse shrink-0">
                                                <div class="w-3 h-3 rounded-full bg-rose-500 text-white flex items-center justify-center">
                                                    <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </div>
                                                <span class="text-[9px] font-black text-rose-600 tracking-wider uppercase whitespace-nowrap">NG Ditemukan</span>
                                            </div>
                                        </template>
                                        <template x-if="stat.ng === 0 && stat.finished < stat.total">
                                            <div class="px-2.5 py-1 bg-[#FFFBEB] border border-[#FEF3C7] rounded-full flex items-center gap-1.5 shadow-sm shrink-0">
                                                <div class="w-3 h-3 rounded-full bg-[#F59E0B] text-white flex items-center justify-center">
                                                    <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </div>
                                                <span class="text-[9px] font-black text-[#D97706] tracking-wider uppercase whitespace-nowrap">Proses</span>
                                            </div>
                                        </template>
                                        <template x-if="stat.ng === 0 && stat.finished === stat.total">
                                            <div class="px-2.5 py-1 bg-[#F0FDF4] border border-[#DCFCE7] rounded-full flex items-center gap-1.5 shadow-sm shrink-0">
                                                <div class="w-3 h-3 rounded-full bg-[#22C55E] text-white flex items-center justify-center">
                                                    <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                                <span class="text-[9px] font-black text-[#16A34A] tracking-wider uppercase whitespace-nowrap">Selesai</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 rounded-md text-[10px] font-bold" x-text="stat.jobText"></span>
                                        <span class="text-slate-300">|</span>
                                        <span class="text-[10px] font-bold text-slate-500 flex items-center gap-1.5">
                                            Line: <span class="text-indigo-600 uppercase" x-text="stat.lineText || '-'"></span> 
                                            <span class="w-1 h-1 rounded-full bg-indigo-300 ml-0.5"></span>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-2.5 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-[10px] font-bold text-[#475569]" x-text="stat.prodTotal + ' Pcs Target'"></span>
                                    </div>
                                </div>

                                {{-- Divider --}}
                                <hr class="my-4 border-slate-100/80">

                                {{-- Middle Section: Sampling Progress --}}
                                <div>
                                    <div class="flex items-center justify-between mb-3 gap-2">
                                        <div class="flex items-center gap-2.5 shrink-0">
                                            <span class="text-[10px] font-black text-[#334155] tracking-widest uppercase whitespace-nowrap">Proses Sampling</span>
                                        </div>
                                        <div class="px-3 py-1 bg-[#EEF2FF] rounded-full flex items-center gap-1.5 shrink-0">
                                            <svg class="w-3 h-3 text-[#4F46E5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="text-[11px] font-black text-[#4F46E5] whitespace-nowrap" x-text="(stat.activeProgressChecked || 0) + ' / ' + (stat.activeProgressTotal || 0) + ' smpl'"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="w-full bg-[#E2E8F0] rounded-full h-2.5 mb-5 overflow-hidden relative shadow-inner">
                                        <div class="bg-gradient-to-r from-[#6366F1] to-[#4F46E5] h-2.5 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(99,102,241,0.5)]" 
                                             :style="'width: ' + Math.min(100, Math.round((stat.activeProgressChecked / Math.max(1, stat.activeProgressTotal)) * 100)) + '%'">
                                        </div>
                                    </div>
                                </div>

                                {{-- Bottom Section: OK / NG Big Cards --}}
                                <div class="flex gap-2">
                                    <!-- OK Box -->
                                    <div class="flex-1 bg-gradient-to-br from-[#F0FDF4] to-white border border-[#DCFCE7] rounded-2xl px-3 py-3 relative overflow-hidden flex items-center justify-between shadow-[0_2px_10px_rgba(34,197,94,0.05)] hover:shadow-[0_4px_14px_rgba(34,197,94,0.1)] transition-all">
                                        <!-- Decorative dots pattern -->
                                        <div class="absolute bottom-1 right-1.5 flex flex-col gap-0.5 opacity-20 z-0">
                                            <div class="flex gap-0.5"><div class="w-1 h-1 rounded-full bg-green-500"></div><div class="w-1 h-1 rounded-full bg-green-500"></div><div class="w-1 h-1 rounded-full bg-green-500"></div></div>
                                            <div class="flex gap-0.5"><div class="w-1 h-1 rounded-full bg-green-500"></div><div class="w-1 h-1 rounded-full bg-green-500"></div><div class="w-1 h-1 rounded-full bg-green-500"></div></div>
                                        </div>
                                        <div class="relative z-10 shrink-0">
                                            <span class="text-[13px] font-black text-[#16A34A] tracking-wider relative flex items-center">OK<span class="absolute top-1/2 -right-3 w-2 border-t border-dashed border-[#16A34A]/40"></span></span>
                                        </div>
                                        <span class="text-3xl font-black text-[#15803D] relative z-10 drop-shadow-sm" x-text="stat.ok"></span>
                                    </div>
                                    
                                    <!-- NG Box -->
                                    <div class="flex-1 bg-gradient-to-br from-[#FFF1F2] to-white border border-[#FFE4E6] rounded-2xl px-3 py-3 relative overflow-hidden flex items-center justify-between shadow-[0_2px_10px_rgba(225,29,72,0.05)] hover:shadow-[0_4px_14px_rgba(225,29,72,0.1)] transition-all">
                                        <!-- Decorative dots pattern -->
                                        <div class="absolute bottom-1 right-1.5 flex flex-col gap-0.5 opacity-20 z-0">
                                            <div class="flex gap-0.5"><div class="w-1 h-1 rounded-full bg-red-500"></div><div class="w-1 h-1 rounded-full bg-red-500"></div><div class="w-1 h-1 rounded-full bg-red-500"></div></div>
                                            <div class="flex gap-0.5"><div class="w-1 h-1 rounded-full bg-red-500"></div><div class="w-1 h-1 rounded-full bg-red-500"></div><div class="w-1 h-1 rounded-full bg-red-500"></div></div>
                                        </div>
                                        <div class="relative z-10 shrink-0">
                                            <span class="text-[13px] font-black text-[#BE123C] tracking-wider relative flex items-center">NG<span class="absolute top-1/2 -right-3 w-2 border-t border-dashed border-[#BE123C]/40"></span></span>
                                        </div>
                                        <span class="text-3xl font-black text-[#BE123C] relative z-10 drop-shadow-sm" x-text="stat.ng"></span>
                                    </div>
                                </div>
                            </a>
                        </template>

                        <!-- Empty State -->
                        <div x-show="!loading && partStatsArray.length === 0" style="display: none;" class="col-span-full py-10 flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                            <svg class="w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <p class="text-sm font-bold text-slate-400">Belum ada data inspeksi pada periode ini</p>
                        </div>
                    </div>
                </div>

                {{-- QPR PIPELINE WIDGET --}}
                <div x-data="{
                    pipelineExpanded: false,
                    get pipeline() {
                        if (!this.qprAllItems || !this.qprAllItems.length) return [];
                        const stages = [
                            { key: 'open',      label: 'Terbuka / Draft',       color: 'bg-slate-400',   text: 'text-slate-600',   bg: 'bg-slate-50',   border: 'border-slate-200', count: 0 },
                            { key: 'pending',   label: 'Menunggu TTD GL',        color: 'bg-amber-400',   text: 'text-amber-600',   bg: 'bg-amber-50',   border: 'border-amber-200', count: 0 },
                            { key: 'progress',  label: 'Dalam Perbaikan',        color: 'bg-blue-500',    text: 'text-blue-600',    bg: 'bg-blue-50',    border: 'border-blue-200',  count: 0 },
                            { key: 'verif',     label: 'Menunggu Verifikasi',    color: 'bg-violet-500',  text: 'text-violet-600',  bg: 'bg-violet-50',  border: 'border-violet-200', count: 0 },
                            { key: 'a3',        label: 'Perlu A3 Report',        color: 'bg-rose-500',    text: 'text-rose-600',    bg: 'bg-rose-50',    border: 'border-rose-200',  count: 0 },
                            { key: 'closed',    label: 'Selesai / Close',        color: 'bg-emerald-500', text: 'text-emerald-600', bg: 'bg-emerald-50', border: 'border-emerald-200', count: 0 },
                        ];
                        this.qprAllItems.forEach(i => {
                            const s = (i.status || '').toLowerCase();
                            if (s === 'close' || s === 'closed') stages[5].count++;
                            else if (s.includes('a3')) stages[4].count++;
                            else if (s.includes('verif') || s.includes('waiting verif')) stages[3].count++;
                            else if (s.includes('gl approved') || s.includes('progress') || s.includes('waiting action')) stages[2].count++;
                            else if (s === 'pending approval') stages[1].count++;
                            else stages[0].count++;
                        });
                        return stages;
                    },
                    get activeTotal() { return (this.qprAllItems||[]).filter(i => { const s=(i.status||'').toLowerCase(); return s!=='close'&&s!=='closed'; }).length; },
                    get overdueCount() {
                        const today = new Date(); today.setHours(0,0,0,0);
                        return (this.qprAllItems||[]).filter(i => {
                            const s = (i.status||'').toLowerCase();
                            if (s==='close'||s==='closed') return false;
                            if (!i.target_selesai) return false;
                            const t = new Date(i.target_selesai); t.setHours(0,0,0,0);
                            return t < today;
                        }).length;
                    }
                }">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span> Quality Problem Report (QPR)
                        </h2>
                        <a href="/qpr" class="text-[10px] font-bold text-amber-500 hover:text-amber-700">Lihat Semua →</a>
                    </div>

                    <div class="bg-white rounded-[20px] border border-slate-100 shadow-sm overflow-hidden">
                        {{-- Header row: total active + overdue alert --}}
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
                            <div class="flex items-center gap-4">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Aktif</p>
                                    <p class="text-2xl font-black text-slate-800 leading-none mt-0.5" x-text="loading ? '...' : activeTotal"></p>
                                </div>
                                <div class="w-px h-8 bg-slate-100"></div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Selesai</p>
                                    <p class="text-2xl font-black text-emerald-600 leading-none mt-0.5" x-text="loading ? '...' : qpr.approved"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <template x-if="!loading && overdueCount > 0">
                                    <a href="/qpr" class="flex items-center gap-1.5 bg-rose-50 border border-rose-200 text-rose-600 px-3 py-1.5 rounded-xl hover:bg-rose-100 transition-colors">
                                        <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span class="text-[10px] font-black" x-text="overdueCount + ' QPR Terlambat'"></span>
                                    </a>
                                </template>
                                <template x-if="!loading && overdueCount === 0">
                                    <span class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-100 text-emerald-600 px-3 py-1.5 rounded-xl">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-[10px] font-black">Tidak Ada Terlambat</span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Pipeline stages --}}
                        <div class="px-5 py-4">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Status Pipeline</p>
                            <div class="space-y-2">
                                <template x-for="stage in pipeline" :key="stage.key">
                                    <div class="flex items-center gap-3">
                                        {{-- Stage label --}}
                                        <span class="text-[10px] font-bold text-slate-500 w-36 shrink-0 leading-snug" x-text="stage.label"></span>
                                        {{-- Bar --}}
                                        <div class="flex-1 h-5 rounded-full bg-slate-100 overflow-hidden relative">
                                            <div class="h-full rounded-full transition-all duration-700"
                                                :class="stage.color"
                                                :style="'width: ' + (qpr.total > 0 ? Math.max(2, Math.min(100, Math.round(stage.count / qpr.total * 100))) : 0) + '%'">
                                            </div>
                                        </div>
                                        {{-- Count badge --}}
                                        <span class="w-8 text-right text-[11px] font-black shrink-0" :class="stage.count > 0 ? stage.text : 'text-slate-300'" x-text="stage.count"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Footer: recently updated QPRs --}}
                        <div class="border-t border-slate-50 px-5 py-3 flex items-center justify-between">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Total Semua QPR</p>
                            <span class="text-[10px] font-black text-slate-600" x-text="loading ? '...' : (qpr.total || 0) + ' QPR'"></span>
                        </div>
                    </div>
                </div>

                {{-- RECENT LI TABLE --}}
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50">
                        <h3 class="text-sm font-black text-slate-800">Item Check Terbaru dalam Periode</h3>
                        <span class="text-[10px] text-slate-400 font-bold bg-slate-50 px-2 py-1 rounded-lg" x-text="filteredLi.length + ' dokumen'"></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/60 border-b border-slate-100">
                                    <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">No Form</th>
                                    <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Part</th>
                                    <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Judgement</th>
                                    <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template x-if="loading">
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400 text-sm font-bold animate-pulse">Memuat data...</td></tr>
                                </template>
                                <template x-if="!loading && filteredLi.length === 0">
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400 text-sm font-bold">Tidak ada data pada periode ini</td></tr>
                                </template>
                                <template x-for="item in filteredLi.slice(0,5)" :key="item.id">
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-2 text-[11px] font-bold text-slate-700" x-text="item.no_form || '-'"></td>
                                        <td class="px-5 py-3">
                                            <p class="text-xs font-bold text-slate-800 truncate max-w-[160px]" x-text="item.part_name || '-'"></p>
                                            <p class="text-[10px] text-slate-400" x-text="item.job_no || ''"></p>
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black"
                                                  :class="item.judgement === 'OK' ? 'bg-emerald-50 text-emerald-600' : (item.judgement === 'NG' ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-500')"
                                                  x-text="item.judgement || '-'"></span>
                                        </td>
                                        <td class="px-5 py-3 text-[10px] text-slate-500 font-bold" x-text="item.tanggal || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



                {{-- NEW TIMELINE TRACKER WIDGET --}}
                @if(in_array(auth()->user()->role, ['Supervisor', 'Manager', 'Admin', 'Foreman', 'Leader', 'Group Leader']))
                <div class="  bg-white rounded-[24px] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 flex flex-col relative overflow-hidden">
                    
                    {{-- Header Top --}}
                    <div class="flex items-center justify-between mb-3 border-b border-slate-50 pb-3">
                        <div class="flex items-center gap-3">
                            <div>
                                <h3 class="text-[16px] font-black text-[#0F172A] tracking-tight">Live Tracking</h3>
                                <p class="text-[12px] text-slate-500 mt-0.5">Dokumen aktif terakhir</p>
                            </div>
                        </div>
                        <div class="px-3 py-1.5 bg-[#ECFDF5] rounded-full flex items-center gap-2 border border-[#D1FAE5]">
                            <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
                            <span class="text-[11px] font-bold text-[#059669] tracking-wide">Aktif</span>
                        </div>
                    </div>

                    <template x-if="!trackingLoading && monitoringList.length > 0">
                        <div x-data="{ 
                            idx: 0, 
                            filter: 'Semua',
                            get filteredList() {
                                if (this.filter === 'Semua') return monitoringList;
                                return monitoringList.filter(d => d.type === this.filter);
                            },
                            get tracked() { return this.filteredList[this.idx] || {}; },
                            next() { if (this.filteredList.length > 0) this.idx = (this.idx + 1) % this.filteredList.length; },
                            prev() { if (this.filteredList.length > 0) this.idx = (this.idx - 1 + this.filteredList.length) % this.filteredList.length; },
                            setFilter(type) { this.filter = type; this.idx = 0; },
                            fmtDate(dStr) {
                                if (!dStr) return '';
                                const d = new Date(dStr);
                                if(isNaN(d)) return dStr;
                                return d.toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}).replace('.', ':');
                            }
                        }" class="relative">
                            
                            <!-- Filter Buttons Auto-Scroller -->
                            <div x-data="{
                                dir: 1,
                                scroller: null,
                                startAutoScroll() {
                                    this.scroller = setInterval(() => {
                                        const el = this.$refs.scrollArea;
                                        if (!el) return;
                                        
                                        // Only scroll if there is overflow
                                        if (el.scrollWidth <= el.clientWidth) return;
                                        
                                        el.scrollLeft += this.dir * 0.5;
                                        
                                        // Reverse direction if hitting edges
                                        if (Math.ceil(el.scrollLeft) >= el.scrollWidth - el.clientWidth) this.dir = -1;
                                        if (el.scrollLeft <= 0) this.dir = 1;
                                    }, 20);
                                },
                                stopAutoScroll() { clearInterval(this.scroller); }
                            }" x-init="startAutoScroll()" class="w-full relative">
                            
                                <div x-ref="scrollArea" 
                                     @mouseenter="stopAutoScroll()" 
                                     @mouseleave="startAutoScroll()"
                                     @touchstart="stopAutoScroll()"
                                     @touchend="startAutoScroll()"
                                     class="flex items-center justify-start gap-2 mb-4 overflow-x-auto pb-1 w-full scrollbar-hide" style="-ms-overflow-style: none; scrollbar-width: none;">
                                <button @click="setFilter('Semua')" 
                                        :class="filter === 'Semua' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-100'"
                                        class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0">
                                    Semua
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black" :class="filter === 'Semua' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="monitoringList.length"></span>
                                </button>
                                
                                <button @click="setFilter('Item Check')" 
                                        :class="filter === 'Item Check' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-100'"
                                        class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0">
                                    Item Check
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black" 
                                          :class="filter === 'Item Check' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" 
                                          x-text="monitoringList.filter(d => d.type === 'Item Check').length"></span>
                                </button>
                                
                                <button @click="setFilter('QPR')" 
                                        :class="filter === 'QPR' ? 'bg-rose-600 text-white shadow-md shadow-rose-100' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-100'"
                                        class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0">
                                    QPR
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black"
                                          :class="filter === 'QPR' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" 
                                          x-text="monitoringList.filter(d => d.type === 'QPR').length"></span>
                                </button>
                                
                                <button @click="setFilter('Lembar Inspeksi')" 
                                        :class="filter === 'Lembar Inspeksi' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-100' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-100'"
                                        class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0">
                                    Master LI
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black"
                                          :class="filter === 'Lembar Inspeksi' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" 
                                          x-text="monitoringList.filter(d => d.type === 'Lembar Inspeksi').length"></span>
                                </button>
                            </div>
                            </div>
                            
                            <!-- Empty State if filteredList is 0 -->
                            <div x-cloak x-show="filteredList.length === 0" class="mb-6 bg-[#F8FAFC] py-8 rounded-[20px] border border-slate-100 flex flex-col items-center justify-center">
                                <p class="text-[12px] font-bold text-slate-400">Tidak ada dokumen aktif di kategori ini</p>
                            </div>

                            <!-- Document Info Header -->
                            <div x-cloak x-show="filteredList.length > 0" class="mb-6 bg-[#F8FAFC] p-5 rounded-[20px] border border-slate-100 relative flex flex-col items-center justify-center min-h-[110px]">
                                <!-- Prev -->
                                <button type="button" @click="prev()" x-show="filteredList.length > 1" class="absolute -left-3 top-1/2 -translate-y-1/2 w-12 h-12 bg-white shadow-md border border-slate-100 flex items-center justify-center text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition-all z-50 active:scale-95">
                                    <svg class="w-5 h-5 pr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                
                                <!-- Type & Status Badges -->
                                <div class="flex flex-wrap items-center justify-center gap-2 mb-4">
                                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-lg"
                                         :class="tracked.type === 'QPR' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="text-[11px] font-bold tracking-wide" x-text="tracked.type"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 px-3 py-1 bg-[#EEF2FF] text-[#4338CA] rounded-lg">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-[11px] font-bold tracking-wide" x-text="tracked.statusLabel"></span>
                                    </div>
                                </div>


                                
                                <!-- Doc Title -->
                                <div class="flex items-center justify-center w-full px-8 mb-1.5">
                                    <h4 class="text-lg font-black text-slate-800 tracking-tight text-center leading-snug" x-text="tracked.info"></h4>
                                </div>
                                
                                <!-- Doc Subtitle -->
                                <div class="flex flex-col items-center justify-center gap-1 mb-2 text-slate-400">
                                    <p class="text-[11px] font-medium tracking-wide">
                                        <span x-show="tracked.no_job && tracked.no_job !== '-'" x-text="tracked.no_job"></span>
                                        <span x-show="!tracked.no_job || tracked.no_job === '-'" x-text="tracked.no_form"></span>
                                    </p>
                                    <p x-show="tracked.line && tracked.line !== '-'" class="text-[11px] font-bold tracking-wider text-indigo-400 uppercase" x-text="'LINE: ' + tracked.line"></p>
                                </div>
                                
                                <!-- Next -->
                                <button type="button" @click="next()" x-show="filteredList.length > 1" class="absolute -right-3 top-1/2 -translate-y-1/2 w-12 h-12 bg-white shadow-md border border-slate-100 flex items-center justify-center text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition-all z-50 active:scale-95">
                                    <svg class="w-5 h-5 pl-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div> <!-- Closes Document Info Header -->
                              <!-- Lembar Inspeksi Steps -->
                            <template x-if="tracked.type === 'Lembar Inspeksi'">
                                <div class="relative pl-6 pr-2 mb-8">
                                    <div class="absolute left-[35px] top-[15px] bottom-[25px] border-l-[2px] border-dashed border-slate-200 z-0"></div>
                                    <div class="space-y-7 relative z-10">
                                        <!-- Step 1 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 1 ? 'bg-[#2563EB] text-white' : (tracked.step === 1 ? 'bg-white border-[2px] border-[#2563EB]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 1"><div class="w-2.5 h-2.5 bg-[#2563EB] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 1 ? 'text-slate-800' : (tracked.step === 1 ? 'text-[#2563EB]' : 'text-slate-400')">Pembuatan Form (Draft)</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 1 ? 'text-slate-500' : (tracked.step === 1 ? 'text-blue-500' : 'text-slate-400')">Admin / QC menyiapkan template form</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 1">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        <span class="text-[11px] font-medium tracking-wide">Selesai</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step === 1">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#EEF2FF] text-[#4338CA] rounded-md">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span class="text-[11px] font-medium tracking-wide">Proses</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step < 1">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-500 rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Menunggu</span>
                                                    </div>
                                                </template>
                                                <p class="text-[11px] text-slate-400 mt-2 font-medium" x-show="tracked.step >= 1" x-text="(tracked.step === 1 ? 'Sejak ' : '') + fmtDate(tracked.created_at)"></p>
                                            </div>
                                        </div>
                                        <!-- Step 2 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 2 ? 'bg-[#2563EB] text-white' : (tracked.step === 2 ? 'bg-white border-[2px] border-[#2563EB]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 2"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 2"><div class="w-2.5 h-2.5 bg-[#2563EB] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 2 ? 'text-slate-800' : (tracked.step === 2 ? 'text-[#2563EB]' : 'text-slate-400')">Review Foreman</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 2 ? 'text-slate-500' : (tracked.step === 2 ? 'text-blue-500' : 'text-slate-400')">Foreman QC</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 2">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Selesai</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step === 2">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#EEF2FF] text-[#4338CA] rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Proses</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step < 2">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Menunggu</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <!-- Step 3 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 3 ? 'bg-[#2563EB] text-white' : (tracked.step === 3 ? 'bg-white border-[2px] border-[#2563EB]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 3"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 3"><div class="w-2.5 h-2.5 bg-[#2563EB] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 3 ? 'text-slate-800' : (tracked.step === 3 ? 'text-[#2563EB]' : 'text-slate-400')">Verifikasi Supervisor</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 3 ? 'text-slate-500' : (tracked.step === 3 ? 'text-blue-500' : 'text-slate-400')">Supervisor QC</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 3">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Selesai</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step === 3">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#EEF2FF] text-[#4338CA] rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Proses</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step < 3">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Menunggu</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Item Check Steps -->
                            <template x-if="tracked.type === 'Item Check'">
                                <div class="relative pl-6 pr-2 mb-8">
                                    <div class="absolute left-[35px] top-[15px] bottom-[25px] border-l-[2px] border-dashed border-slate-200 z-0"></div>
                                    <div class="space-y-7 relative z-10">
                                        <!-- Step 1 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 1 ? 'bg-[#059669] text-white' : (tracked.step === 1 ? 'bg-white border-[2px] border-[#059669]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 1"><div class="w-2.5 h-2.5 bg-[#059669] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 1 ? 'text-slate-800' : (tracked.step === 1 ? 'text-[#059669]' : 'text-slate-400')">Inspeksi Aktual (OK/NG)</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 1 ? 'text-slate-500' : (tracked.step === 1 ? 'text-emerald-500' : 'text-slate-400')">Quality Control / Operator</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 1">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Selesai</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step === 1">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md border border-emerald-200">
                                                        <span class="text-[11px] font-medium tracking-wide">Proses</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step < 1">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-500 rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Menunggu</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <!-- Step 2 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 2 ? 'bg-[#059669] text-white' : (tracked.step === 2 ? 'bg-white border-[2px] border-[#059669]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 2"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 2"><div class="w-2.5 h-2.5 bg-[#059669] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 2 ? 'text-slate-800' : (tracked.step === 2 ? 'text-[#059669]' : 'text-slate-400')">Review Pengecekan</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 2 ? 'text-slate-500' : (tracked.step === 2 ? 'text-emerald-500' : 'text-slate-400')">Group Leader</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 2">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Selesai</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step === 2">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md border border-emerald-200">
                                                        <span class="text-[11px] font-medium tracking-wide">Proses</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step < 2">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Menunggu</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <!-- Step 3 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 3 ? 'bg-[#059669] text-white' : (tracked.step === 3 ? 'bg-white border-[2px] border-[#059669]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 3"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 3"><div class="w-2.5 h-2.5 bg-[#059669] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 3 ? 'text-slate-800' : (tracked.step === 3 ? 'text-[#059669]' : 'text-slate-400')">Pengesahan Inspeksi</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 3 ? 'text-slate-500' : (tracked.step === 3 ? 'text-emerald-500' : 'text-slate-400')">Foreman Produksi</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 3">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Selesai</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step === 3">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md border border-emerald-200">
                                                        <span class="text-[11px] font-medium tracking-wide">Proses</span>
                                                    </div>
                                                </template>
                                                <template x-if="tracked.step < 3">
                                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md">
                                                        <span class="text-[11px] font-medium tracking-wide">Menunggu</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- QPR Steps -->
                            <template x-if="tracked.type === 'QPR'">
                                <div class="relative pl-6 pr-2 mb-8">
                                    <div class="absolute left-[35px] top-[15px] bottom-[25px] border-l-[2px] border-dashed border-slate-200 z-0"></div>
                                    <div class="space-y-6 relative z-10">
                                        <!-- Step 1 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 1 ? 'bg-[#E11D48] text-white' : (tracked.step === 1 ? 'bg-white border-[2px] border-[#E11D48]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 1"><div class="w-2.5 h-2.5 bg-[#E11D48] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 1 ? 'text-slate-800' : (tracked.step === 1 ? 'text-[#E11D48]' : 'text-slate-400')">Investigasi Temuan</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 1 ? 'text-slate-500' : (tracked.step === 1 ? 'text-rose-500' : 'text-slate-400')">Penemu NG / Operator</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 1"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md"><span class="text-[11px] font-medium tracking-wide">Selesai</span></div></template>
                                                <template x-if="tracked.step === 1"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 text-rose-600 rounded-md border border-rose-200"><span class="text-[11px] font-medium tracking-wide">Proses</span></div></template>
                                                <template x-if="tracked.step < 1"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-500 rounded-md"><span class="text-[11px] font-medium tracking-wide">Menunggu</span></div></template>
                                            </div>
                                        </div>
                                        <!-- Step 2 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 2 ? 'bg-[#E11D48] text-white' : (tracked.step === 2 ? 'bg-white border-[2px] border-[#E11D48]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 2"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 2"><div class="w-2.5 h-2.5 bg-[#E11D48] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 2 ? 'text-slate-800' : (tracked.step === 2 ? 'text-[#E11D48]' : 'text-slate-400')">Pengecekan Awal</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 2 ? 'text-slate-500' : (tracked.step === 2 ? 'text-rose-500' : 'text-slate-400')">GL / Foreman</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 2"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md"><span class="text-[11px] font-medium tracking-wide">Selesai</span></div></template>
                                                <template x-if="tracked.step === 2"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 text-rose-600 rounded-md border border-rose-200"><span class="text-[11px] font-medium tracking-wide">Proses</span></div></template>
                                                <template x-if="tracked.step < 2"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md"><span class="text-[11px] font-medium tracking-wide">Menunggu</span></div></template>
                                            </div>
                                        </div>
                                        <!-- Step 3 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 3 ? 'bg-[#E11D48] text-white' : (tracked.step === 3 ? 'bg-white border-[2px] border-[#E11D48]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 3"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 3"><div class="w-2.5 h-2.5 bg-[#E11D48] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 3 ? 'text-slate-800' : (tracked.step === 3 ? 'text-[#E11D48]' : 'text-slate-400')">Persetujuan Seksi Terkait</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 3 ? 'text-slate-500' : (tracked.step === 3 ? 'text-rose-500' : 'text-slate-400')">Supervisor Produksi / Lainnya</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 3"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md"><span class="text-[11px] font-medium tracking-wide">Selesai</span></div></template>
                                                <template x-if="tracked.step === 3"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 text-rose-600 rounded-md border border-rose-200"><span class="text-[11px] font-medium tracking-wide">Proses</span></div></template>
                                                <template x-if="tracked.step < 3"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md"><span class="text-[11px] font-medium tracking-wide">Menunggu</span></div></template>
                                            </div>
                                        </div>
                                        <!-- Step 4 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 4 ? 'bg-[#E11D48] text-white' : (tracked.step === 4 ? 'bg-white border-[2px] border-[#E11D48]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 4"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 4"><div class="w-2.5 h-2.5 bg-[#E11D48] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 4 ? 'text-slate-800' : (tracked.step === 4 ? 'text-[#E11D48]' : 'text-slate-400')">Verifikasi 1</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 4 ? 'text-slate-500' : (tracked.step === 4 ? 'text-rose-500' : 'text-slate-400')">Quality Control</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 4"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md"><span class="text-[11px] font-medium tracking-wide">Selesai</span></div></template>
                                                <template x-if="tracked.step === 4"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 text-rose-600 rounded-md border border-rose-200"><span class="text-[11px] font-medium tracking-wide">Proses</span></div></template>
                                                <template x-if="tracked.step < 4"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md"><span class="text-[11px] font-medium tracking-wide">Menunggu</span></div></template>
                                            </div>
                                        </div>
                                        <!-- Step 5 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 5 ? 'bg-[#E11D48] text-white' : (tracked.step === 5 ? 'bg-white border-[2px] border-[#E11D48]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 5"><div class="w-2.5 h-2.5 bg-[#E11D48] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 5 ? 'text-slate-800' : (tracked.step === 5 ? 'text-[#E11D48]' : 'text-slate-400')">Verifikasi 2</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 5 ? 'text-slate-500' : (tracked.step === 5 ? 'text-rose-500' : 'text-slate-400')">Quality Control</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 5"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md"><span class="text-[11px] font-medium tracking-wide">Selesai</span></div></template>
                                                <template x-if="tracked.step === 5"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 text-rose-600 rounded-md border border-rose-200"><span class="text-[11px] font-medium tracking-wide">Proses</span></div></template>
                                                <template x-if="tracked.step < 5"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md"><span class="text-[11px] font-medium tracking-wide">Menunggu</span></div></template>
                                            </div>
                                        </div>
                                        <!-- Step 6 -->
                                        <div class="flex items-start justify-between group">
                                            <div class="flex gap-4 items-start">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 ring-[6px] ring-white transition-all mt-0.5"
                                                     :class="tracked.step > 6 ? 'bg-[#E11D48] text-white' : (tracked.step === 6 ? 'bg-white border-[2px] border-[#E11D48]' : 'bg-[#E2E8F0]')">
                                                     <template x-if="tracked.step > 6"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                                                     <template x-if="tracked.step === 6"><div class="w-2.5 h-2.5 bg-[#E11D48] rounded-full"></div></template>
                                                </div>
                                                <div>
                                                    <p class="text-[14px] font-bold" :class="tracked.step > 6 ? 'text-slate-800' : (tracked.step === 6 ? 'text-[#E11D48]' : 'text-slate-400')">Verifikasi 3</p>
                                                    <p class="text-[12px] mt-0.5" :class="tracked.step > 6 ? 'text-slate-500' : (tracked.step === 6 ? 'text-rose-500' : 'text-slate-400')">Quality Control</p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end">
                                                <template x-if="tracked.step > 6"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#ECFDF5] text-[#059669] rounded-md"><span class="text-[11px] font-medium tracking-wide">Selesai</span></div></template>
                                                <template x-if="tracked.step === 6"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 text-rose-600 rounded-md border border-rose-200"><span class="text-[11px] font-medium tracking-wide">Proses</span></div></template>
                                                <template x-if="tracked.step < 6"><div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] text-slate-500 rounded-md"><span class="text-[11px] font-medium tracking-wide">Menunggu</span></div></template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <a :href="tracked.url" class="mt-6 flex items-center justify-between w-full bg-[#2563EB] hover:bg-blue-700 text-white text-[14px] font-bold py-3.5 px-6 rounded-[16px] transition-all shadow-md active:scale-[0.98]">
                                <span></span>
                                <span class="flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Buka Dokumen</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                            <p class="text-center text-[#94A3B8] text-[11px] mt-3 flex items-center justify-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Akses sesuai dengan izin dan peran Anda
                            </p>
                    </template>
                    
                    <template x-if="!trackingLoading && monitoringList.length === 0">
                        <div class="py-10 text-center flex flex-col items-center">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-xs font-bold text-slate-800">Semua Clear!</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">Tidak ada antrean dokumen</p>
                        </div>
                    </template>
                    <template x-if="trackingLoading">
                        <div class="py-10 text-center">
                            <p class="text-xs font-bold text-slate-400 animate-pulse">Memuat tracking...</p>
                        </div>
                    </template>
                </div>
                @endif


            </div>

        </div>
    </div>
    
    @else
    {{-- â•â• REGULAR DASHBOARD (NON SUPERVISOR) â•â• --}}
    
    @php
        $operatorSchedules = collect();
        if(auth()->user()->role === 'Operator') {
            $filterDate = \Carbon\Carbon::today();
            $user = auth()->user();
            
            $schedulesQuery = \App\Models\ProductionSchedule::with(['itemChecks'])
                            ->whereDate('tanggal_produksi', $filterDate)
                            ->orderByRaw('ISNULL(row_no), row_no ASC');
                            
            if (!empty($user->assigned_line) && $user->assigned_line !== 'Semua Line') {
                $schedulesQuery->where('line', 'like', '%' . $user->assigned_line . '%');
            }
            $operatorSchedules = $schedulesQuery->get();
            
            // Cek template menggunakan logika fallback seperti di ItemCheckController
            foreach ($operatorSchedules as $sched) {
                $template = \App\Models\LembarInspeksi::where('job_no', $sched->job_no)->first();
                
                if (!$template && !empty(trim($sched->job_no))) {
                    $template = \App\Models\LembarInspeksi::where('job_no', 'like', '%' . trim($sched->job_no) . '%')->first();
                }
                
                // Cek part_no dengan variasi dash (K4047 -> K-4047) dan split tandem (K4047/48)
                if (!$template) {
                    $partNo = trim($sched->part_no);
                    $partName = trim($sched->part_name);
                    
                    // Kumpulkan kandidat kata kunci dari part_no dan part_name
                    $candidates = array_filter(array_unique([
                        $partNo,
                        $partName,
                        preg_split('/[\/&]/', $partNo)[0] ?? '', // Ambil K4047 dari K4047/48
                        preg_split('/[\/&]/', $partName)[0] ?? '',
                    ]));

                    foreach ($candidates as $cand) {
                        if (empty($cand)) continue;
                        
                        $candWithDash = preg_replace('/^([a-zA-Z]+)(\d+)/', '$1-$2', $cand);
                        $candWithoutDash = str_replace('-', '', $cand);
                        
                        $template = \App\Models\LembarInspeksi::where(function($q) use ($cand, $candWithDash, $candWithoutDash) {
                            $q->where('part_no', $cand)
                              ->orWhere('part_no', $candWithDash)
                              ->orWhere('part_no', $candWithoutDash)
                              ->orWhere('part_name', $cand)
                              ->orWhere('part_name', 'like', $cand . '%')
                              ->orWhere('part_name', 'like', $candWithDash . '%')
                              ->orWhere('job_no', $cand)
                              ->orWhere('job_no', $candWithDash)
                              ->orWhere('job_no', $candWithoutDash)
                              ->orWhere('job_no', 'like', '%' . $cand . '%')
                              ->orWhere('job_no', 'like', '%' . $candWithDash . '%');
                        })->first();
                        
                        if ($template) break;
                    }
                }

                if (!$template && stripos($sched->job_no, ' WIP') !== false) {
                    $cleanJobNo = trim(str_ireplace(' WIP', '', $sched->job_no));
                    if (!empty($cleanJobNo)) {
                        $template = \App\Models\LembarInspeksi::where('job_no', $cleanJobNo)
                                                              ->orWhere('part_no', $cleanJobNo)
                                                              ->orWhere('part_name', $cleanJobNo)
                                                              ->orWhere('job_no', 'like', '%' . $cleanJobNo . '%')
                                                              ->first();
                    }
                }

                $sched->master_template_id = $template ? $template->id : null;

            }
        }
    @endphp

    <div class="mb-8">
        <x-hero-banner />
    </div>

    <div x-data="dashboardData()" x-init="initDashboard()" class="mb-10">
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @if(auth()->user()->role === 'Operator')
                @php
                    $targetHariIni = $operatorSchedules->count();
                    $sudahDikerjakan = $operatorSchedules->filter(function($s) {
                        return $s->itemChecks->whereNotIn('status', ['not_started'])->count() > 0;
                    })->count();
                    $sisaAntrean = max(0, $targetHariIni - $sudahDikerjakan);
                @endphp
                <!-- Target Part -->
                <div class="bg-white rounded-[20px] p-5 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-blue-50 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Target Part</p>
                        <p class="text-2xl font-black text-slate-800">{{ $targetHariIni }}</p>
                    </div>
                </div>

                <!-- Sudah Diinspeksi -->
                <div class="bg-white rounded-[20px] p-5 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-emerald-50 text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Telah Dicek</p>
                        <p class="text-2xl font-black text-slate-800">{{ $sudahDikerjakan }}</p>
                    </div>
                </div>

                <!-- Sisa Antrean -->
                <div class="bg-white rounded-[20px] p-5 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-amber-50 text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sisa Antrean</p>
                        <p class="text-2xl font-black text-slate-800">{{ $sisaAntrean }}</p>
                    </div>
                </div>

                <!-- Tugas QPR -->
                <div class="bg-white rounded-[20px] p-5 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-rose-50 text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tugas QPR</p>
                        <p class="text-2xl font-black text-slate-800" x-text="quickStats.find(s => s.title === 'Tugas QPR')?.value || 0"></p>
                    </div>
                </div>
            @else
                <template x-for="stat in quickStats" :key="stat.title">
                    <div class="bg-white rounded-[20px] p-5 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] border border-slate-100 flex items-center gap-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center" :class="stat.bgClass + ' ' + stat.textClass">
                            <span x-html="stat.icon"></span>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider" x-text="stat.title"></p>
                            <p class="text-2xl font-black text-slate-800" x-text="stat.value"></p>
                        </div>
                    </div>
                </template>
            @endif
        </div>

        <!-- WORKLIST / TUGAS WAJIB HARI INI (OPERATOR ONLY) -->
        @if(auth()->user()->role === 'Operator')
        <div class="mb-10">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-[18px] font-black text-slate-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500 animate-pulse ring-4 ring-blue-100"></span>
                        Worklist: Tugas Wajib Hari Ini
                    </h2>
                    <p class="text-sm font-semibold text-slate-500 mt-1">Jadwal inspeksi otomatis dari SAP (Line: <span class="text-blue-600 font-bold uppercase">{{ auth()->user()->assigned_line ?? 'Semua Line' }}</span>)</p>
                </div>
                <div class="px-4 py-1.5 bg-blue-50 rounded-xl border border-blue-100 shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span class="text-xs font-black text-blue-700 uppercase tracking-widest">{{ $operatorSchedules->count() }} Target Part</span>
                </div>
            </div>

            @if($operatorSchedules->isEmpty())
                <div class="bg-gradient-to-br from-white to-slate-50 rounded-[24px] border border-slate-100 p-12 text-center shadow-sm relative overflow-hidden">
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/diagonal-stripes.png')] opacity-[0.03]"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 text-emerald-400">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 tracking-tight">Tidak Ada Jadwal Produksi!</h3>
                        <p class="text-sm font-semibold text-slate-500 mt-2 max-w-md mx-auto">Sistem tidak mendeteksi tarikan jadwal inspeksi untuk hari ini di Line Anda. Anda bisa bersantai sejenak.</p>
                    </div>
                </div>
            @else
                @php
                    $pendingTasks = [];
                    $finishedTasks = [];

                    foreach($operatorSchedules as $index => $schedule) {
                        $existingCheck = $schedule->itemChecks->first();
                        $status = $existingCheck ? $existingCheck->status : 'not_started';
                        $schedule->ui_status = $status;
                        $schedule->ui_index = $index + 1;
                        $schedule->ui_existing_check = $existingCheck;
                        $schedule->actual_qty_display = $schedule->actual_qty > 0 ? $schedule->actual_qty : $schedule->target_qty;
                        
                        if (in_array($status, ['finished', 'approved', 'waiting_gl', 'waiting_foreman', 'waiting_qc_approval', 'ready_for_qc'])) {
                            $finishedTasks[] = $schedule;
                        } else {
                            $pendingTasks[] = $schedule;
                        }
                    }
                @endphp

                {{-- â”€â”€ ANTREAN INSPEKSI HARI INI â”€â”€ --}}
                @if(count($pendingTasks) > 0)
                    <div class="mb-8">
                        <h3 class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            Antrean Inspeksi (Bebas Pilih)
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            @foreach($pendingTasks as $schedule)
                                <div class="bg-white rounded-[24px] p-5 border shadow-sm relative overflow-hidden flex flex-col group transition-all {{ $schedule->ui_status === 'in_progress' ? 'border-amber-200 shadow-[0_4px_15px_-3px_rgba(245,158,11,0.2)] bg-gradient-to-b from-amber-50/30 to-white' : 'border-slate-200 hover:border-blue-300 hover:shadow-md' }}">
                                    
                                    <div class="flex items-start gap-4 mb-4">
                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-base shrink-0 {{ $schedule->ui_status === 'in_progress' ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-500 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors' }}">
                                            {{ $schedule->ui_index }}
                                        </div>
                                        <div class="flex-grow">
                                            <h4 class="text-sm font-black text-slate-800 leading-tight line-clamp-2" title="{{ $schedule->part_name }}">{{ $schedule->part_name }}</h4>
                                            <p class="text-[11px] font-bold text-slate-500 mt-1">{{ $schedule->job_no }} | Target: {{ $schedule->actual_qty_display }} PCS</p>
                                        </div>
                                    </div>

                                    {{-- Status Progress (OK/NG) Jika in_progress --}}
                                    @if($schedule->ui_status === 'in_progress' && $schedule->ui_existing_check)
                                        @php
                                            $samples = [];
                                            $processData = function($data, $isVisual) use (&$samples) {
                                                if (!is_array($data)) return;
                                                foreach ($data as $key => $val) {
                                                    if (preg_match('/_(\d+)$/', $key, $matches)) {
                                                        $col = $matches[1];
                                                        $strVal = strtolower(trim((string)$val));
                                                        if ($strVal === '') continue;
                                                        if (!isset($samples[$col])) $samples[$col] = ['is_ng' => false, 'has_judgement' => false];
                                                        if ($isVisual) {
                                                            if (in_array($strVal, ['ok', 'ng'])) $samples[$col]['has_judgement'] = true;
                                                            if ($strVal === 'ng') $samples[$col]['is_ng'] = true;
                                                        }
                                                    }
                                                }
                                            };
                                            
                                            $processData($schedule->ui_existing_check->hasil_visual, true);
                                            $processData($schedule->ui_existing_check->hasil_dimensi, false);
                                            
                                            $validSamples = collect($samples)->where('has_judgement', true);
                                            $checkedTotal = $validSamples->count();
                                            $ngTotal = $validSamples->where('is_ng', true)->count();
                                            $okTotal = $checkedTotal - $ngTotal;

                                            $activeTotalProduksi = 0;
                                            if ($schedule->ui_existing_check && $schedule->ui_existing_check->total_produksi > 0) {
                                                $activeTotalProduksi = $schedule->ui_existing_check->total_produksi;
                                            }
                                            
                                            $denom = $activeTotalProduksi > 0 ? $activeTotalProduksi : ($schedule->actual_qty > 0 ? $schedule->actual_qty : ($schedule->target_qty > 0 ? $schedule->target_qty : 0));
                                            
                                            $totalSamples = 0;
                                            $template = \App\Models\LembarInspeksi::find($schedule->master_template_id);
                                            if ($template) {
                                                if (!empty($template->sampling_cols) && is_array($template->sampling_cols)) {
                                                    $cols = $template->sampling_cols;
                                                    if ($denom > 0) {
                                                        $baseCols = array_filter($cols, function($c) use ($denom) {
                                                            return $c <= $denom;
                                                        });
                                                        if (empty($baseCols) || end($baseCols) != $denom) {
                                                            $baseCols[] = (int) $denom;
                                                        }
                                                        $totalSamples = count(array_unique($baseCols));
                                                    } else {
                                                        $totalSamples = count($cols);
                                                    }
                                                } else {
                                                    $totalSamples = $denom > 0 ? min($denom, (int)$template->max_sample) : (int)$template->max_sample;
                                                }
                                            }
                                            $sisaSamples = max(0, $totalSamples - $checkedTotal);
                                        @endphp
                                        <div class="bg-amber-50/50 rounded-xl p-3 border border-amber-100 mb-4 mt-2">
                                            <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-amber-700 mb-2">
                                                <span>Status Inspeksi</span>
                                                <span class="animate-pulse">Berjalan...</span>
                                            </div>
                                            <div class="flex gap-2">
                                                <div class="flex-1 bg-white rounded-lg border border-emerald-100 px-2 py-1.5 flex justify-between items-center">
                                                    <span class="text-[9px] font-black text-emerald-600">OK</span>
                                                    <span class="text-xs font-black text-emerald-700">{{ $okTotal }}</span>
                                                </div>
                                                <div class="flex-1 bg-white rounded-lg border border-rose-100 px-2 py-1.5 flex justify-between items-center">
                                                    <span class="text-[9px] font-black text-rose-600">NG</span>
                                                    <span class="text-xs font-black text-rose-700">{{ $ngTotal }}</span>
                                                </div>
                                            </div>
                                            <div class="text-[10px] font-bold text-center text-amber-700/70 mt-2">
                                                {{ $sisaSamples > 0 ? "Sisa $sisaSamples dari $totalSamples sampel" : "Semua sampel telah dicek" }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                                        @if($schedule->ui_existing_check)
                                            <a href="{{ route('item-check.form', $schedule->ui_existing_check->id) }}" class="flex-1 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-black text-[11px] uppercase tracking-wider rounded-xl transition-all shadow-md shadow-amber-200 text-center flex items-center justify-center gap-1.5">
                                                Lanjutkan Cek
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </a>
                                        @else
                                            @if($schedule->master_template_id)
                                                <form action="{{ route('item-check.start', $schedule->id) }}" method="POST" class="flex-1">
                                                    @csrf
                                                    <button type="submit" class="w-full px-4 py-2.5 bg-slate-800 hover:bg-blue-600 text-white font-black text-[11px] uppercase tracking-wider rounded-xl transition-all text-center flex items-center justify-center gap-1.5 shadow-sm">
                                                        Ambil & Cek
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                    </button>
                                                </form>
                                            @else
                                                <button disabled class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 text-slate-400 font-black text-[11px] uppercase tracking-wider rounded-xl cursor-not-allowed flex items-center justify-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                    No Template
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                {{-- â”€â”€ TUGAS SELESAI HARI INI â”€â”€ --}}
                @if(count($finishedTasks) > 0)
                    <div class="mb-8">
                        <h3 class="text-[11px] font-black text-emerald-600 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Selesai Diinspeksi ({{ count($finishedTasks) }})
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            @foreach($finishedTasks as $schedule)
                                <div class="bg-emerald-50/50 rounded-[20px] p-4 border border-emerald-100 flex items-center justify-between group opacity-75 hover:opacity-100 transition-opacity">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h4 class="text-xs font-bold text-slate-700 truncate" title="{{ $schedule->part_name }}">{{ $schedule->part_name }}</h4>
                                            <p class="text-[9px] text-slate-500 uppercase">{{ $schedule->job_no }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('item-check.form', $schedule->ui_existing_check->id) }}" class="shrink-0 ml-2 p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white rounded-lg transition-colors" title="Lihat Progress">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
        @endif

        <!-- Latest Activities / Tasks (Pindah ke bawah) -->
        @if(auth()->user()->role === 'Operator')
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-[15px] font-bold text-slate-800">Tugas QPR <span class="text-rose-500">(Wajib Diisi)</span></h2>
                        <p class="text-xs text-slate-400 mt-0.5">Dokumen Quality Problem Report (QPR) yang harus Anda lengkapi hari ini akibat temuan NG.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-[20px] shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden border border-slate-100 relative min-h-[150px]">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                        <p class="text-slate-400 font-bold text-sm animate-pulse">Loading data...</p>
                    </div>

                    <div x-show="!loading && activities.filter(a => a.modul === 'QPR').length === 0" class="p-10 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-slate-600 font-bold">Kerja Bagus!</p>
                        <p class="text-xs text-slate-400">Tidak ada cacat (NG) produksi yang perlu Anda laporkan di QPR hari ini.</p>
                    </div>
                    
                    <table x-show="!loading && activities.filter(a => a.modul === 'QPR').length > 0" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400 font-semibold bg-slate-50">
                                <th class="py-3 px-6 w-[25%]">No. Dokumen</th>
                                <th class="py-3 px-6 w-[25%] text-center">Modul</th>
                                <th class="py-3 px-6 w-[25%] text-center">Status</th>
                                <th class="py-3 px-6 w-[25%] text-right pr-10">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-[13px] text-slate-700">
                            <template x-for="item in activities.filter(a => a.modul === 'QPR')" :key="item.id">
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3.5 px-6 font-medium text-slate-800" x-text="item.no_form"></td>
                                    <td class="py-3.5 px-6 text-center text-rose-600 font-bold" x-text="item.modul"></td>
                                    <td class="py-3.5 px-6 text-center">
                                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold" :class="item.statusClass" x-text="item.statusLabel"></span>
                                    </td>
                                    <td class="py-3.5 px-6 text-right pr-8">
                                        <a :href="item.url" class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-rose-600 text-white rounded-lg text-xs font-bold hover:bg-rose-700 transition-colors shadow-sm shadow-rose-200">
                                            Isi Form QPR
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-[15px] font-bold text-slate-800">Antrean QPR / Revisi <span class="text-rose-500">(Butuh Perhatian)</span></h2>
                        <p class="text-xs text-slate-400 mt-0.5">Dokumen inspeksi dan QPR yang menunggu aksi dari role <span class="font-bold uppercase">{{ auth()->user()->role }}</span>.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-[20px] shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden border border-slate-100 relative min-h-[150px]">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                        <p class="text-slate-400 font-bold text-sm animate-pulse">Loading data...</p>
                    </div>

                    <div x-show="!loading && activities.length === 0" class="p-10 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-slate-600 font-bold">Tidak ada antrean tertunda.</p>
                        <p class="text-xs text-slate-400">Anda sudah menyelesaikan semua revisi & QPR Anda!</p>
                    </div>
                    
                    <table x-show="!loading && activities.length > 0" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400 font-semibold bg-white">
                                <th class="py-3 px-6 w-[25%]">No. Dokumen</th>
                                <th class="py-3 px-6 w-[25%] text-center">Modul</th>
                                <th class="py-3 px-6 w-[25%] text-center">Status</th>
                                <th class="py-3 px-6 w-[25%] text-right pr-10">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-[13px] text-slate-700">
                            <template x-for="item in activities" :key="item.id + item.modul">
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3.5 px-6 font-medium text-slate-800" x-text="item.no_form"></td>
                                    <td class="py-3.5 px-6 text-center text-slate-600" x-text="item.modul"></td>
                                    <td class="py-3.5 px-6 text-center">
                                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold" :class="item.statusClass" x-text="item.statusLabel"></span>
                                    </td>
                                    <td class="py-3.5 px-6 text-right pr-8">
                                        <a :href="item.url" class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 text-rose-600 rounded-md text-xs font-semibold hover:bg-rose-100 transition-colors border border-rose-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mini Summary & Profile -->
            <div>
                <h2 class="text-[14px] font-bold text-slate-800 mb-3">Informasi Sistem</h2>
                <div class="bg-white rounded-[20px] border border-slate-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 border-b border-slate-50 pb-4">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Anda masuk sebagai</p>
                                <p class="text-sm font-black text-slate-800 uppercase">{{ auth()->user()->role }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-b border-slate-50 pb-4">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Tanggal Hari Ini</p>
                                <p class="text-sm font-black text-slate-800">{{ now()->format('d M Y') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Waktu Saat Ini</p>
                                <p class="text-sm font-black text-slate-800 tracking-wider font-mono" x-text="currentTime"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QPR Overdue / Mepet Target -->
                <template x-if="!loading">
                    <div class="mt-6">
                        <h2 class="text-[14px] font-bold text-slate-800 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            QPR Mendekati Deadline
                        </h2>
                        <div class="bg-white rounded-[20px] border overflow-hidden" :class="overdueQprs.length > 0 ? 'border-rose-100 shadow-[0_4px_12px_-4px_rgba(225,29,72,0.1)]' : 'border-slate-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)]'">
                            
                            <!-- Empty State -->
                            <template x-if="overdueQprs.length === 0">
                                <div class="p-6 flex flex-col items-center justify-center text-center">
                                    <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p class="text-xs font-bold text-slate-600">Semua Terkendali</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Tidak ada dokumen QPR yang mepet deadline hari ini.</p>
                                </div>
                            </template>

                            <!-- List Overdue -->
                            <template x-if="overdueQprs.length > 0">
                                <ul class="divide-y divide-slate-50">
                                    <template x-for="qpr in overdueQprs" :key="qpr.id">
                                        <li class="p-4 hover:bg-slate-50 transition-colors">
                                            <div class="flex justify-between items-start mb-1">
                                                <a :href="`/qpr/${qpr.id}/edit`" class="font-bold text-slate-800 hover:text-rose-600 transition-colors text-[13px]" x-text="qpr.no_qpr || 'Draft'"></a>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black tracking-wide shrink-0 ml-2" :class="qpr.urgencyClass" x-text="qpr.urgencyText"></span>
                                            </div>
                                            <p class="text-[11px] font-semibold text-slate-500 line-clamp-1 mb-0.5" x-text="qpr.nama_part ? qpr.nama_part + ' (Job ' + (qpr.no_job||'-') + ')' : '-'"></p>
                                            <p class="text-[11px] text-slate-400 line-clamp-1" x-text="qpr.defect || 'Belum ada deskripsi defect'"></p>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                    </div>
                </template>

            </div>


        </div>
        @endif

        <!-- NEW: Supervisor Monitoring Panel -->
        <template x-if="['Supervisor', 'Manager', 'Admin', 'Foreman', 'Leader', 'Group Leader'].includes(role)">
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-[16px] font-bold text-slate-800">Pipeline Monitoring <span class="text-rose-500">(Fungsi Kontrol)</span></h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pantau progres dokumen aktif dari tahap awal hingga selesai untuk mengidentifikasi hambatan.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-[20px] shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden border border-slate-100 relative min-h-[150px]">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                        <p class="text-slate-400 font-bold text-sm animate-pulse">Loading pipeline data...</p>
                    </div>

                    <table x-show="!loading" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400 font-semibold bg-slate-50">
                                <th class="py-3 px-6 w-[25%]">Dokumen</th>
                                <th class="py-3 px-6 w-[10%] text-center">Tipe</th>
                                <th class="py-3 px-6 w-[35%]">Tahapan / Progress</th>
                                <th class="py-3 px-6 w-[20%] text-center">Status</th>
                                <th class="py-3 px-6 w-[10%] text-right pr-6">Detail</th>
                            </tr>
                        </thead>
                        <template x-for="item in monitoringList" :key="item.type + item.id">
                            <tbody class="text-[13px] text-slate-700 border-b border-slate-50">
                                <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer group" @click="expandedId === (item.type + item.id) ? expandedId = null : expandedId = (item.type + item.id)">
                                    <td class="py-3 px-6">
                                        <div class="font-bold text-slate-800" x-text="item.no_form"></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5" x-text="item.info"></div>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span class="inline-block px-2 py-0.5 rounded text-[9px] font-black tracking-wide" 
                                              :class="item.type === 'Item Check' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700'" 
                                              x-text="item.type"></span>
                                    </td>
                                    <td class="py-3 px-6">
                                        <!-- Lembar Inspeksi Pipeline -->
                                        <template x-if="item.type === 'Lembar Inspeksi'">
                                            <div class="flex flex-col gap-2 w-full max-w-[340px]">
                                                <div class="flex items-center gap-1 w-full">
                                                    <div class="h-2.5 flex-1 rounded-l-full transition-all duration-500"
                                                         :style="item.step >= 1 ? 'background:#60a5fa' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 transition-all duration-500"
                                                         :style="item.step >= 2 ? 'background:#3b82f6' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 rounded-r-full transition-all duration-500"
                                                         :style="item.step >= 3 ? 'background:#22c55e' : 'background:#e2e8f0'"></div>
                                                </div>
                                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wide">
                                                    <span :style="item.step >= 1 ? 'color:#3b82f6' : 'color:#cbd5e1'">Draft</span>
                                                    <span :style="item.step >= 2 ? 'color:#3b82f6' : 'color:#cbd5e1'">Foreman</span>
                                                    <span :style="item.step >= 3 ? 'color:#22c55e' : 'color:#cbd5e1'">SPV (Selesai)</span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Item Check Pipeline -->
                                        <template x-if="item.type === 'Item Check'">
                                            <div class="flex flex-col gap-2 w-full max-w-[340px]">
                                                <div class="flex items-center gap-1 w-full">
                                                    <div class="h-2.5 flex-1 rounded-l-full transition-all duration-500"
                                                         :style="item.step >= 1 ? 'background:#06b6d4' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 transition-all duration-500"
                                                         :style="item.step >= 2 ? 'background:#0ea5e9' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 rounded-r-full transition-all duration-500"
                                                         :style="item.step >= 3 ? 'background:#22c55e' : 'background:#e2e8f0'"></div>
                                                </div>
                                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wide">
                                                    <span :style="item.step >= 1 ? 'color:#06b6d4' : 'color:#cbd5e1'">Inspeksi</span>
                                                    <span :style="item.step >= 2 ? 'color:#0ea5e9' : 'color:#cbd5e1'">GL</span>
                                                    <span :style="item.step >= 3 ? 'color:#22c55e' : 'color:#cbd5e1'">Foreman (Selesai)</span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- QPR Pipeline -->
                                        <template x-if="item.type === 'QPR'">
                                            <div class="flex flex-col gap-2 w-full max-w-[340px]">
                                                <div class="flex items-center gap-1 w-full">
                                                    <div class="h-2.5 flex-1 rounded-l-full transition-all duration-500"
                                                         :style="item.step >= 1 ? 'background:#f87171' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 transition-all duration-500"
                                                         :style="item.step >= 2 ? 'background:#ef4444' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 transition-all duration-500"
                                                         :style="item.step >= 3 ? 'background:#a855f7' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 transition-all duration-500"
                                                         :style="item.step >= 4 ? 'background:#3b82f6' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 transition-all duration-500"
                                                         :style="item.step >= 5 ? 'background:#06b6d4' : 'background:#e2e8f0'"></div>
                                                    <div class="h-2.5 flex-1 rounded-r-full transition-all duration-500"
                                                         :style="item.step >= 6 ? 'background:#22c55e' : 'background:#e2e8f0'"></div>
                                                </div>
                                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wide">
                                                    <span :style="item.step >= 1 ? 'color:#ef4444' : 'color:#cbd5e1'" title="Investigasi Temuan">1</span>
                                                    <span :style="item.step >= 2 ? 'color:#ef4444' : 'color:#cbd5e1'" title="Pengecekan Awal">2</span>
                                                    <span :style="item.step >= 3 ? 'color:#a855f7' : 'color:#cbd5e1'" title="Persetujuan Seksi">3</span>
                                                    <span :style="item.step >= 4 ? 'color:#3b82f6' : 'color:#cbd5e1'" title="Verifikasi 1">4</span>
                                                    <span :style="item.step >= 5 ? 'color:#06b6d4' : 'color:#cbd5e1'" title="Verifikasi 2">5</span>
                                                    <span :style="item.step >= 6 ? 'color:#22c55e' : 'color:#cbd5e1'" title="Verifikasi 3 / Selesai">6</span>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600" x-text="item.statusLabel"></span>
                                    </td>
                                    <td class="py-3 px-4 text-right pr-6">
                                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wide transition-all"
                                                :class="expandedId === (item.type + item.id) ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">
                                            <span x-text="expandedId === (item.type + item.id) ? 'Tutup' : 'Detail'"></span>
                                            <svg class="w-3.5 h-3.5 transition-transform" :class="expandedId === (item.type + item.id) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Detail Dropdown Row -->
                                <tr x-show="expandedId === (item.type + item.id)" x-transition class="bg-slate-50 border-b-4 border-slate-100">
                                    <td colspan="5" class="px-6 py-6 border-t border-slate-100">
                                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                                            
                                            <!-- LEFT COLUMN: VERTICAL TIMELINE -->
                                            <div class="lg:col-span-7 relative bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                                                <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-6">Tracking Timeline</h4>
                                                
                                                <div class="relative ml-2">
                                                    <!-- Garis vertikal penghubung -->
                                                    <div class="absolute left-2.5 top-2 bottom-2 w-0.5 bg-slate-100 rounded-full"></div>
                                                    
                                                    <!-- Timeline Steps -->
                                                    <template x-if="item.type === 'Lembar Inspeksi'">
                                                        <div class="space-y-6 relative">
                                                            <!-- Step 1: Draft -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 1 ? 'bg-blue-500 text-white shadow-md shadow-blue-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 1 ? 'text-slate-800' : 'text-slate-400'">Pembuatan Form (Draft)</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 1 ? 'text-slate-500' : 'text-slate-400'">Pembuatan form awal Lembar Inspeksi.</p>
                                                                </div>
                                                            </div>
                                                            <!-- Step 2: Foreman -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 2 ? 'bg-blue-500 text-white shadow-md shadow-blue-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 2 ? 'text-slate-800' : 'text-slate-400'">Review Foreman</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 2 ? 'text-slate-500' : 'text-slate-400'">Pengecekan awal oleh Foreman.</p>
                                                                </div>
                                                            </div>
                                                            <!-- Step 3: SPV -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 3 ? 'bg-emerald-500 text-white shadow-md shadow-emerald-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 3 ? 'text-slate-800' : 'text-slate-400'">Verifikasi Supervisor</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 3 ? 'text-slate-500' : 'text-slate-400'">Approval akhir dokumen dan siap digunakan.</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <template x-if="item.type === 'Item Check'">
                                                        <div class="space-y-6 relative">
                                                            <!-- Step 1: OPR -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 1 ? 'bg-cyan-500 text-white shadow-md shadow-cyan-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 1 ? 'text-slate-800' : 'text-slate-400'">Inspeksi Aktual (QC/OPR)</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 1 ? 'text-slate-500' : 'text-slate-400'">Pengecekan part dan penentuan OK/NG.</p>
                                                                </div>
                                                            </div>
                                                            <!-- Step 2: GL -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 2 ? 'bg-blue-500 text-white shadow-md shadow-blue-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 2 ? 'text-slate-800' : 'text-slate-400'">Review Group Leader</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 2 ? 'text-slate-500' : 'text-slate-400'">Group leader mengevaluasi hasil inspeksi.</p>
                                                                </div>
                                                            </div>
                                                            <!-- Step 3: Foreman -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 3 ? 'bg-emerald-500 text-white shadow-md shadow-emerald-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 3 ? 'text-slate-800' : 'text-slate-400'">Pengesahan Foreman</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 3 ? 'text-slate-500' : 'text-slate-400'">Foreman mengesahkan hasil Item Check.</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- QPR Timeline Steps -->
                                                    <template x-if="item.type === 'QPR'">
                                                        <div class="space-y-6 relative">
                                                            <!-- Step 1: Temuan -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 1 ? 'bg-rose-500 text-white shadow-md shadow-rose-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 1 ? 'text-slate-800' : 'text-slate-400'">Investigasi Temuan</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 1 ? 'text-slate-500' : 'text-slate-400'">Laporan QPR di-generate karena ada defect (NG).</p>
                                                                </div>
                                                            </div>
                                                            <!-- Step 2: Foreman -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 2 ? 'bg-rose-500 text-white shadow-md shadow-rose-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 2 ? 'text-slate-800' : 'text-slate-400'">Pengecekan Awal</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 2 ? 'text-slate-500' : 'text-slate-400'">Pengecekan oleh Group Leader / Foreman.</p>
                                                                </div>
                                                            </div>
                                                            <!-- Step 3: SPV -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 3 ? 'bg-purple-500 text-white shadow-md shadow-purple-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 3 ? 'text-slate-800' : 'text-slate-400'">Persetujuan Seksi Terkait</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 3 ? 'text-slate-500' : 'text-slate-400'">Review dari Supervisor atau Manager Seksi.</p>
                                                                </div>
                                                            </div>
                                                            <!-- Step 4, 5, 6: Verifikasi -->
                                                            <div class="flex gap-4 group">
                                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 z-10 transition-all duration-300 ring-4 ring-white"
                                                                     :class="item.step >= 4 ? 'bg-blue-500 text-white shadow-md shadow-blue-200' : 'bg-slate-100 text-slate-300'">
                                                                     <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-bold transition-colors" :class="item.step >= 4 ? 'text-slate-800' : 'text-slate-400'">Verifikasi Quality Control</p>
                                                                    <p class="text-[10px] mt-0.5 transition-colors" :class="item.step >= 4 ? 'text-slate-500' : 'text-slate-400'">Tahapan verifikasi kualitas QC (1, 2, 3).</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- RIGHT COLUMN: INFO & ACTIONS -->
                                            <div class="lg:col-span-5 flex flex-col justify-center">
                                                <!-- Item Check / Lembar Inspeksi Details Box -->
                                                <template x-if="item.type === 'Item Check' || item.type === 'Lembar Inspeksi'">
                                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                                                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                                                            <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center shrink-0">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                            </div>
                                                            <div>
                                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest" x-text="`Informasi ${item.type}`"></p>
                                                                <p class="text-sm font-bold text-slate-700" x-text="item.no_form"></p>
                                                            </div>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">No. Job</p>
                                                                <p class="text-xs font-bold text-slate-700" x-text="item.raw.job_no || '-'"></p>
                                                            </div>
                                                            <div>
                                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Operator</p>
                                                                <p class="text-xs font-bold text-slate-700" x-text="item.raw.assigned_operator ? item.raw.assigned_operator.name : 'Belum Klaim'"></p>
                                                            </div>
                                                            <div class="col-span-2 pt-2">
                                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Judgement</p>
                                                                <span class="inline-block font-black px-3 py-1 rounded-lg text-[10px]"
                                                                    :style="getLiJudgement(item.raw) === 'OK' ? 'background:#d1fae5;color:#065f46' : (getLiJudgement(item.raw) === 'NG' ? 'background:#fee2e2;color:#991b1b' : 'background:#f1f5f9;color:#64748b')"
                                                                    x-text="getLiJudgement(item.raw) || 'Menunggu Proses Selesai'"></span>
                                                            </div>
                                                        </div>
                                                        <div class="pt-4 border-t border-slate-100">
                                                            <a :href="item.url" class="flex w-full items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 text-white font-black rounded-xl hover:bg-slate-900 transition-all shadow-sm text-[11px] uppercase tracking-wide">
                                                                <span x-text="`Buka Form ${item.type}`"></span>
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </template>
                                                
                                                <!-- QPR Details Box -->
                                                <template x-if="item.type === 'QPR'">
                                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                                                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                                                            <div class="w-10 h-10 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center shrink-0">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                            </div>
                                                            <div>
                                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Informasi QPR</p>
                                                                <p class="text-sm font-bold text-slate-700" x-text="item.no_form"></p>
                                                            </div>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div class="col-span-2">
                                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Defect / Jenis</p>
                                                                <p class="text-xs font-bold text-slate-700 line-clamp-2" x-text="item.raw.defect || '-'"></p>
                                                            </div>
                                                            <div>
                                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Reject Qty</p>
                                                                <p class="text-xs font-black text-rose-600" x-text="(item.raw.reject_qty || '0') + ' PCS'"></p>
                                                            </div>
                                                            <div>
                                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Source Item Check</p>
                                                                <template x-if="item.sourceLiUrl">
                                                                    <a :href="item.sourceLiUrl" class="text-xs font-bold text-blue-600 hover:underline">Lihat Item Check &rarr;</a>
                                                                </template>
                                                                <template x-if="!item.sourceLiUrl">
                                                                    <span class="text-xs font-bold text-slate-400">-</span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <div class="pt-4 border-t border-slate-100">
                                                            <a :href="item.url" class="flex w-full items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 text-white font-black rounded-xl hover:bg-rose-700 transition-all shadow-sm shadow-rose-200 text-[11px] uppercase tracking-wide">
                                                                Buka Form QPR
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                        <tbody x-show="monitoringList.length === 0">
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-400 text-sm">Tidak ada dokumen aktif yang sedang berjalan di production floor saat ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
    @endif

    @push('scripts')

    <script>
        function dashboardData() {
            return {
                role: '{{ auth()->user()->role }}',
                userId: {{ auth()->id() }},
                loading: true,
                activities: [],
                quickStats: [],
                monitoringList: [],
                overdueQprs: [],
                expandedId: null,
                currentTime: '--:--:--',
                clockInterval: null,

                async initDashboard() {
                    this.updateClock();
                    this.clockInterval = setInterval(() => this.updateClock(), 1000);
                    
                    this.loading = true;
                    try {
                        const ts = new Date().getTime();

                        // Fetch LI data
                        const resLi = await axios.get('/api/inspeksi');
                        const dataLi = Array.isArray(resLi.data) ? resLi.data : (resLi.data.data || []);
                        
                        // Fetch QPR data
                        let dataQpr = [];
                        try {
                            const resQpr = await axios.get(`/api/qprs?_t=${ts}`);
                            dataQpr = Array.isArray(resQpr.data) ? resQpr.data : (resQpr.data.data || []);
                        } catch (e) {
                            console.log('Error fetching QPR for dashboard', e);
                        }

                        // Fetch Item Check data
                        let dataIc = [];
                        try {
                            const resIc = await axios.get(`/api/item-check/summary?_t=${ts}`);
                            dataIc = Array.isArray(resIc.data) ? resIc.data : (resIc.data.data || []);
                        } catch (e) {
                            console.log('Error fetching Item Check for dashboard', e);
                        }
                        
                        this.processData(dataLi, dataIc, dataQpr);
                    } catch (e) {
                        console.error('Failed to load dashboard data', e);
                    } finally {
                        this.loading = false;
                    }
                },

                processData(listLi, listIc, listQpr) {
                    // â”€â”€ Central Status Config â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                    const LHI_STATUS = {
                        revision:            { role: 'Leader',       label: 'Perlu Revisi',     cls: 'bg-rose-50 text-rose-600' },
                        submitted:           { role: 'Foreman',      label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_foreman:     { role: 'Foreman',      label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_qc_approval: { role: 'Foreman',      label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_supervisor:  { role: 'Supervisor',   label: 'Menunggu SPV',     cls: 'bg-purple-50 text-purple-600' },
                        locked:              { role: 'Operator',     label: 'Siap Dicek QC',    cls: 'bg-sky-50 text-sky-600' },
                        ready_for_qc:        { role: 'Operator',     label: 'Siap Dicek QC',    cls: 'bg-sky-50 text-sky-600' },
                        _gl_qc_approval:     { role: 'Group Leader', label: 'Verifikasi QC',    cls: 'bg-amber-50 text-amber-600' },
                    };
                    const QPR_STATUS = {
                        draft:              { role: 'Operator',  label: 'Perlu Diisi',      cls: 'bg-sky-100 text-sky-700' },
                        waiting_foreman:    { role: 'Foreman',   label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_supervisor: { role: 'Supervisor', label: 'Menunggu SPV',    cls: 'bg-purple-50 text-purple-600' },
                        waiting_manager:    { role: 'Manager',   label: 'Menunggu Manager', cls: 'bg-purple-50 text-purple-600' },
                    };
                    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

                    let acts = [];
                    let stats = [
                        { title: 'Total LI', value: listLi.length, bgClass: 'bg-blue-50', textClass: 'text-blue-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>' }
                    ];

                    let urgentLi = 0;
                    let monitor = [];
                    
                    listLi.forEach(d => {
                        let isUrgentForMe = false;
                        let statusLabel = d.status;
                        let statusClass = 'bg-slate-100 text-slate-600';
                        
                        // Monitoring step mapping
                        let step = 1;
                        let monitorLabel = 'Draft';
                        if (d.status === 'draft' || d.status === 'submitted' || d.status === 'revision') { step = 1; monitorLabel = (d.status === 'revision' ? 'Revisi OPR' : 'Draft / Submitted'); }
                        else if (d.status === 'waiting_foreman') { step = 2; monitorLabel = 'Menunggu Foreman'; }
                        else if (d.status === 'waiting_supervisor') { step = 3; monitorLabel = 'Menunggu SPV'; }
                        else if (d.status === 'locked' || d.status === 'ready_for_qc' || d.status === 'waiting_qc_approval') { step = 4; monitorLabel = 'QC / Verifikasi'; }
                        else if (d.status === 'finished' || d.status === 'approved') { step = 5; monitorLabel = 'Selesai'; }
                        
                        // Only track manual Lembar Inspeksi (not locked/archived)
                        if (d.status !== 'locked' && d.status !== 'archived_template') {
                            monitor.push({
                                id: d.id,
                                type: 'Lembar Inspeksi',
                                no_form: d.no_form || 'LI-Draft',
                                info: d.part_name || '-',
                                step: step,
                                statusLabel: monitorLabel,
                                url: `/li/${d.id}/edit`,
                                date: new Date(d.updated_at || d.created_at),
                                raw: d
                            });
                        }

                        // Resolve urgency using central LHI_STATUS config
                        const lhiCfg = LHI_STATUS[d.status];
                        const isGlQc = (this.role === 'Group Leader' && d.status === 'waiting_qc_approval');
                        if (isGlQc) {
                            isUrgentForMe = true;
                            statusLabel = LHI_STATUS._gl_qc_approval.label;
                            statusClass = LHI_STATUS._gl_qc_approval.cls;
                        } else if (lhiCfg && lhiCfg.role === this.role) {
                            isUrgentForMe = true;
                            statusLabel = lhiCfg.label;
                            statusClass = lhiCfg.cls;
                        } else if (this.role === 'Leader' && d.status === 'draft') {
                            isUrgentForMe = true;
                            statusLabel = 'Perlu Revisi';
                            statusClass = 'bg-rose-50 text-rose-600';
                        } else if (this.role === 'Admin' && d.status !== 'finished' && d.status !== 'approved') {
                            isUrgentForMe = true;
                            statusLabel = d.status;
                            statusClass = 'bg-amber-50 text-amber-600';
                        }

                        if (isUrgentForMe) {
                            urgentLi++;
                            acts.push({
                                id: d.id,
                                modul: 'Lembar Inspeksi',
                                no_form: d.no_form || 'Draft',
                                statusLabel: statusLabel,
                                statusClass: statusClass,
                                url: `/li/${d.id}/edit`,
                                date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });

                    stats.push({ title: 'Tugas Lembar Inspeksi', value: urgentLi, bgClass: 'bg-amber-50', textClass: 'text-amber-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' });

                    let urgentIc = 0;
                    listIc.forEach(d => {
                        let isUrgentForMe = false;
                        let statusLabel = d.status;
                        let statusClass = 'bg-slate-100 text-slate-600';
                        
                        let step = 1;
                        let monitorLabel = 'Proses';
                        if (d.status === 'draft' || d.status === 'in_progress' || d.status === 'submitted' || d.status === 'waiting_qc_approval') { 
                            step = 1; 
                            monitorLabel = 'Pengecekan Aktual'; 
                            if (d.status === 'waiting_qc_approval') { step = 2; monitorLabel = 'Review Pengecekan'; }
                            if (d.gl_signed) { step = 3; monitorLabel = 'Pengesahan Inspeksi'; }
                            if (d.foreman_signed) { step = 4; monitorLabel = 'Selesai'; }
                        }
                        else if (d.status === 'waiting_gl') { step = 2; monitorLabel = 'Menunggu TTD GL'; }
                        else if (d.status === 'waiting_foreman') { step = 3; monitorLabel = 'Review Foreman'; }
                        else if (d.status === 'finished' || d.status === 'locked' || d.status === 'approved') { step = 4; monitorLabel = 'Selesai'; }
                        
                        console.log('DEBUG PROCESS_DATA IC:', {id: d.id, no_form: d.no_form, status: d.status, gl_signed: d.gl_signed, step: step});
                        
                        // Add to Monitoring Pipeline
                        if (d.status !== 'finished') {
                            monitor.push({
                                id: d.id,
                                type: 'Item Check',
                                no_form: d.no_form || ('IC-' + String(d.id).padStart(5, '0')),
                                info: d.part_name || '-',
                                step: step,
                                statusLabel: monitorLabel,
                                url: `/item-check/${d.id}/form`,
                                date: new Date(d.updated_at || d.created_at),
                                raw: d
                            });
                        }

                        // Resolve urgency
                        if (d.status === 'waiting_gl' && this.role === 'Group Leader') {
                            isUrgentForMe = true; statusLabel = 'Menunggu GL'; statusClass = 'bg-amber-50 text-amber-600';
                        } else if (d.status === 'waiting_foreman' && (this.role === 'Foreman' || this.role === 'Supervisor')) {
                            isUrgentForMe = true; statusLabel = 'Menunggu Foreman'; statusClass = 'bg-amber-50 text-amber-600';
                        } else if (d.status === 'waiting_qc_approval' && (this.role === 'Group Leader' || this.role === 'Foreman')) {
                            isUrgentForMe = true; statusLabel = 'Verifikasi QC'; statusClass = 'bg-purple-50 text-purple-600';
                        }

                        if (isUrgentForMe) {
                            urgentIc++;
                            acts.push({
                                id: d.id, modul: 'Item Check', no_form: d.no_form || ('IC-' + String(d.id).padStart(5, '0')),
                                statusLabel: statusLabel, statusClass: statusClass, url: `/item-check/${d.id}/form`,
                                date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });

                    stats.push({ title: 'Tugas Item Check', value: urgentIc, bgClass: 'bg-blue-50', textClass: 'text-blue-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>' });

                    let urgentQpr = 0;
                    listQpr.forEach(d => {
                        let isUrgentForMe = false;
                        let statusLabel = d.status;
                        let statusClass = 'bg-slate-100 text-slate-600';

                        // Monitoring step mapping
                        let step = 1;
                        let monitorLabel = 'In Progress';
                        if (d.status === 'draft') { step = 1; monitorLabel = 'Investigasi / Temuan'; }
                        else if (d.status === 'waiting_foreman') { step = 2; monitorLabel = 'Pengecekan Awal (GL/Foreman)'; }
                        else if (d.status === 'waiting_supervisor' || d.status === 'waiting_manager' || d.status === 'waiting_seksi') { step = 3; monitorLabel = 'Persetujuan Seksi Terkait'; }
                        else if (d.status === 'verif_1') { step = 4; monitorLabel = 'Verifikasi 1'; }
                        else if (d.status === 'verif_2') { step = 5; monitorLabel = 'Verifikasi 2'; }
                        else if (d.status === 'verif_3') { step = 6; monitorLabel = 'Verifikasi 3'; }
                        else if (d.status === 'approved' || d.status === 'closed') { step = 6; monitorLabel = 'Selesai'; }

                        if (d.status !== 'approved' && d.status !== 'closed') {
                            let sourceLi = listLi.find(li => li.qpr_id === d.id);
                            let sourceLiUrl = sourceLi ? `/li/${sourceLi.id}/edit` : null;

                            monitor.push({
                                id: d.id,
                                type: 'QPR',
                                no_form: d.no_qpr || 'QPR-Draft',
                                info: d.nama_part ? (d.nama_part + ' (Job ' + (d.no_job || '-') + ')') : '-',
                                step: step,
                                statusLabel: monitorLabel,
                                url: `/qpr/${d.id}/edit`,
                                sourceLiUrl: sourceLiUrl,
                                date: new Date(d.updated_at || d.created_at),
                                raw: d
                            });
                        }

                        // Resolve urgency
                        // 1. QPR Draft: hanya operator yang di-assign (created_by match userId)
                        if (d.status === 'draft' || d.status === 'Draft') {
                            if (d.created_by === this.userId) {
                                isUrgentForMe = true;
                                statusLabel = 'Perlu Diisi';
                                statusClass = 'bg-sky-100 text-sky-700';
                            }
                        // 2. Status lain: gunakan QPR_STATUS config
                        } else {
                            const qprCfg = QPR_STATUS[d.status];
                            if (qprCfg && qprCfg.role === this.role) {
                                isUrgentForMe = true;
                                statusLabel = qprCfg.label;
                                statusClass = qprCfg.cls;
                            } else if (this.role === 'Admin' && d.status !== 'closed' && d.status !== 'approved') {
                                // Admin melihat semua QPR aktif
                                isUrgentForMe = true;
                                statusLabel = 'Monitoring';
                                statusClass = 'bg-slate-100 text-slate-600';
                            }
                        }

                        if (isUrgentForMe) {
                            urgentQpr++;
                            acts.push({
                                id: d.id,
                                modul: 'QPR',
                                no_form: d.no_qpr || 'QPR-Draft',
                                statusLabel: statusLabel,
                                statusClass: statusClass,
                                url: `/qpr/${d.id}/edit`,
                                date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });

                    // â”€â”€ Calculate Overdue QPRs â”€â”€
                    let overdueList = [];
                    let today = new Date();
                    today.setHours(0,0,0,0);
                    
                    listQpr.forEach(d => {
                        if (d.status !== 'approved' && d.status !== 'closed' && d.target_selesai) {
                            let targetDate = new Date(d.target_selesai);
                            targetDate.setHours(0,0,0,0);
                            let diffTime = targetDate.getTime() - today.getTime();
                            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            
                            // Tampilkan jika terlambat, hari ini, atau sisa <= 3 hari
                            if (diffDays <= 3) {
                                let urgencyText = '';
                                let urgencyClass = '';
                                
                                if (diffDays < 0) {
                                    urgencyText = `Terlambat ${Math.abs(diffDays)} Hari`;
                                    urgencyClass = 'bg-rose-100 text-rose-700';
                                } else if (diffDays === 0) {
                                    urgencyText = 'Hari Ini!';
                                    urgencyClass = 'bg-rose-500 text-white shadow-sm shadow-rose-200';
                                } else {
                                    urgencyText = `Sisa ${diffDays} Hari`;
                                    urgencyClass = 'bg-amber-100 text-amber-700';
                                }
                                
                                overdueList.push({ ...d, diffDays, urgencyText, urgencyClass });
                            }
                        }
                    });
                    
                    overdueList.sort((a, b) => a.diffDays - b.diffDays);
                    this.overdueQprs = overdueList;

                    stats.push({ title: 'Tugas QPR', value: urgentQpr, bgClass: 'bg-rose-50', textClass: 'text-rose-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' });

                    let finishedLi = listLi.filter(d => d.status === 'locked' || d.status === 'approved').length;
                    let finishedIc = listIc.filter(d => d.status === 'finished' || d.status === 'approved').length;
                    stats.push({ title: 'Item Check Selesai', value: finishedIc, bgClass: 'bg-emerald-50', textClass: 'text-emerald-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' });

                    // Sort activities by date desc
                    acts.sort((a, b) => b.date - a.date);
                    monitor.sort((a, b) => b.date - a.date);
                    
                    this.activities = acts.slice(0, 10);
                    this.monitoringList = monitor.slice(0, 20); // Track top 20 active processes
                    this.quickStats = stats;
                },

                getLiJudgement(item) {
                    // Judgement hanya valid setelah QC selesai sepenuhnya
                    const notFinalYet = ['draft', 'submitted', 'waiting_foreman', 'revision',
                                         'waiting_supervisor', 'locked', 'ready_for_qc', 'waiting_qc_approval'];
                    if (notFinalYet.includes(item.status)) {
                        return null; // Proses belum selesai, jangan tampilkan judgement
                    }

                    const j = item.qg_judgement;
                    if (!j) return null;

                    if (j === 'OK' || j === 'NG') return j;

                    try {
                        const obj = JSON.parse(j);
                        const values = Object.values(obj);
                        if (values.some(v => typeof v === 'string' && v.toUpperCase().includes('NG'))) {
                            return 'NG';
                        }
                        return 'OK'; 
                    } catch (e) {
                        return j.toUpperCase().includes('NG') ? 'NG' : 'OK';
                    }
                },

                updateClock() {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    this.currentTime = `${hours}:${minutes}:${seconds}`;
                }
            }
        }

        function supervisorDashboard() {
            return {
                stats: {
                    total: 0, pending: 0, ng: 0, finished: 0,
                    ng_rate: 0, completion_rate: 0, approval_rate: 0,
                    avg_per_day: 0, max_day: 0, min_day: 0, week_total: 0,
                    trend_total: ''
                },
                recentActivities: [],
                topDefectParts: [],
                _chartInstances: {},

                async init() {
                    try {
                        const res = await axios.get('/api/inspeksi');
                        const data = Array.isArray(res.data) ? res.data : (res.data.data || []);
                        this.processData(data);
                        this.$nextTick(() => setTimeout(() => this.renderCharts(data), 80));
                    } catch(e) { console.error('Supervisor dashboard error:', e); }
                },

                processData(data) {
                    const total = data.length;
                    const ng = data.filter(d => d.qg_judgement && d.qg_judgement.includes('NG')).length;
                    const pending = data.filter(d => d.status === 'waiting_supervisor').length;
                    const finished = data.filter(d => d.status === 'finished' || d.status === 'approved').length;
                    const approved = data.filter(d => d.status !== 'waiting_supervisor' && d.status !== 'revision' && d.status !== 'draft').length;

                    this.stats.total = total;
                    this.stats.pending = pending;
                    this.stats.ng = ng;
                    this.stats.finished = finished;
                    this.stats.ng_rate = total ? (ng / total * 100).toFixed(1) : 0;
                    this.stats.completion_rate = total ? Math.round(finished / total * 100) : 0;
                    this.stats.approval_rate = total ? Math.round(approved / total * 100) : 0;

                    // 7-day trend
                    const days = {};
                    const now = new Date();
                    for (let i = 6; i >= 0; i--) {
                        const d = new Date(now); d.setDate(d.getDate() - i);
                        const key = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                        days[key] = 0;
                    }
                    data.forEach(d => {
                        const dt = new Date(d.tgl_bulan || d.created_at);
                        const key = dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                        if (days.hasOwnProperty(key)) days[key]++;
                    });

                    const dayVals = Object.values(days);
                    this.stats.week_total = dayVals.reduce((a,b) => a+b, 0);
                    this.stats.avg_per_day = (this.stats.week_total / 7).toFixed(1);
                    this.stats.max_day = Math.max(...dayVals);
                    this.stats.min_day = Math.min(...dayVals);
                    this.stats.trend_total = '+' + this.stats.week_total + ' minggu ini';

                    // Activity feed (latest 8, sorted by date)
                    const statusLabel = {
                        waiting_supervisor: { label: 'Menunggu Approve SPV', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>', bg: 'bg-violet-100 text-violet-600' },
                        finished: { label: 'Selesai diverifikasi', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', bg: 'bg-emerald-100 text-emerald-600' },
                        approved: { label: 'Selesai diverifikasi', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', bg: 'bg-emerald-100 text-emerald-600' },
                        waiting_foreman: { label: 'Menunggu Foreman', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', bg: 'bg-amber-100 text-amber-600' },
                        revision: { label: 'Perlu Revisi', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>', bg: 'bg-rose-100 text-rose-600' },
                    };

                    const sorted = [...data].sort((a,b) => new Date(b.updated_at||b.created_at) - new Date(a.updated_at||a.created_at));
                    this.recentActivities = sorted.slice(0, 8).map(d => {
                        const cfg = statusLabel[d.status] || { label: d.status, icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>', bg: 'bg-slate-100 text-slate-600' };
                        const dt = new Date(d.updated_at || d.created_at);
                        const mins = Math.round((now - dt) / 60000);
                        const timeStr = mins < 60 ? mins + ' menit lalu' : mins < 1440 ? Math.round(mins/60) + ' jam lalu' : Math.round(mins/1440) + ' hari lalu';
                        return { id: d.id, title: cfg.label + (d.no_form ? ' â€” ' + d.no_form : ''), sub: (d.part_name||'-') + (d.line ? ' Â· Line ' + d.line : ''), icon: cfg.icon, iconBg: cfg.bg, time: timeStr };
                    });

                    // Top NG Parts
                    const ngParts = {};
                    data.filter(d => d.qg_judgement && d.qg_judgement.includes('NG')).forEach(d => {
                        const p = d.part_name || 'Unknown'; ngParts[p] = (ngParts[p]||0) + 1;
                    });
                    const maxNg = Math.max(...Object.values(ngParts), 1);
                    this.topDefectParts = Object.entries(ngParts).sort((a,b)=>b[1]-a[1]).slice(0,5)
                        .map(([name, count]) => ({ name, count, pct: Math.round(count/maxNg*100) }));
                },

                renderCharts(data) {
                    if (typeof Chart === 'undefined') return;

                    // 7-day Trend Line Chart
                    const volCtx = document.getElementById('spvVolumeChart');
                    if (volCtx) {
                        if (Chart.getChart(volCtx)) Chart.getChart(volCtx).destroy();
                        const days = {}; const now = new Date();
                        for (let i = 6; i >= 0; i--) {
                            const d = new Date(now); d.setDate(d.getDate()-i);
                            days[d.toLocaleDateString('id-ID',{day:'numeric',month:'short'})] = 0;
                        }
                        data.forEach(d => {
                            const dt = new Date(d.tgl_bulan||d.created_at);
                            const k = dt.toLocaleDateString('id-ID',{day:'numeric',month:'short'});
                            if(days.hasOwnProperty(k)) days[k]++;
                        });
                        const labels = Object.keys(days), values = Object.values(days);
                        new Chart(volCtx, {
                            type:'line', data:{ labels, datasets:[{
                                data: values, borderColor:'#e11d48',
                                backgroundColor:'rgba(225,29,72,0.08)', borderWidth:2.5,
                                fill:true, tension:0.4,
                                pointBackgroundColor:'#e11d48', pointBorderColor:'#fff',
                                pointBorderWidth:2, pointRadius:5, pointHoverRadius:7
                            }]},
                            options:{ responsive:true, maintainAspectRatio:false,
                                plugins:{ legend:{display:false}, tooltip:{
                                    callbacks:{ label: ctx => ' ' + ctx.parsed.y + ' inspeksi' }
                                }},
                                scales:{
                                    y:{beginAtZero:true, ticks:{stepSize:1}, grid:{color:'rgba(0,0,0,0.04)'}, border:{display:false}},
                                    x:{grid:{display:false}, border:{display:false}}
                                }
                            }
                        });
                    }

                    // Completion Donut
                    const compCtx = document.getElementById('spvCompletionChart');
                    if (compCtx) {
                        if (Chart.getChart(compCtx)) Chart.getChart(compCtx).destroy();
                        const v = this.stats.completion_rate;
                        new Chart(compCtx, { type:'doughnut', data:{ datasets:[{
                            data:[v,100-v], backgroundColor:['#10b981','#f0fdf4'],
                            borderWidth:0, cutout:'78%'
                        }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{tooltip:{enabled:false}} } });
                    }

                    // Approval Donut
                    const apprCtx = document.getElementById('spvApprovalChart');
                    if (apprCtx) {
                        if (Chart.getChart(apprCtx)) Chart.getChart(apprCtx).destroy();
                        const v = this.stats.approval_rate;
                        new Chart(apprCtx, { type:'doughnut', data:{ datasets:[{
                            data:[v,100-v], backgroundColor:['#3b82f6','#eff6ff'],
                            borderWidth:0, cutout:'78%'
                        }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{tooltip:{enabled:false}} } });
                    }
                }
            }
        }

        // â•â• DASHBOARD SUMMARY (Supervisor) â•â•
        function dashboardSummary() {
            return {
                loading: false,
                trackingLoading: true,
                activePreset: 'today',
                dateFrom: '',
                dateTo: '',
                presets: [
                    { key: 'today',   label: 'Hari Ini' },
                    { key: 'week',    label: '7 Hari' },
                    { key: 'month',   label: 'Bulan Ini' },
                    { key: 'custom',  label: 'Custom' },
                ],
                selectedPart: 'Semua',
                availableParts: [],
                get formattedPeriode() {
                    const fmt = (d) => {
                        if (!d) return '';
                        return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                    };
                    if (!this.dateFrom || !this.dateTo) return 'Memuat...';
                    if (this.dateFrom === this.dateTo) return fmt(this.dateFrom);
                    return fmt(this.dateFrom) + ' - ' + fmt(this.dateTo);
                },
                get filteredLi() {
                    if (this.selectedPart === 'Semua') return this.li.items;
                    return this.li.items.filter(i => i.part_name === this.selectedPart);
                },
                get filteredTotal() { return this.filteredLi.length; },
                get filteredOk() { return this.filteredLi.filter(i => this.getJudgement(i) === 'OK').length; },
                get filteredNg() { return this.filteredLi.filter(i => this.getJudgement(i) === 'NG').length; },
                get filteredFinished() { return this.filteredLi.filter(i => i.status === 'finished' || i.status === 'approved').length; },
                get partStatsArray() {
                    const stats = {};
                    this.li.items.forEach(i => {
                        const name = i.part_name || 'Unknown Part';
                        if (!stats[name]) {
                            stats[name] = { 
                                name: name, 
                                total: 0, 
                                ok: 0, 
                                ng: 0, 
                                finished: 0, 
                                jobs: new Set(), 
                                lines: new Set(), 
                                jobsMap: {},
                                missingFormula: false,
                                claimedMissingFormula: false,
                                activeProgressTotal: 0,
                                activeProgressChecked: 0,
                                hasActive: false
                            };
                        }
                        
                        const isClaimed = !!i.assigned_operator_id || !!i.operator_claimed_at;
                        const hasFormula = !!(i.sampling_cols && i.sampling_cols.length > 0) || Number(i.max_sample) > 0 || Number(i.tact_time) > 0 || Number(i.ct_dimensi) > 0;
                        if (!hasFormula) {
                            stats[name].missingFormula = true;
                            if (isClaimed) {
                                stats[name].claimedMissingFormula = true;
                            }
                        }

                        stats[name].hasActive = true;
                        
                        let validCols = new Set();
                        let totalSamples = 0;
                        let prodTotal = Number(i.total_produksi) || 0;
                        
                        if (i.sampling_cols && i.sampling_cols.length > 0) {
                            let sCols = i.sampling_cols;
                            if (typeof sCols === 'string') sCols = JSON.parse(sCols);
                            if (Array.isArray(sCols) && prodTotal > 0) {
                                let count = sCols.filter(c => Number(c) < prodTotal).length;
                                totalSamples = count + 1;
                                sCols.filter(c => Number(c) < prodTotal).forEach(c => validCols.add(String(c)));
                                validCols.add(String(prodTotal));
                            } else if (Array.isArray(sCols)) {
                                totalSamples = sCols.length;
                                sCols.forEach(c => validCols.add(String(c)));
                            }
                        } else {
                            totalSamples = Number(i.max_sample) || 0;
                            for (let c = 1; c <= totalSamples; c++) validCols.add(String(c));
                        }

                        const colJudgements = {};
                        const parseData = (dataStr) => {
                            try {
                                let data = dataStr;
                                if (typeof data === 'string') data = JSON.parse(data);
                                if (data && typeof data === 'object') {
                                    for (let key in data) {
                                        const match = key.match(/_(\d+)$/);
                                        if (match) {
                                            const col = match[1];
                                            if (!validCols.has(col)) continue;
                                            const val = String(data[key]).trim().toLowerCase();
                                            if (val === 'ok' || val === 'ng') {
                                                if (!colJudgements[col]) colJudgements[col] = 'ok';
                                                if (val === 'ng') colJudgements[col] = 'ng';
                                            }
                                        }
                                    }
                                }
                            } catch(e) {}
                        };

                        parseData(i.hasil_visual);
                        parseData(i.hasil_dimensi);

                        let checked = Object.keys(colJudgements).length;
                        if (i.status === 'finished' || i.status === 'approved' || i.status === 'locked') {
                            checked = Math.max(checked, totalSamples);
                        }

                        if (hasFormula) {
                            stats[name].activeProgressTotal += totalSamples;
                            stats[name].activeProgressChecked += checked;
                        }

                        stats[name].total++;
                        
                        if (i.job_no) {
                            stats[name].jobs.add(i.job_no);
                            if (!stats[name].jobsMap[i.job_no]) stats[name].jobsMap[i.job_no] = 0;
                            stats[name].jobsMap[i.job_no] = Math.max(stats[name].jobsMap[i.job_no], Number(i.total_produksi) || 0);
                        } else {
                            if (!stats[name].jobsMap['no_job']) stats[name].jobsMap['no_job'] = 0;
                            stats[name].jobsMap['no_job'] += Number(i.total_produksi) || 0; 
                        }

                        if (i.line_name) {
                            stats[name].lines.add(i.line_name);
                        } else if (i.line) {
                            stats[name].lines.add(i.line);
                        } else if (i.lokasi) {
                            stats[name].lines.add(i.lokasi);
                        }

                        const j = this.getJudgement(i);
                        
                        let sampleNgCount = 0;
                        let sampleOkCount = 0;
                        
                        for (let col in colJudgements) {
                            if (colJudgements[col] === 'ng') sampleNgCount++;
                            else if (colJudgements[col] === 'ok') sampleOkCount++;
                        }

                        if (j === 'NG' && sampleNgCount === 0) {
                            let rejectQty = Number(i.reject) || 0;
                            sampleNgCount = rejectQty > 0 && rejectQty <= totalSamples ? rejectQty : 1;
                            sampleOkCount = Math.max(0, checked - sampleNgCount);
                        }

                        stats[name].ok += sampleOkCount;
                        stats[name].ng += sampleNgCount;

                        
                        if (i.status === 'finished' || i.status === 'approved') stats[name].finished++;
                    });
                    
                    return Object.values(stats).map(s => {
                        let prodTotal = 0;
                        for (let j in s.jobsMap) {
                            prodTotal += s.jobsMap[j];
                        }
                        return {
                            ...s,
                            prodTotal,
                            jobText: s.jobs.size > 0 ? Array.from(s.jobs).join(' & ') : '-',
                            lineText: s.lines && s.lines.size > 0 ? Array.from(s.lines).join(', ') : '-'
                        };
                    }).sort((a, b) => b.total - a.total);
                },

                li:  { items: [] },
                qpr: { total: 0, pending: 0, approved: 0 },
                qprAllItems: [],
                stats: { avg_per_day: 0, week_total: 0 },
                monitoringList: [],
                overdueQprs: [],

                async init() {
                    this.applyPreset('today');
                    // Data tracking sudah diinject dari server - langsung load
                    this.monitoringList = @json($activeLiDocs ?? []);
                    this.trackingLoading = false;
                    // Fetch QPR global stats tanpa filter tanggal
                    this.fetchQprGlobalStats();
                },

                getPercentage(val, total) {
                    if (!total) return 0;
                    const p = (val / total) * 100;
                    return parseFloat(p.toFixed(1));
                },

                getJudgement(item) {
                    const notFinalYet = ['draft', 'submitted', 'waiting_foreman', 'revision',
                                         'waiting_supervisor', 'locked', 'ready_for_qc', 'waiting_qc_approval'];
                    if (notFinalYet.includes(item.status)) return null;

                    const j = item.qg_judgement || item.judgement; // handle both cases
                    if (!j) return null;
                    if (j === 'OK' || j === 'NG') return j;
                    try {
                        const obj = typeof j === 'string' ? JSON.parse(j) : j;
                        const values = Object.values(obj);
                        if (values.some(v => typeof v === 'string' && v.toUpperCase().includes('NG'))) return 'NG';
                        return 'OK'; 
                    } catch (e) {
                        return typeof j === 'string' && j.toUpperCase().includes('NG') ? 'NG' : 'OK';
                    }
                },

                async fetchQprGlobalStats() {
                    try {
                        const res = await fetch('/api/qprs?per_page=1000');
                        const data = await res.json();
                        const items = data.data || data || [];
                        this.qprAllItems = items;  // store for pipeline widget
                        this.qpr.total   = items.length;
                        this.qpr.pending = items.filter(i =>
                            i.status === 'Pending Approval' ||
                            i.status === 'GL Approved' ||
                            i.status === 'Progress'
                        ).length;
                        // 'Close'/'Closed' is the terminal state in this system
                        this.qpr.approved = items.filter(i => {
                            const s = (i.status || '').toLowerCase();
                            return s === 'close' || s === 'closed';
                        }).length;
                    } catch(e) {
                        console.warn('QPR global stats error:', e);
                    }
                },

                async fetchLiveTracking() {
                    // Fallback: reload data jika diperlukan (misal setelah update)
                    this.trackingLoading = true;
                    try {
                        const ts = new Date().getTime();
                        const liRes = await fetch(`/api/item-check/summary?per_page=200&_t=${ts}`, { cache: 'no-store' });
                        if (!liRes.ok) throw new Error('API error: ' + liRes.status);
                        const liData = await liRes.json();
                        const listLi = liData.data || liData || [];
                        this.processMonitoring(listLi, []);
                    } catch(e) {
                        // Kalau gagal, biarkan data server-side tetap tampil
                        console.warn('Tracking fetch error (using server data):', e);
                    } finally {
                        this.trackingLoading = false;
                    }
                },

                applyPreset(key) {
                    this.activePreset = key;
                    const now = new Date();
                    const fmt = d => d.toISOString().slice(0, 10);
                    if (key === 'today') {
                        this.dateFrom = this.dateTo = fmt(now);
                    } else if (key === 'week') {
                        const w = new Date(now); w.setDate(now.getDate() - 6);
                        this.dateFrom = fmt(w); this.dateTo = fmt(now);
                    } else if (key === 'month') {
                        this.dateFrom = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
                        this.dateTo   = fmt(now);
                    }
                    if (key !== 'custom') this.fetch();
                },

                shiftDays(offset) {
                    const fmt = d => d.toISOString().slice(0, 10);
                    // If it's a range, we just shift both dates by the offset
                    let from = new Date(this.dateFrom);
                    let to = new Date(this.dateTo);
                    from.setDate(from.getDate() + offset);
                    to.setDate(to.getDate() + offset);
                    
                    this.dateFrom = fmt(from);
                    this.dateTo = fmt(to);
                    this.activePreset = 'custom';
                    this.fetch();
                },

                async fetch() {
                    this.loading = true;
                    try {
                        const [liRes, qprRes] = await Promise.all([
                            fetch(`/api/item-check/summary?from=${this.dateFrom}&to=${this.dateTo}&per_page=200`),
                            fetch(`/api/qprs?from=${this.dateFrom}&to=${this.dateTo}&per_page=200`)
                        ]);
                        const liData  = await liRes.json();
                        const qprData = await qprRes.json();

                        const liItems = liData.data || liData || [];
                        this.li.items    = liItems;
                        this.li.total    = liItems.length;
                        this.li.ok       = liItems.filter(i => this.getJudgement(i) === 'OK').length;
                        this.li.ng       = liItems.filter(i => this.getJudgement(i) === 'NG').length;
                        this.li.finished = liItems.filter(i => i.status === 'finished' || i.status === 'approved').length;


                        // Tambahkan tanggal & judgement ke setiap item untuk tabel
                        this.li.items = liItems.map(i => {
                            return { ...i, judgement: this.getJudgement(i) || 'â€”', tanggal: i.created_at ? new Date(i.created_at).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'}) : 'â€”' };
                        });
                        
                        this.availableParts = [...new Set(this.li.items.map(i => i.part_name).filter(p => p))];
                        if (this.selectedPart !== 'Semua' && !this.availableParts.includes(this.selectedPart)) {
                            this.selectedPart = 'Semua';
                        }

                        const qprItems   = qprData.data || qprData || [];
                        // qpr.total, pending & approved dihitung dari global stats (fetchQprGlobalStats)
                        // supaya QPR dari bulan lalu tetap terhitung

                        // Calculate Overdue QPRs
                        let overdueList = [];
                        let today = new Date();
                        today.setHours(0,0,0,0);
                        
                        qprItems.forEach(d => {
                            if (d.status !== 'approved' && d.status !== 'closed' && d.target_selesai) {
                                let targetDate = new Date(d.target_selesai);
                                targetDate.setHours(0,0,0,0);
                                let diffTime = targetDate.getTime() - today.getTime();
                                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                
                                // Tampilkan jika terlambat, hari ini, atau sisa <= 3 hari
                                if (diffDays <= 3) {
                                    let urgencyText = '';
                                    let urgencyClass = '';
                                    
                                    if (diffDays < 0) {
                                        urgencyText = `Terlambat ${Math.abs(diffDays)} Hari`;
                                        urgencyClass = 'bg-[#FFF1F2] text-[#BE123C] border border-[#FFE4E6]';
                                    } else if (diffDays === 0) {
                                        urgencyText = 'Hari Ini!';
                                        urgencyClass = 'bg-[#E11D48] text-white shadow-sm shadow-[#E11D48]/30';
                                    } else {
                                        urgencyText = `Sisa ${diffDays} Hari`;
                                        urgencyClass = 'bg-[#FFFBEB] text-[#D97706] border border-[#FEF3C7]';
                                    }
                                    
                                    overdueList.push({ ...d, diffDays, urgencyText, urgencyClass });
                                }
                            }
                        });
                        
                        overdueList.sort((a, b) => a.diffDays - b.diffDays);
                        this.overdueQprs = overdueList;
                        
                        // processMonitoring dipindahkan ke fetchLiveTracking() agar independen
                    } catch(e) {
                        console.error('Dashboard fetch error:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                processMonitoring(listLi, listQpr) {
                    let monitor = [];
                    listLi.forEach(d => {
                        let step = 1;
                        let monitorLabel = 'Inspeksi Berjalan';
                        if (d.status === 'draft' || d.status === 'in_progress' || d.status === 'submitted' || d.status === 'waiting_qc_approval') { 
                            step = 1; 
                            monitorLabel = 'Pengecekan Aktual'; 
                            if (d.status === 'waiting_qc_approval') { step = 2; monitorLabel = 'Review Pengecekan'; }
                            if (d.gl_signed) { step = 3; monitorLabel = 'Pengesahan Inspeksi'; }
                            if (d.foreman_signed) { step = 4; monitorLabel = 'Selesai'; }
                        }
                        else if (d.status === 'waiting_gl') { step = 2; monitorLabel = 'Menunggu TTD GL'; }
                        else if (d.status === 'waiting_foreman') { step = 3; monitorLabel = 'Review Foreman'; }
                        else if (d.status === 'finished' || d.status === 'locked' || d.status === 'approved') { step = 4; monitorLabel = 'Selesai'; }
                        
                        if (d.status !== 'finished' && d.status !== 'locked') {
                            monitor.push({
                                id: d.id, type: 'Item Check', no_form: d.no_form || ('IC-' + String(d.id).padStart(5, '0')), no_job: d.job_no || '-', line: d.line || '-', info: d.part_name || '-',
                                step: step, statusLabel: monitorLabel, url: `/item-check/${d.id}/form`, date: d.updated_at || d.created_at, created_at: d.created_at
                            });
                        }
                    });

                    listQpr.forEach(d => {
                        let step = 1; let monitorLabel = 'Proses Lanjutan';
                        let s = (d.status || '').toLowerCase();
                        if (['draft', 'open', 'revision'].includes(s)) { step = 1; monitorLabel = 'Investigasi Temuan'; }
                        else if (['pending approval', 'gl approved'].includes(s)) { step = 2; monitorLabel = 'Pengecekan Awal (GL)'; }
                        else if (s.includes('action') || s.includes('progress') || s.includes('a3')) { step = 3; monitorLabel = 'Tindakan Seksi Terkait'; }
                        else if (s.includes('verif 1')) { step = 4; monitorLabel = 'Verifikasi 1'; }
                        else if (s.includes('verif 2')) { step = 5; monitorLabel = 'Verifikasi 2'; }
                        else if (s.includes('verif 3')) { step = 6; monitorLabel = 'Verifikasi 3'; }
                        else if (s.includes('close')) { step = 6; monitorLabel = 'Selesai'; }
                        
                        if (!s.includes('close') && s !== 'approved' && s !== 'finished') {
                            monitor.push({
                                id: d.id, type: 'QPR', no_form: d.no_qpr || ('QPR-' + String(d.id).padStart(5, '0')), no_job: d.no_job || '-', line: d.line || '-', info: d.nama_part || '-',
                                step: step, statusLabel: monitorLabel, url: `/qpr/${d.id}/edit`, date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });
                    monitor.sort((a, b) => b.date - a.date);
                    this.monitoringList = monitor.slice(0, 10);
                    
                    // Reactive update to the currently viewed tracked object
                    if (this.tracked && this.tracked.id) {
                        const updatedItem = this.monitoringList.find(i => i.id === this.tracked.id && i.type === this.tracked.type);
                        if (updatedItem) {
                            this.tracked = updatedItem;
                        }
                    }
                },


            };
        }

        // â•â• CALENDAR NOTES â•â•
        function calendarNotes() {
            return {
                currentYear:  new Date().getFullYear(),
                currentMonth: new Date().getMonth(),
                selectedDate: null,
                currentNote:  '',
                calendarDays: [],
                showNoteModal: false,
                monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],

                get upcomingNotes() {
                    const prefix = `${this.currentYear}-${String(this.currentMonth+1).padStart(2,'0')}`;
                    const all = Object.entries(JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}'));
                    return all
                        .filter(([d,t]) => d.startsWith(prefix) && t.trim())
                        .sort(([a],[b]) => a.localeCompare(b))
                        .map(([d,t]) => ({
                            date: d, text: t,
                            day:  parseInt(d.slice(8)),
                            dateLabel: new Date(d+'T00:00:00').toLocaleDateString('id-ID',{day:'numeric',month:'long'})
                        }));
                },

                initCalendar() {
                    const today = new Date().toISOString().slice(0,10);
                    this.selectedDate = today;
                    this.loadNote();
                    this.buildCalendar();
                },

                buildCalendar() {
                    const first = new Date(this.currentYear, this.currentMonth, 1);
                    // Monday-based: Mon=0 ... Sun=6
                    let startDow = (first.getDay() + 6) % 7;
                    const daysInMonth = new Date(this.currentYear, this.currentMonth+1, 0).getDate();
                    const cells = [];
                    for (let i = 0; i < startDow; i++) cells.push({ key:'e'+i, date:null, label:'' });
                    for (let d = 1; d <= daysInMonth; d++) {
                        const dateStr = `${this.currentYear}-${String(this.currentMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                        cells.push({ key: dateStr, date: dateStr, label: String(d) });
                    }
                    this.calendarDays = cells;
                },

                prevMonth() {
                    if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; }
                    else this.currentMonth--;
                    this.buildCalendar();
                },

                nextMonth() {
                    if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; }
                    else this.currentMonth++;
                    this.buildCalendar();
                },

                selectDay(date) {
                    this.selectedDate = date;
                    this.loadNote();
                    this.showNoteModal = true;
                },

                closeModal() {
                    this.showNoteModal = false;
                },

                loadNote() {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    this.currentNote = notes[this.selectedDate] || '';
                },

                saveNote() {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    if (this.currentNote.trim()) {
                        notes[this.selectedDate] = this.currentNote;
                    } else {
                        delete notes[this.selectedDate];
                    }
                    localStorage.setItem('qa_calendar_notes', JSON.stringify(notes));
                },

                deleteNote() {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    delete notes[this.selectedDate];
                    localStorage.setItem('qa_calendar_notes', JSON.stringify(notes));
                    this.currentNote = '';
                },

                getNoteText(date) {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    return notes[date] || '';
                },

                hasNote(date) {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    return !!(notes[date] && notes[date].trim());
                },

                isToday(date) {
                    return date === new Date().toISOString().slice(0,10);
                },

                formatDisplayDate(date) {
                    if (!date) return '';
                    return new Date(date+'T00:00:00').toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
                }
            };
        }

    </script>
    @endpush
</x-app-layout>

