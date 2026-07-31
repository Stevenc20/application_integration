# Flowchart 3: Dandori & First Check

## Tujuan
Melakukan persiapan mesin/dies (Dandori) dan pemeriksaan awal (First Check) sebelum produksi dimulai.

## Input
- Job dari Flowchart 2

## Output
- Produksi siap dimulai di Flowchart 4

## Aktor
- Operator
- System

## Flow

```
○ START

↓

□ Mulai Dandori (Operator)

↓

□ Selesai Dandori (Operator)

↓

◇ First Check Diperlukan?

├── Ya

│   ↓

│   □ Mulai 1st Check (Operator)

│   ↓

│   □ Selesai 1st Check (Operator)

│

└── Tidak

↓

○ END
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 2 (Pilih Job)
- **Output** menjadi **Input** Flowchart 4 (Proses Produksi)

## Catatan
- Dandori adalah persiapan mesin/dies sebelum produksi
- First Check (1st Check) adalah pemeriksaan awal kualitas produk sebelum produksi massal
- First Check merupakan bagian dari proses Dandori, bukan aktivitas QC terpisah
- QCheck (modul QC) adalah aktivitas terpisah yang dilakukan selama/produksi oleh Supervisor
