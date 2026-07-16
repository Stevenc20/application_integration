@extends('layouts.supervisor')

@section('title', 'Pencatatan Handwork')

@section('head')
<style>
.hw-card { transition: box-shadow 0.2s, border-color 0.2s; }
.hw-card:hover { box-shadow: 0 8px 30px rgba(192,57,43,0.10); border-color: #fca5a5; }
.stat-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
.stat-ok  { background:#dcfce7; color:#15803d; }
.stat-ng  { background:#fee2e2; color:#b91c1c; }
.stat-neutral { background:#f1f5f9; color:#64748b; }
.detail-panel { animation: slideDown 0.2s ease; }
@keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
.btn-primary { background:#C0392B; color:#fff; border:none; border-radius:10px; padding:9px 20px; font-weight:700; font-size:0.85rem; cursor:pointer; transition:background 0.2s; display:inline-flex; align-items:center; gap:6px; }
.btn-primary:hover { background:#a93226; }
.btn-icon { background:none; border:none; cursor:pointer; padding:6px 10px; border-radius:8px; transition:background 0.15s; display:inline-flex; align-items:center; }
.btn-icon:hover { background:#fee2e2; color:#b91c1c; }
.form-input { width:100%; border:1.5px solid #e5e7eb; border-radius:9px; padding:8px 12px; font-size:0.85rem; transition:border-color 0.2s; outline:none; background:#fafafa; }
.form-input:focus { border-color:#C0392B; background:#fff; box-shadow:0 0 0 3px rgba(192,57,43,0.08); }
.hw-table th { background:#f8fafc; color:#64748b; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; padding:10px 12px; border-bottom:2px solid #f1f5f9; white-space:nowrap; }
.hw-table td { padding:10px 12px; font-size:0.85rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.hw-table tr:hover td { background:#fafafa; }
.hw-table tr:last-child td { border-bottom:none; }
</style>
@endsection

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-gray-200 rounded-2xl px-5 py-4 sm:px-8 sm:py-5 shadow-sm border-l-4 border-l-red-400">
        <div>
            <h1 class="text-xl font-black text-gray-800 uppercase tracking-wide leading-tight">Pencatatan Handwork</h1>
            <p class="text-sm text-gray-400 mt-0.5 font-medium">Supervisor — Repair & Reject Log Per Job</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @if($lineName && $shift)
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                <i class="bx bx-map-pin text-sm"></i>{{ $lineName }} &bull; {{ ucfirst($shift) }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse inline-block"></span>LIVE
            </span>
            @endif
        </div>
    </div>

    {{-- FILTER --}}
    <div class="bg-white border border-gray-200 rounded-2xl px-5 py-4 shadow-sm">
        <form method="GET" action="{{ route('supervisor.handwork.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        <i class="bx bx-calendar mr-1"></i>Tanggal
                    </label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-input">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        <i class="bx bx-git-branch mr-1"></i>Line
                    </label>
                    <select name="line" class="form-input">
                        <option value="">Pilih Line</option>
                        @foreach ($lines as $ln)
                            <option value="{{ $ln }}" {{ $lineName == $ln ? 'selected' : '' }}>{{ $ln }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        <i class="bx bx-time mr-1"></i>Shift
                    </label>
                    <select name="shift" class="form-input">
                        <option value="">Pilih Shift</option>
                        <option value="pagi" {{ $shift == 'pagi' ? 'selected' : '' }}>Pagi</option>
                        <option value="malam" {{ $shift == 'malam' ? 'selected' : '' }}>Malam</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full justify-center">
                        <i class="bx bx-search-alt-2"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- JOB LIST --}}
    @if ($plans->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center">
                    <i class="bx bx-list-check text-red-500 text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-gray-800">Daftar Job</h2>
                    <p class="text-xs text-gray-400">{{ $plans->count() }} job ditemukan</p>
                </div>
            </div>
            <span class="stat-pill stat-neutral">{{ $tanggal }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="hw-table min-w-full">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">Part Number</th>
                        <th class="text-center">Plan</th>
                        <th class="text-center">
                            <span class="text-green-600">Repair OK</span>
                        </th>
                        <th class="text-center">
                            <span class="text-red-600">Reject NG</span>
                        </th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $index => $plan)
                    @php
                        $planRepair = \App\Models\RepairRejectLog::whereHas('jobMaster', function ($q) use ($plan) {
                            $q->where('job_number', 'like', $plan->job_no . '%');
                        })->where('type', 'repair')->sum('qty_a');
                        $planReject = \App\Models\RepairRejectLog::whereHas('jobMaster', function ($q) use ($plan) {
                            $q->where('job_number', 'like', $plan->job_no . '%');
                        })->where('type', 'reject')->sum('qty_a');
                    @endphp
                    <tr class="job-row cursor-pointer" id="row-{{ $plan->id }}">
                        <td class="text-gray-400 font-semibold w-10">{{ $index + 1 }}</td>
                        <td>
                            <div class="font-bold text-gray-800 text-sm">{{ $plan->job_master ?? $plan->job_no }}</div>
                            @if($plan->keterangan)
                            <div class="text-xs text-gray-400 mt-0.5">{{ $plan->keterangan }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="font-bold text-gray-700">{{ $plan->plan ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="stat-pill {{ $planRepair > 0 ? 'stat-ok' : 'stat-neutral' }}">
                                <i class="bx bx-check-circle text-xs"></i> {{ $planRepair }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="stat-pill {{ $planReject > 0 ? 'stat-ng' : 'stat-neutral' }}">
                                <i class="bx bx-x-circle text-xs"></i> {{ $planReject }}
                            </span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn-detail btn-icon text-blue-500 hover:text-blue-700" data-plan-id="{{ $plan->id }}" title="Lihat Detail">
                                <i class="bx bx-chevron-down text-xl transition-transform duration-200" id="chevron-{{ $plan->id }}"></i>
                            </button>
                        </td>
                    </tr>
                    <tr id="detail-row-{{ $plan->id }}" class="hidden">
                        <td colspan="6" class="p-0">
                            <div id="detail-content-{{ $plan->id }}" class="bg-slate-50 border-t border-dashed border-gray-200 detail-panel px-6 py-5 min-h-[80px]">
                                <div class="flex items-center justify-center gap-3 py-6 text-gray-400">
                                    <i class="bx bx-loader-alt bx-spin text-2xl text-red-400"></i>
                                    <span class="text-sm font-medium">Memuat detail...</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @elseif ($lineName && $shift)
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="flex flex-col items-center justify-center py-16 gap-4 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center">
                <i class="bx bx-package text-3xl text-gray-300"></i>
            </div>
            <div>
                <p class="font-bold text-gray-600 text-sm">Tidak Ada Job</p>
                <p class="text-gray-400 text-xs mt-1">Tidak ada job untuk filter yang dipilih.</p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="flex flex-col items-center justify-center py-16 gap-4 text-center">
            <div class="w-16 h-16 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center">
                <i class="bx bx-filter-alt text-3xl text-red-300"></i>
            </div>
            <div>
                <p class="font-bold text-gray-600 text-sm">Pilih Filter Terlebih Dahulu</p>
                <p class="text-gray-400 text-xs mt-1">Pilih tanggal, line, dan shift untuk melihat data handwork.</p>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- Delete Confirm Modal --}}
<div id="hw-delete-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm border border-gray-100 overflow-hidden transform transition-all duration-200 scale-95 opacity-0" id="hw-delete-dialog">
        <div class="flex items-center gap-3 px-6 pt-6 pb-4">
            <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                <i class="bx bx-trash text-red-500 text-xl"></i>
            </div>
            <div>
                <h3 class="font-black text-gray-800 text-sm">Hapus Catatan?</h3>
                <p class="text-xs text-gray-400 mt-0.5">Tindakan ini tidak bisa dibatalkan.</p>
            </div>
        </div>
        <div class="flex gap-2 px-6 pb-6">
            <button id="hw-delete-cancel" class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl transition-all">
                Batal
            </button>
            <button id="hw-delete-confirm" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
                <i class="bx bx-trash text-sm"></i> Hapus
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="hw-toast" class="fixed top-5 right-5 z-50 hidden max-w-xs"></div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Auto-submit filter on change
    const filterForm = document.querySelector('form[action*="handwork"]');
    if (filterForm) {
        filterForm.querySelectorAll('input[name="tanggal"], select[name="line"], select[name="shift"]').forEach(el => {
            el.addEventListener('change', () => {
                // Only auto-submit if line and shift are both filled
                const line = filterForm.querySelector('select[name="line"]').value;
                const shift = filterForm.querySelector('select[name="shift"]').value;
                if (line && shift) {
                    filterForm.requestSubmit();
                }
            });
        });
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('hw-toast');
        const bg = type === 'success' ? '#16a34a' : '#dc2626';
        const icon = type === 'success' ? 'bx-check-circle' : 'bx-x-circle';
        toast.innerHTML = `<div style="background:${bg}" class="text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 text-sm font-semibold">
            <i class="bx ${icon} text-lg"></i><span>${message}</span>
        </div>`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    }

    // Custom delete modal
    let _deleteCallback = null;
    const deleteModal  = document.getElementById('hw-delete-modal');
    const deleteDialog = document.getElementById('hw-delete-dialog');

    function openDeleteModal(callback) {
        _deleteCallback = callback;
        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
        requestAnimationFrame(() => {
            deleteDialog.classList.remove('scale-95', 'opacity-0');
            deleteDialog.classList.add('scale-100', 'opacity-100');
        });
    }

    function closeDeleteModal() {
        deleteDialog.classList.remove('scale-100', 'opacity-100');
        deleteDialog.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            deleteModal.classList.add('hidden');
            deleteModal.classList.remove('flex');
        }, 180);
    }

    document.getElementById('hw-delete-cancel').addEventListener('click', closeDeleteModal);
    deleteModal.addEventListener('click', e => { if (e.target === deleteModal) closeDeleteModal(); });
    document.getElementById('hw-delete-confirm').addEventListener('click', function() {
        closeDeleteModal();
        if (typeof _deleteCallback === 'function') _deleteCallback();
    });

    document.querySelectorAll('.btn-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            const planId = this.dataset.planId;
            const detailRow = document.getElementById('detail-row-' + planId);
            const detailContent = document.getElementById('detail-content-' + planId);
            const chevron = document.getElementById('chevron-' + planId);

            if (!detailRow.classList.contains('hidden')) {
                detailRow.classList.add('hidden');
                chevron.style.transform = 'rotate(0deg)';
                return;
            }

            document.querySelectorAll('[id^="detail-row-"]').forEach(r => r.classList.add('hidden'));
            document.querySelectorAll('[id^="chevron-"]').forEach(c => c.style.transform = 'rotate(0deg)');

            detailRow.classList.remove('hidden');
            chevron.style.transform = 'rotate(180deg)';

            if (detailContent.dataset.loaded) return;

            fetch('{{ url("supervisor/handwork/api/detail") }}/' + planId)
                .then(r => r.json())
                .then(data => {
                    detailContent.dataset.loaded = '1';
                    renderDetail(detailContent, data);
                })
                .catch(() => {
                    detailContent.innerHTML = '<div class="text-center text-red-400 py-6 text-sm font-medium">Gagal memuat detail.</div>';
                });
        });
    });

    function renderDetail(container, data) {
        let html = `
        <div class="flex flex-wrap items-center gap-3 mb-5">
            <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-2">
                <i class="bx bx-check-circle text-green-500 text-lg"></i>
                <div><div class="text-xs text-green-600 font-semibold">Repair OK</div><div class="text-lg font-black text-green-700">${data.total_repair}</div></div>
            </div>
            <div class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-2">
                <i class="bx bx-x-circle text-red-500 text-lg"></i>
                <div><div class="text-xs text-red-600 font-semibold">Reject NG</div><div class="text-lg font-black text-red-700">${data.total_reject}</div></div>
            </div>
            <div class="ml-auto text-xs text-gray-400 font-medium">${data.job_no} &mdash; ${data.line_name}</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-5 mb-5 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <i class="bx bx-plus-circle text-red-400 text-lg"></i>
                <span class="text-sm font-black text-gray-700">Tambah Catatan Handwork</span>
            </div>
            <form class="add-handwork-form" data-plan-id="${data.plan_id}" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Deskripsi Masalah</label>
                        <textarea name="problem_hw" rows="2" required class="form-input" placeholder="Deskripsikan masalah handwork..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Jumlah</label>
                        <input type="number" name="qty" value="1" min="1" required class="form-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nomor Pcs (opsional)</label>
                        <input type="text" name="pcs_number" placeholder="Contoh: 5, 5-8, 3,5,7" class="form-input">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Status Hasil</label>
                        <select name="status" required class="form-input">
                            <option value="ok">✅ OK — Repair Berhasil</option>
                            <option value="ng">❌ NG — Reject</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Foto Sebelum (dari leader)</label>
                        <div id="before-photos" class="flex flex-wrap gap-2 min-h-[32px]"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Foto Sesudah Perbaikan</label>
                        <input type="file" name="foto_sesudah" accept="image/*" class="form-input">
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="bx bx-save"></i> Simpan Catatan
                </button>
            </form>
        </div>`;

        if (data.items.length === 0) {
            html += `<div class="text-center py-8 text-gray-400 text-sm font-medium">
                <i class="bx bx-notepad text-3xl text-gray-200 block mb-2"></i>
                Belum ada catatan handwork untuk job ini.
            </div>`;
        } else {
            html += `<div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
                <table class="hw-table min-w-full">
                    <thead><tr>
                        <th class="text-center">Status</th>
                        <th class="text-left">Deskripsi</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Pcs Ke-</th>
                        <th class="text-center">Foto Sebelum</th>
                        <th class="text-center">Foto Sesudah</th>
                        <th class="text-center">Operator</th>
                        <th class="text-center"></th>
                    </tr></thead>
                    <tbody>`;

            data.items.forEach(item => {
                const statusBadge = item.status === 'ok'
                    ? '<span class="stat-pill stat-ok"><i class="bx bx-check text-xs"></i> OK</span>'
                    : '<span class="stat-pill stat-ng"><i class="bx bx-x text-xs"></i> NG</span>';
                const sebelumHtml = item.foto_sebelum
                    ? `<a href="${item.foto_sebelum}" target="_blank" class="inline-flex items-center gap-1 text-blue-500 hover:text-blue-700 text-xs font-semibold"><i class="bx bx-image-alt"></i> Lihat</a>`
                    : '<span class="text-gray-300 text-xs">—</span>';
                const sesudahHtml = item.foto_sesudah
                    ? `<a href="${item.foto_sesudah}" target="_blank" class="inline-flex items-center gap-1 text-blue-500 hover:text-blue-700 text-xs font-semibold"><i class="bx bx-image-alt"></i> Lihat</a>`
                    : '<span class="text-gray-300 text-xs">—</span>';
                html += `<tr>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-gray-700 max-w-xs">${item.problem_hw}</td>
                    <td class="text-center font-bold text-gray-800">${item.qty}</td>
                    <td class="text-center font-mono text-xs font-bold text-gray-700">${item.pcs_number || '-'}</td>
                    <td class="text-center">${sebelumHtml}</td>
                    <td class="text-center">${sesudahHtml}</td>
                    <td class="text-center text-xs text-gray-500">${item.operator}</td>
                    <td class="text-center">
                        <button type="button" class="btn-delete btn-icon text-red-400 hover:text-red-600" data-id="${item.id}" title="Hapus">
                            <i class="bx bx-trash text-base"></i>
                        </button>
                    </td>
                </tr>`;
            });

            html += `</tbody></table></div>`;
        }

        container.innerHTML = html;

        // Before photos
        const beforePhotosEl = container.querySelector('#before-photos');
        if (beforePhotosEl) {
            if (data.before_photos && data.before_photos.length > 0) {
                data.before_photos.forEach(url => {
                    const a = document.createElement('a');
                    a.href = url; a.target = '_blank';
                    a.className = 'block w-16 h-16 rounded-xl border border-gray-200 overflow-hidden hover:opacity-80 transition-opacity shadow-sm';
                    a.innerHTML = `<img src="${url}" class="w-full h-full object-cover" alt="foto sebelum">`;
                    beforePhotosEl.appendChild(a);
                });
            } else {
                beforePhotosEl.innerHTML = '<span class="text-xs text-gray-400 italic">Tidak ada foto sebelum.</span>';
            }
        }

        container.querySelector('.add-handwork-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('plan_id', this.dataset.planId);
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Menyimpan...';

            const storeUrl = '{{ url("supervisor/handwork/api/store") }}';
            fetch(storeUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            })
            .then(async r => {
                const text = await r.text();
                let res;
                try { res = JSON.parse(text); } catch(e) { res = null; }
                if (r.ok && res?.success) {
                    showToast(res.message, 'success');
                    location.reload();
                } else {
                    const msg = res?.message || text.substring(0,200) || 'Gagal menyimpan';
                    showToast(msg, 'error');
                    console.error('Store failed:', r.status, text);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-save"></i> Simpan Catatan';
                }
            })
            .catch(err => {
                showToast('Terjadi kesalahan: ' + err.message, 'error');
                console.error('Store error:', err);
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-save"></i> Simpan Catatan';
            });
        });

        container.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                openDeleteModal(() => {
                    fetch('{{ url("supervisor/handwork/api/delete") }}/' + id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) { showToast(res.message, 'success'); location.reload(); }
                        else showToast(res.message || 'Gagal menghapus', 'error');
                    })
                    .catch(() => showToast('Terjadi kesalahan', 'error'));
                });
            });
        });
    }

    @if ($expandedPlanId)
    (function() {
        const btn = document.querySelector(`.btn-detail[data-plan-id="{{ $expandedPlanId }}"]`);
        if (btn) btn.click();
    })();
    @endif
});
</script>
@endsection
