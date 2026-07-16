# UAT Checklist — Application Integration

## Hari 1 — Upload, Produksi, Cut Off

### A. Upload Excel
- [ ] Upload Shift Pagi semua press (A, B, C, D)
- [ ] Semua item terimport
- [ ] Tidak ada duplicate Production Plan
- [ ] Timeline berhasil dibuat
- [ ] Breaktime sesuai Master Breaktime
- [ ] Tidak ada overlap antar item

### B. Input Harian
- [ ] Beberapa item selesai (ok = plan)
- [ ] Beberapa item belum selesai (ok < plan)
- [ ] Beberapa item reject
- [ ] Beberapa item repair
- [ ] LKH membaca data benar
- [ ] Achievement sesuai
- [ ] Balance sesuai

### C. Cut Off Shift Pagi
- [ ] Item belum selesai → Recovery dibuat
- [ ] Recovery Qty = Plan - Actual
- [ ] Recovery Queue terisi
- [ ] Status = WAITING_APPROVAL

### D. Recovery Queue
- [ ] Recovery tidak masuk Production Plan
- [ ] Recovery hanya di Recovery Queue
- [ ] History masih kosong

## Hari 2 — Approval & Scheduler

### A. Partial Approval
- [ ] Approve Item A & C saja
- [ ] Item A → APPROVED
- [ ] Item C → APPROVED
- [ ] Item B tetap WAITING_APPROVAL
- [ ] Item D tetap WAITING_APPROVAL
- [ ] Tidak ada item lain yang berubah

### B. Upload Excel Hari Kedua
- [ ] Production Plan PPC berhasil diimport
- [ ] Recovery Queue tidak hilang
- [ ] Recovery History tidak berubah
- [ ] Production Actual hari sebelumnya tetap utuh

### C. Generate Scheduler
- [ ] Recovery prioritas pertama
- [ ] PPC setelah Recovery
- [ ] Tidak ada overlap
- [ ] Tidak melewati Breaktime
- [ ] Tidak melewati Cut Off

### D. Capacity Validation
- [ ] Recovery tanpa slot tetap APPROVED
- [ ] PPC tanpa slot tetap Pending (tidak jadi Recovery baru)

### E. Recovery Lock
- [ ] Input OK > 0 → status IN_PRODUCTION
- [ ] Regenerate Timeline → item tidak berubah
- [ ] Upload ulang Excel → item tidak hilang

## Hari 3 — Continuous Production

### A. Upload Jadwal Hari Ketiga
- [ ] Recovery APPROVED ikut Scheduler
- [ ] Recovery IN_PRODUCTION tidak ikut Generate
- [ ] Recovery COMPLETED hanya di History

### B. Produksi Recovery
- [ ] Selesaikan semua Recovery
- [ ] Status → COMPLETED
- [ ] Recovery pindah ke History

### C. Cut Off Kedua
- [ ] Recovery baru terbuat jika masih ada Balance
- [ ] Recovery Completed tidak muncul kembali

## Multi Press Validation
- [ ] Timeline Press A independen
- [ ] Timeline Press B independen
- [ ] Timeline Press C independen
- [ ] Timeline Press D independen
- [ ] Recovery masing-masing independen
- [ ] Scheduler tidak saling mempengaruhi

## Upload Regression Test
- [ ] Upload tanggal yang sama
- [ ] Upload shift pagi saja
- [ ] Upload shift malam saja
- [ ] Upload semua shift
- [ ] Upload ketika Recovery masih ada
- [ ] Upload ketika Recovery IN_PRODUCTION
- [ ] Upload ketika terdapat Pending PPC

## Breaktime Validation
- [ ] Senin — Istirahat Siang 12:00-12:45
- [ ] Selasa — Istirahat Siang 12:00-12:45
- [ ] Rabu — Istirahat Siang 12:00-12:45
- [ ] Kamis — Istirahat Siang 12:00-12:45
- [ ] Jumat — Istirahat Jumat 11:45-12:45
- [ ] Cingkorak 15:15-15:30
- [ ] Breaktime 16:30-16:45
- [ ] Istirahat Sore 18:00-18:30

## Scheduler Validation
- [ ] Tidak ada overlap
- [ ] Tidak ada Finish > Cut Off
- [ ] Tidak ada Start < Finish sebelumnya
- [ ] Tidak ada duplicate Timeline
- [ ] Tidak ada duplicate Recovery
- [ ] Timeline selalu dimulai dari awal Shift

## Data Reconciliation (per Press)
- [ ] Plan Qty = Completed Qty + Recovery Qty + Pending PPC Qty
- [ ] Tidak ada selisih quantity

## Performance Test
- [ ] Press A: ±170 item — generate berhasil
- [ ] Press B: ±190 item — generate berhasil
- [ ] Press C: ±160 item — generate berhasil
- [ ] Press D: ±220 item — generate berhasil
- [ ] Tidak timeout
- [ ] Memory stabil
- [ ] Timeline tetap valid

---

## Acceptance
- [ ] Semua skenario di atas berhasil
- [ ] Tidak ada kehilangan quantity
- [ ] Tidak ada Recovery hilang
- [ ] Tidak ada Timeline overlap
- [ ] Tidak ada Recovery duplicate
- [ ] Tidak ada Production Plan duplicate
- [ ] Lifecycle Recovery lengkap: WAITING_APPROVAL → APPROVED → SCHEDULED → IN_PRODUCTION → COMPLETED
- [ ] Upload Excel aman di seluruh kondisi
- [ ] Scheduler konsisten di seluruh Press
