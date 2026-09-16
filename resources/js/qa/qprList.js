import axios from 'axios';

window.qprList = (config) => ({
    data: [],
    search: '',
    filterBulan: '',
    activeFilter: 'all',
    archiveMode: false,
    loading: true,
    role: config?.userRole || 'Guest',
    userDepartment: config?.userDepartment || null,
    userId: config?.userId || null,
    userName: config?.userName || null,
    
    // Pagination
    currentPage: 1,
    perPage: 8,

    async init() {
        const defaultFilter = {
            Admin: 'all'
        };
        this.activeFilter = defaultFilter[this.role] || 'my_tasks';
        
        // Reset page when filter changes
        this.$watch('search', () => this.currentPage = 1);
        this.$watch('activeFilter', () => this.currentPage = 1);
        this.$watch('filterBulan', () => this.currentPage = 1);

        await this.loadData();
    },

    async loadData() {
        this.loading = true;
        try {
            const url = `${config?.apiUrl || ''}/api/qprs`;
            const res = await axios.get(url);
            this.data = Array.isArray(res.data) ? res.data : (res.data.data || []);
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },

    toggleArchiveMode() {
        this.archiveMode = !this.archiveMode;
        // TODO: call API with ?archived=1 if backend supports it
    },

    canDelete(item) {
        // IMMORTAL QPR: Jika frekuensi problem sering/kadang-kadang, tidak boleh dihapus sama sekali untuk histori
        if (['Kadang-Kadang', 'Sering'].includes(item.kategori_problem)) {
            return false;
        }

        if (this.role === 'Operator') return false;
        return this.role === 'Admin' || item.status === 'Draft' || item.status === 'Revision';
    },
    canEdit(item) {
        // Admin bisa edit kapan saja di status awal dan Pengajuan Baru
        if (this.role === 'Admin' && (item.status === 'Draft' || item.status === 'Revision' || (item.status === 'OPEN' && !item.assigned_foreman_id))) return true;
        
        // Operator BISA edit jika dia pembuatnya ATAU dia adalah investigator yang ditugaskan, DAN status masih Draft/Revision/OPEN
        if (this.role === 'Operator') {
            const isOwner = (item.created_by == this.userId) || (item.investigator_name === this.userName);
            if (isOwner && (item.status === 'Draft' || item.status === 'Revision' || item.status === 'OPEN')) return true;
            // Any QA Operator can edit OPEN requests that have no foreman assigned
            if (item.status === 'OPEN' && !item.assigned_foreman_id && ['QA', 'Quality Assurance', 'Quality Control'].includes(this.userDepartment)) return true;
        }
        
        // GL / Foreman BISA edit jika QPR menunggu persetujuan mereka
        if (['Group Leader', 'Foreman'].includes(this.role)) {
            const isAssigned = item.assigned_foreman_id == this.userId;
            const glEditableStates = ['Pending Approval', 'OPEN', 'Draft', 'Revision'];
            if (isAssigned && glEditableStates.includes(item.status)) return true;
        }

        // Seksi Terkait BISA edit jika statusnya GL Approved, Progress, Waiting Action, Waiting Verif, atau Waiting A3
        const seksiEditableStates = ['GL Approved', 'Progress', 'Waiting Action 1', 'Waiting Action 2', 'Waiting Action 3', 'Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3', 'Waiting A3 Report'];
        if (seksiEditableStates.includes(item.status)) {
            let sigs = [];
            if (typeof item.approval_signatures === 'string') {
                try { sigs = JSON.parse(item.approval_signatures) || []; } catch(e){}
            } else if (Array.isArray(item.approval_signatures)) {
                sigs = item.approval_signatures;
            }
            
            const seksiRoles = sigs.filter(s => s.role !== 'Operator' && s.role !== 'Foreman' && s.role !== 'Kasie QA').map(s => s.role);
            if (seksiRoles.includes(this.userDepartment) || item.pic_seksi === this.userDepartment) {
                return true; 
            }
        }

        // QA (Foreman, GL, Kasie QA) BISA edit jika statusnya Waiting Verif
        const qaEditableStates = ['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3'];
        if (qaEditableStates.includes(item.status)) {
            if (['Group Leader', 'Foreman', 'Kasie QA', 'Kasie'].includes(this.role)) {
                if (['Kasie QA', 'Kasie'].includes(this.role) || item.assigned_foreman_id == this.userId || ['QA', 'Quality Assurance', 'Quality Control'].includes(this.userDepartment)) {
                    return true;
                }
            }
        }
        
        // Role lain tidak edit lewat halaman form
        return false;
    },

    needsActionFromSeksi(item) {
        if (!this.userDepartment) return false;
        let sigs = [];
        if (typeof item.approval_signatures === 'string') {
            try { sigs = JSON.parse(item.approval_signatures) || []; } catch(e){}
        } else if (Array.isArray(item.approval_signatures)) {
            sigs = item.approval_signatures;
        }
        
        const isSeksi = sigs.some(s => s.role === this.userDepartment) || item.pic_seksi === this.userDepartment;
        if (!isSeksi) return false;

        let corr = [];
        try { corr = typeof item.correction_items === 'string' ? JSON.parse(item.correction_items) : (item.correction_items || []); } catch(e){}
        let damp = [];
        try { damp = typeof item.dampak_items === 'string' ? JSON.parse(item.dampak_items) : (item.dampak_items || []); } catch(e){}

        const hasPendingCorr = corr.some(c => c.text && c.status !== 'A');
        const hasPendingDamp = damp.some(d => d.text && d.status !== 'A');
        
        let acts = [];
        try { acts = typeof item.actions === 'string' ? JSON.parse(item.actions) : (item.actions || []); } catch(e){}
        const hasPendingActs = acts.some(a => a.action && a.pdca !== 'A' && a.pdca !== 'C');
        const isWaitingAction = item.status && (item.status.startsWith('Waiting Action') || item.status === 'Waiting A3 Report');

        return hasPendingCorr || hasPendingDamp || hasPendingActs || isWaitingAction;
    },

    needsSignature(item) {
        if (!item.approval_signatures) return false;
        let sigs = [];
        if (typeof item.approval_signatures === 'string') {
            try { sigs = JSON.parse(item.approval_signatures) || []; } catch(e){}
        } else if (Array.isArray(item.approval_signatures)) {
            sigs = item.approval_signatures;
        }

        // Foreman yang ditugaskan
        if (['Group Leader', 'Foreman'].includes(this.role)) {
            if (item.assigned_foreman_id == this.userId) {
                const foremanSig = sigs.find(s => s.position === 'foreman');
                if (foremanSig && !foremanSig.signature) return true;
            }
        }

        // Seksi Terkait
        if (['GL Approved', 'Progress'].includes(item.status) && this.userDepartment) {
            // Cek apakah user belum TTD
            const seksiSig = sigs.find(s => s.role === this.userDepartment);
            if (seksiSig && !seksiSig.signature) return true;
            
            // Fallback untuk legacy document dimana seksi belum ada di approval_signatures
            if (!seksiSig && item.pic_seksi === this.userDepartment) return true;
        }

        // Kasie QA
        if (['Kasie QA', 'Kasie'].includes(this.role)) {
            const kasieSig = sigs.find(s => s.role === 'Kasie QA');
            if (kasieSig && !kasieSig.signature) return true;
        }

        // QA Verificator (Foreman / GL / Kasie QA)
        if (['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3', 'Waiting Verif A3'].includes(item.status)) {
            if (['Group Leader', 'Foreman', 'Kasie QA', 'Kasie'].includes(this.role)) {
                if (['QA', 'Quality Assurance', 'Quality Control'].includes(this.userDepartment)) {
                    return true;
                }
            }
        }

        return false;
    },

    async deleteQpr(item) {
        if (!confirm('Yakin ingin menghapus QPR ini?')) return;
        try {
            await axios.delete(`${config?.apiUrl || ''}/api/qprs/${item.id}`);
            this.loadData();
        } catch (e) {
            alert(e.response?.data?.message || 'Gagal menghapus');
        }
    },

    get availableTabs() {
        const renderBadge = (count, actionCount) => {
            if (actionCount > 0) {
                return `<span class="ml-1.5 w-[18px] h-[18px] inline-flex items-center justify-center bg-red-600 text-white rounded-full text-[9px] shadow-sm font-black ring-2 ring-white/50 animate-pulse">${actionCount}</span>`;
            }
            return `<span class="ml-1 opacity-60">(${count})</span>`;
        };

        const reqData = this.data.filter(d => d.status === 'OPEN' && !d.assigned_foreman_id);
        const revData = this.data.filter(d => ['Draft', 'Revision'].includes(d.status));
        const glData = this.data.filter(d => ['Pending Approval', 'OPEN'].includes(d.status) && !!d.assigned_foreman_id);
        const seksiData = this.data.filter(d => d.status === 'GL Approved');
        const qaData = this.data.filter(d => ['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3'].includes(d.status));
        const prodData = this.data.filter(d => {
            return ['Progress', 'Waiting Action 1', 'Waiting Action 2', 'Waiting Action 3'].includes(d.status) || 
                   (['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3', 'Waiting Verif A3'].includes(d.status) && this.needsActionFromSeksi(d));
        });
        const a3Data = this.data.filter(d => ['Waiting A3 Report', 'Waiting Verif A3'].includes(d.status));
        
        const tasksCount = this.data.filter(d => this.canEdit(d) || this.needsSignature(d)).length;

        const tabs = [
            { id: 'all', label: `SEMUA <span class="ml-1 opacity-60">(${this.data.length})</span>` },
            { id: 'request_qpr', label: `PENGAJUAN BARU ${renderBadge(reqData.length, reqData.length)}` },
            { id: 'revision', label: `DRAFT & REVISI ${renderBadge(revData.length, revData.filter(d => this.canEdit(d) || this.needsSignature(d)).length)}` },
            { id: 'waiting_gl', label: `MENUNGGU GL/FM ${renderBadge(glData.length, glData.filter(d => this.needsSignature(d)).length)}` },
            { id: 'waiting_seksi', label: `MENUNGGU SEKSI ${renderBadge(seksiData.length, seksiData.filter(d => this.needsSignature(d)).length)}` },
            { id: 'antrian_qa', label: `ANTRIAN QA ${renderBadge(qaData.length, qaData.filter(d => this.needsSignature(d)).length)}` },
            { id: 'antrian_produksi', label: `ANTRIAN PRODUKSI ${renderBadge(prodData.length, prodData.filter(d => this.needsActionFromSeksi(d)).length)}` },
            { id: 'a3_report', label: `A3 REPORT ${renderBadge(a3Data.length, a3Data.filter(d => this.needsActionFromSeksi(d) || this.needsSignature(d)).length)}` },
            { id: 'finished', label: `SELESAI <span class="ml-1 opacity-60">(${this.data.filter(d => d.status === 'Close').length})</span>` }
        ];

        // TUGAS SAYA is available for everyone except Admin
        if (this.role !== 'Admin') {
            tabs.unshift({ id: 'my_tasks', label: `TUGAS SAYA ${renderBadge(tasksCount, tasksCount)}` });
        }

        const priority = {
            Operator: ['my_tasks', 'request_qpr', 'revision', 'a3_report', 'all', 'finished'],
            'Group Leader': ['my_tasks', 'waiting_gl', 'waiting_seksi', 'antrian_produksi', 'antrian_qa', 'a3_report', 'all', 'finished'],
            Foreman: ['my_tasks', 'waiting_gl', 'waiting_seksi', 'antrian_produksi', 'antrian_qa', 'a3_report', 'all', 'finished'],
            Supervisor: ['my_tasks', 'waiting_seksi', 'antrian_produksi', 'waiting_gl', 'antrian_qa', 'a3_report', 'all', 'finished'],
            Staff: ['my_tasks', 'waiting_seksi', 'antrian_produksi', 'a3_report', 'all', 'finished'],
            Admin: ['all', 'request_qpr', 'revision', 'waiting_gl', 'waiting_seksi', 'a3_report', 'antrian_produksi', 'antrian_qa', 'finished'],
            'Kasie QA': ['my_tasks', 'waiting_gl', 'antrian_qa', 'a3_report', 'all', 'finished']
        };

        const order = priority[this.role] || ['all', 'request_qpr', 'revision', 'waiting_gl', 'waiting_seksi', 'a3_report', 'antrian_produksi', 'antrian_qa', 'finished'];
        
        return order.map(id => tabs.find(t => t.id === id)).filter(Boolean);
    },

    get filteredData() {
        let filtered = this.data.filter(d => {
            const q = this.search.toLowerCase();
            const matchesSearch = !q || [d.no_qpr, d.no_job, d.model, d.nama_part, d.status, d.kategori_problem]
                .some(v => (v || '').toLowerCase().includes(q));
            if (!matchesSearch) return false;

            if (this.filterBulan) {
                const tgl = d.tanggal || d.created_at;
                if (!tgl) return false;
                const d2 = new Date(tgl);
                const yearMonth = d2.getFullYear() + '-' + String(d2.getMonth() + 1).padStart(2, '0');
                if (yearMonth !== this.filterBulan) return false;
            }

            if (this.activeFilter === 'all') return true;
            if (this.activeFilter === 'my_tasks') return this.canEdit(d) || this.needsSignature(d);
            if (this.activeFilter === 'request_qpr') return d.status === 'OPEN' && !d.assigned_foreman_id;
            if (this.activeFilter === 'revision') return ['Draft', 'Revision'].includes(d.status);
            if (this.activeFilter === 'waiting_gl') return ['Pending Approval', 'OPEN'].includes(d.status) && !!d.assigned_foreman_id;
            if (this.activeFilter === 'waiting_seksi') return d.status === 'GL Approved';
            if (this.activeFilter === 'antrian_qa') return ['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3'].includes(d.status);
            if (this.activeFilter === 'antrian_produksi') {
                return ['Progress', 'Waiting Action 1', 'Waiting Action 2', 'Waiting Action 3'].includes(d.status) || 
                       (['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3', 'Waiting Verif A3'].includes(d.status) && this.needsActionFromSeksi(d));
            }
            if (this.activeFilter === 'a3_report') return ['Waiting A3 Report', 'Waiting Verif A3'].includes(d.status);
            if (this.activeFilter === 'progress') return d.status === 'Progress';
            if (this.activeFilter === 'finished') return d.status === 'Close';

            return true;
        });

        // Sort: Items requiring action by this user appear first
        return filtered.sort((a, b) => {
            const aAction = this.canEdit(a) || this.needsSignature(a);
            const bAction = this.canEdit(b) || this.needsSignature(b);
            if (aAction && !bAction) return -1;
            if (!aAction && bAction) return 1;
            
            // Secondary sort: descending by id (newest first)
            return b.id - a.id;
        });
    },

    get paginatedData() {
        const start = (this.currentPage - 1) * this.perPage;
        const end = start + this.perPage;
        return this.filteredData.slice(start, end);
    },

    get totalPages() {
        return Math.ceil(this.filteredData.length / this.perPage);
    },

    get pageNumbers() {
        let pages = [];
        for (let i = 1; i <= this.totalPages; i++) {
            if (i === 1 || i === this.totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                pages.push(i);
            } else if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }
        return pages;
    },

    get startIndex() {
        return this.filteredData.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
    },

    get endIndex() {
        return Math.min(this.currentPage * this.perPage, this.filteredData.length);
    },

    nextPage() {
        if (this.currentPage < this.totalPages) this.currentPage++;
    },

    prevPage() {
        if (this.currentPage > 1) this.currentPage--;
    },

    goToPage(page) {
        if (page !== '...' && page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
        }
    },

    get bulanOptions() {
        const seen = new Set();
        const opts = [];
        const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        const sorted = [...this.data].sort((a, b) => new Date(b.tanggal || b.created_at) - new Date(a.tanggal || a.created_at));
        for (const d of sorted) {
            const tgl = d.tanggal || d.created_at;
            if (!tgl) continue;
            const dt = new Date(tgl);
            const key = dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0');
            if (!seen.has(key)) {
                seen.add(key);
                opts.push({ value: key, label: monthNames[dt.getMonth()] + ' ' + dt.getFullYear() });
            }
        }
        return opts;
    },

    getStatusStyles(status, item = null) {
        if (status === 'OPEN' && item && !item.assigned_foreman_id) {
            return { bg: 'bg-orange-50', text: 'text-orange-600', border: 'border-orange-200', btnGradient: 'from-orange-600 to-orange-700 hover:from-orange-500 hover:to-orange-600 shadow-orange-600/20' };
        }
        const map = {
            'Draft': { bg: 'bg-red-50', text: 'text-red-600', border: 'border-red-200', btnGradient: 'from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 shadow-red-600/20' },
            'Revision': { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', btnGradient: 'from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 shadow-amber-600/20' },
            'OPEN': { bg: 'bg-blue-50', text: 'text-blue-600', border: 'border-blue-200', btnGradient: 'from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 shadow-blue-600/20' },
            'Pending Approval': { bg: 'bg-blue-50', text: 'text-blue-600', border: 'border-blue-200', btnGradient: 'from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 shadow-blue-600/20' },
            'GL Approved': { bg: 'bg-indigo-50', text: 'text-indigo-600', border: 'border-indigo-200', btnGradient: 'from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 shadow-indigo-600/20' },
            'Progress': { bg: 'bg-purple-50', text: 'text-purple-600', border: 'border-purple-200', btnGradient: 'from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600 shadow-purple-600/20' },
            'Waiting Action 1': { bg: 'bg-fuchsia-50', text: 'text-fuchsia-600', border: 'border-fuchsia-200', btnGradient: 'from-fuchsia-600 to-fuchsia-700 hover:from-fuchsia-500 hover:to-fuchsia-600 shadow-fuchsia-600/20' },
            'Waiting Action 2': { bg: 'bg-fuchsia-50', text: 'text-fuchsia-600', border: 'border-fuchsia-200', btnGradient: 'from-fuchsia-600 to-fuchsia-700 hover:from-fuchsia-500 hover:to-fuchsia-600 shadow-fuchsia-600/20' },
            'Waiting Action 3': { bg: 'bg-fuchsia-50', text: 'text-fuchsia-600', border: 'border-fuchsia-200', btnGradient: 'from-fuchsia-600 to-fuchsia-700 hover:from-fuchsia-500 hover:to-fuchsia-600 shadow-fuchsia-600/20' },
            'Waiting Verif 1': { bg: 'bg-sky-50', text: 'text-sky-600', border: 'border-sky-200', btnGradient: 'from-sky-600 to-sky-700 hover:from-sky-500 hover:to-sky-600 shadow-sky-600/20' },
            'Waiting Verif 2': { bg: 'bg-sky-50', text: 'text-sky-600', border: 'border-sky-200', btnGradient: 'from-sky-600 to-sky-700 hover:from-sky-500 hover:to-sky-600 shadow-sky-600/20' },
            'Waiting Verif 3': { bg: 'bg-sky-50', text: 'text-sky-600', border: 'border-sky-200', btnGradient: 'from-sky-600 to-sky-700 hover:from-sky-500 hover:to-sky-600 shadow-sky-600/20' },
            'Waiting Verif A3': { bg: 'bg-sky-50', text: 'text-sky-600', border: 'border-sky-200', btnGradient: 'from-sky-600 to-sky-700 hover:from-sky-500 hover:to-sky-600 shadow-sky-600/20' },
            'Waiting A3 Report': { bg: 'bg-rose-50', text: 'text-rose-600', border: 'border-rose-200', btnGradient: 'from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 shadow-rose-600/20' },
            'Close': { bg: 'bg-emerald-50', text: 'text-emerald-600', border: 'border-emerald-200', btnGradient: 'from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 shadow-emerald-600/20' },
        };
        return map[status] || { bg: 'bg-slate-50', text: 'text-slate-600', border: 'border-slate-200', btnGradient: 'from-slate-600 to-slate-700 hover:from-slate-500 hover:to-slate-600 shadow-slate-600/20' };
    },

    getStatusLabel(status, item = null) {
        if (status === 'OPEN' && item && !item.assigned_foreman_id) return 'PENGAJUAN QPR';
        const map = {
            'Draft': 'DRAFT',
            'Revision': 'REVISI',
            'OPEN': 'MENUNGGU GL/FM',
            'Pending Approval': 'MENUNGGU GL/FM',
            'GL Approved': 'MENUNGGU SEKSI',
            'Progress': 'PROGRESS',
            'Waiting Action 1': 'ACTION 1 (PRODUKSI)',
            'Waiting Action 2': 'ACTION 2 (PRODUKSI)',
            'Waiting Action 3': 'ACTION 3 (PRODUKSI)',
            'Waiting Verif 1': 'VERIFIKASI 1 (QA)',
            'Waiting Verif 2': 'VERIFIKASI 2 (QA)',
            'Waiting Verif 3': 'VERIFIKASI 3 (QA)',
            'Waiting Verif A3': 'VERIFIKASI A3 (QA)',
            'Waiting A3 Report': 'MENUNGGU A3 REPORT',
            'Close': 'SELESAI',
        };
        return map[status] || status;
    },

    get stats() {
        const icons = {
            doc: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
            warn: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
            clock: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            check: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        };

        return [
            { label: 'Total QPR', value: this.data.length, icon: icons.doc, iconBg: 'bg-[#E3EFFF] text-[#4F8CE3]', textColor: 'text-[#4F8CE3]', cardBg: 'bg-gradient-to-br from-white via-[#EEF6FF] to-[#D5E8FF] border-blue-100', img: '/storage/bannercard-list/totalli.png' },
            { label: 'Draft & Revisi', value: this.data.filter(d => ['Draft', 'Revision'].includes(d.status)).length, icon: icons.warn, iconBg: 'bg-[#FFF0D4] text-[#D98E2E]', textColor: 'text-[#D98E2E]', cardBg: 'bg-gradient-to-br from-white via-[#FFF8ED] to-[#FFE9C0] border-amber-100', img: '/storage/bannercard-list/perlurevisi.png' },
            { label: 'Menunggu TTD', value: this.data.filter(d => ['Pending Approval', 'GL Approved'].includes(d.status)).length, icon: icons.clock, iconBg: 'bg-[#FEFFD4] text-[#D9D62E]', textColor: 'text-[#D9D62E]', cardBg: 'bg-gradient-to-br from-white via-[#FFF8ED] to-[#FFFFC0] border-amber-100', img: '/storage/bannercard-list/butuhdicek.png' },
            { label: 'Sudah Selesai', value: this.data.filter(d => d.status === 'Close').length, icon: icons.check, iconBg: 'bg-[#E3F8EA] text-[#41A966]', textColor: 'text-[#41A966]', cardBg: 'bg-gradient-to-br from-white via-[#EDFAF2] to-[#C8F0D8] border-emerald-100', img: '/storage/bannercard-list/sudahselesai.png' },
        ];
    }
});
