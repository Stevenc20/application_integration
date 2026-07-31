# Flowchart 5: Pencatatan Hasil Produksi

## Tujuan
Mencatat hasil produksi dalam bentuk OK, Repair, dan Reject selama produksi berlangsung.

## Input
- Job dari Flowchart 4

## Output
- Data hasil produksi tersimpan

## Aktor
- Operator
- System

## Flow

```
○ START

↓

◇ Ada Hasil Produksi?

├── OK → □ Input Hasil OK (Operator)

├── Repair

│   ↓

│   □ Input Qty Repair (Operator)

│   ↓

│   □ Catat Detail Defect (Operator)

│     (Defect Name, Qty A/B, Area Problem,

│      Root Cause, Countermeasure, Gambar)

│   ↓

│   □ Simpan RepairRejectLog (System)

│

├── Reject

│   ↓

│   □ Input Qty Reject (Operator)

│   ↓

│   □ Catat Detail Defect (Operator)

│     (Defect Name, Qty A/B, Area Problem,

│      Root Cause, Countermeasure, Gambar)

│   ↓

│   □ Simpan RepairRejectLog (System)

│

↓

□ Simpan Data Produksi (System)

  (Delta-based: OK/Repair/Reject ditambahkan

   ke DailyProduction)

↓

□ Sync ke ProductionPlan (System)

↓

□ Signal Dashboard Real-Time (System)

↓

○ END
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 4 (Proses Produksi)
- **Output** menjadi bagian dari data yang diverifikasi di Flowchart 4 (Submit Shift)

## Catatan
- Flowchart ini berjalan selama produksi berlangsung
- Input OK dilakukan langsung tanpa detail defect
- Input Repair/Reject memerlukan detail defect: nama defect, area problem, root cause, countermeasure
- Repair/Reject bisa disertai gambar (upload foto defect)
- Data tersimpan di 2 tabel: `daily_productions` (summary) dan `repair_reject_logs` (detail)
- Simpan menggunakan delta-based: qty ditambahkan ke nilai yang sudah ada, bukan di-overwrite
- Maksimal 5 production log per job per hari (log lama otomatis dihapus)
- Data ini akan diverifikasi saat submit shift di Flowchart 4
