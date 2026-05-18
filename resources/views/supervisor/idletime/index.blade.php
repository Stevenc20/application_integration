@extends('layouts.supervisor')

@section('title', 'Idle Time')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Mulai Pencatatan Idle Time</h5>
    </div>
    <div class="p-5">
        <p class="text-gray-600 mb-4">Pilih Line dan Shift untuk menampilkan daftar Job.</p>
        
        <form id="select-job-form">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
                    <label for="line-select" class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" id="line-select" name="line">
                        <option value="">Pilih Line</option>
                        @foreach ($top_dropdown_lines as $line)
                            <option value="{{ $line->id }}">{{ $line->namaline }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="shift-select" class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" id="shift-select" name="shift">
                        <option value="">Pilih Shift</option>
                        <option value="1">Shift 1</option>
                        <option value="2">Shift 2</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors">Tampilkan Item</button>
                </div>
            </div>
        </form>

        <div id="job-list-container" class="mt-6"></div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">History Idle Time</h5>
    </div>
    <div class="p-5">
        <form method="GET" action="{{ route('supervisor.idletime.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-4">
                <div class="md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="history_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $selected_history_date }}">
                </div>
                <div class="md:col-span-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                    <select name="history_line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                        <option value="">Semua Line</option>
                        @foreach ($history_dropdown_lines as $line)
                            <option value="{{ $line->id }}" {{ $line->id == $selected_history_line ? 'selected' : '' }}>
                                {{ $line->namaline }} - Shift {{ $line->shift }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors">Filter History</button>
                </div>
            </div>
        </form>
        
        <hr class="border-gray-200 my-4">
        
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item / Part Number</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Mulai</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Selesai</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi (Menit)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($idletime_history as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item->id_idle->id_detailjob->id_itemproduksi->job_number ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item->reason_idle }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ \Carbon\Carbon::parse($item->start_idle)->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item->finish_idle ? \Carbon\Carbon::parse($item->finish_idle)->format('d-m-Y H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-center text-gray-900 font-medium">{{ number_format($item->duration_idle, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 bg-gray-50/50">Tidak ada history untuk filter yang dipilih.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <th colspan="4" class="px-4 py-3 text-right text-sm font-bold text-gray-700">Total Durasi:</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-primary-red">{{ number_format($total_duration_history, 2) }} Menit</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('select-job-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const lineId = document.getElementById('line-select').value;
            const shift = document.getElementById('shift-select').value;
            
            // Mock URL for now
            const url = `/api/get_jobs?line=${lineId}&shift=${shift}`;

            if (lineId && shift) {
                // Mocking fetch response since API might not exist
                let jobListContainer = document.getElementById('job-list-container');
                jobListContainer.innerHTML = `
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="bx bx-info-circle text-blue-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">Pencarian untuk Line ${lineId} dan Shift ${shift}</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 rounded-md border border-gray-200">
                        <a href="#" class="block px-4 py-3 hover:bg-gray-50 transition-colors text-sm text-gray-700">Job A - Part 123</a>
                        <a href="#" class="block px-4 py-3 hover:bg-gray-50 transition-colors text-sm text-gray-700">Job B - Part 456</a>
                    </div>
                `;
            }
        });
    }
});
</script>
@endsection
