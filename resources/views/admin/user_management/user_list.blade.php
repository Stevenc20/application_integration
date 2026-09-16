@extends('layouts.supervisor')

@section('content')
<div class="p-3 sm:p-4 md:p-6 bg-gray-50 min-h-screen">

    {{-- FLASH MESSAGES --}}
    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-start gap-3 shadow-sm">
        <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        <ul class="text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3 shadow-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">User Management</h1>
            <p class="text-gray-500 text-xs sm:text-sm">{{ now()->format('d F Y') }}</p>
        </div>
        <button onclick="openModal()"
            class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add User
        </button>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-blue-50 border border-blue-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-blue-600">Total Users</p>
            <p class="text-lg font-bold text-blue-700">{{ $users->total() }}</p>
        </div>
        <div class="bg-red-50 border border-red-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-red-600">Admin</p>
            <p class="text-lg font-bold text-red-700">{{ $users->where('role','admin')->count() }}</p>
        </div>
        <div class="bg-purple-50 border border-purple-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-purple-600">Supervisor</p>
            <p class="text-lg font-bold text-purple-700">{{ $users->where('role','supervisor')->count() }}</p>
        </div>
        <div class="bg-green-50 border border-green-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-green-600">Operator</p>
            <p class="text-lg font-bold text-green-700">{{ $users->where('role','operator')->count() }}</p>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-sm md:text-base text-gray-700">Registered Users</h2>
            <span class="text-xs text-gray-400">{{ $users->total() }} total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs md:text-sm">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr class="text-left">
                        <th class="px-5 py-3 font-semibold text-gray-600">#</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Name</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">NRP</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Role</th>
                        <th class="px-5 py-3 font-semibold text-gray-600 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $index => $user)
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $users->firstItem() + $index }}</td>
                        <td class="px-5 py-3 font-medium text-gray-800 whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                {{ $user->name }}
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600 whitespace-nowrap font-mono">{{ $user->nrp ?? '-' }}</td>
                        <td class="px-5 py-3">
                            @php
                                $roleColors = [
                                    'admin'        => 'bg-red-100 text-red-700',
                                    'supervisor'   => 'bg-purple-100 text-purple-700',
                                    'manager'      => 'bg-rose-100 text-rose-700',
                                    'kadiv'        => 'bg-red-100 text-red-700',
                                    'direktur'     => 'bg-purple-100 text-purple-700',
                                    'presdir'      => 'bg-yellow-100 text-yellow-700',
                                    'ppc'          => 'bg-amber-100 text-amber-700',
                                    'foreman'      => 'bg-indigo-100 text-indigo-700',
                                    'operator'     => 'bg-green-100 text-green-700',
                                    'leader'       => 'bg-sky-100 text-sky-700',
                                    'shearing'     => 'bg-orange-100 text-orange-700',
                                    'handwork'     => 'bg-teal-100 text-teal-700',
                                    'quality'      => 'bg-blue-100 text-blue-700',
                                    'production'   => 'bg-gray-100 text-gray-700',
                                    'dies_shop'    => 'bg-stone-100 text-stone-700',
                                    'plant_service'=> 'bg-lime-100 text-lime-700',
                                    'irm'          => 'bg-pink-100 text-pink-700',
                                    'logistik'     => 'bg-gray-100 text-gray-700',
                                ];
                                $color = $roleColors[$user->role] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $color }}">
                                @php
                                    $roleLabel = $user->role;
                                    if ($roleLabel == 'ppc') $roleLabel = 'PPC';
                                    elseif ($roleLabel == 'presdir') $roleLabel = 'Presdir';
                                    elseif ($roleLabel == 'kadiv') $roleLabel = 'Kadiv';
                                    elseif ($roleLabel == 'irm') $roleLabel = 'IRM';
                                    else $roleLabel = ucwords(str_replace('_', ' ', $roleLabel));
                                @endphp
                                {{ $roleLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex gap-2 justify-center">
                                <button onclick="openEditModal('{{ $user->id }}','{{ addslashes($user->name) }}','{{ $user->nrp }}','{{ $user->role }}')"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </button>
                                <button onclick="openDeleteModal('{{ $user->id }}')"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                            No users found
                        </td>
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
@endsection

{{-- ═══════════════════════════════════════════ --}}
{{-- MODAL: ADD USER                            --}}
{{-- ═══════════════════════════════════════════ --}}
<div id="userModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300" id="modalBackdrop" onclick="closeModal()"></div>
    <div id="modalBox" class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-300">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Add New User</h2>
                <p class="text-xs text-gray-400 mt-0.5">Fill in the details below</p>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none transition" placeholder="Enter full name">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">NRP</label>
                <input type="text" name="nrp" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none transition" placeholder="Enter NRP">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none transition" placeholder="Min. 6 characters">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Role</label>
                <input type="hidden" name="role" id="add_role_val" required>
                <div class="grid grid-cols-2 gap-3 mt-1.5">
                    <div onclick="setAddRole('admin')" id="add_role_admin" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-slate-300 hover:bg-slate-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Admin</span>
                            <span class="block text-[9px] font-medium text-gray-400">System Administrator</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('operator')" id="add_role_operator" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-sky-300 hover:bg-sky-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Operator</span>
                            <span class="block text-[9px] font-medium text-gray-400">Produksi Line</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('ppc')" id="add_role_ppc" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">PPC</span>
                            <span class="block text-[9px] font-medium text-gray-400">Production Planning Control</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('foreman')" id="add_role_foreman" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-amber-300 hover:bg-amber-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Foreman</span>
                            <span class="block text-[9px] font-medium text-gray-400">Line Foreman</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('leader a')" id="add_role_leader_a" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader A</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line A</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('leader b')" id="add_role_leader_b" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader B</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line B</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('leader c')" id="add_role_leader_c" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader C</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line C</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('leader d')" id="add_role_leader_d" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader D</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line D</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('shearing')" id="add_role_shearing" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-orange-300 hover:bg-orange-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Shearing</span>
                            <span class="block text-[9px] font-medium text-gray-400">Shearing Section</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('handwork')" id="add_role_handwork" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-teal-300 hover:bg-teal-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Handwork</span>
                            <span class="block text-[9px] font-medium text-gray-400">Handwork Section</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('quality')" id="add_role_quality" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-cyan-300 hover:bg-cyan-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Quality</span>
                            <span class="block text-[9px] font-medium text-gray-400">Quality Control</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('production')" id="add_role_production" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-gray-300 hover:bg-gray-100 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Production</span>
                            <span class="block text-[9px] font-medium text-gray-400">Production Staff</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('supervisor')" id="add_role_supervisor" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-emerald-300 hover:bg-emerald-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Supervisor</span>
                            <span class="block text-[9px] font-medium text-gray-400">Area Supervisor</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('manager')" id="add_role_manager" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-rose-300 hover:bg-rose-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Manager</span>
                            <span class="block text-[9px] font-medium text-gray-400">Manager Produksi</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('kadiv')" id="add_role_kadiv" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-red-300 hover:bg-red-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Kadiv</span>
                            <span class="block text-[9px] font-medium text-gray-400">Kepala Divisi</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('direktur')" id="add_role_direktur" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-purple-300 hover:bg-purple-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Direktur</span>
                            <span class="block text-[9px] font-medium text-gray-400">Direktur Utama</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('presdir')" id="add_role_presdir" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-yellow-300 hover:bg-yellow-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Presdir</span>
                            <span class="block text-[9px] font-medium text-gray-400">President Director</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('dies_shop')" id="add_role_dies_shop" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-stone-300 hover:bg-stone-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-stone-50 text-stone-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Dies Shop</span>
                            <span class="block text-[9px] font-medium text-gray-400">Maintenance Dies</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('plant_service')" id="add_role_plant_service" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-lime-300 hover:bg-lime-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-lime-50 text-lime-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Plant Service</span>
                            <span class="block text-[9px] font-medium text-gray-400">Maintenance Plant</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('irm')" id="add_role_irm" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-pink-300 hover:bg-pink-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">IRM</span>
                            <span class="block text-[9px] font-medium text-gray-400">Inventory Raw Material</span>
                        </div>
                    </div>
                    <div onclick="setAddRole('logistik')" id="add_role_logistik" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-gray-300 hover:bg-gray-100 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1 2 1 2-1 2 1 2-1 2 1z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Logistik</span>
                            <span class="block text-[9px] font-medium text-gray-400">Logistik & Incoming</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition shadow-sm">Save User</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════ --}}
{{-- MODAL: EDIT USER                           --}}
{{-- ═══════════════════════════════════════════ --}}
<div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Edit User</h2>
                <p class="text-xs text-gray-400 mt-0.5">Update user information</p>
            </div>
            <button onclick="closeEditModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_id">
            <div>
                <label class="text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="edit_name" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">NRP</label>
                <input type="text" name="nrp" id="edit_nrp" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Password <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="password" name="password" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 outline-none transition" placeholder="Leave blank to keep current">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Role</label>
                <input type="hidden" name="role" id="edit_role_val" required>
                <div class="grid grid-cols-2 gap-3 mt-1.5">
                    <div onclick="setEditRole('admin')" id="edit_role_admin" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-slate-300 hover:bg-slate-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Admin</span>
                            <span class="block text-[9px] font-medium text-gray-400">System Administrator</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('operator')" id="edit_role_operator" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-sky-300 hover:bg-sky-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Operator</span>
                            <span class="block text-[9px] font-medium text-gray-400">Produksi Line</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('ppc')" id="edit_role_ppc" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">PPC</span>
                            <span class="block text-[9px] font-medium text-gray-400">Production Planning Control</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('foreman')" id="edit_role_foreman" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-amber-300 hover:bg-amber-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Foreman</span>
                            <span class="block text-[9px] font-medium text-gray-400">Line Foreman</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('leader a')" id="edit_role_leader_a" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader A</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line A</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('leader b')" id="edit_role_leader_b" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader B</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line B</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('leader c')" id="edit_role_leader_c" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader C</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line C</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('leader d')" id="edit_role_leader_d" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-violet-300 hover:bg-violet-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Leader D</span>
                            <span class="block text-[9px] font-medium text-gray-400">Leader Line D</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('shearing')" id="edit_role_shearing" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-orange-300 hover:bg-orange-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Shearing</span>
                            <span class="block text-[9px] font-medium text-gray-400">Shearing Section</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('handwork')" id="edit_role_handwork" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-teal-300 hover:bg-teal-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Handwork</span>
                            <span class="block text-[9px] font-medium text-gray-400">Handwork Section</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('quality')" id="edit_role_quality" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-cyan-300 hover:bg-cyan-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Quality</span>
                            <span class="block text-[9px] font-medium text-gray-400">Quality Control</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('production')" id="edit_role_production" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-gray-300 hover:bg-gray-100 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Production</span>
                            <span class="block text-[9px] font-medium text-gray-400">Production Staff</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('supervisor')" id="edit_role_supervisor" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-emerald-300 hover:bg-emerald-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Supervisor</span>
                            <span class="block text-[9px] font-medium text-gray-400">Area Supervisor</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('manager')" id="edit_role_manager" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-rose-300 hover:bg-rose-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Manager</span>
                            <span class="block text-[9px] font-medium text-gray-400">Manager Produksi</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('kadiv')" id="edit_role_kadiv" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-red-300 hover:bg-red-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Kadiv</span>
                            <span class="block text-[9px] font-medium text-gray-400">Kepala Divisi</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('direktur')" id="edit_role_direktur" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-purple-300 hover:bg-purple-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Direktur</span>
                            <span class="block text-[9px] font-medium text-gray-400">Direktur Utama</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('presdir')" id="edit_role_presdir" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-yellow-300 hover:bg-yellow-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Presdir</span>
                            <span class="block text-[9px] font-medium text-gray-400">President Director</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('dies_shop')" id="edit_role_dies_shop" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-stone-300 hover:bg-stone-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-stone-50 text-stone-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Dies Shop</span>
                            <span class="block text-[9px] font-medium text-gray-400">Maintenance Dies</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('plant_service')" id="edit_role_plant_service" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-lime-300 hover:bg-lime-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-lime-50 text-lime-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Plant Service</span>
                            <span class="block text-[9px] font-medium text-gray-400">Maintenance Plant</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('irm')" id="edit_role_irm" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-pink-300 hover:bg-pink-50 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">IRM</span>
                            <span class="block text-[9px] font-medium text-gray-400">Inventory Raw Material</span>
                        </div>
                    </div>
                    <div onclick="setEditRole('logistik')" id="edit_role_logistik" class="role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-gray-300 hover:bg-gray-100 cursor-pointer transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1 2 1 2-1 2 1 2-1 2 1z"/></svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-black text-gray-800 tracking-wide">Logistik</span>
                            <span class="block text-[9px] font-medium text-gray-400">Logistik & Incoming</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition shadow-sm">Update</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════ --}}
{{-- MODAL: DELETE CONFIRMATION                 --}}
{{-- ═══════════════════════════════════════════ --}}
<div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6 text-center">
        <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </div>
        <h2 class="text-lg font-bold text-gray-800 mb-2">Delete User?</h2>
        <p class="text-sm text-gray-500 mb-6">This action cannot be undone. The user will be permanently removed.</p>
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

@push('scripts')
<script>
const roleColors = {
    admin:         { border: 'border-slate-500', bg: 'bg-slate-100/30', ring: 'ring-slate-500/20' },
    operator:      { border: 'border-sky-500', bg: 'bg-sky-50/30', ring: 'ring-sky-500/20' },
    ppc:           { border: 'border-indigo-500', bg: 'bg-indigo-50/30', ring: 'ring-indigo-500/20' },
    foreman:       { border: 'border-amber-500', bg: 'bg-amber-50/30', ring: 'ring-amber-500/20' },
    'leader a':    { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    'leader b':    { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    'leader c':    { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    'leader d':    { border: 'border-violet-500', bg: 'bg-violet-50/30', ring: 'ring-violet-500/20' },
    shearing:      { border: 'border-orange-500', bg: 'bg-orange-50/30', ring: 'ring-orange-500/20' },
    handwork:      { border: 'border-teal-500', bg: 'bg-teal-50/30', ring: 'ring-teal-500/20' },
    quality:       { border: 'border-cyan-500', bg: 'bg-cyan-50/30', ring: 'ring-cyan-500/20' },
    production:    { border: 'border-gray-500', bg: 'bg-gray-50/30', ring: 'ring-gray-500/20' },
    supervisor:    { border: 'border-emerald-500', bg: 'bg-emerald-50/30', ring: 'ring-emerald-500/20' },
    manager:       { border: 'border-rose-500', bg: 'bg-rose-50/30', ring: 'ring-rose-500/20' },
    kadiv:         { border: 'border-red-500', bg: 'bg-red-50/30', ring: 'ring-red-500/20' },
    direktur:      { border: 'border-purple-500', bg: 'bg-purple-50/30', ring: 'ring-purple-500/20' },
    presdir:       { border: 'border-yellow-500', bg: 'bg-yellow-50/30', ring: 'ring-yellow-500/20' },
    dies_shop:     { border: 'border-stone-500', bg: 'bg-stone-50/30', ring: 'ring-stone-500/20' },
    plant_service: { border: 'border-lime-500', bg: 'bg-lime-50/30', ring: 'ring-lime-500/20' },
    irm:           { border: 'border-pink-500', bg: 'bg-pink-50/30', ring: 'ring-pink-500/20' },
    logistik:      { border: 'border-gray-500', bg: 'bg-gray-50/30', ring: 'ring-gray-500/20' },
};

function normalizeRoleId(role) {
    return role.replace(/\s+/g, '_');
}

function setAddRole(val) {
    document.getElementById('add_role_val').value = val;
    document.querySelectorAll('#userModal .role-card').forEach(c => {
        c.className = 'role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-gray-300 hover:bg-gray-50 cursor-pointer transition-all duration-200 group';
    });
    const card = document.getElementById('add_role_' + normalizeRoleId(val));
    if (card) {
        const color = roleColors[val];
        card.className = 'role-card flex items-center gap-3 p-3 rounded-2xl border-2 ' + color.border + ' ' + color.bg + ' cursor-pointer transition-all duration-200 group shadow-md ring-2 ' + color.ring;
    }
}

function setEditRole(val) {
    document.getElementById('edit_role_val').value = val;
    document.querySelectorAll('#editModal .role-card').forEach(c => {
        c.className = 'role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-gray-300 hover:bg-gray-50 cursor-pointer transition-all duration-200 group';
    });
    const card = document.getElementById('edit_role_' + normalizeRoleId(val));
    if (card) {
        const color = roleColors[val];
        card.className = 'role-card flex items-center gap-3 p-3 rounded-2xl border-2 ' + color.border + ' ' + color.bg + ' cursor-pointer transition-all duration-200 group shadow-md ring-2 ' + color.ring;
    }
}
function openModal() {
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');
    setTimeout(() => {
        document.getElementById('modalBackdrop').classList.remove('opacity-0');
        document.getElementById('modalBox').classList.remove('opacity-0', 'scale-95');
    }, 10);
}
window.closeModal = function () {
    document.getElementById('modalBackdrop').classList.add('opacity-0');
    document.getElementById('modalBox').classList.add('opacity-0', 'scale-95');
    setTimeout(() => {
        document.getElementById('userModal').classList.add('hidden');
        document.getElementById('userModal').classList.remove('flex');
    }, 300);
};

function openEditModal(id, name, nrp, role) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_nrp').value = nrp;
    setEditRole(role);
    var form = document.getElementById('editForm');
    form.action = '/admin/users/' + id;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}
window.closeEditModal = function () {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
};

function openDeleteModal(id) {
    var form = document.getElementById('deleteForm');
    form.action = '/admin/users/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}
window.closeDeleteModal = function () {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
};
</script>
@endpush
