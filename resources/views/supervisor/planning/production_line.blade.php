@extends('layouts.supervisor')

@section('content')
<div class="p-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Production Line Management</h1>
            <p class="text-sm text-gray-500 mt-1">{{ now()->format('d F Y') }}</p>
        </div>
        <button onclick="openCreateModal()"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-500 hover:from-red-700 hover:to-rose-600 text-white font-semibold text-sm shadow-md shadow-red-200 transition-all flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Tambah Line
        </button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        {{-- Total --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Total Line</p>
                <h4 class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</h4>
            </div>
        </div>
        {{-- Active --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Active</p>
                <h4 class="text-2xl font-bold text-gray-800">{{ $stats['active'] }}</h4>
            </div>
        </div>
        {{-- Maintenance --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Maintenance</p>
                <h4 class="text-2xl font-bold text-gray-800">{{ $stats['maintenance'] }}</h4>
            </div>
        </div>
        {{-- Inactive --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Inactive</p>
                <h4 class="text-2xl font-bold text-gray-800">{{ $stats['inactive'] }}</h4>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            </div>
            <h2 class="font-semibold text-gray-800">Filter Data</h2>
        </div>
        <div class="p-5">
            <form id="filterForm" method="GET" action="{{ route('supervisor.planning.production_line') }}"
                  class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pencarian</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-sm bg-gray-50 focus:bg-white placeholder-gray-400 focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all" placeholder="Kode / Nama Line">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all appearance-none cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Shift</label>
                    <select name="shift" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all appearance-none cursor-pointer">
                        <option value="">Semua Shift</option>
                        <option value="Shift 1" {{ request('shift') == 'Shift 1' ? 'selected' : '' }}>Shift 1</option>
                        <option value="Shift 2" {{ request('shift') == 'Shift 2' ? 'selected' : '' }}>Shift 2</option>
                        <option value="Semua" {{ request('shift') == 'Semua' ? 'selected' : '' }}>Semua</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-900 text-white font-semibold text-sm transition-colors">
                        Terapkan
                    </button>
                    <a href="{{ route('supervisor.planning.production_line') }}" class="px-4 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm text-gray-600 font-medium transition-colors text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h2 class="font-semibold text-gray-800">Daftar Production Line</h2>
            <span class="text-sm font-medium text-gray-500 bg-white px-3 py-1 rounded-full border border-gray-200">{{ $lines->total() }} lines</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full text-sm">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Line</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Shift</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Kapasitas</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Mesin</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Plans (Aktif/Total)</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                @forelse($lines as $line)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-xs font-semibold text-gray-600 bg-gray-100 px-2.5 py-1 rounded-lg border border-gray-200">{{ $line->line_code }}</span>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-800">
                            {{ $line->line_name }}
                            @if($line->description)
                                <p class="text-xs text-gray-400 font-normal mt-0.5 truncate max-w-[200px]" title="{{ $line->description }}">{{ $line->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $line->shift }}
                        </td>
                        <td class="px-6 py-4 text-center text-gray-600 font-medium">
                            {{ number_format($line->capacity) }}
                        </td>
                        <td class="px-6 py-4 text-center text-gray-600">
                            {{ $line->machine_count ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1.5 text-xs font-semibold">
                                <span class="{{ $line->active_plans > 0 ? 'text-emerald-600' : 'text-gray-400' }}">{{ $line->active_plans }}</span>
                                <span class="text-gray-300">/</span>
                                <span class="text-gray-600">{{ $line->total_plans }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl border
                                    @if($line->status == 'active') bg-emerald-50 text-emerald-700 border-emerald-200
                                    @elseif($line->status == 'inactive') bg-gray-50 text-gray-600 border-gray-200
                                    @else bg-amber-50 text-amber-700 border-amber-200 @endif">
                                    <span class="w-1.5 h-1.5 rounded-full 
                                        @if($line->status == 'active') bg-emerald-500
                                        @elseif($line->status == 'inactive') bg-gray-400
                                        @else bg-amber-500 @endif"></span>
                                    {{ ucfirst($line->status) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Quick Status Toggle --}}
                                @if($line->status !== 'active')
                                    <button onclick="toggleStatus({{ $line->id }}, 'active')" class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 flex items-center justify-center transition-colors" title="Set Active">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                @endif
                                @if($line->status !== 'maintenance')
                                    <button onclick="toggleStatus({{ $line->id }}, 'maintenance')" class="w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 flex items-center justify-center transition-colors" title="Set Maintenance">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                @endif
                                
                                <div class="w-px h-6 bg-gray-200 mx-1"></div>
                                
                                <button onclick="openEditModal({{ $line->id }})" class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition-colors" title="Edit Line">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button onclick="deleteLine({{ $line->id }})" class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition-colors" title="Hapus Line">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800">Tidak ada data line</h3>
                                <p class="text-sm text-gray-500 mt-1">Belum ada production line yang ditambahkan.</p>
                                <button onclick="openCreateModal()" class="mt-4 px-4 py-2 rounded-xl bg-red-50 text-red-600 font-semibold text-sm hover:bg-red-100 transition-colors">
                                    + Tambah Line Pertama
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $lines->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- Create / Edit Modal --}}
<div id="lineModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" aria-modal="true">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeLineModal()"></div>

    {{-- Modal Box --}}
    <div id="modalBox" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-100 overflow-hidden transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col">
        {{-- Header --}}
        <div class="flex-shrink-0 bg-gradient-to-br from-red-600 via-red-500 to-rose-600 px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-lg font-bold text-white">Tambah Production Line</h3>
                        <p id="modalSubtitle" class="text-red-100 text-xs mt-0.5">Isi detail master line</p>
                    </div>
                </div>
                <button onclick="closeLineModal()" class="w-8 h-8 rounded-lg bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-6">
            <form id="lineForm">
                <input type="hidden" name="line_id" id="line_id" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    {{-- Line Code --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            Kode Line <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="line_code" id="line_code" required placeholder="Contoh: LN-01"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white placeholder-gray-400 focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all uppercase" />
                    </div>
                    
                    {{-- Line Name --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            Nama Line <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="line_name" id="line_name" required
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all appearance-none cursor-pointer">
                                <option value="" disabled selected>Pilih Line...</option>
                                <option value="Line A">Line A</option>
                                <option value="Line B">Line B</option>
                                <option value="Line C">Line C</option>
                                <option value="Line D">Line D</option>
                                <option value="Shearing">Shearing</option>
                                <option value="Handwork">Handwork</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                    </div>

                    {{-- Capacity --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            Kapasitas / Shift <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="capacity" id="capacity" min="0" required placeholder="0"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white placeholder-gray-400 focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all" />
                    </div>

                    {{-- Machine Count --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            Jumlah Mesin
                        </label>
                        <input type="number" name="machine_count" id="machine_count" min="0" placeholder="Opsional"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white placeholder-gray-400 focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all" />
                    </div>

                    {{-- Shift --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            Shift Operasional <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="shift" id="shift" required
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all appearance-none cursor-pointer">
                                <option value="Semua">Semua Shift</option>
                                <option value="Shift 1">Shift 1</option>
                                <option value="Shift 2">Shift 2</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="status" id="status" required
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all appearance-none cursor-pointer">
                                <option value="active">✅  Active</option>
                                <option value="inactive">⏸️  Inactive</option>
                                <option value="maintenance">🔧  Maintenance</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="3" placeholder="Deskripsi atau spesifikasi line (opsional)…"
                                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white placeholder-gray-400 focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none transition-all resize-none"></textarea>
                    </div>
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="flex-shrink-0 border-t border-gray-100 px-6 py-4 bg-gray-50 flex flex-col-reverse sm:flex-row justify-end gap-3">
            <button type="button" onclick="closeLineModal()"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-100 text-gray-600 font-semibold text-sm transition-colors">
                Batal
            </button>
            <button type="button" onclick="document.getElementById('lineForm').dispatchEvent(new Event('submit', {cancelable:true, bubbles:true}))"
                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-500 hover:from-red-700 hover:to-rose-600 text-white font-semibold text-sm shadow-md shadow-red-200 transition-all flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                Simpan Line
            </button>
        </div>
    </div>
</div>

{{-- Premium Toast Notification --}}
<div id="toast" class="fixed top-5 right-5 z-[9999] hidden" role="alert">
    <div class="flex items-center gap-3 min-w-[280px] max-w-sm px-4 py-3.5 rounded-2xl shadow-2xl text-white text-sm font-medium border border-white/10 backdrop-blur-sm" id="toastInner">
        <div id="toastIcon" class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center bg-white/20"></div>
        <span id="toastMsg" class="flex-1"></span>
        <button onclick="this.closest('#toast').classList.add('hidden')" class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div id="deleteBox" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm border border-gray-100 overflow-hidden transform transition-all duration-300 scale-95 opacity-0">
        {{-- Icon Header --}}
        <div class="flex flex-col items-center pt-8 pb-5 px-6">
            <div class="w-16 h-16 rounded-2xl bg-red-50 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800 text-center">Hapus Line Ini?</h3>
            <p class="text-sm text-gray-500 text-center mt-1.5">Tindakan ini tidak dapat dibatalkan. Jika ada production plan aktif di line ini, penghapusan akan digagalkan.</p>
        </div>
        {{-- Actions --}}
        <div class="border-t border-gray-100 flex">
            <button onclick="closeDeleteModal()" class="flex-1 px-4 py-3.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors border-r border-gray-100">
                Batal
            </button>
            <button id="confirmDeleteBtn" class="flex-1 px-4 py-3.5 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"/></svg>
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const csrf = '{{ csrf_token() }}';

    const toastIcons = {
        success: '<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
        danger:  '<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
        warning: '<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
        info:    '<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/></svg>',
    };
    const toastColors = { success: 'bg-emerald-500', danger: 'bg-red-500', warning: 'bg-amber-500', info: 'bg-blue-500' };
    let toastTimer;

    function showToast(message, type = 'success') {
        const toast   = document.getElementById('toast');
        const inner   = document.getElementById('toastInner');
        const iconEl  = document.getElementById('toastIcon');
        const msgEl   = document.getElementById('toastMsg');

        inner.className = `flex items-center gap-3 min-w-[280px] max-w-sm px-4 py-3.5 rounded-2xl shadow-2xl text-white text-sm font-medium border border-white/10 backdrop-blur-sm ${toastColors[type] || toastColors.info}`;
        iconEl.innerHTML = toastIcons[type] || toastIcons.info;
        msgEl.textContent = message;

        toast.style.transform = 'translateX(120%)';
        toast.classList.remove('hidden');
        requestAnimationFrame(() => {
            toast.style.transition = 'transform 0.35s cubic-bezier(0.34,1.56,0.64,1)';
            toast.style.transform = 'translateX(0)';
        });

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.style.transition = 'transform 0.25s ease-in';
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.classList.add('hidden'), 260);
        }, 3000);
    }

    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Tambah Production Line';
        document.getElementById('modalSubtitle').innerText = 'Isi detail master line';
        document.getElementById('lineForm').reset();
        document.getElementById('line_id').value = '';
        const modal = document.getElementById('lineModal');
        const box = document.getElementById('modalBox');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            box.classList.remove('scale-95', 'opacity-0');
            box.classList.add('scale-100', 'opacity-100');
        });
    }

    function openEditModal(id) {
        fetch(`/supervisor/planning/production-line/${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const line = data.line;
                    document.getElementById('modalTitle').innerText = 'Edit Production Line';
                    document.getElementById('modalSubtitle').innerText = 'Perbarui detail master line';
                    document.getElementById('line_id').value = line.id;
                    document.getElementById('line_code').value = line.line_code;
                    document.getElementById('line_name').value = line.line_name;
                    document.getElementById('capacity').value = line.capacity;
                    document.getElementById('machine_count').value = line.machine_count ?? '';
                    document.getElementById('shift').value = line.shift;
                    document.getElementById('status').value = line.status;
                    document.getElementById('description').value = line.description ?? '';
                    
                    const modal = document.getElementById('lineModal');
                    const box = document.getElementById('modalBox');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    requestAnimationFrame(() => {
                        box.classList.remove('scale-95', 'opacity-0');
                        box.classList.add('scale-100', 'opacity-100');
                    });
                } else {
                    showToast('Gagal memuat data line', 'danger');
                }
            })
            .catch(() => showToast('Error jaringan', 'danger'));
    }

    function closeLineModal() {
        const modal = document.getElementById('lineModal');
        const box = document.getElementById('modalBox');
        box.classList.remove('scale-100', 'opacity-100');
        box.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    document.getElementById('lineForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('line_id').value;
        const url = id ? `/supervisor/planning/production-line/${id}` : '/supervisor/planning/production-line';
        const formData = new FormData(this);
        if (id) formData.append('_method', 'PUT');

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeLineModal();
                setTimeout(() => location.reload(), 800);
            } else {
                const msgs = Object.values(data.errors).flat().join('\n');
                showToast(msgs, 'danger');
            }
        })
        .catch(() => showToast('Error saat menyimpan', 'danger'));
    });

    // Toggle Status
    function toggleStatus(id, status) {
        fetch(`/supervisor/planning/production-line/${id}/status`, {
            method: 'PATCH',
            headers: { 
                'X-CSRF-TOKEN': csrf, 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: status })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Gagal mengubah status', 'danger');
            }
        })
        .catch(() => showToast('Error jaringan', 'danger'));
    }

    // Delete flow
    let pendingDeleteId = null;

    function deleteLine(id) {
        pendingDeleteId = id;
        const modal = document.getElementById('deleteModal');
        const box   = document.getElementById('deleteBox');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            box.classList.remove('scale-95', 'opacity-0');
            box.classList.add('scale-100', 'opacity-100');
        });
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        const box   = document.getElementById('deleteBox');
        box.classList.remove('scale-100', 'opacity-100');
        box.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            pendingDeleteId = null;
        }, 200);
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (!pendingDeleteId) return;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Menghapus…';

        fetch(`/supervisor/planning/production-line/${pendingDeleteId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: new URLSearchParams('_method=DELETE')
        })
        .then(r => r.json())
        .then(data => {
            closeDeleteModal();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                showToast(data.message || 'Gagal menghapus line', 'danger');
            }
        })
        .catch(() => {
            closeDeleteModal();
            showToast('Error jaringan', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"/></svg> Ya, Hapus';
        });
    });
</script>
@endsection
