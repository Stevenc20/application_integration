# ERD Recap – PPC Schedule & Production Flow
## Acuan Pembuatan PPT

---

## 1. Gambaran Umum Sistem

**Nama Sistem:** Application Integration – Modul Produksi
**Lokasi:** PT. IPPI (Stamping Manufacturing)
**Teknologi:** Laravel 12, MySQL, PlantUML / Mermaid

**Cakupan Database:** 25 tabel dalam 7 domain yang saling terintegrasi:

| No | Domain | Jumlah Tabel | Fungsi Utama |
|----|--------|:-:|--------------|
| 1 | PPC Schedule | 9 | Perencanaan & penjadwalan produksi |
| 2 | Breaktime | 2 | Pengaturan jam istirahat |
| 3 | Production Flow | 4 | Eksekusi produksi real-time |
| 4 | Downtime | 4 | Tracking hambatan & kerusakan mesin |
| 5 | Quality | 2 | Tracking defect (repair & reject) |
| 6 | Changeover | 4 | Tracking pergantian job (dandori) |
| | **Total** | **25** | |

---

## 2. Domain PPC Schedule (9 Tabel)

### 2.1 Master Data

| Tabel | Kolom Penting | Fungsi |
|-------|--------------|--------|
| **line_masters** | id, line_code (unique), line_name, shift (Pagi/Malam), capacity, machine_count, status (active/inactive/maintenance) | Mendefinisikan setiap line produksi, kapasitas, dan shift operasional |
| **job_masters** | id, job_number (unique), job_name, target_qty, status, line, plan_start, plan_end | Master data setiap job/part yang diproduksi |
| **job_processes** | id, job_master_id (FK), process_name, sequence_no, standard_minutes | Proses kerja yang harus dilalui dalam suatu job |
| **master_stampings** | id, job_no, part_no, part_name, ct_detik, dct, mct, tpt, customer | Data referensi stamping khusus untuk mesin press |

### 2.2 Jadwal Produksi

| Tabel | Kolom Penting | Fungsi |
|-------|--------------|--------|
| **production_plans** | id, line_master_id (FK), plan_date, shift_name, press_name, job_no, plan (target), ok, repair, reject, ct_detik, dct, mct, start_time, finish_time, act_start, act_finish, p1–p4 (status mesin), dt_menit, source_type (ppc/recovery) | **Jadwal inti** – 60+ kolom mencakup semua informasi produksi harian |
| **schedule_stampings** | id, upload_date, press_name, shift_name, row_type, job_no, plan, ok, repair, reject, ct_detik | Salinan jadwal stamping dari upload file Excel |
| **recovery_schedules** | id, plan_date, shift_name, status (waiting_approval/approved/rejected/scheduled), approved_by, approved_at | Header untuk penjadwalan ulang (recovery) dengan approval workflow |
| **recovery_items** | id, recovery_schedule_id (FK), production_plan_id (FK), job_no, plan_qty, ok, repair, reject, duration_minutes, status | Item detail dari recovery schedule |
| **schedule_revisions** | id, plan_date, shift_name, action, snapshot_before (JSON), snapshot_after (JSON), created_by | Riwayat perubahan jadwal (audit trail) |

---

## 3. Domain Breaktime (2 Tabel)

| Tabel | Kolom Penting | Fungsi |
|-------|--------------|--------|
| **master_break_times** | id, hari (senin–minggu/semua), shift, waktu_mulai, waktu_selesai, type (istirahat/cinkorak), label, is_active, sort_order | Master jadwal istirahat per hari & shift |
| **break_times** | id, nama_istirahat, shift, hari, waktu_mulai, waktu_selesai | Data break time (versi lama, masih digunakan) |

**Relasi:** Kedua tabel terhubung secara logis ke `production_plans` melalui pencocokan `hari + shift`.

---

## 4. Domain Production Flow (4 Tabel)

| Tabel | Kolom Penting | Fungsi |
|-------|--------------|--------|
| **production_sessions** | id, job_master_id (FK), work_date, start_time, pause_time, finish_time, total_seconds, status (idle/running/paused/finished) | **Sesi produksi real-time** – operator start/stop produksi |
| **daily_productions** | id, job_master_id (FK), work_date, line, shift, target_qty, actual_ok, actual_repair, actual_reject, runtime_seconds, downtime_seconds, efficiency, status (open/closed) | **Rekap hasil harian** – ok/repair/reject + efisiensi |
| **production_logs** | id, job_master_id, ok_qty, repair_qty, reject_qty | Log individual setiap kali ada penambahan hasil produksi |
| **production_targets** | id, target_date, job_id (FK), process_type, shift, target_qty | Target produksi yang ditetapkan per job |

---

## 5. Domain Downtime & Machine (4 Tabel)

| Tabel | Kolom Penting | Fungsi |
|-------|--------------|--------|
| **machines** | id, name, line | Master data mesin |
| **machine_logs** | id, machine_id (FK), status (running/downtime/maintenance), downtime_start, downtime_end | Log status mesin secara real-time |
| **downtimes** | id, job_master_id (FK), jenis_downtime (Produksi/Mesin/Dies/Logistic/Material/Try out), problem, penyebab, action, pic, start_time, finish_time, duration_seconds | **Tracking downtime** – penyebab, durasi, dan penanganan |
| **hambatan_jalur** | id, downtime_id (FK), line_name, mesin, job_no, jenis_hambatan, sub_jenis, problem, penyebab, penanggulangan, status (open/closed), signature_image, signed_by, leader_signature_image, leader_signed_by | **Hambatan jalur** – dokumentasi lengkap dengan tanda tangan digital leader & foreman |

---

## 6. Domain Quality (2 Tabel)

| Tabel | Kolom Penting | Fungsi |
|-------|--------------|--------|
| **repair_reject_logs** | id, job_master_id (FK), type (repair/reject), sketch_no, repair_category, defect_name, qty_a, qty_b, pcs_number, area_problem, root_cause, countermeasure, created_by | **Log defect** – kategorisasi repair vs reject lengkap dengan root cause |
| **repair_reject_images** | id, repair_reject_log_id (FK), image_path, image_type (before/after) | **Foto defect** – dokumentasi visual sebelum & sesudah perbaikan |

---

## 7. Domain Changeover / Dandori (4 Tabel)

| Tabel | Kolom Penting | Fungsi |
|-------|--------------|--------|
| **dandoris** | id, previous_job_id, next_job_id, line, shift, activity, jenis_dandori, start_time, finish_time, duration_minutes, work_date | **Changeover record** – waktu pergantian antar job |
| **dandori_sessions** | id, job_master_id, job_number, job_name, line, shift, status (waiting/running/done), start_time, finish_time, total_minutes | Sesi dandori per job |
| **dandori_groups** | id, session_id, group_name, sequence_no, status, start_time, finish_time, total_minutes | Grouping kegiatan dalam sesi dandori |
| **dandori_details** | id, group_id, activity_name, sequence_no, status, start_time, finish_time, duration_minutes, remarks | Detail kegiatan per group |

---

## 8. Alur Data Proses Produksi (Text)

**Tahap 1 – Perencanaan (PPC):**
1. PPC mengupload jadwal dari Excel ke `production_plans` atau `schedule_stampings`
2. Data master dicek: `line_masters` (line & shift), `job_masters` (job_number), `master_stampings` (ct, dct, mct)
3. `plan_date`, `shift_name`, `press_name`, dan `job_no` diisi sebagai identitas jadwal
4. Target qty diisi di kolom `plan`, breakdown mesin di `p1`–`p4`
5. Jika target tidak tercapai, dibuat `recovery_schedules` → `recovery_items` (butuh approval)

**Tahap 2 – Eksekusi (Operator):**
1. Operator memulai sesi → `production_sessions.status = running`
2. Istirahat → `production_sessions.status = paused`
   - Breaktime otomatis dihitung berdasarkan `master_break_times` (hari + shift)
3. Hasil produksi dicatat → `production_logs` (ok/repair/reject_qty)
4. End shift → `daily_productions` diisi rekap harian + `efficiency`
5. Sesi selesai → `production_sessions.status = finished`

**Tahap 3 – Handling Masalah:**
1. Jika mesin mati → `machine_logs.status = downtime`
2. Jika ada hambatan produksi → `downtimes` (jenis, problem, durasi)
   - Hambatan berat dicatat di `hambatan_jalur` + tanda tangan leader & foreman
3. Jika ada defect → `repair_reject_logs` + `repair_reject_images`
4. Jika ganti job → `dandoris` / `dandori_sessions` tracking waktu changeover

---

## 9. Key Performance Metrics (Text)

### 9.1 Plan vs Actual
- **Data:** `production_plans.plan` (target) vs `daily_productions.actual_ok` (realisasi)
- **Output:** Selisih target vs realisasi per line/shift/hari

### 9.2 Production Efficiency
- **Data:** `daily_productions.efficiency`
- **Rumus:** (actual_ok / target_qty) × 100%
- **Output:** Persentase efisiensi harian per job/line

### 9.3 Quality Rate
- **Repair Rate:** `daily_productions.actual_repair` / total produksi
- **Reject Rate:** `daily_productions.actual_reject` / total produksi
- **Output:** Kualitas produk per line/shift/bulan

### 9.4 Downtime Analysis
- **Data:** `downtimes.jenis_downtime`, `downtimes.duration_seconds`
- **Output:** Total downtime per kategori (Produksi/Mesin/Dies/Logistic/Material/Try out)

### 9.5 Changeover Time
- **Data:** `dandoris.duration_minutes`
- **Output:** Rata-rata waktu changeover antar job per line

### 9.6 Recovery Performance
- **Data:** `recovery_items.plan_qty` vs `recovery_items.actual_qty`
- **Output:** Efektivitas recovery schedule

### 9.7 Overall Equipment Effectiveness (OEE)
- **Availability:** (total runtime – downtime) / total runtime
- **Performance:** actual_ok / target_qty
- **Quality:** (actual_ok - repair - reject) / actual_ok
- **OEE:** Availability × Performance × Quality

---

## 10. Sumber Data per Laporan Manager

| Laporan | Sumber Data Utama |
|---------|------------------|
| Dashboard Harian | `production_plans` + `daily_productions` + `production_sessions` |
| Laporan Shift | `production_plans.shift_name` + `daily_productions` |
| Laporan Downtime | `downtimes` + `hambatan_jalur` |
| Laporan Defect | `repair_reject_logs` + `repair_reject_images` |
| Laporan Changeover | `dandoris` + `dandori_sessions` |
| Laporan Recovery | `recovery_schedules` + `recovery_items` |
| Laporan OEE | Gabungan seluruh domain |
| Audit Jadwal | `schedule_revisions` (JSON snapshot before/after) |

---

## 11. Catatan Teknis

- **Relasi utama** menghubungkan 25 tabel melalui foreign key `job_master_id` dan `line_master_id`
- **job_masters** adalah tabel sentral yang menghubungkan hampir semua domain
- **production_plans** adalah tabel terbesar dengan 60+ kolom
- Semua tabel memiliki `created_at` dan `updated_at` (timestamp otomatis)
- Tabel dengan soft delete: `line_masters`
- Audit trail perubahan jadwal menggunakan JSON snapshot di `schedule_revisions`

---

*Dokumen ini disusun sebagai acuan pembuatan PPT presentasi sistem Application Integration – Modul PPC Schedule & Production Flow PT. IPPI.*
