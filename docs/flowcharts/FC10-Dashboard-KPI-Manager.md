# Flowchart 10: Dashboard KPI (Manager)

## Tujuan
Memantau KPI produksi seperti GSPH, OEE, dan Achievement.

## Input
- Data produksi dari Flowchart 4

## Output
- Informasi KPI

## Aktor
- Manager
- System

## Flow

```
○ START (Parallel — berjalan selama produksi)

↓

□ Lihat Dashboard KPI (Manager)

↓

□ GSPH (System)

↓

□ OEE (System)

↓

□ Achievement (System)

↓

○ END (Berjalan terus selama produksi)
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 4 (Proses Produksi)
- **Output** berjalan paralel dengan Flowchart 4

## Catatan
- Flowchart ini berjalan **paralel** dengan produksi
- Manager dapat mengakses kapan saja selama shift berlangsung
- Dashboard KPI menampilkan metrik kinerja produksi
- GSPH (Gross Standard Per Hour), OEE (Overall Equipment Effectiveness), Achievement
