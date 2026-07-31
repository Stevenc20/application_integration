# Flowchart 9: Monitoring Produksi (Supervisor)

## Tujuan
Memantau produksi secara real-time selama shift berlangsung.

## Input
- Data produksi dari Flowchart 4

## Output
- Informasi Near Real-Time

## Aktor
- Supervisor
- System

## Flow

```
○ START (Parallel — berjalan selama produksi)

↓

□ Dashboard Real-Time (Supervisor)

↓

□ Status Line (Supervisor)

↓

□ Progress Produksi (Supervisor)

↓

□ Achievement (Supervisor)

↓

○ END (Berjalan terus selama produksi)
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 4 (Proses Produksi)
- **Output** berjalan paralel dengan Flowchart 4

## Catatan
- Flowchart ini berjalan **paralel** dengan produksi
- Supervisor dapat mengakses kapan saja selama shift berlangsung
- Dashboard menampilkan status line, progress, dan achievement
- Data di-update secara real-time menggunakan Server-Sent Events (SSE)
