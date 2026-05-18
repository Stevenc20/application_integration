@extends('layouts.supervisor')

@section('title', 'Pilih Job untuk Handwork')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <h4 class="mb-4 text-xl font-bold text-gray-800 border-b pb-2">Pilih Job untuk Mencatat Handwork</h4>

    <form method="GET" class="mb-6">
        <div class="flex items-center max-w-md">
            <input type="date" name="tanggal" class="form-input rounded-l-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 flex-grow" value="{{ $selected_date_str }}">
            <button class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-r-md transition-colors shadow-sm" type="submit">Filter Tanggal</button>
        </div>
    </form>

    <form method="POST" action="#">
        @csrf
        <div class="mb-6">
            <label for="detailjob-select" class="block text-sm font-medium text-gray-700 mb-2">Pilih Item dari Job yang Aktif:</label>
            <select name="detailjob_id" id="detailjob-select" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                <option value="" disabled selected>--- Pilih salah satu ---</option>
                @forelse ($semua_detailjob as $dj)
                    <option value="{{ $dj->id_detailjob }}">
                        JOB{{ $dj->id_job }} - Item: {{ $dj->job_number }} (Line: {{ $dj->namaline }})
                    </option>
                @empty
                @endforelse
            </select>
            
            @if (empty($semua_detailjob))
                <div class="mt-2 bg-yellow-50 border-l-4 border-yellow-400 p-3">
                    <p class="text-sm text-yellow-700">Tidak ada job yang aktif pada tanggal yang dipilih.</p>
                </div>
            @endif
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed" {{ empty($semua_detailjob) ? 'disabled' : '' }}>
                Lanjutkan ke Form Handwork
            </button>
            <a href="{{ route('supervisor.job.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Batal</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-lg font-bold text-gray-800">History Handwork ({{ \Carbon\Carbon::parse($selected_date_str)->format('d M Y') }})</h5>
    </div>
    
    <div class="overflow-x-auto rounded-b-lg border-t-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ID Detail Job</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Problem</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Dicatat</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($history_handwork as $item)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">{{ $item->id_detailjob ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $item->problem_hw ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if ($item->is_ok)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">OK</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">REJECT</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-500">{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 bg-gray-50/50">Tidak ada history handwork untuk tanggal ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
