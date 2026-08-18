<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Login - Production System</title>
    @vite('resources/css/app.css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F8F7F5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 16px; padding: 2rem; width: 90%; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 10px 15px rgba(0,0,0,0.1); text-align: center; }
        .icon-wrapper { width: 64px; height: 64px; background: #FDECEA; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #C0392B; }
        h2 { font-size: 1.25rem; font-weight: 700; color: #111110; margin-bottom: 0.5rem; }
        p { font-size: 0.9rem; color: #5C5A58; margin-bottom: 1.5rem; line-height: 1.5; }
        .device-info { background: #f8f9fa; border: 1px solid #E9E7E4; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; text-align: left; }
        .device-info .row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .device-info .row:last-child { margin-bottom: 0; }
        .device-info .label { color: #A09E9C; font-size: 0.8rem; font-weight: 500; }
        .device-info .val { color: #111110; font-size: 0.85rem; font-weight: 600; }
        .btn-approve { display: block; width: 100%; padding: 0.75rem; background: #C0392B; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-bottom: 0.75rem; transition: background 0.2s; }
        .btn-approve:hover { background: #E74C3C; }
        .btn-cancel { display: block; width: 100%; padding: 0.75rem; background: white; color: #5C5A58; border: 1px solid #E9E7E4; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-cancel:hover { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrapper">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="9" y1="3" x2="9" y2="21"></line>
            </svg>
        </div>
        <h2>Persetujuan Login</h2>
        <p>Sebuah perangkat mencoba masuk ke akun Anda. Pastikan ini adalah layar monitor Anda.</p>
        
        <div class="device-info">
            <div class="row">
                <span class="label">Sistem Operasi</span>
                <span class="val">{{ $device['os'] }}</span>
            </div>
            <div class="row">
                <span class="label">Browser</span>
                <span class="val">{{ $device['browser'] }}</span>
            </div>
        </div>

        <button type="button" class="btn-approve" onclick="actionRequest('approve')">Setujui Masuk</button>
        <button type="button" class="btn-cancel" onclick="actionRequest('cancel')">Tolak</button>
        <a href="{{ route('device_link.scanner') }}" class="btn-cancel" style="margin-top: 0.75rem; text-decoration: none;">Kembali ke Scanner</a>
        
        <p id="actionStatus" style="margin-top: 1rem; margin-bottom: 0; font-weight: 600; display: none;"></p>
    </div>

    <script>
        async function actionRequest(action) {
            const statusEl = document.getElementById('actionStatus');
            const btns = document.querySelectorAll('button');
            btns.forEach(b => b.disabled = true);
            statusEl.style.display = 'block';
            statusEl.innerText = 'Memproses...';
            statusEl.style.color = '#5C5A58';

            try {
                const res = await fetch(`{{ url('/auth/device-link/' . $tokenHash) }}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await res.json();
                if (data.success) {
                    statusEl.innerText = action === 'approve' ? 'Berhasil disetujui! Layar monitor akan segera masuk.' : 'Permintaan ditolak.';
                    statusEl.style.color = action === 'approve' ? '#27ae60' : '#C0392B';
                } else {
                    statusEl.innerText = data.message || 'Terjadi kesalahan.';
                    statusEl.style.color = '#C0392B';
                    btns.forEach(b => b.disabled = false);
                }
            } catch (err) {
                statusEl.innerText = 'Gagal memproses.';
                statusEl.style.color = '#C0392B';
                btns.forEach(b => b.disabled = false);
            }
        }
    </script>
</body>
</html>
