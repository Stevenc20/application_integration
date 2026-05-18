@extends('layouts.supervisor')

@section('title', 'Data Job')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
    <h4 class="mb-4 text-xl font-bold text-gray-800 border-b pb-2">Data Job</h4>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
        
        <form method="GET" action="{{ route('supervisor.job.index') }}" class="flex items-center w-full md:w-auto">
            <label for="tanggal" class="mr-2 font-medium text-gray-600">Tanggal:</label>
            <input type="date" id="tanggal" name="tanggal" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $selected_date_str }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm">Filter</button>
        </form>
        
        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <a href="{{ url('supervisor/dashboard') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-md transition-colors text-center border border-gray-300">
                <i class="bx bx-arrow-back mr-1"></i> Dashboard
            </a>
            <a href="{{ route('supervisor.job.create') }}" class="bg-primary-red hover-bg-primary-red text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-center">
                <i class="bx bx-plus mr-1"></i> Create Job
            </a>
        </div>
    </div>
    
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable hover:bg-gray-100">ID Job</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable hover:bg-gray-100">Production Line</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable hover:bg-gray-100">Karyawan</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable hover:bg-gray-100">Tanggal Produksi</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="job-table-body">
                @forelse ($filterjob as $item)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center font-medium text-gray-900 id-job">JOB{{ $item->id_job ?? $item->id }}</td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $item->productionLine->namaline ?? 'N/A' }} - Shift {{ $item->productionLine->shift ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $item->karyawan->nama_karyawan ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ \Carbon\Carbon::parse($item->date)->format('d M, Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex flex-col gap-1.5">
                            <a href="#" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium py-1.5 px-3 rounded shadow-sm w-full">
                                <i class="bx bx-edit-alt"></i> Production Plan
                            </a>
                            <a href="#" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium py-1.5 px-3 rounded shadow-sm w-full">
                                <i class="bx bx-list-ul"></i> Detail Job
                            </a>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex flex-col gap-1.5">
                            <a href="{{ route('supervisor.job.update', $item->id_job ?? $item->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium py-1.5 px-3 rounded shadow-sm w-full">
                                <i class="bx bx-edit-alt"></i> Update
                            </a>
                            <form action="{{ route('supervisor.job.delete', $item->id_job ?? $item->id) }}" method="POST" class="w-full m-0 p-0" onsubmit="return confirm('Apakah anda yakin ingin menghapus job ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1.5 px-3 rounded shadow-sm w-full">
                                    <i class="bx bx-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 bg-gray-50/50">Tidak ada data job untuk tanggal yang dipilih.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="flex justify-between items-center px-4 py-3 bg-gray-50 border-t border-gray-200">
            <div id="pagination-info" class="text-sm text-gray-600 font-medium"></div>
            <div class="flex gap-2">
                <button id="prevBtn" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-1 px-3 rounded text-sm shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <button id="nextBtn" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-1 px-3 rounded text-sm shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const rows = Array.from(document.querySelectorAll("#job-table-body tr")).filter(row => !row.querySelector('td[colspan]'));
    
    // --- Sorting ---
    document.querySelectorAll("th.sortable").forEach((header, index) => {
        let asc = true;
        const indicator = document.createElement("span");
        indicator.className = "sort-indicator ml-1 text-gray-400";
        header.appendChild(indicator);

        header.addEventListener("click", () => {
            if (rows.length === 0) return;
            const sorted = [...rows].sort((a, b) => {
                const aText = a.cells[index].innerText.trim().toLowerCase();
                const bText = b.cells[index].innerText.trim().toLowerCase();
                return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            asc = !asc;

            document.querySelectorAll(".sort-indicator").forEach(el => el.textContent = "");
            indicator.textContent = asc ? "▲" : "▼";
            indicator.classList.add('text-primary-red');

            const tbody = document.getElementById("job-table-body");
            sorted.forEach(row => tbody.appendChild(row));
        });
    });

    // --- Pagination ---
    const perPage = 10;
    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / perPage);
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const info = document.getElementById("pagination-info");

    function showPage(page) {
        if (rows.length === 0) {
            if (info) info.textContent = "0 data";
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;
            return;
        }
        
        const start = (page - 1) * perPage;
        const end = start + perPage;

        rows.forEach((row, index) => {
            row.style.display = index >= start && index < end ? "" : "none";
        });

        info.textContent = `Menampilkan ${start + 1}-${Math.min(end, rows.length)} dari ${rows.length} data`;
        prevBtn.disabled = page === 1;
        nextBtn.disabled = page === totalPages || totalPages === 0;
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", function () {
            if (currentPage > 1) {
                currentPage--;
                showPage(currentPage);
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", function () {
            if (currentPage < totalPages) {
                currentPage++;
                showPage(currentPage);
            }
        });
    }

    showPage(currentPage);
});
</script>
@endsection
