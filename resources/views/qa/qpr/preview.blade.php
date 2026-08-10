<x-app-layout pageTitle="Preview QPR">
    @push('scripts')
    <script>window.deferSkeletonHide = true;</script>
    @endpush

    <div x-data="qprForm({ apiUrl: '{{ url('/') }}', id: {{ $id ?? 'null' }}, userRole: '{{ auth()->user()->role ?? 'Guest' }}', userId: {{ auth()->id() ?? 'null' }}, userName: '{{ auth()->user()->name ?? '' }}', userDepartment: '{{ auth()->user()->department ?? '' }}' })" class="max-w-5xl mx-auto space-y-6">
        
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 print:hidden">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ url('/qpr') }}" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </a>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Detail QPR</h1>

                </div>
                <p class="text-slate-400 text-sm font-semibold" x-text="form.no_qpr || 'Memuat...'"></p>
            </div>
            
            <div class="flex items-center gap-3">
                <span :class="form.status === 'OPEN' ? 'bg-amber-100 text-amber-800 border-amber-500' : (form.status === 'Draft' ? 'bg-slate-100 text-slate-600 border-slate-300' : 'bg-indigo-100 text-indigo-700 border-indigo-200')"
                      class="px-4 py-2 rounded-xl text-xs font-black border uppercase tracking-wider shadow-sm" x-text="form.status">
                </span>
            </div>
        </div>

        <div>
            @include('qa.qpr.paper-preview')

            {{-- Footer Actions --}}
            <div class="flex items-center justify-end mt-8 pt-6 border-t border-slate-200">
                <template x-if="canSubmitApprove">
                    <button @click="submit('Approve')" class="px-8 py-2.5 bg-red-600 text-white rounded-xl text-sm font-black hover:bg-red-700 transition-all shadow-lg shadow-red-600/20 flex items-center gap-2" :disabled="loading">
                        <template x-if="loading"><div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div></template>
                        <span x-text="submitButtonText"></span>
                    </button>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
