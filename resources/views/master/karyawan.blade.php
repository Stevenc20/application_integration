@extends('layouts.ppc')
@section('title', 'Data Karyawan')

@section('content')
<div class="space-y-6">

    {{-- Flash Message --}}
    @if(session('success'))
    <div id="flashMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl px-5 py-3 flex items-center gap-3 text-sm font-bold shadow-sm">
        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Hero Header --}}
    <div class="bg-gradient-to-r from-red-900 via-red-800 to-rose-700 rounded-3xl px-8 py-6 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 300" fill="none">
                <circle cx="700" cy="30" r="180" fill="white"/>
                <circle cx="80" cy="280" r="130" fill="white"/>
            </svg>
        </div>
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">DATA KARYAWAN</h1>
                    <p class="text-rose-200 text-xs font-semibold flex items-center gap-2 mt-0.5">
                        <span class="inline-block w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                        Database Master Karyawan &bull; {{ now()->translatedFormat('d F Y') }}
                    </p>
                </div>
            </div>
            <button onclick="openAddModal()"
                class="flex items-center gap-2 px-5 py-2.5 bg-white/15 hover:bg-white/25 text-white rounded-xl text-xs font-black transition-all ring-1 ring-white/20 backdrop-blur-sm shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                TAMBAH KARYAWAN
            </button>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-3 md:grid-cols-7 gap-3">
        @php
        $statItems = [
            ['label'=>'Total','value'=>$stats['total'],'color'=>'from-rose-500 to-red-700','border'=>'border-rose-100'],
            ['label'=>'Operator','value'=>$stats['operator'],'color'=>'from-sky-500 to-sky-700','border'=>'border-sky-100'],
            ['label'=>'Leader','value'=>$stats['leader'],'color'=>'from-violet-500 to-violet-700','border'=>'border-violet-100'],
            ['label'=>'Foreman','value'=>$stats['foreman'],'color'=>'from-amber-500 to-amber-700','border'=>'border-amber-100'],
            ['label'=>'Supervisor','value'=>$stats['supervisor'],'color'=>'from-emerald-500 to-emerald-700','border'=>'border-emerald-100'],
            ['label'=>'PPC','value'=>$stats['ppc'] ?? 0,'color'=>'from-indigo-500 to-indigo-700','border'=>'border-indigo-100'],
            ['label'=>'Admin','value'=>$stats['admin'],'color'=>'from-slate-500 to-slate-700','border'=>'border-slate-100'],
        ];
        @endphp
        @foreach($statItems as $s)
        <div class="bg-white rounded-2xl border {{ $s['border'] }} p-4 shadow-sm flex items-center gap-3">
            <div class="w-9 h-9 bg-gradient-to-br {{ $s['color'] }} rounded-xl flex items-center justify-center shadow-md flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <p class="text-xl font-black text-slate-800">{{ $s['value'] }}</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $s['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('master.karyawan') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4 flex flex-col md:flex-row gap-3 items-end">
        <div class="flex-1">
            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Cari Karyawan</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau NRP..."
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
        </div>
        <div class="w-48">
            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Filter Jabatan</label>
            <select name="jabatan" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                <option value="">Semua Jabatan</option>
                <option value="admin" @selected(request('jabatan')==='admin')>Admin</option>
                <option value="operator" @selected(request('jabatan')==='operator')>Operator</option>
                <option value="leader" @selected(request('jabatan')==='leader')>Leader</option>
                <option value="leader a" @selected(request('jabatan')==='leader a')>Leader A</option>
                <option value="leader b" @selected(request('jabatan')==='leader b')>Leader B</option>
                <option value="leader c" @selected(request('jabatan')==='leader c')>Leader C</option>
                <option value="leader d" @selected(request('jabatan')==='leader d')>Leader D</option>
                <option value="shearing" @selected(request('jabatan')==='shearing')>Shearing</option>
                <option value="handwork" @selected(request('jabatan')==='handwork')>Handwork</option>
                <option value="foreman" @selected(request('jabatan')==='foreman')>Foreman</option>
                <option value="supervisor" @selected(request('jabatan')==='supervisor')>Supervisor</option>
                <option value="ppc" @selected(request('jabatan')==='ppc')>PPC</option>
                <option value="quality" @selected(request('jabatan')==='quality')>Quality</option>
                <option value="production" @selected(request('jabatan')==='production')>Production</option>
                <option value="manager" @selected(request('jabatan')==='manager')>Manager</option>
                <option value="kadiv" @selected(request('jabatan')==='kadiv')>Kepala Divisi</option>
                <option value="direktur" @selected(request('jabatan')==='direktur')>Direktur</option>
                <option value="presdir" @selected(request('jabatan')==='presdir')>Presdir</option>
                <option value="dies_shop" @selected(request('jabatan')==='dies_shop')>Dies Shop</option>
                <option value="plant_service" @selected(request('jabatan')==='plant_service')>Plant Service</option>
                <option value="irm" @selected(request('jabatan')==='irm')>IRM</option>
                <option value="logistik" @selected(request('jabatan')==='logistik')>Logistik</option>
            </select>
        </div>
        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-red-800 to-rose-700 hover:from-red-900 hover:to-rose-800 text-white rounded-xl text-xs font-black shadow-lg shadow-rose-200/50 transition-all hover:-translate-y-0.5">
            FILTER
        </button>
        @if(request('search') || request('jabatan'))
        <a href="{{ route('master.karyawan') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black transition-all">RESET</a>
        @endif
    </form>

    {{-- Table Card --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-slate-900">
                        <th class="px-4 py-3.5 text-left text-[9px] font-black text-slate-400 uppercase tracking-widest border-r border-slate-700 w-10">#</th>
                        <th class="px-4 py-3.5 text-left text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700">NRP</th>
                        <th class="px-4 py-3.5 text-left text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700">Nama Karyawan</th>
                        <th class="px-4 py-3.5 text-center text-[9px] font-black text-amber-400 uppercase tracking-widest border-r border-slate-700">Jabatan</th>
                        <th class="px-4 py-3.5 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($karyawans as $i => $k)
                    @php
                    $jabatanConfig = [
                        'admin'         => ['bg'=>'bg-slate-100','text'=>'text-slate-700','border'=>'border-slate-200'],
                        'operator'      => ['bg'=>'bg-sky-50','text'=>'text-sky-700','border'=>'border-sky-100'],
                        'leader'        => ['bg'=>'bg-violet-50','text'=>'text-violet-700','border'=>'border-violet-100'],
                        'leader a'      => ['bg'=>'bg-violet-50','text'=>'text-violet-700','border'=>'border-violet-100'],
                        'leader b'      => ['bg'=>'bg-violet-50','text'=>'text-violet-700','border'=>'border-violet-100'],
                        'leader c'      => ['bg'=>'bg-violet-50','text'=>'text-violet-700','border'=>'border-violet-100'],
                        'leader d'      => ['bg'=>'bg-violet-50','text'=>'text-violet-700','border'=>'border-violet-100'],
                        'shearing'      => ['bg'=>'bg-teal-50','text'=>'text-teal-700','border'=>'border-teal-100'],
                        'handwork'      => ['bg'=>'bg-orange-50','text'=>'text-orange-700','border'=>'border-orange-100'],
                        'foreman'       => ['bg'=>'bg-amber-50','text'=>'text-amber-700','border'=>'border-amber-100'],
                        'supervisor'    => ['bg'=>'bg-emerald-50','text'=>'text-emerald-700','border'=>'border-emerald-100'],
                        'ppc'           => ['bg'=>'bg-indigo-50','text'=>'text-indigo-700','border'=>'border-indigo-100'],
                        'quality'       => ['bg'=>'bg-cyan-50','text'=>'text-cyan-700','border'=>'border-cyan-100'],
                        'production'    => ['bg'=>'bg-blue-50','text'=>'text-blue-700','border'=>'border-blue-100'],
                        'manager'       => ['bg'=>'bg-rose-50','text'=>'text-rose-700','border'=>'border-rose-100'],
                        'kadiv'         => ['bg'=>'bg-red-50','text'=>'text-red-700','border'=>'border-red-100'],
                        'direktur'      => ['bg'=>'bg-purple-50','text'=>'text-purple-700','border'=>'border-purple-100'],
                        'presdir'       => ['bg'=>'bg-yellow-50','text'=>'text-yellow-700','border'=>'border-yellow-100'],
                        'dies_shop'     => ['bg'=>'bg-stone-50','text'=>'text-stone-700','border'=>'border-stone-100'],
                        'plant_service' => ['bg'=>'bg-lime-50','text'=>'text-lime-700','border'=>'border-lime-100'],
                        'irm'           => ['bg'=>'bg-pink-50','text'=>'text-pink-700','border'=>'border-pink-100'],
                        'logistik'      => ['bg'=>'bg-gray-50','text'=>'text-gray-700','border'=>'border-gray-100'],
                    ];
                    $jc = $jabatanConfig[$k->jabatan] ?? ['bg'=>'bg-slate-100','text'=>'text-slate-600','border'=>'border-slate-200'];
                    @endphp
                    <tr class="border-b border-slate-100 hover:bg-rose-50/30 transition-colors group">
                        <td class="px-4 py-3 text-center text-[11px] font-bold text-slate-400 border-r border-slate-100">{{ $karyawans->firstItem() + $i }}</td>
                        <td class="px-4 py-3 border-r border-slate-100">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400 flex-shrink-0"></span>
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ $k->nrp_karyawan }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 border-r border-slate-100">
                            <span class="text-sm font-medium text-slate-700">{{ $k->nama_karyawan }}</span>
                        </td>
                        <td class="px-4 py-3 text-center border-r border-slate-100">
                            <span class="inline-block px-2.5 py-1 rounded-lg {{ $jc['bg'] }} {{ $jc['text'] }} text-[10px] font-black uppercase tracking-wider border {{ $jc['border'] }}">{{ $k->jabatan }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button"
                                    onclick="openKaryawanEditModal({id:'{{ $k->id_karyawan }}',nama:'{{ addslashes($k->nama_karyawan) }}',nrp:'{{ addslashes($k->nrp_karyawan) }}',jabatan:'{{ $k->jabatan }}'})"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-all hover:scale-110 active:scale-95" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button type="button"
                                    data-id="{{ $k->id_karyawan }}"
                                    data-nama="{{ $k->nama_karyawan }}"
                                    data-nrp="{{ $k->nrp_karyawan }}"
                                    onclick="openKaryawanDeleteModal(this)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-600 transition-all hover:scale-110 active:scale-95" title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-400">Belum ada data karyawan</p>
                                <p class="text-xs text-slate-300">Klik tombol "TAMBAH KARYAWAN" untuk mulai menambahkan data</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($karyawans->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $karyawans->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ===== MODAL ADD ===== --}}
<div id="addModal" class="fixed inset-0 z-[99999] hidden items-start justify-center pt-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="relative w-full max-w-lg mx-4">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-800 to-rose-700 px-8 py-6">
                <h2 class="text-lg font-black text-white tracking-tight">TAMBAH KARYAWAN</h2>
                <p class="text-rose-200 text-xs mt-0.5">Isi formulir untuk menambahkan karyawan baru</p>
            </div>
            <form action="{{ route('master.karyawan.store') }}" method="POST" class="px-8 py-6 space-y-4 overflow-y-auto max-h-[65vh]">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nama Karyawan</label>
                    <input type="text" name="nama_karyawan" required placeholder="Nama lengkap..."
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">NRP Karyawan</label>
                    <input type="text" name="nrp_karyawan" required placeholder="Nomor Registrasi Pegawai..."
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pilih Jabatan</label>
                    <input type="hidden" name="jabatan" id="add_jabatan_val" required>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Card Operator -->
                        <div onclick="setAddJabatan('operator')" id="add_jab_operator" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-sky-300 hover:bg-sky-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-cog text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Operator</span>
                                <span class="block text-[9px] font-medium text-slate-400">Produksi Line</span>
                            </div>
                        </div>

                        <!-- Card Leader A -->
                        <div onclick="setAddJabatan('leader a')" id="add_jab_leader-a" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader A</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line A Leader</span>
                            </div>
                        </div>

                        <!-- Card Leader B -->
                        <div onclick="setAddJabatan('leader b')" id="add_jab_leader-b" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader B</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line B Leader</span>
                            </div>
                        </div>

                        <!-- Card Leader C -->
                        <div onclick="setAddJabatan('leader c')" id="add_jab_leader-c" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader C</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line C Leader</span>
                            </div>
                        </div>

                        <!-- Card Leader D -->
                        <div onclick="setAddJabatan('leader d')" id="add_jab_leader-d" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader D</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line D Leader</span>
                            </div>
                        </div>

                        <!-- Card Shearing -->
                        <div onclick="setAddJabatan('shearing')" id="add_jab_shearing" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-teal-300 hover:bg-teal-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-cut text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Shearing</span>
                                <span class="block text-[9px] font-medium text-slate-400">Shearing Line</span>
                            </div>
                        </div>

                        <!-- Card Handwork -->
                        <div onclick="setAddJabatan('handwork')" id="add_jab_handwork" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-orange-300 hover:bg-orange-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-hands text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Handwork</span>
                                <span class="block text-[9px] font-medium text-slate-400">Handwork Line</span>
                            </div>
                        </div>


                        <!-- Card Foreman -->
                        <div onclick="setAddJabatan('foreman')" id="add_jab_foreman" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-amber-300 hover:bg-amber-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-user-shield text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Foreman</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line Foreman</span>
                            </div>
                        </div>

                        <!-- Card Supervisor -->
                        <div onclick="setAddJabatan('supervisor')" id="add_jab_supervisor" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-crown text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Supervisor</span>
                                <span class="block text-[9px] font-medium text-slate-400">Area Supervisor</span>
                            </div>
                        </div>

                        <!-- Card Admin -->
                        <div onclick="setAddJabatan('admin')" id="add_jab_admin" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-slate-300 hover:bg-slate-100/30 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-key text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Admin</span>
                                <span class="block text-[9px] font-medium text-slate-400">System Administrator</span>
                            </div>
                        </div>

                        <!-- Card PPC -->
                        <div onclick="setAddJabatan('ppc')" id="add_jab_ppc" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-indigo-300 hover:bg-indigo-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-calendar-alt text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">PPC</span>
                                <span class="block text-[9px] font-medium text-slate-400">Production Planning Control</span>
                            </div>
                        </div>

                        <!-- Card Quality -->
                        <div onclick="setAddJabatan('quality')" id="add_jab_quality" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-cyan-300 hover:bg-cyan-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-check-double text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Quality</span>
                                <span class="block text-[9px] font-medium text-slate-400">Quality Control</span>
                            </div>
                        </div>

                        <!-- Card Production -->
                        <div onclick="setAddJabatan('production')" id="add_jab_production" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-blue-300 hover:bg-blue-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-industry text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Production</span>
                                <span class="block text-[9px] font-medium text-slate-400">Production Staff</span>
                            </div>
                        </div>

                        <!-- Card Manager -->
                        <div onclick="setAddJabatan('manager')" id="add_jab_manager" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-rose-300 hover:bg-rose-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-briefcase text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Manager</span>
                                <span class="block text-[9px] font-medium text-slate-400">Manager Produksi</span>
                            </div>
                        </div>

                        <!-- Card Kadiv -->
                        <div onclick="setAddJabatan('kadiv')" id="add_jab_kadiv" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-red-300 hover:bg-red-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-user-tie text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Kadiv</span>
                                <span class="block text-[9px] font-medium text-slate-400">Kepala Divisi</span>
                            </div>
                        </div>

                        <!-- Card Direktur -->
                        <div onclick="setAddJabatan('direktur')" id="add_jab_direktur" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-purple-300 hover:bg-purple-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-crown text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Direktur</span>
                                <span class="block text-[9px] font-medium text-slate-400">Direktur Utama</span>
                            </div>
                        </div>

                        <!-- Card Presdir -->
                        <div onclick="setAddJabatan('presdir')" id="add_jab_presdir" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-yellow-300 hover:bg-yellow-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-star text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Presdir</span>
                                <span class="block text-[9px] font-medium text-slate-400">President Director</span>
                            </div>
                        </div>

                        <!-- Card Dies Shop -->
                        <div onclick="setAddJabatan('dies_shop')" id="add_jab_dies_shop" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-stone-300 hover:bg-stone-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-stone-50 text-stone-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-tools text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Dies Shop</span>
                                <span class="block text-[9px] font-medium text-slate-400">Maintenance Dies</span>
                            </div>
                        </div>

                        <!-- Card Plant Service -->
                        <div onclick="setAddJabatan('plant_service')" id="add_jab_plant_service" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-lime-300 hover:bg-lime-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-lime-50 text-lime-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-wrench text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Plant Service</span>
                                <span class="block text-[9px] font-medium text-slate-400">Maintenance Plant</span>
                            </div>
                        </div>

                        <!-- Card IRM -->
                        <div onclick="setAddJabatan('irm')" id="add_jab_irm" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-pink-300 hover:bg-pink-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-boxes text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">IRM</span>
                                <span class="block text-[9px] font-medium text-slate-400">Inventory Raw Material</span>
                            </div>
                        </div>

                        <!-- Card Logistik -->
                        <div onclick="setAddJabatan('logistik')" id="add_jab_logistik" class="add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-gray-300 hover:bg-gray-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-gray-50 text-gray-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-truck text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Logistik</span>
                                <span class="block text-[9px] font-medium text-slate-400">Logistik & Incoming</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeAddModal()" class="flex-1 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">BATAL</button>
                    <button type="submit" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-800 to-rose-700 hover:from-red-900 hover:to-rose-800 text-white rounded-xl text-sm font-black shadow-lg shadow-rose-200 transition-all hover:-translate-y-0.5">SIMPAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL EDIT ===== --}}
<div id="editModal" class="fixed inset-0 z-[99999] hidden items-start justify-center pt-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeKaryawanEditModal()"></div>
    <div class="relative w-full max-w-lg mx-4">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-slate-700 to-slate-900 px-8 py-6">
                <h2 class="text-lg font-black text-white tracking-tight">EDIT KARYAWAN</h2>
                <p class="text-slate-400 text-xs mt-0.5">Perbarui informasi karyawan yang dipilih</p>
            </div>
            <form id="editForm" method="POST" class="px-8 py-6 space-y-4 overflow-y-auto max-h-[65vh]">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nama Karyawan</label>
                    <input type="text" id="edit_nama" name="nama_karyawan" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">NRP Karyawan</label>
                    <input type="text" id="edit_nrp" name="nrp_karyawan" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pilih Jabatan</label>
                    <input type="hidden" name="jabatan" id="edit_jabatan_val" required>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Card Operator -->
                        <div onclick="setEditJabatan('operator')" id="edit_jab_operator" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-sky-300 hover:bg-sky-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-cog text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Operator</span>
                                <span class="block text-[9px] font-medium text-slate-400">Produksi Line</span>
                            </div>
                        </div>

                        <!-- Card Leader A -->
                        <div onclick="setEditJabatan('leader a')" id="edit_jab_leader-a" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader A</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line A Leader</span>
                            </div>
                        </div>

                        <!-- Card Leader B -->
                        <div onclick="setEditJabatan('leader b')" id="edit_jab_leader-b" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader B</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line B Leader</span>
                            </div>
                        </div>

                        <!-- Card Leader C -->
                        <div onclick="setEditJabatan('leader c')" id="edit_jab_leader-c" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader C</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line C Leader</span>
                            </div>
                        </div>

                        <!-- Card Leader D -->
                        <div onclick="setEditJabatan('leader d')" id="edit_jab_leader-d" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-violet-300 hover:bg-violet-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Leader D</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line D Leader</span>
                            </div>
                        </div>

                        <!-- Card Shearing -->
                        <div onclick="setEditJabatan('shearing')" id="edit_jab_shearing" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-teal-300 hover:bg-teal-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-cut text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Shearing</span>
                                <span class="block text-[9px] font-medium text-slate-400">Shearing Line</span>
                            </div>
                        </div>

                        <!-- Card Handwork -->
                        <div onclick="setEditJabatan('handwork')" id="edit_jab_handwork" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-orange-300 hover:bg-orange-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-hands text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Handwork</span>
                                <span class="block text-[9px] font-medium text-slate-400">Handwork Line</span>
                            </div>
                        </div>

                        <!-- Card Foreman -->
                        <div onclick="setEditJabatan('foreman')" id="edit_jab_foreman" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-amber-300 hover:bg-amber-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-user-shield text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Foreman</span>
                                <span class="block text-[9px] font-medium text-slate-400">Line Foreman</span>
                            </div>
                        </div>

                        <!-- Card Supervisor -->
                        <div onclick="setEditJabatan('supervisor')" id="edit_jab_supervisor" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-crown text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Supervisor</span>
                                <span class="block text-[9px] font-medium text-slate-400">Area Supervisor</span>
                            </div>
                        </div>

                        <!-- Card Admin -->
                        <div onclick="setEditJabatan('admin')" id="edit_jab_admin" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-slate-300 hover:bg-slate-100/30 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-key text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Admin</span>
                                <span class="block text-[9px] font-medium text-slate-400">System Administrator</span>
                            </div>
                        </div>

                        <!-- Card PPC -->
                        <div onclick="setEditJabatan('ppc')" id="edit_jab_ppc" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-indigo-300 hover:bg-indigo-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-calendar-alt text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">PPC</span>
                                <span class="block text-[9px] font-medium text-slate-400">Production Planning Control</span>
                            </div>
                        </div>

                        <!-- Card Quality -->
                        <div onclick="setEditJabatan('quality')" id="edit_jab_quality" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-cyan-300 hover:bg-cyan-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-check-double text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Quality</span>
                                <span class="block text-[9px] font-medium text-slate-400">Quality Control</span>
                            </div>
                        </div>

                        <!-- Card Production -->
                        <div onclick="setEditJabatan('production')" id="edit_jab_production" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-blue-300 hover:bg-blue-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-industry text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Production</span>
                                <span class="block text-[9px] font-medium text-slate-400">Production Staff</span>
                            </div>
                        </div>

                        <!-- Card Manager -->
                        <div onclick="setEditJabatan('manager')" id="edit_jab_manager" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-rose-300 hover:bg-rose-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-briefcase text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Manager</span>
                                <span class="block text-[9px] font-medium text-slate-400">Manager Produksi</span>
                            </div>
                        </div>

                        <!-- Card Kadiv -->
                        <div onclick="setEditJabatan('kadiv')" id="edit_jab_kadiv" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-red-300 hover:bg-red-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-user-tie text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Kadiv</span>
                                <span class="block text-[9px] font-medium text-slate-400">Kepala Divisi</span>
                            </div>
                        </div>

                        <!-- Card Direktur -->
                        <div onclick="setEditJabatan('direktur')" id="edit_jab_direktur" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-purple-300 hover:bg-purple-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-crown text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Direktur</span>
                                <span class="block text-[9px] font-medium text-slate-400">Direktur Utama</span>
                            </div>
                        </div>

                        <!-- Card Presdir -->
                        <div onclick="setEditJabatan('presdir')" id="edit_jab_presdir" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-yellow-300 hover:bg-yellow-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-star text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Presdir</span>
                                <span class="block text-[9px] font-medium text-slate-400">President Director</span>
                            </div>
                        </div>

                        <!-- Card Dies Shop -->
                        <div onclick="setEditJabatan('dies_shop')" id="edit_jab_dies_shop" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-stone-300 hover:bg-stone-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-stone-50 text-stone-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-tools text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Dies Shop</span>
                                <span class="block text-[9px] font-medium text-slate-400">Maintenance Dies</span>
                            </div>
                        </div>

                        <!-- Card Plant Service -->
                        <div onclick="setEditJabatan('plant_service')" id="edit_jab_plant_service" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-lime-300 hover:bg-lime-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-lime-50 text-lime-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-wrench text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Plant Service</span>
                                <span class="block text-[9px] font-medium text-slate-400">Maintenance Plant</span>
                            </div>
                        </div>

                        <!-- Card IRM -->
                        <div onclick="setEditJabatan('irm')" id="edit_jab_irm" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-pink-300 hover:bg-pink-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-boxes text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">IRM</span>
                                <span class="block text-[9px] font-medium text-slate-400">Inventory Raw Material</span>
                            </div>
                        </div>

                        <!-- Card Logistik -->
                        <div onclick="setEditJabatan('logistik')" id="edit_jab_logistik" class="edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-gray-300 hover:bg-gray-50/10 cursor-pointer transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-xl bg-gray-50 text-gray-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                                <i class="fas fa-truck text-sm"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-xs font-black text-slate-800 tracking-wide uppercase">Logistik</span>
                                <span class="block text-[9px] font-medium text-slate-400">Logistik & Incoming</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeKaryawanEditModal()" class="flex-1 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">BATAL</button>
                    <button type="submit" class="flex-1 px-5 py-3 bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-black text-white rounded-xl text-sm font-black shadow-lg transition-all hover:-translate-y-0.5">UPDATE</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL DELETE ===== --}}
<div id="deleteModal" class="fixed inset-0 z-[99999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeKaryawanDeleteModal()"></div>
    <div class="relative w-full max-w-sm mx-4">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center">
            <div class="px-8 pt-8 pb-6">
                <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-black text-slate-800 mb-2">Hapus Karyawan?</h2>
                <p class="text-xs text-red-500 font-bold mb-5">Tindakan ini tidak bisa dibatalkan.</p>
                <div class="bg-slate-50 rounded-2xl px-5 py-4 mb-6 text-left space-y-2 border border-slate-100">
                    <div class="flex gap-3 text-sm">
                        <span class="text-slate-400 font-bold w-12 shrink-0 text-[11px] uppercase tracking-wider">NRP</span>
                        <span id="deleteNrp" class="font-black text-slate-800"></span>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <span class="text-slate-400 font-bold w-12 shrink-0 text-[11px] uppercase tracking-wider">Nama</span>
                        <span id="deleteNama" class="font-bold text-slate-700"></span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeKaryawanDeleteModal()" class="flex-1 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">BATAL</button>
                    <button type="button" onclick="confirmKaryawanDelete()" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-700 to-red-900 hover:from-red-800 hover:to-red-950 text-white rounded-xl text-sm font-black shadow-lg shadow-red-200 transition-all hover:-translate-y-0.5">YA, HAPUS</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showModal(id) { const m=document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); }
function hideModal(id) { const m=document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); }

// ================= JABATAN CARD SELECTOR HANDLERS =================
const roleColors = {
    operator: { border: 'border-sky-500', bg: 'bg-sky-50/30', ring: 'ring-sky-500/20' },
    leader: { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    'leader a': { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    'leader b': { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    'leader c': { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    'leader d': { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    shearing: { border: 'border-teal-500', bg: 'bg-teal-50/30', ring: 'ring-teal-500/20' },
    handwork: { border: 'border-orange-500', bg: 'bg-orange-50/30', ring: 'ring-orange-500/20' },
    foreman: { border: 'border-amber-500', bg: 'bg-amber-50/30', ring: 'ring-amber-500/20' },
    supervisor: { border: 'border-emerald-500', bg: 'bg-emerald-50/30', ring: 'ring-emerald-500/20' },
    ppc: { border: 'border-indigo-500', bg: 'bg-indigo-50/30', ring: 'ring-indigo-500/20' },
    admin: { border: 'border-slate-500', bg: 'bg-slate-100/50', ring: 'ring-slate-500/20' },
    quality: { border: 'border-cyan-500', bg: 'bg-cyan-50/30', ring: 'ring-cyan-500/20' },
    production: { border: 'border-blue-500', bg: 'bg-blue-50/30', ring: 'ring-blue-500/20' },
    manager: { border: 'border-rose-500', bg: 'bg-rose-50/30', ring: 'ring-rose-500/20' },
    kadiv: { border: 'border-red-500', bg: 'bg-red-50/30', ring: 'ring-red-500/20' },
    direktur: { border: 'border-purple-500', bg: 'bg-purple-50/30', ring: 'ring-purple-500/20' },
    presdir: { border: 'border-yellow-500', bg: 'bg-yellow-50/30', ring: 'ring-yellow-500/20' },
    dies_shop: { border: 'border-stone-500', bg: 'bg-stone-50/30', ring: 'ring-stone-500/20' },
    plant_service: { border: 'border-lime-500', bg: 'bg-lime-50/30', ring: 'ring-lime-500/20' },
    irm: { border: 'border-pink-500', bg: 'bg-pink-50/30', ring: 'ring-pink-500/20' },
    logistik: { border: 'border-gray-500', bg: 'bg-gray-50/30', ring: 'ring-gray-500/20' }
};

function setAddJabatan(val) {
    document.getElementById('add_jabatan_val').value = val;
    document.querySelectorAll('.add-jab-card').forEach(c => {
        // Reset classes
        c.className = "add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-slate-300 hover:bg-slate-50 cursor-pointer transition-all duration-200 group";
    });

    const safeVal = val.replace(/\s+/g, '-');
    const activeCard = document.getElementById('add_jab_' + safeVal);
    if(activeCard) {
        const color = roleColors[val];
        activeCard.className = `add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 ${color.border} ${color.bg} cursor-pointer transition-all duration-200 group shadow-md ring-2 ${color.ring}`;
    }
}

function setEditJabatan(val) {
    document.getElementById('edit_jabatan_val').value = val;
    document.querySelectorAll('.edit-jab-card').forEach(c => {
        // Reset classes
        c.className = "edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-slate-300 hover:bg-slate-50 cursor-pointer transition-all duration-200 group";
    });

    const safeVal = val.replace(/\s+/g, '-');
    const activeCard = document.getElementById('edit_jab_' + safeVal);
    if(activeCard) {
        const color = roleColors[val];
        activeCard.className = `edit-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 ${color.border} ${color.bg} cursor-pointer transition-all duration-200 group shadow-md ring-2 ${color.ring}`;
    }
}

// ================= ADD =================
function openAddModal() { 
    // Reset add jabatan
    document.getElementById('add_jabatan_val').value = '';
    document.querySelectorAll('.add-jab-card').forEach(c => {
        c.className = "add-jab-card flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-100 hover:border-slate-300 hover:bg-slate-50 cursor-pointer transition-all duration-200 group";
    });
    showModal('addModal'); 
}
function closeAddModal() { hideModal('addModal'); }

// ================= EDIT =================
function openKaryawanEditModal(data) {
    document.getElementById('edit_nama').value    = data.nama;
    document.getElementById('edit_nrp').value     = data.nrp;
    setEditJabatan(data.jabatan);
    document.getElementById('editForm').action    = '/master/karyawan/update/' + data.id;
    showModal('editModal');
}
function closeKaryawanEditModal() { hideModal('editModal'); }

// ================= DELETE =================
let deleteId = null;
function openKaryawanDeleteModal(btn) {
    deleteId = btn.dataset.id;
    document.getElementById('deleteNama').textContent = btn.dataset.nama;
    document.getElementById('deleteNrp').textContent  = btn.dataset.nrp;
    showModal('deleteModal');
}
function closeKaryawanDeleteModal() { deleteId = null; hideModal('deleteModal'); }
function confirmKaryawanDelete() { if (deleteId) window.location.href = '/master/karyawan/delete/' + deleteId; }

// Auto-hide flash
setTimeout(() => { const f = document.getElementById('flashMsg'); if(f) f.style.display='none'; }, 4000);
</script>
@endsection
