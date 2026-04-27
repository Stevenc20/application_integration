@extends('layouts.supervisor')
@section('title', 'Pilih Job Handwork')
@section('header_title', 'Pilih Job untuk Handwork')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 p-6">
    <h4 class="text-xl font-bold text-gray-800 mb-4">Pilih Job untuk Mencatat Handwork</h4>

    <form method="GET" class="mb-6">
        <div class="flex items-end max-w-sm">
            <div class="w-2/3">
                <input type="date" name="tanggal" class="w-full rounded-l-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $selected_date_str ?? date('Y-m-d') }}">
            </div>
            <div class="w-1/3">
                <button type="submit" class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-r-md shadow-sm transition-colors border border-gray-600 hover:border-gray-700 h-[42px] flex items-center justify-center">Filter</button>
            </div>
        </div>
    </form>
    
    <hr class="mb-6 border-gray-200">

    <form method="POST" action="#">
        @csrf
        <div class="mb-6">
            <label for="detailjob-select" class="block text-sm font-medium text-gray-700 mb-2">Pilih Item dari Job yang Aktif:</label>
            <select name="detailjob_id" id="detailjob-select" class="w-full max-w-2xl rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                <option value="" disabled selected>--- Pilih salah satu ---</option>
                @foreach($semua_detailjob ?? [] as $dj)
                    <option value="{{ $dj->id_detailjob }}">
                        JOB{{ $dj->id_job->id_job ?? 'XXX' }} - Item: {{ $dj->id_itemproduksi->job_number ?? 'PART-X' }} (Line: {{ $dj->id_job->id_productionline->namaline ?? 'Line A' }})
                    </option>
                @endforeach
                <!-- Template fallback -->
                @if(empty($semua_detailjob))
                    <option value="1">JOB123 - Item: PART-A (Line: Line A)</option>
                    <option value="2">JOB124 - Item: PART-B (Line: Line B)</option>
                @endif
            </select>
        </div>

        @if(empty($semua_detailjob) && !isset($is_template))
            <!-- In a real app, logic would show/hide this. Using template dummy check -->
            <div class="p-4 mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md text-sm flex items-center">
                <i class="bx bx-info-circle mr-2 text-lg"></i> Tidak ada job yang aktif pada tanggal yang dipilih.
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Lanjutkan ke Form Handwork
            </button>
            <a href="{{ route('supervisor.job.index') ?? '#' }}" class="px-6 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm transition-colors">Batal</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h5 class="text-lg font-bold text-gray-800">History Handwork ({{ $selected_date_str ?? date('Y-m-d') }})</h5>
    </div>
    
    <div class="overflow-x-auto p-4">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-200 text-gray-700">
                    <th class="py-3 px-4 text-center font-semibold text-sm rounded-tl-md">ID Detail Job</th>
                    <th class="py-3 px-4 font-semibold text-sm">Problem</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Status</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm rounded-tr-md">Waktu Dicatat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 border-x border-b border-gray-200">
                @forelse($history_handwork ?? [] as $item)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4 text-center text-sm font-medium text-gray-900">{{ $item->id_handwork->id_detailjob->id_detailjob ?? 'DTL-123' }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $item->problem_hw ?? '-' }}</td>
                    <td class="py-3 px-4 text-center">
                        @if(isset($item->is_ok) && $item->is_ok)
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-200">OK</span>
                        @else
                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-red-200">REJECT</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-8 px-4 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="bx bx-history text-4xl text-gray-300 mb-2"></i>
                            <p>Tidak ada history handwork untuk tanggal ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
