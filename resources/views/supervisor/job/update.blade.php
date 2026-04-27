@extends('layouts.supervisor')

@section('title', 'Update Job')
@section('header_title', 'Update Job')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h5 class="text-lg font-bold text-gray-800">Update Job</h5>
                <p class="text-sm text-gray-500 mt-1">Edit Data Job</p>
            </div>
            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-red-200">Data Job</span>
        </div>
        
        <div class="p-6">
            <form action="#" method="post" class="space-y-6">
                @csrf
                
                <!-- Production Line -->
                <div class="flex flex-col sm:flex-row sm:items-center">
                    <label class="sm:w-1/3 text-sm font-medium text-gray-700 mb-2 sm:mb-0" for="id_productionline">Production Line</label>
                    <div class="sm:w-2/3">
                        <select name="id_productionline" id="id_productionline" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            @foreach($dataproductionline ?? [] as $item)
                                <option value="{{ $item->id }}" {{ isset($jobobj) && $item->id == $jobobj->id_productionline->id ? 'selected' : '' }}>
                                    {{ $item->namaline }} - Shift {{ $item->shift }}
                                </option>
                            @endforeach
                            <!-- Fallback option for template -->
                            @if(empty($dataproductionline))
                                <option value="1">Line A - Shift 1</option>
                                <option value="2">Line B - Shift 2</option>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Karyawan -->
                <div class="flex flex-col sm:flex-row sm:items-center">
                    <label class="sm:w-1/3 text-sm font-medium text-gray-700 mb-2 sm:mb-0" for="id_karyawan">Karyawan</label>
                    <div class="sm:w-2/3">
                        <select name="id_karyawan" id="id_karyawan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            @foreach($datakaryawan ?? [] as $item)
                                <option value="{{ $item->id_karyawan }}" {{ isset($jobobj) && $item->id_karyawan == $jobobj->id_karyawan ? 'selected' : '' }}>
                                    {{ $item->nama_karyawan }}
                                </option>
                            @endforeach
                            <!-- Fallback option for template -->
                            @if(empty($datakaryawan))
                                <option value="1">John Doe</option>
                                <option value="2">Jane Smith</option>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Tanggal -->
                <div class="flex flex-col sm:flex-row sm:items-center">
                    <label class="sm:w-1/3 text-sm font-medium text-gray-700 mb-2 sm:mb-0" for="date">Tanggal</label>
                    <div class="sm:w-2/3">
                        <input type="date" id="date" name="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $tanggal ?? date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-200 flex justify-end gap-3">
                    <a href="{{ route('supervisor.job.index') ?? '#' }}" class="px-6 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm transition-colors">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
