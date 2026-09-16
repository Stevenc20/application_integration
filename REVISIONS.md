# REVISIONS — Application Integration

## Fix Sebelumnya (Done)

### 1. Image Upload (R&R, Profile)
- Path: `storage_path('app/public/')` → `public_path('uploads/')`
- Validasi: `image` → `file`
- `chmod(0644)` setelah setiap copy
- JS escaping pakai `@json()`
- Placeholder dibuat
- **File:** semua view terkait

### 2. Python Crash Fix
- `scripts/read_schedule_stamping.py:155` — tambah `last_job_master = None`
- **Root cause:** cell JOB MASTER kosong setelah inheritance chain putus

### 3. Flash Messages
- Tambah `session('success')`, `session('error')`, `$errors` display via `showToast()`
- **File:** `resources/views/ppc/planning/production_plan.blade.php`

### 4. findPython() Fix
- `return 'python'` → `return null`
- Guard `if (!$python)` sekarang work

### 5. Validation Fix (Missing fileinfo)
- `mimes:xlsx,xls,xlsm` → `extensions:xlsx,xls,xlsm`
- Bypass missing `fileinfo` extension di hosting

### 6. PhpSpreadsheet Fallback
- Installed via composer
- Import method: Python first → PHP fallback
- **File baru:** `app/Services/ExcelScheduleParser.php`

### 7. Form Action
- Hardcoded `/ppc/...` → `{{ route('ppc.planning.production_plan.import') }}`

---

# Revisions — 6 Item Asli

## 1. Jadwal PPC — Data Sesuai Excel (HIGH) ⚠️
### ✅ Done
- Data Press A sudah sesuai Excel (K-1041, K-1042, PVS-003B1, GT-6196, GT-6197, K4045/46, K4047/48)
- Rev + non-Rev sheet merged — non-Rev items jadi baseline, Rev nambahin item unik
- PT 03-Juli-2026 hosting: 30 items, urutan sesuai

### ❌ Masih — Breaks Dobel
ISTIRAHAT SIANG, CINGKORAK, BREAKTIME, ISTIRAHAT SORE muncul 2x karena import dari Rev + non-Rev sheet keduanya punya break di jam sama.
**Fix:** Dedup break rows di `ProductionPlanController` — cukup import break sekali per jam + deskripsi.

### ❌ Masih — Import device lain
Belum clear masalahnya — perlu investigasi lanjutan.

### Files
- `app/Http/Controllers/Ppc/ProductionPlanController.php`

---

## 2. Break → Pause Timer (HIGH) ❌
### Akar Masalah
Sekarang: item kepotong break di-split jadi 2 JobMaster → LKH & Input Harian tampil 2 baris. Merge sudah diimplementasi tapi tetap tidak ideal karena menambah kompleksitas.

### Fix
- **Hapus auto-split:** `TimelineGenerationService` — 1 item = 1 JobMaster utuh, jangan bikin JobMaster baru untuk Session B
- **Pause timer otomatis:** `production-engine.js` — detect jam break dari jadwal, pause timer + input produksi, resume otomatis setelah break selesai
- **Button break:** tetap ada untuk pause manual jika diperlukan
- **Rollback merge:** hapus kode merge children di `ReportController` & `InputHarianController` (Issue #2 sebelumnya)

### Files
- `app/Services/TimelineGenerationService.php`
- `resources/js/operational/production-engine.js`
- `app/Http/Controllers/Supervisor/ReportController.php`
- `app/Http/Controllers/Operational/InputHarianController.php`

---

## 3. End-Shift Submit + Validasi Lengkap (HIGH) ❌
### Sudah Ada
- Tombol "Akhiri Shift" di header Input Harian
- Validasi DT: problem/penyebab/action wajib diisi
- Migration & model `shift_submissions`
- Loading spinner, success toast, button disabled

### Kurang
1. **Validasi Repair & Reject:** problem/penyebab/action wajib diisi
2. **Validasi Remain Item:** jika ada JobMaster dengan status `running` / `pending` → tolak submit
3. **Detail alert:** daftar item bermasalah per kategori + form yang belum diisi
4. **Direction link:** link ke section masing-masing
5. **Gate:** baru bisa submit kalau SEMUA bersih

### Files
- `app/Http/Controllers/Operational/InputHarianController.php`
- `resources/views/operational/input_harian.blade.php`
- `database/migrations/2026_07_02_200740_create_shift_submissions_table.php`
- `app/Models/ShiftSubmission.php`

---

## 4. TOTAL_DT Dashboard Dandori (MEDIUM) ❌
### Akar Masalah
Card TOTAL_DT di dashboard masih kehitung **dandori + downtime**.
**Fix:** `DashboardRealtimeService`: filter `jenis_downtime != 'dandori'`

### Files
- `app/Services/DashboardRealtimeService.php`

---

## 5. +/- Button OK/Repair/Reject (LOW) ✅ DONE

---

## 6. Next Item Dropdown + Finalisasi (LOW) ✅ DONE

---

# Deployment Checklist
- [x] Rev #1a — Data PPC sudah sesuai Excel ✅
- [x] Rev #1b — Fix breaks dobel ✅
- [ ] Rev #1c — Investigasi import device lain
- [ ] Rev #2 — Break → Pause timer (hapus split)
- [ ] Rev #3 — End-shift validasi Repair/Reject/Remain
- [ ] Rev #4 — TOTAL_DT dashboard (filter dandori)
- [x] Rev #5 — +/- Button ✅
- [x] Rev #6 — Dropdown ✅
- [x] Build Vite: `npm run build` ✅
- [x] Test: `php vendor/bin/pest` → 42 passed, 7 failed (pre-existing)
- [ ] Run migration: `php artisan migrate` (hosting)
- [ ] Commit + push
- [ ] Deploy + test Input Harian, LKH, Dandori di hosting

---

# Revisi 3 & 4 (End-Of-Shift)

## Revisi 3 — Batalkan "Akhiri Shift" (1×) ✅ DONE
### Deskripsi
Leader bisa membatalkan submit "Akhiri Shift" satu kali per shift. Pembatalan:
- Menghapus RecoveryItems yang dibuat oleh cut-off saat submit (khusus press/line-nya saja)
- Membersihkan RecoverySchedule yang menjadi kosong
- Membuka kembali (unlock) shift → data bisa diedit lagi
- Menandai submission `cancelled_at`/`cancelled_by` dan menaikkan `cancel_count`
- Setelah itu leader bisa submit ulang (cut-off berjalan lagi, baris submission yang sama di-reactivate — `cancel_count` tidak di-reset sehingga pembatalan kedua ditolak)

### Aturan
- Cancel hanya boleh jika submission aktif (belum pernah di-cancel): `cancel_count + 1 = 1`
- Cancel kedua → `403 "Pembatalan hanya dapat dilakukan satu kali per shift."`
- Guard lock (`saveProductionLog`, `start/finish`, downtime, dll) sekarang mengabaikan submission yang sudah di-cancel → edit diperbolehkan lagi
- `submitShift` idempotent hanya untuk submission aktif; submission yang di-cancel boleh di-submit ulang

### Files
- `database/migrations/2026_09_16_093000_add_cancel_and_comment_to_shift_submissions.php` (baru)
- `app/Models/ShiftSubmission.php` (+ `comment`, `cancelled_at`, `cancelled_by`, `cancel_count`, relasi `line()`)
- `app/Http/Controllers/Operational/InputHarianController.php` (`cancelShift()`, `resolveSubmitContext()`, `submitShift()` resubmit path, lock checks `whereNull('cancelled_at')`)
- `routes/web.php` (`POST /operational/shift/{lineId}/cancel` → `operational.shift.cancel`)
- `resources/views/operational/input_harian.blade.php` (tombol "Batalkan" + `cancelShift()` JS)
- `tests/Feature/EndShiftCancelCommentTest.php` (baru)

## Revisi 4 — Komentar Leader (End-Of-Shift) ✅ DONE
### Deskripsi
- Leader bisa menulis **komentar opsional** saat klik "Akhiri Shift" (input di header Input Harian, maks 255 char)
- Komentar tersimpan di `shift_submissions.comment`
- **Foreman Dashboard** menampilkan komentar Akhir Shift terbaru (kartu "Catatan Akhir Shift") — route foreman & operator dashboard memakai `OperatorDashboardController::index` → `operator.dashboard`
- **Input Harian shift berikutnya** menampilkan banner kuning "Catatan Shift Sebelumnya" berisi komentar dari shift sebelumnya untuk line yang sama (Pagi→Malam hari sama, Malam→Pagi hari berikutnya)
- Komentar dari submission yang di-cancel tidak ditampilkan (`whereNull('cancelled_at')`)

### Files
- Migration yang sama dgn Revisi 3 (kolom `comment`)
- `app/Http/Controllers/Operational/InputHarianController.php` (query `prevShiftComment` di `index()`)
- `app/Http/Controllers/Operator/OperatorDashboardController.php` (query `shiftComments`)
- `resources/views/operational/input_harian.blade.php` (input komentar + banner)
- `resources/views/operator/dashboard.blade.php` (kartu "Catatan Akhir Shift")
- `tests/Feature/EndShiftCancelCommentTest.php` (baru)

## Test
- `php artisan test --filter="EndShiftIdempotencyTest|EndShiftCancelCommentTest|SignatureControllerTest|ForemanLkhEditTest"` → **58 passed (144 assertions)** ✅
