@extends('layouts.supervisor')

@section('title', 'Create Data Job')
@section('header_title', 'Create Data Job')

@section('head')
<style>
    .section-title {
        font-weight: 600;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #991b1b;
        margin-bottom: 1rem;
        border-bottom: 2px solid #fee2e2;
        padding-bottom: 0.5rem;
    }

    .item-list-container {
        max-height: 160px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        background-color: #ffffff;
        border-radius: 0 0 0.375rem 0.375rem;
        border-top: none;
    }

    .item-option {
        padding: 8px 12px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        color: #4b5563;
        font-size: 0.875rem;
        transition: background 0.15s, color 0.15s;
    }

    .item-option:last-child {
        border-bottom: none;
    }

    .item-option:hover {
        background-color: #fee2e2;
    }

    .item-option.selected {
        background-color: #991b1b !important;
        color: #fff !important;
        font-weight: 500;
    }
</style>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <h5 class="text-lg font-bold text-gray-800">Create Data Job & Production Plan</h5>
        <p class="text-sm text-gray-500 mt-1">Isi informasi job, rencana produksi, lalu detail item stamping.</p>
    </div>

    <div class="p-6">
        <form action="#" method="post">
            @csrf

            <!-- ========== 1. INFO JOB ========== -->
            <div class="mb-8">
                <div class="section-title">1. Informasi Utama Job</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- PRODUCTION LINE -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Production Line</label>
                        <select id="id_productionline_select" name="id_productionline" class="w-full" required>
                            <option value="" disabled selected>-- Pilih Line & Shift --</option>
                            @foreach($dataproductionline ?? [] as $line)
                                <option value="{{ $line->id }}">
                                    {{ $line->namaline }} - Shift {{ $line->shift }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Ketik nama line / shift untuk mencari.</p>
                    </div>

                    <!-- KARYAWAN -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Karyawan</label>
                        <select id="id_karyawan_select" name="id_karyawan" class="w-full" required>
                            <option value="" disabled selected>-- Pilih Karyawan --</option>
                            @foreach($datakaryawan ?? [] as $item)
                                <option value="{{ $item->id_karyawan }}">
                                    {{ $item->nama_karyawan }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Ketik nama karyawan untuk mencari.</p>
                    </div>

                    <!-- TANGGAL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Produksi</label>
                        <input type="date" name="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                    </div>
                </div>
            </div>

            <!-- ========== 2. PRODUCTION PLAN ========== -->
            <div class="mb-8">
                <div class="section-title">2. Production Plan (Per Shift)</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GSPH Plan</label>
                        <input type="number" name="gsph_plan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Contoh: 250">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stroke Plan</label>
                        <input type="number" name="stroke_plan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Contoh: 18000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Man Power</label>
                        <input type="number" name="mp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Jumlah operator">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Repair Plan (%)</label>
                        <input type="number" step="0.01" name="repair_plan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="0" placeholder="Misal: 5.50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reject Plan (%)</label>
                        <input type="number" step="0.01" name="reject_plan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="0" placeholder="Misal: 2.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Idle Plan (menit)</label>
                        <input type="number" name="idle_plan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="0" placeholder="Total idle satu shift">
                    </div>
                </div>
            </div>

            <!-- ========== 3. DETAIL JOB PER ITEM ========== -->
            <div>
                <div class="section-title border-none mb-2">3. Detail Job per Item</div>
                <p class="text-sm text-gray-500 mb-4">Tambahkan item stamping yang akan dikerjakan dalam job ini.</p>

                <div id="tambahdetailjob" class="space-y-4">

                    <!-- ==== TEMPLATE BARIS DETAIL JOB ==== -->
                    <div class="detail-row bg-gray-50 rounded-lg border border-gray-200 p-4 shadow-sm relative">
                        <div class="flex items-center justify-between border-b border-gray-200 pb-3 mb-4">
                            <h5 class="font-semibold text-gray-800 detail-row-title">Detail Job 1</h5>
                            <div class="flex items-center gap-3">
                                <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded">Item per Job Number & Mesin</span>
                                <button type="button" onclick="deleterow(this)" class="text-red-600 hover:text-red-800 text-sm font-medium px-2 py-1 border border-transparent hover:border-red-200 rounded transition">
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            <!-- ITEM SEARCH -->
                            <div class="lg:col-span-4 item-search-wrapper">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Item Produksi (Job Number)</label>
                                <input type="text" class="search-item-input w-full rounded-t-md border-gray-300 focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" placeholder="Ketik Job Number untuk mencari..." onkeyup="filterItemList(this)">

                                <div class="item-list-container">
                                    @foreach($dataitemproduksi ?? [] as $item)
                                        <div class="item-option" data-id="{{ $item->id }}" data-job="{{ $item->job_number }}" onclick="selectItem(this)">
                                            <div class="font-semibold">{{ $item->job_number }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ $item->part_number }}{{ $item->customer ? ' · ' . $item->customer : '' }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <input type="hidden" name="id_item_produksi[]" class="real-item-input" required>
                                <p class="mt-2 text-xs text-gray-500 selected-text-display">Belum ada item dipilih</p>
                            </div>

                            <!-- PLAN FIELDS -->
                            <div class="lg:col-span-5">
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Plan Qty</label>
                                        <input type="number" name="plan_qty[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" placeholder="Plan" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Plan CT (detik)</label>
                                        <input type="number" step="0.01" name="plan_ct_display[]" class="plan-ct-input w-full rounded-md border-gray-300 bg-gray-100 text-sm" placeholder="Auto" readonly>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Plan DCT</label>
                                        <input type="number" name="plan_dct[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" value="0">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Plan VCT</label>
                                        <input type="number" name="plan_vct[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" value="0">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Plan Idle</label>
                                        <input type="number" name="plan_idle[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" value="0">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Plan Breaktime</label>
                                        <input type="number" name="plan_breaktime[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" value="0">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Plan First QCheck</label>
                                        <input type="number" name="plan_first_qcheck[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- MESIN -->
                            <div class="lg:col-span-3">
                                <label class="block text-xs font-medium text-gray-700 mb-2 border-b border-gray-200 pb-1">Machine Used</label>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-2 machine-container">
                                    @foreach($machines ?? [] as $m)
                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-1 rounded transition">
                                            <input type="checkbox" class="form-checkbox text-primary-red focus:ring-red-500 rounded border-gray-300" name="machine_used_0[]" value="{{ $m->id }}">
                                            <span class="text-sm text-gray-700">{{ $m->code }}</span>
                                        </label>
                                    @endforeach
                                    <!-- Placeholder if empty -->
                                    @if(empty($machines))
                                    <div class="text-xs text-gray-500 italic">Data mesin tidak tersedia.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ==== END TEMPLATE BARIS DETAIL JOB ==== -->

                </div>

                <div class="mt-4">
                    <button type="button" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors flex items-center" onclick="tambahDetailJob()">
                        <i class="bx bx-plus mr-2 text-lg"></i> Tambah Detail Job
                    </button>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('supervisor.job.index') ?? '#' }}" class="px-6 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm transition-colors">Batal</a>
                <button type="submit" class="px-6 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Simpan Job</button>
            </div>

        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const itemsData = {!! $items_json_str ?? '[]' !!};
    const machinesData = {!! $machines_json_str ?? '[]' !!};
    let detailJobCounter = 1;

    $(document).ready(function () {
        $('#id_productionline_select').select2({
            placeholder: "-- Pilih Line & Shift --",
            allowClear: true,
            width: '100%',
        });

        $('#id_karyawan_select').select2({
            placeholder: "-- Pilih Karyawan --",
            allowClear: true,
            width: '100%',
        });
        
        // Add Tailwind classes to select2 container
        $('.select2-container--default .select2-selection--single').addClass('border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 h-[42px] flex items-center');
    });

    function filterItemList(inputElement) {
        const filter = inputElement.value.toUpperCase();
        const listContainer = inputElement.nextElementSibling;
        const items = listContainer.getElementsByClassName('item-option');

        for (let i = 0; i < items.length; i++) {
            const txtValue = items[i].innerText || items[i].textContent;
            items[i].style.display = (txtValue.toUpperCase().indexOf(filter) > -1) ? "" : "none";
        }
    }

    function selectItem(optionElement) {
        const id = optionElement.getAttribute('data-id');
        const jobNumber = optionElement.getAttribute('data-job');
        const wrapper = optionElement.closest('.item-search-wrapper');

        const hiddenInput = wrapper.querySelector('.real-item-input');
        hiddenInput.value = id;

        const allOptions = wrapper.querySelectorAll('.item-option');
        allOptions.forEach(opt => opt.classList.remove('selected'));
        optionElement.classList.add('selected');

        const feedback = wrapper.querySelector('.selected-text-display');
        feedback.textContent = "Terpilih: " + jobNumber;
        feedback.classList.remove('text-gray-500');
        feedback.classList.add('text-green-600', 'font-bold');

        updateCycleTime(hiddenInput);
    }

    function updateCycleTime(inputElement) {
        const selectedItemId = inputElement.value;
        const parentRow = inputElement.closest('.detail-row');
        const planCtInput = parentRow.querySelector('.plan-ct-input');
        
        const selectedItem = itemsData.find(item => item.id == selectedItemId);
        
        if (selectedItem) {
            planCtInput.value = selectedItem.cycle_time;

            const machineCheckboxes = parentRow.querySelectorAll('.machine-container input[type="checkbox"]');
            machineCheckboxes.forEach(chk => {
                chk.checked = true;
            });
        } else {
            planCtInput.value = '';
        }
    }

    function tambahDetailJob() {
        const container = document.getElementById('tambahdetailjob');
        const templateRow = container.querySelector('.detail-row');

        const newRow = templateRow.cloneNode(true);
        detailJobCounter++;

        newRow.querySelector('.detail-row-title').textContent = `Detail Job ${detailJobCounter}`;

        // Reset inputs
        newRow.querySelectorAll('input[type="number"]').forEach(input => {
            if(input.name.includes('plan_qty') || input.name.includes('plan_ct_display')) {
                input.value = '';
            } else {
                input.value = '0';
            }
        });

        const searchInput = newRow.querySelector('.search-item-input');
        searchInput.value = '';
        
        const realInput = newRow.querySelector('.real-item-input');
        realInput.value = '';

        const textDisplay = newRow.querySelector('.selected-text-display');
        textDisplay.textContent = 'Belum ada item dipilih';
        textDisplay.className = 'mt-2 text-xs text-gray-500 selected-text-display';

        const itemOptions = newRow.querySelectorAll('.item-option');
        itemOptions.forEach(opt => {
            opt.classList.remove('selected');
            opt.style.display = '';
        });

        // Update checkbox names
        newRow.querySelectorAll('input[type="checkbox"]').forEach(chk => {
            chk.name = `machine_used_${detailJobCounter}[]`;
            chk.checked = false;
        });

        container.appendChild(newRow);
    }

    function deleterow(button) {
        const container = document.getElementById('tambahdetailjob');
        if (container.querySelectorAll('.detail-row').length > 1) {
            button.closest('.detail-row').remove();
            
            // Renumber rows
            const rows = container.querySelectorAll('.detail-row');
            rows.forEach((row, index) => {
                row.querySelector('.detail-row-title').textContent = `Detail Job ${index + 1}`;
                row.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                    chk.name = `machine_used_${index}[]`;
                });
            });
            detailJobCounter = rows.length;
        } else {
            alert('Tidak bisa menghapus baris terakhir.');
        }
    }
</script>
@endsection
