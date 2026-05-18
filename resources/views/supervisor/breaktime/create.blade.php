@extends('layouts.supervisor')

@section('title', $break_obj ? 'Update Jadwal Istirahat' : 'Tambah Jadwal Istirahat')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-3xl mx-auto">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">{{ $break_obj ? 'Update' : 'Tambah' }} Jadwal Istirahat</h5>
    </div>
    
    <div class="p-5">
        <form method="POST" action="{{ $break_obj ? route('supervisor.breaktime.update', $break_obj->id) : route('supervisor.breaktime.store') }}">
            @csrf
            @if($break_obj)
                @method('PUT')
            @endif
            
            <div class="mb-5">
                <label for="nama_istirahat" class="block text-sm font-medium text-gray-700 mb-1">Nama Istirahat</label>
                <input type="text" name="nama_istirahat" id="nama_istirahat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $break_obj->nama_istirahat ?? 'Istirahat' }}" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label for="waktu_mulai" class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai</label>
                    <input type="time" name="waktu_mulai" id="waktu_mulai" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $break_obj ? \Carbon\Carbon::parse($break_obj->waktu_mulai)->format('H:i') : '' }}" required>
                </div>
                <div>
                    <label for="waktu_selesai" class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
                    <input type="time" name="waktu_selesai" id="waktu_selesai" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $break_obj ? \Carbon\Carbon::parse($break_obj->waktu_selesai)->format('H:i') : '' }}" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                <div>
                    <label for="shift" class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                    <input type="number" name="shift" id="shift" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $break_obj->shift ?? '1' }}" required>
                </div>
                <div>
                    <label for="hari" class="block text-sm font-medium text-gray-700 mb-1">Hari Spesifik</label>
                    <select name="hari" id="hari" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                        <option value="">Berlaku Setiap Hari</option>
                        @foreach ($choices_hari as $choice)
                            <option value="{{ $choice[0] }}" {{ ($break_obj->hari ?? '') == $choice[0] ? 'selected' : '' }}>
                                {{ $choice[1] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Simpan</button>
                <a href="{{ route('supervisor.breaktime.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
