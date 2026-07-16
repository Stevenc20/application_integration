@extends('layouts.supervisor')

@section('title', 'Breaktime Management')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
<style>
    .bt-page { font-family: 'Inter', system-ui, sans-serif; }
    .bt-header { background: #991B1B; border: 1px solid #E5C7C7; }
    .bt-card {
        border-radius: 1rem;
        border: 1px solid #E5C7C7;
        background: #FFF8F8;
    }
    .bt-table th { background: #FFF8F8; border-bottom: 1px solid #E5C7C7; }
    .bt-table td { border-bottom: 1px solid #E5C7C7; }
    .bt-table tbody tr:hover { background: #FFF8F8; }
</style>

<div class="bt-page space-y-6">
    {{-- Header Card --}}
    <div class="bt-header rounded-2xl shadow-md px-6 py-5 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <p class="text-xs text-red-100/80 font-medium mb-1">Factory Timeline Parameter Management</p>
            <h1 class="text-xl font-black tracking-tight uppercase">Breaktime Parameter Dashboard</h1>
            <p class="text-xs text-white/80 mt-1">Regenerate PPC &amp; LKH timeline automatically based on constraint adjustments</p>
        </div>
        <button type="button" id="bt-btn-create" class="px-5 py-2.5 bg-white text-[#991B1B] rounded-xl text-xs font-black shadow-md hover:bg-[#FFF8F8] transition duration-200 uppercase tracking-wider flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Tambah Parameter
        </button>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">{{ session('success') }}</div>
    @endif

    @if(empty($useMaster))
        <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
            Tabel <code>master_break_times</code> belum ada. Jalankan migrasi + seeder.
        </div>
    @endif

    {{-- Shift Filter Tabs --}}
    <div class="flex items-center gap-2">
        <a href="{{ route('supervisor.breaktime.index', ['shift' => 'pagi']) }}"
           class="px-6 py-2.5 rounded-xl text-xs font-black transition-all {{ $selectedShift === 'pagi' ? 'bg-primary-red text-white shadow-lg shadow-red-200' : 'bg-white text-slate-500 hover:bg-rose-50 hover:text-rose-600 border border-slate-200' }}">
            SHIFT PAGI
        </a>
        <a href="{{ route('supervisor.breaktime.index', ['shift' => 'malam']) }}"
           class="px-6 py-2.5 rounded-xl text-xs font-black transition-all {{ $selectedShift === 'malam' ? 'bg-primary-red text-white shadow-lg shadow-red-200' : 'bg-white text-slate-500 hover:bg-rose-50 hover:text-rose-600 border border-slate-200' }}">
            SHIFT MALAM
        </a>
    </div>

    {{-- Parameters by Break Type --}}
    @php
        $currentBreaks = $selectedShift === 'malam' ? $malamBreaks : $pagiBreaks;
        $grouped = $currentBreaks->groupBy(fn($b) => strtolower($b->type));
        $istirahatBreaks = $grouped->get('istirahat', collect());
        $cinkorakBreaks = $grouped->get('cinkorak', collect());
    @endphp

    @if(!empty($useMaster))
        {{-- ISTIRAHAT --}}
        @if($istirahatBreaks->isNotEmpty())
        <div class="bg-white rounded-2xl border border-blue-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-blue-50 border-b border-blue-200 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xs font-black text-blue-800 uppercase tracking-wider">ISTIRAHAT</h3>
                <span class="ml-auto text-[10px] font-bold text-blue-500">{{ $istirahatBreaks->count() }} parameter</span>
            </div>
            <div class="overflow-x-auto">
                <table class="bt-table min-w-full text-sm">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">Hari</th>
                            <th class="px-4 py-3 text-center">Start</th>
                            <th class="px-4 py-3 text-center">Finish</th>
                            <th class="px-4 py-3 text-center">Durasi</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bt-break-rows divide-y divide-blue-100">
                        @foreach($istirahatBreaks as $break)
                        <tr class="transition hover:bg-blue-50/50" data-id="{{ $break->id }}">
                                <td class="px-4 py-3 font-bold text-gray-900">{{ $break->label }}</td>
                                <td class="px-4 py-3 capitalize text-gray-700 font-semibold text-sm">{{ $break->hari }}</td>
                                <td class="px-4 py-3 text-center font-mono text-xs font-bold text-slate-800">{{ \Carbon\Carbon::parse($break->waktu_mulai)->format('H:i') }}</td>
                                <td class="px-4 py-3 text-center font-mono text-xs font-bold text-slate-800">{{ \Carbon\Carbon::parse($break->waktu_selesai)->format('H:i') }}</td>
                                <td class="px-4 py-3 text-center font-bold text-slate-600 text-xs">{{ $break->durationMinutes() }} menit</td>
                                <td class="px-4 py-3 text-center">
                                    @if($break->is_active)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 border border-emerald-200">Aktif</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-zinc-100 text-zinc-500 border border-zinc-200">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" class="bt-edit h-7 w-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 inline-flex items-center justify-center transition" title="Edit Parameter" data-row="{{ json_encode(['id' => $break->id, 'label' => $break->label, 'hari' => $break->hari, 'shift' => $break->shift, 'waktu_mulai' => \Carbon\Carbon::parse($break->waktu_mulai)->format('H:i'), 'waktu_selesai' => \Carbon\Carbon::parse($break->waktu_selesai)->format('H:i'), 'type' => $break->type, 'is_active' => (bool) $break->is_active]) }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button" class="bt-toggle h-7 w-7 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 inline-flex items-center justify-center transition" title="Ubah Status" data-id="{{ $break->id }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                                        </button>
                                        <button type="button" class="bt-delete h-7 w-7 rounded-lg bg-red-50 hover:bg-red-100 text-[#991B1B] inline-flex items-center justify-center transition" title="Hapus Parameter" data-id="{{ $break->id }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        {{-- CINGKORAK --}}
        @if($cinkorakBreaks->isNotEmpty())
        <div class="bg-white rounded-2xl border border-violet-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-violet-50 border-b border-violet-200 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <h3 class="text-xs font-black text-violet-800 uppercase tracking-wider">CINGKORAK</h3>
                <span class="ml-auto text-[10px] font-bold text-violet-500">{{ $cinkorakBreaks->count() }} parameter</span>
            </div>
            <div class="overflow-x-auto">
                <table class="bt-table min-w-full text-sm">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">Hari</th>
                            <th class="px-4 py-3 text-center">Start</th>
                            <th class="px-4 py-3 text-center">Finish</th>
                            <th class="px-4 py-3 text-center">Durasi</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-violet-100">
                        @foreach($cinkorakBreaks as $break)
                        <tr class="transition hover:bg-violet-50/50" data-id="{{ $break->id }}">
                            <td class="px-4 py-3 font-bold text-gray-900">{{ $break->label }}</td>
                            <td class="px-4 py-3 capitalize text-gray-700 font-semibold text-sm">{{ $break->hari }}</td>
                            <td class="px-4 py-3 text-center font-mono text-xs font-bold text-slate-800">{{ \Carbon\Carbon::parse($break->waktu_mulai)->format('H:i') }}</td>
                            <td class="px-4 py-3 text-center font-mono text-xs font-bold text-slate-800">{{ \Carbon\Carbon::parse($break->waktu_selesai)->format('H:i') }}</td>
                            <td class="px-4 py-3 text-center font-bold text-slate-600 text-xs">{{ $break->durationMinutes() }} menit</td>
                            <td class="px-4 py-3 text-center">
                                @if($break->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 border border-emerald-200">Aktif</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-zinc-100 text-zinc-500 border border-zinc-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" class="bt-edit h-7 w-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 inline-flex items-center justify-center transition" title="Edit Parameter" data-row="{{ json_encode(['id' => $break->id, 'label' => $break->label, 'hari' => $break->hari, 'shift' => $break->shift, 'waktu_mulai' => \Carbon\Carbon::parse($break->waktu_mulai)->format('H:i'), 'waktu_selesai' => \Carbon\Carbon::parse($break->waktu_selesai)->format('H:i'), 'type' => $break->type, 'is_active' => (bool) $break->is_active]) }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" class="bt-toggle h-7 w-7 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 inline-flex items-center justify-center transition" title="Ubah Status" data-id="{{ $break->id }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                                    </button>
                                    <button type="button" class="bt-delete h-7 w-7 rounded-lg bg-red-50 hover:bg-red-100 text-[#991B1B] inline-flex items-center justify-center transition" title="Hapus Parameter" data-id="{{ $break->id }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($currentBreaks->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center">
            <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-bold text-slate-400">Belum ada parameter untuk {{ $selectedShift === 'malam' ? 'Shift Malam' : 'Shift Pagi' }}</p>
            <p class="text-xs text-slate-300 mt-1">Klik "Tambah Parameter" untuk menambahkan break baru.</p>
        </div>
        @endif
    @else
        {{-- Legacy fallback --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="bt-table min-w-full text-sm">
                <thead>
                    <tr class="text-[10px] font-black uppercase tracking-wider text-gray-600">
                        <th class="px-4 py-3.5 text-left">Nama</th>
                        <th class="px-4 py-3.5 text-left">Hari</th>
                        <th class="px-4 py-3.5 text-left">Shift</th>
                        <th class="px-4 py-3.5 text-center">Start</th>
                        <th class="px-4 py-3.5 text-center">Finish</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($semua_break as $break)
                    <tr>
                        <td class="px-4 py-3.5 font-bold">{{ $break->nama_istirahat }}</td>
                        <td class="px-4 py-3.5">{{ $break->hari ?? 'semua' }}</td>
                        <td class="px-4 py-3.5">{{ $break->shift ?? '-' }}</td>
                        <td class="px-4 py-3.5 text-center font-mono font-semibold">{{ \Carbon\Carbon::parse($break->waktu_mulai)->format('H:i') }}</td>
                        <td class="px-4 py-3.5 text-center font-mono font-semibold">{{ \Carbon\Carbon::parse($break->waktu_selesai)->format('H:i') }}</td>
                        <td class="px-4 py-3.5 text-center"><span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-700">Aktif</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada parameter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- System Notes Card --}}
    <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 flex gap-3 text-xs text-blue-900">
        <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <h4 class="font-bold uppercase tracking-wider">Integrasi Engine PPC &amp; LKH</h4>
            <p class="mt-1 leading-relaxed font-medium">
                Setiap kali parameter breaktime disimpan atau dimodifikasi, sistem secara otomatis me-regenerate total timeline job harian.
                Waktu akhir (finish time) setiap job akan digeser otomatis, memastikan sinkronisasi penuh antara rencana timeline dan realisasi lantai produksi.
            </p>
        </div>
    </div>
</div>

{{-- Premium Form Modal (Create / Edit) --}}
<div id="bt-form-modal" class="fixed inset-0 z-[9998] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" data-bt-close></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-[#E7C9C9] overflow-hidden animate-in zoom-in-95 duration-150">
            <div class="px-6 py-4 border-b border-[#E7C9C9] bg-[#FDF4F4] flex justify-between items-center">
                <h2 id="bt-form-title" class="font-black text-gray-900 uppercase tracking-wider text-sm">Tambah Breaktime</h2>
                <button type="button" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 inline-flex items-center justify-center" data-bt-close>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form id="bt-form" class="p-6 space-y-4">
                <input type="hidden" name="id" id="bt-id">
                
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Nama Parameter</label>
                    <input type="text" name="label" required class="w-full rounded-xl border-gray-300 text-sm focus:border-[#9F1D1D] focus:ring focus:ring-red-100" placeholder="Contoh: ISTIRAHAT SIANG">
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Hari Kerja</label>
                        <select name="hari" required class="w-full rounded-xl border-gray-300 text-sm focus:border-[#9F1D1D] focus:ring focus:ring-red-100">
                            <option value="semua">Semua Hari</option>
                            <option value="senin">Senin</option>
                            <option value="selasa">Selasa</option>
                            <option value="rabu">Rabu</option>
                            <option value="kamis">Kamis</option>
                            <option value="jumat">Jumat</option>
                            <option value="sabtu">Sabtu</option>
                            <option value="minggu">Minggu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Shift Spesifik</label>
                        <select name="shift" class="w-full rounded-xl border-gray-300 text-sm focus:border-[#9F1D1D] focus:ring focus:ring-red-100">
                            <option value="">Semua Shift</option>
                            <option value="Shift Pagi">Shift Pagi</option>
                            <option value="Shift Malam">Shift Malam</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Start Time</label>
                        <input type="time" name="waktu_mulai" required class="w-full rounded-xl border-gray-300 text-sm focus:border-[#9F1D1D] focus:ring focus:ring-red-100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Finish Time</label>
                        <input type="time" name="waktu_selesai" required class="w-full rounded-xl border-gray-300 text-sm focus:border-[#9F1D1D] focus:ring focus:ring-red-100">
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Kategori Break</label>
                    <select name="type" required class="w-full rounded-xl border-gray-300 text-sm focus:border-[#9F1D1D] focus:ring focus:ring-red-100">
                        <option value="istirahat">break / istirahat</option>
                        <option value="cinkorak">cinkorak</option>
                    </select>
                </div>
                
                <label class="inline-flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-[#9F1D1D] focus:ring-[#9F1D1D]"> 
                    Parameter Aktif
                </label>

                {{-- Live Preview Impact --}}
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 flex gap-2 text-[11px] text-amber-900">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div class="flex-1">
                        <h5 class="font-black uppercase tracking-wider flex items-center justify-between">
                            <span>Live Preview Impact:</span>
                            <span id="bt-live-impact-status" class="text-[9px] bg-amber-200 text-amber-950 px-2 py-0.5 rounded-full font-black animate-pulse">UP TO DATE</span>
                        </h5>
                        <div id="bt-live-impact-content" class="mt-1 space-y-1 font-semibold leading-relaxed">
                            Tindakan ini membatasi timeline harian pada shift dan lini terkait (<span class="font-black text-amber-950">PRESS A, PRESS B, PRESS C, PRESS D</span>). Seluruh start/finish job akan digeser otomatis.
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-2 border-t border-[#F3E6E6]">
                    <button type="submit" id="bt-form-save" class="flex-1 py-2.5 bg-[#9F1D1D] hover:bg-[#7F1A1A] text-white text-xs font-black rounded-xl shadow-md transition duration-200 uppercase tracking-wider flex items-center justify-center gap-2">
                        <span id="bt-save-spinner" class="hidden w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span id="bt-save-text">Simpan Parameter</span>
                    </button>
                    <button type="button" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition duration-200" data-bt-close>Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Custom Premium Alert/Confirm Dialog --}}
<div id="bt-confirm-modal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" id="bt-confirm-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-2xl border border-red-100 shadow-2xl p-6 text-center animate-in zoom-in-95 duration-150">
            <div class="mx-auto w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-[#9F1D1D] mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider mb-2">Konfirmasi Tindakan</h3>
            <p class="text-xs text-gray-500 font-semibold leading-relaxed mb-6" id="bt-confirm-message">Apakah Anda yakin ingin melakukan tindakan ini?</p>
            <div class="flex gap-3 justify-center">
                <button type="button" id="bt-confirm-yes" class="px-5 py-2.5 bg-[#9F1D1D] hover:bg-[#7F1A1A] text-white text-xs font-black rounded-xl shadow-md transition-colors flex-1 uppercase tracking-wider">Ya, Lanjutkan</button>
                <button type="button" id="bt-confirm-no" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-black rounded-xl transition-colors flex-1">Batal</button>
            </div>
        </div>
    </div>
</div>

@include('components.ui.toast')

@if(!empty($useMaster))
@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const base = @json(url('/supervisor/api/breaktime-parameters'));
    const modal = document.getElementById('bt-form-modal');
    const form = document.getElementById('bt-form');
    
    // In-memory list of existing breaks for client-side overlap checks
    const allBreaks = [];
    document.querySelectorAll('.bt-break-rows tr[data-id]').forEach(tr => {
        const editBtn = tr.querySelector('.bt-edit');
        if (editBtn && editBtn.dataset.row) {
            allBreaks.push(JSON.parse(editBtn.dataset.row));
        }
    });

    function headers() {
        return { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' };
    }

    function confirmAction(message, onYes) {
        const cModal = document.getElementById('bt-confirm-modal');
        document.getElementById('bt-confirm-message').textContent = message;
        cModal.classList.remove('hidden');
        
        const yesBtn = document.getElementById('bt-confirm-yes');
        const noBtn = document.getElementById('bt-confirm-no');
        const backdrop = document.getElementById('bt-confirm-backdrop');
        
        const cleanup = () => {
            cModal.classList.add('hidden');
            yesBtn.removeEventListener('click', handleYes);
            noBtn.removeEventListener('click', handleNo);
            backdrop.removeEventListener('click', handleNo);
        };
        
        function handleYes() {
            cleanup();
            onYes();
        }
        function handleNo() {
            cleanup();
        }
        
        yesBtn.addEventListener('click', handleYes);
        noBtn.addEventListener('click', handleNo);
        backdrop.addEventListener('click', handleNo);
    }

    function openModal(row) {
        document.getElementById('bt-form-title').textContent = row ? 'Edit Breaktime' : 'Tambah Breaktime';
        document.getElementById('bt-id').value = row?.id || '';
        form.label.value = row?.label || '';
        form.hari.value = row?.hari || 'semua';
        form.shift.value = row?.shift || '';
        form.waktu_mulai.value = row?.waktu_mulai || '12:00';
        form.waktu_selesai.value = row?.waktu_selesai || '12:45';
        form.type.value = row?.type === 'cinkorak' ? 'cinkorak' : 'istirahat';
        form.is_active.checked = row ? !!row.is_active : true;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        triggerSimulation();
    }

    function closeModal() { 
        modal.classList.add('hidden'); 
        document.body.classList.remove('overflow-hidden');
    }

    document.getElementById('bt-btn-create')?.addEventListener('click', () => openModal(null));
    document.querySelectorAll('[data-bt-close]').forEach(el => el.addEventListener('click', closeModal));

    document.querySelectorAll('.bt-edit').forEach(btn => {
        btn.addEventListener('click', () => openModal(JSON.parse(btn.dataset.row)));
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('bt-id').value ? parseInt(document.getElementById('bt-id').value, 10) : null;
        
        const payload = {
            label: form.label.value.trim(),
            hari: form.hari.value,
            shift: form.shift.value.trim() || null,
            waktu_mulai: form.waktu_mulai.value,
            waktu_selesai: form.waktu_selesai.value,
            type: form.type.value,
            is_active: form.is_active.checked,
        };

        // PHASE 7: Safety Validation
        if (payload.waktu_selesai <= payload.waktu_mulai) {
            showToast({
                type: 'error',
                title: 'Validasi Gagal',
                message: 'Waktu selesai harus setelah waktu mulai!'
            });
            return;
        }

        // Duplicate name check
        const dupName = allBreaks.find(b => b.id !== id && b.label.toLowerCase() === payload.label.toLowerCase() && b.hari === payload.hari && (b.shift === payload.shift || !b.shift && !payload.shift));
        if (dupName) {
            showToast({
                type: 'error',
                title: 'Validasi Gagal',
                message: `Nama parameter "${payload.label}" sudah digunakan pada hari & shift yang sama.`
            });
            return;
        }

        // Overlap check
        const overlap = allBreaks.find(b => {
            if (b.id === id) return false;
            if (b.hari !== payload.hari && b.hari !== 'semua' && payload.hari !== 'semua') return false;
            if (b.shift && payload.shift && b.shift.toLowerCase() !== payload.shift.toLowerCase()) return false;
            
            return (payload.waktu_mulai < b.waktu_selesai && payload.waktu_selesai > b.waktu_mulai);
        });
        if (overlap) {
            showToast({
                type: 'error',
                title: 'Tumpang Tindih Waktu',
                message: `Waktu berbenturan dengan break "${overlap.label}" (${overlap.waktu_mulai} - ${overlap.waktu_selesai})`
            });
            return;
        }

        // PHASE 5: Loading State
        const saveBtn = document.getElementById('bt-form-save');
        const spinner = document.getElementById('bt-save-spinner');
        const btnText = document.getElementById('bt-save-text');
        
        saveBtn.disabled = true;
        saveBtn.classList.add('opacity-70', 'cursor-not-allowed');
        spinner.classList.remove('hidden');
        btnText.textContent = 'Menyimpan...';

        const url = id ? `${base}/${id}` : base;
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, { method, headers: headers(), body: JSON.stringify(payload) });
            const data = await res.json();
            if (!res.ok) {
                showToast({
                    type: 'error',
                    title: 'Gagal Menyimpan',
                    message: data.message || 'Terjadi kesalahan sistem'
                });
                return;
            }

            showToast({
                type: 'success',
                title: 'Berhasil Disimpan',
                message: 'Parameter disimpan & timeline diregenerate!'
            });

            setTimeout(() => {
                window.location.reload();
            }, 1000);

        } catch (err) {
            showToast({
                type: 'error',
                title: 'Koneksi Gagal',
                message: err.message || 'Gagal terhubung ke server'
            });
        } finally {
            saveBtn.disabled = false;
            saveBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            spinner.classList.add('hidden');
            btnText.textContent = 'Simpan Parameter';
        }
    });

    document.querySelectorAll('.bt-toggle').forEach(btn => {
        btn.addEventListener('click', async () => {
            confirmAction('Ubah status aktif parameter breaktime ini?', async () => {
                try {
                    const res = await fetch(`${base}/${btn.dataset.id}/toggle`, { method: 'PATCH', headers: headers(), body: '{}' });
                    if (res.ok) {
                        showToast({
                            type: 'success',
                            title: 'Status Diperbarui',
                            message: 'Status breaktime diubah & timeline diregenerate.'
                        });
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        showToast({ type: 'error', title: 'Gagal', message: 'Gagal memperbarui status parameter.' });
                    }
                } catch (e) {
                    showToast({ type: 'error', title: 'Koneksi Gagal', message: e.message });
                }
            });
        });
    });

    document.querySelectorAll('.bt-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            confirmAction('Apakah Anda yakin ingin menghapus parameter breaktime ini? Tindakan ini akan me-regenerate seluruh timeline.', async () => {
                try {
                    const res = await fetch(`${base}/${btn.dataset.id}`, { method: 'DELETE', headers: headers() });
                    if (res.ok) {
                        showToast({
                            type: 'success',
                            title: 'Parameter Dihapus',
                            message: 'Parameter breaktime telah dihapus dari sistem.'
                        });
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        showToast({ type: 'error', title: 'Gagal', message: 'Gagal menghapus parameter.' });
                    }
                } catch (e) {
                    showToast({ type: 'error', title: 'Koneksi Gagal', message: e.message });
                }
            });
        });
    });
    let simulateTimeout = null;
    async function triggerSimulation() {
        const statusEl = document.getElementById('bt-live-impact-status');
        const contentEl = document.getElementById('bt-live-impact-content');
        if (!statusEl || !contentEl) return;

        const payload = {
            label: form.label.value.trim() || 'PROPOSED',
            hari: form.hari.value,
            shift: form.shift.value.trim() || 'Shift Pagi',
            waktu_mulai: form.waktu_mulai.value,
            waktu_selesai: form.waktu_selesai.value,
            type: form.type.value,
            is_active: form.is_active.checked,
            date: allBreaks[0]?.plan_date || new Date().toISOString().split('T')[0],
        };

        if (!payload.waktu_mulai || !payload.waktu_selesai) {
            statusEl.textContent = 'UP TO DATE';
            statusEl.className = 'text-[9px] bg-amber-200 text-amber-955 px-2 py-0.5 rounded-full font-black';
            contentEl.innerHTML = 'Masukkan waktu mulai dan selesai untuk melihat simulasi dampak.';
            return;
        }

        statusEl.textContent = 'CALCULATING...';
        statusEl.className = 'text-[9px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-black animate-pulse';

        try {
            const res = await fetch(`${base}/simulate`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok && data.affected && data.affected.length > 0) {
                statusEl.textContent = 'AFFECTED';
                statusEl.className = 'text-[9px] bg-red-100 text-[#9F1D1D] px-2 py-0.5 rounded-full font-black animate-pulse';
                
                let html = '<div class="text-[10px] font-bold text-red-950 mb-1">Downstream jobs shifted:</div>';
                html += '<ul class="list-disc pl-4 space-y-0.5 text-gray-700 font-semibold">';
                data.affected.forEach(job => {
                    html += `<li><strong>${job.job_no}</strong>: <span class="line-through text-gray-400">${job.old_start}–${job.old_finish}</span> → <span class="text-[#9F1D1D]">${job.new_start}–${job.new_finish}</span></li>`;
                });
                html += '</ul>';
                contentEl.innerHTML = html;
            } else {
                statusEl.textContent = 'NO IMPACT';
                statusEl.className = 'text-[9px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-black';
                contentEl.innerHTML = 'Timeline tidak terpengaruh atau parameter sesuai batas aman.';
            }
        } catch (e) {
            statusEl.textContent = 'ERROR';
            statusEl.className = 'text-[9px] bg-red-200 text-red-900 px-2 py-0.5 rounded-full font-black';
            contentEl.textContent = 'Gagal memuat preview dampak: ' + e.message;
        }
    }

    function debounceSimulate() {
        clearTimeout(simulateTimeout);
        simulateTimeout = setTimeout(triggerSimulation, 300);
    }

    form.label.addEventListener('input', debounceSimulate);
    form.hari.addEventListener('change', debounceSimulate);
    form.shift.addEventListener('change', debounceSimulate);
    form.waktu_mulai.addEventListener('input', debounceSimulate);
    form.waktu_selesai.addEventListener('input', debounceSimulate);
    form.type.addEventListener('change', debounceSimulate);
    form.is_active.addEventListener('change', debounceSimulate);
})();
</script>
@endpush
@endif
@endsection
