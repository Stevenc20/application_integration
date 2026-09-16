@extends('layouts.supervisor')

@section('title', $break_obj ? 'Update Parameter Break' : 'Tambah Parameter Break')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-3xl mx-auto">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">{{ $break_obj ? 'Update' : 'Tambah' }} Parameter Break Time</h5>
    </div>

    <div class="p-5">
        <form method="POST" action="{{ $break_obj ? route('supervisor.breaktime.update', $break_obj->id) : route('supervisor.breaktime.store') }}">
            @csrf
            @if($break_obj)
                @method('PUT')
            @endif

            @if(!empty($useMaster))
                <div class="mb-5">
                    <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                    <input type="text" name="label" id="label" class="w-full rounded-md border-gray-300 shadow-sm" value="{{ old('label', $break_obj->label ?? '') }}" required placeholder="ISTIRAHAT SIANG">
                </div>

                <div class="mb-5">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                    <select name="type" id="type" class="w-full rounded-md border-gray-300 shadow-sm" required>
                        <option value="istirahat" @selected(old('type', $break_obj->type ?? '') === 'istirahat')>Istirahat</option>
                        <option value="cinkorak" @selected(old('type', $break_obj->type ?? '') === 'cinkorak')>Cinkorak</option>
                    </select>
                </div>
            @else
                <div class="mb-5">
                    <label for="nama_istirahat" class="block text-sm font-medium text-gray-700 mb-1">Nama Istirahat</label>
                    <input type="text" name="nama_istirahat" id="nama_istirahat" class="w-full rounded-md border-gray-300 shadow-sm" value="{{ old('nama_istirahat', $break_obj->nama_istirahat ?? 'Istirahat') }}" required>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label for="waktu_mulai" class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai</label>
                    <input type="time" name="waktu_mulai" id="waktu_mulai" class="w-full rounded-md border-gray-300 shadow-sm" value="{{ old('waktu_mulai', $break_obj ? \Carbon\Carbon::parse($break_obj->waktu_mulai)->format('H:i') : '') }}" required>
                </div>
                <div>
                    <label for="waktu_selesai" class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
                    <input type="time" name="waktu_selesai" id="waktu_selesai" class="w-full rounded-md border-gray-300 shadow-sm" value="{{ old('waktu_selesai', $break_obj ? \Carbon\Carbon::parse($break_obj->waktu_selesai)->format('H:i') : '') }}" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label for="shift" class="block text-sm font-medium text-gray-700 mb-1">Shift (kosong = semua)</label>
                    <input type="text" name="shift" id="shift" class="w-full rounded-md border-gray-300 shadow-sm" value="{{ old('shift', $break_obj->shift ?? '') }}" placeholder="Shift Pagi">
                </div>
                <div>
                    <label for="hari" class="block text-sm font-medium text-gray-700 mb-1">Hari</label>
                    <select name="hari" id="hari" class="w-full rounded-md border-gray-300 shadow-sm" required>
                        @foreach ($choices_hari as $choice)
                            <option value="{{ $choice[0] }}" @selected(old('hari', $break_obj->hari ?? 'semua') === $choice[0])>{{ $choice[1] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if(!empty($useMaster))
                <div class="mb-8">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', $break_obj->is_active ?? true))>
                        <span class="text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            @endif

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-6 rounded-md shadow-sm">Simpan</button>
                <a href="{{ route('supervisor.breaktime.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-6 rounded-md shadow-sm">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
