@extends('layouts.supervisor')

@section('title', 'Update Job')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-5 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
        <div>
            <h5 class="text-xl font-bold text-gray-800">Update Job</h5>
            <p class="text-sm text-gray-500 mt-1">Edit Data Job</p>
        </div>
    </div>

    <div class="p-5">
        <form action="{{ route('supervisor.job.update', $jobobj->id_job ?? $jobobj->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="max-w-3xl">
                <!-- Production Line -->
                <div class="mb-4 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                    <label class="block text-sm font-medium text-gray-700 w-full md:w-1/4" for="id_productionline">Production Line</label>
                    <div class="w-full md:w-3/4">
                        <select name="id_productionline" id="id_productionline" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            @foreach ($dataproductionline as $item)
                                <option value="{{ $item->id }}" {{ $item->id == ($jobobj->id_productionline->id ?? $jobobj->id_productionline) ? 'selected' : '' }}>
                                    {{ $item->namaline }} - Shift {{ $item->shift }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Karyawan -->
                <div class="mb-4 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                    <label class="block text-sm font-medium text-gray-700 w-full md:w-1/4" for="id_karyawan">Karyawan</label>
                    <div class="w-full md:w-3/4">
                        <select name="id_karyawan" id="id_karyawan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            @foreach ($datakaryawan as $item)
                                <option value="{{ $item->id_karyawan }}" {{ $item->id_karyawan == ($jobobj->id_karyawan->id_karyawan ?? $jobobj->id_karyawan) ? 'selected' : '' }}>
                                    {{ $item->nama_karyawan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tanggal -->
                <div class="mb-8 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                    <label class="block text-sm font-medium text-gray-700 w-full md:w-1/4">Tanggal</label>
                    <div class="w-full md:w-3/4">
                        <input type="date" name="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $tanggal }}" required>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                    <button type="submit" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Update</button>
                    <a href="{{ route('supervisor.job.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
