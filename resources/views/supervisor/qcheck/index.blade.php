@extends('layouts.supervisor')

@section('title', 'Q-Check')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Mulai Pencatatan Q-Check</h5>
    </div>
    <div class="p-5">
        <p class="text-gray-600 mb-4">Pilih Line dan Shift untuk menampilkan daftar item yang akan di-Q-Check.</p>
        <form method="GET" action="{{ route('supervisor.qcheck.select') }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                <div class="md:col-span-5">
                    <label for="entry_line" class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                    <select id="entry_line" name="line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Line</option>
                        @foreach ($top_dropdown_lines as $line)
                            <option value="{{ $line->id }}">{{ $line->namaline }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-5">
                    <label for="entry_shift" class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                    <select id="entry_shift" name="shift" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Shift</option>
                        <option value="1">Shift 1</option>
                        <option value="2">Shift 2</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors">Tampilkan Item</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">History Q-Check</h5>
    </div>
    <div class="p-5">
        <form method="GET" action="{{ route('supervisor.qcheck.index') }}" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
                    <label for="history_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" id="history_date" name="history_date" value="{{ $selected_history_date }}">
                </div>
                <div class="md:col-span-5">
                    <label for="history_line" class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                    <select id="history_line" name="history_line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                        <option value="">Semua Line</option>
                        @foreach ($history_dropdown_lines as $line)
                        <option value="{{ $line->id }}" {{ $selected_history_line == $line->id ? 'selected' : '' }}>
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

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item/Part Number</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hasil</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Mulai</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Selesai</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi (Menit)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($qcheck_history as $qc)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $qc->id_detailjob->id_itemproduksi->job_number ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $qc->jenis_qcheck_display ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $qc->hasil_qcheck }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">{{ \Carbon\Carbon::parse($qc->start_time)->format('H:i:s') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">{{ $qc->finish_time ? \Carbon\Carbon::parse($qc->finish_time)->format('H:i:s') : '-' }}</td>
                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">{{ number_format($qc->duration, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 bg-gray-50/50">Belum ada history Q-Check untuk filter ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <th colspan="5" class="px-4 py-3 text-right text-sm font-bold text-gray-700">Total Durasi:</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-primary-red">{{ number_format($total_duration_history, 2) }} menit</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
