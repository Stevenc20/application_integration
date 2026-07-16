@extends('layouts.super_admin')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="p-3 sm:p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">Super Admin Dashboard</h1>
        <p class="text-gray-500 text-xs sm:text-sm">Welcome, {{ auth()->user()->name }}. You have full system access.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Users</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\User::count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Features</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\Feature::count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Roles</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\RoleFeature::distinct('role')->count('role') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
