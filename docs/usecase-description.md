# 3.5 Use Case Diagram

## 3.5.1 Use Case Diagram

Berikut adalah Use Case Diagram dari Sistem Integrasi Produksi Stamping yang menjelaskan seluruh aktor dan fungsi yang ada pada sistem.

### Penjelasan Aktor

| No | Aktor | Penjelasan |
|----|-------|------------|
| 1 | **Super Admin** | Pengguna dengan hak akses penuh terhadap seluruh sistem. Bertanggung jawab dalam mengelola pengguna, role, fitur, permission, assignment line, recycle bin, tanda tangan digital (TTD), notifikasi, serta manajemen departemen dan posisi. |
| 2 | **Admin** | Bertanggung jawab dalam mengelola data pengguna serta memantau log aktivitas sistem (business event log). Memiliki akses ke dashboard admin dan monitoring. |
| 3 | **Supervisor** | Pengawas lantai produksi yang bertanggung jawab memantau seluruh jalur produksi secara real-time melalui dashboard SSE, membuat laporan harian (LKH), mengelola Q-Check, menganalisis downtime dan performa produksi, melakukan approval recovery items, serta memantau hambatan jalur. |
| 4 | **Operator** | Pengguna utama yang melakukan pencatatan produksi harian (input harian), mengelola timer produksi (start/pause/resume/finish), mencatat qty OK/repair/reject, mengelola dandori (setup/changeover), downtime, break time, Q-Check, serta melakukan shift submission di akhir shift. |
| 5 | **Foreman** | Bertanggung jawab memantau status line secara real-time, mencatat downtime, melakukan Q-Check, mengelola handwork, serta memantau break time di area produksinya. |
| 6 | **Leader** | Pemimpin tingkat jalur produksi yang memantau status line, mencatat downtime, menandatangani hambatan jalur (line obstruction), serta melihat notifikasi. |
| 7 | **PPC Staff** | Staf perencanaan produksi yang bertanggung jawab mengelola production plan, schedule stamping, recovery schedule (cut-off & approval), Bill of Material (BOM), production order (SPP), MRP, master stamping, serta rundon press. |
| 8 | **IRM Staff** | Staf inventaris dan bahan baku yang mengelola vendor, material, customer, storage location, purchase order, goods receipt, goods issue, stock overview, dan summary kanban. |
| 9 | **Logistik** | Staf logistik yang mengelola rundown incoming, pallet mutation, SMR vendor/customer, data GR, dan data scrap. |
| 10 | **Quality Staff** | Staf kualitas yang memantau quality dashboard, melakukan Q-Check, memantau defect, mencatat repair & reject, serta menganalisis pencapaian kualitas. |
| 11 | **Manager** | Pemimpin tingkat manajer yang memantau dashboard real-time dan menganalisis trouble history produksi. |
| 12 | **Kadiv (Kepala Divisi)** | Kepala divisi yang memantau dashboard real-time dan menganalisis trouble history produksi di tingkat divisi. |
| 13 | **Direktur** | Direktur yang memantau dashboard real-time produksi secara keseluruhan. |
| 14 | **Presiden Direktur** | Presiden direktur yang memantau dashboard realtime produksi di tingkat eksekutif. |
| 15 | **Dies Shop** | Staf dies shop yang terlibat dalam pencatatan hambatan jalur (line obstruction). |
| 16 | **Plant Service** | Staf plant service yang terlibat dalam pencatatan hambatan jalur (line obstruction). |

---

### Gambar 3.1 Use Case Diagram Overview

![Use Case Diagram Overview](usecase-overview.puml)

> *Gambar 3.1 Use Case Diagram Overview — Menunjukkan seluruh aktor dan use case utama pada sistem.*

### Gambar 3.2 Use Case Diagram Operator

![Use Case Diagram Operator](usecase-operator.puml)

> *Gambar 3.2 Use Case Diagram Operator — Detail aktivitas operator dalam input produksi harian.*

### Gambar 3.3 Use Case Diagram Supervisor

![Use Case Diagram Supervisor](usecase-supervisor.puml)

> *Gambar 3.3 Use Case Diagram Supervisor — Detail fungsi supervisor dalam monitoring, laporan, dan approval.*

### Gambar 3.4 Use Case Diagram PPC

![Use Case Diagram PPC](usecase-ppc.puml)

> *Gambar 3.4 Use Case Diagram PPC — Detail perencanaan produksi, schedule, BOM, SPP, dan MRP.*

### Gambar 3.5 Use Case Diagram IRM

![Use Case Diagram IRM](usecase-irm.puml)

> *Gambar 3.5 Use Case Diagram IRM — Detail pengelolaan inventaris, PO, GR, GI, dan kanban.*

### Gambar 3.6 Use Case Diagram Logistik

![Use Case Diagram Logistik](usecase-logistics.puml)

> *Gambar 3.6 Use Case Diagram Logistik — Detail rundown incoming, pallet, dan SMR.*

### Gambar 3.7 Use Case Diagram Quality

![Use Case Diagram Quality](usecase-quality.puml)

> *Gambar 3.7 Use Case Diagram Quality — Detail Q-Check, defect monitoring, dan repair/reject.*

### Gambar 3.8 Use Case Diagram Admin & Super Admin

![Use Case Diagram Admin](usecase-admin.puml)

> *Gambar 3.8 Use Case Diagram Admin — Detail user management, permission, recycle bin, dan TTD.*

### Gambar 3.9 Use Case Diagram Monitoring

![Use Case Diagram Monitoring](usecase-monitoring.puml)

> *Gambar 3.9 Use Case Diagram Monitoring — Detail monitoring real-time untuk seluruh role.*

### Gambar 3.10 Use Case Diagram Recovery Workflow

![Use Case Diagram Recovery](usecase-recovery-workflow.puml)

> *Gambar 3.10 Use Case Diagram Recovery — Detail workflow cut-off hingga approval dan rescheduling.*

---

## 3.5.2 Deskripsi Use Case

Berikut adalah deskripsi detail dari seluruh use case yang ada pada sistem:

### A. Autentikasi & Dashboard

| No | Use Case | Aktor | Deskripsi |
|----|----------|-------|-----------|
| 1 | Login | Semua Aktor | Pengguna melakukan autentikasi menggunakan NRP dan password untuk mengakses sistem berdasarkan role masing-masing. |
| 2 | Lihat Dashboard | Operator, Foreman, Leader, Supervisor, Manager, Kadiv, Direktur, Presdir | Pengguna melihat dashboard sesuai role masing-masing. Supervisor menggunakan dashboard real-time SSE yang menampilkan KPI per line (QTY, GSPH, PROD_T, TOTAL_DT, MACH_T, DIES_T, MAT_T, LOG_T, REPAIR, REJECT). |
| 3 | Lihat Notifikasi | Semua Aktor | Pengguna melihat notifikasi in-app yang berisi informasi penting terkait aktivitas produksi. Notifikasi ditandai sudah dibaca setelah dilihat. |

### B. Produksi

| No | Use Case | Aktor | Deskripsi |
|----|----------|-------|-----------|
| 4 | Input Harian | Operator | Operator mencatat produksi harian per job mencakup qty OK, qty repair, dan qty reject. Use case ini merupakan use case utama yang memiliki beberapa extend use case. |
| 5 | Start Job | Operator | Operator memulai timer produksi saat mengerjakan suatu job. |
| 6 | Pause Job | Operator | Operator menjeda timer produksi saat terjadi gangguan atau istirahat. *Extend dari Start Job.* |
| 7 | Resume Job | Operator | Operator melanjutkan timer produksi setelah selesai menjeda. *Extend dari Start Job.* |
| 8 | Finish Job | Operator | Operator menghentikan timer produksi dan menyelesaikan pencatatan qty. |
| 9 | Input OK Qty | Operator | Operator memasukkan jumlah barang OK yang dihasilkan. *Include dalam Input Harian.* |
| 10 | Input Repair | Operator | Operator memasukkan jumlah barang repair. *Extend dari Input Harian.* |
| 11 | Input Reject | Operator | Operator memasukkan jumlah barang reject. *Extend dari Input Harian.* |
| 12 | Break Time | Operator, Foreman | Pengguna mencatat jadwal istirahat produksi. |
| 13 | Dandori (Setup/Changeover) | Operator | Operator mencatat aktivitas setup atau changeover mesin, mencakup input dandori dan riwayat dandori. *Extend dari Input Harian.* |
| 14 | Downtime | Operator | Operator mencatat waktu henti mesin dengan mencatat masalah, penyebab, tindakan, PIC, dan durasi. *Extend dari Input Harian.* |
| 15 | Q-Check | Operator | Operator melakukan pemeriksaan kualitas di lapangan. *Extend dari Input Harian.* |
| 16 | Repair & Reject Log | Operator | Operator mencatat detail kerusakan barang repair/reject dengan foto, kategori, akar masalah, dan countermeasure. *Extend dari Input Harian.* |
| 17 | Handwork | Foreman, Supervisor | Pengguna mencatat aktivitas pekerjaan manual (handwork) per production plan. |
| 18 | Shift Submission | Operator | Operator melakukan finalisasi data produksi di akhir shift. Use case ini mencakup identifikasi job yang belum selesai untuk masuk ke dalam workflow recovery. *Include Input Harian.* |

### C. Monitoring

| No | Use Case | Aktor | Deskripsi |
|----|----------|-------|-----------|
| 19 | Dashboard Realtime (SSE) | Operator, Foreman, Leader, Supervisor, Manager, Kadiv, Direktur, Presdir | Supervisor memantau KPI produksi secara real-time menggunakan Server-Sent Events (SSE) dengan interval 2 detik. |
| 20 | Monitor Status Line | Foreman, Leader, Supervisor | Pengguna memantau status setiap jalur produksi (PRODUCTION, DOWNTIME, BREAKTIME, TRYOUT, 1ST CHECK, SETUP, NOT RUNNING). |
| 21 | Lihat KPI | Semua Aktor Monitoring | Pengguna melihat indikator kinerja produksi meliputi QTY, GSPH, PROD_T, TOTAL_DT, MACH_T, DIES_T, MAT_T, LOG_T. |
| 22 | Drill-down Detail Line | Supervisor | Supervisor mengklik detail line untuk melihat data produksi lebih mendalam. *Extend dari Dashboard Realtime.* |
| 23 | Line Monitoring View | Foreman, Supervisor | Pengguna melihat tampilan semua line cards beserta status mesinnya. |
| 24 | Cek Machine Status | Foreman, Supervisor | Pengguna memeriksa status mesin pada masing-masing line. *Extend dari Line Monitoring View.* |
| 25 | Downtime List (Aktif) | Foreman, Supervisor | Pengguna melihat daftar downtime yang sedang aktif/berlangsung. |
| 26 | Trouble History | Supervisor, Manager, Kadiv | Pengguna melihat riwayat trouble/downtime dengan filter tanggal dan pencarian. |
| 27 | Tryout Monitoring | Supervisor | Supervisor memantau aktivitas tryout pada line produksi. |
| 28 | Hambatan Jalur (Line Obstruction) | Leader, Dies Shop, Plant Service | Pihak terkait mencatat hambatan jalur dan melakukan tanda tangan leader. |
| 29 | Sign by Leader (TTD) | Leader | Leader menandatangani hambatan jalur secara digital. *Include dalam Hambatan Jalur.* |
| 30 | Production Analytics | Supervisor | Supervisor menganalisis data produksi dengan drill-down ke level job. *Extend dari Drill-down Detail Line.* |

### D. Perencanaan Produksi (PPC)

| No | Use Case | Actor | Deskripsi |
|----|----------|-------|-----------|
| 31 | Production Plan | PPC Staff | PPC Staff mengelola rencana produksi meliputi import Excel, edit inline, reorder sequence, dan clear data. |
| 32 | Import Excel Plan | PPC Staff | PPC Staff mengimpor data production plan dari file Excel. *Include dalam Production Plan.* |
| 33 | Edit Plan (inline) | PPC Staff | PPC Staff mengedit data production plan secara langsung di tabel. *Include dalam Production Plan.* |
| 34 | Reorder Sequence | PPC Staff | PPC Staff mengubah urutan sequence job pada production plan. *Extend dari Production Plan.* |
| 35 | Schedule Stamping | PPC Staff | PPC Staff mengelola jadwal stamping meliputi upload schedule dari Excel, inline update, rekalibrasi, dan export schedule. |
| 36 | Upload Schedule | PPC Staff | PPC Staff mengunggah jadwal stamping dari file Excel. *Include dalam Schedule Stamping.* |
| 37 | Inline Update | PPC Staff | PPC Staff mengupdate jadwal stamping secara langsung di tabel. *Include dalam Schedule Stamping.* |
| 38 | Rekalibrasi | PPC Staff | PPC Staff melakukan rekalibrasi jadwal stamping. *Extend dari Schedule Stamping.* |
| 39 | Export Schedule | PPC Staff | PPC Staff mengekspor jadwal stamping ke file Excel. *Include dalam Schedule Stamping.* |
| 40 | Recovery Schedule | PPC Staff, Supervisor | PPC Staff dan Supervisor mengelola recovery schedule yang dihasilkan dari cut-off shift, mulai dari review, approve/reject, hingga scheduling ke production plan. |
| 41 | Generate Cut-Off | System (otomatis) | Sistem secara otomatis mengidentifikasi job yang belum selesai saat shift berakhir dan membuat recovery item dengan status WAITING_APPROVAL. *Include dalam Recovery Schedule.* |
| 42 | Approve/Reject Item | PPC Staff, Supervisor | PPC Staff atau Supervisor menyetujui atau menolak recovery item. Status berubah: WAITING_APPROVAL → APPROVED → SCHEDULED → IN_PRODUCTION → COMPLETED. *Extend dari Recovery Schedule.* |
| 43 | Schedule ke Plan | PPC Staff | PPC Staff menjadwalkan recovery item ke production plan. *Include dalam Recovery Schedule.* |
| 44 | BOM (Bill of Material) | PPC Staff | PPC Staff mengelola Bill of Material meliputi CRUD, import Excel, dan export PDF/Excel. |
| 45 | Import BOM | PPC Staff | PPC Staff mengimpor data BOM dari file Excel. *Extend dari BOM.* |
| 46 | Export BOM | PPC Staff | PPC Staff mengekspor data BOM ke PDF atau Excel. *Include dalam BOM.* |
| 47 | Production Order (SPP) | PPC Staff | PPC Staff mengelola surat perintah produksi meliputi release, goods issue, confirm, cancel, dan print. |
| 48 | Release Order | PPC Staff | PPC Staff merilis production order ke floor produksi. *Include dalam Production Order.* |
| 49 | Goods Issue Order | PPC Staff | PPC Staff melakukan pencatatan pengeluaran barang untuk production order. *Extend dari Production Order.* |
| 50 | Confirm Order | PPC Staff | PPC Staff mengkonfirmasi penyelesaian production order. *Extend dari Production Order.* |
| 51 | Cancel Order | PPC Staff | PPC Staff membatalkan production order. *Extend dari Production Order.* |
| 52 | MRP (Material Requirement Planning) | PPC Staff | PPC Staff menjalankan MRP untuk menghitung kebutuhan material, mengimpor demand, dan mengekspor hasil MRP ke PDF/Excel. |
| 53 | Run MRP | PPC Staff | PPC Staff menjalankan proses kalkulasi MRP. *Include dalam MRP.* |
| 54 | Import Demand | PPC Staff | PPC Staff mengimpor data demand ke dalam MRP. *Extend dari MRP.* |
| 55 | Master Stamping | PPC Staff | PPC Staff mengelola master data stamping dan mengimpor dari file Excel. |
| 56 | Rundown Press | PPC Staff | PPC Staff melakukan simulasi dan monitoring rundon press, mengupload data dari Excel, dan melakukan inline update. |

### E. Persediaan & Bahan Baku (IRM)

| No | Use Case | Actor | Deskripsi |
|----|----------|-------|-----------|
| 57 | Vendor | IRM Staff | IRM Staff mengelola data vendor meliputi CRUD, import Excel, dan export PDF/Excel. |
| 58 | CRUD Vendor | IRM Staff | IRM Staff membuat, membaca, memperbarui, dan menghapus data vendor. *Include dalam Vendor.* |
| 59 | Import Vendor | IRM Staff | IRM Staff mengimpor data vendor dari file Excel. *Extend dari Vendor.* |
| 60 | Export Vendor | IRM Staff | IRM Staff mengekspor data vendor ke PDF atau Excel. *Include dalam Vendor.* |
| 61 | Material | IRM Staff | IRM Staff mengelola data material meliputi CRUD, import Excel, dan export PDF/Excel. |
| 62 | CRUD Material | IRM Staff | IRM Staff membuat, membaca, memperbarui, dan menghapus data material. *Include dalam Material.* |
| 63 | Import Material | IRM Staff | IRM Staff mengimpor data material dari file Excel. *Extend dari Material.* |
| 64 | Export Material | IRM Staff | IRM Staff mengekspor data material ke PDF atau Excel. *Include dalam Material.* |
| 65 | Customer | IRM Staff | IRM Staff mengelola data customer meliputi CRUD, import Excel, dan export PDF/Excel. |
| 66 | Storage Location | IRM Staff | IRM Staff mengelola data lokasi penyimpanan meliputi CRUD dan export Excel. |
| 67 | Purchase Order (PO) | IRM Staff | IRM Staff mengelola surat pesanan pembelian meliputi create, approval, cancel, import Excel, dan export PDF/Excel. |
| 68 | Create PO | IRM Staff | IRM Staff membuat purchase order baru. *Include dalam Purchase Order.* |
| 69 | Approval PO | IRM Staff, Supervisor | IRM Staff atau Supervisor menyetujui purchase order. *Extend dari Purchase Order.* |
| 70 | Cancel PO | IRM Staff | IRM Staff membatalkan purchase order. *Extend dari Purchase Order.* |
| 71 | Goods Receipt (GR) | IRM Staff | IRM Staff mengelola penerimaan barang meliputi create, update, dan print GR. |
| 72 | Create GR | IRM Staff | IRM Staff membuat goods receipt baru. *Include dalam Goods Receipt.* |
| 73 | Update GR | IRM Staff | IRM Staff memperbarui data goods receipt. *Extend dari Goods Receipt.* |
| 74 | Print GR | IRM Staff | IRM Staff mencetak goods receipt. *Include dalam Goods Receipt.* |
| 75 | Goods Issue (GI) | IRM Staff | IRM Staff mengelola pengeluaran barang meliputi create, update, dan print GI. |
| 76 | Summary Kanban | IRM Staff | IRM Staff mengelola kanban summary meliputi generate PO dari kanban, import demand, dan export ke PDF/Excel. |
| 77 | Stock Overview | IRM Staff | IRM Staff melihat overview stok persediaan dan mengekspor ke Excel. |
| 78 | Business Event Log | IRM Staff | IRM Staff melihat log aktivitas bisnis dan mengekspor ke Excel. |

### F. Logistik

| No | Use Case | Actor | Deskripsi |
|----|----------|-------|-----------|
| 79 | Rundown Incoming | Logistik | Logistik mengelola data material masuk meliputi upload rundown dari Excel, add/delete job, inline update, export ke Excel, dan download template. |
| 80 | Upload Rundown | Logistik | Logistik mengunggah data rundown dari file Excel. *Extend dari Rundown Incoming.* |
| 81 | Add/Delete Incoming Job | Logistik | Logistik menambah atau menghapus job pada rundown incoming. *Include dalam Rundown Incoming.* |
| 82 | Inline Update | Logistik | Logistik mengupdate data rundown secara langsung di tabel. *Include dalam Rundown Incoming.* |
| 83 | Export Rundown | Logistik | Logistik mengekspor data rundown ke file Excel. *Include dalam Rundown Incoming.* |
| 84 | Pallet Mutation | Logistik | Logistik mencatat pergerakan (mutasi) palet keluar dan masuk. |
| 85 | SMR Vendor | Logistik | Logistik melihat data Supplier Material Report dari vendor. |
| 86 | SMR Customer | Logistik | Logistik melihat data Supplier Material Report dari customer. |
| 87 | Data GR | Logistik, Dies Shop | Logistik dan Dies Shop melihat data goods receipt. |
| 88 | Data Scrap | Logistik | Logistik melihat data barang scrap. |

### G. Kualitas

| No | Use Case | Actor | Deskripsi |
|----|----------|-------|-----------|
| 89 | Quality Dashboard | Quality Staff | Quality Staff memantau dashboard kualitas beserta KPI quality dan grafik pencapaian kualitas. |
| 90 | Q-Check | Quality Staff | Quality Staff melakukan pemeriksaan kualitas meliputi pembuatan form, daftar, dan hasil Q-Check. |
| 91 | Defect Monitoring | Quality Staff | Quality Staff memantau data cacat produksi meliputi daftar defect dan analisis berdasarkan jenis/line. |
| 92 | Repair & Reject Log | Quality Staff | Quality Staff mencatat detail repair/reject beserta foto, dan menganalisis berdasarkan root cause. |
| 93 | Quality Achievement | Quality Staff | Quality Staff melihat grafik pencapaian kualitas produksi. |
| 94 | Quality CRUD | Quality Staff | Quality Staff mengelola master data kualitas. |

### H. Administrasi Sistem

| No | Use Case | Actor | Deskripsi |
|----|----------|-------|-----------|
| 95 | User Management | Super Admin, Admin | Super Admin dan Admin mengelola data pengguna meliputi CRUD user, assign role, dan reset password. |
| 96 | Feature & Permission | Super Admin | Super Admin mengelola fitur dan permission per role meliputi toggle feature dan assign feature ke role. |
| 97 | Line Assignment | Super Admin | Super Admin mengatur penugasan pengguna ke line produksi, termasuk assign dan remove. |
| 98 | Recycle Bin | Super Admin | Super Admin mengelola data yang di-soft delete, termasuk restore record dan force delete. |
| 99 | Signature (TTD) | Super Admin, Supervisor | Super Admin mengelola tanda tangan digital (upload/hapus), Supervisor menandatangani LKH. |
| 100 | Department & Position | Super Admin | Super Admin mengelola data departemen, posisi, dan section. |
| 101 | Notification | Super Admin, Supervisor | Super Admin dan pengguna lain melihat notifikasi in-app dan menandai sudah dibaca. |
| 102 | Profile | Semua Aktor | Pengguna memperbarui profil dan mengunggah avatar. |

---

### Ringkasan Use Case per Aktor

| Aktor | Jumlah Use Case | Use Case Utama |
|-------|----------------|----------------|
| Operator | 12 | Input Harian, Start/Pause/Resume/Finish Job, Input OK/Repair/Reject, Break Time, Dandori, Downtime, Q-Check, Shift Submission |
| Supervisor | 18 | Dashboard Realtime, Monitor Line, LKH Report, Trouble History, Grafik Downtime/GSPH/Achievement, Q-Check Management, Approval Recovery, Hambatan Jalur |
| PPC Staff | 20 | Production Plan, Schedule Stamping, Recovery Schedule, BOM, Production Order (SPP), MRP, Master Stamping, Rundown Press |
| IRM Staff | 15 | Vendor, Material, Customer, Storage, PO, GR, GI, Summary Kanban, Stock Overview |
| Foreman | 8 | Dashboard Realtime, Line Monitoring, Downtime, Q-Check, Handwork, Break Time |
| Quality Staff | 6 | Quality Dashboard, Q-Check, Defect Monitoring, Repair & Reject Log, Quality Achievement, Quality CRUD |
| Logistik | 7 | Rundown Incoming, Pallet Mutation, SMR Vendor/Customer, Data GR, Data Scrap |
| Super Admin | 10 | User Management, Feature & Permission, Line Assignment, Recycle Bin, TTD, Dept & Position, Notification, Event Log, Profile |
| Admin | 3 | User Management, Dashboard Admin, Business Event Log |
| Leader | 4 | Dashboard Realtime, Downtime, Hambatan Jalur, Notification |
| Manager | 3 | Dashboard Realtime, Trouble History |
| Kadiv | 3 | Dashboard Realtime, Trouble History |
| Direktur | 2 | Dashboard Realtime |
| Presiden Direktur | 2 | Dashboard Realtime |
| Dies Shop | 3 | Hambatan Jalur, Data GR |
| Plant Service | 2 | Hambatan Jalur |
