
(function() {
    const APP_URL = document.querySelector('meta[name="app-url"]')?.getAttribute('content') || '';

    // --- Helper for API Calls ---
    async function apiFetch(path, options = {}) {
        try {
            const res = await window.axios({
                url: path,
                method: options.method || 'GET',
                data: options.body ? JSON.parse(options.body) : null,
                headers: options.headers || {},
                withCredentials: true,
                ...options
            });
            return res.data;
        } catch (err) {
            if (err.response?.status === 401) {
                window.location.href = APP_URL + '/login';
                throw new Error('Unauthenticated');
            }
            throw new Error(err.response?.data?.message || err.message);
        }
    }

    // --- Helper for Table Columns ---
    // Fallback: classic fixed-checkpoint cols (backward compat)
    function buildCols(total) {
        if (!total || total <= 0) return [];
        if (total <= 9) return Array.from({ length: total }, (_, i) => i + 1);
        const res = [1, 2, 3];
        [10, 20, 40, 60, 80, 100].forEach(v => { if (v <= total) res.push(v); });
        for (let v = 125; v <= 200; v += 25) { if (v <= total) res.push(v); }
        for (let v = 250; v < total; v += 50) res.push(v);
        res.push(total);
        return [...new Set(res)].sort((a, b) => a - b);
    }

    // Metode A (banyak part, CT kecil): Int1=round(CT dim/TT), Int2=floor(CT tanpa/TT)
    // Metode B (part seperti GT-5154, CT besar): pembagi=round(TT+CTdim/60), Int=round(CT/pembagi)
    function calcSamplingIntervals(tactTime, ctDimensi, ctTanpaDimensi, mode = 'auto') {
        const tt = parseFloat(tactTime) || 0;
        const ctD = parseFloat(ctDimensi) || 0;
        const ctT = parseFloat(ctTanpaDimensi) || 0;
        if (!tt || !ctD || !ctT) return null;

        const direct = () => ({
            mode: 'direct',
            modeLabel: 'Langsung CT/TT',
            divisor: tt,
            interval1: Math.max(1, Math.round(ctD / tt)),
            interval2: Math.max(1, Math.floor(ctT / tt)),
        });

        const pembagi = () => {
            const div = Math.max(1, Math.round(tt + ctD / 60));
            return {
                mode: 'pembagi',
                modeLabel: 'Pembagi round(TT+CTmin)',
                divisor: div,
                interval1: Math.max(1, Math.round(ctD / div)),
                interval2: Math.max(1, Math.round(ctT / div)),
            };
        };

        const resolved = mode === 'auto'
            ? (ctD >= 270 ? 'pembagi' : 'direct')
            : mode;

        return resolved === 'pembagi' ? pembagi() : direct();
    }

    function buildColsFormula(total, tactTime, ctDimensi, ctTanpaDimensi, mode = 'auto') {
        const n = parseInt(total) || 0;

        if (n <= 0) return [];
        const calc = calcSamplingIntervals(tactTime, ctDimensi, ctTanpaDimensi, mode);
        if (!calc) return buildCols(n);

        const { interval1, interval2 } = calc;
        const cols = [1];
        let next = 1 + interval1;
        while (next < n) {
            cols.push(next);
            next += interval2;
        }
        if (cols[cols.length - 1] !== n) cols.push(n);
        return [...new Set(cols)].sort((a, b) => a - b);
    }


    window.liForm = function(config = {}) {
        return {
            // ==========================================
            // STATE & INITIALIZATION
            // ==========================================
            editId: config.editId || null,
            role: config.role || 'Guest',
            userName: config.userName || '',
            userId: config.userId || '',
            saving: false, savingDraft: false, loadingData: !!config.editId,
            activeTab: 'main',
            toast: null,
            readyToSave: false, // Flag untuk mencegah overwrite saat baru load
            showConfirmResolve: false, keyToResolve: null,
            showConfirmMain: false, confirmTitle: '', confirmMessage: '', confirmAction: null, confirmBtnText: 'Ya, Lanjutkan', confirmBtnColor: 'bg-red-600',
            showQprPrompt: false, pendingQprId: null,
            
            // Intercom / Pager Call State
            intercomCall: null,
            incomingCall: null,
            showIntercomModal: false,
            showIncomingModal: false,
            selectedPresetMsg: 'Saya meluncur ke jalur sekarang!',
            presetMessages: [
                'Saya meluncur ke jalur sekarang!',
                'Tunggu 5 menit, sedang ada inspeksi lain',
                'Sedang tanggung, mohon ditunggu',
                'Segera ke sana bersama Foreman'
            ],
            // State check-in fisik GL/Foreman di tablet operator
            glArrivedData: null,  // Data dari API setelah status 'arrived'
            
            // Dimensi Settings Modal State
            showDimModal: false,
            targetDimIdx: 0,
            tempDim: { item: '', nominal: '', plus: '', minus: '', method: '' },
            
            // Header Info
            itemCheckRevisionChecked: false,
            jobNo: '', partName: '', partNo: '', partType: '',
            specMat: '', typePallet: '', lokasi: '', prosesRoute: '',
            tanggal: new Date().toISOString().slice(0, 10),
            shift: '', 
            qgName: (['leader', 'admin'].includes((config.role||'').toLowerCase())) ? config.userName : '',
            totalPcs: 0, repair: 0, reject: 0, catatan: '', status: 'draft',
            // Sampling formula parameters (loaded from LI template per part)
            tactTime: 0,        // Tact Time per pcs dalam detik
            ctDimensi: 0,       // Cycle Time check dengan dimensi (detik)
            ctTanpaDimensi: 0,  // Cycle Time check tanpa dimensi (detik)
            samplingFormulaMode: 'auto', // auto | direct | pembagi
            _leaderEditBaseline: null, // snapshot bagian atas (standard) untuk autofill revision record Leader
            _leaderRevisionQueue: {}, // antrian perubahan sebelum dijadikan 1 baris revisi
            _leaderRevisionFlushTimer: null,
            sketchUrl: null,
            customSamplingCols: [],
            
            // ========== SVG SKETCH EDITOR STATE ==========
            sketchSource: 'upload',
            showSketchEditor: false,
            showSketchChoiceModal: false,
            svgShapes: [],
            svgSelected: null,
            svgTool: 'select',
            svgColor: '#DC2626',
            svgDrawing: null,
            svgDragState: null,
            svgResizeState: null,
            svgLineDragEndpoint: null,
            svgLineRotateState: null,
            svgConnState: null,
            svgBgImage: null,
            svgEditingText: null,
            svgTextVal: '',
            svgHoverPort: null,
            svgZoneCount: 0,
            svgCanvasW: 900,
            svgCanvasH: 580,
            svgToolConfig: [
                { id: 'select', icon: 'Ã¢Â¬â€ ', label: 'Pilih' },
                { id: 'rect',   icon: 'Ã¢Â¬Å“', label: 'Kotak' },
                { id: 'circle', icon: 'Ã¢Â­â€¢', label: 'Lingkaran' },
                { id: 'zone',   icon: 'Ã°Å¸â€Â²', label: 'Zona' },
                { id: 'text',   icon: 'T',  label: 'Teks' },
                { id: 'line',   icon: 'Ã¢â€¢Â±',  label: 'Garis' },
                { id: 'arrow',  icon: 'Ã¢â€ â€™',  label: 'Panah' },
                { id: 'delete', icon: 'Ã°Å¸—â€˜', label: 'Hapus' },
            ],
            
            // Item Check Data (Dimensi dengan Toleransi)
            dimStd: Array.from({ length: 7 }, () => ({ item: '', nominal: '', plus: '', minus: '', method: '' })),
            appItems: [
                'Jumlah Hole',
                'Tidak Pecah, Tidak Neck, Tidak Karat',
                'Tidak Pecok, Tidak Benjol, Tidak Gelombang, Tidak Sockline',
                'Tidak Baret, Tidak Burry, Tidak Keriput, Tidak Mencuat',
                'Tidak Penyok, Flange Tidak Miring',
                '',
                '',
                '',
                ''
            ],
            
            // Appearance Standard Setting Modal
            showAppStandardModal: false,
            appStandardTargetRi: null,
            appStandardPresets: [
                'Tidak Pecah',
                'Tidak Neck',
                'Tidak Karat',
                'Tidak Pecok',
                'Tidak Benjol',
                'Tidak Gelombang',
                'Tidak Sockline',
                'Tidak Baret',
                'Tidak Burry',
                'Tidak Keriput',
                'Tidak Mencuat',
                'Tidak Penyok',
                'Flange Tidak Miring'
            ],
            appStandardSelected: [],
            appStandardCustom: '',
            appStandardMarking: '',
            
            dimData: {}, appData: {}, judgement: '', holeStandard: 0,
            
            // NG & Hole Modal State
            showNgModal: false,
            ngTargetRow: null,
            ngTargetCol: null,
            ngCurrentProcesses: { 'OP 10': false, 'OP 20': false, 'OP 30': false, 'OP 40': false, 'OP 50': false, 'OP 60': false },
            ngCurrentAreas: { 1: false, 2: false, 3: false, 4: false, 5: false, 6: false, 7: false, 8: false, 9: false, 10: false, 11: false, 12: false, 13: false, 14: false, 15: false, 16: false },
            ngCurrentProblems: { BARET: false, BURY: false, PECOK: false, PENYOK: false, LAINNYA: false },
            ngCurrentCauses: { MTR: false, DIE: false, OPR: false, HDL: false, MSN: false },
            ngCurrentNote: '',
            ngDetails: {},
            ngCurrentDisposition: '',

            // Dimension Input Modal (Operator)
            showDimInputModal: false,
            dimInputTargetRi: null,
            dimInputTargetCol: null,
            dimInputTemp: '',
            
            // Bundle Data
            bundleChecks: [], bundleTindakan: '',
            bundleFmName: '', bundleGlName: '',
            bundleGlSig: null, bundleFmSig: null,
            // Bundle Check Prompt (di tab Standard)
            wantsBundleCheck: null,        // null=belum pilih, true=mau, false=skip
            showBundleCheckConfirm: false,  // modal konfirmasi double-verify

            // Signature & UI
            glSig: null, fmSig: null, prepSig: null, bundlePrepSig: null,
            glParaf: null, fmParaf: null,
            activePad: null,
            cols: [],
            operatorList: [],
            glUsers: [], fmUsers: [], operatorUsers: [],
            assignedGlId: '', assignedForemanId: '', assignedOperatorId: '',
            catatanRevisi: '', fieldRevisions: {}, pendingFieldRevisions: {},
            revRecords: [],
            operatorClaimedAt: null,
            showSearchModal: false, searchQuery: '', searchResults: [], searchType: 'history',
            lastSelected: { jobNo: '', partNo: '' },
            hasAutoSearched: { jobNo: false, partNo: false },
            quickRevPresets: [
                { key: 'sketch', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`, label: 'Sketch' },
                { key: 'jobNo', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`, label: 'Job No' },
                { key: 'partNo', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>`, label: 'Part No' },
                { key: 'partName', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`, label: 'Part Name' },
                { key: 'specMat', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.183.319l-3.08 1.925a2 2 0 00-.334 2.128c.44.88 1.48 1.22 2.36.78l3.18-1.59a6 6 0 013.86-.517l.318.158a6 6 0 003.86-.517l2.387.477a2 2 0 001.022.547l3.08 1.925a2 2 0 002.36-.78 2 2 0 00-.334-2.128l-3.08-1.925z"/></svg>`, label: 'Spec Mat' },
                { key: 'pallet', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>`, label: 'Pallet' },
                { key: 'lokasi', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`, label: 'Lokasi' },
                { key: 'other', icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>`, label: 'Lainnya' }
            ],

            // ==========================================
            // COMPUTED PROPERTIES
            // ==========================================
            get isLeader() { return ['leader', 'admin'].includes((this.role||'').toLowerCase()); },
            get isGroupLeader() { return ['group leader', 'admin'].includes((this.role||'').toLowerCase()); },
            get isForeman() { return ['foreman', 'admin'].includes((this.role||'').toLowerCase()); },
            get isSupervisor() { return ['supervisor', 'admin'].includes((this.role||'').toLowerCase()); },
            get isOperator() { return ['operator', 'admin'].includes((this.role||'').toLowerCase()); },
            get isQASectionFixed() { return !['draft', 'revision'].includes(this.status); },
            /** Leader (Totok) boleh edit ulang seluruh bagian atas (standard) setelah operator submit */
            get canLeaderEditStandard() {
                if (!this.isLeader) return false;
                return [
                    'draft', 'revision',
                    'waiting_foreman', 'waiting_supervisor',
                    'ready_for_qc', 'locked', 'waiting_qc_approval', 'finished'
                ].includes(this.status);
            },
            get canLeaderEditSampling() { return this.canLeaderEditStandard; },
            /** Setelah operator submit Ã¢â€ â€™ setiap edit bagian atas wajib catat Revision Record */
            get shouldLogLeaderRevision() {
                return this.isLeader && [
                    'ready_for_qc', 'locked', 'waiting_qc_approval', 'finished',
                ].includes(this.status);
            },
            get shouldLogLeaderSamplingRevision() { return this.shouldLogLeaderRevision; },
            /** Boleh edit field di tab Standard (header, sketch, dimensi, appearance, sampling) */
            get canEditStandardSection() {
                if (this.isLeader) return this.canLeaderEditStandard;
                if (this.isSupervisor) return !this.isQASectionFixed;
                return !this.isQASectionFixed;
            },
            get canEditSampling() { return this.canEditStandardSection; },
            get isQCSectionOpen() { return ['admin', 'operator', 'group leader', 'foreman'].includes((this.role||'').toLowerCase()) && ['locked', 'approved', 'finished', 'ready_for_qc', 'revision', 'draft', 'in_progress'].includes(this.status); },
            /** LI selesai / bukan operator QC Ã¢â€ â€™ modal NG hanya lihat, tidak edit */
            get isNgModalReadOnly() {
                return ['finished', 'approved'].includes(this.status)
                    || !(this.isOperator && this.isQCSectionOpen);
            },
            /** Sel masih NG (tanda ✗) atau jumlah hole tidak sesuai standar */
            isNgCellActive(row, col) {
                if (this.getAppVal(row, col) === 'ng') return true;
                const label = (this.appItems[row] || '').toUpperCase();
                if (!label.includes('JUMLAH HOLE')) return false;
                const standard = parseInt(this.holeStandard) || 0;
                const entered = parseInt(this.appData[`${row}_${col}`]);
                return !isNaN(entered) && entered !== standard;
            },

            /** Buang ng_details yang nyangkut setelah edit/reload (sel sudah OK) */
            pruneStaleNgDetails() {
                let changed = false;
                Object.keys(this.ngDetails || {}).forEach((key) => {
                    const parts = key.split('_');
                    const row = parseInt(parts[0], 10);
                    const col = parseInt(parts[1], 10);
                    if (Number.isNaN(row) || Number.isNaN(col) || !this.isNgCellActive(row, col)) {
                        delete this.ngDetails[key];
                        changed = true;
                    }
                });
                if (changed) this.updateRepairRejectCounts();
            },

            get ngSummaryList() {
                const list = [];
                Object.keys(this.ngDetails || {}).forEach((key) => {
                    const d = this.ngDetails[key];
                    if (!d || typeof d !== 'object') return;
                    const parts = key.split('_');
                    const row = parseInt(parts[0], 10);
                    const col = parseInt(parts[1], 10);
                    if (Number.isNaN(row) || Number.isNaN(col)) return;
                    if (!this.isNgCellActive(row, col)) return;
                    const hasContent = (d.catatan || '').trim()
                        || (d.problems || []).filter(Boolean).length
                        || (d.causes || []).filter(Boolean).length;
                    if (!hasContent && this.getAppVal(row, col) !== 'ng') return;
                    list.push({
                        key,
                        row,
                        col,
                        appearance: this.appItems[row] || `Baris ${row + 1}`,
                        sampleLabel: `Sample ${col}`,
                        catatan: (d.catatan || '').trim() || '',
                        problems: (d.problems || []).join(', ') || '',
                        causes: (d.causes || []).join(', ') || '',
                        disposisi: d.disposisi ? String(d.disposisi).toUpperCase() : '',
                        proses: Array.isArray(d.proses) && d.proses.length > 0 ? d.proses.join(', ') : (d.proses || ''),
                        areas: Array.isArray(d.areas) && d.areas.length > 0 ? d.areas.join(', ') : '',
                    });
                });
                return list.sort((a, b) => a.row - b.row || a.col - b.col);
            },

            // NG Severity: 'ok' | 'light_ng' | 'critical_ng'
            get getNgSeverity() {
                const judgement = this.getGlobalJudgement ? this.getGlobalJudgement() : 'OK';
                if (judgement !== 'NG') return 'ok';
                const total = parseInt(this.totalPcs) || 0;
                const rejectCount = parseInt(this.reject) || 0;
                const repairCount = parseInt(this.repair) || 0;
                const ngCount = rejectCount + repairCount;
                const ratio = total > 0 ? (ngCount / total) * 100 : 0;
                // Critical jika: ada REJECT > 0 ATAU rasio NG >= 5%
                if (rejectCount > 0 || ratio >= 5) return 'critical_ng';
                return 'light_ng';
            },

            get isFormComplete() {
                if (!this.cols || this.cols.length === 0) return false;
                if (!this.totalPcs || parseInt(this.totalPcs) <= 0) return false;
                return this.cols.every(col => this.isColComplete(col));
            },

            get actionBarLabel() {
                if (this.saving) return 'Menyimpan...';
                if (this.isLeader && ['draft','revision'].includes(this.status)) return 'Kirim ke Foreman';
                if (this.isForeman && this.status === 'waiting_foreman') return 'Konfirmasi Checked';
                if (this.isSupervisor && this.status === 'waiting_supervisor') return 'Approve Final';
                if (this.isOperator && this.isQCSectionOpen) {
                    if (this.bundleFmSig) return 'Selesai & Kunci Dokumen';
                    return 'Selesai & Ajukan Verifikasi';
                }
                if (this.isGroupLeader && this.status === 'waiting_qc_approval') return 'Verifikasi GL';
                if (this.isForeman && this.status === 'waiting_qc_approval') return 'Verifikasi Foreman (Selesai)';
                return 'Simpan';
            },

            get revisiCatatan() {
                if (this.status !== 'revision') return null;
                return (this.catatanRevisi || this.catatan || 'Perlu perbaikan data.').replace('REVISI: ', '').split(' (Oleh')[0];
            },

            get hasUnresolvedRevisions() {
                if (this.status !== 'revision') return false;
                return Object.values(this.fieldRevisions).some(rev => !rev.resolved);
            },

            get bundleChunks() {
                const chunks = [];
                for (let i = 0; i < this.bundleChecks.length; i += 5) chunks.push(this.bundleChecks.slice(i, i + 5));
                return chunks;
            },

            // ==========================================
            // METHODS: INITIALIZATION & USER
            // ==========================================
            async init() {
                this.$watch('totalPcs',        val => { this.cols = this.rebuildCols(); });
                this.$watch('tactTime',         ()  => { this.cols = this.rebuildCols(); });
                this.$watch('ctDimensi',        ()  => { this.cols = this.rebuildCols(); });
                this.$watch('ctTanpaDimensi',   ()  => { this.cols = this.rebuildCols(); });
                this.$watch('samplingFormulaMode', () => { this.cols = this.rebuildCols(); });
                this.$watch('specMat', val => { 
                    // Tidak lagi disync ke appearance karena Spec Material murni dari header saja
                });

                // Local Auto-save logic (Cepat, 2 detik)
                setInterval(() => {
                    if (this.readyToSave && !this.loadingData && !this.saving && !this.savingDraft && this.status !== 'finished') {
                        this.saveDraft();
                    }
                }, 2000); 

                // Cloud Auto-Sync logic (30 detik, silent)
                setInterval(() => {
                    if (this.readyToSave && !this.loadingData && !this.saving && !this.savingDraft && this.status !== 'finished') {
                        this.handleSaveDraft(true);
                    }
                }, 30000);

                this.initInitialBundles();
                this.cols = this.rebuildCols();

                if (!this.editId) {
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('new') === '1') {
                        localStorage.removeItem(`li_form_draft_${this.editId || 'new'}`);
                        // Auto-fill dari parameter notifikasi produksi
                        if (urlParams.has('job_no')) this.jobNo = urlParams.get('job_no');
                        if (urlParams.has('part_name')) this.partName = decodeURIComponent(urlParams.get('part_name'));
                    }
                    else this.restoreDraft();
                    this.readyToSave = true; // Siap simpan untuk data baru
                }

                await this.fetchUsers();

                // GL/Foreman polling dipindah ke notifications.js (global, semua halaman)

                if (this.editId) {
                    await this.loadData();
                    // Jangan panggil restoreDraft() untuk editId yang sudah ada di DB 
                    // agar data asli DB tidak tertimpa draft localstorage yang mungkin kosong/lama
                    this.readyToSave = true; 
                }

                // Sembunyikan skeleton loader global setelah semuanya selesai dimuat
                if (typeof window.hideSkeleton === 'function') {
                    window.hideSkeleton();
                }
            },

            async fetchUsers() {
                try {
                    const res = await apiFetch('/api/users/by-role?role[]=Group Leader&role[]=Foreman&role[]=Operator');
                    const list = Array.isArray(res) ? res : (res.data || []);
                    this.operatorList = list.filter(u => u.role === 'Operator').map(u => ({ id: u.id, nama: u.name }));
                    this.operatorUsers = this.operatorList;
                    this.glUsers = list.filter(u => u.role === 'Group Leader').map(u => ({ id: u.id, nama: u.name, phone: u.phone || '' }));
                    // Hanya tampilkan Azriel dan Dedy Purwanto sebagai Foreman yang bertugas
                    this.fmUsers = list.filter(u => u.role === 'Foreman' && (
                        u.name.toLowerCase().includes('azriel') ||
                        (u.name.toLowerCase().includes('dedy') && u.name.toLowerCase().includes('purwanto'))
                    )).map(u => ({ id: u.id, nama: u.name, phone: u.phone || '' }));
                } catch (e) { console.error(e); }
            },

            // ==========================================
            // METHODS: DRAFT & STORAGE
            // ==========================================
            saveDraft() {
                try {
                    const key = `li_form_draft_${this.editId || 'new'}`;
                    const d = { 
                        jobNo: this.jobNo, partName: this.partName, partNo: this.partNo, partType: this.partType, 
                        specMat: this.specMat, typePallet: this.typePallet, lokasi: this.lokasi, prosesRoute: this.prosesRoute, tanggal: this.tanggal, 
                        shift: this.shift, totalPcs: this.totalPcs, repair: this.repair, reject: this.reject,
                        tactTime: this.tactTime, ctDimensi: this.ctDimensi, ctTanpaDimensi: this.ctTanpaDimensi,
                        samplingFormulaMode: this.samplingFormulaMode,
                        catatan: this.catatan, judgement: this.judgement, 
                        // Gunakan deep copy agar data proxy Alpine tidak korup saat di-stringify
                        dimData: JSON.parse(JSON.stringify(this.dimData)), 
                        appData: JSON.parse(JSON.stringify(this.appData)),
                        ngDetails: JSON.parse(JSON.stringify(this.ngDetails)),
                        // Data sementara di dalam Modal NG
                        ngCurrentProcesses: JSON.parse(JSON.stringify(this.ngCurrentProcesses)),
                        ngCurrentAreas: JSON.parse(JSON.stringify(this.ngCurrentAreas)),
                        ngCurrentProblems: JSON.parse(JSON.stringify(this.ngCurrentProblems)),
                        ngCurrentCauses: JSON.parse(JSON.stringify(this.ngCurrentCauses)), 
                        ngCurrentNote: this.ngCurrentNote,
                        ngTargetRow: this.ngTargetRow, ngTargetCol: this.ngTargetCol,
                        showNgModal: this.showNgModal,
                        dimStd: JSON.parse(JSON.stringify(this.dimStd)), 
                        appItems: JSON.parse(JSON.stringify(this.appItems)),
                        holeStandard: this.holeStandard,
                        bundleChecks: JSON.parse(JSON.stringify(this.bundleChecks)), 
                        bundleTindakan: this.bundleTindakan,
                        prepSig: this.prepSig, glSig: this.glSig, fmSig: this.fmSig, bundlePrepSig: this.bundlePrepSig
                    };
                    localStorage.setItem(key, JSON.stringify(d));
                } catch (e) { console.error('Save Draft Error:', e); }
            },

            // Compute sampling cols using formula if params set, else fallback
            rebuildCols() {
                if (this.customSamplingCols && this.customSamplingCols.length > 0) {
                    const keepers = this.cols.filter(c => typeof c === 'string' && c.toUpperCase().startsWith('KEEPER'));
                    const maxCol = Math.max(...this.customSamplingCols);
                    let baseCols = [...this.customSamplingCols];
                    
                    if (this.totalPcs > 0) {
                        baseCols = baseCols.filter(c => c <= this.totalPcs);
                        if (baseCols.length === 0 || baseCols[baseCols.length - 1] != this.totalPcs) {
                            baseCols.push(parseInt(this.totalPcs));
                        }
                    }
                    
                    const resCols = [...new Set(baseCols)].sort((a, b) => a - b);
                    return [...resCols, ...keepers];
                }

                return buildColsFormula(
                    this.totalPcs,
                    this.tactTime,
                    this.ctDimensi,
                    this.ctTanpaDimensi,
                    this.samplingFormulaMode,
                    this.cols
                );
            },

            get samplingCalc() {
                return calcSamplingIntervals(
                    this.tactTime,
                    this.ctDimensi,
                    this.ctTanpaDimensi,
                    this.samplingFormulaMode
                );
            },

            get samplingPreview() {
                const c = this.samplingCalc;
                if (!c) return 'Isi parameter untuk preview';
                return `${c.modeLabel}: +${c.interval1} lalu +${c.interval2} Ã¢â€ â€™ ${this.cols.length} kolom`;
            },

            restoreDraft() {
                try {
                    const key = `li_form_draft_${this.editId || 'new'}`;
                    const saved = localStorage.getItem(key);
                    if (!saved) return;
                    const d = JSON.parse(saved);
                    Object.keys(d).forEach(k => { if (d[k] !== undefined) this[k] = d[k]; });
                    this.pruneStaleNgDetails();
                    this.cols = this.rebuildCols();
                    this.captureLeaderEditBaseline();
                } catch (e) {}
            },

            // ==========================================
            // METHODS: TEMPLATE MASTER
            // ==========================================
            async loadTemplateByPartNo(isAutoLoad = false) {
                if (!this.partNo || !this.partNo.trim()) {
                    if (!isAutoLoad) this.showToast('error', 'Isi Part No terlebih dahulu!');
                    return;
                }
                this.loadingData = true;
                console.log('[LI DEBUG] loadTemplateByPartNo called, partNo:', JSON.stringify(this.partNo));
                try {
                    const apiUrl = '/api/li-templates/' + encodeURIComponent(this.partNo.trim()) + '?t=' + Date.now();
                    console.log('[LI DEBUG] Calling API:', apiUrl);
                    const res = await apiFetch(apiUrl);
                    console.log('[LI DEBUG] API response:', res);
                    if (res && res.part_no) {
                        this.partName = res.part_name || '';
                        this.partType = res.type || '';
                        this.specMat = res.spec_material || '';
                        this.typePallet = res.type_pallet || '';
                        this.tactTime = parseFloat(res.tact_time) || 0;
                        this.ctDimensi = parseFloat(res.ct_dimensi) || 0;
                        this.ctTanpaDimensi = parseFloat(res.ct_tanpa_dimensi) || 0;
                        this.customSamplingCols = res.sampling_cols || [];

                        // === SKETCH ===
                        if (res.image_path) {
                            var p = String(res.image_path);
                            // strip leading storage/ prefix if present
                            var idx1 = p.indexOf('storage/');
                            if (idx1 !== -1) p = p.substring(idx1 + 'storage/'.length);
                            this.sketchUrl = '/storage/' + p;
                        } else {
                            this.sketchUrl = null;
                        }

                        // === DIMENSI: rebuild array from scratch ===
                        var newDimStd = [];
                        for (var di = 1; di <= 7; di++) {
                            var dItem   = res['dimensi' + di + '_item']   || '';
                            var dMethod = res['dimensi' + di + '_method'] || '';
                            var dNom = '', dPlus = '', dMinus = '';
                            var dFull = res['dimensi' + di] || '';
                            if (dFull) {
                                // Keep only: digits, space, dot, +, -, /
                                // This strips the diameter symbol and mm cleanly
                                var dClean = dFull.replace(/[^0-9 .+\-\/]/g, ' ').replace(/\s+/g, ' ').trim();
                                var dParts = dClean.split('+');
                                if (dParts.length > 1) {
                                    dNom = dParts[0].trim();
                                    var dPM = dParts[1].split('/-');
                                    dPlus  = dPM[0] ? dPM[0].trim() : '';
                                    dMinus = dPM[1] ? dPM[1].trim() : '';
                                } else {
                                    dNom = dClean.trim();
                                }
                            }
                            newDimStd.push({ item: dItem, nominal: dNom, plus: dPlus, minus: dMinus, method: dMethod, _step: 0.01, label: dFull });
                        }
                        console.log('[LI DEBUG] newDimStd:', newDimStd);
                        this.dimStd = newDimStd;

                        // === APPEARANCE: rebuild array from scratch ===
                        var newApp = [];
                        for (var ai = 6; ai <= 14; ai++) {
                            newApp.push(res['appearance' + ai] || '');
                        }
                        console.log('[LI DEBUG] newApp:', newApp);
                        this.appItems = newApp;

                        // Update holeStandard
                        for (var hi = 0; hi < this.appItems.length; hi++) {
                            if (this.appItems[hi] && this.appItems[hi].toUpperCase().indexOf('JUMLAH HOLE') !== -1) {
                                var hm = this.appItems[hi].match(/[0-9]+/);
                                if (hm) this.holeStandard = parseInt(hm[0]);
                                break;
                            }
                        }

                        console.log('[LI DEBUG] sketchUrl:', this.sketchUrl, '| dimStd count:', this.dimStd.length, '| appItems count:', this.appItems.length);
                        this.showToast('success', isAutoLoad ? 'Data Master berhasil dimuat!' : 'Template Master berhasil dimuat!');
                    } else {
                        console.warn('[LI DEBUG] Template not found or invalid response:', res);
                        this.showToast('error', 'Template tidak ditemukan untuk Part No ini.');
                    }
                } catch(e) {
                    console.error('ERROR_LOAD_TEMPLATE:', e);
                    if (e.message && e.message.includes('Template tidak ditemukan')) {
                        console.warn('[LI DEBUG] Template tidak ditemukan. Membuat template baru.');
                        if (!isAutoLoad) {
                            this.showToast('info', 'Template baru akan dibuat untuk Part No ini.');
                        }
                    } else {
                        this.showToast('error', 'Gagal load Template: ' + e.message);
                    }
                } finally {
                    this.loadingData = false;
                }
            },

            
            async saveTemplate() {
                if (!this.partNo || !this.partNo.trim()) {
                    this.showToast('error', 'Isi Part No terlebih dahulu!');
                    return;
                }
                if (!confirm('Simpan/Update template untuk Part No: ' + this.partNo + '?')) return;
                
                this.saving = true;
                try {
                    const payload = {
                        part_no: this.partNo,
                        part_name: this.partName,
                        type: this.partType,
                        spec_material: this.specMat,
                        type_pallet: this.typePallet,
                        image_path: this.sketchUrl,
                        tact_time: this.tactTime,
                        ct_dimensi: this.ctDimensi,
                        ct_tanpa_dimensi: this.ctTanpaDimensi,
                    };
                    for (let i = 1; i <= 7; i++) {
                        payload[`dimensi${i}`] = this.getDimStandardText(i-1);
                        payload[`dimensi${i}_item`] = this.dimStd[i-1]?.item || '';
                        payload[`dimensi${i}_method`] = this.dimStd[i-1]?.method || '';
                    }
                    for (let i = 6; i <= 14; i++) {
                        payload[`appearance${i}`] = this.appItems[i-6];
                    }
                    
                    const res = await apiFetch('/api/li-templates', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    this.showToast('success', res.message || 'Template Master berhasil disimpan!');
                } catch(e) {
                    this.showToast('error', 'Gagal menyimpan template: ' + e.message);
                } finally {
                    this.saving = false;
                }
            },

            // ==========================================
            // METHODS: TABLE & BUNDLE LOGIC
            // ==========================================
            getAppVal(row, col) { return this.appData[`${row}_${col}`] || ''; },
            getAppIcon(v) {
                if (v === 'ok') return '✓';
                if (v === 'ng') return '✗';
                return '•';
            },
            
            setAppVal(row, col, val) {
                if (!this.isQCSectionOpen || !this.isOperator || !this.isColUnlocked(col)) return;
                const current = this.appData[`${row}_${col}`] || '';
                
                if (current === val) {
                    // Jika klik tombol yang sedang aktif -> hapus status (toggle off)
                    this.appData[`${row}_${col}`] = '';
                    if (val === 'ng') {
                        delete this.ngDetails[`${row}_${col}`];
                    }
                } else {
                    // Set ke nilai baru
                    this.appData[`${row}_${col}`] = val;
                    if (val === 'ng') {
                        if (!this.ngDetails[`${row}_${col}`]) {
                            this.ngDetails[`${row}_${col}`] = {
                                proses: [],
                                areas: [],
                                problems: this.getNgProblemsList(row).filter(p => p !== 'LAINNYA'),
                                causes: [],
                                catatan: '',
                                disposisi: 'repair',
                                jam: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                            };
                        }
                    } else {
                        // Jika diubah ke OK, hapus detail NG sebelumnya
                        delete this.ngDetails[`${row}_${col}`];
                    }
                }
                this.pruneStaleNgDetails();
                this.updateRepairRejectCounts();
            },
            
            cycleApp(row, col) {
                // Pertahankan untuk backward compatibility jika diperlukan
                if (!this.isQCSectionOpen || !this.isOperator) return;
                const v = this.appData[`${row}_${col}`] || '';
                const map = { '': 'ok', 'ok': 'ng', 'ng': '' };
                const nextVal = map[v];
                this.appData[`${row}_${col}`] = nextVal;
                
                if (nextVal === 'ng') {
                    if (!this.ngDetails[`${row}_${col}`]) {
                        this.ngDetails[`${row}_${col}`] = {
                            proses: [],
                            areas: [],
                            problems: this.getNgProblemsList(row).filter(p => p !== 'LAINNYA'),
                            causes: [],
                            catatan: '',
                            disposisi: 'repair',
                            jam: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                        };
                    }
                } else {
                    if (this.ngDetails[`${row}_${col}`]) {
                        delete this.ngDetails[`${row}_${col}`];
                    }
                }
                this.pruneStaleNgDetails();
                this.updateRepairRejectCounts();
            },

             getNgProblemsList(row) {
                 const text = this.appItems[row] || '';
                 let list = [];
                 
                 if (text.toUpperCase().includes('HOLE')) {
                     list.push('JUMLAH HOLE TIDAK SESUAI');
                 }
                 
                 if (text.toUpperCase().includes('MARKING')) {
                     list.push('MARKING TIDAK JELAS');
                 }
                 
                 const possibleProblems = [
                     'PECAH', 'NECK', 'KARAT', 'PECOK', 'BENJOL', 'GELOMBANG', 'SOCKLINE', 
                     'BARET', 'BURRY', 'KERIPUT', 'MENCUAT', 'PENYOK', 'FLANGE MIRING'
                 ];
                 
                 const upperText = text.toUpperCase();
                 possibleProblems.forEach(p => {
                     // Check if the exact string exists, or variants
                     if (
                         upperText.includes(p) || 
                         (p === 'BURRY' && upperText.includes('BURR')) || 
                         (p === 'NECK' && upperText.includes('T/NECK')) ||
                         (p === 'FLANGE MIRING' && upperText.includes('FLANGE'))
                     ) {
                         list.push(p);
                     }
                 });
                 
                 if (list.length === 0) {
                     list = ['NG APPEARANCE'];
                 }
                 
                 return [...list, 'LAINNYA'];
             },

            hasNgNote(row, col) {
                if (!this.isNgCellActive(row, col)) return false;
                const d = this.ngDetails[`${row}_${col}`];
                return !!(d && (d.catatan || '').trim());
            },

            openNgReasonModal(row, col) {
                this.ngTargetRow = row;
                this.ngTargetCol = col;
                const existing = this.ngDetails[`${row}_${col}`] || {};
                const savedProses = existing.proses || [];
                const savedAreas = existing.areas || [];

                // Restore processes
                Object.keys(this.ngCurrentProcesses).forEach(k => {
                    this.ngCurrentProcesses[k] = Array.isArray(savedProses) ? savedProses.includes(k) : savedProses === k;
                });

                // Restore areas
                Object.keys(this.ngCurrentAreas).forEach(k => {
                    this.ngCurrentAreas[k] = savedAreas.includes(parseInt(k));
                });

                this.ngCurrentNote = existing.catatan || '';
                this.ngCurrentDisposition = existing.disposisi || '';
                
                // Reset & Populate Problems based on context
                const problems = {};
                this.getNgProblemsList(row).forEach(p => {
                    problems[p] = (existing.problems || []).includes(p);
                });
                this.ngCurrentProblems = problems;

                // Causes disimpan lowercase di DB, jadi cek dengan lowercase
                Object.keys(this.ngCurrentCauses).forEach(k => this.ngCurrentCauses[k] = (existing.causes || []).includes(k.toLowerCase()));
                this.showNgModal = true;
            },

            getNgDetailsForSave() {
                this.pruneStaleNgDetails();
                const raw = this.ngDetails;
                if (!raw || typeof raw !== 'object') return {};
                const out = {};
                Object.keys(raw).forEach(k => {
                    if (!raw[k] || typeof raw[k] !== 'object') return;
                    const parts = k.split('_');
                    const row = parseInt(parts[0], 10);
                    const col = parseInt(parts[1], 10);
                    if (!Number.isNaN(row) && !Number.isNaN(col) && this.isNgCellActive(row, col)) {
                        out[k] = raw[k];
                    }
                });
                return out;
            },

            async persistNgDetails() {
                if (!this.editId) return;
                const ngPayload = this.getNgDetailsForSave();
                try {
                    await apiFetch(`/api/inspeksi/${this.editId}`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            ng_details: ngPayload,
                            repair: this.repair,
                            reject: this.reject,
                            qg_judgement: this.getGlobalJudgement(),
                        }),
                    });
                } catch (e) {
                    console.warn('persistNgDetails failed', e);
                }
            },

            // Ã¢â€â‚¬Ã¢â€â‚¬ Bundle Check Prompt (dipanggil dari tab Standard) Ã¢â€â‚¬Ã¢â€â‚¬
            requestBundleCheck() {
                this.showBundleCheckConfirm = true;
            },
            confirmBundleCheck() {
                this.wantsBundleCheck = true;
                this.showBundleCheckConfirm = false;
                // Switch tab dulu, lalu scroll setelah render selesai
                this.activeTab = 'bundle';
                setTimeout(() => {
                    document.documentElement.scrollTop = 0;
                    document.body.scrollTop = 0;
                    const main = document.querySelector('main') || document.querySelector('.li-main-scroll');
                    if (main) main.scrollTop = 0;
                }, 120);
            },
            declineBundleCheck() {
                this.wantsBundleCheck = false;
                this.showBundleCheckConfirm = false;
            },


            saveNgReason() {
                if (this.isNgModalReadOnly) {
                    this.showNgModal = false;
                    return;
                }
                const isHole = this.appItems[this.ngTargetRow]?.toUpperCase().includes('HOLE');
                const existing = this.ngDetails[`${this.ngTargetRow}_${this.ngTargetCol}`] || {};
                this.ngDetails[`${this.ngTargetRow}_${this.ngTargetCol}`] = {
                    proses: isHole ? [] : Object.keys(this.ngCurrentProcesses).filter(k => this.ngCurrentProcesses[k]),
                    areas: isHole ? [] : Object.keys(this.ngCurrentAreas).filter(k => this.ngCurrentAreas[k]).map(k => parseInt(k)),
                    problems: isHole ? ['JUMLAH HOLE TIDAK SESUAI'] : Object.keys(this.ngCurrentProblems).filter(k => this.ngCurrentProblems[k]),
                    causes: isHole ? [] : Object.keys(this.ngCurrentCauses).filter(k => this.ngCurrentCauses[k]).map(c => c.toLowerCase()),
                    catatan: this.ngCurrentNote,
                    disposisi: isHole ? '' : this.ngCurrentDisposition,
                    jam: existing.jam || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                };
                this.showNgModal = false;
                this.updateRepairRejectCounts();
                this.persistNgDetails();
            },

            cancelNgReason() {
                const key = `${this.ngTargetRow}_${this.ngTargetCol}`;
                if (!this.ngDetails[key]) {
                    this.appData[key] = '';
                }
                this.showNgModal = false;
            },

            checkHoleCount(col) {
                const holeIdx = this.appItems.findIndex(i => i && i.toUpperCase().includes('JUMLAH HOLE'));
                if (holeIdx === -1) return;
                const standard = parseInt(this.holeStandard) || 0;
                const entered = parseInt(this.appData[`${holeIdx}_${col}`]);
                if (!isNaN(entered) && entered !== standard) {
                    const key = `${holeIdx}_${col}`;
                    if (!this.ngDetails[key]) {
                        this.ngDetails[key] = {
                            proses: '',
                            problems: ['JUMLAH HOLE TIDAK SESUAI'],
                            causes: [],
                            catatan: '',
                            disposisi: '',
                            jam: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                        };
                    }
                } else {
                    const key = `${holeIdx}_${col}`;
                    if (this.ngDetails[key]) {
                        delete this.ngDetails[key];
                    }
                }
                this.pruneStaleNgDetails();
                this.updateRepairRejectCounts();
            },


            updateRepairRejectCounts() {
                let repairCount = 0;
                let rejectCount = 0;
                Object.values(this.ngDetails).forEach(detail => {
                    if (detail && detail.disposisi === 'repair') {
                        repairCount++;
                    } else if (detail && detail.disposisi === 'reject') {
                        rejectCount++;
                    }
                });
                this.repair = repairCount;
                this.reject = rejectCount;
            },

            // =============================================
            // COLUMN PROGRESSIVE UNLOCK LOGIC
            // =============================================

            isColComplete(col) {
                // Cek dimensi - hanya dimensi yang punya standar (ada item-nya)
                for (let ri = 0; ri < 7; ri++) {
                    const hasStandard = this.dimStd[ri] && (this.dimStd[ri].item || this.dimStd[ri].nominal);
                    // Terapkan validasi dimensi ke SEMUA kolom, bukan hanya col <= 3
                    if (hasStandard) {
                        const val = this.dimData[`${ri}_${col}`];
                        if (val === undefined || val === null || val === '') return false;
                    }
                }
                // Cek 9 appearance - hanya yang punya teks item
                for (let ri = 0; ri < 9; ri++) {
                    const app = this.appItems[ri];
                    // Skip SEMUA baris kosong
                    if (!app || app.trim() === '') continue;

                    const appLower = app.toLowerCase();
                    if (appLower.includes('type pallet')) {
                        const valAll = this.appData[ri + '_all'];
                        if (valAll === undefined || valAll === null || valAll === '') return false;
                        continue;
                    }
                    
                    // SKIP Jumlah Hole karena inputnya ada di Bundle Checks, bukan di Item Check table
                    if (appLower.includes('jumlah hole')) {
                        continue;
                    }

                    const val = this.appData[`${ri}_${col}`];
                    if (val === undefined || val === null || val === '') return false;
                }
                return true;
            },

            // Kolom 1 selalu terbuka; kolom N baru terbuka jika kolom N-1 (dalam list cols) sudah complete
            isColUnlocked(col) {
                const index = this.cols.indexOf(col);
                if (index <= 0) return true;
                return this.isColComplete(this.cols[index - 1]);
            },

            // Hint teks untuk operator di dalam input dimensi
            getDimInputHint(ri) {
                const d = this.dimStd[ri];
                if (!d.nominal) return '';
                const p = d.plus  ? `+${d.plus}` : '';
                const m = d.minus ? `-${d.minus}` : '';
                return p || m ? `${p}/${m}` : '';
            },

            getParsedDimStd(ri) {
                const d = this.dimStd[ri];
                if (!d) return { nominal: null, plus: 0, minus: 0 };
                
                let nominal = d.nominal !== '' && d.nominal !== null && d.nominal !== undefined ? parseFloat(d.nominal) : null;
                let plus = d.plus !== '' && d.plus !== null && d.plus !== undefined ? parseFloat(d.plus) : null;
                let minus = d.minus !== '' && d.minus !== null && d.minus !== undefined ? parseFloat(d.minus) : null;
                
                // Jika nominal/toleransi kosong tapi label ada (data lama/master lama), parse secara cerdas!
                if (nominal === null && d.label) {
                    const cleanLabel = d.label.replace(',', '.');
                    // Regex: mencocokkan "Ø0.1 mm +0.03/-0.03" atau "Ø0.1+0.03/-0.03"
                    const regex = /Ø?\s*([0-9.]+)\s*(?:mm)?\s*(?:\+([0-9.]+)\s*\/\s*-\s*([0-9.]+))?/i;
                    const match = cleanLabel.match(regex);
                    if (match) {
                        nominal = parseFloat(match[1]);
                        if (match[2] && match[3]) {
                            plus = parseFloat(match[2]);
                            minus = parseFloat(match[3]);
                        }
                    }
                }
                
                return {
                    nominal: isNaN(nominal) ? null : nominal,
                    plus: isNaN(plus) ? 0 : plus,
                    minus: isNaN(minus) ? 0 : minus
                };
            },

            getDimRange(ri) {
                const parsed = this.getParsedDimStd(ri);
                if (parsed.nominal === null) return null;
                return { 
                    min: parsed.nominal - parsed.minus, 
                    max: parsed.nominal + parsed.plus 
                };
            },

            isDimOut(ri, col) {
                const d = this.dimStd[ri] || {};
                let rawVal = this.dimData[`${ri}_${col}`];
                if (!rawVal) return false;
                
                if (typeof rawVal === 'string') {
                    rawVal = rawVal.replace(',', '.');
                }
                const val = parseFloat(rawVal);
                if (isNaN(val)) return false;
                const range = this.getDimRange(ri);
                if (!range) return false;
                return val < range.min || val > range.max;
            },
            
            getDimUnit(ri) {
                const d = this.dimStd[ri] || {};
                if (d.item && d.item.toUpperCase().includes('JUMLAH HOLE')) return 'pcs';
                return 'mm';
            },

            getDimPrefix(ri) {
                const d = this.dimStd[ri] || {};
                if (d.item && d.item.toUpperCase().includes('JUMLAH HOLE')) return '';
                return 'Ø';
            },

            getDimStandardText(ri) {
                const d = this.dimStd[ri];
                if (!d) return '';

                // Jika nominal ada, gunakan format standar (Ø ... mm)
                if (d.nominal && d.nominal != 0 && d.nominal !== '') {
                    let txt = `Ø ${d.nominal} mm`;
                    if (d.plus || d.minus) {
                        txt += ` +${d.plus || 0}/-${d.minus || 0}`;
                    }
                    return txt;
                }
                // Jika tidak ada nominal (data lama), gunakan label sebagai cadangan
                return d.label || '';
            },

            openDimSettings(ri) {
                if (!this.canEditStandardSection || !this.isLeader) return;
                this.targetDimIdx = ri;
                const d = this.dimStd[ri];
                this.tempDim = { _step: 0.01, ...d };
                this.showDimModal = true;
            },

            formatDimStdBrief(d) {
                if (!d || (!d.item && !d.nominal && !d.label)) return 'Ã¢â‚¬â€';
                const parts = [d.item, d.label || (d.nominal ? `Ø${d.nominal}` : '')].filter(Boolean);
                return parts.join(' / ') || 'Ã¢â‚¬â€';
            },

            saveDimSettings() {
                const idx = this.targetDimIdx;
                const before = this.formatDimStdBrief(this.dimStd[idx]);
                this.dimStd[idx] = { ...this.tempDim };
                const after = this.formatDimStdBrief(this.dimStd[idx]);
                if (this.shouldLogLeaderRevision && before !== after) {
                    this.appendLeaderRevisionRecord(`Dimensi #${idx + 1}`, before, after);
                    if (this._leaderEditBaseline) this._leaderEditBaseline.dimStdJson = JSON.stringify(this.dimStd);
                }
                this.showDimModal = false;
            },

            clearDimSettings() {
                this.tempDim = { item: '', nominal: '', plus: '', minus: '', method: '', _step: 0.01 };
                this.dimStd[this.targetDimIdx] = { ...this.tempDim };
                // Juga hapus data inputan user (sample 1,2,3) untuk baris ini
                ['1','2','3'].forEach(s => {
                    delete this.dimData[`${this.targetDimIdx}_${s}`];
                });
                this.showDimModal = false;
                this.showToast('success', 'Standar dimensi berhasil dikosongkan');
            },

            openAppStandardModal(ri) {
                if (!this.canEditStandardSection || !this.isLeader) return;
                this.appStandardTargetRi = ri;
                this.appStandardSelected = [];
                this.appStandardCustom = '';
                
                let current = this.appItems[ri] || '';
                if (current) {
                    this.appStandardPresets.forEach(preset => {
                        if (current.includes(preset)) {
                            this.appStandardSelected.push(preset);
                            current = current.replace(preset, '').replace(/^[,/\s]+|[,/\s]+$/g, '').trim();
                        }
                    });
                    if (current.includes('Marking ')) {
                        this.appStandardMarking = current.replace('Marking ', '').replace(' harus jelas / nyata', '');
                        current = current.replace(/Marking .* harus jelas \/ nyata/, '').replace(/^[,/\s]+|[,/\s]+$/g, '').trim();
                    }
                    this.appStandardCustom = current;
                }
                this.showAppStandardModal = true;
            },
            
            saveAppStandardModal() {
                const ri = this.appStandardTargetRi;
                const before = (this.appItems[ri] || '').trim() || 'Ã¢â‚¬â€';
                let combined = [...this.appStandardSelected];
                if (this.appStandardMarking) {
                    combined.unshift(`Marking ${this.appStandardMarking} harus jelas / nyata`);
                }
                if (this.appStandardCustom.trim()) combined.push(this.appStandardCustom.trim());
                
                this.appItems[ri] = combined.join(', ');
                this.appItems = [...this.appItems];
                const after = (this.appItems[ri] || '').trim() || 'Ã¢â‚¬â€';
                if (this.shouldLogLeaderRevision && before !== after) {
                    this.appendLeaderRevisionRecord(`Appearance #${ri + 8}`, before, after);
                    if (this._leaderEditBaseline) this._leaderEditBaseline.appItemsJson = JSON.stringify(this.appItems);
                }
                this.showAppStandardModal = false;
            },
            
            clearAppStandard() {
                this.appStandardSelected = [];
                this.appStandardCustom = '';
                this.appStandardMarking = '';
                this.appItems[this.appStandardTargetRi] = '';
                this.appItems = [...this.appItems];
                this.showAppStandardModal = false;
                this.showToast('success', 'Standar appearance berhasil dikosongkan');
            },

            setColOk(col) {
                if (!this.isQCSectionOpen || !this.isOperator) return;
                const holeIdx = this.appItems.findIndex(i => i && i.toUpperCase().includes('JUMLAH HOLE'));
                for (let r = 0; r < 9; r++) {
                    // Skip Jumlah Hole & Type Pallet (isian bebas)
                    const app = this.appItems[r];
                    if (r === holeIdx && holeIdx !== -1) continue;
                    if (app && app.includes('Type Pallet')) continue;
                    this.appData[`${r}_${col}`] = 'ok';
                }
            },

            setDimColOk(col) {
                if (!this.isQCSectionOpen || !this.isOperator || !this.isColUnlocked(col)) return;
                let updated = false;
                for (let r = 0; r < 5; r++) {
                    const dim = this.dimStd[r];
                    if (dim && (dim.item || dim.label)) {
                        let valToSet = null;
                        if (dim.nominal !== '' && dim.nominal !== null && dim.nominal !== undefined) {
                            valToSet = dim.nominal.toString();
                        } else {
                            // Fallback: Copy dari kolom pertama (kolom 1) jika nominal kosong
                            const firstCol = this.cols.length > 0 ? this.cols[0] : '1';
                            const firstColVal = this.dimData[`${r}_${firstCol}`];
                            if (firstColVal !== undefined && firstColVal !== null && firstColVal !== '') {
                                valToSet = firstColVal;
                            }
                        }

                        if (valToSet !== null) {
                            this.dimData[`${r}_${col}`] = valToSet;
                            updated = true;
                        }
                    }
                }
                
                // Paksa trigger reactivity Alpine JS agar UI langsung update
                if (updated) {
                    this.dimData = { ...this.dimData };
                }
            },

            setColDimNominal(col) {
                if (!this.isQCSectionOpen || !this.isOperator) return;
                for (let ri = 0; ri < 7; ri++) {
                    const parsed = this.getParsedDimStd(ri);
                    if (parsed.nominal !== null) {
                        this.dimData[`${ri}_${col}`] = parsed.nominal.toString();
                    }
                }
            },

            // Buka modal input dimensi operator (seperti setting Totok, tapi untuk isi hasil ukur)
            openDimInput(ri, col) {
                if (!this.isQCSectionOpen || !this.isOperator || !this.isColUnlocked(col)) return;
                const d = this.dimStd[ri];
                if (!d.item && !d.nominal && !d.label) return; // skip baris kosong
                this.dimInputTargetRi = ri;
                this.dimInputTargetCol = col;
                this.dimInputTemp = this.dimData[`${ri}_${col}`] || '';
                this.showDimInputModal = true;
            },

            saveDimInput() {
                const val = this.dimInputTemp.toString().replace(',', '.');
                this.dimData[`${this.dimInputTargetRi}_${this.dimInputTargetCol}`] = val;
                this.showDimInputModal = false;
                this.dimInputTemp = '';
            },

            closeDimInput() {
                this.showDimInputModal = false;
                this.dimInputTemp = '';
            },

            // Cek status OK/NG berdasarkan nilai sementara di modal
            getDimInputStatus() {
                if (!this.dimInputTemp && this.dimInputTemp !== 0) return 'empty';
                
                const ri = this.dimInputTargetRi;
                const raw = this.dimInputTemp.toString().replace(',', '.');
                const val = parseFloat(raw);
                if (isNaN(val)) return 'empty';
                
                const parsed = this.getParsedDimStd(ri);
                if (parsed.nominal === null) return 'ok'; // tidak ada standar, anggap OK
                
                if (val < (parsed.nominal - parsed.minus) || val > (parsed.nominal + parsed.plus)) return 'ng';
                return 'ok';
            },
            getJudge(col) {
                let hasNg = false;
                const holeIdx = this.appItems.findIndex(i => i && i.toUpperCase().includes('JUMLAH HOLE'));
                for (let r = 0; r < 9; r++) {
                    if (r === holeIdx && holeIdx !== -1) {
                        const standard = parseInt(this.holeStandard) || 0;
                        const entered = parseInt(this.appData[`${holeIdx}_${col}`]);
                        if (!isNaN(entered) && entered !== standard) hasNg = true;
                    } else {
                        if (this.appData[`${r}_${col}`] === 'ng') hasNg = true;
                    }
                }
                if (hasNg) return 'NG';
                try { 
                    const obj = JSON.parse(this.judgement || '{}'); 
                    return obj[col] || 'OK'; 
                } catch { return 'OK'; }
            },
            getGlobalJudgement() {
                // 1. Cek apakah ada Dimensi yang out of range
                for (let ri = 0; ri < 7; ri++) {
                    const hasStandard = this.dimStd[ri] && (this.dimStd[ri].item || this.dimStd[ri].nominal);
                    if (hasStandard) {
                        for (let col = 1; col <= this.totalPcs; col++) {
                            if (this.dimData[`${ri}_${col}`] && this.isDimOut(ri, col)) {
                                return 'NG';
                            }
                        }
                    }
                }
                
                // 2. Cek apakah ada Appearance yang bernilai NG
                let hasNg = false;
                const holeIdxG = this.appItems.findIndex(i => i && i.toUpperCase().includes('JUMLAH HOLE'));
                for (let ri = 0; ri < 9; ri++) {
                    const app = this.appItems[ri];
                    if (!app && ri > 7) continue;
                    if (app && (app.includes('Spec Material') || app.includes('Type Pallet'))) continue;
                    if (ri === holeIdxG && holeIdxG !== -1) {
                        // Jumlah Hole: cek berdasarkan holeStandard
                        const standard = parseInt(this.holeStandard) || 0;
                        for (let col = 1; col <= this.totalPcs; col++) {
                            const entered = parseInt(this.appData[`${ri}_${col}`]);
                            if (!isNaN(entered) && entered !== standard) hasNg = true;
                        }
                        continue;
                    }
                    for (let col = 1; col <= this.totalPcs; col++) {
                        if (this.appData[`${ri}_${col}`] === 'ng') {
                            hasNg = true;
                        }
                    }
                }
                if (hasNg) return 'NG';

                // 3. Cek apakah ada Bundle yang NG
                let bundleNg = false;
                this.bundleChecks.forEach(b => {
                    if (b.judgement === 'NG') bundleNg = true;
                    ['1','2','3'].forEach(s => {
                        if (b.samples[s]) {
                            Object.values(b.samples[s]).forEach(v => {
                                if (v === 'ng') bundleNg = true;
                            });
                        }
                    });
                });
                if (bundleNg) return 'NG';

                return 'OK';
            },
            computedOkCount() {
                let okCount = 0;
                if (!this.cols || !Array.isArray(this.cols)) return 0;
                this.cols.forEach(c => {
                    if (this.getColJudgement(c) === 'OK') {
                        okCount++;
                    }
                });
                return okCount;
            },
            computedNgCount() {
                let ngCount = 0;
                if (!this.cols || !Array.isArray(this.cols)) return 0;
                this.cols.forEach(c => {
                    if (this.getColJudgement(c) === 'NG') {
                        ngCount++;
                    }
                });
                return ngCount;
            },
            getColJudgement(col) {
                let hasInput = false;

                // 1. Cek Dimensi
                for (let ri = 0; ri < 7; ri++) {
                    const hasStandard = this.dimStd[ri] && (this.dimStd[ri].item || this.dimStd[ri].nominal);
                    if (hasStandard) {
                        const val = this.dimData[`${ri}_${col}`];
                        if (val !== undefined && val !== null && val !== '') {
                            hasInput = true;
                            if (this.isDimOut(ri, col)) return 'NG';
                        }
                    }
                }

                // 2. Cek Appearance
                const holeIdxG = this.appItems.findIndex(i => i && i.toUpperCase().includes('JUMLAH HOLE'));
                for (let ri = 0; ri < 9; ri++) {
                    const app = this.appItems[ri];
                    if (!app && ri > 7) continue;
                    if (app && (app.includes('Spec Material') || app.includes('Type Pallet'))) continue;

                    if (ri === holeIdxG && holeIdxG !== -1) {
                        const standard = parseInt(this.holeStandard) || 0;
                        const val = this.appData[`${ri}_${col}`];
                        if (val !== undefined && val !== null && val !== '') {
                            hasInput = true;
                            const entered = parseInt(val);
                            if (!isNaN(entered) && entered !== standard) return 'NG';
                        }
                        continue;
                    }

                    const val = this.appData[`${ri}_${col}`];
                    if (val !== undefined && val !== null && val !== '') {
                        hasInput = true;
                        if (val === 'ng') return 'NG';
                    }
                }

                if (!hasInput) return '-';
                return 'OK';
            },
            toggleJudge(col) {
                if (!this.isQCSectionOpen || !this.isOperator) return;
                let obj = {};
                try { obj = JSON.parse(this.judgement || '{}'); } catch {}
                obj[col] = (this.getJudge(col) === 'OK' ? 'NG' : 'OK');
                this.judgement = JSON.stringify(obj);
            },
            initInitialBundles() {
                if (this.bundleChecks.length > 0) return;
                this.bundleChecks = Array.from({ length: 5 }, (_, i) => ({
                    id: i + 1, bundleName: '', coilNo: '', judgement: 'OK', samples: { '0':{},'1':{},'2':{},'3':{},'4':{},'5':{},'6':{} }
                }));
            },
            cycleBundleApp(bundle, s, idx) {
                if (!this.isQCSectionOpen || !this.isOperator) return;
                if (!bundle.samples[s]) bundle.samples[s] = {};
                const v = bundle.samples[s][idx] || '';
                const map = { '': 'ok', 'ok': 'ng', 'ng': '' };
                bundle.samples[s][idx] = map[v];
            },

            // ==========================================
            // METHODS: UI & SIGNATURES
            // ==========================================
            getStatusStyle(status) {
                const map = {
                    draft: { label: 'Draft', bg: '#F1F5F9', border: '#CBD5E1', color: '#475569' },
                    submitted: { label: 'Menunggu Foreman', bg: '#FFF7ED', border: '#FB923C', color: '#C2410C' },
                    waiting_foreman: { label: 'Menunggu Foreman', bg: '#FFF7ED', border: '#FB923C', color: '#C2410C' },
                    revision: { label: '⚠️ Perlu Revisi', bg: '#FEF2F2', border: '#FCA5A5', color: '#B91C1C' },
                    waiting_supervisor: { label: 'Menunggu SPV', bg: '#F5F3FF', border: '#C4B5FD', color: '#6D28D9' },
                    ready_for_qc: { label: 'Selesai ✔️', bg: '#F0FDF4', border: '#4ADE80', color: '#15803D' },
                    locked: { label: 'Selesai ✔️', bg: '#F0FDF4', border: '#4ADE80', color: '#15803D' },
                    waiting_qc_approval: { label: 'Selesai ✔️', bg: '#F0FDF4', border: '#4ADE80', color: '#15803D' },
                    finished: { label: 'Selesai ✔️', bg: '#F0FDF4', border: '#4ADE80', color: '#15803D' },
                };
                return map[status] || { label: status || '—', bg: '#F1F5F9', border: '#CBD5E1', color: '#64748B' };
            },

            // ==========================================
            // METHODS: WHATSAPP NOTIFICATION
            // ==========================================
            sendWhatsAppNotification(roleType) {
                // roleType: 'gl' | 'foreman'
                let user = null;
                if (roleType === 'gl') {
                    user = this.glUsers.find(u => String(u.id) === String(this.assignedGlId));
                } else {
                    user = this.fmUsers.find(u => String(u.id) === String(this.assignedForemanId));
                }

                if (!user) {
                    this.showToast('error', roleType === 'gl' ? 'Pilih GL terlebih dahulu!' : 'Pilih Foreman terlebih dahulu!');
                    return;
                }

                const phone = user.phone ? user.phone.replace(/[^0-9]/g, '').replace(/^0/, '62') : '';

                // Hitung severity
                const severity = this.getNgSeverity;
                const total = parseInt(this.totalPcs) || 0;
                const rejectCount = parseInt(this.reject) || 0;
                const repairCount = parseInt(this.repair) || 0;
                const ngCount = rejectCount + repairCount;
                const ratio = total > 0 ? ((ngCount / total) * 100).toFixed(1) : '0.0';
                const docUrl = window.location.href;
                const partInfo = this.partName || 'Ã¢â‚¬â€';
                const jobInfo = this.jobNo || 'Ã¢â‚¬â€';
                const roleLabel = roleType === 'gl' ? 'GL' : 'Foreman';

                let message = '';
                if (severity === 'critical_ng') {
                    message = `Ã°Å¸Å¡Â¨ *[CRITICAL ALERT - SEGERA KE JALUR]* Ã°Å¸Å¡Â¨

Halo Pak/Bu ${user.nama}, terdeteksi temuan *NG KRITIS* di Press Line!

Ã°Å¸â€œâ€¹ *Detail Dokumen:*
- Part Name: ${partInfo}
- Job No: ${jobInfo}
- Total Produksi: ${total} pcs
- *Total NG: ${ngCount} pcs (Rasio Cacat: ${ratio}%)*
- *Status Disposisi: Terdeteksi REJECT / REPAIR Massal!*

Ã¢Å¡Â Ã¯Â¸Â Mohon *SEGERA TURUN KE JALUR* untuk melakukan investigasi fisik langsung bersama GL dan Operator sebelum menyetujui dokumen ini.

Ã°Å¸â€— Link Dokumen: ${docUrl}`;
                } else {
                    message = `Ã°Å¸â€œâ€¹ *Permintaan Verifikasi Lembar Inspeksi*

Halo Pak/Bu ${user.nama}, mohon bantuannya untuk verifikasi Lembar Inspeksi berikut:

- Part Name: ${partInfo}
- Job No: ${jobInfo}
- Status QG: NG Ringan (Masih dalam batas toleransi)

Anda dapat melakukan persetujuan / TTD Mandiri Sah langsung dari HP atau PC Anda tanpa perlu turun ke jalur.

Ã°Å¸â€— Klik link berikut untuk membuka & menyetujui dokumen:
${docUrl}`;
                }

                const encoded = encodeURIComponent(message);

                if (phone) {
                    window.open(`https://wa.me/${phone}?text=${encoded}`, '_blank');
                } else {
                    // Tidak ada nomor telepon Ã¢â‚¬â€ buka dialog input
                    const inputPhone = prompt(`Nomor WhatsApp Pak/Bu ${user.nama} belum terdaftar.\nMasukkan nomor WA (contoh: 08123456789):`);
                    if (inputPhone && inputPhone.trim()) {
                        const cleaned = inputPhone.trim().replace(/[^0-9]/g, '').replace(/^0/, '62');
                        window.open(`https://wa.me/${cleaned}?text=${encoded}`, '_blank');
                    }
                }
            },

            // ==========================================
            // METHODS: DIGITAL PAGING & INTERCOM SYSTEM
            // ==========================================
            intercomInterval: null,
            audioCtx: null,
            ringInterval: null,

            async startIntercomCall(roleType) {
                if (!this.editId) {
                    this.showToast('error', 'Simpan dokumen terlebih dahulu sebelum menggunakan Interkom!');
                    return;
                }

                let user = null;
                if (roleType === 'gl') {
                    user = this.glUsers.find(u => String(u.id) === String(this.assignedGlId));
                } else {
                    user = this.fmUsers.find(u => String(u.id) === String(this.assignedForemanId));
                }

                if (!user) {
                    this.showToast('error', roleType === 'gl' ? 'Pilih GL terlebih dahulu!' : 'Pilih Foreman terlebih dahulu!');
                    return;
                }

                try {
                    const res = await apiFetch('/api/intercom/call', {
                        method: 'POST',
                        body: JSON.stringify({
                            lembar_inspeksi_id: this.editId,
                            role_type: roleType,
                            assigned_user_id: user.id
                        })
                    });

                    if (res && res.success) {
                        this.intercomCall = res.data;
                        this.showIntercomModal = true;
                        this.playRingtone();

                        // Start Polling call status
                        if (this.intercomInterval) clearInterval(this.intercomInterval);
                        this.intercomInterval = setInterval(() => {
                            this.pollIntercomCallStatus();
                        }, 2000);
                    }
                } catch (e) {
                    console.error(e);
                    this.showToast('error', 'Gagal memulai panggilan interkom.');
                }
            },

            async pollIntercomCallStatus() {
                if (!this.editId) return;
                try {
                    const res = await apiFetch(`/api/intercom/status/${this.editId}`);
                    if (res && res.success && res.data) {
                        this.intercomCall = res.data;

                        if (this.intercomCall.status === 'arrived') {
                            // GL sudah check-in fisik Ã¢â€ â€™ tutup modal, tampilkan banner hijau
                            this.stopRingtone();
                            this.playBeep('success');
                            clearInterval(this.intercomInterval);
                            this.intercomInterval = null;
                            this.glArrivedData = this.intercomCall;
                            this.showIntercomModal = false;
                            this._answeredToastShown = false;
                        } else if (this.intercomCall.status === 'answered') {
                            // GL dalam perjalanan Ã¢â€ â€™ JANGAN tutup modal (GL perlu klik check-in)
                            this.stopRingtone();
                            clearInterval(this.intercomInterval);
                            this.intercomInterval = null;
                            if (!this._answeredToastShown) {
                                this._answeredToastShown = true;
                                this.showToast('success', `Ã°Å¸ÂÆ’ ${this.intercomCall.responder_name || 'GL/Foreman'} sedang dalam perjalanan!`);
                            }
                        } else if (this.intercomCall.status === 'declined') {
                            this.stopRingtone();
                            this.playBeep('error');
                            clearInterval(this.intercomInterval);
                            this.intercomInterval = null;
                            this.showToast('error', 'Panggilan ditolak atau GL/Foreman sedang sibuk.');
                        }
                    }
                } catch (e) {
                    console.error(e);
                }
            },

            async cancelIntercomCall() {
                this.stopRingtone();
                if (this.intercomInterval) {
                    clearInterval(this.intercomInterval);
                    this.intercomInterval = null;
                }
                this.showIntercomModal = false;

                try {
                    await apiFetch(`/api/intercom/complete/${this.editId}`, { method: 'POST' });
                } catch (e) {
                    console.error(e);
                }
            },

            // GL/Foreman klik "Saya Sudah Di Sini" di tablet operator
            async arriveAtLine() {
                if (!this.editId) return;
                // Gunakan nama GL dari responder (set saat GL klik 'Dalam Perjalanan' dari device-nya)
                // Ini membuktikan GL yang hadir adalah yang sama yang merespons panggilan
                const arrivedName = this.intercomCall?.responder_name || this.incomingCall?.responder_name || 'GL/Foreman';
                try {
                    const res = await apiFetch('/api/intercom/arrive', {
                        method: 'POST',
                        body: JSON.stringify({
                            lembar_inspeksi_id: this.editId,
                            arrived_name:       arrivedName,
                        })
                    });
                    if (res && res.success) {
                        this.glArrivedData   = res.data;
                        this.showIntercomModal = false;   // Tutup modal outgoing
                        this.showIncomingModal = false;   // Tutup modal incoming (jika ada)
                        this.incomingCall     = null;
                        this._answeredToastShown = false; // reset flag
                        this.stopRingtone();
                        this.playBeep('success');
                        this.showToast('success', `${arrivedName} telah check-in! Alarm di perangkat GL akan padam.`);
                    }
                } catch(e) {
                    console.error(e);
                    this.showToast('error', 'Gagal check-in. Coba lagi.');
                }
            },

            async respondToIncoming(action) {
                if (!this.incomingCall) return;
                this.stopRingtone();
                this.showIncomingModal = false;

                try {
                    const res = await apiFetch('/api/intercom/respond', {
                        method: 'POST',
                        body: JSON.stringify({
                            lembar_inspeksi_id: this.incomingCall.lembar_inspeksi_id,
                            action: action,
                            responder_name: this.userName,
                            message: action === 'accept' ? this.selectedPresetMsg : 'Sibuk/Tolak'
                        })
                    });

                    if (res && res.success) {
                        this.showToast(action === 'accept' ? 'success' : 'info', 
                            action === 'accept' ? `Panggilan diterima! Respon: "${this.selectedPresetMsg}"` : 'Panggilan ditolak.'
                        );
                    }
                    this.incomingCall = null;
                } catch (e) {
                    console.error(e);
                    this.showToast('error', 'Gagal merespons panggilan interkom.');
                }
            },

            // Web Audio API Ringtone & Beep Generator
            playRingtone(sirenStyle = false) {
                if (this.audioCtx) return;
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    this.audioCtx = new AudioContext();

                    this.ringInterval = setInterval(() => {
                        if (!this.audioCtx) return;

                        const osc1 = this.audioCtx.createOscillator();
                        const osc2 = this.audioCtx.createOscillator();
                        const gainNode = this.audioCtx.createGain();

                        if (sirenStyle) {
                            // Suara sirine bergelombang (Loud & urgent untuk Foreman)
                            osc1.frequency.value = 600;
                            osc1.frequency.setValueAtTime(600, this.audioCtx.currentTime);
                            osc1.frequency.linearRampToValueAtTime(1000, this.audioCtx.currentTime + 0.5);
                            osc1.frequency.linearRampToValueAtTime(600, this.audioCtx.currentTime + 1.0);
                            gainNode.gain.setValueAtTime(0.4, this.audioCtx.currentTime);
                        } else {
                            // Nada panggilan telepon standar (Ringing tone)
                            osc1.frequency.value = 440;
                            osc2.frequency.value = 480;
                            gainNode.gain.setValueAtTime(0.25, this.audioCtx.currentTime);
                        }

                        osc1.connect(gainNode);
                        if (!sirenStyle) osc2.connect(gainNode);
                        
                        gainNode.connect(this.audioCtx.destination);

                        osc1.start();
                        if (!sirenStyle) osc2.start();

                        // Ring selama 1.2 detik lalu meredup
                        gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 1.2);
                        osc1.stop(this.audioCtx.currentTime + 1.3);
                        if (!sirenStyle) osc2.stop(this.audioCtx.currentTime + 1.3);
                    }, 2000); // Ulangi setiap 2 detik
                } catch (e) {
                    console.error("Web Audio API not supported:", e);
                }
            },

            stopRingtone() {
                if (this.ringInterval) {
                    clearInterval(this.ringInterval);
                    this.ringInterval = null;
                }
                if (this.audioCtx) {
                    try {
                        this.audioCtx.close();
                    } catch(e){}
                    this.audioCtx = null;
                }
            },

            playBeep(type = 'success') {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    const ctx = new AudioContext();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();

                    osc.frequency.value = type === 'success' ? 880 : 220; // 880Hz (Tinggi) / 220Hz (Rendah)
                    gain.gain.setValueAtTime(0.2, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);

                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start();
                    osc.stop(ctx.currentTime + 0.4);
                } catch (e) {}
            },

            // ==========================================
            // METHODS: REMOTE / MANDIRI SIGN
            // ==========================================
            signRemotely(roleType) {
                // TTD Mandiri: Hanya bisa dilakukan oleh akun yang ditunjuk sebagai GL/Foreman pada dokumen ini.
                // Sistem otomatis membuat tanda tangan digital berbasis nama, tanpa menggambar manual.
                const canvas = document.createElement('canvas');
                canvas.width = 400; canvas.height = 120;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Tanda tangan digital berupa teks kalligrafis
                ctx.font = 'italic bold 36px Georgia, serif';
                ctx.fillStyle = '#1e293b';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(this.userName, 200, 50);

                // Garis bawah tanda tangan
                ctx.beginPath();
                ctx.moveTo(40, 80); ctx.lineTo(360, 80);
                ctx.strokeStyle = '#1e293b'; ctx.lineWidth = 1.5;
                ctx.stroke();

                // Label "Verified Digitally"
                ctx.font = 'bold 9px Arial';
                ctx.fillStyle = '#64748b';
                ctx.fillText('✓ TTD MANDIRI SAH Ã¢â‚¬â€ ' + new Date().toLocaleString('id-ID'), 200, 100);

                const sigData = canvas.toDataURL('image/png');

                if (roleType === 'gl') {
                    this.glParaf = sigData;
                    this.showToast('success', '✓ TTD Mandiri GL berhasil disimpan!');
                } else if (roleType === 'foreman') {
                    this.fmParaf = sigData;
                    this.showToast('success', '✓ TTD Mandiri Foreman berhasil disimpan!');
                } else if (roleType === 'gl_bundle') {
                    this.bundleGlSig = sigData;
                    this.showToast('success', '✓ TTD Mandiri GL (Bundle) berhasil disimpan!');
                } else if (roleType === 'fm_bundle') {
                    this.bundleFmSig = sigData;
                    this.showToast('success', '✓ TTD Mandiri Foreman (Bundle) berhasil disimpan!');
                }
            },

            openSignaturePad(type) {

                this.activePad = type;
                this.initCanvas();
            },
            closeSignaturePad() {
                this.activePad = null;
            },
             initCanvas() {
                 this.$nextTick(() => {
                     const canvas = document.getElementById('sig-canvas');
                     if (!canvas) return;
                     const ctx = canvas.getContext('2d');
                     
                     // Set smooth black stroke styles
                     ctx.strokeStyle = '#000000';
                     ctx.lineWidth = 3;
                     ctx.lineCap = 'round';
                     ctx.lineJoin = 'round';

                     // Fill white canvas background
                     ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);

                     // Load saved signature from localStorage if exists for this user/role
                     const storageKey = 'saved_signature_' + (this.userId || this.role || 'guest');
                     const savedSig = localStorage.getItem(storageKey);
                     if (savedSig) {
                         const img = new Image();
                         img.onload = () => {
                             ctx.drawImage(img, 0, 0);
                         };
                         img.src = savedSig;
                     }

                     let drawing = false;
                     const getPos = (e) => {
                         const r = canvas.getBoundingClientRect();
                         const src = e.touches ? e.touches[0] : e;
                         return { x: (src.clientX - r.left) * (canvas.width / r.width), y: (src.clientY - r.top) * (canvas.height / r.height) };
                     };
                     canvas.onmousedown = canvas.ontouchstart = (e) => { drawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
                     canvas.onmousemove = canvas.ontouchmove = (e) => { if (drawing) { const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); } };
                     canvas.onmouseup = canvas.ontouchend = () => { drawing = false; };
                 });
             },
             saveSignature() {
                 const canvas = document.getElementById('sig-canvas');
                 if (!canvas) return;
                 const data = canvas.toDataURL('image/png');
                 if (this.activePad === 'prep') {
                     this.prepSig = data;
                 }
                 else if (this.activePad === 'operator_ttd') {
                     this.bundlePrepSig = data;
                     // Auto-resolve revisi item check jika sebelumnya ditolak
                     if (this.fieldRevisions && this.fieldRevisions['item_check']) {
                         this.fieldRevisions['item_check'].resolved = true;
                         this.fieldRevisions['item_check'].resolved_at = new Date().toLocaleString();
                         this.showToast('info', 'Catatan revisi ditandai selesai secara otomatis setelah TTD baru.');
                     }
                 }
                 else if (this.activePad === 'fm') this.glSig = data;
                 else if (this.activePad === 'spv') this.fmSig = data;
                 else if (this.activePad === 'gl_global' || this.activePad === 'glParaf') this.bundleGlSig = data;
                 else if (this.activePad === 'fm_global' || this.activePad === 'fmParaf') this.bundleFmSig = data;

                 // Save signature to localStorage under the user's role/id so we can reuse it
                 const storageKey = 'saved_signature_' + (this.userId || this.role || 'guest');
                 localStorage.setItem(storageKey, data);

                 this.closeSignaturePad();
                 this.showToast('success', 'TTD Berhasil disimpan');
             },

            // ==========================================
            // DATA LOADING & SAVING (API)
            // ==========================================
            async loadData() {
                this.loadingData = true;
                try {
                    const res = await apiFetch(`/api/inspeksi/${this.editId}`);
                    const d = res.data ?? res;
                    this.jobNo = d.job_no || ''; this.partName = d.part_name || '';
                    this.partNo = d.part_no || ''; this.partType = d.type || '';
                    this.specMat = d.spec_material || ''; this.typePallet = d.type_pallet || ''; this.prosesRoute = d.proses_route || '';
                    this.lokasi = d.lokasi || ''; this.tanggal = d.tgl_bulan?.slice(0, 10) || '';
                    this.shift = d.shift || ''; this.totalPcs = d.total_produksi || 0; this.repair = d.repair || 0; this.reject = d.reject || 0;
                    this.status = d.status || 'draft';
                    // Load sampling formula parameters
                    this.tactTime       = parseFloat(d.tact_time)       || 0;
                    this.ctDimensi      = parseFloat(d.ct_dimensi)      || 0;
                    this.ctTanpaDimensi = parseFloat(d.ct_tanpa_dimensi) || 0;
                    this.customSamplingCols = d.sampling_cols || [];
                    // Hanya muat nama jika ada di DB, jangan timpa pakai nama user yang lagi buka
                    if (d.qg_name) {
                        this.qgName = d.qg_name;
                    }
                    this.judgement = d.qg_judgement || '';
                    // #region agent log Ã¢â‚¬â€ H-D: [] dari DB membuat ngDetails array Ã¢â€ â€™ hilang saat JSON.stringify
                    let _ngRaw = d.ng_details;
                    const _ngLoadType = _ngRaw == null ? 'nullish' : (Array.isArray(_ngRaw) ? 'array' : typeof _ngRaw);
                    // #endregion
                    if (typeof d.ng_details === 'string') {
                        try { this.ngDetails = JSON.parse(d.ng_details); } catch { this.ngDetails = {}; }
                    } else if (Array.isArray(d.ng_details)) {
                        this.ngDetails = {};
                        d.ng_details.forEach((item, i) => {
                            if (item && typeof item === 'object') {
                                const key = (item.row != null && item.sample != null) ? `${item.row}_${item.sample}` : String(i);
                                this.ngDetails[key] = {
                                    proses: item.proses || '',
                                    problems: item.problems || item.problem || [],
                                    causes: (item.causes || item.penyebab || []).map(c => String(c).toLowerCase()),
                                    catatan: item.catatan || '',
                                    disposisi: item.disposisi || '',
                                };
                            }
                        });
                    } else {
                        this.ngDetails = (d.ng_details && typeof d.ng_details === 'object') ? d.ng_details : {};
                    }
                    // Pastikan selalu plain object (bukan Array) agar JSON.stringify tidak hilang key
                    if (Array.isArray(this.ngDetails)) {
                        const converted = {};
                        Object.keys(this.ngDetails).forEach(k => {
                            if (this.ngDetails[k]) converted[k] = this.ngDetails[k];
                        });
                        this.ngDetails = converted;
                    }
                    
                    const formatSigPath = (path) => {
                        if (!path) return null;
                        if (!path.startsWith('http') && !path.startsWith('data:') && !path.startsWith('/')) {
                            return '/' + path;
                        }
                        return path;
                    };

                    this.glSig = formatSigPath(d.paraf_gl);
                    this.fmSig = formatSigPath(d.paraf_foreman);
                    this.prepSig = formatSigPath(d.prepared_paraf);
                    this.bundlePrepSig = formatSigPath(d.paraf_qc) || null;
                    // BUG FIX: Jika paraf_qc di DB sama persis dengan prepared_paraf
                    // (artinya Leader's sig salah tersimpan sebagai QC sig), reset bundlePrepSig
                    if (this.bundlePrepSig && this.prepSig && this.bundlePrepSig === this.prepSig) {
                        this.bundlePrepSig = null;
                    }
                    this.bundleGlSig = formatSigPath(d.paraf_gl_bottom) || null;
                    this.bundleFmSig = formatSigPath(d.paraf_foreman_bottom) || null;
                    this.bundleGlName = d.paraf_gl_bottom_name || ''; this.bundleFmName = d.paraf_fm_bottom_name || '';
                    this.bundleTindakan = d.bundle_tindakan || '';
                    this.catatan = d.catatan || '';
                    // Cek jika ada data bundle tersimpan
                    const hasBundleData = (d.bundle_checks && Array.isArray(d.bundle_checks) && d.bundle_checks.length > 0)
                        || d.paraf_gl_bottom || d.paraf_foreman_bottom;
                    this.catatanRevisi = d.catatan_revisi || '';
                    this.assignedGlId = d.assigned_gl_id || '';
                    this.assignedForemanId = d.assigned_foreman_id || '';
                    this.assignedOperatorId = d.assigned_operator_id || '';
                    
                    // Restore Field Revisions (Detailed ones)
                    if (d.field_revisions) {
                        try { 
                            let revs = typeof d.field_revisions === 'string' ? JSON.parse(d.field_revisions) : d.field_revisions; 
                            if (Array.isArray(revs)) revs = Object.assign({}, revs);
                            this.fieldRevisions = revs || {};
                        } catch (e) { this.fieldRevisions = {}; }
                    } else {
                        this.fieldRevisions = {};
                    }

                    // Restore Dimensi
                    for (let i = 0; i < 7; i++) {
                        this.dimStd[i] = {
                            item: d[`dimensi${i+1}_item`] || '',
                            label: d[`dimensi${i+1}`] || '',
                            method: d[`dimensi${i+1}_method`] || '',
                            nominal: d[`dimensi${i+1}_nominal`] || '',
                            plus: d[`dimensi${i+1}_plus`] || '',
                            minus: d[`dimensi${i+1}_minus`] || ''
                        };
                        
                        // Load from new generic JSON structure if exists
                        if (d[`dimensi${i+1}_results`]) {
                            const dimRes = typeof d[`dimensi${i+1}_results`] === 'string' 
                                ? JSON.parse(d[`dimensi${i+1}_results`]) 
                                : d[`dimensi${i+1}_results`];
                                
                            Object.entries(dimRes).forEach(([c, v]) => {
                                this.dimData[`${i}_${c}`] = v;
                            });
                        } else {
                            // Fallback for backward compatibility (only loaded 3 samples)
                            ['1','2','3'].forEach(s => {
                                if (d[`dimensi${i+1}_sample_${s}`]) this.dimData[`${i}_${s}`] = d[`dimensi${i+1}_sample_${s}`];
                            });
                        }
                    }

                    // Restore Appearance
                    for (let r = 0; r < 9; r++) {
                        const results = d[`appearance${r+6}_results`] || {};
                        Object.entries(results).forEach(([c, v]) => {
                            this.appData[`${r}_${c}`] = (v === '✓' || v === 'ok') ? 'ok' : (v === '✗' || v === 'ng') ? 'ng' : v;
                        });
                        if (d[`appearance${r+6}`] !== undefined) {
                            this.appItems[r] = d[`appearance${r+6}`] || '';
                        }
                    }
                    if (d.hole_standard !== undefined && d.hole_standard !== null) {
                        this.holeStandard = parseInt(d.hole_standard) || 0;
                    } else {
                        // Fallback: parse from appItems
                        const holeIdx = this.appItems.findIndex(i => i && i.toUpperCase().includes('JUMLAH HOLE'));
                        if (holeIdx !== -1) {
                            const match = this.appItems[holeIdx].match(/\d+/);
                            if (match) this.holeStandard = parseInt(match[0]);
                        }
                    }
                    const _ngKeysBefore = Object.keys(this.ngDetails).length;
                    this.pruneStaleNgDetails();
                    if (this.editId && Object.keys(this.ngDetails).length < _ngKeysBefore) {
                        this.persistNgDetails();
                    }

                    if (d.bundle_checks) this.bundleChecks = d.bundle_checks;
                    this.cols = this.rebuildCols();
                    this.captureLeaderEditBaseline();
                    
                    if (d.image_path) {
                        let path = d.image_path;
                        if (path.includes('storage/')) {
                            path = path.split('storage/')[1];
                        }
                        this.sketchUrl = path.startsWith('data:') 
                            ? path 
                            : ('/storage/' + path.replace(/^\//, ''));
                    }



                    // Restore Revision Records
                    if (d.revision_records) {
                        try { this.revRecords = typeof d.revision_records === 'string' ? JSON.parse(d.revision_records) : d.revision_records; } catch (e) {}
                    }

                    this.operatorClaimedAt = d.claimed_at || null;
                } catch (e) { this.showToast('error', 'Gagal muat: ' + e.message); }
                finally { this.loadingData = false; }
            },

            buildPayload(targetStatus) {
                let imagePathToSave = this.sketchUrl;
                    if (imagePathToSave && imagePathToSave.startsWith('http') && imagePathToSave.includes('storage/')) {
                        imagePathToSave = 'storage/' + imagePathToSave.split('storage/')[1];
                    }

                    const payload = {
                        job_no: this.jobNo, part_name: this.partName, part_no: this.partNo,
                        type: this.partType, spec_material: this.specMat, type_pallet: this.typePallet, proses_route: this.prosesRoute,
                        lokasi: this.lokasi, tgl_bulan: this.tanggal, shift: this.shift,
                        total_produksi: this.totalPcs, status: targetStatus,
                        qg_judgement: this.getGlobalJudgement(), bundle_checks: this.bundleChecks,
                        repair: this.repair, reject: this.reject,
                        bundle_tindakan: this.bundleTindakan, catatan: this.catatan,
                        ng_details: this.getNgDetailsForSave(),
                        paraf_gl_bottom: this.bundleGlSig, paraf_foreman_bottom: this.bundleFmSig,
                        paraf_gl_bottom_name: this.bundleGlName, paraf_fm_bottom_name: this.bundleFmName,
                        assigned_gl_id: this.assignedGlId, assigned_foreman_id: this.assignedForemanId,
                        assigned_operator_id: this.assignedOperatorId,
                        revision_records: this.revRecords,
                        field_revisions: this.fieldRevisions,
                        image_path: imagePathToSave,
                    qg_name: this.qgName,
                    // Sampling formula
                    tact_time:        this.tactTime       || null,
                    ct_dimensi:       this.ctDimensi      || null,
                    ct_tanpa_dimensi: this.ctTanpaDimensi || null,
                    sampling_cols:    this.customSamplingCols || [],
                };

                for (let i = 0; i < 7; i++) {
                    payload[`dimensi${i+1}_item`] = this.dimStd[i]?.item || '';
                    payload[`dimensi${i+1}`] = this.getDimStandardText(i); // Simpan teks lengkap ke kolom label
                    payload[`dimensi${i+1}_method`] = this.dimStd[i]?.method || '';
                    payload[`dimensi${i+1}_nominal`] = this.dimStd[i]?.nominal || '';
                    payload[`dimensi${i+1}_plus`] = this.dimStd[i]?.plus || '';
                    payload[`dimensi${i+1}_minus`] = this.dimStd[i]?.minus || '';
                    ['1','2','3'].forEach(s => { payload[`dimensi${i+1}_sample_${s}`] = this.dimData[`${i}_${s}`] || ''; });
                    
                    // Simpan seluruh data dimensi untuk semua kolom ke json
                    const dimResults = {};
                    this.cols.forEach(c => {
                        const v = this.dimData[`${i}_${c}`];
                        if (v !== undefined && v !== null && v !== '') {
                            dimResults[c] = v;
                        }
                    });
                    payload[`dimensi${i+1}_results`] = dimResults;
                }

                for (let r = 0; r < 9; r++) {
                    let appVal = this.appItems[r];
                    if (appVal && appVal.toUpperCase().includes('JUMLAH HOLE')) {
                        appVal = `Jumlah Hole (${this.holeStandard || 0} pcs)`;
                    }
                    payload[`appearance${r+6}`] = appVal;
                    const results = {};
                    if (appVal && appVal.toUpperCase().includes('TYPE PALLET')) {
                        if (this.appData[`${r}_all`]) {
                            results['all'] = this.appData[`${r}_all`];
                        }
                    } else {
                        this.cols.forEach(c => {
                            const v = this.appData[`${r}_${c}`];
                            if (v) results[c] = (v === 'ok' ? '✓' : v === 'ng' ? '✗' : v);
                        });
                    }
                    payload[`appearance${r+6}_results`] = results;
                }
                
                // Simpan holeStandard ke field khusus agar bisa di-restore
                payload.hole_standard = this.holeStandard || 0;
                
                if (this.glSig) payload.paraf_gl = this.glSig;
                if (this.fmSig) payload.paraf_foreman = this.fmSig;
                if (this.prepSig) payload.prepared_paraf = this.prepSig;
                // paraf_qc hanya diisi jika Operator QC sudah benar-benar TTD sendiri (bundlePrepSig)
                // JANGAN fallback ke prepSig agar TTD Leader tidak masuk ke kolom QC Operator
                if (this.bundlePrepSig) payload.paraf_qc = this.bundlePrepSig;
                
                // #region agent log
                if (payload.qg_judgement === 'NG' || Object.keys(payload.ng_details || {}).length > 0) {
                    const _ngKeys = Object.keys(payload.ng_details || {});
                }
                // #endregion
                
                return payload;
            },

            async handleSave() {
                this.flushLeaderRevisionQueue();
                // VALIDASI KHUSUS LEADER (PAK TOTOK)
                if (this.isLeader && ['draft', 'revision'].includes(this.status)) {
                    const required = {
                        jobNo: 'Job No',
                        partName: 'Nama Part',
                        partNo: 'Part No',
                        lokasi: 'Lokasi',
                        sketchUrl: 'Sketsa/Gambar Part',
                        prepSig: 'Tanda Tangan Leader'
                    };
                    
                    for (const [key, label] of Object.entries(required)) {
                        if (!this[key] || (typeof this[key] === 'number' && this[key] <= 0)) {
                            this.showToast('error', `Wajib diisi: ${label}`);
                            return;
                        }
                    }

                    // Check dimStd (at least one dimension must be fully configured)
                    // Support 2 formats:
                    // a) d.nominal (angka > 0) + d.method  Ã¢â€ Â format baru via DimModal
                    // b) d.label (teks, mis: "Ø15MM +0.5/-0.5") + d.method  Ã¢â€ Â format lama
                    let filledCount = 0;
                    for (let i = 0; i < 7; i++) {
                        const d = this.dimStd[i];
                        const hasNominal = (d.nominal && d.nominal !== '' && parseFloat(d.nominal) > 0)
                                        || (d.label && d.label.trim() !== '' && d.label.trim() !== '?');
                        const hasMethod = d.method && d.method.trim() !== '' && d.method.trim() !== '?';

                        if (hasNominal && hasMethod) {
                            filledCount++;
                        } else if (hasNominal || hasMethod) {
                            this.showToast('error', `Item Dimensi #${i + 1} diisi sebagian. Jika ingin digunakan, wajib isi Item Dimensi dan Metode Pengecekan.`);
                            return;
                        }
                    }

                    if (filledCount === 0) {
                        this.showToast('error', 'Wajib mengisi minimal 1 Standar Dimensi beserta Metode Pengecekannya!');
                        return;
                    }

                    // Validasi Jumlah Hole jika ada di item check appearance
                    const hasHoleItem = this.appItems.some(item => item && item.toUpperCase().includes('JUMLAH HOLE'));
                    if (hasHoleItem && (!this.holeStandard || parseInt(this.holeStandard) <= 0)) {
                        this.showToast('error', 'Wajib diisi: Standar Jumlah Hole (harus lebih dari 0)!');
                        return;
                    }
                }

                // VALIDASI: PASTIKAN SEMUA REVISI SUDAH DI-RESOLVE (KHUSUS STATUS REVISION)
                if (this.isLeader && this.status === 'revision') {
                    const unresolved = Object.values(this.fieldRevisions).some(rev => !rev.resolved);
                    if (unresolved) {
                        this.showToast('error', 'Wajib klik "Selesaikan Revisi" pada tiap bagian yang ditandai sebelum kirim!');
                        return;
                    }
                }
                
                // VALIDASI KHUSUS FOREMAN (PAK AZRIEL)
                if (this.isForeman && this.status === 'waiting_foreman') {
                    if (!this.glSig) {
                        this.showToast('error', 'Wajib diisi: Tanda Tangan Foreman');
                        return;
                    }
                    
                    this.confirmTitle = 'Konfirmasi Checked';
                    this.confirmMessage = 'Apakah Anda yakin data ini sudah benar dan ingin melakukan konfirmasi "Checked"?';
                    this.confirmBtnText = 'Ya, Konfirmasi';
                    this.confirmBtnColor = 'bg-red-600';
                    this.confirmAction = () => {
                        this.showConfirmMain = false;
                        this.executeSave(); // Pindahkan logika simpan ke fungsi terpisah
                    };
                    this.showConfirmMain = true;
                    return;
                }

                // VALIDASI KHUSUS SUPERVISOR (BU NOVINA)
                if (this.isSupervisor && this.status === 'waiting_supervisor') {
                    if (!this.fmSig) {
                        this.showToast('error', 'Wajib diisi: Tanda Tangan Supervisor');
                        return;
                    }
                    
                    this.confirmTitle = 'Approve Final';
                    this.confirmMessage = 'Apakah Anda yakin ingin memberikan Approval Final untuk dokumen ini?';
                    this.confirmBtnText = 'Ya, Approve Final';
                    this.confirmBtnColor = 'bg-red-600';
                    this.confirmAction = () => {
                        this.showConfirmMain = false;
                        this.executeSave();
                    };
                    this.showConfirmMain = true;
                    return;
                }

                // VALIDASI KHUSUS OPERATOR QC
                // VALIDASI KHUSUS OPERATOR QC
                if (this.isOperator && this.isQCSectionOpen) {
                    // Validasi Shift
                    if (!this.shift) {
                        this.showToast('error', 'Wajib diisi: Shift belum dipilih!');
                        return;
                    }

                    // Validasi Total Produksi
                    if (!this.totalPcs || parseInt(this.totalPcs) <= 0) {
                        this.showToast('error', 'Wajib diisi: Total Produksi harus lebih dari 0!');
                        return;
                    }

                    // Validasi: pastikan semua kolom yang diperlukan sudah diisi (isColComplete)
                    const incompleteCols = this.cols.filter(col => !this.isColComplete(col));
                    if (incompleteCols.length > 0) {
                        this.showToast('error', `Belum lengkap! Mohon lengkapi data inspeksi untuk sample: ${incompleteCols.join(', ')}`);
                        return;
                    }

                    // Wajib pilih GL dan Foreman yang akan verifikasi
                    const validGl = this.glUsers.find(u => String(u.id) === String(this.assignedGlId));
                    if (!this.assignedGlId || String(this.assignedGlId).trim() === '' || !validGl) {
                        this.showToast('error', 'Wajib pilih Group Leader yang akan verifikasi!');
                        return;
                    }
                    const validFm = this.fmUsers.find(u => String(u.id) === String(this.assignedForemanId));
                    if (!this.assignedForemanId || String(this.assignedForemanId).trim() === '' || !validFm) {
                        this.showToast('error', 'Wajib pilih Foreman yang akan approval!');
                        return;
                    }

                    // Cek TTD Operator QC Ã¢â‚¬â€ wajib bundlePrepSig (TTD milik Operator sendiri)
                    // JANGAN cek prepSig karena itu TTD Leader (Totok), bukan Operator
                    if (!this.bundlePrepSig) {
                        this.showToast('error', 'Wajib TTD dahulu! Operator QC harus tanda tangan sebelum submit.');
                        return;
                    }

                    if (this.bundleFmSig) {
                        this.confirmTitle = 'Selesaikan & Kunci';
                        this.confirmMessage = 'Apakah Anda yakin semua hasil inspeksi sudah benar? Dokumen akan diselesaikan & dikunci langsung.';
                        this.confirmBtnText = 'Ya, Selesaikan & Kunci';
                    } else {
                        this.confirmTitle = 'Ajukan Verifikasi';
                        this.confirmMessage = 'Apakah Anda yakin semua data hasil inspeksi sudah benar? Dokumen akan diajukan ke GL dan Foreman untuk verifikasi.';
                        this.confirmBtnText = 'Ya, Ajukan Verifikasi';
                    }
                    this.confirmBtnColor = 'bg-red-600';
                    this.confirmAction = () => {
                        this.showConfirmMain = false;
                        this.executeSave();
                    };
                    this.showConfirmMain = true;
                    return;
                }

                // VALIDASI BOTTOM VERIFIKASI GL
                if (this.isGroupLeader && this.status === 'waiting_qc_approval') {
                    if (!this.bundleGlSig) {
                        this.showToast('error', 'Wajib diisi: Tanda Tangan GL (Checked) di bagian bawah!');
                        return;
                    }

                    this.confirmTitle = 'Verifikasi GL';
                    this.confirmMessage = 'Apakah Anda yakin ingin memverifikasi hasil inspeksi Operator QC ini?';
                    this.confirmBtnText = 'Ya, Verifikasi';
                    this.confirmBtnColor = 'bg-emerald-600';
                    this.confirmAction = () => {
                        this.showConfirmMain = false;
                        this.executeSave();
                    };
                    this.showConfirmMain = true;
                    return;
                }

                // VALIDASI BOTTOM VERIFIKASI FOREMAN
                if (this.isForeman && this.status === 'waiting_qc_approval') {
                    if (!this.bundleFmSig) {
                        this.showToast('error', 'Wajib diisi: Tanda Tangan Foreman (Approved) di bagian bawah!');
                        return;
                    }

                    this.confirmTitle = 'Verifikasi Foreman';
                    this.confirmMessage = 'Apakah Anda yakin ingin memverifikasi & menyelesaikan dokumen inspeksi ini?';
                    this.confirmBtnText = 'Ya, Selesaikan';
                    this.confirmBtnColor = 'bg-indigo-600';
                    this.confirmAction = () => {
                        this.showConfirmMain = false;
                        this.executeSave();
                    };
                    this.showConfirmMain = true;
                    return;
                }

                this.executeSave();
            },

            async executeSave() {
                this.saving = true;
                try {
                    // Khusus QC Foreman (Pak Dedy) verifikasi bottom Ã¢â‚¬â€ gunakan /sign endpoint
                    // agar tidak kena validasi prepared_paraf_bottom yg hanya untuk alur Operator-QC
                    if (this.isForeman && this.status === 'waiting_qc_approval') {
                        const signRes = await apiFetch(`/api/inspeksi/${this.editId}/sign`, {
                            method: 'POST',
                            body: JSON.stringify({ role: 'fm_bottom', signature: this.bundleFmSig })
                        });
                        localStorage.removeItem(`li_form_draft_${this.editId || 'new'}`);

                        // Tampilkan modal QPR jika dokumen ini memiliki temuan NG dan QPR terkait
                        const signQprId = signRes?.qpr_id ?? null;
                        if (signQprId) {
                            this.pendingQprId = signQprId;
                            this.showQprPrompt = true;
                            return;
                        }

                        this.showToast('success', 'Verifikasi QC selesai! Dokumen telah diselesaikan.');
                        setTimeout(() => window.location.href = APP_URL + '/li', 1000);
                        return;
                    }

                    let target = this.status;
                    if (this.isLeader && ['draft','revision'].includes(this.status)) target = 'waiting_foreman';
                    else if (this.isForeman && this.status === 'waiting_foreman') target = 'waiting_supervisor';
                    else if (this.isSupervisor && this.status === 'waiting_supervisor') target = 'ready_for_qc';
                    else if (this.isOperator && this.isQCSectionOpen) target = this.bundleFmSig ? 'finished' : 'waiting_qc_approval';
                    else if (this.isGroupLeader && this.status === 'waiting_qc_approval') target = 'waiting_qc_approval';

                    const payload = this.buildPayload(target);
                    let saveRes;
                    if (this.editId) saveRes = await apiFetch(`/api/inspeksi/${this.editId}`, { method: 'PUT', body: JSON.stringify(payload) });
                    else saveRes = await apiFetch('/api/inspeksi', { method: 'POST', body: JSON.stringify(payload) });

                    localStorage.removeItem(`li_form_draft_${this.editId || 'new'}`);

                    // Jika ada QPR yang di-generate otomatis (karena NG), tampilkan prompt urgent
                    const savedData = saveRes?.data ?? saveRes;
                    const autoQprId = savedData?.qpr_id ?? null;
                    // Tampilkan prompt saat dokumen selesai (finished) atau operator menyerahkan ke QC
                    const isTerminalSave = ['finished', 'waiting_qc_approval'].includes(target);
                    if (autoQprId && isTerminalSave) {
                        this.pendingQprId = autoQprId;
                        this.showQprPrompt = true;
                        return; // Jangan langsung redirect Ã¢â‚¬â€ tunggu respon operator
                    }

                    this.showToast('success', 'Berhasil disimpan!');
                    setTimeout(() => window.location.href = APP_URL + '/li', 1000);
                } catch (e) {
                    this.showToast('error', e.message);
                }
                finally { this.saving = false; }
            },
            // ==========================================
            // METHODS: SEARCH & AUTOFILL
            // ==========================================
            async loadFromHistory() {
                if (!this.isLeader) return;
                if (!this.jobNo && !this.partNo) return this.showToast('error', 'Ketik Job No / Part No dulu');
                this.searchType = 'history';
                this.searchQuery = this.jobNo || this.partNo;
                this.showSearchModal = true;
                try {
                    const res = await apiFetch(`/api/inspeksi/search?q=${this.searchQuery}`);
                    this.searchResults = res.data ?? res ?? [];
                } catch (e) { this.showToast('error', 'Gagal cari: ' + e.message); }
            },

            async selectSearchResult(item) {
                // Fill header fields
                // HANYA update Job No jika datanya benar-benar ada dan tidak kosong/strip
                const incomingJob = item.job_no || item.jobNo;
                if (incomingJob && incomingJob !== 'Ã¢â‚¬â€' && incomingJob !== '-') {
                    this.jobNo = incomingJob;
                    this.lastSelected.jobNo = incomingJob;
                }
                
                const pNo = item.part_no || item.partNo || this.partNo;
                if (pNo && pNo !== 'Ã¢â‚¬â€' && pNo !== '-') {
                    this.partNo = pNo;
                    this.lastSelected.partNo = pNo;
                }

                this.partName = item.part_name || item.partName || this.partName;
                this.partType = item.type || item.part_type || item.partType || this.partType;
                this.specMat = item.spec_material || item.spec_mat || item.specMat || this.specMat;
                this.typePallet = item.type_pallet || item.pallet || item.typePallet || this.typePallet;
                this.lokasi = item.lokasi || this.lokasi;

                if (item.tact_time       != null) this.tactTime       = parseFloat(item.tact_time)       || 0;
                if (item.ct_dimensi      != null) this.ctDimensi      = parseFloat(item.ct_dimensi)      || 0;
                if (item.ct_tanpa_dimensi != null) this.ctTanpaDimensi = parseFloat(item.ct_tanpa_dimensi) || 0;

                this.sketchUrl = null;
                this.showSearchModal = false;

                // Hapus draft lama agar tidak override data master yang baru dimuat
                const draftKey = `li_form_draft_${this.editId || 'new'}`;
                localStorage.removeItem(draftKey);
                console.log('[LI DEBUG] Draft cleared before autofill');

                const partToLoad = this.partNo || item.part_no || item.partNo;
                if (partToLoad) {
                    await this.loadTemplateByPartNo(true);
                    // Simpan draft langsung setelah autofill selesai
                    this.saveDraft();
                    console.log('[LI DEBUG] Draft saved after autofill');
                } else {
                    this.showToast('success', 'Data Histori berhasil dimuat!');
                }
            },




            triggerSearch(field) {
                if (!this.isLeader) return;
                if (this.showSearchModal) return;
                if (this.hasAutoSearched[field]) return;

                const val = this[field] || '';
                if (val === this.lastSelected[field]) return;

                if (val.length >= 1) {
                    this.hasAutoSearched[field] = true;
                    if (field === 'jobNo') {
                        this.loadFromHistory();
                    } else {
                        this.loadTemplateByPartNo();
                    }
                }
            },

            async saveTemplate() {
                if (!this.isLeader) return;
                if (!this.partNo) return this.showToast('error', 'Part No wajib diisi');
                this.saving = true;
                try {
                    const payload = {
                        part_no: this.partNo, part_name: this.partName, type: this.partType,
                        spec_material: this.specMat, type_pallet: this.typePallet
                    };
                    // Flatten Dimensi
                    for (let i = 0; i < 7; i++) {
                        const idx = i + 1;
                        payload[`dimensi${idx}_item`] = this.dimStd[i]?.item || '';
                        payload[`dimensi${idx}`] = this.dimStd[i]?.label || '';
                        payload[`dimensi${idx}_method`] = this.dimStd[i]?.method || '';
                    }
                    // Flatten Appearance
                    for (let i = 0; i < 9; i++) {
                        payload[`appearance${i+6}`] = this.appItems[i];
                    }
                    // Sampling formula
                    payload.tact_time        = this.tactTime       || null;
                    payload.ct_dimensi       = this.ctDimensi      || null;
                    payload.ct_tanpa_dimensi = this.ctTanpaDimensi || null;

                    await apiFetch('/api/li-templates', { method: 'POST', body: JSON.stringify(payload) });
                    this.showToast('success', 'Berhasil simpan ke Data Master');
                } catch (e) { this.showToast('error', e.message); }
                finally { this.saving = false; }
            },

            // ==========================================
            // METHODS: REVISIONS & ASSIGNMENTS
            // ==========================================
            toggleFieldRevision(key) {
                if (this.pendingFieldRevisions[key] !== undefined) {
                    delete this.pendingFieldRevisions[key];
                } else {
                    this.pendingFieldRevisions[key] = '';
                }
            },

            async submitAllRevisions() {
                if (Object.keys(this.pendingFieldRevisions).length === 0) {
                    this.showToast('error', 'Wajib tandai minimal satu field untuk direvisi!');
                    return;
                }

                this.confirmTitle = 'Kirim Balik ke Leader';
                this.confirmMessage = 'Apakah Anda yakin ingin mengirim semua catatan revisi ini kembali ke Leader?';
                this.confirmBtnText = 'Ya, Kirim Revisi';
                this.confirmBtnColor = 'bg-amber-500';
                this.confirmAction = () => {
                    this.showConfirmMain = false;
                    this.executeSubmitRevisions();
                };
                this.showConfirmMain = true;
            },

            async executeSubmitRevisions() {
                this.saving = true;
                try {
                    // Transform flat notes into backend expected structure: { field_revisions: { sketch: { catatan: '...' } } }
                    const formattedRevisions = {};
                    for (const [key, note] of Object.entries(this.pendingFieldRevisions)) {
                        formattedRevisions[key] = { catatan: note || '-' };
                    }

                    const payload = { field_revisions: formattedRevisions };
                    
                    await apiFetch(`/api/inspeksi/${this.editId}/field-revisions`, { 
                        method: 'POST', 
                        body: JSON.stringify(payload) 
                    });

                    this.showToast('success', 'Semua catatan revisi berhasil dikirim ke Leader');
                    setTimeout(() => window.location.href = APP_URL + '/li', 1000);
                } catch (e) { this.showToast('error', e.message); }
                finally { this.saving = false; }
            },

            confirmResolveRevision(key) {
                this.keyToResolve = key;
                this.showConfirmResolve = true;
            },

            async executeResolveRevision() {
                const key = this.keyToResolve;
                this.showConfirmResolve = false;
                
                try {
                    const res = await apiFetch(`/api/inspeksi/${this.editId}/resolve-revision`, {
                        method: 'POST',
                        body: JSON.stringify({
                            field: key,
                            resolved: true,
                            catatan: 'Diverifikasi oleh Leader'
                        })
                    });

                    if (this.fieldRevisions[key]) {
                        this.fieldRevisions[key].resolved = true;
                        this.fieldRevisions[key].resolved_at = new Date().toLocaleString();
                    }
                    
                    // Jika semua sudah resolved, status bisa balik ke submitted
                    if (res.status) this.status = res.status;

                    this.showToast('success', 'Berhasil diverifikasi & diperbaiki');
                } catch (e) {
                    this.showToast('error', 'Gagal verifikasi: ' + e.message);
                }
            },

            async updateAssignment() {
                if (!this.editId || !this.assignedOperatorId) return;
                try {
                    await apiFetch(`/api/inspeksi/${this.editId}/assign`, {
                        method: 'POST',
                        body: JSON.stringify({ assigned_operator_id: this.assignedOperatorId })
                    });
                    this.showToast('success', 'Operator berhasil ditugaskan');
                } catch (e) { this.showToast('error', e.message); }
            },

            async claimTask() {
                if (!this.editId) return;
                try {
                    const res = await apiFetch(`/api/inspeksi/${this.editId}/claim`, { method: 'POST' });
                    this.operatorClaimedAt = res.claimed_at;
                    this.showToast('success', 'Tugas berhasil diklaim');
                } catch (e) { this.showToast('error', e.message); }
            },

            // ==========================================
            // METHODS: BUNDLES & OTHER
            // ==========================================
            addMoreBundles() {
                const lastId = this.bundleChecks.length > 0 ? this.bundleChecks[this.bundleChecks.length - 1].id : 0;
                for (let i = 1; i <= 5; i++) {
                    this.bundleChecks.push({
                        id: lastId + i, bundleName: '', coilNo: '', judgement: 'OK',
                        samples: { '0':{},'1':{},'2':{},'3':{},'4':{},'5':{},'6':{} }
                    });
                }
                this.showToast('success', 'Berhasil tambah 5 bundle');
            },

            removeLast5Bundles() {
                if (this.bundleChecks.length <= 5) return;
                this.bundleChecks.splice(-5);
            },

            promptItemCheckRevision() {
                const note = prompt('Masukkan catatan revisi untuk Operator terkait Item Check ini:');
                if (!note) return;
                
                this.saving = true;
                this.bundlePrepSig = null; // Hapus TTD operator agar harus isi ulang
                
                // Tandai bahwa item check perlu revisi
                this.fieldRevisions = {
                    ...this.fieldRevisions,
                    item_check: { catatan: 'Revisi GL: ' + note, resolved: false }
                };
                
                // Ubah status kembali ke revision agar operator tahu ini ditolak
                const payload = this.buildPayload('revision');
                payload.field_revisions = this.fieldRevisions;
                payload.catatan_revisi = 'REVISI GL: ' + note;
                payload.paraf_qc = null; // hapus ttd operator di db agar harus ttd ulang
                payload.paraf_gl = null; // hapus ttd GL juga
                
                apiFetch(`/api/inspeksi/${this.editId}`, { method: 'PUT', body: JSON.stringify(payload) })
                    .then(() => {
                        this.showToast('success', 'Dikembalikan ke Operator untuk direvisi.');
                        setTimeout(() => window.location.href = APP_URL + '/li', 1000);
                    })
                    .catch(e => {
                        this.showToast('error', e.message);
                        this.saving = false;
                    });
            },

            addRevRecord() {
                this.revRecords.push({
                    date: new Date().toISOString().slice(0, 10),
                    record: '', approved: '', checked: '', prepared: this.userName || ''
                });
            },

            removeRevRecord(ri) {
                if (!this.isLeader || !this.canEditStandardSection) return;
                const rec = this.revRecords[ri];
                const hasText = (rec?.record || '').trim();
                if (hasText && !confirm('Hapus baris revisi ini?')) return;
                this.revRecords.splice(ri, 1);
                this.revRecords = [...this.revRecords];
                if (hasText) this.showToast('success', 'Baris revisi dihapus');
            },

            captureLeaderEditBaseline() {
                this._leaderEditBaseline = {
                    jobNo: this.jobNo,
                    partName: this.partName,
                    partNo: this.partNo,
                    partType: this.partType,
                    specMat: this.specMat,
                    typePallet: this.typePallet,
                    prosesRoute: this.prosesRoute,
                    lokasi: this.lokasi,
                    shift: this.shift,
                    tactTime: parseFloat(this.tactTime) || 0,
                    ctDimensi: parseFloat(this.ctDimensi) || 0,
                    ctTanpaDimensi: parseFloat(this.ctTanpaDimensi) || 0,
                    samplingFormulaMode: this.samplingFormulaMode || 'auto',
                    holeStandard: String(this.holeStandard ?? ''),
                    dimStdJson: JSON.stringify(this.dimStd),
                    appItemsJson: JSON.stringify(this.appItems),
                    sketch: this.sketchUrl ? 'ada' : 'Ã¢â‚¬â€',
                };
            },
            captureSamplingBaseline() { this.captureLeaderEditBaseline(); },

            _leaderFieldValue(field) {
                if (field === 'samplingFormulaMode') return this.samplingFormulaMode;
                if (['tactTime', 'ctDimensi', 'ctTanpaDimensi', 'holeStandard'].includes(field)) {
                    return String(this[field] ?? '');
                }
                return this[field];
            },

            scheduleLeaderRevisionFlush() {
                if (this._leaderRevisionFlushTimer) clearTimeout(this._leaderRevisionFlushTimer);
                this._leaderRevisionFlushTimer = setTimeout(() => this.flushLeaderRevisionQueue(), 1200);
            },

            queueLeaderRevision(field, label, newVal) {
                if (!this.shouldLogLeaderRevision || !this._leaderEditBaseline) return;
                const toStr = newVal === '' || newVal == null ? '-' : String(newVal);
                const fromStr = this._leaderRevisionQueue[field]
                    ? this._leaderRevisionQueue[field].from
                    : (this._leaderEditBaseline[field] === '' || this._leaderEditBaseline[field] == null
                        ? '-' : String(this._leaderEditBaseline[field]));
                if (fromStr === toStr) {
                    delete this._leaderRevisionQueue[field];
                    return;
                }
                this._leaderRevisionQueue[field] = { label: label || field, from: fromStr, to: toStr };
                this.scheduleLeaderRevisionFlush();
            },

            flushLeaderRevisionQueue() {
                this._leaderRevisionFlushTimer = null;
                const keys = Object.keys(this._leaderRevisionQueue || {});
                if (!keys.length) return;

                const parts = keys.map((k) => {
                    const q = this._leaderRevisionQueue[k];
                    if (q.from === q.to || (q.from === '-' && q.to === '-')) return null;
                    return `${q.label}: ${q.from} -> ${q.to}`;
                }).filter(Boolean);
                if (!parts.length) {
                    this._leaderRevisionQueue = {};
                    return;
                }
                const text = `[Leader QA] Koreksi ${parts.join('; ')}`;
                const dup = this.revRecords.some((r) => (r.record || '').trim() === text);
                if (!dup) {
                    this.revRecords.push({
                        date: new Date().toISOString().slice(0, 10),
                        record: text,
                        approved: '',
                        checked: '',
                        prepared: this.userName || 'Leader QA',
                    });
                    this.showToast('success', 'Perubahan dicatat di Revision Record (1 baris)');
                }

                keys.forEach((k) => {
                    if (this._leaderEditBaseline && k in this._leaderEditBaseline) {
                        this._leaderEditBaseline[k] = this._leaderRevisionQueue[k].to === '-' ? '' : this._leaderRevisionQueue[k].to;
                    }
                });
                this._leaderRevisionQueue = {};
            },

            appendLeaderRevisionRecord(label, fromVal, toVal) {
                this.flushLeaderRevisionQueue();
                const fromStr = fromVal === '' || fromVal == null ? '—' : String(fromVal);
                const toStr = toVal === '' || toVal == null ? '—' : String(toVal);
                if (fromStr === toStr) return;
                const text = `[Leader QA] Koreksi ${label}: ${fromStr} → ${toStr}`;
                const dup = this.revRecords.some((r) => (r.record || '').trim() === text);
                if (dup) return;
                this.revRecords.push({
                    date: new Date().toISOString().slice(0, 10),
                    record: text,
                    approved: '',
                    checked: '',
                    prepared: this.userName || 'Leader QA',
                });
            },
            appendLeaderSamplingRevision(label, fromVal, toVal) {
                this.appendLeaderRevisionRecord(label, fromVal, toVal);
            },

            onLeaderFieldChange(field, label) {
                if (['tactTime', 'ctDimensi', 'ctTanpaDimensi', 'samplingFormulaMode'].includes(field)) {
                    this.cols = this.rebuildCols();
                }
                if (!this.shouldLogLeaderRevision || !this._leaderEditBaseline) return;
                const newVal = this._leaderFieldValue(field);
                const original = this._leaderEditBaseline[field];
                const originalStr = original === '' || original == null ? '-' : String(original);
                const newStr = newVal === '' || newVal == null ? '-' : String(newVal);
                if (originalStr === newStr) {
                    delete this._leaderRevisionQueue[field];
                    return;
                }

                this._leaderRevisionQueue[field] = { label, from: originalStr, to: newStr };

                clearTimeout(this._leaderEditDebounce);
                this._leaderEditDebounce = setTimeout(() => {
                    this.flushLeaderRevisionQueue();
                    
                    this.$nextTick(() => {
                        const revSection = document.getElementById('revision-record-section');
                        if (revSection) {
                            revSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            
                            setTimeout(() => {
                                const newRow = revSection.querySelector('tbody tr:last-child');
                                if (newRow) {
                                    newRow.classList.add('bg-blue-100', 'transition-colors', 'duration-500');
                                    setTimeout(() => {
                                        newRow.classList.remove('bg-blue-100');
                                    }, 2000);
                                }
                            }, 500);
                        }
                    });
                }, 1000);
            },

            onSamplingFieldChange(field) {
                const labels = {
                    tactTime: 'TT per pcs (detik)',
                    ctDimensi: 'CT Check Dimensi (detik)',
                    ctTanpaDimensi: 'CT Tanpa Dimensi (detik)',
                    samplingFormulaMode: 'Metode rumus sampling',
                };
                this.onLeaderFieldChange(field, labels[field] || field);
            },

            clearSketchWithRevision() {
                if (this.shouldLogLeaderRevision && this._leaderEditBaseline?.sketch === 'ada') {
                    this.appendLeaderRevisionRecord('Sketsa', 'ada', 'Ã¢â‚¬â€');
                    this._leaderEditBaseline.sketch = 'Ã¢â‚¬â€';
                }
                this.sketchUrl = null;
            },

            handleSketch(e) {
                const file = e.target.files[0];
                if (!file) return;

                // 1. Cek Ukuran (Batas 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    this.showToast('error', 'File terlalu besar! Maksimal 2MB.');
                    e.target.value = '';
                    return;
                }

                // 2. Baca & Kompres Otomatis (Resize ke max 1200px)
                const reader = new FileReader();
                reader.onload = (f) => {
                    const img = new Image();
                    img.src = f.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        const maxDim = 1200;

                        if (width > maxDim || height > maxDim) {
                            if (width > height) {
                                height *= maxDim / width;
                                width = maxDim;
                            } else {
                                width *= maxDim / height;
                                height = maxDim;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        // Simpan sebagai JPEG kualitas 70% (Sangat hemat memori)
                        this.sketchUrl = canvas.toDataURL('image/jpeg', 0.7);
                        if (this.shouldLogLeaderRevision && this._leaderEditBaseline) {
                            const before = this._leaderEditBaseline.sketch;
                            if (before !== 'ada') {
                                this.appendLeaderRevisionRecord('Sketsa', before, 'ada');
                                this._leaderEditBaseline.sketch = 'ada';
                            }
                        }
                    };
                };
                reader.readAsDataURL(file);
            },

            async handleSaveDraft(isSilent = false) {
                this.flushLeaderRevisionQueue();
                if (!isSilent) this.savingDraft = true;
                this.saveDraft(); // Tetap simpan ke LocalStorage sebagai backup
                try {
                    const isEditing = !!this.editId;
                    const payload = this.buildPayload(this.status || 'draft');
                    if (isEditing) {
                        // Update record yang sudah ada
                        await apiFetch(`/api/inspeksi/${this.editId}`, { method: 'PUT', body: JSON.stringify(payload) });
                    } else {
                        // Buat record baru sebagai draft di server
                        const res = await apiFetch('/api/inspeksi', { method: 'POST', body: JSON.stringify(payload) });
                        if (res.id) {
                            this.editId = res.id;
                            // Update URL tanpa reload agar user tahu ini sudah jadi record tetap
                            const newUrl = window.location.pathname + '?id=' + res.id;
                            window.history.pushState({ path: newUrl }, '', newUrl);
                        }
                    }
                    if (!isSilent) this.showToast('success', isEditing ? 'Hasil edit berhasil diamankan ke server' : 'Draft berhasil diamankan ke server');
                } catch (e) { 
                    if (!isSilent) this.showToast('error', (this.editId ? 'Gagal menyimpan hasil edit: ' : 'Draft gagal ke server: ') + e.message); 
                } finally { 
                    if (!isSilent) this.savingDraft = false; 
                }
            },

            async handleSendRevisi() {
                this.saving = true;
                try {
                    const payload = this.buildPayload('draft');
                    await apiFetch(`/api/inspeksi/${this.editId}`, { method: 'PUT', body: JSON.stringify(payload) });
                    this.showToast('success', 'Berhasil dikirim balik ke Leader');
                    setTimeout(() => window.location.href = APP_URL + '/li', 1000);
                } catch (e) { this.showToast('error', e.message); }
                finally { this.saving = false; }
            },
            // ==========================================
            // VISUAL DEFECT MAPPING (SKETCH EDITOR) - SVG ENGINE
            // ==========================================
            svgGenId() {
                return Math.random().toString(36).slice(2, 9);
            },
            openBlankSketchEditor(type) {
                this.svgBgImage = type; // 'blank_a4_landscape' or 'blank_a4_portrait'
                this.svgShapes = [];
                this.svgSelected = null;
                this.svgZoneCount = 0;
                this.showSketchEditor = true;
                this.showSketchChoiceModal = false;
                this.svgDrawing = null;
                this.svgDragState = null;
                this.svgConnState = null;
            },
            openSketchEditor() {
                if (this.sketchUrl && !this.svgBgImage) {
                    this.svgBgImage = this.sketchUrl;
                }
                this.showSketchEditor = true;
                this.svgDrawing = null;
                this.svgDragState = null;
                this.svgConnState = null;
            },
            closeSketchEditor() {
                this.showSketchEditor = false;
                this.svgDragState = null;
                this.svgResizeState = null;
                this.svgConnState = null;
                this.svgDrawing = null;
            },
            svgGetPorts(s) {
                if (s.type === 'circle') {
                    const cx = s.x + s.w / 2, cy = s.y + s.h / 2;
                    return {
                        top:    { x: cx, y: s.y },
                        right:  { x: s.x + s.w, y: cy },
                        bottom: { x: cx, y: s.y + s.h },
                        left:   { x: s.x, y: cy },
                    };
                }
                return {
                    top:    { x: s.x + s.w / 2, y: s.y },
                    right:  { x: s.x + s.w, y: s.y + s.h / 2 },
                    bottom: { x: s.x + s.w / 2, y: s.y + s.h },
                    left:   { x: s.x, y: s.y + s.h / 2 },
                };
            },
            svgGetPortNear(pt, excludeId = null, onlySelected = false) {
                for (const s of this.svgShapes) {
                    if (s.id === excludeId || s.type === 'arrow' || s.type === 'line' || s.type === 'text' || s.type === 'image') continue;
                    if (onlySelected && this.svgSelected !== s.id) continue;
                    const ports = this.svgGetPorts(s);
                    for (const [name, pos] of Object.entries(ports)) {
                        const dx = pt.x - pos.x, dy = pt.y - pos.y;
                        if (Math.sqrt(dx * dx + dy * dy) < 14) {
                            return { shapeId: s.id, port: name, x: pos.x, y: pos.y };
                        }
                    }
                }
                return null;
            },
            svgGetPt(e) {
                const svg = this.$refs.svgCanvas;
                if (!svg) return { x: 0, y: 0 };
                const rect = svg.getBoundingClientRect();
                const scaleX = this.svgCanvasW / rect.width;
                const scaleY = this.svgCanvasH / rect.height;
                const src = (e.touches && e.touches[0]) ? e.touches[0] : e;
                return { x: (src.clientX - rect.left) * scaleX, y: (src.clientY - rect.top) * scaleY };
            },
            svgPtNearSeg(pt, x1, y1, x2, y2) {
                const dx = x2 - x1, dy = y2 - y1;
                const len = Math.sqrt(dx * dx + dy * dy);
                if (len === 0) return false;
                const t = Math.max(0, Math.min(1, ((pt.x - x1) * dx + (pt.y - y1) * dy) / (len * len)));
                return Math.sqrt((pt.x - (x1 + t * dx)) ** 2 + (pt.y - (y1 + t * dy)) ** 2) < 12;
            },
            getSvgLinePath(s) {
                const { x1, y1, x2, y2 } = this.getSvgLineCoords(s);
                const type = s.connectionType || 'straight';
                const waypoints = s.waypoints || [];
                // If waypoints exist, always polyline through all points
                if (waypoints.length > 0) {
                    let d = `M ${x1} ${y1}`;
                    for (const wp of waypoints) d += ` L ${wp.x} ${wp.y}`;
                    d += ` L ${x2} ${y2}`;
                    return d;
                }
                if (type === 'orthogonal') {
                    let horizFirst = true;
                    if (s.fromPort === 'top' || s.fromPort === 'bottom') horizFirst = false;
                    if (horizFirst) {
                        const mx = (x1 + x2) / 2;
                        return `M ${x1} ${y1} H ${mx} V ${y2} H ${x2}`;
                    } else {
                        const my = (y1 + y2) / 2;
                        return `M ${x1} ${y1} V ${my} H ${x2} V ${y2}`;
                    }
                } else if (type === 'curved') {
                    let horizFirst = true;
                    if (s.fromPort === 'top' || s.fromPort === 'bottom') horizFirst = false;
                    if (horizFirst) {
                        const mx = (x1 + x2) / 2;
                        return `M ${x1} ${y1} C ${mx} ${y1}, ${mx} ${y2}, ${x2} ${y2}`;
                    } else {
                        const my = (y1 + y2) / 2;
                        return `M ${x1} ${y1} C ${x1} ${my}, ${x2} ${my}, ${x2} ${y2}`;
                    }
                }
                return `M ${x1} ${y1} L ${x2} ${y2}`;
            },
            svgHitTest(s, pt) {
                if (s.type === 'arrow' || s.type === 'line') {
                    const allPts = this.getSvgAllPoints(s);
                    for (let i = 0; i < allPts.length - 1; i++) {
                        if (this.svgPtNearSeg(pt, allPts[i].x, allPts[i].y, allPts[i+1].x, allPts[i+1].y)) return true;
                    }
                    return false;
                }
                return pt.x >= s.x && pt.x <= s.x + s.w && pt.y >= s.y && pt.y <= s.y + s.h;
            },
            svgGetResizeCorner(pt) {
                if (!this.svgSelected) return null;
                const s = this.svgShapes.find(sh => sh.id === this.svgSelected);
                if (!s || ['line', 'arrow'].includes(s.type)) return null;
                const corners = {
                    nw: { cx: s.x,      cy: s.y      },
                    ne: { cx: s.x+s.w,  cy: s.y      },
                    sw: { cx: s.x,      cy: s.y+s.h  },
                    se: { cx: s.x+s.w,  cy: s.y+s.h  },
                };
                for (const [name, pos] of Object.entries(corners)) {
                    const dx = pt.x - pos.cx, dy = pt.y - pos.cy;
                    if (Math.sqrt(dx*dx + dy*dy) < 16) {
                        return { id: s.id, corner: name, origX: s.x, origY: s.y, origW: s.w, origH: s.h, origFontSize: s.fontSize || 14 };
                    }
                }
                return null;
            },
            svgGetSelShape() {
                if (!this.svgSelected) return null;
                return this.svgShapes.find(s => s.id === this.svgSelected) || null;
            },
            svgGetTextWidth(text, fontSize) {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                ctx.font = `bold ${fontSize}px sans-serif`;
                // Support multiline: return width of the widest line
                const lines = (text || '').split('\n');
                return Math.max(10, ...lines.map(l => ctx.measureText(l).width));
            },
            svgUpdateSelProp(key, val) {
                const s = this.svgGetSelShape();
                if (s) {
                    s[key] = val;
                    if (s.type === 'text' && (key === 'label' || key === 'fontSize')) {
                        const lines = (s.label || '').split('\n');
                        s.w = Math.max(10, this.svgGetTextWidth(s.label, s.fontSize));
                        s.h = s.fontSize * 1.4 * lines.length;  // 1.4 = line-height ratio
                    }
                    this.svgShapes = [...this.svgShapes];
                }
            },
            svgDeleteSelected() {
                if (!this.svgSelected) return;
                this.svgShapes = this.svgShapes.filter(s => s.id !== this.svgSelected && s.fromId !== this.svgSelected && s.toId !== this.svgSelected);
                this.svgSelected = null;
            },
            svgSetTool(t) {
                this.svgTool = t;
                if (t !== 'select') { this.svgSelected = null; this.svgEditingText = null; }
            },
            handleSvgMouseDown(e) {
                if (e.button !== undefined && e.button !== 0) return;
                const pt = this.svgGetPt(e);
                // Check resize corner FIRST when in select mode
                if (this.svgTool === 'select' && this.svgSelected) {
                    const rz = this.svgGetResizeCorner(pt);
                    if (rz) { this.svgResizeState = rz; return; }
                }
                // Check line endpoint & waypoint handles on ANY line/arrow FIRST
                if (this.svgTool === 'select') {
                    const HIT = 20;
                    for (let i = this.svgShapes.length - 1; i >= 0; i--) {
                        const s = this.svgShapes[i];
                        if (s.type !== 'line' && s.type !== 'arrow') continue;
                        const allPts = this.getSvgAllPoints(s);
                        const isSelected = this.svgSelected === s.id;
                        // Check start endpoint
                        if (Math.sqrt((pt.x-allPts[0].x)**2+(pt.y-allPts[0].y)**2) < HIT) {
                            this.svgSelected = s.id; this.svgEditingText = null;
                            this.svgLineDragEndpoint = { id: s.id, type: 'start' };
                            return;
                        }
                        // Check end endpoint
                        const last = allPts[allPts.length-1];
                        if (Math.sqrt((pt.x-last.x)**2+(pt.y-last.y)**2) < HIT) {
                            this.svgSelected = s.id; this.svgEditingText = null;
                            this.svgLineDragEndpoint = { id: s.id, type: 'end' };
                            return;
                        }
                        // Waypoint handles & midpoint add-handles (only if selected)
                        if (isSelected) {
                            // Check existing waypoint handles
                            const wps = s.waypoints || [];
                            for (let wi = 0; wi < wps.length; wi++) {
                                if (Math.sqrt((pt.x-wps[wi].x)**2+(pt.y-wps[wi].y)**2) < HIT) {
                                    this.svgLineDragEndpoint = { id: s.id, type: 'waypoint', index: wi };
                                    return;
                                }
                            }
                            // Check segment midpoints (click = insert waypoint)
                            for (let si = 0; si < allPts.length-1; si++) {
                                const mx = (allPts[si].x+allPts[si+1].x)/2;
                                const my = (allPts[si].y+allPts[si+1].y)/2;
                                if (Math.sqrt((pt.x-mx)**2+(pt.y-my)**2) < HIT) {
                                    if (!s.waypoints) s.waypoints = [];
                                    s.waypoints.splice(si, 0, { x: mx, y: my });
                                    this.svgShapes = [...this.svgShapes];
                                    this.svgLineDragEndpoint = { id: s.id, type: 'waypoint', index: si };
                                    return;
                                }
                            }
                        }
                    }
                }
                if (this.svgTool === 'text') {
                    const id = this.svgGenId();
                    const initW = Math.max(10, this.svgGetTextWidth('TEKS', 14));
                    this.svgShapes.push({ id, type: 'text', x: pt.x - 10, y: pt.y - 12, w: initW, h: 14 * 1.2, label: 'TEKS', color: this.svgColor, fontSize: 14 });
                    this.svgSelected = id; this.svgEditingText = id; this.svgTextVal = 'TEKS';
                    this.svgTool = 'select';
                    setTimeout(() => { const el = document.getElementById('svgTextInput'); if (el) { el.focus(); el.select(); } }, 50);
                    return;
                }
                if (this.svgTool === 'select' || this.svgTool === 'delete') {
                    const port = this.svgGetPortNear(pt, null, true);
                    if (port && this.svgTool === 'select') {
                        this.svgConnState = { fromId: port.shapeId, fromPort: port.port, x1: port.x, y1: port.y, x2: pt.x, y2: pt.y };
                        return;
                    }
                    let hit = null;
                    for (let i = this.svgShapes.length - 1; i >= 0; i--) {
                        if (this.svgHitTest(this.svgShapes[i], pt)) { hit = this.svgShapes[i]; break; }
                    }
                    if (this.svgTool === 'delete' && hit) {
                        this.svgShapes = this.svgShapes.filter(s => s.id !== hit.id && s.fromId !== hit.id && s.toId !== hit.id);
                        this.svgSelected = null; return;
                    }
                    if (hit) {
                        const wasAlreadySelected = (this.svgSelected === hit.id);
                        this.svgSelected = hit.id;
                        if (hit.type === 'text') {
                            this.svgEditingText = hit.id; this.svgTextVal = hit.label || '';
                            if (wasAlreadySelected) {
                                setTimeout(() => { const el = document.getElementById('svgTextInput'); if (el) { el.focus(); el.select(); } }, 50);
                            }
                        } else { this.svgEditingText = null; }
                        if (hit.type === 'line' || hit.type === 'arrow') {
                            this.svgDragState = {
                                id: hit.id,
                                ox1: pt.x - hit.x1,
                                oy1: pt.y - hit.y1,
                                ox2: pt.x - hit.x2,
                                oy2: pt.y - hit.y2
                            };
                        } else {
                            this.svgDragState = { id: hit.id, ox: pt.x - hit.x, oy: pt.y - hit.y };
                        }
                    } else { this.svgSelected = null; this.svgEditingText = null; }
                    return;
                }
                if (this.svgTool === 'zone') {
                    this.svgZoneCount++;
                    this.svgDrawing = { type: 'zone', x1: pt.x, y1: pt.y, x2: pt.x, y2: pt.y, zoneNum: this.svgZoneCount, color: this.svgColor };
                } else if (['rect', 'circle', 'line', 'arrow'].includes(this.svgTool)) {
                    this.svgDrawing = { type: this.svgTool, x1: pt.x, y1: pt.y, x2: pt.x, y2: pt.y, color: this.svgColor };
                }
            },
            handleSvgMouseMove(e) {
                const pt = this.svgGetPt(e);
                this.svgHoverPort = (this.svgTool === 'select') ? this.svgGetPortNear(pt) : null;
                // Handle resize drag
                if (this.svgResizeState) {
                    const rz = this.svgResizeState;
                    const shape = this.svgShapes.find(s => s.id === rz.id);
                    if (shape) {
                        const MIN = 20;
                        if (shape.type === 'text') {
                            let newH = rz.origH;
                            if (rz.corner === 'se' || rz.corner === 'sw') {
                                newH = Math.max(MIN, pt.y - rz.origY);
                            } else if (rz.corner === 'ne' || rz.corner === 'nw') {
                                newH = Math.max(MIN, (rz.origY + rz.origH) - pt.y);
                            }
                            const scale = newH / rz.origH;
                            const newFontSize = Math.max(8, Math.min(120, Math.round(rz.origFontSize * scale)));
                            shape.fontSize = newFontSize;
                            
                            const lines = (shape.label || '').split('\n');
                            shape.w = Math.max(10, this.svgGetTextWidth(shape.label, shape.fontSize));
                            shape.h = shape.fontSize * 1.4 * lines.length;
                            
                            if (rz.corner === 'sw' || rz.corner === 'nw') {
                                shape.x = rz.origX + rz.origW - shape.w;
                            }
                            if (rz.corner === 'ne' || rz.corner === 'nw') {
                                shape.y = rz.origY + rz.origH - shape.h;
                            }
                        } else {
                            if (rz.corner === 'se') {
                                shape.w = Math.max(MIN, pt.x - rz.origX);
                                shape.h = Math.max(MIN, pt.y - rz.origY);
                            } else if (rz.corner === 'sw') {
                                shape.w = Math.max(MIN, (rz.origX + rz.origW) - pt.x);
                                shape.x = Math.min(pt.x, rz.origX + rz.origW - MIN);
                                shape.h = Math.max(MIN, pt.y - rz.origY);
                            } else if (rz.corner === 'ne') {
                                shape.w = Math.max(MIN, pt.x - rz.origX);
                                shape.h = Math.max(MIN, (rz.origY + rz.origH) - pt.y);
                                shape.y = Math.min(pt.y, rz.origY + rz.origH - MIN);
                            } else if (rz.corner === 'nw') {
                                shape.w = Math.max(MIN, (rz.origX + rz.origW) - pt.x);
                                shape.h = Math.max(MIN, (rz.origY + rz.origH) - pt.y);
                                shape.x = Math.min(pt.x, rz.origX + rz.origW - MIN);
                                shape.y = Math.min(pt.y, rz.origY + rz.origH - MIN);
                            }
                        }
                        this.svgShapes = [...this.svgShapes];
                    }
                    return;
                }
                // Handle line endpoint/waypoint drag
                if (this.svgLineDragEndpoint) {
                    const ld = this.svgLineDragEndpoint;
                    const shape = this.svgShapes.find(s => s.id === ld.id);
                    if (shape) {
                        if (ld.type === 'waypoint') {
                            if (!shape.waypoints) shape.waypoints = [];
                            shape.waypoints[ld.index] = { x: pt.x, y: pt.y };
                        } else {
                            const snap = this.svgGetPortNear(pt, shape.id);
                            if (ld.type === 'start') {
                                if (snap) { shape.fromId = snap.shapeId; shape.fromPort = snap.port; shape.x1 = snap.x; shape.y1 = snap.y; }
                                else { shape.fromId = null; shape.fromPort = null; shape.x1 = pt.x; shape.y1 = pt.y; }
                            } else {
                                if (snap) { shape.toId = snap.shapeId; shape.toPort = snap.port; shape.x2 = snap.x; shape.y2 = snap.y; }
                                else { shape.toId = null; shape.toPort = null; shape.x2 = pt.x; shape.y2 = pt.y; }
                            }
                        }
                        this.svgShapes = [...this.svgShapes];
                    }
                    return;
                }
                if (this.svgConnState) {
                    const snap = this.svgGetPortNear(pt, this.svgConnState.fromId);
                    this.svgConnState = { ...this.svgConnState, x2: snap ? snap.x : pt.x, y2: snap ? snap.y : pt.y, toSnap: snap };
                    return;
                }
                if (this.svgDrawing) { this.svgDrawing = { ...this.svgDrawing, x2: pt.x, y2: pt.y }; return; }
                if (this.svgDragState) {
                    const shape = this.svgShapes.find(s => s.id === this.svgDragState.id);
                    if (shape) {
                        if (shape.type === 'line' || shape.type === 'arrow') {
                            if (!shape.fromId) {
                                shape.x1 = pt.x - this.svgDragState.ox1;
                                shape.y1 = pt.y - this.svgDragState.oy1;
                            }
                            if (!shape.toId) {
                                shape.x2 = pt.x - this.svgDragState.ox2;
                                shape.y2 = pt.y - this.svgDragState.oy2;
                            }
                        } else {
                            shape.x = Math.max(0, pt.x - this.svgDragState.ox);
                            shape.y = Math.max(0, pt.y - this.svgDragState.oy);
                        }
                        this.svgShapes = [...this.svgShapes];
                    }
                }
            },
            handleSvgMouseUp(e) {
                const pt = this.svgGetPt(e);
                if (this.svgConnState) {
                    const snap = this.svgGetPortNear(pt, this.svgConnState.fromId);
                    if (snap || Math.sqrt((pt.x - this.svgConnState.x1) ** 2 + (pt.y - this.svgConnState.y1) ** 2) > 10) {
                        const id = this.svgGenId();
                        this.svgShapes.push({
                            id, type: 'arrow',
                            x: 0, y: 0, w: 0, h: 0,
                            x1: this.svgConnState.x1, y1: this.svgConnState.y1,
                            x2: snap ? snap.x : pt.x, y2: snap ? snap.y : pt.y,
                            fromId: this.svgConnState.fromId, fromPort: this.svgConnState.fromPort,
                            toId: snap ? snap.shapeId : null, toPort: snap ? snap.port : null,
                            color: this.svgColor,
                            connectionType: 'orthogonal',
                            dashStyle: 'solid',
                            arrowStart: false,
                            arrowEnd: true
                        });
                    }
                    this.svgConnState = null; return;
                }
                if (this.svgDrawing) {
                    const { type, x1, y1, x2, y2, zoneNum, color } = this.svgDrawing;
                    const id = this.svgGenId();
                    if (type === 'line' || type === 'arrow') {
                        if (Math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2) > 5) {
                            this.svgShapes.push({
                                id, type, x: 0, y: 0, w: 0, h: 0,
                                x1, y1, x2, y2, color,
                                waypoints: [],
                                connectionType: 'straight',
                                dashStyle: 'solid',
                                arrowStart: false,
                                arrowEnd: type === 'arrow'
                            });
                        }
                    } else {
                        const x = Math.min(x1, x2), y = Math.min(y1, y2), w = Math.abs(x2 - x1), h = Math.abs(y2 - y1);
                        if (w > 4 && h > 4) {
                            const label = type === 'zone' ? String(this.cols[zoneNum - 1] || zoneNum) : '';
                            this.svgShapes.push({ id, type, x, y, w, h, color, label, fontSize: type === 'zone' ? 20 : 13 });
                            this.svgSelected = id;
                        } else if (type === 'zone') { this.svgZoneCount--; }
                    }
                    this.svgDrawing = null;
                    if (this.svgTool !== 'zone') this.svgTool = 'select';
                }
                this.svgDragState = null;
                this.svgResizeState = null;
                this.svgLineDragEndpoint = null;
                this.svgLineRotateState = null;
            },
            handleSvgBgUpload(e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = ev => { this.svgBgImage = ev.target.result; };
                reader.readAsDataURL(file);
                e.target.value = '';
            },
            handleSvgImageUpload(e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = ev => {
                    const id = this.svgGenId();
                    this.svgShapes.push({ id, type: 'image', x: 80, y: 80, w: 200, h: 150, src: ev.target.result, color: '#000', label: '' });
                    this.svgSelected = id; this.svgTool = 'select';
                };
                reader.readAsDataURL(file);
                e.target.value = '';
            },
            svgRotateImage() {
                const s = this.svgGetSelShape();
                if (!s || s.type !== 'image' || !s.src) return;
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.height;
                    canvas.height = img.width;
                    const ctx = canvas.getContext('2d');
                    ctx.translate(canvas.width / 2, canvas.height / 2);
                    ctx.rotate(90 * Math.PI / 180);
                    ctx.drawImage(img, -img.width / 2, -img.height / 2);
                    s.src = canvas.toDataURL();
                    const oldW = s.w;
                    const oldH = s.h;
                    s.w = oldH;
                    s.h = oldW;
                    s.x = s.x + (oldW - s.w) / 2;
                    s.y = s.y + (oldH - s.h) / 2;
                };
                img.src = s.src;
            },
            svgClearAll() {
                if (confirm('Hapus semua elemen di canvas?')) {
                    this.svgShapes = []; this.svgSelected = null; this.svgZoneCount = 0; this.svgEditingText = null;
                }
            },
            saveSketchAnnotation() {
                this.svgSelected = null;
                this.svgHoverPort = null;
                this.svgDrawing = null;
                this.svgEditingText = null;
                setTimeout(() => {
                    try {
                        // Build SVG string directly from state Ã¢â‚¬â€ more reliable than XMLSerializer
                        // XMLSerializer can miss xmlns, width/height attrs needed for <img src> rendering
                        const w = this.svgCanvasW;
                        const h = this.svgCanvasH;
                        const inner = this.svgRenderAll(); // svgSelected is already null, so no handles
                        const svgStr = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">${inner}</svg>`;
                        const encoded = btoa(unescape(encodeURIComponent(svgStr)));
                        this.sketchUrl = 'data:image/svg+xml;base64,' + encoded;
                        this.showToast('success', 'Anotasi sketch berhasil disimpan!');
                        this.closeSketchEditor();
                        this.saveDraft();
                    } catch(err) {
                        this.showToast('error', 'Gagal simpan sketch: ' + err.message);
                    }
                }, 80);
            },
            getSvgLineCoords(s) {
                let x1 = s.x1 || 0, y1 = s.y1 || 0, x2 = s.x2 || 0, y2 = s.y2 || 0;
                if (s.fromId) {
                    const src = this.svgShapes.find(x => x.id === s.fromId);
                    if (src) { const p = this.svgGetPorts(src)[s.fromPort]; if (p) { x1 = p.x; y1 = p.y; } }
                }
                if (s.toId) {
                    const tgt = this.svgShapes.find(x => x.id === s.toId);
                    if (tgt) { const p = this.svgGetPorts(tgt)[s.toPort]; if (p) { x2 = p.x; y2 = p.y; } }
                }
                return { x1, y1, x2, y2 };
            },
            getSvgAllPoints(s) {
                const { x1, y1, x2, y2 } = this.getSvgLineCoords(s);
                return [{ x: x1, y: y1 }, ...(s.waypoints || []), { x: x2, y: y2 }];
            },
            svgRenderAll() {
                let html = '';

                // 0. Collect unique colors from line/arrow shapes and emit per-color markers
                const arrowColors = new Set(
                    this.svgShapes
                        .filter(s => s.type === 'line' || s.type === 'arrow')
                        .map(s => s.color || '#ef4444')
                );
                const colorId = c => c.replace(/[^a-zA-Z0-9]/g, '_');
                let defs = '<defs>';
                for (const c of arrowColors) {
                    const id = colorId(c);
                    defs += `<marker id="ah-end-${id}" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto" markerUnits="strokeWidth"><path d="M0,0 L8,3 L0,6 Z" fill="${c}" /></marker>`;
                    defs += `<marker id="ah-start-${id}" markerWidth="8" markerHeight="6" refX="1" refY="3" orient="auto" markerUnits="strokeWidth"><path d="M8,0 L0,3 L8,6 Z" fill="${c}" /></marker>`;
                }
                defs += '</defs>';
                html += defs;

                // 1. Background Image
                if (this.svgBgImage === 'blank_a4_landscape') {
                    // 800x565 centered in 900x580
                    html += `<rect x="50" y="7.5" width="800" height="565" fill="#ffffff" rx="8" stroke="#cbd5e1" stroke-width="2" />`;
                } else if (this.svgBgImage === 'blank_a4_portrait') {
                    // 396x560 centered in 900x580
                    html += `<rect x="252" y="10" width="396" height="560" fill="#ffffff" rx="8" stroke="#cbd5e1" stroke-width="2" />`;
                } else if (this.svgBgImage) {
                    html += `<image href="${this.svgBgImage}" x="0" y="0" width="100%" height="100%" preserveAspectRatio="xMidYMid meet" opacity="0.6" style="pointer-events:none;" />`;
                }

                // 2. Render shapes
                for (const s of this.svgShapes) {
                    if (s.type === 'rect') {
                        html += `<rect x="${s.x}" y="${s.y}" width="${s.w}" height="${s.h}" fill="none" stroke="${s.color}" stroke-width="2" rx="4" />`;
                    } else if (s.type === 'circle') {
                        html += `<ellipse cx="${s.x + s.w/2}" cy="${s.y + s.h/2}" rx="${s.w/2}" ry="${s.h/2}" fill="none" stroke="${s.color}" stroke-width="2" />`;
                    } else if (s.type === 'image') {
                        html += `<image href="${s.src}" x="${s.x}" y="${s.y}" width="${s.w}" height="${s.h}" preserveAspectRatio="xMidYMid meet" />`;
                    } else if (s.type === 'zone') {
                        html += `<g>
                            <rect x="${s.x}" y="${s.y}" width="${s.w}" height="${s.h}" fill="${s.color}" fill-opacity="0.1" stroke="${s.color}" stroke-width="2" stroke-dasharray="6,4" rx="4" />
                            <rect x="${s.x}" y="${s.y}" width="24" height="24" fill="${s.color}" rx="4" />
                            <text x="${s.x + 12}" y="${s.y + 16}" fill="#fff" font-size="12" font-weight="bold" text-anchor="middle" style="pointer-events:none;">${s.label}</text>
                        </g>`;
                    } else if (s.type === 'text') {
                        const textLines = (s.label || '').split('\n');
                        const lineH = s.fontSize * 1.4;
                        const totalH = lineH * textLines.length;
                        const maxW = s.w || 20;
                        // Invisible drag background rect Ã¢â‚¬â€ makes clicking/dragging easy
                        html += `<rect x="${s.x - 8}" y="${s.y - 8}" width="${maxW + 16}" height="${totalH + 16}" fill="white" fill-opacity="0" pointer-events="all" style="cursor:move;" />`;
                        // Text element with tspan per line
                        html += `<text x="${s.x}" y="${s.y}" fill="${s.color}" font-size="${s.fontSize}" font-family="sans-serif" font-weight="bold" style="user-select:none; cursor:default;" xml:space="preserve">`;
                        textLines.forEach((line, i) => {
                            html += `<tspan x="${s.x}" dy="${i === 0 ? s.fontSize : lineH}">${line.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') || '\u200B'}</tspan>`;
                        });
                        html += `</text>`;
                        // Blinking cursor at end of last line when editing
                        if (this.svgEditingText === s.id) {
                            const lastLineW = this.svgGetTextWidth(textLines[textLines.length - 1], s.fontSize);
                            const cursorX = s.x + lastLineW;
                            const cursorY1 = s.y + lineH * (textLines.length - 1);
                            const cursorY2 = cursorY1 + s.fontSize;
                            html += `<line x1="${cursorX}" y1="${cursorY1}" x2="${cursorX}" y2="${cursorY2}" stroke="${s.color}" stroke-width="2"><animate attributeName="opacity" values="1;0;1" dur="0.8s" repeatCount="indefinite" /></line>`;
                        }

                    } else if (s.type === 'line' || s.type === 'arrow') {
                        const pathStr = this.getSvgLinePath(s);
                        const dash = s.dashStyle === 'dashed' ? '6,4' : s.dashStyle === 'dotted' ? '2,2' : '';
                        const cid = colorId(s.color || '#ef4444');
                        const mStart = s.arrowStart ? `url(#ah-start-${cid})` : '';
                        const mEnd = (s.arrowEnd || s.type === 'arrow') ? `url(#ah-end-${cid})` : '';
                        
                        html += `<path d="${pathStr}" stroke="${s.color}" stroke-width="2.5" fill="none"
                            ${dash ? `stroke-dasharray="${dash}"` : ''}
                            ${mStart ? `marker-start="${mStart}"` : ''}
                            ${mEnd ? `marker-end="${mEnd}"` : ''}
                            />`;
                    }

                    // 2a. Selection Box & Ports for current shape
                    if (this.svgSelected === s.id) {
                        const hs = 7; // handle square half-size
                        if (s.type === 'line' || s.type === 'arrow') {
                            const allPts = this.getSvgAllPoints(s);
                            html += `<g class="svg-handle">`;
                            // Segment midpoint handles (click to add waypoint) Ã¢â‚¬â€ shown as small cyan circles
                            for (let si = 0; si < allPts.length - 1; si++) {
                                const mx = (allPts[si].x + allPts[si+1].x) / 2;
                                const my = (allPts[si].y + allPts[si+1].y) / 2;
                                html += `<circle cx="${mx}" cy="${my}" r="5" fill="#38bdf8" stroke="#fff" stroke-width="1.5" opacity="0.85" style="cursor:copy;" />`;
                                html += `<circle cx="${mx}" cy="${my}" r="16" fill="transparent" style="cursor:copy;" />`;
                            }
                            // Waypoint handles (draggable) Ã¢â‚¬â€ shown as medium blue circles
                            const wps = s.waypoints || [];
                            for (const wp of wps) {
                                html += `<circle cx="${wp.x}" cy="${wp.y}" r="8" fill="#3b82f6" stroke="#fff" stroke-width="2" style="cursor:move;filter:drop-shadow(0 0 3px #3b82f6);" />`;
                                html += `<circle cx="${wp.x}" cy="${wp.y}" r="20" fill="transparent" style="cursor:move;" />`;
                            }
                            // Start endpoint (indigo)
                            html += `<circle cx="${allPts[0].x}" cy="${allPts[0].y}" r="9" fill="#6366f1" stroke="#fff" stroke-width="2.5" style="cursor:crosshair;filter:drop-shadow(0 0 4px #6366f1);" />`;
                            html += `<circle cx="${allPts[0].x}" cy="${allPts[0].y}" r="20" fill="transparent" style="cursor:crosshair;" />`;
                            // End endpoint (light purple)
                            const ep = allPts[allPts.length-1];
                            html += `<circle cx="${ep.x}" cy="${ep.y}" r="9" fill="#818cf8" stroke="#fff" stroke-width="2.5" style="cursor:crosshair;filter:drop-shadow(0 0 4px #818cf8);" />`;
                            html += `<circle cx="${ep.x}" cy="${ep.y}" r="20" fill="transparent" style="cursor:crosshair;" />`;
                            html += `</g>`;
                        } else if (['line', 'arrow'].indexOf(s.type) === -1) {
                            // Dashed selection rect
                            html += `<rect x="${s.x-4}" y="${s.y-4}" width="${s.w+8}" height="${s.h+8}" fill="none" stroke="#6366f1" stroke-width="1" stroke-dasharray="4,4" class="svg-handle" />`;
                            // Connection ports (only non-image, non-text shapes)
                            if (s.type !== 'image' && s.type !== 'text') {
                                const ports = this.svgGetPorts(s);
                                html += `
                                <circle cx="${ports.top.x}" cy="${ports.top.y}" r="5" fill="#fff" stroke="#6366f1" stroke-width="2" class="cursor-crosshair svg-port" />
                                <circle cx="${ports.right.x}" cy="${ports.right.y}" r="5" fill="#fff" stroke="#6366f1" stroke-width="2" class="cursor-crosshair svg-port" />
                                <circle cx="${ports.bottom.x}" cy="${ports.bottom.y}" r="5" fill="#fff" stroke="#6366f1" stroke-width="2" class="cursor-crosshair svg-port" />
                                <circle cx="${ports.left.x}" cy="${ports.left.y}" r="5" fill="#fff" stroke="#6366f1" stroke-width="2" class="cursor-crosshair svg-port" />`;
                            }
                            // 4 corner resize handles (nw, ne, sw, se)
                            const corners = [
                                { cx: s.x,      cy: s.y,      cur: 'nw-resize' },
                                { cx: s.x+s.w,  cy: s.y,      cur: 'ne-resize' },
                                { cx: s.x,      cy: s.y+s.h,  cur: 'sw-resize' },
                                { cx: s.x+s.w,  cy: s.y+s.h,  cur: 'se-resize' },
                            ];
                            for (const c of corners) {
                                html += `<rect x="${c.cx - hs}" y="${c.cy - hs}" width="${hs*2}" height="${hs*2}" fill="#6366f1" stroke="#fff" stroke-width="1.5" rx="2" style="cursor:${c.cur};" />`;
                            }
                        }
                    }
                }

                // 3. Hover Port
                if (this.svgHoverPort && this.svgTool === 'select') {
                    html += `<circle cx="${this.svgHoverPort.x}" cy="${this.svgHoverPort.y}" r="7" fill="#6366f1" opacity="0.5" class="svg-handle" style="pointer-events:none;" />`;
                }

                // 4. Drawing Shape
                if (this.svgDrawing) {
                    const sd = this.svgDrawing;
                    html += `<g style="pointer-events:none;">`;
                    if (sd.type === 'rect') {
                        html += `<rect x="${Math.min(sd.x1, sd.x2)}" y="${Math.min(sd.y1, sd.y2)}" width="${Math.abs(sd.x2 - sd.x1)}" height="${Math.abs(sd.y2 - sd.y1)}" fill="none" stroke="${sd.color}" stroke-width="2" rx="4" />`;
                    } else if (sd.type === 'circle') {
                        html += `<ellipse cx="${Math.min(sd.x1, sd.x2) + Math.abs(sd.x2 - sd.x1)/2}" cy="${Math.min(sd.y1, sd.y2) + Math.abs(sd.y2 - sd.y1)/2}" rx="${Math.abs(sd.x2 - sd.x1)/2}" ry="${Math.abs(sd.y2 - sd.y1)/2}" fill="none" stroke="${sd.color}" stroke-width="2" />`;
                    } else if (sd.type === 'zone') {
                        html += `<rect x="${Math.min(sd.x1, sd.x2)}" y="${Math.min(sd.y1, sd.y2)}" width="${Math.abs(sd.x2 - sd.x1)}" height="${Math.abs(sd.y2 - sd.y1)}" fill="${sd.color}" fill-opacity="0.1" stroke="${sd.color}" stroke-width="2" stroke-dasharray="6,4" rx="4" />`;
                    } else if (sd.type === 'line') {
                        html += `<line x1="${sd.x1}" y1="${sd.y1}" x2="${sd.x2}" y2="${sd.y2}" stroke="${sd.color}" stroke-width="2.5" />`;
                    } else if (sd.type === 'arrow') {
                        html += `<line x1="${sd.x1}" y1="${sd.y1}" x2="${sd.x2}" y2="${sd.y2}" stroke="${sd.color}" stroke-width="2.5" marker-end="url(#arrowhead)" />`;
                    }
                    html += `</g>`;
                }

                // 5. Connecting Line
                if (this.svgConnState) {
                    const sc = this.svgConnState;
                    html += `<line x1="${sc.x1}" y1="${sc.y1}" x2="${sc.x2}" y2="${sc.y2}" stroke="#6366f1" stroke-width="2" stroke-dasharray="4,4" style="pointer-events:none;" class="svg-handle" />`;
                }

                return html;
            },

             clearCanvas() {
                 const canvas = document.getElementById('sig-canvas');
                 if (!canvas) return;
                 const ctx = canvas.getContext('2d');
                 ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
                 
                 // Re-initialize stroke parameters just in case clearing resets ctx states
                 ctx.strokeStyle = '#000000';
                 ctx.lineWidth = 3;
                 ctx.lineCap = 'round';
                 ctx.lineJoin = 'round';
             },

            formatDate(d) {
                if (!d) return 'Ã¢â‚¬â€';
                return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            showToast(type, msg) {
                this.toast = { type, msg };
                setTimeout(() => this.toast = null, 3000);
            }
        };
    };
})();






