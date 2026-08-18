@php
    $user = auth()->user();
    $hasPullAheadAlert = false;
    $pullAheadMessage = '';
    
    if ($user && in_array(strtolower($user->role), ['ppc', 'manager'])) {
        $count = \App\Models\PullAheadRequest::where('status', 'PENDING')
                    ->where('is_read_by_ppc', false)
                    ->count();
        if ($count > 0) {
            $hasPullAheadAlert = true;
            $pullAheadMessage = "Ada $count Request Pull Ahead baru dari Leader yang menunggu Approval.";
        }
    } elseif ($user && (str_contains(strtolower($user->role), 'leader') || str_contains(strtolower($user->role), 'supervisor'))) {
        $count = \App\Models\PullAheadRequest::whereIn('status', ['APPROVED', 'REJECTED', 'APPLIED'])
                    ->where('is_read_by_leader', false)
                    ->where('requested_by', $user->id)
                    ->count();
        if ($count > 0) {
            $hasPullAheadAlert = true;
            $pullAheadMessage = "Ada $count update status pada Request Pull Ahead Anda.";
        }
    }
@endphp

@if($hasPullAheadAlert)
<div x-data="{ show: true }" x-show="show" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display: none;" x-init="$el.style.display = 'flex'">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-center transform transition-all scale-100 mx-4 border border-blue-100">
        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 ring-8 ring-blue-50/50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Pemberitahuan Sistem</h3>
        <p class="text-gray-600 mb-6 leading-relaxed">{{ $pullAheadMessage }}</p>
        <div class="flex flex-col gap-3">
            @if(in_array(strtolower($user->role), ['ppc', 'manager']))
            <a href="{{ route('ppc.pull_ahead.index') }}" class="w-full inline-flex justify-center items-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition-all">
                Tinjau Request
            </a>
            @else
            <a href="{{ route('operational.input_harian') }}" class="w-full inline-flex justify-center items-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition-all">
                Buka Jadwal
            </a>
            @endif
            <button @click="
                show = false; 
                fetch('{{ route('pull_ahead.mark_read') }}', {
                    method: 'POST', 
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
            " class="w-full text-gray-500 hover:text-gray-800 hover:bg-gray-50 font-medium py-2.5 rounded-xl transition-colors">
                Tutup & Tandai Dibaca
            </button>
        </div>
    </div>
</div>
@endif
