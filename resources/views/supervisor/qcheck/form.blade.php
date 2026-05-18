@extends('layouts.supervisor')

@section('title', $qc ? 'Edit Q-Check' : 'Tambah Q-Check')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-3xl mx-auto">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">{{ $qc ? 'Edit' : 'Tambah' }} Q-Check</h5>
        <p class="text-sm text-gray-500 mt-1">Item: {{ $detail_job->id_itemproduksi->job_number }}</p>
    </div>
    
    <div class="p-5">
        <form method="POST" action="#">
            @csrf
            @if($qc)
                @method('PUT')
            @endif
            
            <input type="hidden" name="id_detailjob" value="{{ $detail_job->id_detailjob }}">

            <div class="mb-5">
                <label for="jenis_qcheck" class="block text-sm font-medium text-gray-700 mb-1">Jenis Q-Check</label>
                <select name="jenis_qcheck" id="jenis_qcheck" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                    <option value="" disabled selected>-- Pilih Jenis --</option>
                    @foreach ($qc_types as $type)
                        <option value="{{ $type[0] }}" {{ ($qc->jenis_qcheck ?? '') == $type[0] ? 'selected' : '' }}>
                            {{ $type[1] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai</label>
                    <input type="time" name="start_time" id="start_time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $qc ? \Carbon\Carbon::parse($qc->start_time)->format('H:i') : '' }}" required>
                </div>
                <div>
                    <label for="finish_time" class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
                    <input type="time" name="finish_time" id="finish_time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $qc && $qc->finish_time ? \Carbon\Carbon::parse($qc->finish_time)->format('H:i') : '' }}">
                </div>
            </div>

            <div class="mb-5">
                <label for="hasil_qcheck" class="block text-sm font-medium text-gray-700 mb-1">Hasil Q-Check</label>
                <select name="hasil_qcheck" id="hasil_qcheck" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                    <option value="OK" {{ ($qc->hasil_qcheck ?? '') == 'OK' ? 'selected' : '' }}>OK</option>
                    <option value="NG" {{ ($qc->hasil_qcheck ?? '') == 'NG' ? 'selected' : '' }}>NG (Reject)</option>
                    <option value="Karantina" {{ ($qc->hasil_qcheck ?? '') == 'Karantina' ? 'selected' : '' }}>Karantina</option>
                </select>
            </div>

            <div class="mb-8">
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Temuan (Opsional)</label>
                <textarea name="keterangan" id="keterangan" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Catatan tambahan hasil pengecekan...">{{ $qc->keterangan ?? '' }}</textarea>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Simpan</button>
                <a href="{{ route('supervisor.qcheck.list', $detail_job->id_detailjob) }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
