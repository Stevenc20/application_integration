@extends('layouts.supervisor')
@section('title', 'Notifikasi')
@section('header_title', 'Notifikasi')

@section('content')
<div class="mb-3">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('overview') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-red-600 transition-colors">
        <i class="bx bx-arrow-back text-lg"></i>
        Kembali
    </a>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800">Semua Notifikasi</h2>
        @if($notifications->whereNull('read_at')->count() > 0)
            <button id="markAllRead" class="text-xs font-semibold text-primary-red hover:text-red-700 transition-colors">
                Tandai Semua Dibaca
            </button>
        @endif
    </div>

    <div id="notificationList" class="divide-y divide-gray-50">
        @forelse($notifications as $n)
            @php
                $hambatanId = $n->data['hambatan_id'] ?? null;
                $message = $n->data['message'] ?? 'Notifikasi';
            @endphp
            <div class="notif-item flex items-start gap-4 px-5 py-4 hover:bg-gray-50 transition-colors {{ $n->read_at ? '' : 'bg-red-50/30' }} {{ $hambatanId ? 'cursor-pointer' : '' }}"
                 data-id="{{ $n->id }}"
                 data-hambatan-id="{{ $hambatanId }}">
                <div class="flex-shrink-0 mt-1">
                    <div class="w-10 h-10 rounded-full {{ $n->read_at ? 'bg-gray-100 text-gray-400' : 'bg-red-100 text-primary-red' }} flex items-center justify-center">
                        <i class="bx bx-bell text-lg"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm {{ $n->read_at ? 'text-gray-600' : 'text-gray-900 font-semibold' }} leading-relaxed">
                        {{ $message }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex-shrink-0 flex items-center gap-2">
                    @if(!$n->read_at)
                        <button class="mark-read-btn text-xs text-gray-400 hover:text-gray-600 transition-colors p-1" title="Tandai dibaca">
                            <i class="bx bx-check-circle text-lg"></i>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-10 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="bx bx-bell-off text-2xl text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-400">Tidak ada notifikasi</p>
            </div>
        @endforelse
    </div>

    <div class="px-5 py-4 border-t border-gray-100">
        {{ $notifications->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function markRead(id, item) {
        return fetch('/notifications/' + id + '/read', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } })
        .then(() => {
            item.classList.remove('bg-red-50/30');
            const p = item.querySelector('p:first-child');
            if (p) { p.classList.remove('font-semibold', 'text-gray-900'); p.classList.add('text-gray-600'); }
            const iconCircle = item.querySelector('.w-10');
            if (iconCircle) { iconCircle.classList.remove('bg-red-100', 'text-primary-red'); iconCircle.classList.add('bg-gray-100', 'text-gray-400'); }
            const markBtn = item.querySelector('.mark-read-btn');
            if (markBtn) markBtn.remove();
        });
    }

    // Mark single notification as read
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const item = this.closest('.notif-item');
            const id = item?.dataset.id;
            if (id) markRead(id, item);
        });
    });

    // Click entire row -> mark read + navigate
    document.querySelectorAll('.notif-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.mark-read-btn')) return;
            const id = this.dataset.id;
            const hambatanId = this.dataset.hambatanId;
            if (!hambatanId || !id) return;
            navigator.sendBeacon('/notifications/' + id + '/read', new URLSearchParams({ _token: csrfToken }));
            window.location.href = '/hambatan-jalur/' + hambatanId;
        });
    });

    // Mark all as read
    const markAllBtn = document.getElementById('markAllRead');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            const unreadItems = Array.from(document.querySelectorAll('.notif-item')).filter(i => i.querySelector('.mark-read-btn'));
            let promises = [];
            unreadItems.forEach(item => {
                const id = item.dataset.id;
                if (id) promises.push(markRead(id, item));
            });
            Promise.all(promises).then(() => { markAllBtn.remove(); });
        });
    }
});
</script>
@endsection
