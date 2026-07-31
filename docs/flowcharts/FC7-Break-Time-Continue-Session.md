# Flowchart 7: Break Time & Continue Session

## Tujuan
Mengelola waktu break selama produksi berlangsung.

## Input
- Produksi dari Flowchart 4

## Output
- Produksi berlanjut

## Aktor
- System
- Operator

## Flow

```
○ START

↓

□ Break Mulai (System)

↓

□ Break Selesai (System)

↓

□ Lanjutkan Produksi (Operator)

↓

○ END
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 4 (Proses Produksi)
- **Output** menjadi **Input** Flowchart 4 (Lanjutkan Produksi)

## Catatan
- Break time ditentukan oleh parameter waktu yang sudah diatur
- Selama break, produksi berhenti sementara
- Setelah break selesai, produksi dilanjutkan
- Break time mempengaruhi perhitungan jam kerja dan OEE
