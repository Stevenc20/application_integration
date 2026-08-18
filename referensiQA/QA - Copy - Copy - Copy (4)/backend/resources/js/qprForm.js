import axios from 'axios';

// #region agent log
// #endregion

const SEKSI_LIST = [
    "IRM",
    "Produksi SA",
    "Produksi Stamping",
    "Produksi Metal Finish",
    "Logistic",
    "PPC",
    "Delivery",
    "Procurement",
    "Dies Shop",
    "Plant Service",
    "Quality Assurance",
    "Maintenance"
];

window.qprForm = (config) => ({
    step: 1,
    editId: config.id || null,
    loading: false,
    loadingData: false,
    seksiList: SEKSI_LIST,
    userRole: config?.userRole || 'Guest',
    userDepartment: config?.userDepartment || null,
    userId: config?.userId || null,
    userName: config?.userName || null,
    isRequestMode: new URLSearchParams(window.location.search).get('mode') === 'request',

    get availableSteps() {
        const baseSteps = [
            { num: 1, label: 'Identifikasi', desc: 'Data Part' },
            { num: 2, label: 'Deskripsi', desc: 'Detail Masalah' }
        ];

        if (this.isRequestMode) {
            return baseSteps;
        }

        return [
            ...baseSteps,
            { num: 3, label: 'Analisa 4M+1E', desc: 'Penyebab' },
            { num: 4, label: 'Countermeasure', desc: 'Tindakan' },
            { num: 5, label: 'Verifikasi', desc: 'Hasil & A3' }
        ];
    },

    get canEditSeksiSection() {
        if (this.userRole === 'Admin') return true;

        if (this.form.pic_seksi && this.userDepartment === this.form.pic_seksi) {
            return true;
        }

        return false;
    },

    get canEditVerifSection() {
        if (this.userRole === 'Admin') return true;
        if (['Kasie QA', 'Kasie', 'Group Leader', 'Foreman'].includes(this.userRole)) {
            return true;
        }
        if (this.userDepartment === 'Quality Control' || this.userDepartment === 'Quality Assurance') {
            return true;
        }
        return false;
    },

    get canEditBasicInfo() {
        // Pembuat QPR baru selalu bisa mengedit form awal mereka
        if (!this.editId) return true;

        if (this.userRole === 'Admin') return true;
        if (this.userRole === 'Operator' && this.form.created_by == this.userId) return true;
        if (['Group Leader', 'Foreman', 'Supervisor'].includes(this.userRole)) return true;
        return false;
    },

    get canSubmitApprove() {
        if (this.form.status === 'Close') return false;

        if (this.userRole === 'Admin') return true;

        if (this.userRole === 'Operator') {
            return false; // Operator does not approve, they only create/edit
        }

        if (['Group Leader', 'Foreman'].includes(this.userRole)) {
            return (!this.editId) || (this.form.assigned_foreman_id == this.userId);
        }

        if (this.canEditSeksiSection) return true;

        if (this.form.approval_signatures) {
            const isSeksiSigner = this.form.approval_signatures.some(s => s.position === 'seksi' && s.role === this.userDepartment);
            if (isSeksiSigner) return true;
        }

        if (['Kasie QA', 'Kasie'].includes(this.userRole)) return true;

        return false;
    },

    get submitButtonText() {
        if (this.loading) return 'Memproses...';

        if (this.isRequestMode) {
            return 'Ajukan QPR ke QA';
        }

        const isSeksi = this.seksiOptions.includes(this.userDepartment);
        const isQA = ['Kasie QA', 'Kasie', 'Group Leader', 'Foreman'].includes(this.userRole) && !isSeksi;

        if (this.form.status === 'Draft' || this.form.status === 'OPEN') {
            return 'Simpan & Kirim';
        }
        if (this.form.status === 'Pending Approval') {
            return 'Approve (GL/Foreman)';
        }

        // If Seksi is looking at it, and it's their turn to action/verify
        if (isSeksi && ['GL Approved', 'Progress', 'Waiting Action 1', 'Waiting Action 2', 'Waiting Action 3'].includes(this.form.status)) {
            const lastAction = this.form.actions && this.form.actions.length > 0
                ? this.form.actions[this.form.actions.length - 1]
                : null;
            if (lastAction && ['C', 'A'].includes(lastAction.pdca)) {
                return 'Simpan & Minta Verif QA';
            }
            return 'Simpan Progress';
        }

        // If QA is looking at it and it's verification time
        if (isQA && this.form.status.startsWith('Waiting Verif')) {
            return 'Verifikasi & Simpan';
        }

        // If it's the final step and Kasie QA is approving
        if (this.userRole === 'Kasie QA' && ['Waiting Verif 3', 'Waiting A3 Report'].includes(this.form.status)) {
            return 'Approve & Close QPR';
        }

        return 'Simpan / Approve';
    },

    canSignRole(roleType) {
        if (this.userRole === 'Admin') return true;

        if (roleType === 'Operator') {
            if (this.userRole === 'Operator') {
                // Bisa TTD jika dia yang buat
                if ((!this.editId) || this.form.created_by == this.userId) return true;
                // ATAU jika ini adalah Pengajuan Baru (OPEN, blm ada foreman) dan dia adalah QA Operator
                if (this.form.status === 'OPEN' && !this.form.assigned_foreman_id && ['QA', 'Quality Assurance', 'Quality Control'].includes(this.userDepartment)) return true;
            }
            return false;
        }
        if (roleType === 'Foreman') {
            return ['Group Leader', 'Foreman'].includes(this.userRole) && ((!this.editId) || this.form.assigned_foreman_id == this.userId);
        }

        if (this.seksiOptions.includes(roleType)) {
            return this.userDepartment === roleType;
        }
        return false;
    },

    glUsers: [],
    fmUsers: [],
    spvUsers: [],
    operatorUsers: [],
    allUsersById: {},
    allUsersByDept: {},
    seksiOptions: SEKSI_LIST,

    get availableForemen() {
        const allowedNames = ['murdianto', 'susang raharjo', 'azriel', 'dedy purwanto', 'deddy purwanto'];
        return [...this.glUsers, ...this.fmUsers].filter(u => {
            const lowerName = (u.nama || '').toLowerCase();
            return allowedNames.some(allowed => lowerName.includes(allowed));
        });
    },

    form: {
        no_job: "", model: "", tanggal: new Date().toISOString().split('T')[0],
        nama_part: "", no_qpr: "", kontrol_part: "",
        rework_qty: 0, reject_qty: 0, stock_ippi_qty: 0,
        rencana_produksi: "", proses_repair: "",
        kategori_problem: "", defect: "", defect_keterangan: "",
        lokasi: "", shift: "", jam: "", last_date_problem: "", sketch: "", pic: "",
        analisa_man: false, analisa_method: false, analisa_machine: false,
        analisa_material: false, analisa_environment: false,
        analisa_man_ket: "", analisa_method_ket: "", analisa_machine_ket: "",
        analisa_material_ket: "", analisa_environment_ket: "",
        status: "OPEN",
        area: "",
        area_problems: {},
        is_a3_required: false,
        a3_due_date: "",
        a3_document: "",
        parent_qpr_id: null,
        actions: [{ action: "", schedule: "", tgl_verif_1: "", verif_1_status: "", verif_1_remarks: "", tgl_verif_2: "", verif_2_status: "", verif_2_remarks: "", tgl_verif_3: "", verif_3_status: "", verif_3_remarks: "", pdca: "", pic: "" }],
        approval_signatures: [],
        correction_items: [{ text: "", checked: false, target: "", pic: "", status: "OPEN" }],
        dampak_items: [{ text: "", checked: false, target: "", pic_seksi: "", status: "OPEN" }],
        pic_seksi: "",
        dokumen: "",
        sketches: [],
        assigned_foreman_id: null,
    },

    async init() {
        await this.fetchUsers();
        if (this.editId) {
            await this.loadData();
        } else {
            this.generateNoQPR();
            if (this.isRequestMode && this.userName) {
                this.form.pic = this.userName;
            }
        }

        if (!this.form.rencana_produksi) {
            let tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.form.rencana_produksi = tomorrow.toISOString().split('T')[0];
        }

        this.initSigners();

        // Sembunyikan skeleton global setelah semua data siap
        if (window.deferSkeletonHide) {
            window.dispatchEvent(new Event('page-ready'));
        }

        // Watch form.pic_seksi to ensure primary seksi is added to signers
        this.$watch('form.pic_seksi', (newVal, oldVal) => {
            if (oldVal) {
                // Hapus oldVal dari signature jika posisinya seksi
                this.form.approval_signatures = this.form.approval_signatures.filter(s => !(s.position === 'seksi' && s.role === oldVal));
            }
            if (newVal) {
                // Tambahkan newVal ke signature jika belum ada
                const existing = this.form.approval_signatures.find(s => s.position === 'seksi' && s.role === newVal);
                if (!existing) {
                    const maxId = this.form.approval_signatures.reduce((max, s) => Math.max(max, s.id || 0), 0);
                    this.form.approval_signatures = [
                        ...this.form.approval_signatures,
                        {
                            id: maxId + 1,
                            role: newVal,
                            sub: "Seksi Utama",
                            nama: "",
                            signature: null,
                            canSignNow: false,
                            required: true,
                            position: "seksi",
                            signed_at: null
                        }
                    ];
                }
            }
        });

        // Watch assigned_foreman_id: auto-fill TTD Foreman dari profil user jika sudah pernah TTD
        this.$watch('form.assigned_foreman_id', (val) => {
            if (!val || !this.allUsersById) return;
            const targetSigner = this.form.approval_signatures.find(s => s.position === 'foreman');
            if (!targetSigner) return;

            const u = this.allUsersById[val];
            if (u) {
                targetSigner.nama = u.name;
                this.form.approval_signatures = [...this.form.approval_signatures];
            }
        });
    },

    showToast(type, msg) {
        if (window.Swal) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success'),
                title: msg,
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
        } else {
            console.log(type, msg);
        }
    },

    async autofillByJobNo() {
        if (!this.form.no_job) return;
        try {
            this.showToast('info', 'Mencari data Job No...');
            const res = await axios.get(`/api/inspeksi/search?q=${encodeURIComponent(this.form.no_job)}`);
            const dataList = res.data;
            if (dataList && dataList.length > 0) {
                const data = dataList[0];
                this.form.nama_part = data.part_name || '';
                this.form.kontrol_part = data.part_no || ''; // If QPR uses kontrol_part for Part No

                // Coba ambil model dari type/model di Lembar Inspeksi
                // Template LI biasanya menyimpan model di kolom type atau dari form.model
                if (data.model) this.form.model = data.model;
                else if (data.type) this.form.model = data.type;

                // Autofill Shift dan Lokasi Kejadian dari data produksi Inspeksi
                if (data.shift) this.form.shift = data.shift;
                if (data.lokasi) this.form.lokasi = data.lokasi;

                // Autofill foto/sketch jika ada
                if (data.image_path) {
                    if (!this.form.sketches) this.form.sketches = [];
                    if (!this.form.sketches.includes(data.image_path)) {
                        this.form.sketches.push(data.image_path);
                    }
                }

                // Otomatis menugaskan QPR ini ke QA Operator yang mengerjakan Lembar Inspeksi part tersebut
                if (data.created_by && this.isRequestMode) {
                    this.form.created_by = data.created_by;
                }

                this.showToast('success', 'Data Part berhasil ditemukan & diisi!');
            } else {
                this.showToast('warning', 'Data Job No tidak ditemukan di riwayat Inspeksi.');
            }
        } catch (e) {
            console.error('Error autofill job no:', e);
            this.showToast('error', 'Gagal menarik data Job No.');
        }
    },

    getStatusBadgeLabel(status) {
        if (status === 'OPEN' && !this.form.assigned_foreman_id) return 'PENGAJUAN QPR';
        const map = {
            'Draft': 'DRAFT',
            'Revision': 'REVISI',
            'OPEN': 'OPEN',
            'Pending Approval': 'MENUNGGU GL',
            'GL Approved': 'MENUNGGU SEKSI',
            'Progress': 'PROGRESS',
            'Waiting Action 1': 'ACTION 1',
            'Waiting Action 2': 'ACTION 2',
            'Waiting Action 3': 'ACTION 3',
            'Waiting Verif 1': 'ANTRIAN QA',
            'Waiting Verif 2': 'ANTRIAN QA',
            'Waiting Verif 3': 'ANTRIAN QA',
            'Waiting Verif A3': 'ANTRIAN QA',
            'Waiting A3 Report': 'MENUNGGU A3',
            'Close': 'SELESAI',
        };
        return map[status] || status;
    },

    getStatusBadgeStyles(status) {
        if (status === 'OPEN' && !this.form.assigned_foreman_id) return { bg: 'bg-[#ea580c] print:bg-[#ea580c]', text: 'text-[#ea580c]' }; // orange-600
        const isError = ['Draft', 'Revision'].includes(status);
        const isWarn = ['OPEN', 'Pending Approval', 'GL Approved', 'Progress', 'Waiting Action 1', 'Waiting Action 2', 'Waiting Action 3'].includes(status);
        const isBlue = ['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3', 'Waiting A3 Report', 'Waiting Verif A3'].includes(status);
        const isSuccess = status === 'Close';

        if (isSuccess) return { bg: 'bg-[#41A966] print:bg-[#41A966]', text: 'text-[#41A966]' };
        if (isBlue) return { bg: 'bg-[#4F8CE3] print:bg-[#4F8CE3]', text: 'text-[#4F8CE3]' };
        if (isWarn) return { bg: 'bg-[#D98E2E] print:bg-[#D98E2E]', text: 'text-[#D98E2E]' };
        if (isError) return { bg: 'bg-[#E11D2A] print:bg-[#E11D2A]', text: 'text-[#E11D2A]' };
        return { bg: 'bg-slate-600 print:bg-slate-600', text: 'text-slate-600' };
    },

    async fetchUsers() {
        try {
            // Fetch semua role yang relevan + semua departemen Seksi dalam satu request
            const seksiDepts = SEKSI_LIST.map(s => `department[]=${encodeURIComponent(s)}`).join('&');
            const res = await axios.get(`${config.apiUrl}/api/users/by-role?role[]=Group Leader&role[]=Foreman&role[]=Supervisor&role[]=Operator&${seksiDepts}`);
            const list = res.data.data || res.data;
            this.glUsers = list.filter(u => u.role === 'Group Leader' || u.role === 'Leader').map(u => ({ id: u.id, nama: u.name, signature: u.signature || null }));
            this.fmUsers = list.filter(u => u.role === 'Foreman').map(u => ({ id: u.id, nama: u.name, signature: u.signature || null }));
            this.spvUsers = list.filter(u => u.role === 'Supervisor').map(u => ({ id: u.id, nama: u.name, signature: u.signature || null }));
            this.operatorUsers = list.filter(u => u.role === 'Operator').map(u => ({ id: u.id, nama: u.name, signature: u.signature || null }));
            // Map ID → user data untuk lookup cepat
            this.allUsersById = {};
            list.forEach(u => { this.allUsersById[u.id] = u; });
            // Map Department → user dengan TTD (ambil yang pertama punya signature, atau yang pertama)
            this.allUsersByDept = {};
            list.forEach(u => {
                if (u.department) {
                    // Prioritaskan user yang punya signature
                    if (!this.allUsersByDept[u.department] || u.signature) {
                        this.allUsersByDept[u.department] = u;
                    }
                }
            });
        } catch (e) { console.error("Fetch users error:", e); }
    },

    async loadData() {
        this.loadingData = true;
        try {
            const res = await axios.get(`${config.apiUrl}/api/qprs/${this.editId}`);
            const d = res.data.data || res.data;
            this.form = { ...this.form, ...d };

            // Handle JSON parsing for arrays if they come as strings
            ['actions', 'correction_items', 'dampak_items', 'sketches', 'approval_signatures'].forEach(key => {
                if (typeof this.form[key] === 'string') {
                    try { this.form[key] = JSON.parse(this.form[key]); } catch (e) { this.form[key] = []; }
                }
                if (!Array.isArray(this.form[key])) {
                    this.form[key] = [];
                }
            });

            if (typeof this.form.area_problems === 'string') {
                try { this.form.area_problems = JSON.parse(this.form.area_problems); } catch (e) { this.form.area_problems = {}; }
            }
            if (!this.form.area_problems || typeof this.form.area_problems !== 'object' || Array.isArray(this.form.area_problems)) {
                this.form.area_problems = {};
            }

            // Convert legacy 'sketch' string into 'sketches' array for UI
            if (this.form.sketch && (!this.form.sketches || this.form.sketches.length === 0)) {
                this.form.sketches = [this.form.sketch];
                // Remove prefix if needed for proper relative URL
                if (!this.form.sketch.startsWith('http') && !this.form.sketch.startsWith('/storage/')) {
                    this.form.sketches[0] = '/storage/' + this.form.sketch;
                }
            } else if (this.form.sketches && this.form.sketches.length > 0) {
                // Ensure all sketches have correct prefix
                this.form.sketches = this.form.sketches.map(s => {
                    if (!s.startsWith('http') && !s.startsWith('/storage/')) {
                        return '/storage/' + s;
                    }
                    return s;
                });
            }

            const formatDate = (val) => val ? (typeof val === 'string' ? val.split('T')[0] : val) : '';
            this.form.tanggal = formatDate(this.form.tanggal);
            this.form.target_selesai = formatDate(this.form.target_selesai);
            this.form.rencana_produksi = formatDate(this.form.rencana_produksi);
            this.form.last_date_problem = formatDate(this.form.last_date_problem);

            this.form.actions.forEach(act => {
                act.schedule = formatDate(act.schedule);
                act.tgl_verif_1 = formatDate(act.tgl_verif_1);
                act.tgl_verif_2 = formatDate(act.tgl_verif_2);
                act.tgl_verif_3 = formatDate(act.tgl_verif_3);
            });

            if (!this.form.no_qpr) {
                await this.generateNoQPR();
            }
        } catch (e) { console.error("Load QPR error:", e); }
        finally { this.loadingData = false; }
    },

    async generateNoQPR() {
        const now = new Date();
        const bulan = String(now.getMonth() + 1).padStart(2, "0");
        const tahun = now.getFullYear();
        try {
            const res = await axios.get(`${config.apiUrl}/api/qprs`);
            const all = res.data.data || res.data;
            const pattern = `/QG/IPPI/${bulan}/${tahun}`;
            const thisMonth = all.filter(q => q.no_qpr?.includes(pattern));
            const maxNo = thisMonth.reduce((max, q) => {
                const num = parseInt(q.no_qpr?.split("/")[0]) || 0;
                return Math.max(max, num);
            }, 0);
            const nextNo = String(maxNo + 1).padStart(2, "0");
            this.form.no_qpr = `${nextNo}/QG/IPPI/${bulan}/${tahun}`;
        } catch (e) { this.form.no_qpr = `01/QG/IPPI/${bulan}/${tahun}`; }
    },

    nextStep() {
        // Validation logic before moving to the next step
        if (this.step === 1) {
            if (this.isRequestMode) {
                if (!this.form.no_job || !this.form.model || !this.form.tanggal || !this.form.nama_part || !this.form.no_qpr) {
                    this.showToast('error', "Mohon lengkapi semua field wajib (*) di Langkah 1");
                    return;
                }
            } else {
                if (!this.form.no_job || !this.form.model || !this.form.tanggal || !this.form.nama_part || !this.form.no_qpr || this.form.rework_qty === "" || this.form.reject_qty === "" || this.form.stock_ippi_qty === "" || !this.form.proses_repair || !this.form.rencana_produksi) {
                    this.showToast('error', "Mohon lengkapi semua field wajib (*) di Langkah 1");
                    return;
                }
            }
        } else if (this.step === 2) {
            if (this.isRequestMode) {
                if (!this.form.defect_keterangan || !this.form.jam) {
                    this.showToast('error', "Mohon lengkapi semua field wajib (*) di Langkah 2");
                    return;
                }
            } else {
                if (!this.form.kategori_problem || !this.form.defect_keterangan || !this.form.shift || !this.form.jam || !this.form.lokasi) {
                    this.showToast('error', "Mohon lengkapi semua field wajib (*) di Langkah 2");
                    return;
                }
            }
        } else if (this.step === 4) {
            if (!this.form.pic_seksi) {
                this.showToast('error', "Mohon pilih PIC Langkah Perbaikan (Seksi Utama) di Langkah 4");
                return;
            }
        }
        if (this.step < this.availableSteps.length) this.step++;
    },
    prevStep() { if (this.step > 1) this.step--; },

    addCorrection() {
        if (!Array.isArray(this.form.correction_items)) this.form.correction_items = [];
        this.form.correction_items.push({ text: "", checked: false, target: "", pic: "", status: "OPEN" });
    },
    removeCorrection(i) { this.form.correction_items.splice(i, 1); },

    addDampak() {
        if (!Array.isArray(this.form.dampak_items)) this.form.dampak_items = [];
        this.form.dampak_items.push({ text: "", checked: false, target: "", pic_seksi: "", status: "OPEN" });
    },
    removeDampak(i) { this.form.dampak_items.splice(i, 1); },

    addAction() {
        if (!Array.isArray(this.form.actions)) this.form.actions = [];
        this.form.actions.push({
            action: "",
            schedule: "",
            tgl_verif_1: "",
            verif_1_status: "",
            verif_1_remarks: "",
            tgl_verif_2: "",
            verif_2_status: "",
            verif_2_remarks: "",
            tgl_verif_3: "",
            verif_3_status: "",
            verif_3_remarks: "",
            pdca: "P",
            pic: "",
            evidence_file: null,
            evidence_remarks: ''
        });
    },
    removeAction(i) { this.form.actions.splice(i, 1); },

    togDefect(t) {
        let list = this.form.defect ? this.form.defect.split(",").map(s => s.trim()).filter(Boolean) : [];
        let index = list.findIndex(x => x.toLowerCase() === t.toLowerCase());
        if (index !== -1) list.splice(index, 1);
        else list.push(t);
        this.form.defect = list.join(", ");
    },

    togAreaDefect(area, defect) {
        if (!this.form.area_problems) this.form.area_problems = {};
        if (!this.form.area_problems[area]) this.form.area_problems[area] = [];

        let index = this.form.area_problems[area].findIndex(x => x.toLowerCase() === defect.toLowerCase());
        if (index !== -1) {
            this.form.area_problems[area].splice(index, 1);
        } else {
            this.form.area_problems[area].push(defect);
        }
        this.syncGlobalDefects();
    },

    syncGlobalDefects() {
        const allDefects = new Set();
        if (this.form.area_problems) {
            Object.values(this.form.area_problems).forEach(defs => {
                defs.forEach(d => allDefects.add(d));
            });
        }
        this.form.defect = Array.from(allDefects).join(", ");
    },

    togProses(op) {
        let list = this.form.proses_repair ? this.form.proses_repair.split(",").map(s => s.trim()).filter(Boolean) : [];
        if (list.includes(op)) list = list.filter(x => x !== op);
        else list.push(op);
        this.form.proses_repair = list.join(", ");
    },

    toggleArea(n) {
        let list = this.form.area ? this.form.area.split(",").map(s => s.trim()).filter(Boolean) : [];
        let index = list.indexOf(n.toString());
        if (!this.form.area_problems) this.form.area_problems = {};

        if (index !== -1) {
            list.splice(index, 1);
            delete this.form.area_problems[n.toString()];
        } else {
            list.push(n.toString());
            this.form.area_problems[n.toString()] = [];
        }

        list.sort((a, b) => parseInt(a) - parseInt(b));
        this.form.area = list.join(", ");
        this.syncGlobalDefects();
    },

    isActionLocked(act) {
        // Baris dikunci jika ada Verifikasi yang NG
        return act.verif_1_status === 'NG' || act.verif_2_status === 'NG' || act.verif_3_status === 'NG';
    },

    handleVerifChange(idx, verifNum) {
        const act = this.form.actions[idx];
        const status = act[`verif_${verifNum}_status`];

        if (status === 'NG') {
            if (idx >= 2) {
                alert(`Verifikasi dinyatakan NG (Batas Maksimal)!\nSilakan Simpan Data agar sistem segera menugaskan Produksi SA / Seksi Terkait untuk mengirimkan A3 Report.`);
            } else if (idx === this.form.actions.length - 1) {
                alert(`Verifikasi ${verifNum} dinyatakan NG!\nBaris ini akan dikunci dan sistem otomatis membuat baris Langkah Perbaikan baru.`);
                this.addAction();
            }
        }
    },

    verifyA3(status) {
        if (status === 'NG') {
            if (confirm(`Verifikasi A3 Report dinyatakan NG!\nLink A3 Report akan dihapus agar Seksi Terkait dapat merevisi. Lanjutkan?`)) {
                this.form.a3_document = "";
                this.form.a3_due_date = "";
                setTimeout(() => {
                    this.submit('Simpan');
                }, 200);
            }
        } else if (status === 'OK') {
            if (confirm(`A3 Report dinyatakan OK. QPR ini akan ditutup dan part dapat diproduksi kembali. Lanjutkan?`)) {
                this.form.a3_status = 'OK'; // Parameter khusus untuk backend state machine
                setTimeout(() => {
                    this.submit('Simpan');
                }, 200);
            }
        }
    },

    // --- SIGNATURE LOGIC ---
    showPadFor: null,
    drawing: false,
    ctx: null,
    new_seksi_signer: "",

    initSigners() {
        const defaultSigners = [
            { id: 1, role: "Dibuat", sub: "Operator", nama: "", signature: null, canSignNow: true, required: true, position: "operator", signed_at: null },
            { id: 2, role: "Diperiksa", sub: "GL / Foreman", nama: "", signature: null, canSignNow: false, required: true, position: "foreman", signed_at: null }
        ];

        // Ensure default signers exist if empty
        if (!this.form.approval_signatures || this.form.approval_signatures.length === 0) {
            this.form.approval_signatures = defaultSigners;
        } else {
            // Removed mistakenly added filter for Kasie QA to allow final QA approval

            // Deduplicate signatures to prevent Alpine x-for duplicate key errors
            const uniqueSigs = [];
            const seenIds = new Set();
            const seenSeksiRoles = new Set();

            this.form.approval_signatures.forEach(s => {
                // Determine if it's a seksi role
                const isSeksi = s.position === 'seksi' || this.seksiOptions.includes(s.role);

                // If it's a duplicate ID, generate a new unique ID
                if (s.id === undefined || s.id === null || seenIds.has(s.id)) {
                    s.id = Date.now() + Math.floor(Math.random() * 10000);
                }

                // If it's a seksi and we already have this role, skip it (deduplicate)
                if (isSeksi) {
                    if (seenSeksiRoles.has(s.role)) {
                        return; // Skip duplicate seksi
                    }
                    seenSeksiRoles.add(s.role);
                }

                seenIds.add(s.id);
                uniqueSigs.push(s);
            });
            this.form.approval_signatures = uniqueSigs;

            // Assign missing IDs and positions for legacy records
            this.form.approval_signatures.forEach((s, i) => {
                if (!s.position) {
                    if (s.role === 'Dibuat' || s.sub === 'Operator') {
                        s.position = 'operator';
                    } else if (s.role === 'Diperiksa' || s.sub === 'GL / Foreman') {
                        s.position = 'foreman';
                    } else if (this.seksiOptions.includes(s.role)) {
                        s.position = 'seksi';
                    } else if (s.role === 'Kasie QA') {
                        s.position = 'kasie_qa';
                    } else {
                        s.position = 'seksi'; // Fallback
                    }
                }
            });
        }

        // Pastikan Primary Seksi (pic_seksi) ada di list approval_signatures saat diload
        if (this.form.pic_seksi) {
            const hasPrimarySeksi = this.form.approval_signatures.find(s => s.position === 'seksi' && s.role === this.form.pic_seksi);
            if (!hasPrimarySeksi) {
                const maxId = this.form.approval_signatures.reduce((max, s) => Math.max(max, s.id || 0), 0);
                this.form.approval_signatures.push({
                    id: maxId + 1,
                    role: this.form.pic_seksi,
                    sub: "Seksi Utama",
                    nama: "",
                    signature: null,
                    canSignNow: false,
                    required: true,
                    position: "seksi",
                    signed_at: null
                });
            }
        }



        // Auto-fill operator name if we can
        const operator = this.form.approval_signatures.find(s => s.position === "operator");
        if (operator && !operator.nama && window.Laravel?.user?.name) {
            operator.nama = window.Laravel.user.name;
        }

        // Auto-fill Foreman signature dari TTD yang sudah tersimpan di profil user
        this._autoFillAllSignatures();
    },

    // Auto-fill TTD hanya untuk user yang sedang login (bukan mengisi TTD orang lain)
    _autoFillAllSignatures() {
        let changed = false;
        const currentUserId = config.userId || window.Laravel?.user?.id;
        const currentUserSig = config.userSignature || window.Laravel?.user?.signature;
        const currentUserName = config.userName || window.Laravel?.user?.name || '';

        this.form.approval_signatures.forEach(sig => {
            if (sig.signature) return; // Sudah ada TTD, skip

            if (sig.position === 'operator') {
                // Operator: auto-fill TTD user yang sedang login
                if (currentUserSig) {
                    sig.signature = currentUserSig;
                    sig.nama = sig.nama || currentUserName;
                    sig.signed_at = sig.signed_at || new Date().toISOString();
                    changed = true;
                }
            } else if (sig.position === 'foreman') {
                // Foreman: hanya auto-fill jika user yang login ADALAH foreman yang ditugaskan
                const foremanId = this.form.assigned_foreman_id;
                if (foremanId && currentUserId && String(foremanId) === String(currentUserId) && currentUserSig) {
                    sig.signature = currentUserSig;
                    sig.nama = sig.nama || currentUserName;
                    sig.signed_at = sig.signed_at || new Date().toISOString();
                    changed = true;
                }
            } else if (sig.position === 'seksi') {
                // Seksi: hanya auto-fill jika user yang login adalah dari departemen seksi ini
                const dept = sig.role;
                if (dept && this.allUsersByDept && this.allUsersByDept[dept]) {
                    const u = this.allUsersByDept[dept];
                    if (String(u.id) === String(currentUserId) && currentUserSig) {
                        sig.signature = currentUserSig;
                        sig.nama = sig.nama || currentUserName;
                        sig.signed_at = sig.signed_at || new Date().toISOString();
                        changed = true;
                    }
                }
            }
        });

        if (changed) {
            this.form.approval_signatures = [...this.form.approval_signatures];
        }
    },

    get operatorSigner() { return this.form.approval_signatures.find(s => s.position === "operator"); },
    get foremanSigner() { return this.form.approval_signatures.find(s => s.position === "foreman"); },
    get seksiSigners() { return this.form.approval_signatures.filter(s => s.position === "seksi"); },


    async handleSketchUpload(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.size > 5 * 1024 * 1024) {
                alert(`File ${file.name} terlalu besar (Max 5MB)`);
                continue;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await axios.post(`${config.apiUrl}/api/qprs/upload-sketch`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (!this.form.sketches) this.form.sketches = [];
                this.form.sketches.push(res.data.url);
            } catch (err) {
                console.error("Upload failed", err);
                alert(`Gagal upload foto ${file.name}`);
            }
        }

        // Reset input so we can upload the same file again if needed
        e.target.value = '';
    },

    async handleEvidenceUpload(e, idx) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert(`File ${file.name} terlalu besar (Max 5MB)`);
            return;
        }

        const act = this.form.actions[idx];
        act.uploading = true;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await axios.post(`${config.apiUrl}/api/qprs/upload-sketch`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            // Format URL properly if needed
            let url = res.data.url;
            if (!url.startsWith('http') && !url.startsWith('/storage/')) {
                url = '/storage/' + url;
            }
            act.evidence_file = url;
        } catch (err) {
            console.error("Upload evidence failed", err);
            alert(`Gagal upload bukti ${file.name}`);
        } finally {
            act.uploading = false;
        }

        e.target.value = '';
    },

    requestQaVerif(act) {
        if (!act.evidence_file && !act.evidence_remarks) {
            alert("⚠️ Bukti Perbaikan Diperlukan!\n\nHarap 'Upload Bukti' (Foto) atau isi 'Keterangan hasil perbaikan' sebelum meminta Verifikasi QA.");
            return;
        }
        act.pdca = 'C';
    },

    addSeksiSigner() {
        if (!this.new_seksi_signer) {
            alert("Pilih seksi terlebih dahulu!");
            return;
        }
        if (this.new_seksi_signer === this.form.pic_seksi) {
            alert("Seksi Utama (" + this.new_seksi_signer + ") sudah otomatis ada di tabel TTD pertama.");
            return;
        }
        // Check if already exists
        if (this.form.approval_signatures.find(s => s.role === this.new_seksi_signer)) {
            alert("Seksi ini sudah ada di daftar TTD.");
            return;
        }

        this.form.approval_signatures.push({
            id: Date.now(),
            role: this.new_seksi_signer,
            sub: "Seksi Terkait",
            nama: "",
            signature: null,
            canSignNow: false,
            required: false,
            position: "seksi",
            signed_at: null
        });
        this.new_seksi_signer = ""; // Reset dropdown after add
    },

    removeSigner(id) {
        this.form.approval_signatures = this.form.approval_signatures.filter(s => s.id !== id);
    },

    updateSignerName(id, name) {
        const signer = this.form.approval_signatures.find(s => s.id === id);
        if (signer) {
            signer.nama = name;
            signer.signature = null; // reset signature if name changes

            // If it's foreman, update assigned_foreman_id
            if (signer.position === 'foreman') {
                const searchName = name.toLowerCase();
                const glUser = this.glUsers.find(u =>
                    (u.nama || "").toLowerCase().includes(searchName) ||
                    (searchName === "liyantoro" && u.nama?.toLowerCase() === "m. liyantoro")
                );
                this.form.assigned_foreman_id = glUser ? glUser.id : null;
            }
        }
    },

    updateForemanName() {
        const id = this.form.assigned_foreman_id;
        const user = this.availableForemen.find(u => u.id == id);
        const signer = this.form.approval_signatures.find(s => s.position === 'foreman');
        if (signer && user) {
            // Hanya isi Nama, JANGAN isi TTD - TTD hanya boleh diisi oleh foreman itu sendiri saat login
            signer.nama = user.nama;
            signer.signature = null;
            signer.signed_at = null;
            this.form.approval_signatures = [...this.form.approval_signatures];
        } else if (signer && !user) {
            signer.nama = '';
            signer.signature = null;
            signer.signed_at = null;
        }
    },

    openSignaturePad(id) {
        this.showPadFor = id;
        setTimeout(() => {
            const canvas = document.getElementById('signature-canvas');
            if (canvas) {
                // Resize canvas to fix blurriness on mobile/tablets
                const rect = canvas.parentElement.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;

                canvas.width = rect.width * dpr;
                canvas.height = 160 * dpr;
                canvas.style.width = `${rect.width}px`;
                canvas.style.height = `160px`;

                this.ctx = canvas.getContext('2d');
                this.ctx.scale(dpr, dpr);
                this.ctx.fillStyle = "#ffffff";
                this.ctx.fillRect(0, 0, rect.width, 160);
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = "round";
                this.ctx.strokeStyle = "#0F172A";
            }
        }, 100);
    },

    closeSignaturePad() {
        this.showPadFor = null;
        this.drawing = false;
        this.ctx = null;
    },

    getPos(e, canvas) {
        const rect = canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        // Since we scale the context by dpr, we just need logical coordinates
        return {
            x: src.clientX - rect.left,
            y: src.clientY - rect.top
        };
    },

    startDrawing(e) {
        e.preventDefault();
        this.drawing = true;
        const canvas = e.target;
        const pos = this.getPos(e, canvas);
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);
    },

    draw(e) {
        e.preventDefault();
        if (!this.drawing) return;
        const canvas = e.target;
        const pos = this.getPos(e, canvas);
        this.ctx.lineTo(pos.x, pos.y);
        this.ctx.stroke();
    },

    stopDrawing() {
        this.drawing = false;
    },

    clearSignature() {
        const canvas = document.getElementById('signature-canvas');
        if (canvas && this.ctx) {
            this.ctx.fillStyle = "#ffffff";
            const rect = canvas.parentElement.getBoundingClientRect();
            this.ctx.fillRect(0, 0, rect.width, 160);
        }
    },

    async saveSignature() {
        const canvas = document.getElementById('signature-canvas');
        if (canvas) {
            const dataUrl = canvas.toDataURL("image/png");
            const index = this.form.approval_signatures.findIndex(s => s.id == this.showPadFor);
            if (index !== -1) {
                this.form.approval_signatures[index].signature = dataUrl;
                this.form.approval_signatures[index].signed_at = new Date().toISOString();
                // Otomatis catat nama dari user yang sedang login
                if (this.userName) {
                    this.form.approval_signatures[index].nama = this.userName;
                }

                // Auto-save to backend for Foreman and Seksi
                const position = this.form.approval_signatures[index].position;
                if (this.editId && (position === 'foreman' || position === 'seksi')) {
                    try {
                        // Tampilkan loading state sederhana di tombol modal jika diperlukan (opsional, UX instant lebih baik)
                        const res = await axios.post(`${config.apiUrl}/api/qprs/${this.editId}/sign`, {
                            signature: dataUrl,
                            position: position
                        });

                        if (res.data && res.data.status) {
                            this.form.status = res.data.status;
                        }
                    } catch (e) {
                        console.error('Gagal menyimpan TTD ke server:', e);
                        // Jangan alert error memblokir, biarkan user klik tombol Simpan & Kirim sebagai fallback
                    }
                }

                // Force Alpine reactivity
                this.form.approval_signatures = [...this.form.approval_signatures];
            }
            this.closeSignaturePad();
        }
    },

    clearSignerSignature(id) {
        const index = this.form.approval_signatures.findIndex(s => s.id == id);
        if (index !== -1) {
            this.form.approval_signatures[index].signature = null;
            this.form.approval_signatures[index].signed_at = null;
            // Force Alpine reactivity
            this.form.approval_signatures = [...this.form.approval_signatures];
        }
    },

    async submit(actionType) {
        this.loading = true;

        // ── Pisahkan intent dari actionType vs status dokumen aktual ──
        // currentStatus = status dokumen sekarang (dari DB / default form)
        // actionType    = intent tombol yang ditekan (bisa null/undefined)
        const currentStatus = this.form.status;

        // ── Basic validation ────────────────────────────────────────────
        if (!this.form.nama_part) {
            alert("Nama Part wajib diisi (Langkah 1)");
            this.step = 1;
            this.loading = false;
            return;
        }

        // Jika bukan Draft, wajib ada TTD Operator (Kecuali Mode Pengajuan)
        if (actionType !== 'Draft' && !this.isRequestMode) {
            const operatorSig = this.operatorSigner;
            if (!operatorSig || !operatorSig.signature) {
                this.showToast('error', "Mohon lengkapi Tanda Tangan Operator (Dibuat Oleh) sebelum menyimpan.");
                this.loading = false;
                return;
            }
        }

        // ── Tentukan status baru yang akan dikirim ke backend ───────────
        let newStatus = currentStatus; // default: jangan ubah status

        if (this.isRequestMode) {
            newStatus = 'OPEN'; // Otomatis OPEN untuk Mode Pengajuan
        } else if (actionType === 'Draft') {
            // Simpan sebagai Draft tanpa validasi ketat
            newStatus = 'Draft';

        } else if (actionType === 'OPEN') {
            // Operator submit → tunggu GL/Foreman
            if (!this.form.assigned_foreman_id) {
                alert("Mohon pilih GL / Foreman yang akan memverifikasi dokumen ini terlebih dahulu.");
                this.loading = false;
                return;
            }
            newStatus = 'OPEN';

        } else {
            // Determine next status based on signatures and current state
            const foremanSig = this.foremanSigner;
            const seksiSigners = this.form.approval_signatures.filter(s => this.seksiOptions.includes(s.role));
            const allSeksiSigned = seksiSigners.length > 0 && seksiSigners.every(s => s.signature);

            // States that are governed purely by backend state machine
            const backendManagedStates = [
                'GL Approved', 'Progress',
                'Waiting Action 1', 'Waiting Verif 1',
                'Waiting Action 2', 'Waiting Verif 2',
                'Waiting Action 3', 'Waiting Verif 3',
                'Waiting A3 Report'
            ];

            // PIC Langkah Perbaikan validation
            if (this.form.actions && this.form.actions.length > 0) {
                for (let i = 0; i < this.form.actions.length; i++) {
                    const act = this.form.actions[i];
                    if (act.action && !act.pic) {
                        alert("Langkah Perbaikan (Correction) ke-" + (i + 1) + " harus mencantumkan PIC.");
                        this.loading = false;
                        return;
                    }
                }
            }

            if (backendManagedStates.includes(currentStatus)) {
                // Biarkan backend state machine yang menentukan transisi
                // Frontend hanya memblokir jika Seksi belum TTD tapi sudah ada PDCA C/A
                const lastAction = this.form.actions?.length > 0
                    ? this.form.actions[this.form.actions.length - 1]
                    : null;
                if (lastAction && (lastAction.pdca === 'C' || lastAction.pdca === 'A') && !allSeksiSigned) {
                    alert("Mohon lengkapi Tanda Tangan Seksi Terkait sebelum meminta Verifikasi QA.");
                    this.loading = false;
                    return;
                }
                newStatus = currentStatus; // Backend state machine akan menentukan

            } else if (currentStatus === 'Close') {
                newStatus = 'Close'; // Tidak bisa diubah dari frontend

            } else {
                // Early phases: Draft, OPEN, Pending Approval, Revision
                // Tentukan status berdasarkan TTD yang sudah ada
                if (foremanSig?.signature) {
                    newStatus = 'GL Approved';
                } else {
                    newStatus = 'Pending Approval';
                }
            }
        }

        // ── Set status ke form sebelum kirim ───────────────────────────
        this.form.status = newStatus;

        try {
            if (newStatus === 'Draft') {
                const url = this.editId
                    ? `${config.apiUrl}/api/qprs/${this.editId}/draft`
                    : `${config.apiUrl}/api/qprs/draft`;
                await axios.patch(url, this.form);
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('next')) {
                    window.location.href = urlParams.get('next');
                    return;
                }
                window.location.href = `${config.apiUrl}/qpr`;
                return;
            }

            const url = this.editId ? `${config.apiUrl}/api/qprs/${this.editId}` : `${config.apiUrl}/api/qprs`;
            const method = this.editId ? 'put' : 'post';
            await axios[method](url, this.form);
            const urlParams2 = new URLSearchParams(window.location.search);
            if (urlParams2.has('next')) {
                window.location.href = urlParams2.get('next');
                return;
            }
            window.location.href = `${config.apiUrl}/qpr`;
        } catch (e) {
            alert("Gagal simpan QPR: " + (e.response?.data?.message || e.message));
        } finally {
            this.loading = false;
        }
    }
});
