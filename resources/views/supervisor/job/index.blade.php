@extends('layouts.supervisor')

@section('title', 'Data Job')
@section('header_title', 'Data Job')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 md:p-6 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h4 class="text-xl font-bold text-gray-800">Data Job</h4>

        <div class="flex flex-col md:flex-row items-center gap-4">
            <form method="GET" action="{{ route('supervisor.job.index') ?? '#' }}" class="flex items-center w-full md:w-auto">
                <label for="tanggal" class="mr-2 text-sm font-medium text-gray-700 whitespace-nowrap">Tanggal:</label>
                <input type="date" id="tanggal" name="tanggal" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ request('tanggal', date('Y-m-d')) }}">
                <button type="submit" class="ml-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Filter</button>
            </form>
            
            <div class="flex items-center gap-2 w-full md:w-auto">
                <a href="{{ route('supervisor.input_harian') ?? '#' }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md shadow-sm transition-colors flex items-center w-full md:w-auto justify-center">
                    <i class="bx bx-arrow-back mr-2"></i> Kembali
                </a>
                <a href="{{ route('supervisor.job.create') ?? '#' }}" class="px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors flex items-center w-full md:w-auto justify-center">
                    <i class="bx bx-plus mr-1"></i> Create Job
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-red-50 border-b border-red-100 text-red-900">
                    <th class="py-3 px-4 text-center font-semibold text-sm sortable cursor-pointer select-none">ID Job</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm sortable cursor-pointer select-none">Production Line</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm sortable cursor-pointer select-none">Karyawan</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm sortable cursor-pointer select-none">Tanggal Produksi</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Detail</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Action</th>
                </tr>
            </thead>
            <tbody id="job-table-body" class="divide-y divide-gray-200">
                @forelse($filterjob ?? [] as $item)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4 text-center text-sm font-medium text-gray-900 id-job">JOB{{ $item->id_job ?? '123' }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $item->id_productionline->namaline ?? 'Line A' }} - Shift {{ $item->id_productionline->shift ?? '1' }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $item->id_karyawan->nama_karyawan ?? 'John Doe' }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ \Carbon\Carbon::parse($item->date ?? now())->format('d M, Y') }}</td>
                    <td class="py-3 px-4">
                        <div class="flex flex-col gap-2">
                            <a href="#" class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded transition-colors text-center shadow-sm flex items-center justify-center">
                                <i class="bx bx-edit-alt mr-1"></i> Prod. Plan
                            </a>
                            <a href="#" class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded transition-colors text-center shadow-sm flex items-center justify-center">
                                <i class="bx bx-list-ul mr-1"></i> Detail Job
                            </a>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex flex-col gap-2">
                            <a href="#" class="px-3 py-1.5 bg-primary-red hover:bg-red-800 text-white text-xs font-medium rounded transition-colors text-center shadow-sm flex items-center justify-center">
                                <i class="bx bx-edit mr-1"></i> Update
                            </a>
                            <button class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 border border-red-200 text-xs font-medium rounded transition-colors text-center flex items-center justify-center">
                                <i class="bx bx-trash mr-1"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="bx bx-folder-open text-4xl text-gray-300 mb-2"></i>
                            <p>Tidak ada data job untuk tanggal yang dipilih.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-gray-50">
        <div id="pagination-info" class="text-sm text-gray-500">Menampilkan data...</div>
        <div class="flex gap-2">
            <button id="prevBtn" class="px-3 py-1 bg-white border border-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-colors">Previous</button>
            <button id="nextBtn" class="px-3 py-1 bg-white border border-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-colors">Next</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const rows = Array.from(document.querySelectorAll("#job-table-body tr"));
    
    // --- Sorting ---
    document.querySelectorAll("th.sortable").forEach((header, index) => {
        let asc = true;
        const indicator = document.createElement("span");
        indicator.className = "ml-1 inline-block w-3";
        header.appendChild(indicator);

        header.addEventListener("click", () => {
            const sorted = [...rows].sort((a, b) => {
                if (a.cells.length <= 1) return 0; // Skip empty message
                const aText = a.cells[index].innerText.trim().toLowerCase();
                const bText = b.cells[index].innerText.trim().toLowerCase();
                return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            asc = !asc;

            document.querySelectorAll("th.sortable span").forEach(el => el.innerHTML = "");
            indicator.innerHTML = asc ? "&#9650;" : "&#9660;";

            const tbody = document.getElementById("job-table-body");
            if(rows.length > 0 && rows[0].cells.length > 1) {
                sorted.forEach(row => tbody.appendChild(row));
            }
        });
    });

    // --- Pagination ---
    if(rows.length > 0 && rows[0].cells.length > 1) {
        const perPage = 10;
        let currentPage = 1;
        const totalPages = Math.ceil(rows.length / perPage);
        const prevBtn = document.getElementById("prevBtn");
        const nextBtn = document.getElementById("nextBtn");
        const info = document.getElementById("pagination-info");

        function showPage(page) {
            const start = (page - 1) * perPage;
            const end = start + perPage;

            rows.forEach((row, index) => {
                row.style.display = index >= start && index < end ? "" : "none";
            });

            info.textContent = `Menampilkan ${start + 1}-${Math.min(end, rows.length)} dari ${rows.length} data`;
            prevBtn.disabled = page === 1;
            nextBtn.disabled = page === totalPages;
        }

        prevBtn.addEventListener("click", function () {
            if (currentPage > 1) {
                currentPage--;
                showPage(currentPage);
            }
        });

        nextBtn.addEventListener("click", function () {
            if (currentPage < totalPages) {
                currentPage++;
                showPage(currentPage);
            }
        });

        showPage(currentPage);
    } else {
        document.getElementById("prevBtn").disabled = true;
        document.getElementById("nextBtn").disabled = true;
        document.getElementById("pagination-info").textContent = "0 data";
    }
});
</script>
@endsection
