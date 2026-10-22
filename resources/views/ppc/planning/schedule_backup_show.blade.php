@extends('layouts.ppc')
@section('title', 'Detail & Compare Backup #' . $backup->id)

@section('content')
<div class="space-y-6 mx-auto max-w-screen-2xl px-4 pb-10">
    @if(session('success'))
    <script>document.addEventListener('DOMContentLoaded',function(){if(typeof showToast==='function'){showToast({!! json_encode(session('success')) !!},'success');}});</script>
    <div class="px-5 py-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <script>document.addEventListener('DOMContentLoaded',function(){if(typeof showToast==='function'){showToast({!! json_encode(session('error')) !!},'error');}});</script>
    <div class="px-5 py-3.5 bg-red-50 border border-red-200 rounded-2xl text-red-800 text-xs font-bold">
        {{ session('error') }}
    </div>
    @endif

    @php
        $planDateStr = $backup->plan_date ? $backup->plan_date->format('Y-m-d') : now()->format('Y-m-d');
        $counts = $diff['counts'] ?? [];
        $payloadPlans = $backup->snapshot_payload['production_plans'] ?? [];
        $completenessInfo = $completeness ?? ($diff['completeness'] ?? ['is_complete' => true, 'incomplete_count' => 0, 'issues' => []]);
        $isComplete = (bool) ($completenessInfo['is_complete'] ?? true);
        $incompleteCount = (int) ($completenessInfo['incomplete_count'] ?? 0);
        $completenessIssues = $completenessInfo['issues'] ?? [];
    @endphp

    {{-- Peringatan jika Snapshot Tidak Lengkap --}}
    @if(!$isComplete)
    <div class="px-5 py-4 bg-red-50 border-2 border-red-600 rounded-2xl text-red-900 space-y-1.5 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-black uppercase text-red-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <span>Peringatan: Data Snapshot Tidak Lengkap ({{ $incompleteCount }} baris bermasalah)</span>
        </div>
        <p class="text-xs text-red-800">
            Snapshot ini memiliki baris jadwal yang kehilangan atribut wajib (seperti <b>PRESS</b>, <b>SHIFT</b>, atau <b>URUTAN</b>). Demi menjaga integritas data produksi, fitur <b>Restore dinonaktifkan</b> untuk snapshot yang tidak lengkap.
        </p>
        @if(!empty($completenessIssues))
        <ul class="list-disc list-inside text-[11px] text-red-700 font-semibold pt-1">
            @foreach($completenessIssues as $issue)
                <li>{{ $issue }}</li>
            @endforeach
        </ul>
        @endif
    </div>
    @endif

    {{-- Header Merah Solid PT IPPI --}}
    <div class="bg-red-700 border border-red-800 px-6 py-5 rounded-2xl shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-lg bg-red-900 text-white text-[10px] font-black uppercase tracking-wider border border-red-500">
                        {{ strtoupper($backup->backup_type) }}
                    </span>
                    <span class="text-red-100 text-xs font-bold">Snapshot #{{ $backup->id }}</span>
                </div>
                <h1 class="text-xl font-black text-white tracking-tight mt-1">
                    {{ $backup->label ?: ('Snapshot Tanggal ' . $planDateStr) }}
                </h1>
                <p class="text-red-100 text-xs mt-0.5">
                    Tanggal Jadwal: <b class="text-white">{{ $planDateStr }}</b> &bull;
                    Dibuat: <b class="text-white">{{ $backup->created_at ? $backup->created_at->format('d M Y H:i:s') : '-' }}</b> &bull;
                    Oleh: <b class="text-white">{{ $backup->creator->name ?? 'System' }}</b>
                    @if($backup->source_filename)
                        &bull; File: <b class="text-white underline">{{ $backup->source_filename }}</b>
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('ppc.planning.backups.index', ['date' => $planDateStr]) }}"
                   class="px-4 py-2 bg-red-800 hover:bg-red-900 text-white rounded-xl text-xs font-black transition-all border border-red-600">
                    &larr; DAFTAR BACKUP
                </a>
                <a href="{{ route('ppc.planning.production_plan', ['date' => $planDateStr]) }}"
                   class="px-4 py-2 bg-white hover:bg-slate-100 text-red-700 rounded-xl text-xs font-black transition-all shadow-sm">
                    LIHAT LIVE PLAN
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Diff Cards & Restore Form --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-black text-slate-800 uppercase">Preview Perbandingan dengan Jadwal Live Saat Ini</h2>
                    <p class="text-[11px] text-slate-500">Simulasi perubahan jika Snapshot #{{ $backup->id }} di-restore sekarang.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-black text-slate-700 uppercase">Akan Dipulihkan</div>
                    <div class="text-xl font-black text-red-700 mt-1">{{ $counts['will_restore_missing'] ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500">Item hilang di live</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-black text-slate-700 uppercase">Update Jadwal</div>
                    <div class="text-xl font-black text-amber-700 mt-1">{{ $counts['will_update_schedule'] ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500">Jam / Qty / Urutan</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-black text-slate-700 uppercase">Aktual Dilindungi</div>
                    <div class="text-xl font-black text-emerald-700 mt-1">{{ $counts['protected_active_plans'] ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500">OK/NG/Status utuh</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-black text-slate-700 uppercase">Item Pasca-Backup</div>
                    <div class="text-xl font-black text-slate-800 mt-1">{{ $counts['post_snapshot_plans'] ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500">Ditambahkan setelah backup</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-black text-slate-700 uppercase">Identik</div>
                    <div class="text-xl font-black text-slate-800 mt-1">{{ $counts['unchanged'] ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500">Tidak berubah</div>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600 space-y-1">
                <div class="font-black text-slate-800 uppercase">Jaminan Keamanan Restore (Non-Destructive Production Guard):</div>
                <div>1. Sebelum restore dieksekusi, sistem otomatis membuat snapshot <b>PRE-RESTORE</b> dari kondisi live saat ini.</div>
                <div>2. Seluruh progres <b>Input Harian</b> (Qty OK, Repair, Reject, Downtime, Dandori, Sesi Running/Completed) <b>TIDAK AKAN DIHAPUS / DIRESET</b>.</div>
                <div>3. Seluruh <b>Tanda Tangan LKH</b> (Leader, Foreman, Supervisor) yang sudah masuk tetap dipertahankan.</div>
            </div>
        </div>

        {{-- Restore Action Box --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-black text-red-700 uppercase tracking-wider">Eksekusi Restore</h2>
                <p class="text-xs text-slate-600 mt-1">
                    Kembalikan susunan jadwal tanggal <b>{{ $planDateStr }}</b> ke kondisi Snapshot <b>#{{ $backup->id }}</b>.
                </p>

                <form method="POST" action="{{ route('ppc.planning.backups.restore', $backup->id) }}" class="mt-4 space-y-4"
                      onsubmit="return confirm('Yakin ingin me-restore jadwal ke Snapshot #{{ $backup->id }}? Progres aktual produksi & TTD LKH tetap aman.');">
                    @csrf
                    <input type="hidden" name="prune_unlisted_idle" value="0">
                    <label class="flex items-start gap-2.5 cursor-pointer p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <input type="checkbox" name="prune_unlisted_idle" value="1" checked
                               class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-600"
                               {{ !$isComplete ? 'disabled' : '' }}>
                        <span class="text-[11px] text-slate-700 leading-snug">
                            <b>Bersihkan item revisi baru yang belum berjalan (Idle)</b><br>
                            Item yang baru ditambahkan setelah snapshot ini dan <i>belum memiliki aktivitas produksi</i> akan dihapus agar jadwal kembali bersih sesuai snapshot. Item yang sudah berjalan tetap dipertahankan.
                        </span>
                    </label>

                    @if($isComplete)
                        <button type="submit"
                                class="w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase rounded-xl shadow-sm transition-all">
                            RESTORE JADWAL KE SNAPSHOT #{{ $backup->id }}
                        </button>
                    @else
                        <button type="button" disabled
                                class="w-full py-3 px-4 bg-slate-200 text-slate-500 font-black text-xs uppercase rounded-xl cursor-not-allowed">
                            RESTORE DINONAKTIFKAN (DATA SNAPSHOT TIDAK LENGKAP)
                        </button>
                    @endif
                </form>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 text-[10px] text-slate-500">
                Checksum SHA-256: <span class="font-mono">{{ substr($backup->checksum ?? '-', 0, 24) }}...</span>
            </div>
        </div>
    </div>

    {{-- Diff Breakdown Sections --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Will Restore Missing & Will Update --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
            <h3 class="text-xs font-black text-slate-800 uppercase">
                Perubahan Jadwal ({{ count($diff['will_restore_missing'] ?? []) + count($diff['will_update_schedule'] ?? []) }} Item)
            </h3>
            <div class="max-h-72 overflow-y-auto space-y-2">
                @foreach(($diff['will_restore_missing'] ?? []) as $item)
                    @php
                        $itemPress = $item['press_name'] ?? $item['press'] ?? '-';
                        $itemShift = $item['shift_name'] ?? $item['shift'] ?? '-';
                        $itemRowNo = $item['row_no'] ?? $item['urutan_plan'] ?? '-';
                        $itemPlanQty = (float) ($item['plan'] ?? $item['qty_plan'] ?? 0);
                    @endphp
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                        <div>
                            <span class="px-1.5 py-0.5 rounded bg-red-600 text-white text-[9px] font-black uppercase mr-1">PULIHKAN</span>
                            <b>{{ $itemPress }}</b> &bull; {{ $itemShift }} &bull; #{{ $itemRowNo }} &bull; <b>{{ $item['job_no'] ?? '-' }}</b> ({{ $item['job_master'] ?? '-' }})
                        </div>
                        <span class="text-[11px] font-bold text-slate-800">Qty: {{ number_format($itemPlanQty) }}</span>
                    </div>
                @endforeach

                @foreach(($diff['will_update_schedule'] ?? []) as $item)
                    @php
                        $itemPress = $item['press_name'] ?? $item['press'] ?? '-';
                        $itemShift = $item['shift_name'] ?? $item['shift'] ?? '-';
                        $itemRowNo = $item['row_no'] ?? $item['urutan_plan'] ?? '-';
                        $hasAct = (bool) ($item['has_live_activity'] ?? $item['has_activity'] ?? false);
                    @endphp
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="px-1.5 py-0.5 rounded bg-amber-600 text-white text-[9px] font-black uppercase mr-1">UPDATE</span>
                                <b>{{ $itemPress }}</b> &bull; {{ $itemShift }} &bull; #{{ $itemRowNo }} &bull; <b>{{ $item['job_no'] ?? '-' }}</b> ({{ $item['job_master'] ?? '-' }})
                            </div>
                            @if($hasAct)
                                <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] font-black">AKTUAL DILINDUNGI</span>
                            @endif
                        </div>
                        <div class="text-[10px] text-slate-600">
                            Field berubah:
                            @foreach(($item['changed_fields'] ?? []) as $f => $vals)
                                @if(is_array($vals))
                                    <span class="inline-block mr-2"><b>{{ $f }}</b>: <del class="text-red-600">{{ $vals['live'] ?? '-' }}</del> &rarr; <ins class="text-emerald-700 no-underline font-bold">{{ $vals['backup'] ?? '-' }}</ins></span>
                                @else
                                    <span class="inline-block mr-2 font-bold">{{ $vals }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if(empty($diff['will_restore_missing']) && empty($diff['will_update_schedule']))
                    <p class="text-xs text-slate-400 py-4 text-center">Semua jadwal pada snapshot ini sudah identik dengan jadwal live saat ini.</p>
                @endif
            </div>
        </div>

        {{-- Post-Snapshot Plans --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
            <h3 class="text-xs font-black text-slate-800 uppercase">
                Item yang Ditambahkan Setelah Snapshot ({{ count($diff['post_snapshot_plans'] ?? []) }} Item)
            </h3>
            <div class="max-h-72 overflow-y-auto space-y-2">
                @forelse(($diff['post_snapshot_plans'] ?? []) as $item)
                    @php
                        $itemPress = $item['press_name'] ?? $item['press'] ?? '-';
                        $itemShift = $item['shift_name'] ?? $item['shift'] ?? '-';
                        $itemRowNo = $item['row_no'] ?? $item['urutan_plan'] ?? '-';
                        $hasAct = (bool) ($item['has_live_activity'] ?? $item['has_activity'] ?? false);
                    @endphp
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                        <div>
                            <b>{{ $itemPress }}</b> &bull; {{ $itemShift }} &bull; #{{ $itemRowNo }} &bull; <b>{{ $item['job_no'] ?? '-' }}</b> ({{ $item['job_master'] ?? '-' }})
                        </div>
                        @if($hasAct)
                            <span class="px-2 py-0.5 rounded bg-emerald-600 text-white text-[9px] font-black uppercase">ADA PROGRES - TETAP DIPERTAHANKAN</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-red-600 text-white text-[9px] font-black uppercase">IDLE - DAPAT DIBERSIHKAN</span>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">Tidak ada item tambahan di luar snapshot ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Full Snapshot Plans Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">Isi Lengkap Jadwal pada Snapshot #{{ $backup->id }} ({{ count($payloadPlans) }} Item)</h3>
            <span class="px-3 py-1 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs font-black">{{ count($payloadPlans) }} Baris</span>
        </div>
        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-black text-slate-600 uppercase tracking-wider sticky top-0">
                        <th class="py-2.5 px-4">Press</th>
                        <th class="py-2.5 px-4">Shift</th>
                        <th class="py-2.5 px-4">Urutan</th>
                        <th class="py-2.5 px-4">Job No</th>
                        <th class="py-2.5 px-4">Part Name / Job Master</th>
                        <th class="py-2.5 px-4 text-right">Qty Plan</th>
                        <th class="py-2.5 px-4 text-center">Jam Plan</th>
                        <th class="py-2.5 px-4 text-right">OK (Saat Backup)</th>
                        <th class="py-2.5 px-4 text-center">Status (Saat Backup)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($payloadPlans as $row)
                        @php
                            $rowPress = $row['press_name'] ?? $row['press'] ?? null;
                            $rowShift = $row['shift_name'] ?? $row['shift'] ?? null;
                            $rowUrutan = $row['row_no'] ?? $row['urutan_plan'] ?? null;
                            $rowQtyPlan = (float) ($row['plan'] ?? $row['qty_plan'] ?? 0);
                            $rowType = strtolower((string) ($row['row_type'] ?? 'job'));
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-4 font-black text-slate-800">{{ $rowPress ?: '-' }}</td>
                            <td class="py-2 px-4 font-semibold text-slate-700">{{ $rowShift ?: '-' }}</td>
                            <td class="py-2 px-4 font-bold text-slate-800">{{ ($rowUrutan !== null && $rowUrutan !== '') ? $rowUrutan : ($rowType === 'break' ? 'BREAK' : '-') }}</td>
                            <td class="py-2 px-4 font-bold text-slate-800">{{ $row['job_no'] ?? '-' }}</td>
                            <td class="py-2 px-4 text-slate-700">{{ $row['job_master'] ?? '-' }}</td>
                            <td class="py-2 px-4 text-right font-black text-slate-800">{{ number_format($rowQtyPlan) }}</td>
                            <td class="py-2 px-4 text-center text-slate-600">{{ $row['start_time'] ?? '-' }} - {{ $row['finish_time'] ?? '-' }}</td>
                            <td class="py-2 px-4 text-right font-bold text-emerald-700">{{ number_format((float) ($row['ok'] ?? 0)) }}</td>
                            <td class="py-2 px-4 text-center">
                                <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-bold uppercase">{{ $row['status'] ?? 'pending' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">Tidak ada baris jadwal di dalam snapshot ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
