@extends('layouts.supervisor')

@section('title', 'Status Q-Check')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg flex justify-between items-center">
        <div>
            <h5 class="text-xl font-bold text-gray-800">Status Q-Check</h5>
            <p class="text-sm text-gray-500 mt-1">Item: {{ $detail_job->id_itemproduksi->job_number }}</p>
        </div>
        <a href="{{ route('supervisor.qcheck.select') }}?line=1&shift=1" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md shadow-sm transition-colors text-sm flex items-center">
            <i class="bx bx-arrow-back mr-1"></i> Kembali
        </a>
    </div>

    <div class="p-5">
        <div class="flex justify-end mb-4">
            <a href="{{ route('supervisor.qcheck.form') }}?detail_job_id={{ $detail_job->id_detailjob }}" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors text-sm flex items-center">
                <i class="bx bx-plus mr-1"></i> Tambah Q-Check
            </a>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Q-Check</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hasil</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Mulai</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Selesai</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi (Menit)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Mock Data -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">First Piece Check</td>
                        <td class="px-4 py-3 text-center text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">OK</span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">08:00:00</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">08:15:00</td>
                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">15.00</td>
                        <td class="px-4 py-3 text-center text-sm font-medium">
                            <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="bx bx-edit text-lg"></i></a>
                            <a href="#" class="text-red-600 hover:text-red-900"><i class="bx bx-trash text-lg"></i></a>
                        </td>
                    </tr>
                    @forelse ($qcheck_status as $qc)
                    <!-- Real logic here -->
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
