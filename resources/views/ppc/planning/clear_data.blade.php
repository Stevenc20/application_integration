@extends('layouts.ppc')
@section('title', 'Clear Data')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-red-900 via-red-800 to-rose-700 px-6 py-5 shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mx-auto max-w-screen-2xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/10 backdrop-blur rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">CLEAR DATA</h1>
                    <p class="text-slate-300 text-[10px] font-semibold mt-0.5">
                        Hapus data Production Plan untuk testing
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-lg mx-auto w-full">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <form method="POST" action="{{ route('ppc.planning.production_plan.clear') }}" onsubmit="return confirm('Yakin akan menghapus data untuk tanggal tersebut?')">
                @csrf
                @method('DELETE')

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal</label>
                        <input type="date" name="date" value="{{ old('date', request('date', now()->toDateString())) }}" required
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 outline-none transition text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Shift (opsional)</label>
                        <select name="shift" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 outline-none transition text-sm">
                            <option value="">Semua Shift</option>
                            <option value="Pagi">Shift Pagi</option>
                            <option value="Malam">Shift Malam</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">Kosongkan untuk menghapus semua shift</p>
                    </div>

                    <button type="submit" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Clear Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
