@extends('layouts.supervisor')

@section('title', 'Create Data Job')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-5 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
        <div>
            <h5 class="text-xl font-bold text-gray-800">Create Data Job & Production Plan</h5>
            <p class="text-sm text-gray-500 mt-1">Isi informasi job, rencana produksi, lalu detail item stamping.</p>
        </div>
    </div>

    <div class="p-5">
        <form action="{{ route('supervisor.job.store') }}" method="POST">
            @csrf

            <!-- ========== 1. INFO JOB ========== -->
            <div class="mb-8">
                <div class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">1. Informasi Utama Job</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- PRODUCTION LINE -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Production Line</label>
                        <select id="id_productionline_select" name="id_productionline" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            <option value="" disabled selected>-- Pilih Line & Shift --</option>
                            @foreach ($dataproductionline as $line)
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
                        <select id="id_karyawan_select" name="id_karyawan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            <option value="" disabled selected>-- Pilih Karyawan --</option>
                            @foreach ($datakaryawan as $item)
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
                        <input type="date" name="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 mb-8">

            <!-- ========== 2. PRODUCTION PLAN ========== -->
            <div class="mb-8">
                <div class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">2. Production Plan (Per Shift)</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GSPH Plan</label>
                        <input type="number" name="gsph_plan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Contoh: 250">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stroke Plan</label>
                        <input type="number" name="stroke_plan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Contoh: 18000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Man Power</label>
                        <input type="number" name="mp" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Jumlah operator">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Repair Plan (%)</label>
                        <input type="number" step="0.01" name="repair_plan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="0" placeholder="Misal: 5.50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reject Plan (%)</label>
                        <input type="number" step="0.01" name="reject_plan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="0" placeholder="Misal: 2.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Idle Plan (menit)</label>
                        <input type="number" name="idle_plan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="0" placeholder="Total idle satu shift">
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 mb-8">

            <!-- ========== 3. DETAIL JOB PER ITEM ========== -->
            <div class="mb-4">
                <div class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">3. Detail Job per Item</div>
                <p class="text-sm text-gray-500">Tambahkan item stamping yang akan dikerjakan dalam job ini.</p>
            </div>

            <div id="tambahdetailjob" class="space-y-4">
                <!-- ==== TEMPLATE BARIS DETAIL JOB ==== -->
                <div class="detail-row bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100">
                        <h5 class="font-bold text-gray-800 detail-row-title">Detail Job 1</h5>
                        <div class="flex items-center gap-3">
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                Item per kombinasi Job & Mesin
                            </span>
                            <button type="button" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1 rounded transition-colors text-sm font-medium" onclick="deleterow(this)">
                                <i class="bx bx-trash mr-1"></i> Hapus
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- ITEM SEARCH -->
                        <div class="col-span-1 lg:col-span-4 item-search-wrapper">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Item Produksi (Job Number)</label>
                            <input type="text" class="w-full rounded-t-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm search-item-input" placeholder="Ketik Job Number untuk mencari..." onkeyup="filterItemList(this)">

                            <div class="item-list-container max-h-40 overflow-y-auto border border-t-0 border-gray-300 rounded-b-md bg-white">
                                @foreach ($dataitemproduksi as $item)
                                    <div class="item-option p-2 border-b border-gray-50 hover:bg-gray-100 cursor-pointer transition-colors" data-id="{{ $item->id }}" data-job="{{ $item->job_number }}" onclick="selectItem(this)">
                                        <div class="font-semibold text-gray-800 text-sm">{{ $item->job_number }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $item->part_number }}@if($item->customer) · {{ $item->customer }}@endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <input type="hidden" name="id_item_produksi[]" class="real-item-input" required>
                            <p class="mt-2 text-xs text-gray-500 selected-text-display">
                                Belum ada item dipilih
                            </p>
                        </div>

                        <!-- PLAN FIELDS -->
                        <div class="col-span-1 lg:col-span-5">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <div class="col-span-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan Qty</label>
                                    <input type="number" name="detail_plan_qty[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" placeholder="Plan" required>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan CT (detik)</label>
                                    <input type="number" step="0.01" class="w-full rounded-md border-gray-300 bg-gray-50 shadow-sm text-sm plan-ct-input" placeholder="Auto" readonly>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan DCT</label>
                                    <input type="number" name="detail_plan_dct[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="0">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan VCT</label>
                                    <input type="number" name="detail_plan_vct[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="0">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan Idle</label>
                                    <input type="number" name="detail_plan_idle[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="0">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan Breaktime</label>
                                    <input type="number" name="detail_plan_breaktime[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="0">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan 1st QCheck</label>
                                    <input type="number" name="detail_plan_first_qcheck[]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="0">
                                </div>
                            </div>
                        </div>

                        <!-- MESIN -->
                        <div class="col-span-1 lg:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Machine Used</label>
                            <div class="machine-container flex flex-wrap gap-2">
                                @foreach ($machines as $m)
                                    <label class="inline-flex items-center bg-gray-50 border border-gray-200 rounded px-2 py-1 cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input type="checkbox" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 mr-2" name="machine_used_0[]" value="{{ $m->id }}">
                                        <span class="text-xs text-gray-700">{{ $m->code }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ==== END TEMPLATE BARIS DETAIL JOB ==== -->
            </div>

            <div class="mt-4">
                <button type="button" class="bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium py-2 px-4 rounded-md shadow-sm transition-colors" onclick="tambahDetailJob()">
                    <i class="bx bx-plus mr-1"></i> Tambah Detail Job
                </button>
            </div>

            <div class="flex justify-end mt-8 pt-4 border-t border-gray-200 gap-3">
                <a href="{{ route('supervisor.job.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Batal</a>
                <button type="submit" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors">Simpan Job</button>
            </div>

        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Mock data conversion - ensures it works even if empty
const itemsData = {!! $items_json_str ?: '[]' !!};
const machinesData = {!! $machines_json_str ?: '[]' !!};
let detailJobCounter = 1;

$(document).ready(function () {
    // Production Line
    $('#id_productionline_select').select2({
        placeholder: "-- Pilih Line & Shift --",
        allowClear: true,
        width: '100%',
    });

    // Karyawan
    $('#id_karyawan_select').select2({
        placeholder: "-- Pilih Karyawan --",
        allowClear: true,
        width: '100%',
    });
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
    allOptions.forEach(opt => {
        opt.classList.remove('bg-blue-500', 'text-white');
        opt.classList.add('hover:bg-gray-100', 'text-gray-800');
        opt.querySelector('.text-gray-500')?.classList.remove('text-blue-100');
    });
    
    optionElement.classList.remove('hover:bg-gray-100', 'text-gray-800');
    optionElement.classList.add('bg-blue-500', 'text-white');
    optionElement.querySelector('.text-xs')?.classList.add('text-blue-100');

    const feedback = wrapper.querySelector('.selected-text-display');
    feedback.textContent = "Terpilih: " + jobNumber;
    feedback.classList.remove('text-gray-500');
    feedback.classList.add('text-green-600', 'font-semibold');

    updateCycleTime(hiddenInput);
}

function updateCycleTime(inputElement) {
    const selectedItemId = inputElement.value;
    const parentRow = inputElement.closest('.detail-row');
    const planCtInput = parentRow.querySelector('.plan-ct-input');
    
    const selectedItem = itemsData.find(item => item.id == selectedItemId);
    
    if (selectedItem) {
        planCtInput.value = selectedItem.cycle_time || 0;

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

    const $newRow = $(templateRow).clone();

    $newRow.find('.detail-row-title').text(`Detail Job ${detailJobCounter + 1}`);

    // reset values
    $newRow.find('input[type="number"]').val('0');
    $newRow.find('.plan-ct-input').val('');
    $newRow.find('input[name="detail_plan_qty[]"]').val('');

    // reset search & selection
    $newRow.find('.search-item-input').val('');
    $newRow.find('.real-item-input').val('');
    $newRow.find('.selected-text-display')
           .text('Belum ada item dipilih')
           .removeClass('text-green-600 font-semibold')
           .addClass('text-gray-500');

    $newRow.find('.item-option')
           .removeClass('bg-blue-500 text-white')
           .addClass('hover:bg-gray-100 text-gray-800')
           .show();
           
    $newRow.find('.item-option .text-xs').removeClass('text-blue-100');

    // reset checkbox
    $newRow.find('input[type="checkbox"]').each(function() {
        this.name = `machine_used_${detailJobCounter}[]`;
        this.checked = false;
    });

    $(container).append($newRow);
    detailJobCounter++;
}

function deleterow(button) {
    const container = document.getElementById('tambahdetailjob');
    if (container.querySelectorAll('.detail-row').length > 1) {
        button.closest('.detail-row').remove();
    } else {
        alert('Tidak bisa menghapus baris terakhir.');
    }
}
</script>
@endsection
