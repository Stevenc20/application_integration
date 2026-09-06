@extends('layouts.super_admin')

@section('title', 'Manage Users')

@section('content')
<div class="p-3 sm:p-4 md:p-6">

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

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">User Management</h1>
            <p class="text-gray-500 text-xs sm:text-sm">Manage all system users including roles and permissions</p>
        </div>
        <button onclick="openSuperAdminAddModal()"
            class="inline-flex items-center gap-2 bg-yellow-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-yellow-600 transition shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add User
        </button>
    </div>

    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs md:text-sm">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr class="text-left">
                        <th class="px-5 py-3 font-semibold text-gray-600">Name / NRP</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">System Role</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Jabatan</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Section</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Status</th>
                        <th class="px-5 py-3 font-semibold text-gray-600 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $index => $user)
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800 whitespace-nowrap">{{ $user->name }}</div>
                            <div class="text-gray-500 font-mono text-xs">{{ $user->nrp ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3">
                            @if($user->system_role === 'superadmin')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800">Superadmin</span>
                            @elseif($user->system_role === 'admin')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Admin</span>
                            @elseif($user->system_role === 'user')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">User</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">Legacy</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-700">
                            {{ $user->position ? $user->position->position_name : ($user->system_role === 'user' ? '-' : 'N/A') }}
                        </td>
                        <td class="px-5 py-3 text-gray-700">
                            {{ $user->section ? $user->section->section_name : ($user->system_role === 'user' ? '-' : 'N/A') }}
                        </td>
                        <td class="px-5 py-3">
                            @if(!$user->system_role)
                                <span class="text-red-500 font-semibold text-xs">Pending Migration</span>
                            @elseif($user->system_role === 'user' && (!$user->position_id || !$user->section_id))
                                <span class="text-orange-500 font-semibold text-xs">Pending Assignment</span>
                            @else
                                <span class="text-green-500 font-semibold text-xs">Active</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex gap-2 justify-center">
                                <button type="button"
                                    data-user='@json($user)'
                                    onclick="openSuperAdminEditModal(this); return false;"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">Edit</button>
                                <button type="button"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    onclick="openSuperAdminDeleteModal(this)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">Delete</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-400">No users found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ADD MODAL --}}
<div id="userModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSuperAdminAddModal()"></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-bold text-gray-800">Add New User</h2>
            <button onclick="closeSuperAdminAddModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('access-management.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">NRP</label>
                <input type="text" name="nrp" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-700 mb-2 block">System Access</label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="system_role" value="user" class="peer hidden" checked onchange="toggleAddOrgFields()">
                        <div class="text-center py-2 border border-gray-200 rounded-xl peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 text-sm font-medium transition">User</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="system_role" value="admin" class="peer hidden" onchange="toggleAddOrgFields()">
                        <div class="text-center py-2 border border-gray-200 rounded-xl peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700 text-sm font-medium transition">Admin</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="system_role" value="superadmin" class="peer hidden" onchange="toggleAddOrgFields()">
                        <div class="text-center py-2 border border-gray-200 rounded-xl peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-700 text-sm font-medium transition">Superadmin</div>
                    </label>
                </div>
            </div>

            <div id="addOrgFields" class="flex gap-3">
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700">Jabatan</label>
                    <select name="position_id" id="add_position" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                        <option value="">- Pilih Jabatan -</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->position_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700">Section</label>
                    <select name="section_id" id="add_section" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                        <option value="">- Pilih Section -</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-xs">
                <p class="font-semibold text-gray-600 mb-1">USER PROFILE SUMMARY</p>
                <div id="addSummaryText" class="text-gray-800">Pending Assignment</div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeSuperAdminAddModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition shadow-sm">Save User</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSuperAdminEditModal()"></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-bold text-gray-800">Edit User</h2>
            <button onclick="closeSuperAdminEditModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="edit_name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">NRP</label>
                <input type="text" name="nrp" id="edit_nrp" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Password <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="password" name="password" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition" placeholder="Leave blank to keep current">
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-700 mb-2 block">System Access</label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="system_role" value="user" id="edit_sys_user" class="peer hidden" onchange="toggleEditOrgFields()">
                        <div class="text-center py-2 border border-gray-200 rounded-xl peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 text-sm font-medium transition">User</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="system_role" value="admin" id="edit_sys_admin" class="peer hidden" onchange="toggleEditOrgFields()">
                        <div class="text-center py-2 border border-gray-200 rounded-xl peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700 text-sm font-medium transition">Admin</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="system_role" value="superadmin" id="edit_sys_super" class="peer hidden" onchange="toggleEditOrgFields()">
                        <div class="text-center py-2 border border-gray-200 rounded-xl peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-700 text-sm font-medium transition">Superadmin</div>
                    </label>
                </div>
            </div>

            <div id="editOrgFields" class="flex gap-3">
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700">Jabatan</label>
                    <select name="position_id" id="edit_position" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                        <option value="">- Pilih Jabatan -</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->position_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700">Section</label>
                    <select name="section_id" id="edit_section" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                        <option value="">- Pilih Section -</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-xs">
                <p class="font-semibold text-gray-600 mb-1">USER PROFILE SUMMARY</p>
                <div id="editSummaryText" class="text-gray-800">Pending Assignment</div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeSuperAdminEditModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition shadow-sm">Update User</button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE MODAL --}}
<div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSuperAdminDeleteModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6 text-center">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Delete User?</h2>
        <p class="text-sm text-gray-500 mb-2" id="deleteUserName"></p>
        <p class="text-sm text-red-500 mb-6">This action cannot be undone.</p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-center">
                <button type="button" onclick="closeSuperAdminDeleteModal()" class="flex-1 px-4 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-sm">Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    toggleAddOrgFields();

    document.getElementById('add_position').addEventListener('change', updateAddSummary);
    document.getElementById('add_section').addEventListener('change', updateAddSummary);
    
    document.getElementById('edit_position').addEventListener('change', updateEditSummary);
    document.getElementById('edit_section').addEventListener('change', updateEditSummary);
});

function toggleAddOrgFields() {
    let sysRole = document.querySelector('input[name="system_role"]:checked').value;
    let orgFields = document.getElementById('addOrgFields');
    let pos = document.getElementById('add_position');
    let sec = document.getElementById('add_section');
    
    if(sysRole === 'user') {
        orgFields.style.display = 'flex';
        pos.setAttribute('required', 'required');
        sec.setAttribute('required', 'required');
    } else {
        orgFields.style.display = 'none';
        pos.removeAttribute('required');
        sec.removeAttribute('required');
        pos.value = '';
        sec.value = '';
    }
    updateAddSummary();
}

function updateAddSummary() {
    let sysRole = document.querySelector('input[name="system_role"]:checked').value;
    let summaryEl = document.getElementById('addSummaryText');
    
    if (sysRole === 'superadmin') {
        summaryEl.textContent = 'Superadmin';
    } else if (sysRole === 'admin') {
        summaryEl.textContent = 'Admin';
    } else {
        let posText = document.getElementById('add_position').options[document.getElementById('add_position').selectedIndex].text;
        let secText = document.getElementById('add_section').options[document.getElementById('add_section').selectedIndex].text;
        
        let validPos = document.getElementById('add_position').value !== '';
        let validSec = document.getElementById('add_section').value !== '';
        
        if (validPos && validSec) {
            summaryEl.textContent = posText + ' ' + secText;
        } else {
            summaryEl.textContent = 'Pending Assignment (Jabatan and Section required)';
        }
    }
}

function toggleEditOrgFields() {
    let sysRole = document.querySelector('#editForm input[name="system_role"]:checked');
    if(!sysRole) return;
    sysRole = sysRole.value;
    
    let orgFields = document.getElementById('editOrgFields');
    let pos = document.getElementById('edit_position');
    let sec = document.getElementById('edit_section');
    
    if(sysRole === 'user') {
        orgFields.style.display = 'flex';
        pos.setAttribute('required', 'required');
        sec.setAttribute('required', 'required');
    } else {
        orgFields.style.display = 'none';
        pos.removeAttribute('required');
        sec.removeAttribute('required');
        pos.value = '';
        sec.value = '';
    }
    updateEditSummary();
}

function updateEditSummary() {
    let sysRole = document.querySelector('#editForm input[name="system_role"]:checked');
    if(!sysRole) return;
    sysRole = sysRole.value;
    
    let summaryEl = document.getElementById('editSummaryText');
    
    if (sysRole === 'superadmin') {
        summaryEl.textContent = 'Superadmin';
    } else if (sysRole === 'admin') {
        summaryEl.textContent = 'Admin';
    } else {
        let posText = document.getElementById('edit_position').options[document.getElementById('edit_position').selectedIndex].text;
        let secText = document.getElementById('edit_section').options[document.getElementById('edit_section').selectedIndex].text;
        
        let validPos = document.getElementById('edit_position').value !== '';
        let validSec = document.getElementById('edit_section').value !== '';
        
        if (validPos && validSec) {
            summaryEl.textContent = posText + ' ' + secText;
        } else {
            summaryEl.textContent = 'Pending Assignment (Jabatan and Section required)';
        }
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

    var form = document.getElementById('editForm');
    form.action = '/access-management/users/' + user.id;

    toggleEditOrgFields();

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
    document.getElementById('deleteUserName').textContent = 'Hapus user "' + name + '"?';
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