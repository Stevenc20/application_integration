@extends('layouts.app')

@section('title', 'Goods Receipt')

@section('content')
    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm mb-6 mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm mb-6 mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm mb-6 mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ $errors->first() }}</span>
    </div>
    @endif

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 mb-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Goods Receipt</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Kelola penerimaan pasokan barang masuk dari Purchase Order dan penempatan stok pada storage location</p>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Toolbar --}}
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col gap-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="text-sm font-black text-slate-700">Goods Receipt</div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('goods_receipts.export', ['search' => $search, 'start_date' => $startDate, 'end_date' => $endDate, 'vendor_id' => $vendorId, 'location_id' => $locationId]) }}" class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl px-3.5 py-2 text-xs font-bold transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg> Export Excel
                        </a>
                        <a href="{{ route('goods_receipts.print_pdf', ['search' => $search, 'start_date' => $startDate, 'end_date' => $endDate, 'vendor_id' => $vendorId, 'location_id' => $locationId]) }}" class="inline-flex items-center gap-1.5 bg-red-500 hover:bg-red-600 text-white rounded-xl px-3.5 py-2 text-xs font-bold transition-all">
                            <span class="material-icons text-sm">print</span> Print PDF
                        </a>
                        <button type="button" onclick="openGrModal('addModal')" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl px-3.5 py-2 text-xs font-bold transition-all">
                            <span class="material-icons text-sm">add</span> + Buat GR
                        </button>
                    </div>
                </div>

                {{-- Filters --}}
                <form action="{{ route('goods_receipts.index') }}" method="GET" class="flex items-center gap-2 flex-wrap">
                    <input type="text" name="search" value="{{ $search }}" placeholder="No. GR / No. PO..." class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 w-48 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 placeholder:text-slate-400">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 w-36 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    <input type="date" name="end_date" value="{{ $endDate }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 w-36 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    <select name="vendor_id" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 min-w-[140px] focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="Semua Vendor">Semua Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ $vendorId == $vendor->id ? 'selected' : '' }}>{{ $vendor->nama }}</option>
                        @endforeach
                    </select>
                    <select name="location_id" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 min-w-[140px] focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="Semua Lokasi">Semua Lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->nama }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center gap-1 bg-slate-800 hover:bg-slate-900 text-white rounded-xl px-3.5 py-2 text-xs font-bold transition-all">
                        <span class="material-icons text-sm">search</span>Cari
                    </button>
                    @if($search || $startDate || $endDate || ($vendorId && $vendorId !== 'Semua Vendor') || ($locationId && $locationId !== 'Semua Lokasi'))
                        <a href="{{ route('goods_receipts.index') }}" class="inline-flex items-center bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl px-3.5 py-2 text-xs font-bold transition-all">Kembali</a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">No. GR</th>
                            <th class="px-4 py-3 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">No. PO</th>
                            <th class="px-4 py-3 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">Vendor</th>
                            <th class="px-4 py-3 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">Tgl Terima</th>
                            <th class="px-4 py-3 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">Lokasi</th>
                            <th class="px-4 py-3 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                            <th class="px-4 py-3 text-center font-black text-slate-500 uppercase tracking-wider whitespace-nowrap w-[180px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($goodsReceipts as $gr)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="javascript:void(0)" onclick="showDetail({{ $gr->id }})" class="text-xs font-black text-blue-600 font-mono hover:text-blue-700 hover:underline">
                                    {{ $gr->no_gr }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-slate-700">{{ $gr->purchaseOrder->no_po ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-bold text-slate-600">{{ $gr->purchaseOrder->vendor->nama ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $gr->tanggal_terima->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $gr->storageLocation->nama ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($gr->status === 'drafted')
                                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border border-slate-300 bg-slate-100 text-slate-600">drafted</span>
                                @elseif($gr->status === 'posted')
                                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border border-emerald-300 bg-emerald-50 text-emerald-600">posted</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border border-slate-300 bg-slate-100 text-slate-600">{{ $gr->status }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="javascript:void(0)" onclick="showDetail({{ $gr->id }})" class="text-blue-600 hover:text-blue-700 font-bold text-xs hover:underline">Detail</a>
                                    <a href="javascript:void(0)" onclick="showEdit({{ $gr->id }})" class="text-amber-600 hover:text-amber-700 font-bold text-xs hover:underline">Edit</a>
                                    <form action="{{ route('goods_receipts.destroy', $gr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus GR {{ $gr->no_gr }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-600 font-bold text-xs hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <p class="text-sm font-semibold">Belum ada data Goods Receipt.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
                <div class="text-xs text-slate-500">
                    Menampilkan <strong class="text-slate-700">{{ $goodsReceipts->firstItem() ?? 0 }}</strong> hingga <strong class="text-slate-700">{{ $goodsReceipts->lastItem() ?? 0 }}</strong> dari <strong class="text-slate-700">{{ $goodsReceipts->total() }}</strong> Goods Receipt
                </div>
                @if($goodsReceipts->hasPages())
                    {{ $goodsReceipts->links('pagination::tailwind') }}
                @endif
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- MODAL: TAMBAH / BUAT GR --}}
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto p-5 hidden" id="addModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
                <span class="material-icons text-red-600 text-xl">add_circle</span> Buat Goods Receipt Baru
            </h3>
            <form action="{{ route('goods_receipts.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="add_no_gr" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nomor GR <span class="text-red-500">*</span></label>
                        <input type="text" name="no_gr" id="add_no_gr" placeholder="Contoh: GR-2026-00007" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="add_purchase_order_id" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Purchase Order (Opsional)</label>
                        <select name="purchase_order_id" id="add_purchase_order_id" onchange="loadPOItemsForAdd(this.value)" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="">Tanpa PO (Penerimaan Langsung)...</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->no_po }} ({{ $po->vendor->nama ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="add_tanggal_terima" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Terima <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_terima" id="add_tanggal_terima" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="add_storage_location_id" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Storage Location <span class="text-red-500">*</span></label>
                        <select name="storage_location_id" id="add_storage_location_id" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="">Pilih Lokasi...</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->kode }} - {{ $loc->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label for="add_status" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="add_status" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="posted">posted</option>
                        <option value="drafted">drafted</option>
                    </select>
                </div>

                {{-- Dynamic Items --}}
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 mt-4">
                    <div class="text-xs font-black text-slate-700 pb-2 mb-3 border-b border-slate-200 flex justify-between items-center">
                        <span>DAFTAR ITEM MATERIAL YANG DITERIMA</span>
                        <button type="button" onclick="addItemRow('add_items_list')" class="bg-slate-700 hover:bg-slate-800 text-white rounded-lg px-3 py-1 text-[10px] font-bold transition-colors">+ Tambah Baris</button>
                    </div>
                    <div id="add_items_list" class="space-y-2.5">
                        <div class="grid grid-cols-[3fr_2fr_40px] gap-2.5 items-center" id="add_row_0">
                            <select name="items[0][material_id]" required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                                <option value="">Pilih Material...</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->kode }} - {{ $material->nama }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" name="items[0][qty]" placeholder="Qty Diterima..." required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <button type="button" onclick="removeRow('add_row_0')" class="bg-red-100 text-red-600 hover:bg-red-200 w-7 h-7 rounded-lg flex items-center justify-center transition-colors">
                                <span class="material-icons text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeGrModal('addModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold transition-colors">Batal</button>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">Simpan GR</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EDIT GR --}}
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto p-5 hidden" id="editModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
                <span class="material-icons text-amber-500 text-xl">edit</span> Edit Goods Receipt
            </h3>
            <form action="{{ route('goods_receipts.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="edit_no_gr" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nomor GR <span class="text-red-500">*</span></label>
                        <input type="text" name="no_gr" id="edit_no_gr" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="edit_purchase_order_id" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Purchase Order (Opsional)</label>
                        <select name="purchase_order_id" id="edit_purchase_order_id" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="">Tanpa PO (Penerimaan Langsung)...</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->no_po }} ({{ $po->vendor->nama ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="edit_tanggal_terima" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Terima <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_terima" id="edit_tanggal_terima" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="edit_storage_location_id" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Storage Location <span class="text-red-500">*</span></label>
                        <select name="storage_location_id" id="edit_storage_location_id" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="">Pilih Lokasi...</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->kode }} - {{ $loc->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label for="edit_status" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="edit_status" required class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="posted">posted</option>
                        <option value="drafted">drafted</option>
                    </select>
                </div>

                {{-- Dynamic Items --}}
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 mt-4">
                    <div class="text-xs font-black text-slate-700 pb-2 mb-3 border-b border-slate-200 flex justify-between items-center">
                        <span>DAFTAR ITEM MATERIAL YANG DITERIMA</span>
                        <button type="button" onclick="addItemRow('edit_items_list')" class="bg-slate-700 hover:bg-slate-800 text-white rounded-lg px-3 py-1 text-[10px] font-bold transition-colors">+ Tambah Baris</button>
                    </div>
                    <div id="edit_items_list" class="space-y-2.5">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeGrModal('editModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold transition-colors">Batal</button>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: DETAIL GR --}}
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto p-5 hidden" id="detailModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
                <span class="material-icons text-blue-500 text-xl">info</span> Detail Goods Receipt
            </h3>
            <div class="mb-5">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 px-2 text-xs font-bold text-slate-500 w-[35%]">Nomor GR</td>
                        <td class="py-2.5 px-2 font-semibold text-slate-800 font-mono text-red-600" id="detail_no_gr"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 px-2 text-xs font-bold text-slate-500 w-[35%]">Nomor PO</td>
                        <td class="py-2.5 px-2 font-semibold text-slate-800" id="detail_no_po"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 px-2 text-xs font-bold text-slate-500 w-[35%]">Vendor</td>
                        <td class="py-2.5 px-2 font-semibold text-slate-800" id="detail_vendor"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 px-2 text-xs font-bold text-slate-500 w-[35%]">Tanggal Terima</td>
                        <td class="py-2.5 px-2 font-semibold text-slate-800" id="detail_tanggal_terima"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 px-2 text-xs font-bold text-slate-500 w-[35%]">Storage Location</td>
                        <td class="py-2.5 px-2 font-semibold text-slate-800" id="detail_location"></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 px-2 text-xs font-bold text-slate-500 w-[35%]">Status</td>
                        <td class="py-2.5 px-2 font-semibold text-slate-800" id="detail_status"></td>
                    </tr>
                </table>
            </div>

            {{-- Detail Items Table --}}
            <div class="border border-slate-200 rounded-xl p-4 bg-white">
                <div class="text-xs font-black text-slate-700 pb-2 mb-3 border-b border-slate-200">
                    RINCIAN MATERIAL DITERIMA
                </div>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-3 py-2 text-left font-bold text-slate-500">Kode</th>
                            <th class="px-3 py-2 text-left font-bold text-slate-500">Nama Material</th>
                            <th class="px-3 py-2 text-right font-bold text-slate-500">Qty Diterima</th>
                            <th class="px-3 py-2 text-center font-bold text-slate-500">UOM</th>
                        </tr>
                    </thead>
                    <tbody id="detail_items_table_body" class="divide-y divide-slate-100">
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-5 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeGrModal('detailModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-6 py-2 rounded-xl text-xs font-bold transition-colors w-full">Tutup</button>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
    let addRowIndex = 1;
    let editRowIndex = 0;

    const purchaseOrdersData = @json($purchaseOrders);

    function openGrModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeGrModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function addItemRow(containerId) {
        const container = document.getElementById(containerId);
        const prefix = containerId.startsWith('add') ? 'add' : 'edit';
        const index = prefix === 'add' ? addRowIndex : editRowIndex;
        const rowId = `${prefix}_row_${index}`;

        const rowHtml = `
            <div class="grid grid-cols-[3fr_2fr_40px] gap-2.5 items-center" id="${rowId}">
                <select name="items[${index}][material_id]" required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    <option value="">Pilih Material...</option>
                    @foreach($materials as $material)
                        <option value="{{ $material->id }}">{{ $material->kode }} - {{ $material->nama }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.001" name="items[${index}][qty]" placeholder="Qty Diterima..." required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                <button type="button" onclick="removeRow('${rowId}')" class="bg-red-100 text-red-600 hover:bg-red-200 w-7 h-7 rounded-lg flex items-center justify-center transition-colors">
                    <span class="material-icons text-sm">delete</span>
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', rowHtml);

        if (prefix === 'add') {
            addRowIndex++;
        } else {
            editRowIndex++;
        }
    }

    function removeRow(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
        }
    }

    function loadPOItemsForAdd(poId) {
        if (!poId) return;
        const po = purchaseOrdersData.find(p => p.id == poId);
        if (!po || !po.items) return;

        const container = document.getElementById('add_items_list');
        container.innerHTML = '';
        addRowIndex = 0;

        po.items.forEach(item => {
            const rowId = `add_row_${addRowIndex}`;
            const remainingQty = Math.max(0, item.qty - item.qty_received);
            const rowHtml = `
                <div class="grid grid-cols-[3fr_2fr_40px] gap-2.5 items-center" id="${rowId}">
                    <select name="items[${addRowIndex}][material_id]" required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="${item.material_id}" selected>${item.material.kode} - ${item.material.nama}</option>
                    </select>
                    <input type="number" step="0.001" name="items[${addRowIndex}][qty]" value="${remainingQty}" placeholder="Qty Diterima..." required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    <button type="button" onclick="removeRow('${rowId}')" class="bg-red-100 text-red-600 hover:bg-red-200 w-7 h-7 rounded-lg flex items-center justify-center transition-colors">
                        <span class="material-icons text-sm">delete</span>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
            addRowIndex++;
        });
    }

    function showDetail(grId) {
        fetch(`goods-receipts/${grId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('detail_no_gr').innerText = data.no_gr;
                document.getElementById('detail_no_po').innerText = data.no_po;
                document.getElementById('detail_vendor').innerText = data.vendor_nama;

                const dateParts = data.tanggal_terima.split('-');
                document.getElementById('detail_tanggal_terima').innerText = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;

                document.getElementById('detail_location').innerText = data.storage_location_nama;
                document.getElementById('detail_status').innerText = data.status;

                const tbody = document.getElementById('detail_items_table_body');
                tbody.innerHTML = '';

                data.items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-slate-100 last:border-b-0';
                    tr.innerHTML = `
                        <td class="px-3 py-2.5 font-mono font-bold text-blue-700 text-xs">${item.material_kode}</td>
                        <td class="px-3 py-2.5 text-slate-700 text-xs">${item.material_nama}</td>
                        <td class="px-3 py-2.5 text-right font-bold text-emerald-600 text-xs">${parseFloat(item.qty).toLocaleString('en-US', {minimumFractionDigits: 3, maximumFractionDigits: 3})}</td>
                        <td class="px-3 py-2.5 text-center text-slate-500 text-xs">${item.material_uom}</td>
                    `;
                    tbody.appendChild(tr);
                });

                openGrModal('detailModal');
            })
            .catch(err => {
                alert('Gagal memuat rincian GR.');
            });
    }

    function showEdit(grId) {
        fetch(`goods-receipts/${grId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_no_gr').value = data.no_gr;
                document.getElementById('edit_purchase_order_id').value = data.purchase_order_id || '';
                document.getElementById('edit_tanggal_terima').value = data.tanggal_terima;
                document.getElementById('edit_storage_location_id').value = data.storage_location_id;
                document.getElementById('edit_status').value = data.status;

                const container = document.getElementById('edit_items_list');
                container.innerHTML = '';
                editRowIndex = 0;

                data.items.forEach(item => {
                    const rowId = `edit_row_${editRowIndex}`;
                    const rowHtml = `
                        <div class="grid grid-cols-[3fr_2fr_40px] gap-2.5 items-center" id="${rowId}">
                            <select name="items[${editRowIndex}][material_id]" required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                                <option value="${item.material_id}" selected>${item.material_kode} - ${item.material_nama}</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->kode }} - {{ $material->nama }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" name="items[${editRowIndex}][qty]" value="${item.qty}" placeholder="Qty Diterima..." required class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <button type="button" onclick="removeRow('${rowId}')" class="bg-red-100 text-red-600 hover:bg-red-200 w-7 h-7 rounded-lg flex items-center justify-center transition-colors">
                                <span class="material-icons text-sm">delete</span>
                            </button>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', rowHtml);
                    editRowIndex++;
                });

                openGrModal('editModal');
            })
            .catch(err => {
                alert('Gagal memuat data edit GR.');
            });
    }

    document.addEventListener('click', function(e) {
        ['addModal', 'editModal', 'detailModal'].forEach(id => {
            const el = document.getElementById(id);
            if (e.target === el) {
                closeGrModal(id);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const poId = urlParams.get('po_id');
        if (poId) {
            openGrModal('addModal');
            const poSelect = document.getElementById('add_purchase_order_id');
            if (poSelect) {
                poSelect.value = poId;
                loadPOItemsForAdd(poId);
            }
        }
    });
</script>
@endpush
