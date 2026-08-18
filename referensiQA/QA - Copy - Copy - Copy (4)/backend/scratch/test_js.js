
        function dashboardData() {
            return {
                role: '1',
                userId: 1,
                loading: true,
                activities: [],
                quickStats: [],
                monitoringList: [],
                overdueQprs: [],
                expandedId: null,
                currentTime: '--:--:--',
                clockInterval: null,

                async initDashboard() {
                    this.updateClock();
                    this.clockInterval = setInterval(() => this.updateClock(), 1000);
                    
                    this.loading = true;
                    try {
                        const ts = new Date().getTime();

                        // Fetch LI data
                        const resLi = await axios.get('/api/inspeksi');
                        const dataLi = Array.isArray(resLi.data) ? resLi.data : (resLi.data.data || []);
                        
                        // Fetch QPR data
                        let dataQpr = [];
                        try {
                            const resQpr = await axios.get(`/api/qprs?_t=${ts}`);
                            dataQpr = Array.isArray(resQpr.data) ? resQpr.data : (resQpr.data.data || []);
                        } catch (e) {
                            console.log('Error fetching QPR for dashboard', e);
                        }

                        // Fetch Item Check data
                        let dataIc = [];
                        try {
                            const resIc = await axios.get(`/api/item-check/summary?_t=${ts}`);
                            dataIc = Array.isArray(resIc.data) ? resIc.data : (resIc.data.data || []);
                        } catch (e) {
                            console.log('Error fetching Item Check for dashboard', e);
                        }
                        
                        this.processData(dataLi, dataIc, dataQpr);
                    } catch (e) {
                        console.error('Failed to load dashboard data', e);
                    } finally {
                        this.loading = false;
                    }
                },

                processData(listLi, listIc, listQpr) {
                    // â”€â”€ Central Status Config â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                    const LHI_STATUS = {
                        revision:            { role: 'Leader',       label: 'Perlu Revisi',     cls: 'bg-rose-50 text-rose-600' },
                        submitted:           { role: 'Foreman',      label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_foreman:     { role: 'Foreman',      label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_qc_approval: { role: 'Foreman',      label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_supervisor:  { role: 'Supervisor',   label: 'Menunggu SPV',     cls: 'bg-purple-50 text-purple-600' },
                        locked:              { role: 'Operator',     label: 'Siap Dicek QC',    cls: 'bg-sky-50 text-sky-600' },
                        ready_for_qc:        { role: 'Operator',     label: 'Siap Dicek QC',    cls: 'bg-sky-50 text-sky-600' },
                        _gl_qc_approval:     { role: 'Group Leader', label: 'Verifikasi QC',    cls: 'bg-amber-50 text-amber-600' },
                    };
                    const QPR_STATUS = {
                        draft:              { role: 'Operator',  label: 'Perlu Diisi',      cls: 'bg-sky-100 text-sky-700' },
                        waiting_foreman:    { role: 'Foreman',   label: 'Menunggu Foreman', cls: 'bg-amber-50 text-amber-600' },
                        waiting_supervisor: { role: 'Supervisor', label: 'Menunggu SPV',    cls: 'bg-purple-50 text-purple-600' },
                        waiting_manager:    { role: 'Manager',   label: 'Menunggu Manager', cls: 'bg-purple-50 text-purple-600' },
                    };
                    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

                    let acts = [];
                    let stats = [
                        { title: 'Total LI', value: listLi.length, bgClass: 'bg-blue-50', textClass: 'text-blue-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>' }
                    ];

                    let urgentLi = 0;
                    let monitor = [];
                    
                    listLi.forEach(d => {
                        let isUrgentForMe = false;
                        let statusLabel = d.status;
                        let statusClass = 'bg-slate-100 text-slate-600';
                        
                        // Monitoring step mapping
                        let step = 1;
                        let monitorLabel = 'Draft';
                        if (d.status === 'draft' || d.status === 'submitted' || d.status === 'revision') { step = 1; monitorLabel = (d.status === 'revision' ? 'Revisi OPR' : 'Draft / Submitted'); }
                        else if (d.status === 'waiting_foreman') { step = 2; monitorLabel = 'Menunggu Foreman'; }
                        else if (d.status === 'waiting_supervisor') { step = 3; monitorLabel = 'Menunggu SPV'; }
                        else if (d.status === 'locked' || d.status === 'ready_for_qc' || d.status === 'waiting_qc_approval') { step = 4; monitorLabel = 'QC / Verifikasi'; }
                        else if (d.status === 'finished' || d.status === 'approved') { step = 5; monitorLabel = 'Selesai'; }
                        
                        // Only track manual Lembar Inspeksi (not locked/archived)
                        if (d.status !== 'locked' && d.status !== 'archived_template') {
                            monitor.push({
                                id: d.id,
                                type: 'Lembar Inspeksi',
                                no_form: d.no_form || 'LI-Draft',
                                info: d.part_name || '-',
                                step: step,
                                statusLabel: monitorLabel,
                                url: `/li/${d.id}/edit`,
                                date: new Date(d.updated_at || d.created_at),
                                raw: d
                            });
                        }

                        // Resolve urgency using central LHI_STATUS config
                        const lhiCfg = LHI_STATUS[d.status];
                        const isGlQc = (this.role === 'Group Leader' && d.status === 'waiting_qc_approval');
                        if (isGlQc) {
                            isUrgentForMe = true;
                            statusLabel = LHI_STATUS._gl_qc_approval.label;
                            statusClass = LHI_STATUS._gl_qc_approval.cls;
                        } else if (lhiCfg && lhiCfg.role === this.role) {
                            isUrgentForMe = true;
                            statusLabel = lhiCfg.label;
                            statusClass = lhiCfg.cls;
                        } else if (this.role === 'Leader' && d.status === 'draft') {
                            isUrgentForMe = true;
                            statusLabel = 'Perlu Revisi';
                            statusClass = 'bg-rose-50 text-rose-600';
                        } else if (this.role === 'Admin' && d.status !== 'finished' && d.status !== 'approved') {
                            isUrgentForMe = true;
                            statusLabel = d.status;
                            statusClass = 'bg-amber-50 text-amber-600';
                        }

                        if (isUrgentForMe) {
                            urgentLi++;
                            acts.push({
                                id: d.id,
                                modul: 'Lembar Inspeksi',
                                no_form: d.no_form || 'Draft',
                                statusLabel: statusLabel,
                                statusClass: statusClass,
                                url: `/li/${d.id}/edit`,
                                date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });

                    stats.push({ title: 'Tugas Lembar Inspeksi', value: urgentLi, bgClass: 'bg-amber-50', textClass: 'text-amber-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' });

                    let urgentIc = 0;
                    listIc.forEach(d => {
                        let isUrgentForMe = false;
                        let statusLabel = d.status;
                        let statusClass = 'bg-slate-100 text-slate-600';
                        
                        let step = 1;
                        let monitorLabel = 'Proses';
                        if (d.status === 'draft' || d.status === 'in_progress' || d.status === 'submitted' || d.status === 'waiting_qc_approval') { 
                            step = 1; 
                            monitorLabel = 'Pengecekan Aktual'; 
                            if (d.status === 'waiting_qc_approval') { step = 2; monitorLabel = 'Review Pengecekan'; }
                            if (d.gl_signed) { step = 3; monitorLabel = 'Pengesahan Inspeksi'; }
                            if (d.foreman_signed) { step = 4; monitorLabel = 'Selesai'; }
                        }
                        else if (d.status === 'waiting_gl') { step = 2; monitorLabel = 'Menunggu TTD GL'; }
                        else if (d.status === 'waiting_foreman') { step = 3; monitorLabel = 'Review Foreman'; }
                        else if (d.status === 'finished' || d.status === 'locked' || d.status === 'approved') { step = 4; monitorLabel = 'Selesai'; }
                        
                        console.log('DEBUG PROCESS_DATA IC:', {id: d.id, no_form: d.no_form, status: d.status, gl_signed: d.gl_signed, step: step});
                        
                        // Add to Monitoring Pipeline
                        if (d.status !== 'finished') {
                            monitor.push({
                                id: d.id,
                                type: 'Item Check',
                                no_form: d.no_form || ('IC-' + String(d.id).padStart(5, '0')),
                                info: d.part_name || '-',
                                step: step,
                                statusLabel: monitorLabel,
                                url: `/item-check/${d.id}/form`,
                                date: new Date(d.updated_at || d.created_at),
                                raw: d
                            });
                        }

                        // Resolve urgency
                        if (d.status === 'waiting_gl' && this.role === 'Group Leader') {
                            isUrgentForMe = true; statusLabel = 'Menunggu GL'; statusClass = 'bg-amber-50 text-amber-600';
                        } else if (d.status === 'waiting_foreman' && (this.role === 'Foreman' || this.role === 'Supervisor')) {
                            isUrgentForMe = true; statusLabel = 'Menunggu Foreman'; statusClass = 'bg-amber-50 text-amber-600';
                        } else if (d.status === 'waiting_qc_approval' && (this.role === 'Group Leader' || this.role === 'Foreman')) {
                            isUrgentForMe = true; statusLabel = 'Verifikasi QC'; statusClass = 'bg-purple-50 text-purple-600';
                        }

                        if (isUrgentForMe) {
                            urgentIc++;
                            acts.push({
                                id: d.id, modul: 'Item Check', no_form: d.no_form || ('IC-' + String(d.id).padStart(5, '0')),
                                statusLabel: statusLabel, statusClass: statusClass, url: `/item-check/${d.id}/form`,
                                date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });

                    stats.push({ title: 'Tugas Item Check', value: urgentIc, bgClass: 'bg-blue-50', textClass: 'text-blue-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>' });

                    let urgentQpr = 0;
                    listQpr.forEach(d => {
                        let isUrgentForMe = false;
                        let statusLabel = d.status;
                        let statusClass = 'bg-slate-100 text-slate-600';

                        // Monitoring step mapping
                        let step = 1;
                        let monitorLabel = 'In Progress';
                        if (d.status === 'draft') { step = 1; monitorLabel = 'Investigasi / Temuan'; }
                        else if (d.status === 'waiting_foreman') { step = 2; monitorLabel = 'Pengecekan Awal (GL/Foreman)'; }
                        else if (d.status === 'waiting_supervisor' || d.status === 'waiting_manager' || d.status === 'waiting_seksi') { step = 3; monitorLabel = 'Persetujuan Seksi Terkait'; }
                        else if (d.status === 'verif_1') { step = 4; monitorLabel = 'Verifikasi 1'; }
                        else if (d.status === 'verif_2') { step = 5; monitorLabel = 'Verifikasi 2'; }
                        else if (d.status === 'verif_3') { step = 6; monitorLabel = 'Verifikasi 3'; }
                        else if (d.status === 'approved' || d.status === 'closed') { step = 6; monitorLabel = 'Selesai'; }

                        if (d.status !== 'approved' && d.status !== 'closed') {
                            let sourceLi = listLi.find(li => li.qpr_id === d.id);
                            let sourceLiUrl = sourceLi ? `/li/${sourceLi.id}/edit` : null;

                            monitor.push({
                                id: d.id,
                                type: 'QPR',
                                no_form: d.no_qpr || 'QPR-Draft',
                                info: d.nama_part ? (d.nama_part + ' (Job ' + (d.no_job || '-') + ')') : '-',
                                step: step,
                                statusLabel: monitorLabel,
                                url: `/qpr/${d.id}/edit`,
                                sourceLiUrl: sourceLiUrl,
                                date: new Date(d.updated_at || d.created_at),
                                raw: d
                            });
                        }

                        // Resolve urgency
                        // 1. QPR Draft: hanya operator yang di-assign (created_by match userId)
                        if (d.status === 'draft' || d.status === 'Draft') {
                            if (d.created_by === this.userId) {
                                isUrgentForMe = true;
                                statusLabel = 'Perlu Diisi';
                                statusClass = 'bg-sky-100 text-sky-700';
                            }
                        // 2. Status lain: gunakan QPR_STATUS config
                        } else {
                            const qprCfg = QPR_STATUS[d.status];
                            if (qprCfg && qprCfg.role === this.role) {
                                isUrgentForMe = true;
                                statusLabel = qprCfg.label;
                                statusClass = qprCfg.cls;
                            } else if (this.role === 'Admin' && d.status !== 'closed' && d.status !== 'approved') {
                                // Admin melihat semua QPR aktif
                                isUrgentForMe = true;
                                statusLabel = 'Monitoring';
                                statusClass = 'bg-slate-100 text-slate-600';
                            }
                        }

                        if (isUrgentForMe) {
                            urgentQpr++;
                            acts.push({
                                id: d.id,
                                modul: 'QPR',
                                no_form: d.no_qpr || 'QPR-Draft',
                                statusLabel: statusLabel,
                                statusClass: statusClass,
                                url: `/qpr/${d.id}/edit`,
                                date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });

                    // â”€â”€ Calculate Overdue QPRs â”€â”€
                    let overdueList = [];
                    let today = new Date();
                    today.setHours(0,0,0,0);
                    
                    listQpr.forEach(d => {
                        if (d.status !== 'approved' && d.status !== 'closed' && d.target_selesai) {
                            let targetDate = new Date(d.target_selesai);
                            targetDate.setHours(0,0,0,0);
                            let diffTime = targetDate.getTime() - today.getTime();
                            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            
                            // Tampilkan jika terlambat, hari ini, atau sisa <= 3 hari
                            if (diffDays <= 3) {
                                let urgencyText = '';
                                let urgencyClass = '';
                                
                                if (diffDays < 0) {
                                    urgencyText = `Terlambat ${Math.abs(diffDays)} Hari`;
                                    urgencyClass = 'bg-rose-100 text-rose-700';
                                } else if (diffDays === 0) {
                                    urgencyText = 'Hari Ini!';
                                    urgencyClass = 'bg-rose-500 text-white shadow-sm shadow-rose-200';
                                } else {
                                    urgencyText = `Sisa ${diffDays} Hari`;
                                    urgencyClass = 'bg-amber-100 text-amber-700';
                                }
                                
                                overdueList.push({ ...d, diffDays, urgencyText, urgencyClass });
                            }
                        }
                    });
                    
                    overdueList.sort((a, b) => a.diffDays - b.diffDays);
                    this.overdueQprs = overdueList;

                    stats.push({ title: 'Tugas QPR', value: urgentQpr, bgClass: 'bg-rose-50', textClass: 'text-rose-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' });

                    let finishedLi = listLi.filter(d => d.status === 'locked' || d.status === 'approved').length;
                    let finishedIc = listIc.filter(d => d.status === 'finished' || d.status === 'approved').length;
                    stats.push({ title: 'Item Check Selesai', value: finishedIc, bgClass: 'bg-emerald-50', textClass: 'text-emerald-600', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' });

                    // Sort activities by date desc
                    acts.sort((a, b) => b.date - a.date);
                    monitor.sort((a, b) => b.date - a.date);
                    
                    this.activities = acts.slice(0, 10);
                    this.monitoringList = monitor.slice(0, 20); // Track top 20 active processes
                    this.quickStats = stats;
                },

                getLiJudgement(item) {
                    // Judgement hanya valid setelah QC selesai sepenuhnya
                    const notFinalYet = ['draft', 'submitted', 'waiting_foreman', 'revision',
                                         'waiting_supervisor', 'locked', 'ready_for_qc', 'waiting_qc_approval'];
                    if (notFinalYet.includes(item.status)) {
                        return null; // Proses belum selesai, jangan tampilkan judgement
                    }

                    const j = item.qg_judgement;
                    if (!j) return null;

                    if (j === 'OK' || j === 'NG') return j;

                    try {
                        const obj = JSON.parse(j);
                        const values = Object.values(obj);
                        if (values.some(v => typeof v === 'string' && v.toUpperCase().includes('NG'))) {
                            return 'NG';
                        }
                        return 'OK'; 
                    } catch (e) {
                        return j.toUpperCase().includes('NG') ? 'NG' : 'OK';
                    }
                },

                updateClock() {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    this.currentTime = `${hours}:${minutes}:${seconds}`;
                }
            }
        }

        function supervisorDashboard() {
            return {
                stats: {
                    total: 0, pending: 0, ng: 0, finished: 0,
                    ng_rate: 0, completion_rate: 0, approval_rate: 0,
                    avg_per_day: 0, max_day: 0, min_day: 0, week_total: 0,
                    trend_total: ''
                },
                recentActivities: [],
                topDefectParts: [],
                _chartInstances: {},

                async init() {
                    try {
                        const res = await axios.get('/api/inspeksi');
                        const data = Array.isArray(res.data) ? res.data : (res.data.data || []);
                        this.processData(data);
                        this.$nextTick(() => setTimeout(() => this.renderCharts(data), 80));
                    } catch(e) { console.error('Supervisor dashboard error:', e); }
                },

                processData(data) {
                    const total = data.length;
                    const ng = data.filter(d => d.qg_judgement && d.qg_judgement.includes('NG')).length;
                    const pending = data.filter(d => d.status === 'waiting_supervisor').length;
                    const finished = data.filter(d => d.status === 'finished' || d.status === 'approved').length;
                    const approved = data.filter(d => d.status !== 'waiting_supervisor' && d.status !== 'revision' && d.status !== 'draft').length;

                    this.stats.total = total;
                    this.stats.pending = pending;
                    this.stats.ng = ng;
                    this.stats.finished = finished;
                    this.stats.ng_rate = total ? (ng / total * 100).toFixed(1) : 0;
                    this.stats.completion_rate = total ? Math.round(finished / total * 100) : 0;
                    this.stats.approval_rate = total ? Math.round(approved / total * 100) : 0;

                    // 7-day trend
                    const days = {};
                    const now = new Date();
                    for (let i = 6; i >= 0; i--) {
                        const d = new Date(now); d.setDate(d.getDate() - i);
                        const key = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                        days[key] = 0;
                    }
                    data.forEach(d => {
                        const dt = new Date(d.tgl_bulan || d.created_at);
                        const key = dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                        if (days.hasOwnProperty(key)) days[key]++;
                    });

                    const dayVals = Object.values(days);
                    this.stats.week_total = dayVals.reduce((a,b) => a+b, 0);
                    this.stats.avg_per_day = (this.stats.week_total / 7).toFixed(1);
                    this.stats.max_day = Math.max(...dayVals);
                    this.stats.min_day = Math.min(...dayVals);
                    this.stats.trend_total = '+' + this.stats.week_total + ' minggu ini';

                    // Activity feed (latest 8, sorted by date)
                    const statusLabel = {
                        waiting_supervisor: { label: 'Menunggu Approve SPV', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>', bg: 'bg-violet-100 text-violet-600' },
                        finished: { label: 'Selesai diverifikasi', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', bg: 'bg-emerald-100 text-emerald-600' },
                        approved: { label: 'Selesai diverifikasi', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', bg: 'bg-emerald-100 text-emerald-600' },
                        waiting_foreman: { label: 'Menunggu Foreman', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', bg: 'bg-amber-100 text-amber-600' },
                        revision: { label: 'Perlu Revisi', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>', bg: 'bg-rose-100 text-rose-600' },
                    };

                    const sorted = [...data].sort((a,b) => new Date(b.updated_at||b.created_at) - new Date(a.updated_at||a.created_at));
                    this.recentActivities = sorted.slice(0, 8).map(d => {
                        const cfg = statusLabel[d.status] || { label: d.status, icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>', bg: 'bg-slate-100 text-slate-600' };
                        const dt = new Date(d.updated_at || d.created_at);
                        const mins = Math.round((now - dt) / 60000);
                        const timeStr = mins < 60 ? mins + ' menit lalu' : mins < 1440 ? Math.round(mins/60) + ' jam lalu' : Math.round(mins/1440) + ' hari lalu';
                        return { id: d.id, title: cfg.label + (d.no_form ? ' â€” ' + d.no_form : ''), sub: (d.part_name||'-') + (d.line ? ' Â· Line ' + d.line : ''), icon: cfg.icon, iconBg: cfg.bg, time: timeStr };
                    });

                    // Top NG Parts
                    const ngParts = {};
                    data.filter(d => d.qg_judgement && d.qg_judgement.includes('NG')).forEach(d => {
                        const p = d.part_name || 'Unknown'; ngParts[p] = (ngParts[p]||0) + 1;
                    });
                    const maxNg = Math.max(...Object.values(ngParts), 1);
                    this.topDefectParts = Object.entries(ngParts).sort((a,b)=>b[1]-a[1]).slice(0,5)
                        .map(([name, count]) => ({ name, count, pct: Math.round(count/maxNg*100) }));
                },

                renderCharts(data) {
                    if (typeof Chart === 'undefined') return;

                    // 7-day Trend Line Chart
                    const volCtx = document.getElementById('spvVolumeChart');
                    if (volCtx) {
                        if (Chart.getChart(volCtx)) Chart.getChart(volCtx).destroy();
                        const days = {}; const now = new Date();
                        for (let i = 6; i >= 0; i--) {
                            const d = new Date(now); d.setDate(d.getDate()-i);
                            days[d.toLocaleDateString('id-ID',{day:'numeric',month:'short'})] = 0;
                        }
                        data.forEach(d => {
                            const dt = new Date(d.tgl_bulan||d.created_at);
                            const k = dt.toLocaleDateString('id-ID',{day:'numeric',month:'short'});
                            if(days.hasOwnProperty(k)) days[k]++;
                        });
                        const labels = Object.keys(days), values = Object.values(days);
                        new Chart(volCtx, {
                            type:'line', data:{ labels, datasets:[{
                                data: values, borderColor:'#e11d48',
                                backgroundColor:'rgba(225,29,72,0.08)', borderWidth:2.5,
                                fill:true, tension:0.4,
                                pointBackgroundColor:'#e11d48', pointBorderColor:'#fff',
                                pointBorderWidth:2, pointRadius:5, pointHoverRadius:7
                            }]},
                            options:{ responsive:true, maintainAspectRatio:false,
                                plugins:{ legend:{display:false}, tooltip:{
                                    callbacks:{ label: ctx => ' ' + ctx.parsed.y + ' inspeksi' }
                                }},
                                scales:{
                                    y:{beginAtZero:true, ticks:{stepSize:1}, grid:{color:'rgba(0,0,0,0.04)'}, border:{display:false}},
                                    x:{grid:{display:false}, border:{display:false}}
                                }
                            }
                        });
                    }

                    // Completion Donut
                    const compCtx = document.getElementById('spvCompletionChart');
                    if (compCtx) {
                        if (Chart.getChart(compCtx)) Chart.getChart(compCtx).destroy();
                        const v = this.stats.completion_rate;
                        new Chart(compCtx, { type:'doughnut', data:{ datasets:[{
                            data:[v,100-v], backgroundColor:['#10b981','#f0fdf4'],
                            borderWidth:0, cutout:'78%'
                        }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{tooltip:{enabled:false}} } });
                    }

                    // Approval Donut
                    const apprCtx = document.getElementById('spvApprovalChart');
                    if (apprCtx) {
                        if (Chart.getChart(apprCtx)) Chart.getChart(apprCtx).destroy();
                        const v = this.stats.approval_rate;
                        new Chart(apprCtx, { type:'doughnut', data:{ datasets:[{
                            data:[v,100-v], backgroundColor:['#3b82f6','#eff6ff'],
                            borderWidth:0, cutout:'78%'
                        }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{tooltip:{enabled:false}} } });
                    }
                }
            }
        }

        // â•â• DASHBOARD SUMMARY (Supervisor) â•â•
        function dashboardSummary() {
            return {
                loading: false,
                trackingLoading: true,
                activePreset: 'today',
                dateFrom: '',
                dateTo: '',
                presets: [
                    { key: 'today',   label: 'Hari Ini' },
                    { key: 'week',    label: '7 Hari' },
                    { key: 'month',   label: 'Bulan Ini' },
                    { key: 'custom',  label: 'Custom' },
                ],
                selectedPart: 'Semua',
                availableParts: [],
                get formattedPeriode() {
                    const fmt = (d) => {
                        if (!d) return '';
                        return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                    };
                    if (!this.dateFrom || !this.dateTo) return 'Memuat...';
                    if (this.dateFrom === this.dateTo) return fmt(this.dateFrom);
                    return fmt(this.dateFrom) + ' - ' + fmt(this.dateTo);
                },
                get filteredLi() {
                    if (this.selectedPart === 'Semua') return this.li.items;
                    return this.li.items.filter(i => i.part_name === this.selectedPart);
                },
                get filteredTotal() { return this.filteredLi.length; },
                get filteredOk() { return this.filteredLi.filter(i => this.getJudgement(i) === 'OK').length; },
                get filteredNg() { return this.filteredLi.filter(i => this.getJudgement(i) === 'NG').length; },
                get filteredFinished() { return this.filteredLi.filter(i => i.status === 'finished' || i.status === 'approved').length; },
                get partStatsArray() {
                    const stats = {};
                    this.li.items.forEach(i => {
                        const name = i.part_name || 'Unknown Part';
                        if (!stats[name]) {
                            stats[name] = { 
                                name: name, 
                                total: 0, 
                                ok: 0, 
                                ng: 0, 
                                finished: 0, 
                                jobs: new Set(), 
                                lines: new Set(), 
                                jobsMap: {},
                                missingFormula: false,
                                claimedMissingFormula: false,
                                activeProgressTotal: 0,
                                activeProgressChecked: 0,
                                hasActive: false
                            };
                        }
                        
                        const isClaimed = !!i.assigned_operator_id || !!i.operator_claimed_at;
                        const hasFormula = !!(i.sampling_cols && i.sampling_cols.length > 0) || Number(i.max_sample) > 0 || Number(i.tact_time) > 0 || Number(i.ct_dimensi) > 0;
                        if (!hasFormula) {
                            stats[name].missingFormula = true;
                            if (isClaimed) {
                                stats[name].claimedMissingFormula = true;
                            }
                        }

                        stats[name].hasActive = true;
                        if (hasFormula) {
                            let totalSamples = 0;
                            if (i.sampling_cols && i.sampling_cols.length > 0) {
                                totalSamples = i.sampling_cols.length;
                            } else {
                                totalSamples = Number(i.max_sample) || 0;
                            }
                            stats[name].activeProgressTotal = Math.max(stats[name].activeProgressTotal, totalSamples);
                            
                            let checked = 0;
                            if (i.status === 'finished' || i.status === 'approved' || i.status === 'locked') {
                                checked = totalSamples;
                            } else {
                                // hasil_visual is directly on i (ItemCheck object from /api/item-check/summary)
                                const samplesMap = {};
                                try {
                                    let data = i.hasil_visual;
                                    if (typeof data === 'string') data = JSON.parse(data);
                                    if (data && typeof data === 'object') {
                                        for (let key in data) {
                                            const match = key.match(/_(\d+)$/);
                                            if (match) {
                                                const col = match[1];
                                                const val = String(data[key]).trim().toLowerCase();
                                                if (val === 'ok' || val === 'ng') {
                                                    samplesMap[col] = true;
                                                }
                                            }
                                        }
                                    }
                                } catch(e) {}
                                checked = Object.keys(samplesMap).length;
                            }
                            stats[name].activeProgressChecked = Math.max(stats[name].activeProgressChecked, checked);
                        }

                        stats[name].total++;
                        
                        if (i.job_no) {
                            stats[name].jobs.add(i.job_no);
                            if (!stats[name].jobsMap[i.job_no]) stats[name].jobsMap[i.job_no] = 0;
                            stats[name].jobsMap[i.job_no] = Math.max(stats[name].jobsMap[i.job_no], Number(i.total_produksi) || 0);
                        } else {
                            // If no job_no, just accumulate to a generic key so we still count it
                            if (!stats[name].jobsMap['no_job']) stats[name].jobsMap['no_job'] = 0;
                            stats[name].jobsMap['no_job'] += Number(i.total_produksi) || 0; 
                        }

                        if (i.line_name) {
                            stats[name].lines.add(i.line_name);
                        } else if (i.line) {
                            stats[name].lines.add(i.line);
                        } else if (i.lokasi) {
                            stats[name].lines.add(i.lokasi);
                        }

                        const j = this.getJudgement(i);
                        
                        let docCheckedSamples = 0;
                        let totalSamples = 0;
                        if (i.sampling_cols && i.sampling_cols.length > 0) {
                            totalSamples = i.sampling_cols.length;
                        } else {
                            totalSamples = Number(i.max_sample) || 0;
                        }

                        // hasil_visual & hasil_dimensi are directly on i (ItemCheck object)
                        const samplesMapOk = {};
                        try {
                            let visualData = i.hasil_visual;
                            if (typeof visualData === 'string') visualData = JSON.parse(visualData);
                            if (visualData && typeof visualData === 'object') {
                                for (let key in visualData) {
                                    const match = key.match(/_(\d+)$/);
                                    if (match) {
                                        const col = match[1];
                                        const val = String(visualData[key]).trim();
                                        if (val !== '') samplesMapOk[col] = true;
                                    }
                                }
                            }
                        } catch(e) {}
                        try {
                            let dimData = i.hasil_dimensi;
                            if (typeof dimData === 'string') dimData = JSON.parse(dimData);
                            if (dimData && typeof dimData === 'object') {
                                for (let key in dimData) {
                                    const match = key.match(/_(\d+)$/);
                                    if (match) {
                                        const col = match[1];
                                        const val = String(dimData[key]).trim();
                                        if (val !== '') samplesMapOk[col] = true;
                                    }
                                }
                            }
                        } catch(e) {}
                        docCheckedSamples = Object.keys(samplesMapOk).length;

                        if (i.status === 'finished' || i.status === 'approved' || i.status === 'locked') {
                            docCheckedSamples = Math.max(docCheckedSamples, totalSamples);
                        }

                        let sampleNgCount = 0;
                        if (j === 'NG') {
                            let rejectQty = Number(i.reject) || 0;
                            if (rejectQty > 0 && rejectQty <= totalSamples) {
                                sampleNgCount = rejectQty;
                            } else {
                                sampleNgCount = 1; 
                            }
                        }

                        let sampleOkCount = Math.max(0, docCheckedSamples - sampleNgCount);

                        stats[name].ok += sampleOkCount;
                        stats[name].ng += sampleNgCount;
                        
                        if (i.status === 'finished' || i.status === 'approved') stats[name].finished++;
                    });
                    
                    return Object.values(stats).map(s => {
                        let prodTotal = 0;
                        for (let j in s.jobsMap) {
                            prodTotal += s.jobsMap[j];
                        }
                        return {
                            ...s,
                            prodTotal,
                            jobText: s.jobs.size > 0 ? Array.from(s.jobs).join(' & ') : '-',
                            lineText: s.lines && s.lines.size > 0 ? Array.from(s.lines).join(', ') : '-'
                        };
                    }).sort((a, b) => b.total - a.total);
                },

                li:  { items: [] },
                qpr: { total: 0, pending: 0, approved: 0 },
                stats: { avg_per_day: 0, week_total: 0 },
                monitoringList: [],
                overdueQprs: [],

                async init() {
                    this.applyPreset('today');
                    // Data tracking sudah diinject dari server - langsung load
                    this.monitoringList = [];
                    this.trackingLoading = false;
                    // Fetch QPR global stats tanpa filter tanggal
                    this.fetchQprGlobalStats();
                },

                getPercentage(val, total) {
                    if (!total) return 0;
                    const p = (val / total) * 100;
                    return parseFloat(p.toFixed(1));
                },

                getJudgement(item) {
                    const notFinalYet = ['draft', 'submitted', 'waiting_foreman', 'revision',
                                         'waiting_supervisor', 'locked', 'ready_for_qc', 'waiting_qc_approval'];
                    if (notFinalYet.includes(item.status)) return null;

                    const j = item.qg_judgement || item.judgement; // handle both cases
                    if (!j) return null;
                    if (j === 'OK' || j === 'NG') return j;
                    try {
                        const obj = typeof j === 'string' ? JSON.parse(j) : j;
                        const values = Object.values(obj);
                        if (values.some(v => typeof v === 'string' && v.toUpperCase().includes('NG'))) return 'NG';
                        return 'OK'; 
                    } catch (e) {
                        return typeof j === 'string' && j.toUpperCase().includes('NG') ? 'NG' : 'OK';
                    }
                },

                async fetchQprGlobalStats() {
                    try {
                        const res = await fetch('/api/qprs?per_page=1000');
                        const data = await res.json();
                        const items = data.data || data || [];
                        this.qpr.total   = items.length;
                        this.qpr.pending = items.filter(i =>
                            i.status === 'Pending Approval' ||
                            i.status === 'GL Approved' ||
                            i.status === 'Progress'
                        ).length;
                        this.qpr.approved = items.filter(i => i.hasil === 'a').length;
                    } catch(e) {
                        console.warn('QPR global stats error:', e);
                    }
                },

                async fetchLiveTracking() {
                    // Fallback: reload data jika diperlukan (misal setelah update)
                    this.trackingLoading = true;
                    try {
                        const ts = new Date().getTime();
                        const liRes = await fetch(`/api/item-check/summary?per_page=200&_t=${ts}`, { cache: 'no-store' });
                        if (!liRes.ok) throw new Error('API error: ' + liRes.status);
                        const liData = await liRes.json();
                        const listLi = liData.data || liData || [];
                        this.processMonitoring(listLi, []);
                    } catch(e) {
                        // Kalau gagal, biarkan data server-side tetap tampil
                        console.warn('Tracking fetch error (using server data):', e);
                    } finally {
                        this.trackingLoading = false;
                    }
                },

                applyPreset(key) {
                    this.activePreset = key;
                    const now = new Date();
                    const fmt = d => d.toISOString().slice(0, 10);
                    if (key === 'today') {
                        this.dateFrom = this.dateTo = fmt(now);
                    } else if (key === 'week') {
                        const w = new Date(now); w.setDate(now.getDate() - 6);
                        this.dateFrom = fmt(w); this.dateTo = fmt(now);
                    } else if (key === 'month') {
                        this.dateFrom = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
                        this.dateTo   = fmt(now);
                    }
                    if (key !== 'custom') this.fetch();
                },

                shiftDays(offset) {
                    const fmt = d => d.toISOString().slice(0, 10);
                    // If it's a range, we just shift both dates by the offset
                    let from = new Date(this.dateFrom);
                    let to = new Date(this.dateTo);
                    from.setDate(from.getDate() + offset);
                    to.setDate(to.getDate() + offset);
                    
                    this.dateFrom = fmt(from);
                    this.dateTo = fmt(to);
                    this.activePreset = 'custom';
                    this.fetch();
                },

                async fetch() {
                    this.loading = true;
                    try {
                        const [liRes, qprRes] = await Promise.all([
                            fetch(`/api/item-check/summary?from=${this.dateFrom}&to=${this.dateTo}&per_page=200`),
                            fetch(`/api/qprs?from=${this.dateFrom}&to=${this.dateTo}&per_page=200`)
                        ]);
                        const liData  = await liRes.json();
                        const qprData = await qprRes.json();

                        const liItems = liData.data || liData || [];
                        this.li.items    = liItems;
                        this.li.total    = liItems.length;
                        this.li.ok       = liItems.filter(i => this.getJudgement(i) === 'OK').length;
                        this.li.ng       = liItems.filter(i => this.getJudgement(i) === 'NG').length;
                        this.li.finished = liItems.filter(i => i.status === 'finished' || i.status === 'approved').length;


                        // Tambahkan tanggal & judgement ke setiap item untuk tabel
                        this.li.items = liItems.map(i => {
                            return { ...i, judgement: this.getJudgement(i) || 'â€”', tanggal: i.created_at ? new Date(i.created_at).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'}) : 'â€”' };
                        });
                        
                        this.availableParts = [...new Set(this.li.items.map(i => i.part_name).filter(p => p))];
                        if (this.selectedPart !== 'Semua' && !this.availableParts.includes(this.selectedPart)) {
                            this.selectedPart = 'Semua';
                        }

                        const qprItems   = qprData.data || qprData || [];
                        // qpr.total, pending & approved dihitung dari global stats (fetchQprGlobalStats)
                        // supaya QPR dari bulan lalu tetap terhitung

                        // Calculate Overdue QPRs
                        let overdueList = [];
                        let today = new Date();
                        today.setHours(0,0,0,0);
                        
                        qprItems.forEach(d => {
                            if (d.status !== 'approved' && d.status !== 'closed' && d.target_selesai) {
                                let targetDate = new Date(d.target_selesai);
                                targetDate.setHours(0,0,0,0);
                                let diffTime = targetDate.getTime() - today.getTime();
                                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                
                                // Tampilkan jika terlambat, hari ini, atau sisa <= 3 hari
                                if (diffDays <= 3) {
                                    let urgencyText = '';
                                    let urgencyClass = '';
                                    
                                    if (diffDays < 0) {
                                        urgencyText = `Terlambat ${Math.abs(diffDays)} Hari`;
                                        urgencyClass = 'bg-[#FFF1F2] text-[#BE123C] border border-[#FFE4E6]';
                                    } else if (diffDays === 0) {
                                        urgencyText = 'Hari Ini!';
                                        urgencyClass = 'bg-[#E11D48] text-white shadow-sm shadow-[#E11D48]/30';
                                    } else {
                                        urgencyText = `Sisa ${diffDays} Hari`;
                                        urgencyClass = 'bg-[#FFFBEB] text-[#D97706] border border-[#FEF3C7]';
                                    }
                                    
                                    overdueList.push({ ...d, diffDays, urgencyText, urgencyClass });
                                }
                            }
                        });
                        
                        overdueList.sort((a, b) => a.diffDays - b.diffDays);
                        this.overdueQprs = overdueList;
                        
                        // processMonitoring dipindahkan ke fetchLiveTracking() agar independen
                    } catch(e) {
                        console.error('Dashboard fetch error:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                processMonitoring(listLi, listQpr) {
                    let monitor = [];
                    listLi.forEach(d => {
                        let step = 1;
                        let monitorLabel = 'Inspeksi Berjalan';
                        if (d.status === 'draft' || d.status === 'in_progress' || d.status === 'submitted' || d.status === 'waiting_qc_approval') { 
                            step = 1; 
                            monitorLabel = 'Pengecekan Aktual'; 
                            if (d.status === 'waiting_qc_approval') { step = 2; monitorLabel = 'Review Pengecekan'; }
                            if (d.gl_signed) { step = 3; monitorLabel = 'Pengesahan Inspeksi'; }
                            if (d.foreman_signed) { step = 4; monitorLabel = 'Selesai'; }
                        }
                        else if (d.status === 'waiting_gl') { step = 2; monitorLabel = 'Menunggu TTD GL'; }
                        else if (d.status === 'waiting_foreman') { step = 3; monitorLabel = 'Review Foreman'; }
                        else if (d.status === 'finished' || d.status === 'locked' || d.status === 'approved') { step = 4; monitorLabel = 'Selesai'; }
                        
                        if (d.status !== 'finished' && d.status !== 'locked') {
                            monitor.push({
                                id: d.id, type: 'Item Check', no_form: d.no_form || ('IC-' + String(d.id).padStart(5, '0')), no_job: d.job_no || '-', line: d.line || '-', info: d.part_name || '-',
                                step: step, statusLabel: monitorLabel, url: `/item-check/${d.id}/form`, date: d.updated_at || d.created_at, created_at: d.created_at
                            });
                        }
                    });

                    listQpr.forEach(d => {
                        let step = 1; let monitorLabel = 'Proses Lanjutan';
                        let s = (d.status || '').toLowerCase();
                        if (['draft', 'open', 'revision'].includes(s)) { step = 1; monitorLabel = 'Investigasi Temuan'; }
                        else if (['pending approval', 'gl approved'].includes(s)) { step = 2; monitorLabel = 'Pengecekan Awal (GL)'; }
                        else if (s.includes('action') || s.includes('progress') || s.includes('a3')) { step = 3; monitorLabel = 'Tindakan Seksi Terkait'; }
                        else if (s.includes('verif 1')) { step = 4; monitorLabel = 'Verifikasi 1'; }
                        else if (s.includes('verif 2')) { step = 5; monitorLabel = 'Verifikasi 2'; }
                        else if (s.includes('verif 3')) { step = 6; monitorLabel = 'Verifikasi 3'; }
                        else if (s.includes('close')) { step = 6; monitorLabel = 'Selesai'; }
                        
                        if (!s.includes('close') && s !== 'approved' && s !== 'finished') {
                            monitor.push({
                                id: d.id, type: 'QPR', no_form: d.no_qpr || ('QPR-' + String(d.id).padStart(5, '0')), no_job: d.no_job || '-', line: d.line || '-', info: d.nama_part || '-',
                                step: step, statusLabel: monitorLabel, url: `/qpr/${d.id}/edit`, date: new Date(d.updated_at || d.created_at)
                            });
                        }
                    });
                    monitor.sort((a, b) => b.date - a.date);
                    this.monitoringList = monitor.slice(0, 10);
                    
                    // Reactive update to the currently viewed tracked object
                    if (this.tracked && this.tracked.id) {
                        const updatedItem = this.monitoringList.find(i => i.id === this.tracked.id && i.type === this.tracked.type);
                        if (updatedItem) {
                            this.tracked = updatedItem;
                        }
                    }
                },


            };
        }

        // â•â• CALENDAR NOTES â•â•
        function calendarNotes() {
            return {
                currentYear:  new Date().getFullYear(),
                currentMonth: new Date().getMonth(),
                selectedDate: null,
                currentNote:  '',
                calendarDays: [],
                showNoteModal: false,
                monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],

                get upcomingNotes() {
                    const prefix = `${this.currentYear}-${String(this.currentMonth+1).padStart(2,'0')}`;
                    const all = Object.entries(JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}'));
                    return all
                        .filter(([d,t]) => d.startsWith(prefix) && t.trim())
                        .sort(([a],[b]) => a.localeCompare(b))
                        .map(([d,t]) => ({
                            date: d, text: t,
                            day:  parseInt(d.slice(8)),
                            dateLabel: new Date(d+'T00:00:00').toLocaleDateString('id-ID',{day:'numeric',month:'long'})
                        }));
                },

                initCalendar() {
                    const today = new Date().toISOString().slice(0,10);
                    this.selectedDate = today;
                    this.loadNote();
                    this.buildCalendar();
                },

                buildCalendar() {
                    const first = new Date(this.currentYear, this.currentMonth, 1);
                    // Monday-based: Mon=0 ... Sun=6
                    let startDow = (first.getDay() + 6) % 7;
                    const daysInMonth = new Date(this.currentYear, this.currentMonth+1, 0).getDate();
                    const cells = [];
                    for (let i = 0; i < startDow; i++) cells.push({ key:'e'+i, date:null, label:'' });
                    for (let d = 1; d <= daysInMonth; d++) {
                        const dateStr = `${this.currentYear}-${String(this.currentMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                        cells.push({ key: dateStr, date: dateStr, label: String(d) });
                    }
                    this.calendarDays = cells;
                },

                prevMonth() {
                    if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; }
                    else this.currentMonth--;
                    this.buildCalendar();
                },

                nextMonth() {
                    if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; }
                    else this.currentMonth++;
                    this.buildCalendar();
                },

                selectDay(date) {
                    this.selectedDate = date;
                    this.loadNote();
                    this.showNoteModal = true;
                },

                closeModal() {
                    this.showNoteModal = false;
                },

                loadNote() {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    this.currentNote = notes[this.selectedDate] || '';
                },

                saveNote() {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    if (this.currentNote.trim()) {
                        notes[this.selectedDate] = this.currentNote;
                    } else {
                        delete notes[this.selectedDate];
                    }
                    localStorage.setItem('qa_calendar_notes', JSON.stringify(notes));
                },

                deleteNote() {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    delete notes[this.selectedDate];
                    localStorage.setItem('qa_calendar_notes', JSON.stringify(notes));
                    this.currentNote = '';
                },

                getNoteText(date) {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    return notes[date] || '';
                },

                hasNote(date) {
                    const notes = JSON.parse(localStorage.getItem('qa_calendar_notes') || '{}');
                    return !!(notes[date] && notes[date].trim());
                },

                isToday(date) {
                    return date === new Date().toISOString().slice(0,10);
                },

                formatDisplayDate(date) {
                    if (!date) return '';
                    return new Date(date+'T00:00:00').toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
                }
            };
        }

    
