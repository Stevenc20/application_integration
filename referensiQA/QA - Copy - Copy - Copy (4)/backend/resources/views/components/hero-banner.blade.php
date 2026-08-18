@php
    $hour = now()->setTimezone('Asia/Jakarta')->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $emoji    = $hour < 12 ? '👋' : ($hour < 17 ? '☀️' : '🌙');
    $role     = auth()->user()->role ?? 'Guest';
@endphp

<div class="relative rounded-[1.5rem] overflow-hidden w-full h-full bg-white border border-slate-100 flex flex-col"
     style="box-shadow: 0 8px 30px -8px rgba(0,0,0,0.08); min-height: 260px;">

    {{-- Subtle gradient blobs --}}
    <div class="absolute -top-14 -left-14 w-56 h-56 rounded-full pointer-events-none z-0"
         style="background: radial-gradient(circle, rgba(254,202,202,0.55) 0%, transparent 65%);"></div>
    <div class="absolute bottom-0 left-1/3 w-40 h-40 rounded-full pointer-events-none z-0"
         style="background: radial-gradient(circle, rgba(254,226,226,0.4) 0%, transparent 70%);"></div>

    {{-- Building image — right side, inside card --}}
    <div class="absolute right-0 bottom-0 top-0 pointer-events-none select-none z-0"
         style="width: 50%;">
        {{-- Fade gradient agar blend ke kiri --}}
        <div class="absolute inset-0 z-10"
             style="background: linear-gradient(90deg, #ffffff 0%, rgba(255,255,255,0.5) 30%, rgba(255,255,255,0) 60%);"></div>
        <img src="{{ asset('GedungIPPI.png') }}"
             alt="Gedung PT. IPPI"
             class="w-full h-full object-contain object-right-bottom"
             style="filter: drop-shadow(0 8px 24px rgba(0,0,0,0.08));">
    </div>

    {{-- Content area --}}
    <div class="relative z-10 flex flex-col justify-between h-full px-7 py-6" style="max-width: 62%;">

        {{-- Top: Greeting --}}
        <div>
            {{-- Role badge --}}
            <div class="inline-flex items-center gap-1.5 mb-3 px-2.5 py-1 rounded-full bg-red-50 border border-red-100">
                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                <span class="text-[9px] font-black text-red-600 uppercase tracking-widest">{{ $role }}</span>
            </div>

            <h1 class="text-[22px] font-black text-slate-900 leading-tight mb-1" style="font-family:'Plus Jakarta Sans',sans-serif;">
                {{ $greeting }}, {{ auth()->user()->name }} <span>{{ $emoji }}</span>
            </h1>
            <p class="text-slate-500 text-xs leading-relaxed max-w-[300px]">
                Pantau kualitas produksi dan kelola laporan inspeksi hari ini.
            </p>
        </div>

        {{-- Middle: Real-time Clock + Date --}}
        <div class="flex items-center gap-4 my-4"
             x-data="{ time: '' }"
             x-init="
                const pad = n => String(n).padStart(2,'0');
                const tick = () => {
                    const now = new Date();
                    time = pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds());
                };
                tick(); setInterval(tick, 1000);
             ">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Jam</p>
                <p class="text-xl font-black text-slate-800 tabular-nums leading-none" x-text="time">--:--:--</p>
            </div>
            <div class="w-px h-8 bg-slate-200"></div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Tanggal</p>
                <p class="text-sm font-black text-slate-800 leading-none">{{ now()->setTimezone('Asia/Jakarta')->isoFormat('D MMMM YYYY') }}</p>
            </div>
        </div>

        {{-- Bottom: CTAs berdasarkan role --}}
        <div class="flex items-center gap-2 flex-wrap">
            @if(in_array($role, ['Admin', 'Leader', 'Group Leader', 'Foreman']))
            <a href="{{ url('/li/create?new=1') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-full bg-red-600 text-white hover:bg-red-700 transition-all active:scale-95"
               style="box-shadow: 0 4px 14px rgba(220,38,38,0.3);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Mulai Inspeksi
            </a>
            @endif

            @if(in_array($role, ['Supervisor', 'Admin']))
            <a href="{{ url('/li/rekap') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-full bg-slate-800 text-white hover:bg-slate-900 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Rekap Bulanan
            </a>

            @endif

            @if(in_array($role, ['Leader', 'Group Leader', 'Foreman', 'Operator', 'QC']))
            <a href="{{ url('/li') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition-all active:scale-95 border border-slate-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Lihat LI List
            </a>
            @endif
        </div>

    </div>
</div>
