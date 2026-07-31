# Revisi Laporan Magang

## Tabel Revisi

| Mata Kuliah | Kompetensi MK | Hasil Magang | Keterkaitan |
|---|---|---|---|
| **Data Mining** | Analisis data absensi dan penyajian hasil evaluasi | Mengembangkan dashboard produksi, laporan harian, monitoring target produksi, serta analisis data produksi menggunakan metode **trend analysis**, **anomaly detection (Z-Score)**, dan **Pareto 80/20** | Pengolahan data produksi menjadi informasi yang mendukung evaluasi proses produksi serta deteksi dini terhadap anomali performa |
| **Cyber Security** | Penerapan autentikasi dan keamanan data | **1. Autentikasi & RBAC**: Login session-based, 18 role dengan 34 fitur, middleware `role` + `feature` <br> **2. Security Headers**: X-Frame-Options, X-Content-Type-Options, Referrer-Policy <br> **3. Rate Limiting**: Proteksi brute-force (5×/menit login) <br> **4. Network Access Logger**: Seluruh request HTTP tercatat (IP, method, endpoint, status, durasi) <br> **5. Security Dashboard**: Deteksi otomatis brute-force attempt, probing (404 flood), dan high-rate traffic per IP <br> **6. Port Scanner**: Scan port umum (22, 80, 443, 3306, 6379, 8080, 8001) pada container Docker dan gateway | Sistem menerapkan lapisan keamanan berlapis (*defense in depth*): autentikasi, otorisasi berbasis peran, logging akses, pembatasan percobaan login, deteksi anomali traffic, dan pemantauan port terbuka |
| **Jaringan Komputer Lanjut** | Mendukung komunikasi data pada sistem | Deploy aplikasi pada server Ubuntu menggunakan **Docker**, **Nginx Proxy Manager**, **MariaDB**, **Cloudflare Tunnel**, dan **phpMyAdmin** | Sistem berjalan melalui jaringan lokal dan internet sehingga pengguna dapat mengakses aplikasi secara terintegrasi |

---

## Screenshot yang Disarankan

### Cyber Security (minimal 3-4 gambar)

| # | Screenshot | Keterangan |
|---|------------|-----------|
| 1 | **Security Dashboard** | Halaman `/security/dashboard` — 4 stat card (Total Requests, Error Rate, Unique IPs, Open Ports) + tabel Suspicious IPs + tabel Open Ports |
| 2 | **Suspicious IPs Table** | Close-up tabel IP mencurigakan (Brute Force, High Rate, Probing) — bukti implementasi *intrusion detection* |
| 3 | **Network Access Logs** | Halaman `/security/logs` dengan filter — bukti implementasi *network access logging* |
| 4 | **Rate Limiting** | Screenshot error 429 Too Many Requests — bukti implementasi *brute-force protection* |

### Data Mining (2-3 gambar)

| # | Screenshot | Keterangan |
|---|------------|-----------|
| 1 | **Trend Chart** | Grafik line efficiency/downtime — bukti implementasi *trend analysis* |
| 2 | **Anomaly Detection** | Tabel anomali dengan Z-Score — bukti implementasi *anomaly detection* |
| 3 | **Pareto Chart** | Diagram Pareto downtime — bukti implementasi *Pareto 80/20 analysis* |

### Jaringan Komputer Lanjut (2-3 gambar)

| # | Screenshot | Keterangan |
|---|------------|-----------|
| 1 | **Network Monitor Dashboard** | Tiga tab: Containers, Access Logs, Latency — bukti monitoring infrastruktur |
| 2 | **Docker Container Monitoring** | Tab Containers — bukti monitoring container via Docker API socket |
| 3 | **Latency Monitoring** | Grafik latency antar container — bukti network performance monitoring |

---

## Keterangan Tambahan per Fitur Cybersecurity

### 1. Security Headers
Middleware `SecurityHeadersMiddleware` dipasang global (`bootstrap/app.php`), otomatis menambahkan header:
- `X-Frame-Options: DENY` — mencegah clickjacking
- `X-Content-Type-Options: nosniff` — mencegah MIME-type sniffing
- `Referrer-Policy: strict-origin-when-cross-origin` — kontrol informasi referer

### 2. Rate Limiting (Brute-Force Protection)
Menggunakan Laravel Throttle middleware di `routes/web.php`:
- `throttle:5,1` — maksimal 5 percobaan login per menit per IP
- `throttle:10,1` — maksimal 10 akses halaman login per menit

### 3. Port Scanner
Command `NetworkScanPorts` (`app/Console/Commands/NetworkScanPorts.php`) berjalan setiap 5 menit melalui `routes/console.php`. Melakukan koneksi TCP ke 8 port umum menggunakan `fsockopen()` dengan timeout 2 detik. Hasil disimpan ke tabel `network_port_scans` dan ditampilkan di Security Dashboard sebagai **Open Ports**.

### 4. Intrusion Detection (Security Dashboard)
Analisis otomatis terhadap tabel `network_access_logs` di `SecurityController`:
- **Brute Force**: IP dengan ≥5× error 401 dalam 24 jam terakhir
- **High Rate**: IP dengan ≥30 request dalam 5 menit terakhir
- **Probing**: IP dengan ≥10× error 404 dalam 24 jam terakhir

### 5. Security Logs Viewer
Halaman `/security/logs` menampilkan seluruh `network_access_logs` dengan filter:
- Pencarian berdasarkan IP Address atau endpoint URL
- Pagination 50 data per halaman
- Status badge (success/redirect/client error/server error)

---

## Status Deployment

Semua fitur sudah di-push ke `main`. Jalankan di server:
```bash
cd ~/docker/projects/application_integration && git pull origin main
docker exec laravel-app php artisan migrate
docker exec laravel-app php artisan db:seed --class=FeaturePermissionSeeder
docker exec laravel-app php artisan network:scan-ports
docker restart laravel-app
```

Setelah deploy, assign fitur `security` ke role masing-masing di database (tabel `role_features`).
