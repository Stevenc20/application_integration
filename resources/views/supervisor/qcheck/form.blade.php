@extends('layouts.supervisor')

@section('title', isset($qc) ? 'Edit Q-Check' : 'Tambah Q-Check')
@section('header_title', isset($qc) ? 'Edit Q-Check' : 'Tambah Q-Check')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h5 class="text-lg font-bold text-gray-800">{{ isset($qc) ? 'Edit' : 'Tambah' }} Q Check</h5>
                <p class="text-sm text-gray-500 mt-1">Job {{ $detail_job->id_itemproduksi->job_number ?? ($qc->id_detailjob->id_itemproduksi->job_number ?? 'PART-XYZ') }}</p>
            </div>
            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-blue-200">Quality Control</span>
        </div>
        
        <div class="p-6">
            <form method="POST" action="#" class="space-y-6">
                @csrf
                
                <div class="space-y-1">
                    <label for="jenis_qcheck" class="block text-sm font-medium text-gray-700">Jenis Q Check</label>
                    <select name="jenis_qcheck" id="jenis_qcheck" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                        <option value="">-- Pilih Jenis Q Check --</option>
                        @foreach($qc_types ?? [] as $code => $label)
                            <option value="{{ $code }}" {{ isset($qc) && $qc->jenis_qcheck == $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                        <!-- Template fallback -->
                        @if(empty($qc_types))
                            <option value="first_check">First Check</option>
                            <option value="middle_check">Middle Check</option>
                            <option value="last_check">Last Check</option>
                        @endif
                    </select>
                </div>

                <div class="space-y-1">
                    <label for="hasil_qcheck" class="block text-sm font-medium text-gray-700">Hasil Q Check</label>
                    <input type="text" name="hasil_qcheck" id="hasil_qcheck" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required placeholder="Contoh: OK / NG" value="{{ $qc->hasil_qcheck ?? '' }}">
                </div>

                <div class="space-y-1">
                    <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Keterangan tambahan (opsional)">{{ $qc->keterangan ?? '' }}</textarea>
                </div>

                <div class="pt-6 border-t border-gray-200 flex justify-end gap-3">
                    <a href="{{ route('supervisor.qcheck.list', $detail_job->id_detailjob ?? ($qc->id_detailjob->id_detailjob ?? 1)) ?? '#' }}" class="px-6 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm transition-colors">Kembali</a>
                    <button type="submit" class="px-6 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                        {{ isset($qc) ? 'Update' : 'Simpan & Mulai Timer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
