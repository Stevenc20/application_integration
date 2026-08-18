<x-app-layout pageTitle="Formulir QPR">
    @push('scripts')
    <script>window.deferSkeletonHide = true;</script>
    @endpush

    <div x-data="qprForm({ apiUrl: '{{ url('/') }}', id: {{ $id ?? 'null' }}, userRole: '{{ auth()->user()->role ?? 'Guest' }}', userDepartment: '{{ auth()->user()->department ?? '' }}', userId: {{ auth()->id() ?? 'null' }}, userName: '{{ auth()->user()->name ?? '' }}', userSignature: {{ auth()->user()->signature ? json_encode(auth()->user()->signature) : 'null' }}, userDepartment: '{{ auth()->user()->department ?? '' }}' })" class="max-w-5xl mx-auto space-y-6">
        
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ url('/qpr') }}" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </a>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight" x-text="editId ? 'Edit QPR' : (isRequestMode ? 'Pengajuan QPR Baru' : 'Buat QPR Baru')"></h1>
                </div>
                <p class="text-slate-400 text-sm font-semibold" x-text="form.no_qpr || 'Memuat...'"></p>
            </div>
            
            <div class="flex items-center gap-3">
                <span :class="form.status === 'OPEN' ? 'bg-amber-100 text-amber-800 border-amber-500' : 'bg-slate-100 text-slate-600 border-slate-300'"
                      class="px-4 py-2 rounded-xl text-xs font-black border uppercase tracking-wider shadow-sm" x-text="form.status">
                </span>
            </div>
        </div>

        <div class="space-y-6">
            
            {{-- Stepper --}}
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm overflow-x-auto">
                <div class="flex items-center min-w-max">
                    <template x-for="(s, i) in availableSteps">
                        <div class="flex items-center" :class="i < (availableSteps.length - 1) ? 'flex-1' : ''">
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-black transition-all duration-300"
                                     :class="step > s.num ? 'bg-red-600 text-white border-2 border-red-600' : 
                                             step === s.num ? 'bg-red-600 text-white border-4 border-red-100 shadow-lg shadow-red-600/20' : 
                                             'bg-slate-50 text-slate-400 border-2 border-slate-200'">
                                    <template x-if="step > s.num">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <template x-if="step <= s.num">
                                        <span x-text="s.num"></span>
                                    </template>
                                </div>
                                <div>
                                    <p class="text-sm font-bold transition-all" :class="step === s.num ? 'text-red-600' : step > s.num ? 'text-slate-800' : 'text-slate-400'" x-text="s.label"></p>
                                    <p class="text-[10px] uppercase font-bold" :class="step === s.num ? 'text-red-400' : 'text-slate-400'" x-text="s.desc"></p>
                                </div>
                            </div>
                            <template x-if="i < (availableSteps.length - 1)">
                                <div class="flex-1 h-0.5 mx-4 rounded-full transition-all duration-300" :class="step > s.num ? 'bg-red-600' : 'bg-slate-200'"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Form Sections --}}
            <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-sm transition-all duration-300">
                
                {{-- STEP 1: Identifikasi --}}
                <div x-show="step === 1" x-transition.opacity.duration.300ms class="space-y-6">
                    <fieldset :disabled="!canEditBasicInfo" :class="!canEditBasicInfo ? 'pointer-events-none opacity-90' : ''" class="space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-1.5 h-6 bg-red-600 rounded-full"></div>
                        <h2 class="text-lg font-black text-slate-800">Identifikasi Part</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">No. Job <span class="text-red-600">*</span></label>
                            <div class="flex gap-2">
                                <input type="text" x-model="form.no_job" @keydown.enter.prevent="autofillByJobNo" placeholder="Cth: PB-061 NBI" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                                <button type="button" @click="autofillByJobNo" class="px-4 py-3 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl font-bold transition-all text-sm whitespace-nowrap shadow-sm border border-blue-100">
                                    Cari
                                </button>
                            </div>                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Model <span class="text-red-600">*</span></label>
                            <input type="text" x-model="form.model" placeholder="Cth: VT-02" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tanggal <span class="text-red-600">*</span></label>
                            <input type="date" x-model="form.tanggal" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">No. QPR <span class="text-red-600">*</span></label>
                            <input type="text" x-model="form.no_qpr" class="w-full px-4 py-3 bg-red-50/50 border border-red-200 rounded-xl text-sm font-bold text-red-700 outline-none" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div :class="!form.computed_investigator ? '' : 'md:col-span-2'">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nama Part <span class="text-red-600">*</span></label>
                            <input type="text" x-model="form.nama_part" placeholder="Cth: Bracket Assembly" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                        </div>
                        <div x-show="!form.computed_investigator" x-transition>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Penemu NG / PIC (Bila Manual)</label>
                            <input type="text" x-model="form.pic" placeholder="Cth: Nama Operator" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                        </div>
                    </div>

                    <div x-show="!isRequestMode">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6 mt-6">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Rework/Pcs <span class="text-red-600">*</span></label>
                                <input type="number" x-model.number="form.rework_qty" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Reject/Pcs <span class="text-red-600">*</span></label>
                                <input type="number" x-model.number="form.reject_qty" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-red-600 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Stock/Pcs <span class="text-red-600">*</span></label>
                                <input type="number" x-model.number="form.stock_ippi_qty" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Repair Process / Proses Terkait <span class="text-red-600">*</span></label>
                                <input type="text" x-model="form.proses_repair" placeholder="Cth: OP 10, OP 20" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all mb-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="op in ['OP 10', 'OP 20', 'OP 30', 'OP 40', 'OP 50', 'OP 60']">
                                        <button @click="togProses(op)" 
                                        type="button"
                                        :class="(form.proses_repair || '').includes(op) ? 'bg-red-50 text-red-700 border-red-200' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300'"
                                        class="px-2.5 py-1 rounded-md text-[10px] font-bold border transition-all shadow-sm" x-text="op"></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Rencana Produksi <span class="text-red-600">*</span></label>
                            <input type="date" x-model="form.rencana_produksi" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                        </div>
                    </div>
                    </fieldset>
                </div>

                {{-- STEP 2: Deskripsi --}}
                <div x-show="step === 2" x-transition.opacity.duration.300ms class="space-y-8" style="display: none;">
                    <fieldset :disabled="!canEditBasicInfo" :class="!canEditBasicInfo ? 'pointer-events-none opacity-90' : ''" class="space-y-8">
                    {{-- Sketch Upload --}}
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                            <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                            <h2 class="text-lg font-black text-slate-800">Sketch Area & Foto Masalah</h2>
                        </div>
                        
                        <div class="relative">
                            <!-- Area Upload (Muncul jika kosong) -->
                            <label for="sketch-upload" class="block cursor-pointer" x-show="!form.sketches || form.sketches.length === 0">
                                <div class="border-2 border-dashed border-slate-300 hover:border-blue-500 bg-slate-50 hover:bg-blue-50/50 rounded-2xl p-10 flex flex-col items-center justify-center transition-all group min-h-[250px]">
                                    <div class="w-14 h-14 bg-white shadow-sm border border-slate-200 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 group-hover:border-blue-200 transition-all">
                                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700">Klik untuk upload foto / sketch</p>
                                    <p class="text-xs text-slate-400 mt-2">Sertakan lokasi kejadian, shift & jam kejadian</p>
                                </div>
                            </label>
                            
                            <input id="sketch-upload" type="file" multiple accept="image/*" class="hidden" @change="handleSketchUpload">

                            <!-- Area Preview (Muncul jika ada foto) -->
                            <div class="space-y-4" x-show="(form.sketches || []).length > 0" style="display: none;">
                                <template x-for="(src, i) in (form.sketches || [])">
                                    <div class="relative group rounded-2xl overflow-hidden border-2 border-slate-200 shadow-sm bg-slate-50 min-h-[300px] flex items-center justify-center">
                                        <img :src="src" class="w-full object-contain max-h-[500px]">
                                        
                                        <!-- Tombol Hapus (Besar) -->
                                        <button @click="form.sketches.splice(i, 1)" class="absolute top-4 right-4 w-12 h-12 bg-white/90 backdrop-blur rounded-2xl text-red-600 shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 hover:text-white hover:scale-110 z-10 border border-red-100">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        
                                        <!-- Label Info -->
                                        <div class="absolute top-4 left-4 px-4 py-2 bg-white/90 backdrop-blur rounded-xl text-xs font-black text-slate-700 shadow-sm border border-slate-200">
                                            Foto / Sketch <span x-text="i + 1"></span>
                                        </div>
                                    </div>
                                </template>
                                
                                <!-- Tombol Tambah Foto Lain -->
                                <label for="sketch-upload" class="cursor-pointer block border-2 border-dashed border-slate-300 hover:border-blue-500 bg-white hover:bg-blue-50/50 rounded-2xl p-6 flex flex-col items-center justify-center transition-all group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600 group-hover:text-blue-600">Tambah Foto Lain</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Area Problem --}}
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                            <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                            <h2 class="text-lg font-black text-slate-800">Area Problem</h2>
                        </div>
                        <p class="text-xs text-slate-400 mb-3">Area kecacatan yang terjadi pada part (1-16). Klik untuk memilih/menyesuaikan.</p>
                        
                        <div class="grid grid-cols-8 gap-2 max-w-2xl">
                            <template x-for="n in 16" :key="n">
                                <label class="flex flex-col items-center justify-center gap-1.5 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 transition-colors aspect-square"
                                       :class="(form.area || '').split(',').map(s=>s.trim()).includes(n.toString()) ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'text-slate-500'">
                                    <input type="checkbox" :value="n" 
                                           :checked="(form.area || '').split(',').map(s=>s.trim()).includes(n.toString())"
                                           @change="toggleArea(n)"
                                           class="hidden">
                                    <span class="text-sm font-black leading-none" x-text="n"></span>
                                    <div class="w-4 h-4 rounded-[4px] border-2 flex items-center justify-center transition-all"
                                         :class="(form.area || '').split(',').map(s=>s.trim()).includes(n.toString()) ? 'border-blue-500 bg-blue-500' : 'border-slate-300 bg-white'">
                                        <svg x-show="(form.area || '').split(',').map(s=>s.trim()).includes(n.toString())" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Problem Types --}}
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                            <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                            <h2 class="text-lg font-black text-slate-800">Frekuensi & Jenis Problem</h2>
                        </div>
                        
                        <div class="mb-6" x-show="!isRequestMode" style="display: none;">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Frekuensi Problem <span class="text-red-600">*</span></label>
                            <div class="flex flex-wrap gap-3">
                                <template x-for="f in ['Baru Pertama', 'Kadang-Kadang', 'Sering']">
                                    <button @click="form.kategori_problem = f" 
                                            :class="form.kategori_problem === f ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-600/20' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300'"
                                            class="px-5 py-2.5 rounded-xl text-xs font-bold border transition-all" x-text="f"></button>
                                </template>
                            </div>
                        </div>

                        <div x-show="!form.area" class="p-6 bg-slate-50 border border-slate-200 rounded-xl text-center">
                            <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-xs font-bold text-slate-500">Silakan pilih Area Problem di atas terlebih dahulu untuk menentukan Jenis Problem.</p>
                        </div>
                        
                        <div x-show="form.area" style="display: none;">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Rincian Problem per Area <span class="text-red-600">*</span></label>
                            
                            <div class="space-y-4">
                                <template x-for="a in (form.area || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="a">
                                    <div class="p-5 bg-white border border-slate-200 shadow-sm rounded-xl relative overflow-hidden group hover:border-blue-300 transition-colors">
                                        <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                                        <div class="flex items-center gap-2 mb-3">
                                            <span class="bg-blue-100 text-blue-700 text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-widest">Area</span>
                                            <span class="text-sm font-black text-slate-800" x-text="a"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="t in ['Marking Tidak Jelas', 'Pecah', 'Neck', 'Karat', 'Pecok', 'Benjol', 'Gelombang', 'Sockline', 'Baret', 'Burry', 'Keriput', 'Mencuat', 'Penyok', 'Flange Miring']">
                                                <button type="button" @click="togAreaDefect(a, t)" 
                                                        :class="(form.area_problems && form.area_problems[a] && form.area_problems[a].some(x => x.toLowerCase() === t.toLowerCase())) ? 'bg-red-50 text-red-700 border-red-200 shadow-sm' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-bold border transition-all" x-text="t"></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Details --}}
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                            <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                            <h2 class="text-lg font-black text-slate-800">Keterangan & Lokasi</h2>
                        </div>

                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Keterangan / Detail Problem <span class="text-red-600">*</span></label>
                            <textarea x-model="form.defect_keterangan" rows="3" placeholder="Jelaskan detail masalah yang ditemukan..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div x-show="!isRequestMode" style="display: none;">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Shift <span class="text-red-600">*</span></label>
                                <select x-model="form.shift" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Pilih --</option>
                                    <option value="Shift 1 (Pagi)">Shift 1 (Pagi)</option>
                                    <option value="Shift 2 (Sore)">Shift 2 (Sore)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Jam <span class="text-red-600">*</span></label>
                                <input type="time" x-model="form.jam" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                            </div>
                            <div x-show="!isRequestMode" style="display: none;">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Last Date Problem</label>
                                <input type="date" x-model="form.last_date_problem" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all">
                            </div>
                            <div x-show="!isRequestMode" style="display: none;">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Lokasi Kejadian <span class="text-red-600">*</span></label>
                                <select x-model="form.lokasi" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Pilih --</option>
                                    <option value="PRESS A">PRESS A</option>
                                    <option value="PRESS B">PRESS B</option>
                                    <option value="PRESS C">PRESS C</option>
                                    <option value="PRESS D">PRESS D</option>
                                </select>
                            </div>
                            <div class="md:col-span-2" x-show="!isRequestMode" style="display: none;">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Dokumen Referensi</label>
                                <select x-model="form.dokumen" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/10 focus:border-red-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Pilih --</option>
                                    <option value="Plan Customer - ADM (Astra Daihatsu Motor)">Plan Customer - ADM (Astra Daihatsu Motor)</option>
                                    <option value="Plan Customer - TMMIN (Toyota Motor Manufacturing Indonesia)">Plan Customer - TMMIN (Toyota Motor Manufacturing Indonesia)</option>
                                    <option value="Plan Customer - IAMI (Isuzu Astra Motor Indonesia)">Plan Customer - IAMI (Isuzu Astra Motor Indonesia)</option>
                                    <option value="Plan Customer - PAMA PERSADA">Plan Customer - PAMA PERSADA</option>
                                    <option value="Plan Customer - Wuling">Plan Customer - Wuling</option>
                                    <option value="Plan Customer - Gaya Motor">Plan Customer - Gaya Motor</option>
                                    <option value="Laporan Kerja Harian (Produksi)">Laporan Kerja Harian (Produksi)</option>
                                    <option value="Laporan Harian Inspeksi - Incoming">Laporan Harian Inspeksi - Incoming</option>
                                    <option value="Laporan Harian Inspeksi - Pre-delivery">Laporan Harian Inspeksi - Pre-delivery</option>
                                    <option value="Laporan Harian Inspeksi - GP-12">Laporan Harian Inspeksi - GP-12</option>
                                    <option value="Laporan Harian Inspeksi - Stamping">Laporan Harian Inspeksi - Stamping</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    </fieldset>
                </div>

                {{-- STEP 3: Analisa 4M+1E --}}
                <div x-show="step === 3" x-transition.opacity.duration.300ms class="space-y-6" style="display: none;">
                    <fieldset :disabled="!canEditBasicInfo" :class="!canEditBasicInfo ? 'pointer-events-none opacity-90' : ''" class="space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-1.5 h-6 bg-purple-600 rounded-full"></div>
                        <h2 class="text-lg font-black text-slate-800">Analisa Penyebab (4M+1E)</h2>
                    </div>
                    <p class="text-xs text-slate-400 mb-6">Centang & isi faktor yang relevan menyebabkan problem kualitas ini.</p>

                    <div class="space-y-4">
                        <template x-for="f in [
                            { key: 'analisa_man', ket: 'analisa_man_ket', label: 'Man', color: 'blue', ph: 'Kesalahan manusia...' },
                            { key: 'analisa_method', ket: 'analisa_method_ket', label: 'Method', color: 'amber', ph: 'Kesalahan metode...' },
                            { key: 'analisa_machine', ket: 'analisa_machine_ket', label: 'Machine', color: 'red', ph: 'Masalah mesin...' },
                            { key: 'analisa_material', ket: 'analisa_material_ket', label: 'Material', color: 'emerald', ph: 'Masalah material...' },
                            { key: 'analisa_environment', ket: 'analisa_environment_ket', label: 'Environ.', color: 'purple', ph: 'Faktor lingkungan...' }
                        ]">
                            <div class="flex items-center gap-4 bg-slate-50 p-2 rounded-2xl border border-slate-200">
                                <button @click="form[f.key] = !form[f.key]" 
                                        :class="form[f.key] ? 'bg-' + f.color + '-600 border-' + f.color + '-600 text-white' : 'bg-white border-slate-300 text-transparent'"
                                        class="w-6 h-6 rounded-lg border-2 flex items-center justify-center shrink-0 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <div :class="'text-' + f.color + '-700 bg-' + f.color + '-100'" class="w-24 py-2 text-center rounded-xl text-xs font-black uppercase tracking-wider shrink-0" x-text="f.label"></div>
                                <input type="text" x-model="form[f.ket]" :placeholder="f.ph" :disabled="!form[f.key]" 
                                       :class="form[f.key] ? 'bg-white border-slate-200' : 'bg-transparent border-transparent opacity-40'"
                                       class="flex-1 px-4 py-2 border rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                            </div>
                        </template>
                    </div>
                    </fieldset>
                </div>

                {{-- STEP 4: Correction --}}
                <div x-show="step === 4" x-transition.opacity.duration.300ms class="space-y-8" style="display: none;">
                    
                    {{-- Temporary Correction --}}
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                            <div class="w-1.5 h-6 bg-emerald-600 rounded-full"></div>
                            <h2 class="text-lg font-black text-slate-800">Langkah Penanggulangan Sementara (Correction)</h2>
                        </div>

                        <fieldset :disabled="!canEditSeksiSection" :class="!canEditSeksiSection ? 'opacity-70 pointer-events-none grayscale-[30%]' : ''">
                        <div class="space-y-4">
                            <template x-for="(item, idx) in (form.correction_items || [])">
                                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                                    <div class="flex items-center gap-3 mb-4">
                                        <button @click="item.checked = !item.checked" 
                                                :class="item.checked ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-300 text-transparent'"
                                                class="w-5 h-5 rounded-[6px] border-2 flex items-center justify-center shrink-0 transition-all">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <input type="text" x-model="item.text" placeholder="Deskripsi langkah..." class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:border-emerald-500">
                                        <template x-if="(form.correction_items || []).length > 1">
                                            <button @click="removeCorrection(idx)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg">✕</button>
                                        </template>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 ml-8">
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Target</label>
                                            <input type="date" x-model="item.target" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold outline-none focus:border-emerald-500">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">PIC</label>
                                            <input type="text" x-model="item.pic" placeholder="Nama PIC" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold outline-none focus:border-emerald-500">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Status (PDCA)</label>
                                            <div class="relative w-full h-[38px]">
                                                <!-- Visual Layer -->
                                                <div class="absolute inset-0 flex items-center justify-between px-3 bg-white border border-slate-200 rounded-lg pointer-events-none">
                                                    <div class="flex items-center gap-2">
                                                        <svg width="20" height="20" viewBox="0 0 28 28" class="shrink-0">
                                                            <circle cx="14" cy="14" r="12" fill="white" stroke="#CBD5E1" stroke-width="1.5" />
                                                            <path x-show="item.status === 'P'" d="M 14 14 L 14 2 A 12 12 0 0 1 26 14 Z" fill="#0F172A" style="display: none;" />
                                                            <path x-show="item.status === 'D'" d="M 14 14 L 14 26 A 12 12 0 0 0 14 2 Z" fill="#0F172A" style="display: none;" />
                                                            <path x-show="item.status === 'C'" d="M 14 14 L 14 2 A 12 12 0 1 1 2 14 Z" fill="#0F172A" style="display: none;" />
                                                            <path x-show="item.status === 'A'" d="M 14 2 A 12 12 0 1 1 13.999 2 Z" fill="#0F172A" style="display: none;" />
                                                            <line x1="14" y1="2" x2="14" y2="26" :stroke="item.status === 'A' ? 'white' : '#CBD5E1'" stroke-width="1.5" />
                                                            <line x1="2" y1="14" x2="26" y2="14" :stroke="item.status === 'A' ? 'white' : '#CBD5E1'" stroke-width="1.5" />
                                                            <circle cx="14" cy="14" r="12" fill="none" stroke="#CBD5E1" stroke-width="1.5" />
                                                        </svg>
                                                        <span class="text-xs font-bold text-slate-700" x-text="item.status === 'P' ? 'Plan' : item.status === 'D' ? 'Do' : item.status === 'C' ? 'Check' : item.status === 'A' ? 'Action' : '-'"></span>
                                                    </div>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </div>
                                                <!-- Native Select -->
                                                <select x-model="item.status" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer appearance-none">
                                                    <option value="">-</option>
                                                    <option value="P">Plan</option>
                                                    <option value="D">Do</option>
                                                    <option value="C">Check</option>
                                                    <option value="A">Action</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <button @click="addCorrection()" class="text-xs font-bold text-emerald-600 bg-emerald-50 px-4 py-2 rounded-xl hover:bg-emerald-100 transition-all">+ Tambah Langkah</button>
                        </div>
                        </fieldset>
                    </div>

                    {{-- Dampak --}}
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                            <div class="w-1.5 h-6 bg-purple-600 rounded-full"></div>
                            <h2 class="text-lg font-black text-slate-800">Penanggulangan Dampak (Produk Sejenis)</h2>
                        </div>

                        <fieldset :disabled="!canEditSeksiSection" :class="!canEditSeksiSection ? 'opacity-70 pointer-events-none grayscale-[30%]' : ''">
                        <div class="space-y-4">
                            <template x-for="(item, idx) in (form.dampak_items || [])">
                                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                                    <div class="flex items-center gap-3 mb-4">
                                        <button @click="item.checked = !item.checked" 
                                                :class="item.checked ? 'bg-purple-600 border-purple-600 text-white' : 'bg-white border-slate-300 text-transparent'"
                                                class="w-5 h-5 rounded-[6px] border-2 flex items-center justify-center shrink-0 transition-all">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <input type="text" x-model="item.text" placeholder="Cek produk sejenis..." class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:border-purple-500">
                                        <template x-if="(form.dampak_items || []).length > 1">
                                            <button @click="removeDampak(idx)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg">✕</button>
                                        </template>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 ml-8">
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Target</label>
                                            <input type="date" x-model="item.target" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold outline-none focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">PIC Seksi</label>
                                            <input type="text" x-model="item.pic_seksi" placeholder="Seksi PIC" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold outline-none focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Status (PDCA)</label>
                                            <div class="relative w-full h-[38px]">
                                                <!-- Visual Layer -->
                                                <div class="absolute inset-0 flex items-center justify-between px-3 bg-white border border-slate-200 rounded-lg pointer-events-none">
                                                    <div class="flex items-center gap-2">
                                                        <svg width="20" height="20" viewBox="0 0 28 28" class="shrink-0">
                                                            <circle cx="14" cy="14" r="12" fill="white" stroke="#CBD5E1" stroke-width="1.5" />
                                                            <path x-show="item.status === 'P'" d="M 14 14 L 14 2 A 12 12 0 0 1 26 14 Z" fill="#0F172A" style="display: none;" />
                                                            <path x-show="item.status === 'D'" d="M 14 14 L 14 26 A 12 12 0 0 0 14 2 Z" fill="#0F172A" style="display: none;" />
                                                            <path x-show="item.status === 'C'" d="M 14 14 L 14 2 A 12 12 0 1 1 2 14 Z" fill="#0F172A" style="display: none;" />
                                                            <path x-show="item.status === 'A'" d="M 14 2 A 12 12 0 1 1 13.999 2 Z" fill="#0F172A" style="display: none;" />
                                                            <line x1="14" y1="2" x2="14" y2="26" :stroke="item.status === 'A' ? 'white' : '#CBD5E1'" stroke-width="1.5" />
                                                            <line x1="2" y1="14" x2="26" y2="14" :stroke="item.status === 'A' ? 'white' : '#CBD5E1'" stroke-width="1.5" />
                                                            <circle cx="14" cy="14" r="12" fill="none" stroke="#CBD5E1" stroke-width="1.5" />
                                                        </svg>
                                                        <span class="text-xs font-bold text-slate-700" x-text="item.status === 'P' ? 'Plan' : item.status === 'D' ? 'Do' : item.status === 'C' ? 'Check' : item.status === 'A' ? 'Action' : '-'"></span>
                                                    </div>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </div>
                                                <!-- Native Select -->
                                                <select x-model="item.status" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer appearance-none">
                                                    <option value="">-</option>
                                                    <option value="P">Plan</option>
                                                    <option value="D">Do</option>
                                                    <option value="C">Check</option>
                                                    <option value="A">Action</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <button @click="addDampak()" class="text-xs font-bold text-purple-600 bg-purple-50 px-4 py-2 rounded-xl hover:bg-purple-100 transition-all">+ Tambah Item</button>
                        </div>
                        </fieldset>
                        
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">PIC Langkah Perbaikan (Seksi Utama)</label>
                            <select x-model="form.pic_seksi" :disabled="!canEditBasicInfo" :class="!canEditBasicInfo ? 'opacity-90 pointer-events-none bg-slate-100' : ''" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-purple-500 appearance-none cursor-pointer">
                                <option value="">-- Pilih Seksi Utama --</option>
                                <template x-for="s in seksiList" :key="s">
                                    <option :value="s" x-text="s"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    {{-- Actions Permanent --}}
                    <div>
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                            <div class="w-1.5 h-6 bg-pink-600 rounded-full"></div>
                            <h2 class="text-lg font-black text-slate-800">Corrective & Preventive Action (Permanent)</h2>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white" :class="!(canEditSeksiSection || canEditVerifSection) ? 'opacity-80 pointer-events-none' : ''">
                            <table class="w-full text-left border-collapse min-w-[800px]">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200">
                                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest border-r border-slate-200">Langkah Perbaikan</th>
                                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest border-r border-slate-200 text-center">Schedule</th>
                                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest border-r border-slate-200 text-center">Verif 1-3</th>
                                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest border-r border-slate-200 text-center">PDCA</th>
                                        <th class="p-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">PIC</th>
                                        <th class="p-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(act, idx) in (form.actions || [])">
                                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors">
                                            <td class="p-2.5 border-r border-slate-100 align-top min-w-[250px]">
                                                <textarea x-model="act.action" :disabled="!canEditSeksiSection || isActionLocked(act)" rows="3" placeholder="Tulis langkah perbaikan..." class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-pink-500 focus:ring-2 focus:ring-pink-500/10 focus:bg-white transition-all resize-none font-semibold text-slate-700 disabled:opacity-50 disabled:bg-slate-100"></textarea>
                                                
                                                <!-- Bagian Bukti / Evidence -->
                                                <div class="mt-2 space-y-2">
                                                    <input type="text" x-model="act.evidence_remarks" :disabled="!canEditSeksiSection || isActionLocked(act)" placeholder="Keterangan hasil perbaikan (Opsional jika ada foto)..." class="w-full text-[10px] p-2 bg-white border border-slate-200 rounded-lg outline-none focus:border-pink-500 font-semibold text-slate-600 disabled:opacity-50 disabled:bg-slate-100">
                                                    
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <!-- Tombol Upload File -->
                                                        <label x-show="canEditSeksiSection && !isActionLocked(act)" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-[10px] font-bold transition-all border border-slate-200 shrink-0">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            Upload Bukti
                                                            <input type="file" accept="image/*" class="hidden" @change="handleEvidenceUpload($event, idx)">
                                                        </label>

                                                        <!-- Loading Indicator Upload -->
                                                        <div x-show="act.uploading" class="flex items-center gap-1 text-[10px] text-pink-600 font-bold" style="display: none;">
                                                            <div class="w-3 h-3 border-2 border-pink-600 border-t-transparent rounded-full animate-spin"></div>
                                                            Loading...
                                                        </div>
                                                        
                                                        <!-- Link Lihat Bukti -->
                                                        <template x-if="act.evidence_file">
                                                            <a :href="act.evidence_file" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-pink-600 bg-pink-50 hover:bg-pink-100 px-3 py-1.5 rounded-lg transition-all border border-pink-200 shrink-0">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                Lihat Bukti Lampiran
                                                            </a>
                                                        </template>
                                                        
                                                        <template x-if="canEditSeksiSection && !isActionLocked(act) && act.evidence_file">
                                                            <button type="button" @click="act.evidence_file = null" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50" title="Hapus Bukti">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2.5 border-r border-slate-100 align-top">
                                                <input type="date" x-model="act.schedule" :disabled="!canEditSeksiSection || isActionLocked(act)" class="w-full min-w-[110px] text-[10px] p-2 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white font-bold text-slate-600 transition-colors disabled:opacity-50 disabled:bg-slate-100">
                                            </td>
                                            <td class="p-2.5 border-r border-slate-100 align-top min-w-[200px]">
                                                <div class="flex flex-col gap-1.5">
                                                    <div class="flex flex-col gap-1">
                                                        <div class="flex items-center gap-1">
                                                            <span class="text-[9px] font-black text-slate-400 w-4 text-center shrink-0">I</span>
                                                            <input type="date" x-model="act.tgl_verif_1" :disabled="!canEditVerifSection" class="w-[100px] text-[9px] p-1.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white text-slate-600 transition-colors disabled:opacity-50 disabled:bg-slate-100">
                                                            <select x-model="act.verif_1_status" @change="handleVerifChange(idx, 1)" :disabled="!canEditVerifSection" class="flex-1 text-[9px] p-1.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white text-slate-600 transition-colors font-bold appearance-none cursor-pointer disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed" :class="act.verif_1_status === 'OK' ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : act.verif_1_status === 'NG' ? 'text-red-600 bg-red-50 border-red-200' : ''">
                                                                <option value="">-</option>
                                                                <option value="OK">OK</option>
                                                                <option value="NG">NG</option>
                                                            </select>
                                                        </div>
                                                        <textarea x-model="act.verif_1_remarks" x-show="act.verif_1_status === 'NG'" :disabled="!canEditVerifSection" placeholder="Catatan NG Verif 1..." class="w-full text-[9px] p-1.5 bg-red-50 border border-red-200 rounded-lg outline-none focus:border-red-500 text-red-700 placeholder-red-400 transition-colors disabled:opacity-50 ml-5 w-[calc(100%-1.25rem)] resize-none" rows="2" style="display: none;"></textarea>
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <div class="flex items-center gap-1">
                                                            <span class="text-[9px] font-black text-slate-400 w-4 text-center shrink-0">II</span>
                                                            <input type="date" x-model="act.tgl_verif_2" :disabled="!canEditVerifSection" class="w-[100px] text-[9px] p-1.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white text-slate-600 transition-colors disabled:opacity-50 disabled:bg-slate-100">
                                                            <select x-model="act.verif_2_status" @change="handleVerifChange(idx, 2)" :disabled="!canEditVerifSection" class="flex-1 text-[9px] p-1.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white text-slate-600 transition-colors font-bold appearance-none cursor-pointer disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed" :class="act.verif_2_status === 'OK' ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : act.verif_2_status === 'NG' ? 'text-red-600 bg-red-50 border-red-200' : ''">
                                                                <option value="">-</option>
                                                                <option value="OK">OK</option>
                                                                <option value="NG">NG</option>
                                                            </select>
                                                        </div>
                                                        <textarea x-model="act.verif_2_remarks" x-show="act.verif_2_status === 'NG'" :disabled="!canEditVerifSection" placeholder="Catatan NG Verif 2..." class="w-full text-[9px] p-1.5 bg-red-50 border border-red-200 rounded-lg outline-none focus:border-red-500 text-red-700 placeholder-red-400 transition-colors disabled:opacity-50 ml-5 w-[calc(100%-1.25rem)] resize-none" rows="2" style="display: none;"></textarea>
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <div class="flex items-center gap-1">
                                                            <span class="text-[9px] font-black text-slate-400 w-4 text-center shrink-0">III</span>
                                                            <input type="date" x-model="act.tgl_verif_3" :disabled="!canEditVerifSection" class="w-[100px] text-[9px] p-1.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white text-slate-600 transition-colors disabled:opacity-50 disabled:bg-slate-100">
                                                            <select x-model="act.verif_3_status" @change="handleVerifChange(idx, 3)" :disabled="!canEditVerifSection" class="flex-1 text-[9px] p-1.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white text-slate-600 transition-colors font-bold appearance-none cursor-pointer disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed" :class="act.verif_3_status === 'OK' ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : act.verif_3_status === 'NG' ? 'text-red-600 bg-red-50 border-red-200' : ''">
                                                                <option value="">-</option>
                                                                <option value="OK">OK</option>
                                                                <option value="NG">NG</option>
                                                            </select>
                                                        </div>
                                                        <textarea x-model="act.verif_3_remarks" x-show="act.verif_3_status === 'NG'" :disabled="!canEditVerifSection" placeholder="Catatan NG Verif 3..." class="w-full text-[9px] p-1.5 bg-red-50 border border-red-200 rounded-lg outline-none focus:border-red-500 text-red-700 placeholder-red-400 transition-colors disabled:opacity-50 ml-5 w-[calc(100%-1.25rem)] resize-none" rows="2" style="display: none;"></textarea>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2 border-r border-slate-100 text-center">
                                                <div class="relative w-32 mx-auto h-[38px]">
                                                    <!-- Visual Layer (Behind the select) -->
                                                    <div class="absolute inset-0 flex items-center justify-between px-3 bg-white border border-slate-200 rounded-lg pointer-events-none">
                                                        <div class="flex items-center gap-2">
                                                            <svg width="20" height="20" viewBox="0 0 28 28" class="shrink-0">
                                                                <circle cx="14" cy="14" r="12" fill="white" stroke="#CBD5E1" stroke-width="1.5" />
                                                                <path x-show="act.pdca === 'P'" d="M 14 14 L 14 2 A 12 12 0 0 1 26 14 Z" fill="#0F172A" style="display: none;" />
                                                                <path x-show="act.pdca === 'D'" d="M 14 14 L 14 26 A 12 12 0 0 0 14 2 Z" fill="#0F172A" style="display: none;" />
                                                                <path x-show="act.pdca === 'C'" d="M 14 14 L 14 2 A 12 12 0 1 1 2 14 Z" fill="#0F172A" style="display: none;" />
                                                                <path x-show="act.pdca === 'A'" d="M 14 2 A 12 12 0 1 1 13.999 2 Z" fill="#0F172A" style="display: none;" />
                                                                <line x1="14" y1="2" x2="14" y2="26" :stroke="act.pdca === 'A' ? 'white' : '#CBD5E1'" stroke-width="1.5" />
                                                                <line x1="2" y1="14" x2="26" y2="14" :stroke="act.pdca === 'A' ? 'white' : '#CBD5E1'" stroke-width="1.5" />
                                                                <circle cx="14" cy="14" r="12" fill="none" stroke="#CBD5E1" stroke-width="1.5" />
                                                            </svg>
                                                            <span class="text-xs font-bold text-slate-700" x-text="act.pdca === 'P' ? 'Plan' : act.pdca === 'D' ? 'Do' : act.pdca === 'C' ? 'Check' : act.pdca === 'A' ? 'Action' : '-'"></span>
                                                        </div>
                                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                    </div>
                                                    
                                                    <!-- Native Select (Invisible but fully clickable) -->
                                                    <select x-model="act.pdca" :disabled="!(canEditSeksiSection || canEditVerifSection) || isActionLocked(act)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer appearance-none disabled:cursor-not-allowed">
                                                        <option value="">-</option>
                                                        <option value="P">Plan</option>
                                                        <option value="D">Do</option>
                                                        <template x-if="canEditVerifSection || act.pdca === 'C' || act.pdca === 'A'">
                                                            <option value="C">Check</option>
                                                        </template>
                                                        <template x-if="canEditVerifSection || act.pdca === 'A'">
                                                            <option value="A">Action</option>
                                                        </template>
                                                    </select>
                                                </div>
                                                
                                                <!-- Button Minta Verifikasi QA -->
                                                <button x-show="canEditSeksiSection && !isActionLocked(act) && (act.pdca === 'P' || act.pdca === 'D')" 
                                                        @click.prevent="requestQaVerif(act)"
                                                        class="mt-2 text-[9px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-200 shadow-sm whitespace-nowrap">
                                                    Minta Verif QA
                                                </button>
                                            </td>
                                            <td class="p-2.5 border-r border-slate-100 align-top">
                                                <input type="text" x-model="act.pic" :disabled="!canEditSeksiSection || isActionLocked(act)" placeholder="Ketik PIC..." class="w-full min-w-[80px] text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-pink-500 focus:bg-white font-bold text-slate-700 text-center transition-colors disabled:opacity-50 disabled:bg-slate-100">
                                            </td>
                                            <td class="p-2.5 text-center align-top">
                                                <template x-if="(form.actions || []).length > 1">
                                                    <button @click="removeAction(idx)" class="text-red-400 hover:text-red-600">✕</button>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <button @click="addAction()" x-show="canEditSeksiSection" class="mt-4 text-xs font-bold text-pink-600 bg-pink-50 px-4 py-2 rounded-xl hover:bg-pink-100 transition-all">+ Tambah Baris Action</button>
                    </div>
                    
                    {{-- A3 Report Section (3-Strike Rule) --}}
                    <div x-show="form.is_a3_required" x-transition class="mt-8 bg-red-50 border-2 border-red-200 rounded-2xl p-6 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-10">
                            <svg class="w-32 h-32 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-red-800">A3 Report Diperlukan!</h2>
                                    <p class="text-xs text-red-600 font-medium">Telah terjadi kegagalan verifikasi sebanyak 3 kali berturut-turut. <span class="font-black underline decoration-red-400">PART DITAHAN (TIDAK BISA DIPRODUKSI)</span> sampai A3 diverifikasi QA.</p>
                                </div>
                            </div>
                            
                            <fieldset :class="!canEditSeksiSection ? 'opacity-80' : ''">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-red-700 uppercase tracking-widest mb-2">Due Date A3 Report</label>
                                        <input type="date" x-model="form.a3_due_date" :readonly="!canEditSeksiSection" class="w-full px-4 py-3 bg-white border border-red-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-red-700 uppercase tracking-widest mb-2">Link Dokumen A3 / Keterangan</label>
                                        <div class="flex gap-2">
                                            <input type="text" x-model="form.a3_document" :readonly="!canEditSeksiSection" placeholder="Tempel link Google Drive atau nomor dokumen..." class="w-full px-4 py-3 bg-white border border-red-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all">
                                            <template x-if="form.a3_document && form.a3_document.startsWith('http')">
                                                <a :href="form.a3_document" target="_blank" class="px-4 py-3 bg-red-100 text-red-600 rounded-xl hover:bg-red-200 transition-colors flex items-center justify-center shrink-0 pointer-events-auto" title="Buka Link Dokumen">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            {{-- QA Verification UI for A3 Report --}}
                            <template x-if="form.a3_document">
                                <div class="mt-6 pt-6 border-t border-red-200">
                                    <label class="block text-[10px] font-black text-red-700 uppercase tracking-widest mb-3">Verifikasi QA (A3 Report)</label>
                                    
                                    <template x-if="canEditVerifSection">
                                        <div class="flex gap-3">
                                            <button @click.prevent="verifyA3('OK')" class="px-5 py-2.5 bg-emerald-500 text-white rounded-xl text-xs font-bold hover:bg-emerald-600 transition-colors shadow-sm shadow-emerald-500/20">✔ OK - Lanjut Produksi</button>
                                            <button @click.prevent="verifyA3('NG')" class="px-5 py-2.5 bg-rose-500 text-white rounded-xl text-xs font-bold hover:bg-rose-600 transition-colors shadow-sm shadow-rose-500/20">✖ NG - Tolak & Revisi A3</button>
                                        </div>
                                    </template>

                                    <template x-if="!canEditVerifSection">
                                        <div class="px-4 py-3 bg-white border border-red-200 rounded-xl text-sm font-bold text-slate-500">
                                            Menunggu verifikasi QA...
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- STEP 5: Approval --}}
                <div x-show="step === 5" x-transition.opacity.duration.300ms class="space-y-6" style="display: none;">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-6">
                        <div class="w-1.5 h-6 bg-slate-800 rounded-full"></div>
                        <h2 class="text-lg font-black text-slate-800">Finalisasi & Approval</h2>
                    </div>

                    {{-- Paper Form Preview --}}
                    @include('qpr.paper-preview')

                    {{-- Assign Foreman & Tambah Seksi --}}
                    <template x-if="userRole === 'Operator' || userRole === 'Admin'">
                        <div>
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm mt-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-bold text-slate-800">Tugaskan ke GL / Foreman</h3>
                                        <p class="text-[10px] text-slate-500">Pilih GL/Foreman yang akan memverifikasi dan menyetujui laporan ini.</p>
                                    </div>
                                </div>
                                
                                <select x-model="form.assigned_foreman_id" @change="updateForemanName()" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-blue-500 appearance-none cursor-pointer">
                                    <option value="">-- Pilih GL / Foreman --</option>
                                    <template x-for="u in availableForemen" :key="u.id">
                                        <option :value="u.id" x-text="u.nama"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm mt-6">
                                <h3 class="text-xs font-bold text-slate-800 mb-1">Tambah Seksi Terkait</h3>
                                <p class="text-[10px] text-slate-400 mb-4">Seksi yang ditambah akan muncul di tabel TTD</p>
                                
                                <div class="flex gap-3">
                                    <select x-model="new_seksi_signer" class="flex-1 bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold outline-none focus:border-slate-400">
                                        <option value="">-- Pilih Seksi --</option>
                                        <template x-for="s in seksiOptions.filter(opt => opt !== form.pic_seksi)" :key="s">
                                            <option :value="s" x-text="s"></option>
                                        </template>
                                    </select>
                                    <button @click="addSeksiSigner()" class="px-6 py-2 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-900 transition-all">+ Tambah</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- TTD Section and Modal have been moved to paper-preview.blade.php --}}

            </div>

            {{-- Footer Actions --}}
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200">
                <button @click="prevStep()" x-show="step > 1" class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
                    Kembali
                </button>
                <div x-show="step === 1" class="w-px"></div> {{-- Placeholder --}}
                
                <div class="flex gap-3">
                    <button @click="submit('Draft')" x-show="step === availableSteps.length" class="px-6 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm font-black hover:bg-slate-200 transition-all shadow-sm" :disabled="loading">
                        Simpan Draft
                    </button>
                    
                    <button @click="submit('OPEN')" x-show="step === availableSteps.length && (userRole === 'Operator' || userRole === 'Admin')" class="px-8 py-2.5 bg-red-600 text-white rounded-xl text-sm font-black hover:bg-red-700 transition-all shadow-lg shadow-red-600/20 flex items-center gap-2" :disabled="loading">
                        <template x-if="loading"><div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div></template>
                        <span x-text="loading ? 'Menyimpan...' : 'Simpan & OPEN'"></span>
                    </button>
                    
                    <button @click="submit('Approve')" x-show="step === availableSteps.length && userRole !== 'Operator'" class="px-8 py-2.5 bg-red-600 text-white rounded-xl text-sm font-black hover:bg-red-700 transition-all shadow-lg shadow-red-600/20 flex items-center gap-2" :disabled="loading">
                        <template x-if="loading"><div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div></template>
                        <span x-text="submitButtonText"></span>
                    </button>

                    <button @click="nextStep()" x-show="step < availableSteps.length" class="px-8 py-2.5 bg-red-600 text-white rounded-xl text-sm font-black hover:bg-red-700 transition-all shadow-lg shadow-red-600/20">
                        Lanjut
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
