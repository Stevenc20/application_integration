import axios from 'axios';

window.qcWorklist = (config) => ({
    items: [],
    loading: true,
    refreshing: false,

    async init() {
        await this.fetchItems();
    },

    async fetchItems() {
        this.loading = true;
        try {
            const res = await axios.get(`${config.apiUrl}/api/inspeksi/pending-ttd`);
            const data = res.data.data || res.data;
            this.items = Array.isArray(data) ? data.filter(item => item.status === 'locked' || item.status === 'ready_for_qc' || item.status === 'waiting_qc') : [];
        } catch (e) {
            console.error('Fetch QC Worklist Error:', e);
        } finally {
            this.loading = false;
        }
    },

    async handleRefresh() {
        this.refreshing = true;
        await this.fetchItems();
        this.refreshing = false;
    },

    fmtDate(v) {
        if (!v) return "—";
        const d = new Date(v);
        return isNaN(d) ? v : d.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
    },

    processLI(item) {
        window.location.href = `${config.apiUrl}/li/${item.id}/edit`;
    }
});
