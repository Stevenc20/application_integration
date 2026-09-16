# Flowchart 6: Downtime & Hambatan Jalur

## Tujuan
Mencatat downtime saat terjadi gangguan produksi, termasuk approval PIC dan Leader jika ada hambatan jalur.

## Input
- Produksi dari Flowchart 4

## Output
- Produksi dilanjutkan

## Aktor
- Operator
- PIC
- Leader
- System

## Flow

```
○ START

↓

□ Catat Downtime (Operator)

↓

◇ Hambatan Jalur?

├── Ya

│   ↓

│   □ Tanda Tangan PIC (PIC)

│   ↓

│   □ Tanda Tangan Leader (Leader)

│   ↓

│

└── Tidak

↓

□ Selesai Downtime (Operator)

↓

□ Resume Produksi (System)

↓

○ END
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 4 (Proses Produksi)
- **Output** menjadi **Input** Flowchart 4 (Lanjutkan Produksi)

## Catatan
- Downtime terjadi selama produksi berlangsung
- Hambatan jalur memerlukan tanda tangan PIC dan Leader
- Setelah downtime selesai, produksi dilanjutkan
- Downtime dicatat untuk keperluan laporan downtime di Flowchart 12
