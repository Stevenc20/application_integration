# Dashboard Supervisor — Monitoring Flow Manufacturing

## 1. Kebutuhan Dashboard Supervisor

Dashboard supervisor dirancang untuk memenuhi kebutuhan monitoring produksi secara real-time di Departemen Stamping. Kebutuhan utama meliputi:

### a. Monitoring Real-Time KPI per Line
Setiap line produksi (press) menampilkan KPI yang diperbarui secara langsung:

| KPI | Deskripsi |
|-----|-----------|
| **QTY** | Jumlah produksi aktual (OK) vs plan |
| **GSPH** | Gerak per Stroke per Hour — produktivitas aktual vs plan |
| **PROD_T** | Total waktu produksi (runtime) dalam menit |
| **TOTAL_DT** | Total downtime dalam menit |
| **MACH_T** | Downtime kategori mesin (machine) |
| **DIES_T** | Downtime kategori dies |
| **MAT_T** | Downtime kategori material |
| **LOG_T** | Downtime kategori logistik |
| **REPAIR** | Jumlah produk repair (pcs) |
| **REJECT** | Jumlah produk reject (pcs) |

Setiap KPI dilengkapi data plan, actual, dan current (untuk job yang sedang running), serta popup detail (rows breakdown).

### b. Monitoring Status Line Real-Time
Status operasional setiap line ditampilkan dengan indikator warna:

| Status | Warna | Keterangan |
|--------|-------|------------|
| PRODUCTION | Hijau | Mesin sedang berproduksi normal |
| DOWNTIME | Merah | Ada downtime aktif (mesin/process issue) |
| BREAKTIME | Kuning | Sedang jam istirahat |
| TRYOUT | Biru | Sedang try out |
| 1ST CHECK | Ungu | Sedang first check |
| SETUP | Amber | Sedang setup/changeover |
| NOT RUNNING | Abu-abu | Tidak ada aktivitas |

### c. Overview Produksi
Tampilan menyeluruh yang mencakup:
- **Ringkasan KPI**: total OK, repair, reject, target, achievement %, gap
- **Grafik kumulatif**: actual production vs expected production per jam
- **Jadwal break**: daftar break time, status istirahat, countdown sisa waktu
- **Line status**: status seluruh line dalam satu tampilan
- **Log produksi terbaru**: histori input produksi

### d. Laporan LKH (Lembar Kerja Harian)
- Laporan produksi harian lengkap dengan tabel KPI per item
- Performance analysis
- Downtime recap per kategori
- Handwork recap
- Ekspor PDF dan Excel

### e. Grafik Analisis
- Grafik kualitas (quality)
- Grafik downtime by item, type, machine
- Grafik GSPH

### f. Monitoring Aktivitas Operator
- **Input Harian**: start/pause/resume job, input hasil produksi (OK/repair/reject)
- **Dandori (Setup/Changeover)**: pencatatan setup mesin dan first check
- **Downtime**: pencatatan downtime dengan problem, penyebab, action, PIC
- **Repair & Reject**: logging defect dengan gambar

### g. Recovery & Cut-Off
- Monitoring recovery items yang perlu dikerjakan
- Approval workflow recovery
- Cut-off otomatis akhir shift

### h. Trouble History
Riwayat downtime per line dengan filter tanggal dan pencarian.

---

## 2. Alasan Dibuatnya Sistem

### Permasalahan Sebelumnya
- **Data tersebar**: Data produksi dicatat secara manual di kertas atau spreadsheet Excel, tersimpan di masing-masing operator/leader, tidak terpusat
- **Tidak real-time**: Supervisor tidak bisa memantau kondisi produksi secara langsung. Informasi keterlambatan baru diketahui setelah shift berjalan atau bahkan setelah shift selesai
- **Deteksi downtime lambat**: Ketika mesin mengalami masalah / downtime, supervisor tidak mendapatkan notifikasi langsung sehingga respons lambat
- **Rekap manual rentan error**: Pembuatan laporan LKH, performance report, dan downtime recap dilakukan manual dengan mengumpulkan data dari berbagai sumber, rawan kesalahan dan memakan waktu
- **Tidak ada visibilitas GSPH**: Metrik produktivitas GSPH tidak terpantau secara live, sehingga target produktivitas sulit dikendalikan
- **Koordinasi terbatas**: Tidak ada platform terpadu untuk koordinasi antara PPC (perencanaan), operator (pelaksana), dan supervisor (pengawas)
- **Data historis sulit dilacak**: Data produksi terdahulu tersimpan di dokumen fisik, sulit untuk analisis tren dan pengambilan keputusan

### Kebutuhan yang Muncul
- Pusat monitoring terintegrasi yang memberikan visibilitas penuh ke supervisor terhadap seluruh line produksi
- Dashboard real-time yang menampilkan KPI, status line, dan data produksi terkini
- Sistem pencatatan produksi terkomputerisasi yang terpusat (input harian, downtime, dandori)
- Pelaporan otomatis yang mengurangi pekerjaan administratif manual
- Basis data historis untuk analisis dan pengambilan keputusan berbasis data

---

## 3. Solusi yang Kamu Bangun

### Arsitektur Sistem
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL
- **Frontend**: Blade templates, Tailwind CSS 4, Alpine.js, Chart.js
- **Real-time**: Server-Sent Events (SSE) untuk streaming data dashboard
- **Build tools**: Vite 7

### Komponen Utama Supervisor Dashboard

#### Backend (Services)

| Service | Fungsi |
|---------|--------|
| `DashboardRealtimeService` | Komputasi KPI per line — mengagregasi OK/repair/reject, menghitung runtime, downtime breakdown, GSPH, PROD_T. Menangani auto-create JobMaster dan DailyProduction jika belum ada |
| `DashboardDetailService` | Data detail per line untuk popup |
| `LineStatusService` | Menentukan status operasional setiap line (PRODUCTION/DOWNTIME/BREAKTIME/TRYOUT/1ST CHECK/SETUP/NOT RUNNING) dengan prioritas tertentu |
| `ProductionMetricsService` | Formula KPI: GSPH, TPT, CT, OEE (availability/performance/quality), downtime breakdown, balance, achievement |

#### Controller
- **`Supervisor/DashboardController`**: Handler utama dashboard — index, overview, real-time SSE streaming, API data, detail data, trouble history, line status
- **`Supervisor/ReportController`**: Laporan LKH daily production, performance, downtime recap, handwork recap dengan ekspor PDF/Excel
- **`Api/GrafikController`**: API endpoint untuk grafik kualitas, downtime item/type/machine

#### Real-Time Dashboard (SSE)
Dashboard menggunakan Server-Sent Events (`/supervisor/dashboard/stream`) yang:
1. Melakukan polling setiap 2 detik
2. Mengecek apakah ada update per line (via cache key `dash_update_{line}`)
3. Jika ada update, mengirim data KPI, meta, dan detail data ke frontend
4. Frontend JavaScript secara otomatis memperbarui kartu KPI, status, dan tabel tanpa reload

**Flow**: Input Harian (Operator) → `ProductionService` → `DashboardRealtimeService::signalUpdate(line)` → SSE Stream → Dashboard Supervisor

### Fitur Lengkap

#### Dashboard (Real-Time)
- Kartu KPI per line dengan plan/actual/current
- Status line dengan indikator warna dan animasi pulse
- Live clock
- Filter tanggal dan shift
- Popup detail setiap KPI (tabel breakdown)
- Grafik produksi (QTY, downtime, repair/reject) dengan zoom/pan interaktif

#### Overview
- Ringkasan KPI: total OK, repair, reject, target, achievement %, gap
- Progress bar dengan warna indikator (hijau ≥80%, kuning ≥50%, merah <50%)
- Grafik kumulatif actual vs expected per jam
- Informasi shift (start/end time, sisa waktu, overtime)
- Jadwal break real-time dengan status dan countdown
- Line status seluruh line
- Log produksi terbaru dengan pagination

#### Monitoring Line
- Tampilan monitor yang menunjukkan kartu status setiap line
- Informasi current job, stroke count

#### Reports (LKH)
- Daily production report dengan tabel KPI per item produksi
- Performance analysis
- Downtime recap lengkap per kategori
- Handwork recap
- Ekspor PDF dan Excel

#### Grafik Analisis
- Grafik kualitas (OK vs reject rate)
- Grafik downtime item, type, dan machine
- Grafik GSPH per period

#### Trouble History
- Riwayat downtime dengan filter tanggal dan pencarian
- Detail: problem, penyebab, action, PIC, durasi

### Integrasi Sistem
Dashboard supervisor tidak berdiri sendiri, melainkan terintegrasi dengan modul-modul lain:

| Modul | Hubungan dengan Dashboard |
|-------|--------------------------|
| **Input Harian** | Sumber data produksi (OK/repair/reject), memicu update dashboard via signal |
| **Dandori** | Mempengaruhi status line (SETUP / 1ST CHECK) |
| **Downtime** | Sumber data downtime breakdown, mempengaruhi status line (DOWNTIME/BREAKTIME) |
| **PPC Planning** | Sumber data plan (target QTY, GSPH plan, timeline) |
| **Recovery** | Monitoring item recovery, approval workflow |
| **Shift Submission** | Finalisasi data produksi per shift |
| **LKH Reports** | Mengambil data dari daily production, downtime, dan dandori untuk laporan |

### Teknologi dan Tools
- **SSE Streaming**: Update dashboard real-time tanpa WebSocket, menggunakan Laravel StreamedResponse
- **Cache Driver**: File cache untuk signaling update antar request
- **Tailwind CSS 4**: Layout responsif grid 1-2 kolom, kartu KPI dengan desain modern
- **Chart.js**: Grafik produksi dengan fitur zoom dan pan (via hammer.js)
- **JavaScript Vanilla**: Timer engine, auto-refresh dashboard, live clock

---

## 4. Identifikasi Masalah

Berdasarkan latar belakang yang telah diuraikan, permasalahan yang teridentifikasi dalam sistem yang berjalan adalah sebagai berikut:

### 4.1. Proses pencatatan produksi pada modul Input Harian tidak efisien
Mekanisme parent dan sub item pada modul Input Harian mengharuskan operator menjalankan beberapa timer secara berurutan sebelum produksi dapat dicatat. Hal ini menambah langkah kerja operator, meningkatkan jeda antara aktivitas produksi dengan pencatatannya, serta memperlambat pembaruan data produksi.

### 4.2. Data produksi tidak terintegrasi dalam satu platform
Data hasil produksi, waktu henti mesin (downtime), aktivitas setup (dandori), pemeriksaan kualitas (Q-Check), data repair dan reject, serta data perencanaan produksi dari PPC masih tersimpan dan dikelola secara terpisah. Supervisor harus memeriksa data dari beberapa sumber untuk mendapatkan gambaran kondisi produksi secara menyeluruh.

### 4.3. Monitoring supervisor belum berjalan secara near real-time
Dashboard monitoring belum menampilkan data yang diperbarui segera setelah aktivitas produksi berlangsung. Informasi produktivitas, capaian produksi, kondisi setiap jalur produksi, dan Gross Stroke Per Hour (GSPH) tidak dapat diperoleh secara cepat dan akurat melalui satu tampilan terpadu.

### 4.4. Pelaporan produksi masih dilakukan secara manual
Proses rekap Lembar Kerja Harian (LKH), performance report, dan downtime recap dikerjakan secara manual dengan mengumpulkan data dari berbagai sumber. Hal ini membutuhkan waktu yang lama, rentan terhadap kesalahan manusia, dan menghambat ketersediaan laporan tepat waktu.

### 4.5. Antarmuka modul Input Harian belum terintegrasi secara optimal dengan kebutuhan monitoring supervisor
Struktur parent dan sub item pada Input Harian menyebabkan data produksi (OK, repair, reject) tidak segera tercatat dan tersedia untuk dashboard monitoring. Aktivitas operator, pencatatan downtime, aktivitas setup, dan data perencanaan PPC berjalan pada alur yang terpisah sehingga data yang dibutuhkan supervisor untuk memantau produktivitas, GSPH, dan status jalur produksi tidak tersedia secara near real-time dalam satu tampilan terpadu.

---

## 5. Tujuan Penelitian

Berdasarkan permasalahan dan solusi yang telah dibangun, penelitian ini bertujuan untuk:

1. **Merancang dan membangun sistem dashboard monitoring produksi real-time** yang terintegrasi untuk supervisor di Departemen Stamping, mampu menampilkan KPI (QTY, GSPH, PROD_T, TOTAL_DT, MACH_T, DIES_T, MAT_T, LOG_T, REPAIR, REJECT) secara langsung dan akurat.

2. **Mengintegrasikan data dari berbagai sumber operasional** — input harian operator, pencatatan downtime, aktivitas dandori (setup/changeover), dan data planning PPC — ke dalam satu platform dashboard terpadu sehingga supervisor memiliki visibilitas menyeluruh terhadap flow manufacturing.

3. **Menyediakan visualisasi status line dan KPI secara real-time** menggunakan teknologi Server-Sent Events (SSE) yang memungkinkan supervisor mendeteksi dan merespons gangguan produksi (downtime, bottleneck) dengan cepat.

4. **Mengotomatiskan pembuatan laporan LKH (Lembar Kerja Harian)** dan rekap performa yang sebelumnya dilakukan secara manual, sehingga mengurangi beban administratif supervisor dan meningkatkan akurasi data laporan.

5. **Meningkatkan efektivitas pengawasan supervisor** terhadap proses produksi melalui dashboard yang menyajikan informasi terkini, data historis, dan grafik analisis sebagai dasar pengambilan keputusan yang cepat dan tepat.

---

## 6. Manfaat Penelitian

Penelitian ini diharapkan memberikan manfaat sebagai berikut:

### 6.1. Bagi Supervisor
- Mendapatkan dashboard monitoring produksi real-time yang menampilkan KPI (QTY, GSPH, PROD_T, TOTAL_DT, MACH_T, DIES_T, MAT_T, LOG_T, REPAIR, REJECT) dan status setiap jalur produksi dalam satu tampilan terpadu.
- Memantau kondisi line secara langsung melalui indikator status (PRODUCTION, DOWNTIME, BREAKTIME, SETUP, dll) sehingga respons terhadap gangguan produksi dapat dilakukan lebih cepat.
- Mengakses laporan LKH, performance report, dan downtime recap secara otomatis tanpa rekap manual.

### 6.2. Bagi Operator
- Proses pencatatan produksi pada Input Harian menjadi lebih efisien dengan mekanisme timer yang terintegrasi langsung dengan job dan plan produksi.
- Pencatatan downtime, dandori, repair, dan reject dapat dilakukan dalam satu sistem tanpa beralih ke media lain.

### 6.3. Bagi Production Planning and Control (PPC)
- Data hasil produksi aktual (OK, repair, reject) langsung terintegrasi dengan data perencanaan sehingga memudahkan evaluasi pencapaian plan vs actual.
- Proses cut-off dan recovery dapat dijalankan secara otomatis tanpa rekonsiliasi data manual.

### 6.4. Bagi Manager
- Mendapatkan ringkasan capaian produksi seluruh line dalam satu tampilan dashboard tanpa harus mengecek data ke masing-masing supervisor.
- Memantau efektivitas operasional melalui indikator GSPH, downtime breakdown, dan achievement produksi secara near real-time.
- Mengakses laporan performa produksi secara cepat untuk evaluasi harian, mingguan, maupun bulanan.

### 6.5. Bagi Manajemen
- Mendapatkan laporan produksi yang akurat, tepat waktu, dan dapat diekspor dalam format PDF maupun Excel untuk kebutuhan analisis lebih lanjut.
- Data historis produksi tersimpan secara terpusat dan dapat diakses kapan saja untuk kebutuhan evaluasi dan pengambilan keputusan strategis.

### 6.5. Bagi Perusahaan
- Digitalisasi proses pencatatan produksi mengurangi penggunaan kertas dan meminimalkan risiko kehilangan data.
- Peningkatan efektivitas operasional melalui monitoring near real-time yang mempercepat deteksi dan penanganan masalah produksi.
- Data produksi yang akurat dan terintegrasi mendukung perbaikan berkelanjutan (continuous improvement) pada proses manufaktur.
