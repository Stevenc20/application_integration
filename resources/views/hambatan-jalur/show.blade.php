@extends('layouts.supervisor')
@section('title', 'Detail Hambatan Jalur')

@section('content')
<style>
/* Premium Red Theme for Hambatan Jalur Form */
.hj-form-wrapper {
    max-width: 900px;
    margin: 0 auto;
}
.hj-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    transition: all 0.25s;
    padding: 6px 14px;
    border-radius: 10px;
    background: white;
    border: 1px solid #e2e8f0;
}
.hj-back-btn:hover {
    color: #991b1b;
    background: #fef2f2;
    border-color: #fecaca;
    transform: translateX(-2px);
}
/* Form Container */
.hj-form-container {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.03);
}
/* Form Header */
.hj-form-header {
    background: linear-gradient(135deg, #991b1b 0%, #b91c1c 40%, #dc2626 100%);
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}
.hj-form-header::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
/* Table Styles */
.hj-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.hj-table td {
    border: 1px solid #e2e8f0;
    padding: 10px 16px;
    vertical-align: top;
}
.hj-table .hj-label {
    font-weight: 800;
    color: #991b1b;
    width: 130px;
    background: linear-gradient(135deg, #fff5f5 0%, #fef2f2 100%);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 11px;
}
.hj-table .hj-value {
    color: #1e293b;
    font-weight: 600;
}
.hj-table .hj-sub-label {
    color: #94a3b8;
    font-weight: 600;
    font-size: 11px;
}
/* Checkbox Styles */
.hj-check-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: default;
}
.hj-check-item input[type="checkbox"] {
    accent-color: #dc2626;
    width: 14px;
    height: 14px;
    border-radius: 3px;
}
/* Textarea */
.hj-textarea {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 12px;
    resize: none;
    transition: all 0.25s;
    background: #fafbfc;
    font-family: inherit;
    color: #1e293b;
}
.hj-textarea:focus {
    outline: none;
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,0.08);
    background: white;
}
/* Keterangan Section */
.hj-keterangan {
    padding: 16px 24px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
}
.hj-keterangan-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 3px 24px;
    font-size: 11px;
    color: #64748b;
}
/* Catatan */
.hj-catatan {
    padding: 12px 24px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 11px;
    color: #64748b;
}
/* Signature Table */
.hj-sig-table {
    width: 100%;
    border-collapse: collapse;
}
.hj-sig-table td {
    border: 1px solid #e2e8f0;
    padding: 16px 20px;
    text-align: center;
    width: 33.33%;
    vertical-align: top;
}
.hj-sig-trigger {
    cursor: pointer;
    border-radius: 12px;
    padding: 12px;
    transition: all 0.3s;
    border: 2px dashed #fca5a5;
    background: #fef2f2;
}
.hj-sig-trigger:hover {
    border-color: #dc2626;
    background: #fee2e2;
}
/* Status Footer */
.hj-footer-signed {
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border-top: 1px solid #bbf7d0;
}
.hj-footer-pending {
    padding: 14px 24px;
    text-align: center;
    background: linear-gradient(135deg, #fff5f5 0%, #fef2f2 100%);
    border-top: 1px solid #fecaca;
}
/* Alert */
.hj-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}
.hj-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.hj-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
@keyframes hjFadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
.hj-animate { animation: hjFadeUp 0.4s ease both; }

/* Responsive: Mobile < 640px */
@media (max-width: 639px) {
    .hj-form-wrapper { padding-left: 4px; padding-right: 4px; }
    .hj-form-container { border-radius: 10px; }
    .hj-form-header {
        flex-direction: column; align-items: flex-start; gap: 6px;
        padding: 12px 14px;
    }
    .hj-form-header .text-right { text-align: left; width: 100%; }
    .hj-form-header .flex.items-center.gap-3 { flex-wrap: wrap; }

    .hj-table, .hj-table tbody, .hj-table tr, .hj-table td { display: block; width: 100%; box-sizing: border-box; }
    .hj-table td { border: none; border-bottom: 1px solid #e2e8f0; padding: 6px 10px; }
    .hj-table .hj-label {
        width: 100%; background: #fff5f5; font-size: 10px;
        padding: 4px 10px; border-bottom: 1px solid #fecaca;
    }
    .hj-table .hj-value { font-size: 11px; }
    .hj-table .flex.items-center { flex-wrap: wrap; gap: 4px; }
    .hj-table .w-1\/2 { width: 100%; }
    .hj-sub-label { font-size: 10px; }
    .hj-check-item { font-size: 11px; }
    .hj-textarea { font-size: 11px; padding: 8px 10px; }
    .hj-value .ml-4 { display: block; margin-left: 0; margin-top: 2px; }

    .hj-keterangan { padding: 10px 14px; }
    .hj-keterangan-grid { grid-template-columns: 1fr; gap: 1px; }
    .hj-catatan { padding: 10px 14px; }

    .hj-sig-table, .hj-sig-table tbody, .hj-sig-table tr, .hj-sig-table td { display: block; width: 100%; }
    .hj-sig-table td { width: 100% !important; border-bottom: 1px solid #e2e8f0; padding: 12px; }
    .hj-sig-table td:last-child { border-bottom: none; }

    .hj-sig-trigger { padding: 8px; }
    #signatureArea .border-2, #leaderSignatureArea .border-2 {
        width: 100% !important;
        max-width: 250px;
    }
    .hj-form-container .px-6 { padding-left: 14px; padding-right: 14px; }
}
@media (min-width: 640px) and (max-width: 768px) {
    .hj-keterangan-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="hj-form-wrapper space-y-4">
    {{-- BACK BUTTON --}}
    <a href="{{ url()->previous() ?? route('hambatan-jalur.index') }}" class="hj-back-btn" onclick="event.preventDefault();window.history.back()">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>

    {{-- ALERTS --}}
    @if(session('success'))
    <div class="hj-alert hj-alert-success hj-animate">
        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="hj-alert hj-alert-error hj-animate">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ session('error') }}
    </div>
    @endif
    @if(session('warning'))
    <div class="hj-alert hj-alert-error hj-animate" style="background:#fffbeb;border-color:#fcd34d;color:#92400e">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        {{ session('warning') }}
    </div>
    @endif

    @php
        $canPicSign = $item->status === 'open' && !$item->signature_image;
        $canLeaderSign = $item->status === 'pic_signed' && ($isLineLeader ?? false);
        $showForm = $canPicSign || $canLeaderSign;
    @endphp

    @if($showForm)
    <form action="{{ $canPicSign ? route('hambatan-jalur.sign', $item->id) : route('hambatan-jalur.leader-sign', $item->id) }}" method="POST" id="hj-sign-form">
    @csrf
    @endif

    <div class="hj-form-container hj-animate">
        {{-- HEADER --}}
        <div class="hj-form-header">
            <div class="flex items-center gap-3 relative z-10">
                <img class="w-10 h-auto drop-shadow-md" src="{{ asset('images/ippi_logo.png') }}" alt="Logo IPPI">
                <span class="text-sm font-bold text-white tracking-wide">PT INTI PANTJA PRESS INDUSTRI</span>
            </div>
            <div class="text-right relative z-10">
                <p class="text-sm font-black text-white tracking-wide">LAPORAN HAMBATAN JALUR</p>
                <p class="text-[10px] text-red-200 font-semibold">FISM PRO-02-01-03</p>
            </div>
        </div>

        {{-- FORM TABLE --}}
        <table class="hj-table">
            {{-- JALUR (4 baris merged) --}}
            <tr>
                <td class="hj-label" rowspan="4">JALUR</td>
                <td class="hj-value">
                    <span class="hj-sub-label">PRESS LINE :</span>
                    <span class="ml-2">{{ $item->line_name ?? '_____________' }}</span>
                    <span class="hj-sub-label ml-4">MESIN :</span>
                    <span class="ml-2">{{ $item->mesin ?? '_____________' }}</span>
                </td>
                <td class="hj-value">
                    <span class="hj-sub-label">SHEARING MESIN :</span>
                    <span class="ml-2">_______________</span>
                </td>
            </tr>
            <tr>
                <td class="hj-value"></td>
                <td class="hj-value"></td>
            </tr>
            <tr>
                <td class="hj-value">
                    <span class="hj-sub-label">SUB ASSY LINE :</span>
                    <span class="ml-2">_______________</span>
                    <span class="hj-sub-label ml-4">MESIN :</span>
                    <span class="ml-2">_______________</span>
                </td>
                <td class="hj-value">
                    <span class="hj-sub-label">METAL FINISH MEJA :</span>
                    <span class="ml-2">_______________</span>
                </td>
            </tr>
            <tr>
                <td class="hj-value"></td>
                <td class="hj-value"></td>
            </tr>
            {{-- JOB NO | NAMA PART --}}
            <tr>
                <td class="hj-label">JOB NO</td>
                <td colspan="2" class="hj-value">
                    <div class="flex items-center">
                        <span class="w-1/2 font-mono">{{ $item->job_no ?? '________________________' }}</span>
                        <span class="w-1/2">
                            <span class="hj-label" style="background:none;width:auto;display:inline;padding:0">NAMA PART :</span>
                            <span class="ml-1">{{ $item->nama_part ?? '________________________' }}</span>
                        </span>
                    </div>
                </td>
            </tr>
            {{-- JENIS HAMBATAN --}}
            <tr>
                <td class="hj-label" rowspan="2">JENIS<br>HAMBATAN</td>
                <td colspan="2" class="hj-value">
                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                        @php $jenisList = ['DT','MT','SMT','MST','SPT','PT','JT','QT','Prot']; @endphp
                        @foreach($jenisList as $j)
                        <label class="hj-check-item">
                            <input type="checkbox" {{ $item->jenis_hambatan === $j ? 'checked' : '' }} disabled>
                            <span>{{ $j }}</span>
                        </label>
                        @endforeach
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="hj-value">
                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                        @php $subJenisList = ['DTr','JTr','Acd','NO','UBP','ES','LAINNYA']; @endphp
                        @foreach($subJenisList as $sj)
                        <label class="hj-check-item">
                            <input type="checkbox" {{ $item->sub_jenis === $sj ? 'checked' : '' }} disabled>
                            <span>{{ $sj }}</span>
                        </label>
                        @endforeach
                        <span class="text-gray-500 ml-2">:</span>
                        <span class="ml-1 text-gray-800">{{ $item->sub_jenis && !in_array($item->sub_jenis, $subJenisList) ? $item->sub_jenis : '' }}</span>
                    </div>
                </td>
            </tr>
            {{-- PIC HAMBATAN --}}
            <tr>
                <td class="hj-label">PIC<br>HAMBATAN</td>
                <td colspan="2" class="hj-value">@if($item->status === 'signed' && $item->signer){{ $item->signer->name }} ({{ $item->signer->nrp }})@else{{ $item->pic_hambatan ?? '________________________' }}@endif</td>
            </tr>
            {{-- WAKTU --}}
            <tr>
                <td class="hj-label">WAKTU</td>
                <td colspan="2" class="hj-value">{{ $item->waktu ? \Carbon\Carbon::parse($item->waktu)->format('d M Y H:i') : '________________________' }}</td>
            </tr>
            {{-- PCS NUMBER (dari Repair/Reject) --}}
            @if($repairRejectLogs->isNotEmpty())
            <tr>
                <td class="hj-label">PCS No</td>
                <td colspan="2" class="hj-value">
                    @foreach($repairRejectLogs as $rr)
                    <div class="flex items-center gap-2 mb-1 last:mb-0">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $rr->type === 'repair' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800' }}">{{ strtoupper($rr->type) }}</span>
                        <span class="text-gray-800 font-semibold">{{ $rr->pcs_number ?? '-' }}</span>
                        @if($rr->defect_name)
                        <span class="text-gray-500 text-[11px]">— {{ $rr->defect_name }}</span>
                        @endif
                    </div>
                    @endforeach
                </td>
            </tr>
            @endif
            {{-- PROBLEM --}}
            <tr>
                <td class="hj-label">PROBLEM</td>
                <td colspan="2">
                    @if($canPicSign)
                        <textarea name="problem" rows="3" class="hj-textarea">{{ $item->problem }}</textarea>
                    @else
                        <span class="hj-value whitespace-pre-wrap">{{ $item->problem ?? '________________________' }}</span>
                    @endif
                </td>
            </tr>
            {{-- PENYEBAB --}}
            <tr>
                <td class="hj-label">PENYEBAB</td>
                <td colspan="2">
                    @if($canPicSign)
                        <textarea name="penyebab" rows="2" class="hj-textarea">{{ $item->penyebab }}</textarea>
                    @else
                        <span class="hj-value whitespace-pre-wrap">{{ $item->penyebab ?? '________________________' }}</span>
                    @endif
                </td>
            </tr>
            {{-- PENANGGULANGAN --}}
            <tr>
                <td class="hj-label">PENANGGULANGAN</td>
                <td colspan="2">
                    @if($canPicSign)
                        <textarea name="penanggulangan" rows="2" class="hj-textarea">{{ $item->penanggulangan }}</textarea>
                    @else
                        <span class="hj-value whitespace-pre-wrap">{{ $item->penanggulangan ?? '________________________' }}</span>
                    @endif
                </td>
            </tr>
        </table>

        {{-- KETERANGAN --}}
        <div class="hj-keterangan">
            <p class="font-extrabold text-[#991b1b] mb-2 text-xs uppercase tracking-wide">Keterangan :</p>
            <div class="hj-keterangan-grid">
                <div>1. Die Trouble (DT)</div>
                <div>5. Single Part Trouble (SPT)</div>
                <div>9. Production Trouble (ProT)</div>
                <div>2. Machine Trouble (MT)</div>
                <div>6. Pallet Trouble (PaT)</div>
                <div>10. Die Trial (DTr)</div>
                <div>3. Supp. Mach. Trouble (SMT)</div>
                <div>7. Jig Trouble (JT)</div>
                <div>11. Jig Trial (JTr)</div>
                <div>4. Material Sheet Trouble (MST)</div>
                <div>8. Quality Trouble (QT)</div>
                <div>12. Accident (Acd)</div>
                <div></div>
                <div>13. No Order (NO)</div>
                <div></div>
                <div></div>
                <div>14. Un Balance Process (UBP)</div>
                <div></div>
                <div></div>
                <div>15. Electrical Shutdown (ES)</div>
                <div></div>
            </div>
        </div>

        {{-- CATATAN --}}
        <div class="hj-catatan">
            <p class="font-bold text-[#991b1b] text-xs">Catatan :</p>
            <ol class="list-decimal list-inside text-gray-600 ml-1 mt-1">
                <li>Laporan Hambatan Jalur ini dianggap SAH jika ditandatangani oleh GL / FRM Jalur Produksi dan PIC Hambatan</li>
                <li>Lembar Putih : PRODUKSI</li>
                <li>Lembar Merah : PIC HAMBATAN</li>
            </ol>
        </div>

        {{-- TANGGAL --}}
        <div class="px-6 py-3 border-b border-slate-200 text-xs text-gray-700 font-medium">
            Bekasi, {{ $item->signed_at ? \Carbon\Carbon::parse($item->signed_at)->format('d F Y') : '_______________________ 20____' }}
        </div>

        {{-- SIGNATURE --}}
        @php $sigInputName = $canPicSign ? 'signature_image' : 'leader_signature_image'; @endphp
        <table class="hj-sig-table">
            <tr>
                <td>
                    <p class="font-bold text-[#991b1b] text-[11px] mb-2">PIC Hambatan</p>
                    @if($canPicSign)
                        <input type="hidden" name="{{ $sigInputName }}" id="signature_image" value="">
                        <div id="signatureTrigger" class="hj-sig-trigger">
                            <svg class="w-7 h-7 text-red-300 mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            <p class="text-gray-400 text-[11px]">Nama : _______________</p>
                            <p class="text-gray-400 text-[11px]">NRP  : _______________</p>
                            <p class="text-xs text-red-600 font-bold mt-1">Klik untuk tanda tangan</p>
                        </div>
                            <div id="signatureArea" class="hidden mt-2">
                                <div class="border-2 border-dashed border-red-200 rounded-xl overflow-hidden bg-white mx-auto" style="max-width:250px;height:100px;">
                                    <canvas id="signatureCanvas" width="250" height="100" style="display:block;width:100%;max-width:250px;height:100px;" class="touch-none cursor-crosshair"></canvas>
                            </div>
                            <div class="flex items-center justify-center gap-2 mt-3">
                                <button type="button" id="clearSignature" class="px-3 py-1.5 text-[10px] font-bold text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Hapus</button>
                                <button type="button" id="confirmSignature" class="px-4 py-1.5 text-[10px] font-bold text-white bg-gradient-to-r from-red-600 to-red-700 rounded-lg hover:shadow-lg shadow-red-500/25 transition">Selesai</button>
                                <button type="button" id="cancelSignature" class="px-3 py-1.5 text-[10px] font-bold text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Batal</button>
                            </div>
                        </div>
                    @elseif($item->signature_image)
                        <div class="flex justify-center my-2"><img src="{{ $item->signature_image }}" alt="Tanda Tangan" class="max-h-16"></div>
                        <p class="text-sm font-bold text-gray-800 mt-1">{{ $item->signer->name ?? '_______________' }}</p>
                        <p class="text-[10px] text-gray-500">{{ $item->signer->role ?? '_______________' }} — NRP : {{ $item->signer->nrp ?? '_______________' }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ $item->signed_at ? \Carbon\Carbon::parse($item->signed_at)->format('d M Y H:i') : '' }}</p>
                    @else
                        <div class="mt-6 mb-6"></div>
                        <p class="text-gray-400 text-[11px]">Nama : _______________</p>
                        <p class="text-gray-400 text-[11px]">NRP  : _______________</p>
                    @endif
                </td>
                <td>
                    <p class="font-bold text-[#991b1b] text-[11px] mb-2">Diperiksa oleh,</p>
                    @if($canLeaderSign)
                        <input type="hidden" name="{{ $sigInputName }}" id="leader_signature_image" value="">
                        <div id="leaderSignatureTrigger" class="hj-sig-trigger">
                            <svg class="w-7 h-7 text-red-300 mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            <p class="text-gray-400 text-[11px]">Nama : _______________</p>
                            <p class="text-gray-400 text-[11px]">NRP  : _______________</p>
                            <p class="text-xs text-red-600 font-bold mt-1">Klik untuk tanda tangan</p>
                        </div>
                        <div id="leaderSignatureArea" class="hidden mt-2">
                                <div class="border-2 border-dashed border-red-200 rounded-xl overflow-hidden bg-white mx-auto" style="max-width:250px;height:100px;">
                                    <canvas id="leaderSignatureCanvas" width="250" height="100" style="display:block;width:100%;max-width:250px;height:100px;" class="touch-none cursor-crosshair"></canvas>
                            </div>
                            <div class="flex items-center justify-center gap-2 mt-3">
                                <button type="button" id="clearLeaderSignature" class="px-3 py-1.5 text-[10px] font-bold text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Hapus</button>
                                <button type="button" id="confirmLeaderSignature" class="px-4 py-1.5 text-[10px] font-bold text-white bg-gradient-to-r from-red-600 to-red-700 rounded-lg hover:shadow-lg shadow-red-500/25 transition">Selesai</button>
                                <button type="button" id="cancelLeaderSignature" class="px-3 py-1.5 text-[10px] font-bold text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Batal</button>
                            </div>
                        </div>
                    @elseif($item->leader_signature_image)
                        <div class="flex justify-center my-2"><img src="{{ $item->leader_signature_image }}" alt="Tanda Tangan" class="max-h-16"></div>
                        <p class="text-sm font-bold text-gray-800 mt-1">{{ $item->leaderSigner->name ?? '_______________' }}</p>
                        <p class="text-[10px] text-gray-500">{{ $item->leaderSigner->role ?? '_______________' }} — NRP : {{ $item->leaderSigner->nrp ?? '_______________' }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ $item->leader_signed_at ? \Carbon\Carbon::parse($item->leader_signed_at)->format('d M Y H:i') : '' }}</p>
                    @else
                        <div class="mt-8 mb-8"></div>
                        <p class="text-gray-400 text-[11px]">Nama : _______________</p>
                        <p class="text-gray-400 text-[11px]">NRP  : _______________</p>
                    @endif
                </td>
                <td>
                    <p class="font-bold text-[#991b1b] text-[11px] mb-2">Dilaporkan oleh,</p>
                    <div class="mt-8 mb-8"></div>
                    <p class="text-gray-400 text-[11px]">Nama : _______________</p>
                    <p class="text-gray-400 text-[11px]">NRP  : _______________</p>
                </td>
            </tr>
        </table>

        {{-- STATUS FOOTER --}}
        @if($canPicSign)
        <div class="hj-footer-pending">
            <p class="text-xs text-red-700 font-semibold">✏️ Gambar tanda tangan di atas, lalu klik <strong>Selesai</strong></p>
        </div>
        @elseif($canLeaderSign)
        <div class="hj-footer-pending">
            <p class="text-xs text-red-700 font-semibold">✏️ Tanda tangan sebagai GL / FRM, lalu klik <strong>Selesai</strong></p>
        </div>
        @elseif($item->status === 'signed')
        <div class="hj-footer-signed rounded-b-[16px]">
            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span class="text-sm font-black text-emerald-700 uppercase tracking-wider">Selesai</span>
        </div>
        @endif
    </div>

    @if($showForm)
    </form>
    @endif

    @if($showForm)
    @php $isLeader = $canLeaderSign ? 'true' : 'false'; @endphp
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isLeader = {{ $isLeader }};
        const prefix = isLeader ? 'leader' : '';
        const triggerId = prefix ? 'leaderSignatureTrigger' : 'signatureTrigger';
        const areaId = prefix ? 'leaderSignatureArea' : 'signatureArea';
        const canvasId = prefix ? 'leaderSignatureCanvas' : 'signatureCanvas';
        const clearId = prefix ? 'clearLeaderSignature' : 'clearSignature';
        const confirmId = prefix ? 'confirmLeaderSignature' : 'confirmSignature';
        const cancelId = prefix ? 'cancelLeaderSignature' : 'cancelSignature';
        const hiddenId = prefix ? 'leader_signature_image' : 'signature_image';

        const trigger = document.getElementById(triggerId);
        const area = document.getElementById(areaId);
        const canvas = document.getElementById(canvasId);
        const hiddenInput = document.getElementById(hiddenId);
        const form = document.getElementById('hj-sign-form');
        let pad = null;
        function initPad() {
            if (pad) return;
            canvas.width = 250; canvas.height = 100;
            canvas.style.width = '250px'; canvas.style.height = '100px';
            try { pad = new SignaturePad(canvas, { backgroundColor:'rgb(255,255,255)', penColor:'rgb(0,0,0)', minWidth:1, maxWidth:3 }); } catch(e) { console.error(e); }
        }
        trigger.addEventListener('click', function() { trigger.classList.add('hidden'); area.classList.remove('hidden'); initPad(); });
        document.getElementById(clearId).addEventListener('click', function() { if(pad) pad.clear(); });
        document.getElementById(confirmId).addEventListener('click', function() {
            if(!pad || pad.isEmpty()) { alert('Silakan tanda tangan terlebih dahulu.'); return; }
            hiddenInput.value = pad.toDataURL(); form.submit();
        });
        document.getElementById(cancelId).addEventListener('click', function() { area.classList.add('hidden'); trigger.classList.remove('hidden'); if(pad){pad.clear();pad=null;} });
    });
    </script>
    @endif
</div>
@endsection
