@extends('layouts.supervisor')

@section('title', 'Data Break Time')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 border-b pb-4">
        <h4 class="text-xl font-bold text-gray-800">Data Jadwal Istirahat</h4>
        <div class="flex gap-2 w-full md:w-auto">
            <a href="{{ route('supervisor.job.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-md shadow-sm transition-colors text-center w-full md:w-auto">
                <i class="bx bx-arrow-back mr-1"></i> Kembali ke Daftar Job
            </a>
            <a href="{{ route('supervisor.breaktime.create') }}" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors text-center w-full md:w-auto">
                <i class="bx bx-plus mr-1"></i> Tambah Jadwal Baru
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Istirahat</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Mulai</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Selesai</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($semua_break as $break)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $break->nama_istirahat }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700">{{ $break->shift }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700">{{ $break->get_hari_display ?? 'Setiap Hari' }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700">{{ \Carbon\Carbon::parse($break->waktu_mulai)->format('H:i') }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700">{{ \Carbon\Carbon::parse($break->waktu_selesai)->format('H:i') }}</td>
                    <td class="px-4 py-3 text-center text-sm font-medium">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('supervisor.breaktime.update', $break->id) }}" class="bg-emerald-500 hover:bg-emerald-600 text-white py-1 px-3 rounded shadow-sm transition-colors text-xs">Update</a>
                            <form action="{{ route('supervisor.breaktime.delete', $break->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded shadow-sm transition-colors text-xs">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 bg-gray-50/50">Belum ada jadwal istirahat yang ditambahkan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
