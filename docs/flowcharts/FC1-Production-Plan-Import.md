# Flowchart 1: Production Plan Import

## Tujuan
Mengimpor Production Plan dari PPC ke dalam sistem dan menghasilkan Production Schedule yang siap digunakan Departemen Produksi.

## Input
- File Excel Production Plan dari PPC

## Output
- Production Schedule (Timeline) siap digunakan

## Aktor
- PPC
- System

## Flow

```
○ START

↓

□ Upload Production Plan (PPC)

↓

□ Import & Parse Excel (System)

↓

◇ Data Valid?

├── Tidak

│   ↓

│   Tampilkan Error (System)

│   ↓

│   Minta Upload Ulang (PPC)

│   ↓

│   (Kembali ke Upload Production Plan)

│

└── Ya

↓

□ Validasi Data (System)

↓

□ Simpan Baseline Jadwal (System)

↓

□ Generate Timeline (System)

↓

◇ Timeline Ter-generate?

├── Tidak

│   ↓

│   Gagal, ulangi Generate (System)

│   ↓

│   (Kembali ke Generate Timeline)

│

└── Ya

↓

□ Publish Production Schedule (PPC)

↓

○ END
```

## Hubungan dengan Flowchart Lain
- **Output** menjadi **Input** Flowchart 2 (Input Harian)

## Catatan
- Flowchart ini berada di level PPC, bukan Departemen Produksi
- Production Plan yang di-upload berupa file Excel dari PPC
- Production Schedule yang di-publish akan terlihat oleh Operator di Flowchart 2
- Validasi data memastikan format Excel sesuai dengan format yang diharapkan sistem
