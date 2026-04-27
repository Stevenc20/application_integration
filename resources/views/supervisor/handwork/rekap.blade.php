@extends('layouts.supervisor')

@section('title', 'Rekap Handwork')
@section('header_title', 'Rekap Handwork')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-12 gap-6">

    <!-- Form Input Handwork -->
    <div class="md:col-span-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden sticky top-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h5 class="text-lg font-bold text-red-900">Catat Hasil Handwork</h5>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-700 mb-6">Untuk Part Number: <strong class="text-primary-red">{{ $detail_job->id_itemproduksi->part_number ?? 'PART-XYZ' }}</strong></p>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Masalah</label>
                        <textarea name="problem_hw" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" rows="3" placeholder="Jelaskan masalah..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sebelum Perbaikan</label>
                        <input type="file" name="foto_sebelum" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-primary-red hover:file:bg-red-100 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Sesudah Perbaikan</label>
                        <input type="file" name="foto_sesudah" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-primary-red hover:file:bg-red-100 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Hasil</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            <option value="is_ok">OK (Repair Berhasil)</option>
                            <option value="is_reject">NG (Reject)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                        <input type="number" name="quantity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="1" min="1" required>
                    </div>
                    
                    <button type="submit" class="w-full mt-2 px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Simpan Catatan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Rekap dan History -->
    <div class="md:col-span-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-full">
            <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h5 class="text-lg font-bold text-gray-800">Rekap Handwork</h5>
                <div class="flex flex-col sm:flex-row gap-2">
                    <a href="{{ route('supervisor.input_harian') ?? '#' }}?tanggal={{ isset($detail_job) ? \Carbon\Carbon::parse($detail_job->id_job->date)->format('Y-m-d') : date('Y-m-d') }}" class="px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 text-sm font-medium rounded-md shadow-sm transition-colors flex items-center justify-center">
                        <i class="bx bx-spreadsheet mr-1"></i> Input Harian
                    </a>
                    <a href="{{ route('supervisor.rekap_detailjob', $detail_job->id_job->id_job ?? 1) ?? '#' }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 text-sm font-medium rounded-md shadow-sm transition-colors flex items-center justify-center">
                        <i class="bx bx-arrow-back mr-1"></i> Detail Job
                    </a>
                </div>
            </div>
            
            <div class="p-6">
                @if(session('messages'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
                        {{ session('messages') }}
                    </div>
                @endif

                <!-- Statistik -->
                <div class="flex flex-wrap justify-around text-center mb-6 gap-4">
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg min-w-[150px]">
                        <h4 class="text-3xl font-bold text-green-700 mb-1">{{ $total_ok ?? 0 }}</h4>
                        <span class="inline-block bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">TOTAL REPAIR OK</span>
                    </div>
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg min-w-[150px]">
                        <h4 class="text-3xl font-bold text-red-700 mb-1">{{ $total_reject ?? 0 }}</h4>
                        <span class="inline-block bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">TOTAL REJECT</span>
                    </div>
                </div>
                
                <hr class="my-6 border-gray-200">

                <h6 class="font-semibold text-gray-700 mb-4">Detail Catatan:</h6>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200 text-gray-700">
                                <th class="py-2 px-3 text-center font-semibold text-sm">Status</th>
                                <th class="py-2 px-3 font-semibold text-sm">Deskripsi</th>
                                <th class="py-2 px-3 text-center font-semibold text-sm">Sebelum</th>
                                <th class="py-2 px-3 text-center font-semibold text-sm">Sesudah</th>
                                <th class="py-2 px-3 text-center font-semibold text-sm">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($handwork_items ?? [] as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-2 px-3 text-center">
                                    @if(isset($item->is_ok) && $item->is_ok)
                                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded border border-green-200">OK</span>
                                    @else
                                        <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded border border-red-200">NG</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-sm text-gray-600">{{ $item->problem_hw ?? "-" }}</td>
                                <td class="py-2 px-3 text-center">
                                    @if(isset($item->foto_sebelum) && $item->foto_sebelum)
                                        <a href="{{ url($item->foto_sebelum) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline">Lihat</a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-center">
                                    @if(isset($item->foto_sesudah) && $item->foto_sesudah)
                                        <a href="{{ url($item->foto_sesudah) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline">Lihat</a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <a href="#" class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 border border-red-200 text-xs font-medium rounded transition-colors inline-block">Hapus</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-6 px-4 text-center text-gray-500 italic">Belum ada data catatan handwork.</td>
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
