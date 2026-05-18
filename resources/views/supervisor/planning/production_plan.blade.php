@extends('layouts.ppc')

@section('content')
<div class="min-h-screen bg-slate-50">
    {{-- Header Section --}}
    <div class="bg-white border-b border-slate-200">
        <div class="px-6 py-6 mx-auto max-w-screen-2xl">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-800">PRODUCTION PLANNING</h1>
                    <p class="mt-1 text-sm font-medium text-slate-500">Kelola jadwal produksi harian dan sinkronisasi dengan operasional.</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Filter Tanggal --}}
                    <div class="relative group">
                        <input type="date" id="planDateFilter" value="{{ $date ?? date('Y-m-d') }}" 
                               class="pl-10 pr-4 py-2.5 bg-slate-100 border-none rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-hover:text-indigo-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Tombol Import --}}
                    <button onclick="openImportModal()" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        IMPORT EXCEL
                    </button>
                </div>
            </div>

            {{-- Summary Stats --}}
            <div class="grid grid-cols-2 gap-4 mt-8 md:grid-cols-4">
                <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-wider">Total Planning</p>
                    <p class="mt-1 text-2xl font-black text-indigo-700">{{ $plans->where('row_type', 'job')->count() }} <span class="text-sm font-bold text-indigo-400">Jobs</span></p>
                </div>
                <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                    <p class="text-[10px] font-black text-emerald-400 uppercase tracking-wider">Target Qty</p>
                    <p class="mt-1 text-2xl font-black text-emerald-700">{{ number_format($plans->sum('plan')) }} <span class="text-sm font-bold text-emerald-400">Pcs</span></p>
                </div>
                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
                    <p class="text-[10px] font-black text-amber-400 uppercase tracking-wider">Approved</p>
                    <p class="mt-1 text-2xl font-black text-amber-700">{{ $plans->where('status', 'approved')->count() }} <span class="text-sm font-bold text-amber-400">Jobs</span></p>
                </div>
                <div class="p-4 bg-slate-100 rounded-2xl border border-slate-200">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Pending</p>
                    <p class="mt-1 text-2xl font-black text-slate-700">{{ $plans->where('status', 'pending')->count() }} <span class="text-sm font-bold text-slate-400">Jobs</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="px-6 py-8 mx-auto max-w-screen-2xl">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[1800px]">
                    <thead>
                        <tr class="bg-slate-800">
                            <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700">#</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest border-r border-slate-700">Job Master</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700 w-20">Type</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">Qty/Plt</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">Total Plt</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest border-r border-slate-700">Job No.</th>
                            <th class="px-4 py-4 text-[10px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700">Plan</th>
                            <th class="px-4 py-4 text-[10px] font-black text-emerald-400 uppercase tracking-widest text-center border-r border-slate-700">OK</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700 w-16">Mesin</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">CT (")</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">Proc Time</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">Reg Act</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">DCT</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">MCT</th>
                            <th class="px-4 py-4 text-[10px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700">TPT</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center border-r border-slate-700">GSPH</th>
                            <th class="px-4 py-4 text-[10px] font-black text-indigo-400 uppercase tracking-widest text-center border-r border-slate-700">Start</th>
                            <th class="px-4 py-4 text-[10px] font-black text-indigo-400 uppercase tracking-widest text-center border-r border-slate-700">Finish</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest border-r border-slate-700">Keterangan</th>
                            <th class="px-4 py-4 text-[10px] font-black text-slate-200 uppercase tracking-widest text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($plans as $plan)
                        @if($plan->row_type === 'break')
                        <tr class="bg-slate-50/50 italic font-medium">
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td colspan="4" class="px-4 py-3 text-slate-500 font-black uppercase text-xs tracking-wider">{{ $plan->job_no }}</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center font-bold text-slate-600">{{ $plan->dct }}</td>
                            <td class="px-4 py-3 text-center font-bold text-slate-600">{{ $plan->mct }}</td>
                            <td class="px-4 py-3 text-center font-black text-amber-600">{{ $plan->tpt }}</td>
                            <td class="px-4 py-3 text-center text-slate-400">—</td>
                            <td class="px-4 py-3 text-center font-mono text-xs font-bold text-indigo-600">{{ $plan->start_time }}</td>
                            <td class="px-4 py-3 text-center font-mono text-xs font-bold text-indigo-600">{{ $plan->finish_time }}</td>
                            <td class="px-4 py-3">—</td>
                            <td class="px-4 py-3 text-center">—</td>
                        </tr>
                        @else
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-4 py-3 text-center text-[11px] font-bold text-slate-400 border-r border-slate-100">{{ $plan->row_no }}</td>
                            <td class="px-4 py-3 border-r border-slate-100">
                                <input type="text" value="{{ $plan->job_master }}" 
                                       onchange="updateInline({{ $plan->id }}, 'job_master', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-slate-800 p-0">
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-indigo-100 text-indigo-700 uppercase tracking-tighter">{{ $plan->type_plt }}</span>
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100">
                                <input type="number" value="{{ $plan->qty_plt }}" 
                                       onchange="updateInline({{ $plan->id }}, 'qty_plt', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0">
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">
                                {{ number_format($plan->total_plt, 1) }}
                            </td>
                            <td class="px-4 py-3 border-r border-slate-100">
                                <input type="text" value="{{ $plan->job_no }}" 
                                       onchange="updateInline({{ $plan->id }}, 'job_no', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm font-medium text-slate-600 p-0">
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100">
                                <input type="number" value="{{ $plan->plan }}" 
                                       onchange="updateInline({{ $plan->id }}, 'plan', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-amber-600 text-center p-0">
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-black text-emerald-600 text-sm">
                                {{ number_format($plan->ok) }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100">
                                <input type="number" value="{{ $plan->total_mesin }}" 
                                       onchange="updateInline({{ $plan->id }}, 'total_mesin', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0">
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100">
                                <input type="number" step="0.1" value="{{ $plan->ct_detik }}" 
                                       onchange="updateInline({{ $plan->id }}, 'ct_detik', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0">
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">
                                {{ number_format($plan->process_time, 1) }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100">
                                <input type="number" value="{{ $plan->reg_active }}" 
                                       onchange="updateInline({{ $plan->id }}, 'reg_active', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0">
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-sm">
                                {{ $plan->dct }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-sm">
                                {{ $plan->mct }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-black text-amber-700 text-sm">
                                {{ $plan->tpt }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-sm">
                                {{ number_format($plan->gsph_item) }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600">
                                {{ $plan->start_time }}
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600">
                                {{ $plan->finish_time }}
                            </td>
                            <td class="px-4 py-3 border-r border-slate-100">
                                <input type="text" value="{{ $plan->keterangan }}" 
                                       onchange="updateInline({{ $plan->id }}, 'keterangan', this.value)"
                                       class="w-full bg-transparent border-none focus:ring-0 text-xs font-medium text-slate-500 p-0">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <select onchange="updateInline({{ $plan->id }}, 'status', this.value)"
                                        class="bg-transparent border-none focus:ring-0 text-[10px] font-black uppercase tracking-widest
                                        {{ $plan->status === 'approved' ? 'text-emerald-600' : ($plan->status === 'pending' ? 'text-amber-500' : 'text-slate-400') }}">
                                    <option value="pending" {{ $plan->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $plan->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="completed" {{ $plan->status === 'completed' ? 'selected' : '' }}>Done</option>
                                </select>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="20" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <h3 class="text-slate-800 font-black text-lg">BELUM ADA JADWAL</h3>
                                    <p class="text-slate-500 text-sm max-w-xs mx-auto mt-1">Silahkan import file Excel schedule stamping untuk melihat perencanaan produksi hari ini.</p>
                                    <button onclick="openImportModal()" class="mt-6 px-6 py-2.5 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-700 transition-all">
                                        Mulai Import
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Import --}}
<div id="importModal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeImportModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="px-8 py-8">
                <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m11 0h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">IMPORT JADWAL</h3>
                <p class="text-slate-500 text-sm mt-1">Upload file Excel (.xlsx, .xlsm) untuk sinkronisasi jadwal produksi.</p>

                <form id="importForm" action="{{ route('supervisor.planning.production_plan.import') }}" method="POST" enctype="multipart/form-data" class="mt-8">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Pilih File Excel</label>
                            <input type="file" name="excel_file" required accept=".xlsx,.xls,.xlsm"
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 transition-all border border-slate-200 rounded-xl p-1">
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Tanggal Rencana</label>
                            <input type="text" name="date" value="{{ date('d F Y') }}" placeholder="Contoh: 10 Mei 2026"
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="closeImportModal()" class="flex-1 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                            BATAL
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-black shadow-lg shadow-indigo-100 transition-all">
                            IMPORT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

@push('scripts')
<script>
    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
    }

    document.getElementById('planDateFilter').addEventListener('change', function() {
        window.location.href = `{{ route('supervisor.planning.production_plan') }}?date=${this.value}`;
    });

    function updateInline(id, field, value) {
        fetch(`{{ route('supervisor.planning.production_plan.inline') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ id, field, value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // For certain fields, we might want to reload to see calculated results (TPT, Start/Finish)
                const calcFields = ['plan', 'qty_plt', 'ct_detik', 'status', 'total_mesin', 'reg_active'];
                if (calcFields.includes(field)) {
                    window.location.reload();
                }
                // Show a small toast notification could be nice here
            } else {
                alert('Gagal mengupdate data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan data.');
        });
    }

    // Form Submission for Import
    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        
        submitBtn.disabled = true;
        submitBtn.innerText = 'IMPORTING...';

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Import gagal: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan fatal saat import.');
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
    });
</script>
@endpush
@endsection
