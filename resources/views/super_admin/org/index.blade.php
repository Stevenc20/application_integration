@extends('layouts.super_admin')

@section('title', 'Organization Structure')

@section('content')
<div class="p-3 sm:p-4 md:p-6 space-y-6">

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-start gap-3 shadow-sm">
        <ul class="text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3 shadow-sm">
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3 shadow-sm">
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Departments --}}
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800">Departments</h2>
            <button onclick="openDeptModal()" class="inline-flex items-center gap-1 bg-yellow-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-yellow-600 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add
            </button>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($departments as $dept)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="font-medium text-gray-800">{{ $dept->department_name }}</span>
                    <span class="text-xs text-gray-400 ml-2">({{ $dept->sections->count() }} sections)</span>
                </div>
                <div class="flex gap-2">
                    <button onclick="editDept({{ $dept->id }}, '{{ $dept->department_name }}')" class="text-xs px-2.5 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">Edit</button>
                    <button onclick="deleteDept({{ $dept->id }}, '{{ $dept->department_name }}')" class="text-xs px-2.5 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">Delete</button>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-gray-400 text-sm">No departments yet</div>
            @endforelse
        </div>
    </div>

    {{-- Sections --}}
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800">Sections</h2>
            <button onclick="openSectionModal()" class="inline-flex items-center gap-1 bg-yellow-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-yellow-600 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add
            </button>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($departments as $dept)
                @foreach ($dept->sections as $sec)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <span class="font-medium text-gray-800">{{ $sec->section_name }}</span>
                        <span class="text-xs text-gray-400 ml-2">({{ $dept->department_name }})</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editSection({{ $sec->id }}, {{ $sec->department_id }}, '{{ $sec->section_name }}')" class="text-xs px-2.5 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">Edit</button>
                        <button onclick="deleteSection({{ $sec->id }}, '{{ $sec->section_name }}')" class="text-xs px-2.5 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">Delete</button>
                    </div>
                </div>
                @endforeach
            @empty
            <div class="px-5 py-6 text-center text-gray-400 text-sm">No sections yet</div>
            @endforelse
        </div>
    </div>

    {{-- Positions --}}
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800">Positions</h2>
            <button onclick="openPositionModal()" class="inline-flex items-center gap-1 bg-yellow-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-yellow-600 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add
            </button>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($positions as $pos)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="font-medium text-gray-800">{{ $pos->position_name }}</span>
                    <span class="text-xs text-gray-400 ml-2">(Level {{ $pos->level }})</span>
                </div>
                <div class="flex gap-2">
                    <button onclick="editPosition({{ $pos->id }}, '{{ $pos->position_name }}', {{ $pos->level }})" class="text-xs px-2.5 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">Edit</button>
                    <button onclick="deletePosition({{ $pos->id }}, '{{ $pos->position_name }}')" class="text-xs px-2.5 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">Delete</button>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-gray-400 text-sm">No positions yet</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Add/Edit Department Modal --}}
<div id="deptModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeptModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800" id="deptModalTitle">Add Department</h2>
            <button onclick="closeDeptModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="deptForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Department Name</label>
                <input type="text" name="department_name" id="dept_name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeDeptModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition shadow-sm">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Add/Edit Section Modal --}}
<div id="sectionModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSectionModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800" id="sectionModalTitle">Add Section</h2>
            <button onclick="closeSectionModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="sectionForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Department</label>
                <select name="department_id" id="section_dept_id" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                    <option value="">Select department</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Section Name</label>
                <input type="text" name="section_name" id="section_name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeSectionModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition shadow-sm">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Add/Edit Position Modal --}}
<div id="positionModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePositionModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800" id="positionModalTitle">Add Position</h2>
            <button onclick="closePositionModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="positionForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Position Name</label>
                <input type="text" name="position_name" id="position_name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Level</label>
                <select name="level" id="position_level" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                    <option value="">Select level</option>
                    <option value="1">1 - Manager / Kepala Divisi</option>
                    <option value="2">2 - SPV</option>
                    <option value="3">3 - Foreman</option>
                    <option value="4">4 - Leader</option>
                    <option value="5">5 - Member</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closePositionModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition shadow-sm">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6 text-center">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Delete?</h2>
        <p class="text-sm text-gray-500 mb-2" id="deleteItemName"></p>
        <p class="text-sm text-red-500 mb-6">This action cannot be undone.</p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-center">
                <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-sm">Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Department
window.openDeptModal = function () {
    document.getElementById('deptModalTitle').textContent = 'Add Department';
    document.getElementById('deptForm').action = '{{ route("super-admin.departments.store") }}';
    document.getElementById('dept_name').value = '';
    document.getElementById('deptForm').querySelector('input[name="_method"]')?.remove();
    document.getElementById('deptModal').classList.remove('hidden');
    document.getElementById('deptModal').classList.add('flex');
};
window.closeDeptModal = function () {
    document.getElementById('deptModal').classList.add('hidden');
    document.getElementById('deptModal').classList.remove('flex');
};
window.editDept = function (id, name) {
    document.getElementById('deptModalTitle').textContent = 'Edit Department';
    document.getElementById('dept_name').value = name;
    var form = document.getElementById('deptForm');
    form.action = '{{ url("super-admin/departments") }}/' + id;
    var methodInput = form.querySelector('input[name="_method"]');
    if (!methodInput) { methodInput = document.createElement('input'); methodInput.type = 'hidden'; methodInput.name = '_method'; form.appendChild(methodInput); }
    methodInput.value = 'PUT';
    document.getElementById('deptModal').classList.remove('hidden');
    document.getElementById('deptModal').classList.add('flex');
};
window.deleteDept = function (id, name) {
    document.getElementById('deleteItemName').textContent = 'Delete department "' + name + '"?';
    var form = document.getElementById('deleteForm');
    form.action = '{{ url("super-admin/departments") }}/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
};

// Section
window.openSectionModal = function () {
    document.getElementById('sectionModalTitle').textContent = 'Add Section';
    document.getElementById('sectionForm').action = '{{ route("super-admin.sections.store") }}';
    document.getElementById('section_dept_id').value = '';
    document.getElementById('section_name').value = '';
    document.getElementById('sectionForm').querySelector('input[name="_method"]')?.remove();
    document.getElementById('sectionModal').classList.remove('hidden');
    document.getElementById('sectionModal').classList.add('flex');
};
window.closeSectionModal = function () {
    document.getElementById('sectionModal').classList.add('hidden');
    document.getElementById('sectionModal').classList.remove('flex');
};
window.editSection = function (id, deptId, name) {
    document.getElementById('sectionModalTitle').textContent = 'Edit Section';
    document.getElementById('section_name').value = name;
    document.getElementById('section_dept_id').value = deptId;
    var form = document.getElementById('sectionForm');
    form.action = '{{ url("super-admin/sections") }}/' + id;
    var methodInput = form.querySelector('input[name="_method"]');
    if (!methodInput) { methodInput = document.createElement('input'); methodInput.type = 'hidden'; methodInput.name = '_method'; form.appendChild(methodInput); }
    methodInput.value = 'PUT';
    document.getElementById('sectionModal').classList.remove('hidden');
    document.getElementById('sectionModal').classList.add('flex');
};
window.deleteSection = function (id, name) {
    document.getElementById('deleteItemName').textContent = 'Delete section "' + name + '"?';
    var form = document.getElementById('deleteForm');
    form.action = '{{ url("super-admin/sections") }}/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
};

// Position
window.openPositionModal = function () {
    document.getElementById('positionModalTitle').textContent = 'Add Position';
    document.getElementById('positionForm').action = '{{ route("super-admin.positions.store") }}';
    document.getElementById('position_name').value = '';
    document.getElementById('position_level').value = '';
    document.getElementById('positionForm').querySelector('input[name="_method"]')?.remove();
    document.getElementById('positionModal').classList.remove('hidden');
    document.getElementById('positionModal').classList.add('flex');
};
window.closePositionModal = function () {
    document.getElementById('positionModal').classList.add('hidden');
    document.getElementById('positionModal').classList.remove('flex');
};
window.editPosition = function (id, name, level) {
    document.getElementById('positionModalTitle').textContent = 'Edit Position';
    document.getElementById('position_name').value = name;
    document.getElementById('position_level').value = level;
    var form = document.getElementById('positionForm');
    form.action = '{{ url("super-admin/positions") }}/' + id;
    var methodInput = form.querySelector('input[name="_method"]');
    if (!methodInput) { methodInput = document.createElement('input'); methodInput.type = 'hidden'; methodInput.name = '_method'; form.appendChild(methodInput); }
    methodInput.value = 'PUT';
    document.getElementById('positionModal').classList.remove('hidden');
    document.getElementById('positionModal').classList.add('flex');
};
window.deletePosition = function (id, name) {
    document.getElementById('deleteItemName').textContent = 'Delete position "' + name + '"?';
    var form = document.getElementById('deleteForm');
    form.action = '{{ url("super-admin/positions") }}/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
};

// Delete modal
window.closeDeleteModal = function () {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
};
</script>
@endpush
