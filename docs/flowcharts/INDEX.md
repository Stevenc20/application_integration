# 13 Detailed Flowcharts — Master Business Flow

## Ringkasan

13 flowchart detail ini merupakan pemecahan dari Master Business Flow.

Seluruh flowchart harus digabungkan kembali untuk menghasilkan Master Business Flow yang sama.

Flowchart dibuat pada level Business Process, bukan level implementasi source code.

---

## Daftar Flowchart

| # | Nama | File | Coverage |
|---|------|------|----------|
| 1 | Production Plan Import | [FC1](FC1-Production-Plan-Import.md) | PPC Upload → Publish Schedule |
| 2 | Input Harian | [FC2](FC2-Input-Harian.md) | Buka Input Harian → Pilih Job |
| 3 | Dandori & First Check | [FC3](FC3-Dandori-First-Check.md) | Persiapan Produksi |
| 4 | Proses Produksi | [FC4](FC4-Proses-Produksi.md) | Start → Finish → Submit Shift |
| 5 | Pencatatan Hasil Produksi | [FC5](FC5-Pencatatan-Hasil-Produksi.md) | OK / Repair / Reject |
| 6 | Downtime & Hambatan Jalur | [FC6](FC6-Downtime-Hambatan-Jalur.md) | Downtime + TTD |
| 7 | Break Time & Continue Session | [FC7](FC7-Break-Time-Continue-Session.md) | Break Start → Finish |
| 8 | Recovery | [FC8](FC8-Recovery.md) | Cut-Off → Approval → Regenerate |
| 9 | Monitoring Produksi (Supervisor) | [FC9](FC9-Monitoring-Produksi-Supervisor.md) | Real-Time Dashboard |
| 10 | Dashboard KPI (Manager) | [FC10](FC10-Dashboard-KPI-Manager.md) | GSPH, OEE, Achievement |
| 11 | LKH | [FC11](FC11-LKH.md) | Laporan Kerja Harian |
| 12 | Downtime Recap | [FC12](FC12-Downtime-Recap.md) | Rekap Downtime |
| 13 | Performance Report | [FC13](FC13-Performance-Report.md) | Laporan Kinerja |

---

## Flow Connection Map

```
FC1 → FC2 → FC3 → FC4 → FC5
                       ↓
                    FC6 (loop back to FC4)
                    FC7 (loop back to FC4)
                       ↓
                    FC8 → FC1 (recovery loop)
                       ↓
                    FC4 continues...
                       ↓
                    FC9 (parallel, runs throughout)
                    FC10 (parallel, runs throughout)
                       ↓
                    FC11, FC12, FC13 (reporting after shift ends)
```

---

## Simbol Flowchart

| Simbol | Keterangan |
|--------|------------|
| ○ | Start / End |
| □ | Proses |
| ◇ | Decision |
| → | Alur |

---

## Role yang Digunakan

| Role | Keterangan |
|------|------------|
| PPC | Production Planning & Control — penyedia data |
| Operator | Pelaksana produksi di lantai workshop |
| PIC | Penanggung jawab hambatan jalur |
| Leader | Penanggung jawab line + submit shift |
| Supervisor | Monitoring, reporting, QCheck, break time |
| Manager | Dashboard KPI, performance review |
| System | Proses otomatis (timeline, cut-off, sync, notifikasi) |

---

## Role yang TIDAK Digunakan dalam Flowchart

| Role | Alasan |
|------|--------|
| Foreman | Hak akses sama dengan Supervisor, tidak ada aktivitas khusus |
| Admin Produksi | Tidak ada aktivitas operasional pada implementasi sistem |
| Quality | Akses ke modul QCheck, tidak ada aktivitas terpisah dari Supervisor |

---

## Fitur yang TIDAK Dimasukkan ke Flowchart

| Fitur | Alasan |
|-------|--------|
| Handwork | Belum digunakan pada implementasi saat ini |
| Defect Monitoring | Belum digunakan sebagai bagian proses bisnis utama |
| Reject Analysis | Belum digunakan sebagai bagian proses bisnis utama |
| Tryout | Pengujian sistem, bukan bagian produksi |
| QCheck | Pemeriksaan kualitas melalui modul QC, dilakukan oleh Supervisor saat dibutuhkan |

---

## Catatan Penting

1. Seluruh flowchart mengikuti implementasi sistem yang sebenarnya
2. Flowchart menggunakan proses bisnis, bukan source code
3. Setiap flowchart memiliki hubungan dengan flowchart sebelumnya dan sesudahnya
4. Flowchart 9 dan 10 berjalan paralel dengan produksi
5. Flowchart 8 (Recovery) membuat loop kembali ke Flowchart 1
6. Flowchart 11, 12, 13 dibuat setelah shift selesai
7. First Check (1st Check) adalah bagian dari Dandori, bukan QCheck
8. Recovery memerlukan persetujuan PPC sebelum masuk jadwal
9. Repair/Reject memerlukan detail defect (area problem, root cause, countermeasure)
