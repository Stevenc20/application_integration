@extends('layouts.ppc')
@section('title', 'Pull Ahead Approval')
@section('header_title', 'Pull Ahead Approval')

@section('content')
<div class="space-y-6">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pull Ahead Requests</h1>
            <p class="text-sm text-gray-500 mt-1">Review dan kelola pengajuan penarikan item produksi dari Shift selanjutnya.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-xl shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- PENDING REQUESTS TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Menunggu Approval
            </h2>
            <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-full">{{ $pendingRequests->count() }} PENDING</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-600 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Tgl & Leader</th>
                        <th class="px-6 py-3 font-semibold">Line & Shift</th>
                        <th class="px-6 py-3 font-semibold">Item & Job</th>
                        <th class="px-6 py-3 font-semibold">Qty Req.</th>
                        <th class="px-6 py-3 font-semibold">Usulan Posisi</th>
                        <th class="px-6 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pendingRequests as $req)
                    <tr class="hover:bg-blue-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $req->created_at->format('d M Y, H:i') }}</div>
                            <div class="text-gray-500 text-xs mt-1">{{ $req->requester->name ?? 'Unknown' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $req->originalPlan->line->line_name ?? '-' }}</div>
                            <div class="text-blue-600 text-xs mt-1 font-semibold">{{ $req->target_shift }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $req->originalPlan->job_master ?? '-' }}</div>
                            <div class="text-gray-500 text-xs mt-1">{{ $req->originalPlan->job_no ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-100 text-blue-800 font-bold px-3 py-1 rounded-lg">{{ $req->qty_requested }} PCS</span>
                            <div class="text-xs text-gray-500 mt-1">Avail: {{ $req->originalPlan->remaining_plan }} PCS</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-600">Setelah ID: {{ $req->proposed_sequence_after ?? 'Paling Bawah' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button onclick="openApproveModal({{ $req->id }}, {{ $req->qty_requested }}, {{ $req->originalPlan->remaining_plan ?? $req->originalPlan->plan ?? 0 }})" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg font-semibold shadow-md shadow-emerald-500/20 transition-all text-xs flex items-center gap-1 inline-flex">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Review
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada request PENDING.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- HISTORY TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-800">Histori Request (100 Terakhir)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-600 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Tgl Update</th>
                        <th class="px-6 py-3 font-semibold">Item & Leader</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Qty Apprv.</th>
                        <th class="px-6 py-3 font-semibold">Approver & Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($historyRequests as $hist)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $hist->updated_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $hist->originalPlan->job_master ?? 'Deleted' }}</div>
                            <div class="text-xs text-gray-500 mt-1">Req by: {{ $hist->requester->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($hist->status === 'APPROVED' || $hist->status === 'APPLIED')
                                <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full">APPROVED</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full">REJECTED</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-700">{{ $hist->qty_approved ?? '-' }} PCS</td>
                        <td class="px-6 py-4">
                            <div class="text-gray-800 font-medium">{{ $hist->approver->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500 mt-1 italic">{{ $hist->remarks ?? 'Tidak ada catatan' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada histori.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL APPROVE --}}
<div id="approveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 backdrop-blur-sm hidden transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-all duration-300">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 tracking-tight">Review Pull Ahead</h3>
            </div>
            <button onclick="closeApproveModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6">
            <form id="approveForm" method="POST" action="">
                @csrf
                
                <div class="mb-5 relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Qty Approved (PCS)</label>
                    <div class="relative">
                        <input type="number" id="qty_approved" name="qty_approved" class="w-full pl-4 pr-12 py-3 rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all font-semibold text-gray-800 text-lg" required>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">PCS</span>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="bg-blue-50 text-blue-600 text-xs font-bold px-2.5 py-1 rounded-md border border-blue-100">
                            Maksimal: <span id="max_qty_label" class="font-black text-sm"></span> PCS
                        </div>
                        <span class="text-xs text-gray-500">(Sisa Plan Asli)</span>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Final Sequence (Opsional)</label>
                    <input type="number" name="final_sequence_after" placeholder="Masukkan ID Production Plan (After)" class="w-full px-4 py-3 rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all font-medium text-gray-700 placeholder-gray-400">
                    <div class="flex items-start gap-2 mt-2 text-xs text-gray-500">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p>Kosongkan jika ingin menyisipkan item ini di antrean paling bawah pada Shift yang meminta.</p>
                    </div>
                </div>

                <!-- Footer / Actions -->
                <div class="flex justify-between items-center gap-4 pt-4 border-t border-gray-100">
                    <button type="button" onclick="rejectRequest()" class="text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 font-bold px-5 py-2.5 rounded-xl transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Tolak
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-emerald-600/20 hover:shadow-emerald-600/40 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Approve & Apply
                    </button>
                </div>
            </form>
            
            {{-- Form Reject Hidden --}}
            <form id="rejectForm" method="POST" action="" class="hidden">
                @csrf
                <input type="hidden" name="remarks" value="Ditolak oleh PPC.">
            </form>
        </div>
    </div>
</div>

<script>
    let currentReqId = null;

    function openApproveModal(id, reqQty, maxQty) {
        currentReqId = id;
        document.getElementById('qty_approved').value = reqQty;
        document.getElementById('qty_approved').max = maxQty;
        document.getElementById('max_qty_label').innerText = maxQty;
        
        document.getElementById('approveForm').action = `/ppc/pull-ahead/${id}/approve`;
        document.getElementById('rejectForm').action = `/ppc/pull-ahead/${id}/reject`;
        
        document.getElementById('approveModal').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }

    function rejectRequest() {
        if(confirm('Yakin ingin menolak request ini?')) {
            document.getElementById('rejectForm').submit();
        }
    }
</script>
@endsection
