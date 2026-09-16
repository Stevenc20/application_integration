{{-- Tailwind Toast Notification Component --}}
<div id="toast-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

<style>
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
@keyframes fadeOutUp {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(-20px);
        opacity: 0;
    }
}
.animate-toast-in {
    animation: slideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.animate-toast-out {
    animation: fadeOutUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

<script>
window.showToast = function({ type = 'success', title = 'Pemberitahuan', message = '' }) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    // Create toast card
    const toast = document.createElement('div');
    toast.className = `w-[340px] pointer-events-auto rounded-2xl border bg-white p-4 shadow-[0_10px_30px_rgba(0,0,0,0.08)] flex gap-3 animate-toast-in`;
    
    // Set colors & icons based on type
    let borderClass = 'border-emerald-200';
    let iconBgClass = 'bg-emerald-50 text-emerald-600';
    let iconPath = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>`;
    
    if (type === 'error') {
        borderClass = 'border-red-200';
        iconBgClass = 'bg-red-50 text-[#9F1D1D]';
        iconPath = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>`;
    } else if (type === 'warning') {
        borderClass = 'border-amber-200';
        iconBgClass = 'bg-amber-50 text-amber-600';
        iconPath = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>`;
    }

    toast.className += ` ${borderClass}`;

    toast.innerHTML = `
        <div class="flex-shrink-0 w-8 h-8 rounded-xl ${iconBgClass} flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${iconPath}
            </svg>
        </div>
        <div class="flex-1">
            <h4 class="text-xs font-black text-gray-900 leading-tight uppercase tracking-wider">${title}</h4>
            <p class="text-xs text-gray-500 mt-1 font-medium leading-relaxed">${message}</p>
        </div>
        <button class="flex-shrink-0 text-gray-400 hover:text-gray-600 w-5 h-5 flex items-center justify-center rounded-lg hover:bg-gray-50" onclick="this.closest('.animate-toast-in').remove()">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    `;

    container.appendChild(toast);

    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.classList.replace('animate-toast-in', 'animate-toast-out');
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    }, 4000);
};
</script>
