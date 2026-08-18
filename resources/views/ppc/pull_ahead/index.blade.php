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
                            <button onclick="openApproveModal({{ $req->id }}, {{ $req->qty_requested }}, {{ $req->originalPlan->remaining_plan }})" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg font-semibold transition-colors text-xs">Review</button>
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
<div id="approveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform scale-95 transition-all">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Review Pull Ahead</h3>
            <button onclick="closeApproveModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <form id="approveForm" method="POST" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Qty Approved (PCS)</label>
                    <input type="number" id="qty_approved" name="qty_approved" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <p class="text-xs text-gray-500 mt-1">Maksimal: <span id="max_qty_label" class="font-bold"></span> PCS (Sisa Plan Asli)</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Final Sequence (Opsional)</label>
                    <input type="number" name="final_sequence_after" placeholder="Masukkan ID Production Plan (After)" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-2">Kosongkan jika ingin menyisipkan di antrean paling bawah Shift aktif.</p>
                </div>

                <div class="flex justify-between items-center gap-4 mt-8 pt-4 border-t border-gray-100">
                    <button type="button" onclick="rejectRequest()" class="text-red-600 hover:bg-red-50 font-semibold px-4 py-2.5 rounded-xl transition-colors">Tolak Request</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-md shadow-blue-500/30 transition-all">Approve & Apply</button>
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
