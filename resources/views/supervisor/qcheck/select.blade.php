@extends('layouts.supervisor')

@section('title', 'Pilih Item Q-Check')
@section('header_title', 'Pilih Item Q-Check')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h5 class="text-lg font-bold text-gray-800">Pilih Item untuk Dicatat Q-Check</h5>
    </div>
    <div class="p-6">
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-md text-sm flex items-center">
            <i class="bx bx-info-circle mr-2 text-lg"></i>
            <div>
                Menampilkan item untuk Line: <strong class="font-bold text-blue-900">{{ $selected_line->namaline ?? 'Line Terpilih' }}</strong> - Shift: <strong class="font-bold text-blue-900">{{ $selected_shift ?? '1' }}</strong> pada tanggal hari ini.
            </div>
        </div>

        <div class="overflow-x-auto border border-gray-200 rounded-md">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-700">
                        <th class="py-3 px-4 font-semibold text-sm">ID Detail Job</th>
                        <th class="py-3 px-4 font-semibold text-sm">Item/Part Number</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($items ?? [] as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-sm font-medium text-gray-900">{{ $item->id_detailjob ?? 'DTL-001' }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $item->id_itemproduksi->job_number ?? 'PART-X' }}</td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('supervisor.qcheck.list', $item->id_detailjob ?? 1) ?? '#' }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-md shadow-sm transition-colors inline-flex items-center">
                                <i class="bx bx-timer mr-1"></i> Catat Waktu Q-Check
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-8 px-4 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="bx bx-x-circle text-4xl text-gray-300 mb-2"></i>
                                <p>Tidak ada item yang aktif untuk Line dan Shift ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                    <!-- Template fallback -->
                    @if(empty($items))
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-sm font-medium text-gray-900">DTL-123</td>
                        <td class="py-3 px-4 text-sm text-gray-600">PART-A</td>
                        <td class="py-3 px-4 text-center">
                            <a href="#" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-md shadow-sm transition-colors inline-flex items-center">
                                <i class="bx bx-timer mr-1"></i> Catat Waktu Q-Check
                            </a>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('supervisor.qcheck.index') ?? '#' }}" class="px-6 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm transition-colors inline-flex items-center">
                <i class="bx bx-arrow-back mr-2"></i> Kembali
            </a>
        </div>
    </div>
</div>
@endsection
