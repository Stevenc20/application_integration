@extends('layouts.supervisor')

@section('title', isset($break_obj) ? 'Update Jadwal Istirahat' : 'Tambah Jadwal Istirahat')
@section('header_title', isset($break_obj) ? 'Update Jadwal Istirahat' : 'Tambah Jadwal Istirahat')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h5 class="text-lg font-bold text-gray-800">{{ isset($break_obj) ? 'Update' : 'Tambah' }} Jadwal Istirahat</h5>
            </div>
            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-red-200">Manajemen Waktu</span>
        </div>
        
        <div class="p-6">
            <form method="POST" action="#" class="space-y-6">
                @csrf
                
                <div class="space-y-1">
                    <label for="nama_istirahat" class="block text-sm font-medium text-gray-700">Nama Istirahat</label>
                    <input type="text" name="nama_istirahat" id="nama_istirahat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $break_obj->nama_istirahat ?? 'Istirahat' }}" required>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="waktu_mulai" class="block text-sm font-medium text-gray-700">Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" id="waktu_mulai" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ isset($break_obj) ? \Carbon\Carbon::parse($break_obj->waktu_mulai)->format('H:i') : '' }}" required>
                    </div>
                    <div class="space-y-1">
                        <label for="waktu_selesai" class="block text-sm font-medium text-gray-700">Waktu Selesai</label>
                        <input type="time" name="waktu_selesai" id="waktu_selesai" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ isset($break_obj) ? \Carbon\Carbon::parse($break_obj->waktu_selesai)->format('H:i') : '' }}" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="shift" class="block text-sm font-medium text-gray-700">Shift</label>
                        <input type="number" name="shift" id="shift" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $break_obj->shift ?? '1' }}" required>
                    </div>
                    <div class="space-y-1">
                        <label for="hari" class="block text-sm font-medium text-gray-700">Hari Spesifik</label>
                        <select name="hari" id="hari" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                            <option value="">Berlaku Setiap Hari</option>
                            @foreach($choices_hari ?? [] as $code => $name)
                            <option value="{{ $code }}" {{ isset($break_obj) && $code == $break_obj->hari ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                            @endforeach
                            <!-- Template fallback -->
                            @if(empty($choices_hari))
                                <option value="senin">Senin</option>
                                <option value="selasa">Selasa</option>
                                <option value="rabu">Rabu</option>
                                <option value="kamis">Kamis</option>
                                <option value="jumat">Jumat</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-200 flex justify-end gap-3">
                    <a href="{{ route('supervisor.breaktime.index') ?? '#' }}" class="px-6 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm transition-colors">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
