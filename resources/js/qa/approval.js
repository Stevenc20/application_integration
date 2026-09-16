
window.approvalPage = (config) => ({
    pending: [],
    pendingLI: [],
    loading: true,
    refreshing: false,
    selected: null,
    selectedLI: null,
    activeTab: 'qpr',
    trackingSearch: '',
    trackingFilter: 'all',
    
    // Auth context
    userId: config.userId,
    userName: config.userName,
    userRole: config.userRole,

    // Sig pad state for modals
    showPad: false,
    activeSample: null,
    pendingSig: null,
    saving: false,
    catatanRevisi: '',
    showRevisiForm: false,
    done: false,
    
    // detail loading inside modal
    detailLoading: false,
    detail: null,

    get isQCUser() {
        return this.userRole === 'Operator' || this.userRole === 'QC';
    },

    get showLITab() {
        return ['Foreman', 'Group Leader', 'Admin', 'Supervisor', 'Operator', 'QC'].includes(this.userRole);
    },

    get todaySigned() {
        const today = new Date().toDateString();
        return this.pending.filter(p => {
            let sigs = [];
            try { sigs = typeof p.qpr.approval_signatures === 'string' ? JSON.parse(p.qpr.approval_signatures) : (p.qpr.approval_signatures || []); } catch(e){}
            return sigs.some(s => s.signature && new Date(s.signed_at).toDateString() === today);
        }).length;
    },

    get filteredLI() {
        return [
            ...this.liNeedCheck,
            ...this.liNeedItemCheck,
            ...this.liNeedQcApproval,
            ...this.liNeedSupervisorApprove,
            ...this.liNeedGLCheck,
            ...this.liRevisions,
        ];
    },

    get liAllNeedTtd() {
        const list = [];
        this.liNeedCheck.forEach(item => {
            if (!list.some(x => x.id === item.id)) {
                list.push({ ...item, ttdType: 'CHECKED (FOREMAN TOP)' });
            }
        });
        this.liNeedGLCheck.forEach(item => {
            if (!list.some(x => x.id === item.id)) {
                list.push({ ...item, ttdType: 'QC CHECKED (GL)' });
            }
        });
        this.liNeedQcApproval.forEach(item => {
            if (!list.some(x => x.id === item.id)) {
                list.push({ ...item, ttdType: 'FINAL APPROVED (FOREMAN)' });
            }
        });
        this.liNeedSupervisorApprove.forEach(item => {
            if (!list.some(x => x.id === item.id)) {
                list.push({ ...item, ttdType: 'APPROVED (SUPERVISOR)' });
            }
        });
        return list;
    },

    get liRevisions() {
        // Dokumen yang statusnya 'revision' dan di-assign ke user ini
        return this.pendingLI.filter(item => {
            const uid = String(this.userId);
            const isAssigned = String(item.foreman_id) === uid || 
                               String(item.assigned_foreman_id) === uid ||
                               String(item.assigned_operator_id) === uid;
            return item.status === 'revision' && isAssigned;
        });
    },

    get liNeedCheck() {
        // Foreman/Azriel: TTD CHECKED pertama (slot paraf_gl)
        if (!['Foreman', 'Admin'].includes(this.userRole)) return [];
        return this.pendingLI.filter(item => {
            const uid = String(this.userId);
            const uname = String(this.userName).trim().toLowerCase();
            // Azriel di-assign ke assigned_gl_id
            const isAssignedToMe = String(item.assigned_gl_id) === uid || 
                                   String(item.gl_name || '').trim().toLowerCase() === uname;

            return item.status === 'waiting_foreman' && isAssignedToMe && !item.paraf_gl;
        });
    },

    get liNeedItemCheck() {
        // Azriel/Operator: Isi data Item Check
        return this.pendingLI.filter(item => {
            const uid = String(this.userId);
            return item.status === 'waiting_qc' &&
                   String(item.assigned_operator_id) === uid &&
                   !item.qg_judgement;
        });
    },

    get liNeedQcApproval() {
        // Foreman/Azriel: TTD Final setelah QC (slot paraf_foreman_bottom)
        if (!['Foreman', 'Admin'].includes(this.userRole)) return [];
        return this.pendingLI.filter(item => {
            const uid = String(this.userId);
            const uname = String(this.userName).trim().toLowerCase();
            const isAssignedToMe = String(item.assigned_foreman_id) === uid ||
                                   String(item.frm_name || '').trim().toLowerCase() === uname;

            return item.status === 'waiting_qc_approval' && isAssignedToMe && !item.paraf_foreman_bottom;
        });
    },

    get liNeedSupervisorApprove() {
        // Supervisor/Novina: TTD APPROVED (slot paraf_foreman)
        if (!['Supervisor', 'Admin'].includes(this.userRole)) return [];
        return this.pendingLI.filter(item => {
            const uid = String(this.userId);
            const uname = String(this.userName).trim().toLowerCase();
            // Novina di-assign ke assigned_foreman_id
            const isAssignedToMe = String(item.assigned_foreman_id) === uid ||
                                   String(item.frm_name || '').trim().toLowerCase() === uname;

            return item.status === 'waiting_supervisor' && isAssignedToMe && !item.paraf_foreman;
        });
    },

    get liNeedGLCheck() {
        // Group Leader: TTD Checked (if GL workflow active)
        if (!['Group Leader', 'GroupLeader', 'Leader', 'Admin'].includes(this.userRole)) return [];
        const uid = String(this.userId);
        const uname = String(this.userName).trim().toLowerCase();
        return this.pendingLI.filter(item => {
            const isAssigned = String(item.assigned_gl_id) === uid ||
                               String(item.gl_name || '').trim().toLowerCase() === uname ||
                               (!item.assigned_gl_id && !item.gl_name); // Catch-all for unassigned GL

            return isAssigned && !item.paraf_gl_bottom &&
                   ['waiting_foreman', 'waiting_qc_approval'].includes(item.status);
        });
    },

    get totalPending() {
        return this.pending.length +
               this.liNeedCheck.length +
               this.liNeedItemCheck.length +
               this.liNeedQcApproval.length +
               this.liNeedSupervisorApprove.length +
               this.liNeedGLCheck.length +
               this.liRevisions.length;
    },

    get trackingLI() {
        if (this.userRole !== 'Supervisor') return [];
        return this.pendingLI.map(item => {
            let step = 1;
            let stepName = "QA Leader";
            if (item.prepared_paraf) { step = 2; stepName = "Foreman (Azriel)"; }
            if (item.paraf_gl || item.paraf_gl_bottom) { step = 3; stepName = "Supervisor (Novina)"; }
            if (item.paraf_foreman || item.paraf_foreman_bottom) { step = 4; stepName = "QC Check (Angga Prasetiyantoro)"; }
            if (item.qg_judgement) { step = 5; stepName = "Selesai"; }
            return { ...item, currentStep: step, stepName };
        }).filter(item => {
            const ms = !this.trackingSearch || (item.no_form || '').toLowerCase().includes(this.trackingSearch.toLowerCase()) || (item.part_name || '').toLowerCase().includes(this.trackingSearch.toLowerCase());
            let mst = true;
            if (this.trackingFilter === 'foreman') mst = item.currentStep === 2;
            if (this.trackingFilter === 'me') mst = item.currentStep === 3;
            return ms && mst;
        });
    },

    async init() {
        await this.fetchAll();
        this.$watch('loading', (val) => {
            if (!val && this.showLITab && this.pending.length === 0 && this.filteredLI.length > 0 && this.activeTab === 'qpr') {
                this.activeTab = 'li';
            }
        });
    },

    async fetchAll() {
        this.loading = true;
        try {
            const [qprRes, liRes] = await Promise.allSettled([
                axios.get(`${config.apiUrl}/api/qprs/pending-approval`),
                axios.get(`${config.apiUrl}/api/inspeksi/pending-ttd`)
            ]);
            if (qprRes.status === 'fulfilled') {
                this.pending = Array.isArray(qprRes.value.data.data || qprRes.value.data) ? (qprRes.value.data.data || qprRes.value.data) : [];
            } else { this.pending = []; }

            if (liRes.status === 'fulfilled') {
                this.pendingLI = Array.isArray(liRes.value.data.data || liRes.value.data) ? (liRes.value.data.data || liRes.value.data) : [];
            } else { this.pendingLI = []; }
        } catch (e) {
            console.error("Approval fetch error", e);
        } finally {
            this.loading = false;
        }
    },

    async handleRefresh() {
        this.refreshing = true;
        await this.fetchAll();
        this.refreshing = false;
    },

    openQprModal(item) {
        this.selected = item;
        this.resetModalState();
        this.fetchQprDetail(item.qpr?.id);
    },

    openLiModal(item) {
        this.selectedLI = item;
        this.resetModalState();
    },

    closeModal() {
        this.selected = null;
        this.selectedLI = null;
    },

    resetModalState() {
        this.showPad = false;
        this.pendingSig = null;
        this.catatanRevisi = '';
        this.showRevisiForm = false;
        this.done = false;
        this.saving = false;
    },

    async fetchQprDetail(qprId) {
        if (!qprId) return;
        this.detailLoading = true;
        try {
            const res = await axios.get(`${config.apiUrl}/api/qprs/${qprId}`);
            this.detail = res.data?.data || res.data;
        } catch (e) {
            console.error(e);
            this.detail = this.selected?.qpr;
        } finally {
            this.detailLoading = false;
        }
    },

    fmtDate(v) {
        if (!v) return "—";
        const d = new Date(v);
        return isNaN(d) ? v : d.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
    },
    
    fmtDateShort(v) {
        if (!v) return "—";
        const parts = String(v).split('T')[0].split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return v;
    },

    parseJSONSafely(str) {
        if (!str) return null;
        if (typeof str !== 'string') return str;
        try { return JSON.parse(str); } catch (e) { return null; }
    },

    // --- LI Modal Logic ---
    get liRoleContext() {
        if (!this.selectedLI) return { role: '', label: '', isAssignedGL: false, isForeman: false, isCreator: false };
        const item = this.selectedLI;
        const uid = String(this.userId);
        const uname = String(this.userName).trim().toLowerCase();

        const isCreator = String(item.created_by) === uid;
        
        // Cek penugasan
        const isAssignedForeman = String(item.assigned_foreman_id) === uid || 
                                 String(item.frm_name || '').toLowerCase() === uname;

        const isAssignedGL = String(item.assigned_gl_id) === uid || 
                            String(item.gl_name || '').toLowerCase() === uname;
        
        let role = 'gl';
        let label = 'CHECKED (FOREMAN)';

        if (this.userRole === 'Supervisor') {
            role = 'foreman';
            label = 'APPROVED (SUPERVISOR)';
        } else if (this.userRole === 'Foreman') {
            role = 'gl';
            label = 'CHECKED (FOREMAN)';
        } else if (this.userRole === 'Group Leader' || this.userRole === 'Leader') {
            role = 'gl';
            label = 'CHECKED (GL)';
        } else if (isCreator) {
            role = 'prepared';
            label = 'PREPARED (QA LEADER)';
        }

        // Overwrite jika di fase QC Approval (Final TTD)
        if (item.status === 'waiting_qc_approval') {
            if (this.userRole === 'Foreman') { 
                role = 'fm_bottom'; 
                label = 'FINAL APPROVED (FOREMAN)'; 
            } else if (this.userRole === 'Group Leader' || this.userRole === 'Leader') { 
                role = 'gl_bottom'; 
                label = 'QC CHECKED (GL)'; 
            }
        }

        return { role, label, isAssignedGL, isForeman: isAssignedForeman, isCreator };
    },

    get liAlreadySigned() {
        if (!this.selectedLI) return false;
        const r = this.liRoleContext.role;
        const item = this.selectedLI;
        return (r === 'foreman') ? !!item.paraf_foreman 
             : (r === 'prepared') ? !!item.prepared_paraf 
             : (r === 'gl_bottom') ? !!item.paraf_gl_bottom 
             : (r === 'fm_bottom') ? !!item.paraf_foreman_bottom 
             : !!item.paraf_gl;
    },

    handleOpenPadLi(sampleIdx = null) {
        const ctx = this.liRoleContext;
        const item = this.selectedLI;
        if (sampleIdx === 'foreman_global' && !ctx.isForeman) {
            alert('Hanya Foreman yang bisa mengisi Paraf ini.'); return;
        } else if (sampleIdx !== 'foreman_global') {
            if (ctx.role === 'gl' && !ctx.isAssignedGL) { alert(`Hanya GL yang ditugaskan (${item.gl_name})`); return; }
            if (ctx.role === 'prepared' && !ctx.isCreator) { alert('Hanya pembuat form ini'); return; }
            if (ctx.role === 'foreman' && !ctx.isForeman) { alert(`Hanya Foreman yang ditugaskan (${item.frm_name})`); return; }
        }
        this.activeSample = sampleIdx;
        this.showPad = true;
    },

    async handleFinalSubmitLi() {
        if (!this.pendingSig) { alert('Silakan tanda tangan terlebih dahulu.'); return; }
        this.saving = true;
        try {
            await axios.post(`${config.apiUrl}/api/inspeksi/${this.selectedLI.id}/sign`, {
                role: this.liRoleContext.role,
                signature: this.pendingSig,
                catatan_revisi: this.catatanRevisi
            });
            this.done = true;
            setTimeout(() => { this.closeModal(); this.handleRefresh(); }, 1500);
        } catch (e) {
            alert('Gagal simpan: ' + (e.response?.data?.message || e.message));
        } finally {
            this.saving = false;
        }
    },

    async handleRejectLi() {
        if (!this.catatanRevisi.trim()) { alert('Harap isi Catatan/Alasan Revisi terlebih dahulu.'); return; }
        if (!window.confirm('Yakin menolak & minta revisi?')) return;
        this.saving = true;
        try {
            await axios.post(`${config.apiUrl}/api/inspeksi/${this.selectedLI.id}/reject`, {
                catatan: this.catatanRevisi,
                role: this.liRoleContext.role
            });
            this.done = true;
            setTimeout(() => { this.closeModal(); this.handleRefresh(); }, 1500);
        } catch(e) {
            alert('Gagal revisi: ' + (e.response?.data?.message || e.message));
        } finally {
            this.saving = false;
        }
    },

    // --- QPR Modal Logic ---
    async handleSavePadQpr(sig, position) {
        this.saving = true;
        try {
            await axios.post(`${config.apiUrl}/api/qprs/${this.detail.id}/sign`, {
                signature: sig,
                position: position
            });
            this.done = true;
            this.showPad = false;
            setTimeout(() => { this.closeModal(); this.handleRefresh(); }, 1500);
        } catch(e) {
            alert('Gagal simpan TTD: ' + (e.response?.data?.message || e.message));
        } finally {
            this.saving = false;
        }
    },

    async submitRevisiQpr() {
        if (!this.catatanRevisi.trim()) {
            alert('Harap isi Catatan Revisi.');
            return;
        }
        if (!window.confirm('Yakin kembalikan QPR ini untuk revisi?')) return;
        
        this.saving = true;
        try {
            await axios.post(`${config.apiUrl}/api/qprs/${this.detail.id}/revision`, {
                catatan_revisi: this.catatanRevisi
            });
            this.done = true;
            this.showRevisiForm = false;
            setTimeout(() => { this.closeModal(); this.handleRefresh(); }, 1500);
        } catch(e) {
            alert('Gagal mengirim permintaan revisi: ' + (e.response?.data?.message || e.message));
        } finally {
            this.saving = false;
        }
    },

    // --- Helpers ---
    getFullUrl(path) {
        if (!path) return null;
        if (path.startsWith("data:")) return path;
        const base = config.apiUrl;
        let clean = path;
        if (path.startsWith("http")) { 
            try { const u = new URL(path); clean = u.pathname; } catch(e){} 
        }
        return `${base}/${clean.startsWith("/") ? clean.slice(1) : clean}`;
    },
    
    getCols(total) {
        if (!total || total <= 0) return [];
        if (total <= 9) return Array.from({length: total}, (_, i) => i + 1);
        const res = [1,2,3];
        [10,20,40,60,80,100].forEach(v => { if (v <= total) res.push(v); });
        for(let v = 125; v <= 200; v += 25) { if (v <= total) res.push(v); }
        for(let v = 250; v < total; v += 50) res.push(v);
        res.push(total);
        return [...new Set(res)].sort((a,b)=>a-b);
    }
});

// Reusable Alpine Signature Pad component
window.signaturePad = () => ({
    isEmpty: true,
    confirming: false,
    previewSrc: null,
    drawing: false,
    ctx: null,

    init() {
        this.$nextTick(() => {
            if (this.$refs.canvas) {
                this.ctx = this.$refs.canvas.getContext('2d');
                this.ctx.fillStyle = '#fff';
                this.ctx.fillRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
            }
        });
    },
    getPos(e) {
        const rect = this.$refs.canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return {
            x: (src.clientX - rect.left) * (this.$refs.canvas.width / rect.width),
            y: (src.clientY - rect.top) * (this.$refs.canvas.height / rect.height)
        };
    },
    start(e) {
        e.preventDefault();
        this.drawing = true;
        const pos = this.getPos(e);
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);
    },
    draw(e) {
        e.preventDefault();
        if (!this.drawing) return;
        const pos = this.getPos(e);
        this.ctx.lineWidth = 2;
        this.ctx.lineCap = 'round';
        this.ctx.strokeStyle = '#0F172A';
        this.ctx.lineTo(pos.x, pos.y);
        this.ctx.stroke();
        this.isEmpty = false;
    },
    stop() {
        this.drawing = false;
    },
    clear() {
        this.ctx.fillStyle = '#fff';
        this.ctx.fillRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
        this.isEmpty = true;
        this.confirming = false;
        this.previewSrc = null;
    },
    save() {
        if (!this.isEmpty) {
            this.previewSrc = this.$refs.canvas.toDataURL('image/png');
            this.confirming = true;
        }
    }
});
