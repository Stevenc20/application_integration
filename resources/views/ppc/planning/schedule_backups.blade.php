@extends('layouts.ppc')
@section('title', 'Backup & Restore Jadwal PPC')

@section('content')
<div class="space-y-6 mx-auto max-w-screen-2xl px-4 pb-10">
    @if(session('success'))
    <script>document.addEventListener('DOMContentLoaded',function(){if(typeof showToast==='function'){showToast({!! json_encode(session('success')) !!},'success');}});</script>
    <div class="px-5 py-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center justify-between">
        <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <script>document.addEventListener('DOMContentLoaded',function(){if(typeof showToast==='function'){showToast({!! json_encode(session('error')) !!},'error');}});</script>
    <div class="px-5 py-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-bold flex items-center justify-between">
        <span>{{ session('error') }}</span>
    </div>
    @endif
    @if($errors->any())
    <div class="px-5 py-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-bold space-y-1">
        @foreach($errors->all() as $err)
        <div>&bull; {{ $err }}</div>
        @endforeach
    </div>
    @endif

    {{-- Header --}}
    <div class="bg-gradient-to-r from-red-900 via-red-800 to-rose-700 px-6 py-5 rounded-2xl shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/10 backdrop-blur rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight uppercase">Backup &amp; Restore Jadwal PPC</h1>
                    <p class="text-slate-200 text-xs font-medium mt-0.5">
                        Riwayat snapshot jadwal otomatis (Initial / Pre-Import / Pre-Restore) &amp; pemulihan aman tanpa menghapus aktual produksi atau tanda tangan LKH.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('ppc.planning.production_plan', ['date' => $date]) }}"
                   class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-black transition-all flex items-center gap-2 ring-1 ring-white/20">
                    &larr; KEMBALI KE PRODUCTION PLAN
                </a>
            </div>
        </div>
    </div>

    {{-- Filter & Create Manual Backup Card --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Filter Box --}}
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-3">Filter Riwayat Backup</h2>
            <form method="GET" action="{{ route('ppc.planning.backups.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Tanggal Plan</label>
                    <input type="date" name="date" value="{{ request('date', '') }}"
                           class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-700 px-3 py-2 focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Tipe Backup</label>
                    <select name="type" class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-700 px-3 py-2 focus:ring-2 focus:ring-red-500">
                        <option value="">SEMUA TIPE</option>
                        @foreach(['initial' => 'INITIAL (Baseline Awal)', 'pre_import' => 'PRE-IMPORT (Sebelum Revisi)', 'manual' => 'MANUAL SNAPSHOT', 'pre_restore' => 'PRE-RESTORE (Safety)', 'pre_clear' => 'PRE-CLEAR (Sebelum Hapus)'] as $k => $v)
                            <option value="{{ $k }}" {{ ($type ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-black rounded-xl transition-all">
                        TERAPKAN FILTER
                    </button>
                    <a href="{{ route('ppc.planning.backups.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                        RESET
                    </a>
                </div>
            </form>
        </div>

        {{-- Manual Snapshot Box --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-3">Buat Snapshot Manual</h2>
            <form method="POST" action="{{ route('ppc.planning.backups.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Tanggal Jadwal</label>
                        <input type="date" name="plan_date" value="{{ $date }}" required
                               class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-700 px-3 py-2 focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Label Catatan</label>
                        <input type="text" name="label" placeholder="Contoh: Sebelum revisi siang" maxlength="190"
                               class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-700 px-3 py-2 focus:ring-2 focus:ring-red-500">
                    </div>
                </div>
                <button type="submit" class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-black rounded-xl shadow-sm transition-all">
                    + SIMPAN SNAPSHOT SEKARANG
                </button>
            </form>
        </div>
    </div>

    {{-- Backup List Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">Daftar Snapshot Tersimpan</h3>
                <p class="text-[11px] text-slate-500">Klik <b>Lihat &amp; Compare</b> untuk meninjau perbedaan jadwal sebelum melakukan Restore.</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black">{{ $backups->total() }} Snapshot</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-black text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Tanggal Plan</th>
                        <th class="py-3 px-4">Tipe</th>
                        <th class="py-3 px-4">Label / Sumber File</th>
                        <th class="py-3 px-4 text-center">Items</th>
                        <th class="py-3 px-4 text-center">Job Aktif</th>
                        <th class="py-3 px-4 text-center">TTD LKH</th>
                        <th class="py-3 px-4">Dibuat Pada</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($backups as $bk)
                        @php
                            $badgeColors = [
                                'initial' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'pre_import' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                'manual' => 'bg-purple-50 text-purple-700 ring-purple-200',
                                'pre_restore' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                'pre_clear' => 'bg-rose-50 text-rose-700 ring-rose-200',
                            ];
                            $badgeClass = $badgeColors[$bk->backup_type] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-black text-slate-700">#{{ $bk->id }}</td>
                            <td class="py-3 px-4 font-bold text-slate-800">
                                {{ $bk->plan_date ? $bk->plan_date->format('Y-m-d') : '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase ring-1 {{ $badgeClass }}">
                                    {{ strtoupper($bk->backup_type) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $bk->label ?: '-' }}</div>
                                @if($bk->source_filename)
                                    <div class="text-[10px] text-slate-400">File: {{ $bk->source_filename }}</div>
                                @endif
                                @if($bk->last_restored_at)
                                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5">
                                        Pernah direstore: {{ $bk->last_restored_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-black text-slate-700">{{ $bk->plans_count }}</td>
                            <td class="py-3 px-4 text-center">
                                @if($bk->active_jobs_count > 0)
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-black text-[11px]">{{ $bk->active_jobs_count }}</span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-600">{{ $bk->signatures_count }}</td>
                            <td class="py-3 px-4 text-slate-600">
                                <div>{{ $bk->created_at ? $bk->created_at->format('d M Y H:i:s') : '-' }}</div>
                                <div class="text-[10px] text-slate-400">Oleh: {{ $bk->creator->name ?? 'System' }}</div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('ppc.planning.backups.show', $bk->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-red-700 text-white text-[11px] font-black transition-all">
                                    LIHAT &amp; COMPARE
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400 text-xs font-semibold">
                                Belum ada snapshot backup untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($backups->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $backups->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
