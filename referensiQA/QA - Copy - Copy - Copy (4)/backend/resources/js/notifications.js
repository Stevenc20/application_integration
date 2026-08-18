// Notifications factory — dipanggil dari Alpine x-data
// Berjalan di SEMUA halaman, termasuk Dashboard, QPR, dll.
function notificationsFactory() {
    return {
        qprCount: 0,
        liCount: 0,
        icCount: 0,
        pendingData: { qprs: [], lis: [], ics: [] },
        showPopup: false,
        closing: false,
        userName: '',
        userRole: '',
        userId: null,
        userDepartment: '',
        sidebarOpen: true,
        mobileNavOpen: false,
        isDesktop: true,

        // ── INTERCOM STATE (Global) ──────────────────────────────────────────────
        // Digunakan di SEMUA halaman untuk GL/Foreman
        intercomAlert: null,       // Data panggilan masuk dari API
        showIntercomOverlay: false, // Full-screen overlay takeover

        init() {
            const mq = window.matchMedia('(min-width: 768px)');
            const syncViewport = () => {
                this.isDesktop = mq.matches;
                if (mq.matches) {
                    this.mobileNavOpen = false;
                    document.body.classList.remove('overflow-hidden');
                }
            };
            syncViewport();
            this.sidebarOpen = mq.matches;
            mq.addEventListener('change', syncViewport);

            // Ambil data user dari meta tag (server-rendered, selalu akurat)
            const metaUser = document.querySelector('meta[name="auth-user"]');
            if (metaUser) {
                try {
                    const u = JSON.parse(metaUser.getAttribute('content'));
                    this.userName       = u?.name || 'Anda';
                    this.userRole       = (u?.role || '').toLowerCase();
                    this.userId         = u?.id || null;
                    this.userDepartment = (u?.department || '').toLowerCase();
                } catch (e) {}
            }
            if (!this.userName || this.userName === 'Anda') {
                const u = JSON.parse(localStorage.getItem('user') || '{}');
                this.userName       = u.name || u.nama || 'Anda';
                this.userRole       = (u.role || '').toLowerCase();
                this.userId         = u.id || null;
                this.userDepartment = (u.department || '').toLowerCase();
            }

            // Tidak perlu load dari sessionStorage lagi — pakai flag `popupShown`

            // Delay singkat: biarkan halaman render dulu, baru fetch notifikasi
            setTimeout(() => {
                this.fetchPending();
                setInterval(() => this.fetchPending(), 30000); // Poll notifikasi tiap 30 detik

                // Global Intercom Polling: khusus GL dan Foreman, di SEMUA halaman
                const isGLOrForeman = ['group leader', 'foreman'].some(r => this.userRole.includes(r));
                if (isGLOrForeman && this.userId) {
                    this._pollIntercom();
                    setInterval(() => this._pollIntercom(), 4000); // Poll setiap 4 detik
                }
            }, 2000);
        },

        // ── GLOBAL INTERCOM POLLING ─────────────────────────────────────────────
        async _pollIntercom() {
            if (!this.userId) return;
            try {
                const res = await window.axios.get(
                    `/api/intercom/active-incoming?user_id=${encodeURIComponent(this.userId)}&role=${encodeURIComponent(this.userRole)}`
                );
                const call = res.data?.data;

                if (call && call.status !== 'completed' && call.status !== 'declined') {
                    this.intercomAlert = call;

                    // Jika status = 'arrived', GL sudah check-in fisik → tutup overlay di device GL
                    if (call.status === 'arrived') {
                        if (this.showIntercomOverlay) {
                            this.showIntercomOverlay = false;
                            this._stopIntercomAudio();
                        }
                    } else {
                        // Masih memanggil atau answered (tapi belum tiba) → tampilkan overlay
                        if (!this.showIntercomOverlay) {
                            this.showIntercomOverlay = true;
                            this._playIntercomSiren();
                            // Kunci scroll body
                            document.body.classList.add('overflow-hidden');
                        }
                    }
                } else {
                    // Tidak ada panggilan aktif / sudah completed
                    if (this.showIntercomOverlay) {
                        this.showIntercomOverlay = false;
                        this.intercomAlert = null;
                        this._stopIntercomAudio();
                        document.body.classList.remove('overflow-hidden');
                    }
                }
            } catch (e) {
                console.error('[Intercom] Poll error:', e);
            }
        },

        // GL/Foreman merespons dari device mereka (HANYA accept/decline, belum dismiss overlay)
        // Overlay baru padam setelah GL tiba di tablet (status = 'arrived')
        async respondIntercom(action) {
            if (!this.intercomAlert) return;
            try {
                await window.axios.post('/api/intercom/respond', {
                    lembar_inspeksi_id: this.intercomAlert.lembar_inspeksi_id,
                    action:             action,
                    responder_name:     this.userName,
                    message:            action === 'accept' ? 'Dalam Perjalanan ke Lapangan' : 'Sibuk/Tidak Bisa',
                });

                if (action === 'decline') {
                    this.showIntercomOverlay = false;
                    this.intercomAlert = null;
                    this._stopIntercomAudio();
                    document.body.classList.remove('overflow-hidden');
                }
            } catch (e) {
                console.error('[Intercom] Respond error:', e);
            }
        },

        _audioCtx: null,
        _sirenInterval: null,

        _playIntercomSiren() {
            if (this._audioCtx) return;
            try {
                const AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) return;
                this._audioCtx = new AC();
                this._sirenInterval = setInterval(() => {
                    if (!this._audioCtx) return;
                    const osc  = this._audioCtx.createOscillator();
                    const gain = this._audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(this._audioCtx.destination);
                    osc.frequency.value = 700;
                    osc.frequency.linearRampToValueAtTime(1100, this._audioCtx.currentTime + 0.4);
                    osc.frequency.linearRampToValueAtTime(700,  this._audioCtx.currentTime + 0.8);
                    gain.gain.setValueAtTime(0.35, this._audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, this._audioCtx.currentTime + 0.9);
                    osc.start(this._audioCtx.currentTime);
                    osc.stop(this._audioCtx.currentTime + 0.9);
                }, 1200);
            } catch (e) {}
        },

        _stopIntercomAudio() {
            if (this._sirenInterval) {
                clearInterval(this._sirenInterval);
                this._sirenInterval = null;
            }
            if (this._audioCtx) {
                try { this._audioCtx.close(); } catch (e) {}
                this._audioCtx = null;
            }
        },

        // ── SIDEBAR & NAVIGATION ────────────────────────────────────────────────
        toggleSidebar() {
            if (this.isDesktop) {
                this.sidebarOpen = !this.sidebarOpen;
            } else {
                this.mobileNavOpen = !this.mobileNavOpen;
                document.body.classList.toggle('overflow-hidden', this.mobileNavOpen);
            }
        },

        closeMobileNav() {
            this.mobileNavOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        onNavClick() {
            if (!this.isDesktop) {
                this.closeMobileNav();
            }
        },

        // ── PENDING NOTIFICATIONS ───────────────────────────────────────────────
        async fetchPending() {
            try {
                const [qprRes, liRes, icRes] = await Promise.allSettled([
                    window.axios.get('/api/qprs/pending-approval'),
                    window.axios.get('/api/inspeksi/pending-ttd'),
                    window.axios.get('/api/item-check/pending-ttd'),
                ]);

                let finalQPRs = [];
                let finalLIs  = [];
                let finalICs  = [];

                if (qprRes.status === 'fulfilled') {
                    const items = qprRes.value.data || [];
                    console.log('QPR API raw items:', items.length, items);
                    const u = JSON.parse(localStorage.getItem('user') || '{}');
                    finalQPRs = items.filter((item) => {
                        const targetQpr = item.qpr || item;
                        const sigs = typeof targetQpr.approval_signatures === 'string'
                            ? JSON.parse(targetQpr.approval_signatures)
                            : (targetQpr.approval_signatures || []);

                        // Cek Foreman/GL
                        if (['group leader', 'foreman'].some(r => this.userRole.includes(r))) {
                            if (targetQpr.assigned_foreman_id == this.userId) {
                                const foremanSig = sigs.find(s => s.position === 'foreman');
                                if (foremanSig && !foremanSig.signature) return true;
                            }
                        }

                        // Cek Seksi Terkait
                        const u = JSON.parse(localStorage.getItem('user') || '{}');
                        const dept = u.department || '';
                        
                        // 1. Cek TTD (GL Approved / Progress)
                        if (['GL Approved', 'Progress'].includes(targetQpr.status) && dept) {
                            const seksiSig = sigs.find(s => s.role === dept);
                            if (seksiSig && !seksiSig.signature) return true;
                        }
                        
                        // 2. Cek Action Lanjutan (Waiting Action X / A3 Report)
                        if (targetQpr.status && (targetQpr.status.startsWith('Waiting Action') || targetQpr.status === 'Waiting A3 Report') && dept) {
                            if (targetQpr.pic_seksi === dept) return true;
                        }

                        // Cek QA Verificator
                        if (['Waiting Verif 1', 'Waiting Verif 2', 'Waiting Verif 3'].includes(targetQpr.status)) {
                            if (['group leader', 'foreman', 'kasie qa', 'kasie'].some(r => this.userRole.includes(r))) {
                                if (['qa', 'quality assurance', 'quality control'].includes(this.userDepartment)) {
                                    return true;
                                }
                            }
                        }

                        // Fallback check nama
                        const mySig = sigs.find((s) => s.nama === this.userName);
                        return mySig && !mySig.signature;
                    });
                }

                if (liRes.status === 'fulfilled') {
                    finalLIs = liRes.value.data || [];
                }

                if (icRes && icRes.status === 'fulfilled') {
                    finalICs = icRes.value.data?.data || [];
                }

                const newQprCount = finalQPRs.length;
                const newLiCount  = finalLIs.length;
                const newIcCount  = finalICs.length;
                const hasItems    = newQprCount > 0 || newLiCount > 0 || newIcCount > 0;

                const popupKey  = this.userId ? 'notif_popup_shown_' + this.userId : null;
                const alreadyShownThisSession = popupKey ? sessionStorage.getItem(popupKey) === '1' : false;
                const countIncreased = (newQprCount > this.qprCount || newLiCount > this.liCount || newIcCount > this.icCount);
                const isDashboardPage = ['/', '/dashboard'].some(p => window.location.pathname === p)
                    || window.location.pathname.startsWith('/dashboard');

                if (hasItems && isDashboardPage && (!alreadyShownThisSession || countIncreased)) {
                    console.log('%c[NOTIF] ✅ WILL SHOW POPUP — dispatching event', 'color:green;font-weight:bold');
                    this.pendingData = { qprs: finalQPRs, lis: finalLIs, ics: finalICs };
                    this.showPopup = true;
                    if (popupKey) sessionStorage.setItem(popupKey, '1');

                    window.dispatchEvent(new CustomEvent('show-priority-popup', {
                        detail: { qprs: finalQPRs, lis: finalLIs, ics: finalICs, qprCount: newQprCount, liCount: newLiCount, icCount: newIcCount }
                    }));

                    // Beep ringan untuk notifikasi tugas baru
                    try {
                        const AC = window.AudioContext || window.webkitAudioContext;
                        if (AC) {
                            const ctx  = new AC();
                            const osc  = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.frequency.value = 880;
                            gain.gain.setValueAtTime(0.3, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                            osc.start(ctx.currentTime);
                            osc.stop(ctx.currentTime + 0.5);
                        }
                    } catch (e) {}
                } 

                this.qprCount = newQprCount;
                this.liCount  = newLiCount;
                this.icCount  = newIcCount;
            } catch (e) {
                console.error('Notification poll error:', e);
            }
        },

        closePopup() {
            this.closing = true;
            setTimeout(() => { this.showPopup = false; this.closing = false; }, 300);
        },

        fmtDate(v) {
            if (!v) return '—';
            const d = new Date(v);
            return isNaN(d) ? v : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },
    };
}

export default notificationsFactory;
