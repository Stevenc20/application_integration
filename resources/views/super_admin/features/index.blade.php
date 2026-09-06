@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto" x-data="permissionsDashboard()">
    
    <!-- Page Header -->
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Feature Permissions</h1>
            <p class="mt-1 text-sm text-slate-500">Manage feature access by organizational role and section.</p>
        </div>
    </div>

    <!-- Top Filter -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-8 flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto min-w-[200px]">
            <label class="block text-sm font-medium text-slate-700 mb-1">Jabatan</label>
            <select x-model="position_id" @change="loadPermissions()" class="w-full form-select px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- All / Global --</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos->id }}">{{ $pos->position_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto min-w-[200px]">
            <label class="block text-sm font-medium text-slate-700 mb-1">Section</label>
            <select x-model="section_id" @change="loadPermissions()" class="w-full form-select px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- All / Global --</option>
                @foreach($sections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <button @click="resetFilters()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors border border-slate-200">
                Reset to Default
            </button>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-show="notification.show" 
         x-transition.opacity.duration.300ms
         class="fixed top-4 right-4 z-50 bg-slate-800 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3"
         style="display: none;">
        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span x-text="notification.message" class="text-sm font-medium"></span>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex justify-center py-12" style="display: none;">
        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- empty state -->
    <div x-show="!loading && !hasSelection()" class="text-center py-12 bg-white rounded-xl shadow-sm border border-slate-200" style="display: none;">
        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-slate-900">No organizational role selected</h3>
        <p class="mt-1 text-sm text-slate-500">Select a Jabatan and Section above to configure feature permissions.</p>
    </div>

    <!-- Permissions List -->
    <div x-show="!loading && hasSelection()" style="display: none;" class="space-y-6">
        <template x-for="(features, groupName) in groups" :key="groupName">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                    <h2 class="font-semibold text-slate-800" x-text="groupName"></h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <template x-for="feature in features" :key="feature.feature_id">
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                            <div>
                                <h3 class="text-base font-medium text-slate-800" x-text="feature.feature_name"></h3>
                                <p class="text-sm text-slate-500 mt-1" x-text="feature.feature_code"></p>
                                
                                <!-- Advanced actions (expandable) -->
                                <div class="mt-3 flex flex-wrap gap-4" x-show="feature.is_active" x-collapse>
                                    <template x-for="(val, actionKey) in feature.actions" :key="actionKey">
                                        <label class="inline-flex items-center gap-2 cursor-pointer group">
                                            <input type="checkbox" 
                                                   class="form-checkbox h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                                                   :checked="val"
                                                   @change="toggleAction(feature.feature_id, actionKey, $event.target.checked)">
                                            <span class="text-xs font-medium text-slate-600 capitalize group-hover:text-slate-900" x-text="actionKey.replace('can_', '')"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center sm:justify-end shrink-0">
                                <!-- Modern Toggle Switch -->
                                <button type="button" 
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
                                    :class="feature.is_active ? 'bg-blue-600' : 'bg-slate-200'"
                                    role="switch" 
                                    :aria-checked="feature.is_active.toString()"
                                    @click="toggleFeature(feature.feature_id, !feature.is_active)">
                                    <span class="sr-only">Toggle feature</span>
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                          :class="feature.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                                <span class="ml-3 text-sm font-medium w-8" :class="feature.is_active ? 'text-blue-600' : 'text-slate-400'" x-text="feature.is_active ? 'ON' : 'OFF'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('permissionsDashboard', () => ({
        position_id: '',
        section_id: '',
        groups: {},
        loading: false,
        notification: { show: false, message: '' },

        init() {
            this.$watch('position_id', () => { if(!this.hasSelection()) this.groups = {}; });
            this.$watch('section_id', () => { if(!this.hasSelection()) this.groups = {}; });
        },

        hasSelection() { return this.position_id !== '' || this.section_id !== ''; },

        resetFilters() {
            this.position_id = '';
            this.section_id = '';
            this.groups = {};
        },

        async loadPermissions() {
            if (!this.hasSelection()) {
                this.groups = {};
                return;
            }
            this.loading = true;
            try {
                const res = await fetch(`{{ route('access-management.features.ajax') }}?position_id=${this.position_id}&section_id=${this.section_id}`);
                const data = await res.json();
                if (data.success) {
                    this.groups = data.groups;
                }
            } catch (err) { console.error(err); } finally { this.loading = false; }
        },

        async toggleFeature(featureId, newState) {
            let success = await this.updateBackend(featureId, 'is_active', newState);
            if(success) {
                for (let g in this.groups) {
                    let f = this.groups[g].find(x => x.feature_id === featureId);
                    if (f) {
                        f.is_active = newState;
                        if(newState) f.actions.can_view = true;
                        if(!newState) {
                            for(let k in f.actions) f.actions[k] = false;
                        }
                        this.showNotification(`${f.feature_name} access ${newState ? 'enabled' : 'disabled'}`);
                        break;
                    }
                }
            }
        },

        async toggleAction(featureId, action, newState) {
            let success = await this.updateBackend(featureId, action, newState);
            if(success) {
                for (let g in this.groups) {
                    let f = this.groups[g].find(x => x.feature_id === featureId);
                    if (f) {
                        f.actions[action] = newState;
                        if(newState && action !== 'can_view') {
                            f.actions.can_view = true;
                            f.is_active = true;
                        }
                        this.showNotification(`${action.replace('can_', '')} permission updated`);
                        break;
                    }
                }
            }
        },

        async updateBackend(featureId, action, state) {
            try {
                const res = await fetch(`{{ route('access-management.features.toggle') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        position_id: this.position_id,
                        section_id: this.section_id,
                        feature_id: featureId,
                        action: action,
                        state: state
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    alert('Error updating permission.'); return false;
                }
                return true;
            } catch (err) {
                console.error(err); alert('Connection error.'); return false;
            }
        },

        showNotification(msg) {
            this.notification.message = msg;
            this.notification.show = true;
            setTimeout(() => { this.notification.show = false; }, 3000);
        }
    }));
});
</script>
@endsection
