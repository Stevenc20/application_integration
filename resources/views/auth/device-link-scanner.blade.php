<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner QR - Tautkan Perangkat</title>
    @vite('resources/css/app.css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Load html5-qrcode from CDN -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #F8F7F5; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; box-sizing: border-box;}
        .card { background: white; border-radius: 16px; padding: 1.5rem; width: 100%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 10px 15px rgba(0,0,0,0.1); text-align: center; }
        .icon-wrapper { width: 56px; height: 56px; background: #FDECEA; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: #C0392B; }
        h2 { font-size: 1.25rem; font-weight: 700; color: #111110; margin-bottom: 0.5rem; }
        p { font-size: 0.85rem; color: #5C5A58; margin-bottom: 1.5rem; line-height: 1.5; }
        #reader { width: 100%; max-width: 100%; margin: 0 auto; border-radius: 12px; overflow: hidden; border: 1px solid #E9E7E4; }
        .btn-cancel { display: block; width: 100%; padding: 0.75rem; background: white; color: #5C5A58; border: 1px solid #E9E7E4; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 1.5rem; text-decoration: none; }
        .btn-cancel:hover { background: #f8f9fa; }
        /* Override html5-qrcode default styling */
        #reader button { background: #C0392B; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; margin: 5px; }
        #reader select { padding: 8px; border-radius: 6px; border: 1px solid #E9E7E4; margin: 5px; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                <rect x="7" y="7" width="10" height="10" rx="1"></rect>
            </svg>
        </div>
        <h2>Scan QR Code</h2>
        <p>Arahkan kamera ke kode QR yang muncul di layar monitor untuk menautkan perangkat.</p>
        
        <div id="reader"></div>

        <a href="javascript:history.back()" class="btn-cancel">Kembali</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 },
                /* verbose= */ false);
            
            function onScanSuccess(decodedText, decodedResult) {
                // Hentikan scanner segera setelah berhasil scan pertama
                html5QrcodeScanner.clear();
                
                // Pastikan yang di-scan adalah URL dari aplikasi ini
                if(decodedText.includes('/auth/device-link/scan?token=')) {
                    // Tampilkan indikator loading
                    document.getElementById('reader').innerHTML = `
                        <div style="padding: 40px 0; color: #27ae60;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 15px;">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <p style="font-weight: 600; margin: 0;">Berhasil scan! Mengalihkan...</p>
                        </div>
                    `;
                    // Arahkan ke URL konfirmasi
                    window.location.href = decodedText;
                } else {
                    alert("Kode QR tidak valid untuk sistem ini.");
                    // Restart scanner jika salah
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            }
            
            function onScanFailure(error) {
                // handle scan failure, usually better to ignore and keep scanning.
            }
            
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });
    </script>
</body>
</html>
