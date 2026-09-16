@extends('layouts.app')

@section('title', 'Daftar Vendor')



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
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Vendor</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Kelola data master vendor proses makloon dan coil center bahan baku</p>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col p-6">
            {{-- Toolbar Header --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                <form action="{{ route('vendors.index') }}" method="GET" class="flex items-center gap-2 w-full lg:w-auto flex-wrap">
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full lg:max-w-[240px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode / nama..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px]">Cari</button>
                    @if($search)
                        <a href="{{ route('vendors.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                    @endif
                </form>

                {{-- Right: Actions buttons --}}
                <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto">
                    <a href="{{ route('vendors.export', ['search' => $search]) }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-emerald-200">
                        <span class="material-icons">file_download</span> Export
                    </a>
                    <a href="{{ route('vendors.template') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-slate-200">
                        <span class="material-icons">receipt_long</span> Template
                    </a>
                    <button type="button" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs shadow-sm border border-slate-200" onclick="openVendorModal('importModal')">
                        <span class="material-icons">upload</span> Import
                    </button>
                    <a href="{{ route('vendors.print_pdf', ['search' => $search]) }}" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-rose-200">
                        <span class="material-icons">print</span> Print PDF
                    </a>
                    <button type="button" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs shadow-sm border border-slate-200"  onclick="openVendorModal('addModal')">
                        <span class="material-icons">add</span> Add Vendor
                    </button>
                </div>
            </div>
        </div>
        {{-- Table wrap --}}
            <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                            <th style="width: 50px; text-align: center;">No.</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Kode</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Nama</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Tipe</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Alamat</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Kontak</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Email</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Telepon</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Status</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($vendors as $index => $vendor)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $vendors->firstItem() + $index }}</td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center"><span class="vendor-code">{{ $vendor->kode }}</span></td>
                            <td style="font-weight: 700; color: #333; text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $vendor->nama }}</td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">
                                @if(str_contains(strtolower($vendor->tipe), 'coil'))
                                    <span class="bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase border border-purple-200">{{ $vendor->tipe }}</span>
                                @elseif(str_contains(strtolower($vendor->tipe), 'process') || str_contains(strtolower($vendor->tipe), 'makloon'))
                                    <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase border border-blue-200">{{ $vendor->tipe }}</span>
                                @else
                                    <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase border border-slate-200">{{ $vendor->tipe }}</span>
                                @endif
                            </td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $vendor->alamat ?? '-' }}</td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $vendor->kontak ?? '-' }}</td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $vendor->email ?? '-' }}</td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $vendor->telepon ?? '-' }}</td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">
                                <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase border border-emerald-200">
                                    {{ $vendor->status }}
                                </span>
                            </td>
                            <td style="text-align: center;" class="py-3 px-4 text-xs font-medium text-slate-600 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="javascript:void(0)" class="bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 px-2 py-1 rounded text-[10px] font-bold transition-colors" data-item="{{ json_encode($vendor) }}" onclick="showVendorDetail(JSON.parse(this.dataset.item))">Detail</a>
                                    <a href="javascript:void(0)" class="bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 px-2 py-1 rounded text-[10px] font-bold transition-colors" data-item="{{ json_encode($vendor) }}" onclick="showVendorEdit(JSON.parse(this.dataset.item))">Edit</a>
                                    <button type="button" class="bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 px-2 py-1 rounded text-[10px] font-bold transition-colors" data-url="{{ route('vendors.destroy', $vendor->id) }}" data-name="{{ $vendor->nama }}" onclick="showVendorDeleteModal(this.dataset.url, this.dataset.name)">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td colspan="10" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <span class="material-icons" style="font-size: 40px; display: block; margin-bottom: 8px;">search_off</span>
                                Tidak ada data vendor ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($vendors->total() > 0 && $vendors->lastPage() > 1)
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    Menampilkan <strong>{{ $vendors->firstItem() ?? 0 }}-{{ $vendors->lastItem() ?? 0 }}</strong> dari <strong>{{ $vendors->total() }}</strong> vendor
                </div>
                
                <div class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if($vendors->onFirstPage())
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-100 text-slate-300 text-xs font-medium cursor-not-allowed"><span class="material-icons">chevron_left</span></span>
                    @else
                        <a href="{{ $vendors->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors"><span class="material-icons">chevron_left</span></a>
                    @endif

                    {{-- Pagination Pages --}}
                    @php
                        $start = max(1, $vendors->currentPage() - 2);
                        $end = min($vendors->lastPage(), $vendors->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $vendors->url(1) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors">1</a>
                        @if($start > 2)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $vendors->currentPage())
                            <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-600 bg-red-600 text-white text-xs font-medium">{{ $page }}</span>
                        @else
                            <a href="{{ $vendors->url($page) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $vendors->lastPage())
                        @if($end < $vendors->lastPage() - 1)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                        <a href="{{ $vendors->url($vendors->lastPage()) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors">{{ $vendors->lastPage() }}</a>
                    @endif

                    {{-- Next Page Link --}}
                    @if($vendors->hasMorePages())
                        <a href="{{ $vendors->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-rose-50 hover:text-red-600 hover:border-red-200 transition-colors"><span class="material-icons">chevron_right</span></a>
                    @else
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-100 text-slate-300 text-xs font-medium cursor-not-allowed"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- MODAL: TAMBAH VENDOR --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="addModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">add_circle</span> Tambah Vendor Baru</h3>
            <form action="{{ route('vendors.store') }}" method="POST">
                @csrf   
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode Vendor <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="add_kode" placeholder="Contoh: 1202208" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status <span style="color: red;">*</span></label>
                        <select name="status" id="add_status" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Vendor <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="add_nama" placeholder="Contoh: PT. TRI CENTRUM FORTUNA" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tipe Vendor <span style="color: red;">*</span></label>
                    <select name="tipe" id="add_tipe" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        <option value="Process / Makloon">Process / Makloon</option>
                        <option value="Coil Center (Supplier Bahan Baku)">Coil Center (Supplier Bahan Baku)</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Alamat</label>
                    <input type="text" name="alamat" id="add_alamat" placeholder="-" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Contact Person</label>
                    <input type="text" name="kontak" id="add_kontak" placeholder="-" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Email</label>
                        <input type="email" name="email" id="add_email" placeholder="-" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Telepon</label>
                        <input type="text" name="telepon" id="add_telepon" placeholder="-" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                    <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeVendorModal('addModal')">Batal</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EDIT VENDOR --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="editModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">edit</span> Edit Vendor</h3>
            <form action="{{ route('vendors.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode Vendor <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="edit_kode" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status <span style="color: red;">*</span></label>
                        <select name="status" id="edit_status" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Vendor <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tipe Vendor <span style="color: red;">*</span></label>
                    <select name="tipe" id="edit_tipe" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        <option value="Process / Makloon">Process / Makloon</option>
                        <option value="Coil Center (Supplier Bahan Baku)">Coil Center (Supplier Bahan Baku)</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Alamat</label>
                    <input type="text" name="alamat" id="edit_alamat" placeholder="-" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="flex flex-col gap-1.5" style="margin-bottom: 12px;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Contact Person</label>
                    <input type="text" name="kontak" id="edit_kontak" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Email</label>
                        <input type="email" name="email" id="edit_email" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Telepon</label>
                        <input type="text" name="telepon" id="edit_telepon" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                    <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeVendorModal('editModal')">Batal</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: DETAIL VENDOR --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="detailModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">info</span> Detail Vendor</h3>
            <div style="margin-bottom: 20px;">
                <table class="w-full border-collapse text-sm">
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Kode Vendor</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_kode" style="font-family: monospace; color: var(--red-main);"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Nama Vendor</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_nama"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Tipe Vendor</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_tipe"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Alamat</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_alamat"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Contact Person</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_kontak"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Email</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_email"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">No. Telepon</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_telepon"></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-500 w-[35%] border-b border-slate-100">Status</td>
                        <td class="py-3 px-4 font-bold text-slate-800 border-b border-slate-100" id="detail_status"></td>
                    </tr>
                </table>
            </div>
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeVendorModal('detailModal')" style="width: 100%;">Tutup</button>
            </div>
        </div>
    </div>

    {{-- MODAL: IMPORT EXCEL --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center overflow-y-auto p-4 modal-overlay" id="importModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl" style="max-width: 440px;">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2"><span class="material-icons">upload_file</span> Import Excel</h3>
            <form action="{{ route('vendors.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="material-icons" style="font-size: 48px; color: var(--red-main);">upload_file</span>
                    <p style="font-size: 13px; color: #555; margin-top: 8px;">Upload template Excel yang telah diisi data vendor.</p>
                    <a href="{{ route('vendors.template') }}" style="font-size: 12px; color: var(--red-main); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 8px;">
                        <span class="material-icons" style="font-size: 16px;">download</span> Download Template Vendor
                    </a>
                </div>
                
                <div class="flex flex-col gap-1.5" style="margin-bottom: 0;">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pilih File Excel (.xlsx, .xls) <span style="color: red;">*</span></label>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required style="padding: 6px 10px;" class="border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                </div>
                
                <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                    <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm" onclick="closeVendorModal('importModal')">Batal</button>
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
                <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm w-full" onclick="closeVendorModal('deleteModal')">Batal</button>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm w-full">Ya, Hapus!</button>
            </form>
        </div>
    </div>

<script>
    function showVendorDeleteModal(url, name) {
        try {
            document.getElementById('deleteForm').action = url;
            document.getElementById('delete_item_name').innerText = name;
            openVendorModal('deleteModal');
        } catch(e) { alert("Error in showDeleteModal: " + e.message); }
    }

    function openVendorModal(id) {
        try {
            var el = document.getElementById(id);
            if(!el) { alert("Modal element " + id + " not found!"); return; }
            el.classList.remove('hidden');
            el.classList.add('flex');
        } catch(e) { alert("Error in openModal: " + e.message); }
    }
    
    function closeVendorModal(id) {
        document.getElementById(id).classList.remove('flex');
        document.getElementById(id).classList.add('hidden');
    }

    function showVendorDetail(vendor) {
        try {
            document.getElementById('detail_kode').innerText = vendor.kode;
            document.getElementById('detail_nama').innerText = vendor.nama;
            document.getElementById('detail_tipe').innerText = vendor.tipe;
            document.getElementById('detail_alamat').innerText = vendor.alamat || '-';
            document.getElementById('detail_kontak').innerText = vendor.kontak || '-';
            document.getElementById('detail_email').innerText = vendor.email || '-';
            document.getElementById('detail_telepon').innerText = vendor.telepon || '-';
            document.getElementById('detail_status').innerText = vendor.status;
            openVendorModal('detailModal');
        } catch(e) { alert("Error in showDetail: " + e.message); }
    }

    function showVendorEdit(vendor) {
        try {
            document.getElementById('edit_id').value = vendor.id;
            document.getElementById('edit_kode').value = vendor.kode;
            document.getElementById('edit_nama').value = vendor.nama;
            document.getElementById('edit_tipe').value = vendor.tipe;
            document.getElementById('edit_alamat').value = vendor.alamat || '';
            document.getElementById('edit_kontak').value = vendor.kontak || '';
            document.getElementById('edit_email').value = vendor.email || '';
            document.getElementById('edit_telepon').value = vendor.telepon || '';
            document.getElementById('edit_status').value = vendor.status;
            openVendorModal('editModal');
        } catch(e) { alert("Error in showEdit: " + e.message); }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('flex');
            event.target.classList.add('hidden');
        }
    }
</script>
@endpush
