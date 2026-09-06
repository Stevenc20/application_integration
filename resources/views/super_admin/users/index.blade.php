@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">User Management</h1>
            <p class="mt-1 text-sm text-slate-500">Manage all employee accounts and system access.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <button onclick="openSuperAdminAddModal()" class="btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 fill-current opacity-80" viewBox="0 0 16 16"><path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z"/></svg>
                    <span>Add New Employee</span>
                </span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 px-4 py-3 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-r-lg">
            {{ session('success') }}
        </div>
    @endif
    @if (->any())
        <div class="mb-6 px-4 py-3 bg-rose-50 border-l-4 border-rose-500 text-rose-700 rounded-r-lg">
            <ul class="list-disc pl-5">
                @foreach (->all() as )
                    <li>{{ \ }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-sm font-semibold uppercase tracking-wider">
                        <th class="px-6 py-4">Name / NRP</th>
                        <th class="px-6 py-4">Account Type</th>
                        <th class="px-6 py-4">Organization</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @foreach( as \)
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-800">{{ \->name }}</div>
                            <div class="text-slate-500 text-xs mt-1">{{ \->nrp ?? 'No NRP' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if(\->system_role === 'superadmin')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">Superadmin</span>
                            @elseif(\->system_role === 'admin')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Admin</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">User</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if(\->position || \->section)
                                <div class="font-medium text-slate-700">{{ \->position ? \->position->position_name : '-' }}</div>
                                <div class="text-slate-500 text-xs mt-0.5">{{ \->section ? \->section->section_name : '-' }}</div>
                            @else
                                <span class="text-slate-400 italic">Global Access</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-3 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                <button type="button" 
                                    data-user="{{ json_encode(\) }}"
                                    onclick="openSuperAdminEditModal(this)"
                                    class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-2 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button type="button" 
                                    data-user-id="{{ \->id }}" 
                                    data-user-name="{{ \->name }}"
                                    onclick="openSuperAdminDeleteModal(this)"
                                    class="text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 p-2 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            {{ \->links() }}
        </div>
    </div>

</div>

{{-- ADD MODAL --}}
<div id="userModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeSuperAdminAddModal()"></div>
    <div class="relative bg-white w-full max-w-2xl mx-4 rounded-xl shadow-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-slate-800">Add New Employee</h2>
            <button onclick="closeSuperAdminAddModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('access-management.users.store') }}" method="POST" class="space-y-8">
            @csrf
            
            <!-- SECTION 1 -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Section 1: Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                        <input type="text" name="name" required class="w-full form-input px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">NRP *</label>
                        <input type="text" name="nrp" required class="w-full form-input px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password *</label>
                        <input type="password" name="password" required class="w-full form-input px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- SECTION 2 -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Section 2: Account Type</h3>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer relative">
                        <input type="radio" name="system_role" value="user" class="peer sr-only" checked onchange="toggleAddOrgFields()">
                        <div class="text-center px-3 py-3 border border-slate-200 rounded-lg peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:ring-1 peer-checked:ring-blue-500 hover:bg-slate-50 transition-all">
                            <div class="text-sm font-medium text-slate-800 peer-checked:text-blue-700">User</div>
                        </div>
                    </label>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="system_role" value="admin" class="peer sr-only" onchange="toggleAddOrgFields()">
                        <div class="text-center px-3 py-3 border border-slate-200 rounded-lg peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:ring-1 peer-checked:ring-blue-500 hover:bg-slate-50 transition-all">
                            <div class="text-sm font-medium text-slate-800 peer-checked:text-blue-700">Admin</div>
                        </div>
                    </label>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="system_role" value="superadmin" class="peer sr-only" onchange="toggleAddOrgFields()">
                        <div class="text-center px-3 py-3 border border-slate-200 rounded-lg peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:ring-1 peer-checked:ring-purple-500 hover:bg-slate-50 transition-all">
                            <div class="text-sm font-medium text-slate-800 peer-checked:text-purple-700">Superadmin</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- SECTION 3 -->
            <div id="addOrgSection">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Section 3: Organization Assignment</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jabatan <span class="text-rose-500">*</span></label>
                        <select name="position_id" id="add_position" class="w-full form-select px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">[ Select Position ]</option>
                            @foreach(\ as \)
                                <option value="{{ \->id }}">{{ \->position_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Section <span class="text-rose-500">*</span></label>
                        <select name="section_id" id="add_section" class="w-full form-select px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">[ Select Section ]</option>
                            @foreach(\ as \)
                                <option value="{{ \->id }}">{{ \->section_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeSuperAdminAddModal()" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 rounded-lg shadow-sm transition-colors">Save Employee</button>
            </div>
        </form>
    </div>
</div>


{{-- EDIT MODAL --}}
<div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeSuperAdminEditModal()"></div>
    <div class="relative bg-white w-full max-w-2xl mx-4 rounded-xl shadow-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-slate-800">Edit Employee</h2>
            <button onclick="closeSuperAdminEditModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="editForm" method="POST" class="space-y-8">
            @csrf
            @method('PUT')
            
            <!-- SECTION 1 -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Section 1: Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                        <input type="text" name="name" id="edit_name" required class="w-full form-input px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">NRP *</label>
                        <input type="text" name="nrp" id="edit_nrp" required class="w-full form-input px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-slate-400 font-normal">(Leave blank to keep current)</span></label>
                        <input type="password" name="password" class="w-full form-input px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- SECTION 2 -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Section 2: Account Type</h3>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer relative">
                        <input type="radio" name="system_role" value="user" id="edit_sys_user" class="peer sr-only" onchange="toggleEditOrgFields()">
                        <div class="text-center px-3 py-3 border border-slate-200 rounded-lg peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:ring-1 peer-checked:ring-blue-500 hover:bg-slate-50 transition-all">
                            <div class="text-sm font-medium text-slate-800 peer-checked:text-blue-700">User</div>
                        </div>
                    </label>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="system_role" value="admin" id="edit_sys_admin" class="peer sr-only" onchange="toggleEditOrgFields()">
                        <div class="text-center px-3 py-3 border border-slate-200 rounded-lg peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:ring-1 peer-checked:ring-blue-500 hover:bg-slate-50 transition-all">
                            <div class="text-sm font-medium text-slate-800 peer-checked:text-blue-700">Admin</div>
                        </div>
                    </label>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="system_role" value="superadmin" id="edit_sys_super" class="peer sr-only" onchange="toggleEditOrgFields()">
                        <div class="text-center px-3 py-3 border border-slate-200 rounded-lg peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:ring-1 peer-checked:ring-purple-500 hover:bg-slate-50 transition-all">
                            <div class="text-sm font-medium text-slate-800 peer-checked:text-purple-700">Superadmin</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- SECTION 3 -->
            <div id="editOrgSection">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Section 3: Organization Assignment</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jabatan <span class="text-rose-500">*</span></label>
                        <select name="position_id" id="edit_position" class="w-full form-select px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">[ Select Position ]</option>
                            @foreach(\ as \)
                                <option value="{{ \->id }}">{{ \->position_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Section <span class="text-rose-500">*</span></label>
                        <select name="section_id" id="edit_section" class="w-full form-select px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">[ Select Section ]</option>
                            @foreach(\ as \)
                                <option value="{{ \->id }}">{{ \->section_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeSuperAdminEditModal()" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 rounded-lg shadow-sm transition-colors">Update Employee</button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE MODAL --}}
<div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeSuperAdminDeleteModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-xl shadow-2xl p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h2 class="text-lg font-bold text-slate-800 mb-2">Delete User?</h2>
        <p class="text-sm text-slate-500 mb-6" id="deleteUserName"></p>
        
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-center">
                <button type="button" onclick="closeSuperAdminDeleteModal()" class="flex-1 px-4 py-2.5 text-sm font-medium bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-sm font-medium bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition shadow-sm">Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    toggleAddOrgFields();
});

function toggleAddOrgFields() {
    var sysRole = document.querySelector('input[name="system_role"]:checked').value;
    var addOrg = document.getElementById('addOrgSection');
    if (sysRole === 'superadmin') {
        addOrg.style.display = 'none';
        document.getElementById('add_position').required = false;
        document.getElementById('add_section').required = false;
    } else {
        addOrg.style.display = 'block';
        document.getElementById('add_position').required = (sysRole === 'user');
        document.getElementById('add_section').required = (sysRole === 'user');
    }
}

function toggleEditOrgFields() {
    var sysRole = document.querySelector('#editModal input[name="system_role"]:checked').value;
    var editOrg = document.getElementById('editOrgSection');
    if (sysRole === 'superadmin') {
        editOrg.style.display = 'none';
        document.getElementById('edit_position').required = false;
        document.getElementById('edit_section').required = false;
    } else {
        editOrg.style.display = 'block';
        document.getElementById('edit_position').required = (sysRole === 'user');
        document.getElementById('edit_section').required = (sysRole === 'user');
    }
}

window.openSuperAdminAddModal = function () {
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');
    toggleAddOrgFields();
};
window.closeSuperAdminAddModal = function () {
    document.getElementById('userModal').classList.add('hidden');
    document.getElementById('userModal').classList.remove('flex');
};

window.openSuperAdminEditModal = function (btn) {
    var user = JSON.parse(btn.getAttribute('data-user'));

    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_nrp').value = user.nrp || '';
    
    var sysRole = user.system_role || 'user';
    if(sysRole === 'superadmin') {
        document.getElementById('edit_sys_super').checked = true;
    } else if(sysRole === 'admin') {
        document.getElementById('edit_sys_admin').checked = true;
    } else {
        document.getElementById('edit_sys_user').checked = true;
    }

    document.getElementById('edit_position').value = user.position_id || '';
    document.getElementById('edit_section').value = user.section_id || '';

    toggleEditOrgFields();

    var form = document.getElementById('editForm');
    form.action = '/access-management/users/' + user.id;

    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
};
window.closeSuperAdminEditModal = function () {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
};

window.openSuperAdminDeleteModal = function (btn) {
    var name = btn.getAttribute('data-user-name');
    var id = btn.getAttribute('data-user-id');
    document.getElementById('deleteUserName').textContent = 'Are you sure you want to delete "' + name + '"?';
    var form = document.getElementById('deleteForm');
    form.action = '/access-management/users/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
};
window.closeSuperAdminDeleteModal = function () {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
};
</script>
@endpush
