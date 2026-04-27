@extends('layouts.supervisor')

@section('title', 'Handwork Main')
@section('header_title', 'Handwork')

@section('content')
<div class="grid grid-cols-1 gap-6">

    <!-- Card: Mulai Pencatatan -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-bold text-gray-800">Mulai Pencatatan Handwork</h5>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">Pilih Line dan Shift untuk menampilkan daftar item yang akan di-Handwork.</p>
            
            <form id="select-job-form">
                <div class="flex flex-col md:flex-row md:items-end gap-4">
                    <div class="w-full md:w-1/3">
                        <label for="line-select" class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                        <select id="line-select" name="line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                            <option value="">Pilih Line</option>
                            @foreach($top_dropdown_lines ?? [] as $line)
                                <option value="{{ $line->id }}">{{ $line->namaline }}</option>
                            @endforeach
                            <!-- Template fallback -->
                            @if(empty($top_dropdown_lines))
                                <option value="1">Line A</option>
                                <option value="2">Line B</option>
                            @endif
                        </select>
                    </div>
                    <div class="w-full md:w-1/3">
                        <label for="shift-select" class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                        <select id="shift-select" name="shift" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                            <option value="">Pilih Shift</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/3 flex gap-2">
                        <button type="submit" class="w-full px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Tampilkan Item</button>
                        <a href="{{ route('supervisor.handwork.select') ?? '#' }}" class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors text-center flex items-center justify-center">Select Job</a>
                    </div>
                </div>
            </form>

            <div id="job-list-container" class="mt-6">
                <!-- Data will be populated here via JS -->
            </div>
        </div>
    </div>

    <!-- Card: History Handwork -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-bold text-gray-800">History Handwork</h5>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('supervisor.handwork.index') ?? '#' }}">
                <div class="flex flex-col md:flex-row md:items-end gap-4">
                    <div class="w-full md:w-1/3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="history_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $selected_history_date ?? date('Y-m-d') }}">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                        <select name="history_line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                            <option value="">Semua Line</option>
                            @foreach($history_dropdown_lines ?? [] as $line)
                                <option value="{{ $line->id }}" {{ isset($selected_history_line) && $line->id == $selected_history_line ? 'selected' : '' }}>
                                    {{ $line->namaline }} - Shift {{ $line->shift }}
                                </option>
                            @endforeach
                            <!-- Template fallback -->
                            @if(empty($history_dropdown_lines))
                                <option value="1">Line A - Shift 1</option>
                            @endif
                        </select>
                    </div>
                    <div class="w-full md:w-1/3">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Filter History</button>
                    </div>
                </div>
            </form>
            
            <hr class="my-6 border-gray-200">
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-red-50 border-b border-red-100 text-red-900">
                            <th class="py-3 px-4 font-semibold text-sm">Item / Part Number</th>
                            <th class="py-3 px-4 font-semibold text-sm">Problem</th>
                            <th class="py-3 px-4 font-semibold text-sm text-center">Status</th>
                            <th class="py-3 px-4 font-semibold text-sm text-center">Waktu Input</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($handwork_history ?? [] as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 text-sm font-medium text-gray-900">{{ $item->id_handwork->id_detailjob->id_itemproduksi->job_number ?? 'JOB-XXX' }}</td>
                            <td class="py-3 px-4 text-sm text-gray-600">{{ $item->problem_hw ?? 'Problem description' }}</td>
                            <td class="py-3 px-4 text-center">
                                @if(isset($item->is_ok) && $item->is_ok)
                                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-200">OK</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-red-200">Reject</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center text-sm text-gray-600">{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 px-4 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="bx bx-wrench text-4xl text-gray-300 mb-2"></i>
                                    <p>Tidak ada history untuk filter yang dipilih.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
            const url = `/api/jobs?line=${lineId}&shift=${shift}`; // Placeholder API
            
            const jobListContainer = document.getElementById('job-list-container');
            
            if (lineId && shift) {
                jobListContainer.innerHTML = '<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin text-2xl text-primary-red"></i></div>';
                
                setTimeout(() => {
                    const data = {
                        jobs: [
                            { id: 1, name: 'JOB-001 (Part A)' },
                            { id: 2, name: 'JOB-002 (Part B)' }
                        ]
                    };
                    
                    if (data.jobs.length > 0) {
                        let html = '<div class="bg-white border border-gray-200 rounded-md shadow-sm overflow-hidden divide-y divide-gray-200">';
                        data.jobs.forEach(job => {
                            let jobUrl = `/supervisor/handwork/rekap/${job.id}`; // Placeholder URL
                            html += `
                                <a href="${jobUrl}" class="block px-4 py-3 hover:bg-red-50 transition-colors flex justify-between items-center group">
                                    <span class="text-sm font-medium text-gray-800 group-hover:text-primary-red">${job.name}</span>
                                    <i class="bx bx-chevron-right text-gray-400 group-hover:text-primary-red"></i>
                                </a>
                            `;
                        });
                        html += '</div>';
                        jobListContainer.innerHTML = html;
                    } else {
                        jobListContainer.innerHTML = '<div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md text-sm"><i class="bx bx-info-circle mr-2"></i>Tidak ada job yang dijadwalkan untuk line dan shift ini.</div>';
                    }
                }, 500);
            }
        });
    }
});
</script>
@endsection
