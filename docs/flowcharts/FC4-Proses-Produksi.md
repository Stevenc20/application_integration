# Flowchart 4: Proses Produksi

## Tujuan
Melakukan produksi dari awal hingga job selesai, termasuk submit shift.

## Input
- Job dari Flowchart 3

## Output
- Job selesai, data tersimpan

## Aktor
- Operator
- System

## Flow

```
○ START

↓

□ Mulai Produksi (Operator)

↓

◇ Job Selesai?

├── Tidak → Kembali ke Input Hasil Produksi

│   ↓

│   □ Input Hasil Produksi (Operator)

│   ↓

│   (Kembali ke cek Job Selesai)

│

└── Ya

↓

□ Selesai Job (Operator)

↓

◇ Target Tercapai?

├── Tidak

│   ↓

│   □ Recovery Item Dibuat (System)

│   ↓

│   □ Notifikasi ke PPC (System)

│   ↓

│   (Masuk ke Flowchart 8: Recovery)

│

└── Ya

↓

◇ Job Berikutnya Ada?

├── Ya

│   ↓

│   □ Auto-Start Job Berikutnya (System)

│   ↓

│   (Kembali ke Mulai Produksi)

│

└── Tidak

↓

◇ Semua Job Selesai?

├── Tidak

│   ↓

│   □ Pilih Job Berikutnya (Operator)

│   ↓

│   (Kembali ke Mulai Produksi)

│

└── Ya

↓

□ Validasi Submit Shift (System)

↓

◇ Submit Valid?

├── Tidak

│   ↓

│   Tampilkan Issues (DT/Repair/Reject Belum Lengkap)

│   ↓

│   (Kembali ke Input Hasil Produksi)

│

└── Ya

↓

□ Submit & Kunci Shift (Operator)

↓

○ END
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 3 (Dandori & First Check)
- **Output** menjadi **Input** Flowchart 8 (Recovery, jika target tidak tercapai)
- **Loop** kembali ke Flowchart 4 jika ada job berikutnya

## Catatan
- Flowchart ini mencakup loop produksi: mulai → selesai → job berikutnya
- Validasi submit shift memastikan semua data lengkap sebelum shift terkunci
- Jika target tidak tercapai, recovery item akan dibuat di Flowchart 8
- Submit shift dilakukan oleh operator di akhir shift
