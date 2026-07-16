@extends('layouts.app')

@section('title', 'Storage Location')



@section('content')
    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm mb-6 mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm mb-6 mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm mb-6 mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ $errors->first() }}</span>
    </div>
    @endif

        {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 mb-6 mt-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Storage Location</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Kelola data area penyimpanan material, deskripsi lokasi, tipe material, dan klasifikasi scrap</p>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col p-6">
            {{-- Toolbar Header --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                <form action="{{ route('storage_locations.index') }}" method="GET" class="flex items-center gap-2 w-full lg:w-auto flex-wrap">
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full lg:max-w-[240px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode / nama lokasi..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px]">Cari</button>
                    @if($search)
                        <a href="{{ route('storage_locations.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                    @endif
                </form>

                {{-- Right: Actions buttons --}}
                <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto">
                    <a href="{{ route('storage_locations.export', ['search' => $search]) }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-emerald-200">
                        <span class="material-icons">file_download</span> Export Excel
                    </a>
                    <a href="{{ route('storage_locations.template') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-slate-200">
                        <span class="material-icons">receipt_long</span> Template
                    </a>
                    <button type="button" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs shadow-sm border border-slate-200" onclick="openLocationModal('importModal')">
                        <span class="material-icons">upload</span> Import Excel
                    </button>
                    <a href="{{ route('storage_locations.print_pdf', ['search' => $search]) }}" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-rose-200">
                        <span class="material-icons">print</span> Print PDF
                    </a>
                    <a href="{{ route('storage_locations.create') }}" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs shadow-sm border border-slate-200" >
                        <span class="material-icons">add</span> + Tambah Lokasi
                    </a>
                </div>
            </div>
        </div>
        {{-- Table wrap --}}
            <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                            <th>Kode</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Nama</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Deskripsi</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Tipe Material</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Scrap</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($storageLocations as $location)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4 text-xs font-medium text-slate-600 text-center">
                                <a href="javascript:void(0)" class="location-code-link" data-item="{{ json_encode($location) }}" onclick="showLocationDetail(JSON.parse(this.dataset.item))">
                                    {{ $location->kode }}
                                </a>
                            </td>
                            <td style="font-weight: 700; color: #333;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $location->nama }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $location->deskripsi ?? '-' }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-600 text-center">
                                @if($location->tipe_material === 'RM')
                                    <span class="pill-badge pill-rm">RM</span>
                                @elseif($location->tipe_material === 'WIP')
                                    <span class="pill-badge pill-wip">WIP</span>
                                @elseif($location->tipe_material === 'FP')
                                    <span class="pill-badge pill-fp">FP</span>
                                @else
                                    <span class="pill-badge">{{ $location->tipe_material }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-600 text-center">
                                @if($location->is_scrap)
                                    <span class="pill-badge pill-scrap">Scrap</span>
                                @else
                                    <span style="color:#bbb">—</span>
                                @endif
                            </td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="javascript:void(0)" class="bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 px-2 py-1 rounded text-[10px] font-bold transition-colors" data-item="{{ json_encode($location) }}" onclick="showLocationDetail(JSON.parse(this.dataset.item))">Detail</a>
                                    <a href="javascript:void(0)" class="bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 px-2 py-1 rounded text-[10px] font-bold transition-colors" data-item="{{ json_encode($location) }}" onclick="showLocationEdit(JSON.parse(this.dataset.item))">Edit</a>
                                    <button type="button" class="bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 px-2 py-1 rounded text-[10px] font-bold transition-colors" data-url="{{ route('storage_locations.destroy', $location->id) }}" data-name="{{ $location->nama }}" onclick="showLocationDeleteModal(this.dataset.url, this.dataset.name)">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <span class="material-icons" style="font-size: 40px; display: block; margin-bottom: 8px;">search_off</span>
                                Belum ada data storage location.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($storageLocations->total() > 0 && $storageLocations->lastPage() > 1)
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    Menampilkan <strong>{{ $storageLocations->firstItem() ?? 0 }}-{{ $storageLocations->lastItem() ?? 0 }}</strong> dari <strong>{{ $storageLocations->total() }}</strong> storage location
                </div>
                
                <div class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if($storageLocations->onFirstPage())
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-100 text-slate-300 text-xs font-medium cursor-not-allowed"><span class="material-icons">chevron_left</span></span>
                    @else
                        <a href="{{ $storageLocations->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors"><span class="material-icons">chevron_left</span></a>
                    @endif

                    {{-- Pagination Pages --}}
                    @php
                        $start = max(1, $storageLocations->currentPage() - 2);
                        $end = min($storageLocations->lastPage(), $storageLocations->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $storageLocations->url(1) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors">1</a>
                        @if($start > 2)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $storageLocations->currentPage())
                            <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-600 bg-red-600 text-white text-xs font-medium">{{ $page }}</span>
                        @else
                            <a href="{{ $storageLocations->url($page) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $storageLocations->lastPage())
                        @if($end < $storageLocations->lastPage() - 1)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                        <a href="{{ $storageLocations->url($storageLocations->lastPage()) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors">{{ $storageLocations->lastPage() }}</a>
                    @endif

                    {{-- Next Page Link --}}
                    @if($storageLocations->hasMorePages())
                        <a href="{{ $storageLocations->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors"><span class="material-icons">chevron_right</span></a>
                    @else
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-100 text-slate-300 text-xs font-medium cursor-not-allowed"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- MODAL: TAMBAH LOKASI --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="addModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">add_circle</span> Tambah Lokasi Baru</h3>
            <form action="{{ route('storage_locations.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode Lokasi <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="add_kode" placeholder="Contoh: 1101-S" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tipe Material</label>
                        <select name="tipe_material" id="add_tipe_material" class="filter-select" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                            <option value="">Semua Tipe / Gudang Umum</option>
                            <option value="RM">RM (Raw Material)</option>
                            <option value="WIP">WIP (Work in Progress)</option>
                            <option value="FP">FP (Finished Product)</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Lokasi <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="add_nama" placeholder="Contoh: Gudang IRM" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Deskripsi</label>
                    <input type="text" name="deskripsi" id="add_deskripsi" placeholder="Keterangan penyimpanan lokasi" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Klasifikasi Scrap <span style="color: red;">*</span></label>
                    <select name="is_scrap" id="add_is_scrap" class="filter-select" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        <option value="0">Bukan Scrap</option>
                        <option value="1">Scrap</option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                    <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeLocationModal('addModal')">Batal</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EDIT LOKASI --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="editModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">edit</span> Edit Lokasi</h3>
            <form action="{{ route('storage_locations.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode Lokasi <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="edit_kode" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tipe Material</label>
                        <select name="tipe_material" id="edit_tipe_material" class="filter-select" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                            <option value="">Semua Tipe / Gudang Umum</option>
                            <option value="RM">RM (Raw Material)</option>
                            <option value="WIP">WIP (Work in Progress)</option>
                            <option value="FP">FP (Finished Product)</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Lokasi <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Deskripsi</label>
                    <input type="text" name="deskripsi" id="edit_deskripsi" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Klasifikasi Scrap <span style="color: red;">*</span></label>
                    <select name="is_scrap" id="edit_is_scrap" class="filter-select" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        <option value="0">Bukan Scrap</option>
                        <option value="1">Scrap</option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                    <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeLocationModal('editModal')">Batal</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: DETAIL LOKASI --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="detailModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl" style="max-width: 650px; width: 90%;">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">info</span> Detail Storage Location</h3>
            <div style="margin-bottom: 20px;">
                <table class="w-full border-collapse text-sm">
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Kode Lokasi</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_kode" style="font-family: monospace; color: var(--red-main);"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Nama Lokasi</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_nama"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Deskripsi</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_deskripsi"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Tipe Material</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_tipe_material"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Klasifikasi Scrap</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_is_scrap"></td>
                    </tr>
                </table>
            </div>

            <div id="detail_stocks_container" style="margin-top: 20px; display: none;">
                <h4 style="font-size: 13px; font-weight: 800; color: var(--navy-dark); margin-bottom: 10px; border-bottom: 2px solid #eaeaea; padding-bottom: 6px;">Stok di Lokasi Ini</h4>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #f1f5f9; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <th style="padding: 8px; text-align: left; font-weight: 800; color: #555;">Kode Material</th>
                                <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Nama Material</th>
                                <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Qty</th>
                                <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">UoM</th>
                            </tr>
                        </thead>
                        <tbody id="detail_stocks_body">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100" style="margin-top: 24px;">
                <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeLocationModal('detailModal')" style="width: 100%;">Tutup</button>
            </div>
        </div>
    </div>

    {{-- MODAL: IMPORT EXCEL --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="importModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl" style="max-width: 440px;">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">upload_file</span> Import Excel</h3>
            <form action="{{ route('storage_locations.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="material-icons" style="font-size: 48px; color: var(--red-main);">upload_file</span>
                    <p style="font-size: 13px; color: #555; margin-top: 8px;">Upload template Excel yang telah diisi data master storage location.</p>
                    <a href="{{ route('storage_locations.template') }}" style="font-size: 12px; color: var(--red-main); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 8px;">
                        <span class="material-icons" style="font-size: 16px;">download</span> Download Template Storage Location
                    </a>
                </div>
                
                <div class="flex flex-col gap-1.5" style="margin-bottom: 0;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pilih File Excel (.xlsx, .xls) <span style="color: red;">*</span></label>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required style="padding: 6px 10px;" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>
                
                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                    <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeLocationModal('importModal')">Batal</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')

    {{-- MODAL: DELETE CONFIRMATION --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="deleteModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl text-center">
            <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <h3 class="text-lg font-black text-slate-800 mb-2">Konfirmasi Hapus</h3>
            <p class="text-sm text-slate-500 mb-6">Apakah Anda yakin ingin menghapus <strong id="delete_item_name" class="text-slate-800"></strong>? Data yang dihapus tidak dapat dikembalikan.</p>
            
            <form id="deleteForm" action="" method="POST" class="flex justify-center gap-3">
                @csrf
                @method('DELETE')
                <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm w-full" onclick="closeLocationModal('deleteModal')">Batal</button>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm w-full">Ya, Hapus!</button>
            </form>
        </div>
    </div>

<script>
    function showLocationDeleteModal(url, name) {
        document.getElementById('deleteForm').action = url;
        document.getElementById('delete_item_name').innerText = name;
        openLocationModal('deleteModal');
    }

    function openLocationModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }
    
    function closeLocationModal(id) {
        document.getElementById(id).classList.remove('flex');
        document.getElementById(id).classList.add('hidden');
    }

    function showLocationDetail(loc) {
        document.getElementById('detail_kode').innerText = loc.kode;
        document.getElementById('detail_nama').innerText = loc.nama;
        document.getElementById('detail_deskripsi').innerText = loc.deskripsi || '-';
        document.getElementById('detail_tipe_material').innerText = loc.tipe_material || 'Semua Tipe';
        document.getElementById('detail_is_scrap').innerText = loc.is_scrap ? 'Scrap' : 'Bukan Scrap';

        // Populate stocks table
        const tbody = document.getElementById('detail_stocks_body');
        tbody.innerHTML = '';
        if (loc.stocks && loc.stocks.length > 0) {
            loc.stocks.forEach(stock => {
                const materialCode = stock.material ? stock.material.code : '-';
                const materialName = stock.material ? stock.material.name : '-';
                const uom = stock.material ? (stock.material.unit_of_measure || 'PCS') : 'PCS';
                const qtyVal = stock.qty !== undefined ? stock.qty : (stock.quantity !== undefined ? stock.quantity : 0);
                
                const formattedQty = parseFloat(qtyVal).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                const qtyClass = qtyVal <= 0 ? 'text-red-600' : 'text-green-700';

                const tr = document.createElement('tr');
                tr.className = 'border-b hover:bg-gray-50';
                tr.innerHTML = `
                    <td style="padding: 8px; font-family: monospace; color: var(--navy-dark); font-weight: bold;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">${materialCode}</td>
                    <td style="padding: 8px; color: #333;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">${materialName}</td>
                    <td style="padding: 8px; text-align: right; font-weight: bold;" class="${qtyClass}" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">${formattedQty}</td>
                    <td style="padding: 8px; color: #666;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">${uom}</td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('detail_stocks_container').style.display = 'block';
        } else {
            document.getElementById('detail_stocks_container').style.display = 'none';
        }

        openLocationModal('detailModal');
    }

    function showLocationEdit(loc) {
        document.getElementById('edit_id').value = loc.id;
        document.getElementById('edit_kode').value = loc.kode;
        document.getElementById('edit_nama').value = loc.nama;
        document.getElementById('edit_deskripsi').value = loc.deskripsi || '';
        document.getElementById('edit_tipe_material').value = loc.tipe_material || '';
        document.getElementById('edit_is_scrap').value = loc.is_scrap ? '1' : '0';
        openLocationModal('editModal');
    }

    // Close modals on clicking overlay
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('flex');
            event.target.classList.add('hidden');
        }
    }
</script>
@endpush
