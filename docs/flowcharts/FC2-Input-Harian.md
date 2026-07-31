# Flowchart 2: Input Harian

## Tujuan
Operator membuka halaman Input Harian, melakukan sinkronisasi data, dan memilih Job dari Production Schedule.

## Input
- Production Schedule dari Flowchart 1

## Output
- Job siap diproses di Flowchart 3

## Aktor
- Operator
- System

## Flow

```
○ START

↓

□ Buka Input Harian (Operator)

↓

□ Sync Data ke Job Master (System)

↓

◇ Shift Terkunci?

├── Ya

│   ↓

│   Tampilkan Read-Only View (Historical)

│   ↓

│   ○ END

│

└── Tidak

↓

□ Pilih Job dari Jadwal (Operator)

↓

○ END
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 1 (Production Schedule)
- **Output** menjadi **Input** Flowchart 3 (Dandori & First Check)

## Catatan
- Jika shift sudah terkunci, operator hanya bisa melihat data historis (read-only)
- Sync Data memastikan data Job terbaru dari PPC tersedia di halaman Input Harian
- Pemilihan Job menentukan job mana yang akan diproses di Flowchart 3
