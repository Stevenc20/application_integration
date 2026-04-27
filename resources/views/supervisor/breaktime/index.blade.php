@extends('layouts.supervisor')
@section('title', 'Data Break Time')
@section('header_title', 'Data Break Time')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 md:p-6 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h4 class="text-xl font-bold text-gray-800">Data Jadwal Istirahat</h4>

        <div class="flex items-center gap-2">
            <a href="{{ route('supervisor.job.index') ?? '#' }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md shadow-sm transition-colors flex items-center justify-center">
                <i class="bx bx-arrow-back mr-2"></i> Kembali ke Daftar Job
            </a>
            <a href="{{ route('supervisor.breaktime.create') ?? '#' }}" class="px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors flex items-center justify-center">
                <i class="bx bx-plus mr-1"></i> Tambah Jadwal Baru
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-red-50 border-b border-red-100 text-red-900">
                    <th class="py-3 px-4 font-semibold text-sm">Nama Istirahat</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Shift</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Hari</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Waktu Mulai</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Waktu Selesai</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($semua_break ?? [] as $break)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4 text-sm font-medium text-gray-900">{{ $break->nama_istirahat }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $break->shift }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $break->hari_display ?? 'Setiap Hari' }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ \Carbon\Carbon::parse($break->waktu_mulai)->format('H:i') }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ \Carbon\Carbon::parse($break->waktu_selesai)->format('H:i') }}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="#" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors shadow-sm">Update</a>
                            <a href="#" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors shadow-sm">Delete</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="bx bx-coffee text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada jadwal istirahat yang ditambahkan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
                <!-- Template example -->
                @if(empty($semua_break))
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4 text-sm font-medium text-gray-900">Makan Siang</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">1</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">Setiap Hari</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">12:00</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">13:00</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="#" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors shadow-sm">Update</a>
                            <a href="#" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors shadow-sm">Delete</a>
                        </div>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
