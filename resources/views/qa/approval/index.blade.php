@extends('layouts.app')
@section('content')

    <style>
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8); }
        .task-card { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; }
        .task-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px -10px rgba(0,0,0,0.1); border-color: #ef4444; }
        .status-badge { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 99px; }
        .revision-glow { animation: revisionGlow 2s infinite; }
        @keyframes revisionGlow { 0%, 100% { box-shadow: 0 0 0px rgba(245, 158, 11, 0); } 50% { box-shadow: 0 0 15px rgba(245, 158, 11, 0.4); } }
        [x-cloak] { display: none !important; }
    </style>

    <div x-data="approvalPage({ 
            apiUrl: '{{ url('/') }}', 
            userId: {{ auth()->user()->id }},
            userName: '{{ auth()->user()->name }}',
            userRole: '{{ auth()->user()->role }}'
         })" 
         @signature-confirmed.window="pendingSig = $event.detail; showPad = false"
         @close-pad.window="showPad = false"
         class="max-w-6xl mx-auto px-4 pb-20" x-cloak>
        
        {{-- Header Premium --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 pt-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-8 h-1 bg-red-600 rounded-full"></span>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-[3px]">Pusat Kendali Tugas</p>
                </div>
                <h1 class="text-4xl font-black text-slate-800 tracking-tight">Approval Hub</h1>
                <p class="text-slate-500 font-medium mt-1 italic">Halo <span class="text-red-600 font-bold" x-text="userName"></span>, silakan tinjau tugas Anda di bawah ini.</p>
            </div>
            <div class="flex gap-3">
                <button @click="handleRefresh()" :disabled="refreshing || loading" class="px-6 py-3 glass-card rounded-2xl text-slate-700 text-sm font-bold flex items-center gap-3 hover:bg-slate-50 transition-all active:scale-95 disabled:opacity-50 shadow-sm">
                    <svg class="w-4 h-4" :class="refreshing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh Data
                </button>
            </div>
        </div>

        {{-- Stats Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <div class="glass-card rounded-3xl p-6 border-l-4 border-red-600 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total Pending</p>
                <p class="text-3xl font-black text-slate-800" x-text="totalPending"></p>
            </div>
            <div class="glass-card rounded-3xl p-6 border-l-4 border-amber-500 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Butuh Revisi</p>
                <p class="text-3xl font-black text-amber-600" x-text="liRevisions.length"></p>
            </div>
            <div class="glass-card rounded-3xl p-6 border-l-4 border-blue-600 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Item Check LI</p>
                <p class="text-3xl font-black text-blue-700" x-text="liNeedItemCheck.length"></p>
            </div>
            <div class="glass-card rounded-3xl p-6 border-l-4 border-emerald-500 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">QPR Menunggu TTD</p>
                <p class="text-3xl font-black text-emerald-700" x-text="pending.length"></p>
            </div>
        </div>

        {{-- TASK SECTIONS --}}
        <div class="space-y-16">
            
            {{-- 1. SECTION: REVISI (URGENT) --}}
            <template x-if="liRevisions.length > 0">
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-amber-100 text-amber-600 rounded-lg">⚠️</div>
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-wide">Perlu Perbaikan (Revisi)</h2>
                        <span class="bg-amber-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full" x-text="liRevisions.length"></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <template x-for="item in liRevisions" :key="item.id">
                            <div @click="openLiModal(item)" class="task-card glass-card rounded-3xl p-6 relative overflow-hidden bg-amber-50/30 border-amber-200 revision-glow">
                                <div class="flex justify-between items-start mb-4">
                                    <span class="status-badge bg-amber-100 text-amber-700">REVISI</span>
                                    <span class="text-[10px] font-mono text-slate-400 font-bold" x-text="item.no_form"></span>
                                </div>
                                <h3 class="text-lg font-black text-slate-800 mb-1 leading-tight" x-text="item.part_name"></h3>
                                <p class="text-xs font-bold text-slate-500 mb-4" x-text="item.job_no"></p>
                                <div class="bg-amber-100/50 p-3 rounded-xl">
                                    <p class="text-[9px] font-black text-amber-700 uppercase mb-1">Alasan Revisi:</p>
                                    <p class="text-[11px] font-bold text-amber-800 line-clamp-2" x-text="item.catatan_revisi || 'Cek detail...'"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- 2. SECTION: PENDING TTD (Azriel/Susang/Dedy as Checker/Approver) --}}
            <template x-if="liAllNeedTtd.length > 0">
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-red-100 text-red-600 rounded-lg">✍️</div>
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-wide">Menunggu TTD (Checked / Approved)</h2>
                        <span class="bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full" x-text="liAllNeedTtd.length"></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <template x-for="item in liAllNeedTtd" :key="item.id">
                            <div @click="openLiModal(item)" class="task-card glass-card rounded-3xl p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <span class="status-badge bg-blue-100 text-blue-700" x-text="item.ttdType || 'NEW TASK'"></span>
                                    <span class="text-[10px] font-mono text-slate-400 font-bold" x-text="item.no_form"></span>
                                </div>
                                <h3 class="text-lg font-black text-slate-800 mb-1 leading-tight" x-text="item.part_name"></h3>
                                <p class="text-xs font-bold text-slate-500 mb-6" x-text="item.job_no"></p>
                                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-[10px]">👤</div>
                                        <span class="text-[10px] font-bold text-slate-500" x-text="item.qg_name"></span>
                                    </div>
                                    <span class="text-[10px] font-black text-red-600">✍️ BUTUH TTD</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- 3. SECTION: ITEM CHECK (Azriel/Dedy as Inspector) --}}
            <template x-if="liNeedItemCheck.length > 0">
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">📏</div>
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-wide">Pengecekan Item Dimensi</h2>
                        <span class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full" x-text="liNeedItemCheck.length"></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <template x-for="item in liNeedItemCheck" :key="item.id">
                            <div @click="openLiModal(item)" class="task-card glass-card rounded-3xl p-6 bg-blue-50/20 border-blue-100">
                                <div class="flex justify-between items-start mb-4">
                                    <span class="status-badge bg-blue-600 text-white">INPUT DATA</span>
                                    <span class="text-[10px] font-mono text-slate-400 font-bold" x-text="item.no_form"></span>
                                </div>
                                <h3 class="text-lg font-black text-slate-800 mb-1 leading-tight" x-text="item.part_name"></h3>
                                <p class="text-xs font-bold text-slate-500 mb-6" x-text="item.job_no"></p>
                                <button class="w-full py-2 bg-blue-600 text-white rounded-xl text-[10px] font-black">Mulai Pengecekan</button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- 4. SECTION: QPR PENDING APPROVAL (GL / Foreman OR Seksi Terkait) --}}
            <template x-if="pending.length > 0">
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg">📋</div>
                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-wide">QPR Menunggu TTD Anda</h2>
                        <span class="bg-emerald-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full" x-text="pending.length"></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <template x-for="item in pending" :key="item.qpr?.id">
                            <div @click="openQprModal(item)" class="task-card glass-card rounded-3xl p-6"
                                 :class="item.type === 'seksi' ? 'bg-blue-50/10 border-blue-100' : 'bg-emerald-50/10 border-emerald-100'">
                                <div class="flex justify-between items-start mb-4">
                                    <span class="status-badge"
                                          :class="item.type === 'seksi' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                                          x-text="item.type === 'seksi' ? 'SEKSI TERKAIT' : 'GL / FOREMAN'"></span>
                                    <span class="text-[10px] font-mono text-slate-400 font-bold" x-text="item.qpr?.no_qpr || 'QPR BARU'"></span>
                                </div>
                                <h3 class="text-lg font-black text-slate-800 mb-1 leading-tight" x-text="item.qpr?.nama_part || item.qpr?.part_name || 'N/A'"></h3>
                                <p class="text-xs font-bold text-slate-500 mb-4" x-text="item.qpr?.no_job || item.qpr?.job_no || 'N/A'"></p>

                                <div class="bg-slate-50 rounded-xl px-3 py-2 mb-4">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Seksi / Role</p>
                                    <p class="text-[11px] font-black" 
                                       :class="item.type === 'seksi' ? 'text-blue-700' : 'text-emerald-700'"
                                       x-text="item.role"></p>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400" x-text="item.qpr?.defect_keterangan?.substring(0,40) + '...'"></span>
                                    <span class="text-[10px] font-black flex items-center gap-1"
                                          :class="item.type === 'seksi' ? 'text-blue-600' : 'text-emerald-600'">
                                        ✍️ TTD
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="!loading && totalPending === 0">
                <div class="py-20 text-center">
                    <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-6xl shadow-inner">☕</div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">Semua Beres, Pak!</h3>
                    <p class="text-slate-500 font-medium">Belum ada tugas baru yang masuk untuk saat ini.</p>
                </div>
            </template>

            {{-- Loading State --}}
            <template x-if="loading">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <template x-for="i in 3">
                        <div class="glass-card rounded-3xl p-6 h-48 animate-pulse">
                            <div class="flex justify-between mb-4">
                                <div class="w-20 h-4 bg-slate-100 rounded-full"></div>
                                <div class="w-12 h-4 bg-slate-100 rounded-full"></div>
                            </div>
                            <div class="w-3/4 h-6 bg-slate-100 rounded-lg mb-2"></div>
                            <div class="w-1/2 h-4 bg-slate-100 rounded-lg"></div>
                        </div>
                    </template>
                </div>
            </template>

        </div>

        {{-- Include Modals & Signature Pad (Shared with original logic) --}}
        @include('qa.approval.partials.modals')

    </div>
@endsection
