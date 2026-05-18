@extends('layouts.supervisor')

@section('title', 'Rekap Handwork')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h5 class="font-bold text-gray-800">Catat Hasil Handwork</h5>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-600 mb-4">Untuk Part Number: <strong class="text-gray-900">PN-12345 (MOCK)</strong></p>
                
                <form method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Masalah</label>
                        <textarea name="problem_hw" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" rows="3" placeholder="Deskripsikan masalah pada part..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sebelum Perbaikan</label>
                        <input type="file" name="foto_sebelum" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-primary-red hover:file:bg-red-100 transition-colors">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sesudah Perbaikan</label>
                        <input type="file" name="foto_sesudah" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-primary-red hover:file:bg-red-100 transition-colors">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Hasil</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" required>
                            <option value="is_ok">OK (Repair Berhasil)</option>
                            <option value="is_reject">NG (Reject)</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                        <input type="number" name="quantity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="1" min="1" required>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors">Simpan Catatan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="lg:col-span-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg flex justify-between items-center">
                <h5 class="font-bold text-gray-800">Rekap Handwork</h5>
                <div class="flex gap-2">
                    <a href="{{ route('supervisor.handwork.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-1 px-3 rounded text-sm shadow-sm transition-colors flex items-center">
                        <i class="bx bx-arrow-back mr-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="p-5">
                @if (session('success'))
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4 mb-6 text-center">
                    <div class="bg-green-50 rounded-lg p-4 border border-green-100 shadow-sm">
                        <h4 class="text-3xl font-bold text-green-600 mb-1">{{ $total_ok }}</h4>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 uppercase tracking-wider">TOTAL REPAIR OK</span>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4 border border-red-100 shadow-sm">
                        <h4 class="text-3xl font-bold text-red-600 mb-1">{{ $total_reject }}</h4>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 uppercase tracking-wider">TOTAL REJECT</span>
                    </div>
                </div>
                
                <hr class="border-gray-200 my-4">

                <h6 class="font-semibold text-gray-700 mb-3">Detail Catatan:</h6>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sebelum</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sesudah</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($handwork_items as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-center">
                                    @if ($item->is_ok)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">OK</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">NG</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $item->problem_hw ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if ($item->foto_sebelum)
                                        <a href="{{ $item->foto_sebelum->url }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">Lihat</a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if ($item->foto_sesudah)
                                        <a href="{{ $item->foto_sesudah->url }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">Lihat</a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form action="#" method="POST" class="inline" onsubmit="return confirm('Hapus catatan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 bg-gray-50/50">Belum ada data rekapan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
