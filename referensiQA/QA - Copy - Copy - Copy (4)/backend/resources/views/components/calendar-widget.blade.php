<div x-data="calendarNotes()" x-init="initCalendar()" class="bg-white rounded-[1.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-5 h-full flex flex-col relative z-20 transition-all hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)]">

    {{-- Calendar Header --}}
    <div class="flex items-center justify-between mb-2">
        <button @click="prevMonth()" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 transition-all text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="text-center">
            <p class="text-sm font-black text-slate-800" x-text="monthNames[currentMonth] + ' ' + currentYear"></p>
        </div>
        <button @click="nextMonth()" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 transition-all text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    {{-- Day Headers --}}
    <div class="grid grid-cols-7 mb-2">
        <template x-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d">
            <div class="text-center text-[9px] font-black text-slate-400 uppercase py-1" x-text="d"></div>
        </template>
    </div>

    {{-- Calendar Grid --}}
    <div class="grid grid-cols-7 gap-y-0.5">
        <template x-for="day in calendarDays" :key="day.key">
            <div @click="day.date && selectDay(day.date)"
                 :title="day.date && hasNote(day.date) ? getNoteText(day.date) : ''"
                 :class="{
                     'opacity-20 pointer-events-none': !day.date,
                     'cursor-pointer': day.date,
                     'bg-blue-600 text-white rounded-xl shadow-md shadow-blue-200': day.date && selectedDate === day.date && showNoteModal,
                     'bg-blue-50 rounded-xl': day.date && isToday(day.date) && !(selectedDate === day.date && showNoteModal),
                     'hover:bg-slate-100 rounded-xl': day.date && !(selectedDate === day.date && showNoteModal) && !isToday(day.date),
                 }"
                 class="relative flex flex-col items-center justify-center h-8 w-full sm:w-8 mx-auto transition-all group">
                <span class="text-[12px] font-black leading-none" :class="(selectedDate === day.date && showNoteModal) ? 'text-white' : (isToday(day.date) ? 'text-blue-600' : 'text-slate-700')" x-text="day.label"></span>
                
                {{-- Note dot (mark) --}}
                <template x-if="day.date && hasNote(day.date)">
                    <span class="absolute bottom-1 w-1 h-1 rounded-full transition-transform group-hover:scale-125" :class="(selectedDate === day.date && showNoteModal) ? 'bg-white' : 'bg-red-500'"></span>
                </template>
            </div>
        </template>
    </div>

    {{-- Note Modal Popup (Like Google Calendar) --}}
    <template x-if="showNoteModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/30 backdrop-blur-sm" @click="closeModal()" x-transition.opacity>
            <div class="bg-white rounded-2xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] w-full max-w-[320px] m-4 relative overflow-hidden transform transition-all" @click.stop x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold tracking-widest uppercase mb-0.5">Catatan Harian</p>
                        <p class="text-sm font-black text-slate-800" x-text="formatDisplayDate(selectedDate)"></p>
                    </div>
                    <button @click="closeModal()" class="w-7 h-7 rounded-full hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="mt-2 w-5 h-5 text-blue-500 shrink-0">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </div>
                        <div class="flex-1">
                            <textarea x-model="currentNote"
                                      placeholder="Tambahkan judul / aktivitas..."
                                      class="w-full min-h-[80px] text-[13px] font-semibold text-slate-700 bg-transparent border-0 border-b-2 border-slate-100 px-0 py-2 resize-none focus:ring-0 focus:border-blue-500 transition-colors placeholder:text-slate-300"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-5 py-4 flex items-center justify-between bg-white">
                    <div>
                        <button x-show="currentNote" @click="deleteNote(); closeModal()"
                                class="px-3 py-1.5 text-[11px] font-bold text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                            Hapus
                        </button>
                    </div>
                    <button @click="saveNote(); closeModal()"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-black rounded-xl shadow-md shadow-blue-200 transition-all active:scale-95">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
