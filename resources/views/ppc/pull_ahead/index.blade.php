@extends('layouts.ppc')
@section('title', 'Pull Ahead Approval')
@section('header_title', 'Pull Ahead Approval')

@section('content')
@php
    $statPending  = $pendingRequests->count();
    $statApproved = $historyRequests->where('status', 'APPROVED')->count();
    $statApplied  = $historyRequests->where('status', 'APPLIED')->count();
    $statRejected = $historyRequests->where('status', 'REJECTED')->count();
@endphp

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <a href="{{ route('ppc.dashboard') }}"
               class="mt-0.5 inline-flex items-center justify-center w-10 h-10 shrink-0 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-primary-red hover:bg-red-50 hover:border-red-200 shadow-sm transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Pull Ahead Requests</h1>
                <p class="text-sm text-gray-500 mt-1">Review dan kelola pengajuan penarikan item produksi dari Shift selanjutnya.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 pl-[52px] sm:pl-0">
            @if($statPending > 0)
                <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-800 text-xs font-bold px-3 py-2 rounded-xl">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    {{ $statPending }} Menunggu
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-2 rounded-xl">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Semua Ter-handle
                </span>
            @endif
            <a href="{{ route('ppc.pull_ahead.index') }}"
               class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-600 hover:text-primary-red hover:bg-red-50 hover:border-red-200 text-xs font-bold px-3 py-2 rounded-xl shadow-sm transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </a>
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
        <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-200 border-l-4 border-l-emerald-500 text-emerald-800 p-4 rounded-xl shadow-sm">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-start gap-3 bg-red-50 border border-red-200 border-l-4 border-l-red-500 text-red-800 p-4 rounded-xl shadow-sm">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium">{{ session('error') }}</p>
        </div>
    @endif

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Menunggu</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-black text-gray-800">{{ $statPending }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Disetujui</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-black text-gray-800">{{ $statApproved }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Diterapkan</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-black text-gray-800">{{ $statApplied }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ditolak</span>
                <span class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-black text-gray-800">{{ $statRejected }}</div>
        </div>
    </div>

    {{-- PENDING REQUESTS TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                Menunggu Approval
            </h2>
            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1 rounded-full">{{ $statPending }} PENDING</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-bold">Tgl & Leader</th>
                        <th class="px-6 py-3 font-bold">Line & Shift</th>
                        <th class="px-6 py-3 font-bold">Item & Job</th>
                        <th class="px-6 py-3 font-bold">Qty Req.</th>
                        <th class="px-6 py-3 font-bold">Usulan Posisi</th>
                        <th class="px-6 py-3 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pendingRequests as $req)
                    @php
                        $plan  = $req->originalPlan;
                        $avail = (float) ($plan?->remaining_plan ?? 0);
                        if ($avail <= 0) {
                            $avail = (float) ($plan?->plan ?: $plan?->target_qty ?: 0);
                        }
                        $lineName = $plan?->line?->line_name ?? '-';
                    @endphp
                    <tr class="hover:bg-amber-50/40 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-semibold text-gray-800">{{ $req->created_at->format('d M Y, H:i') }}</div>
                            <div class="text-gray-500 text-xs mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $req->requester->name ?? 'Unknown' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800">{{ $lineName }}</div>
                            <span class="inline-block mt-1 bg-blue-50 text-blue-700 text-xs font-bold px-2.5 py-0.5 rounded-md border border-blue-100">{{ $req->target_shift }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $plan?->job_master ?? 'Item Terhapus' }}</div>
                            <div class="text-gray-400 text-xs mt-1 font-mono">{{ $plan?->job_no ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-100 text-blue-800 font-bold px-3 py-1 rounded-lg whitespace-nowrap">{{ $req->qty_requested }} PCS</span>
                            <div class="text-xs text-gray-500 mt-1">Avail: <span class="font-bold text-gray-700">{{ $avail }} PCS</span></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-600 font-medium whitespace-nowrap">Setelah ID: {{ $req->proposed_sequence_after ?? 'Paling Bawah' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button type="button"
                                    data-line="{{ $lineName }}"
                                    data-shift="{{ $req->target_shift }}"
                                    data-item="{{ $plan?->job_master ?? 'Item Terhapus' }}"
                                    data-job="{{ $plan?->job_no ?? '-' }}"
                                    data-leader="{{ $req->requester->name ?? 'Unknown' }}"
                                    onclick="openApproveModal({{ $req->id }}, {{ $req->qty_requested }}, {{ max($avail, 0) }}, this)"
                                    class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg font-semibold shadow-md shadow-emerald-500/20 transition-all text-xs inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Review
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="mt-4 font-bold text-gray-700">Tidak ada request menunggu</p>
                            <p class="text-sm text-gray-400 mt-1">Semua pengajuan Pull Ahead sudah Anda tangani.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- HISTORY TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                Histori Request
            </h2>
            <span class="bg-gray-100 text-gray-600 text-xs font-bold px-3 py-1 rounded-full">100 Terakhir</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-bold">Tgl Update</th>
                        <th class="px-6 py-3 font-bold">Line & Shift</th>
                        <th class="px-6 py-3 font-bold">Item & Leader</th>
                        <th class="px-6 py-3 font-bold">Status</th>
                        <th class="px-6 py-3 font-bold">Qty Apprv.</th>
                        <th class="px-6 py-3 font-bold">Approver & Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($historyRequests as $hist)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 font-medium">{{ $hist->updated_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800">{{ $hist->originalPlan?->line?->line_name ?? '-' }}</div>
                            <div class="text-gray-400 text-xs mt-1">{{ $hist->target_shift }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $hist->originalPlan?->job_master ?? 'Item Terhapus' }}</div>
                            <div class="text-xs text-gray-500 mt-1">Req by: {{ $hist->requester->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($hist->status === 'APPLIED')
                                <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full">DITERAPKAN</span>
                            @elseif($hist->status === 'APPROVED')
                                <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full">DISETUJUI</span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full">DITOLAK</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-700 whitespace-nowrap">
                            {{ $hist->qty_approved !== null ? $hist->qty_approved . ' PCS' : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-800 font-medium">{{ $hist->approver->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500 mt-1 italic">{{ $hist->remarks ?? 'Tidak ada catatan' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 text-gray-400 flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0112 19V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 13v6a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="mt-4 font-bold text-gray-700">Belum ada histori</p>
                            <p class="text-sm text-gray-400 mt-1">Riwayat approval akan muncul di sini setelah request diproses.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL APPROVE --}}
<div id="approveModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden modal-panel">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <h3 class="text-xl font-bold text-gray-800 tracking-tight">Review Pull Ahead</h3>
            </div>
            <button type="button" onclick="closeApproveModal()" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-6">
            <div id="modalContext" class="mb-5 bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm text-gray-600"></div>

            <form id="approveForm" method="POST" action="">
                @csrf

                <div class="mb-5">
                    <label for="qty_approved" class="block text-sm font-semibold text-gray-700 mb-2">Qty Approved (PCS)</label>
                    <div class="relative">
                        <input type="number" id="qty_approved" name="qty_approved" min="1" step="1"
                               class="w-full pl-4 pr-14 py-3 rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all font-bold text-gray-800 text-lg">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">PCS</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <div class="bg-blue-50 text-blue-600 text-xs font-bold px-2.5 py-1 rounded-md border border-blue-100">
                            Maksimal: <span id="max_qty_label" class="font-black text-sm">0</span> PCS
                        </div>
                        <button type="button" onclick="fillMaxQty()" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline">Isi Maksimal</button>
                        <span class="text-xs text-gray-400">Sisa plan asli</span>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="final_sequence_after" class="block text-sm font-semibold text-gray-700 mb-2">Final Sequence <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="number" id="final_sequence_after" name="final_sequence_after" placeholder="ID Production Plan (After)"
                           class="w-full px-4 py-3 rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all font-medium text-gray-700 placeholder-gray-400">
                    <div class="flex items-start gap-2 mt-2 text-xs text-gray-500">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>Kosongkan jika ingin menyisipkan item ini di antrean paling bawah pada Shift yang meminta.</p>
                    </div>
                </div>

                <div class="flex justify-between items-center gap-4 pt-4 border-t border-gray-100">
                    <button type="button" onclick="rejectRequest()" class="text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 font-bold px-5 py-2.5 rounded-xl transition-all inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tolak
                    </button>
                    <button type="submit" onclick="return confirmApprove()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve &amp; Apply
                    </button>
                </div>
            </form>

            <form id="rejectForm" method="POST" action="" class="hidden">
                @csrf
                <input type="hidden" name="remarks" value="Ditolak oleh PPC.">
            </form>
        </div>
    </div>
</div>

<script>
    let currentReqId = null;
    let currentMaxQty = 0;

    const pullAheadBase = '/ppc/pull-ahead';

    function buildContext(btn) {
        const wrap = document.getElementById('modalContext');
        wrap.textContent = '';

        const rows = [
            ['Line', btn.dataset.line],
            ['Shift', btn.dataset.shift],
            ['Item', btn.dataset.item],
            ['Job', btn.dataset.job],
            ['Leader', btn.dataset.leader],
        ];

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-2 gap-x-4 gap-y-1';

        rows.forEach(function (row) {
            const label = document.createElement('span');
            label.className = 'text-xs font-semibold text-gray-400 uppercase tracking-wide';
            label.textContent = row[0];

            const value = document.createElement('span');
            value.className = 'text-sm font-bold text-gray-700 text-right truncate';
            value.textContent = row[1] || '-';

            grid.appendChild(label);
            grid.appendChild(value);
        });

        wrap.appendChild(grid);
    }

    function openApproveModal(id, reqQty, maxQty, btn) {
        currentReqId = id;
        currentMaxQty = parseInt(maxQty) || 0;

        document.getElementById('qty_approved').value = reqQty;
        document.getElementById('qty_approved').max = currentMaxQty;
        document.getElementById('max_qty_label').innerText = currentMaxQty;
        document.getElementById('final_sequence_after').value = '';

        document.getElementById('approveForm').action = pullAheadBase + '/' + id + '/approve';
        document.getElementById('rejectForm').action = pullAheadBase + '/' + id + '/reject';

        if (btn) buildContext(btn);

        const modal = document.getElementById('approveModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        modal.querySelector('.modal-panel').classList.add('modal-panel-open');

        setTimeout(function () { document.getElementById('qty_approved').focus(); }, 60);
    }

    function closeApproveModal() {
        const modal = document.getElementById('approveModal');
        modal.querySelector('.modal-panel').classList.remove('modal-panel-open');
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }

    function fillMaxQty() {
        if (currentMaxQty > 0) document.getElementById('qty_approved').value = currentMaxQty;
    }

    function confirmApprove() {
        const input = document.getElementById('qty_approved');
        const val = parseInt(input.value) || 0;

        if (val < 1) {
            alert('Qty Approved minimal 1 PCS.');
            return false;
        }
        if (currentMaxQty > 0 && val > currentMaxQty) {
            alert('Qty Approved tidak boleh melebihi sisa plan (' + currentMaxQty + ' PCS).');
            return false;
        }

        return confirm('Yakin menyetujui request ini dan menerapkan ' + val + ' PCS ke Shift peminta?');
    }

    function rejectRequest() {
        if (confirm('Yakin ingin menolak request ini?')) {
            closeApproveModal();
            document.getElementById('rejectForm').submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('approveModal');
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeApproveModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeApproveModal();
        });
    });
</script>

<style>
    .modal-panel { opacity: 0; transform: scale(.95) translateY(8px); transition: opacity .2s ease, transform .2s ease; }
    .modal-panel-open { opacity: 1; transform: scale(1) translateY(0); }
</style>
@endsection