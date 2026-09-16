import axios from 'axios';

window.userManagement = (config) => ({
    users: [],
    loading: true,
    search: '',
    showModal: false,
    selectedUser: null,
    submitting: false,
    toggling: null,
    toast: null,
    
    form: {
        name: '',
        email: '',
        employee_id: '',
        role: 'Operator',
        department: '',
        assigned_line: '',
        password: '',
        is_active: true
    },

    roles: ["Admin", "Group Leader", "Foreman", "Supervisor", "Leader", "Operator"],

    roleConfig: {
        'Admin':        { bg: '#FEF2F2', border: '#FCA5A5', color: '#991B1B' },
        'Group Leader': { bg: '#EFF6FF', border: '#93C5FD', color: '#1E40AF' },
        'Foreman':      { bg: '#F0FDF4', border: '#86EFAC', color: '#166534' },
        'Supervisor':   { bg: '#F5F3FF', border: '#C4B5FD', color: '#5B21B6' },
        'Leader':       { bg: '#ECFEFF', border: '#67E8F9', color: '#0891B2' },
        'Operator':     { bg: '#FFFBEB', border: '#FCD34D', color: '#92400E' },
    },

    get filteredUsers() {
        const q = this.search.toLowerCase();
        return this.users.filter(u => {
            return !q || [u.name, u.email, u.employee_id, u.role, u.department].some(v => (v || '').toLowerCase().includes(q));
        });
    },

    get stats() {
        return [
            { label: 'Total User', value: this.users.length, color: '#0f172a', bg: '#fff', border: '#e2e8f0' },
            { label: 'Aktif', value: this.users.filter(u => u.is_active).length, color: '#10b981', bg: '#f0fdf4', border: '#86efac' },
            { label: 'Nonaktif', value: this.users.filter(u => !u.is_active).length, color: '#64748b', bg: '#f8fafc', border: '#cbd5e1' },
            { label: 'Admin', value: this.users.filter(u => u.role === 'Admin').length, color: '#991b1b', bg: '#fef2f2', border: '#fca5a5' },
        ];
    },

    async init() {
        await this.fetchUsers();
    },

    showToastMsg(type, msg) {
        this.toast = { type, msg };
        setTimeout(() => this.toast = null, 3000);
    },

    async fetchUsers() {
        this.loading = true;
        try {
            const res = await axios.get(`${config.apiUrl}/api/users`);
            this.users = Array.isArray(res.data) ? res.data : (res.data.data || []);
        } catch (e) {
            this.showToastMsg('error', 'Gagal ambil data: ' + e.message);
        } finally {
            this.loading = false;
        }
    },

    openModal(user = null) {
        this.selectedUser = user;
        if (user) {
            this.form = { ...user, password: '' };
        } else {
            this.form = {
                name: '', email: '', employee_id: '',
                role: 'Operator', department: '', assigned_line: '', password: 'password', is_active: true
            };
        }
        this.showModal = true;
    },

    async handleSubmit() {
        this.submitting = true;
        try {
            const payload = { ...this.form };
            if (this.selectedUser && !payload.password) delete payload.password; // Don't send empty password

            const url = this.selectedUser ? `${config.apiUrl}/api/users/${this.selectedUser.id}` : `${config.apiUrl}/api/users`;
            const method = this.selectedUser ? 'put' : 'post';

            await axios({
                method,
                url,
                data: payload
            });

            this.showToastMsg("success", this.selectedUser ? "User berhasil diupdate ✓" : "User baru berhasil ditambah ✓");
            this.showModal = false;
            await this.fetchUsers();
        } catch (e) {
            this.showToastMsg("error", e.response?.data?.message || e.message);
        } finally {
            this.submitting = false;
        }
    },

    async toggleActive(user) {
        this.toggling = user.id;
        try {
            await axios.put(`${config.apiUrl}/api/users/${user.id}/toggle-active`);
            this.showToastMsg("success", `User ${user.is_active ? "dinonaktifkan" : "diaktifkan"}`);
            await this.fetchUsers();
        } catch (e) {
            this.showToastMsg("error", e.message);
        } finally {
            this.toggling = null;
        }
    }
});
