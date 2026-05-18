@extends('layouts.supervisor')

@section('title', 'Handwork')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Mulai Pencatatan Handwork</h5>
    </div>
    <div class="p-5">
        <p class="text-gray-600 mb-4">Pilih Line dan Shift untuk menampilkan daftar item yang akan di-Handwork.</p>
        
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
        <h5 class="text-xl font-bold text-gray-800">History Handwork</h5>
    </div>
    <div class="p-5">
        <form method="GET" action="{{ route('supervisor.handwork.index') }}">
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Problem</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Input</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($handwork_history as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item->id_handwork->id_detailjob->id_itemproduksi->job_number ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item->problem_hw }}</td>
                        <td class="px-4 py-3 text-center text-sm">
                            @if ($item->is_ok)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">OK</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Reject</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 bg-gray-50/50">Tidak ada history untuk filter yang dipilih.</td>
                    </tr>
                    @endforelse
                </tbody>
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
            
            if (lineId && shift) {
                // Mocking fetch response
                let jobListContainer = document.getElementById('job-list-container');
                jobListContainer.innerHTML = `
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="bx bx-info-circle text-blue-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">Pencarian Handwork untuk Line ${lineId} dan Shift ${shift}</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white shadow-sm">
                        <a href="{{ route('supervisor.handwork.rekap', 1) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-gray-800">Job: 101 - Part: ABC-123</p>
                                <p class="text-xs text-gray-500 mt-1">Pending items: 5</p>
                            </div>
                            <i class="bx bx-chevron-right text-gray-400 text-xl"></i>
                        </a>
                        <a href="{{ route('supervisor.handwork.rekap', 2) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-gray-800">Job: 102 - Part: DEF-456</p>
                                <p class="text-xs text-gray-500 mt-1">Pending items: 2</p>
                            </div>
                            <i class="bx bx-chevron-right text-gray-400 text-xl"></i>
                        </a>
                    </div>
                `;
            }
        });
    }
});
</script>
@endsection
