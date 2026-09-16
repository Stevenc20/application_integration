# Flowchart 8: Recovery

## Tujuan
Memproses item yang tidak tercapai targetnya, mulai dari cut-off hingga timeline baru di-generate melalui persetujuan PPC.

## Input
- Item tidak tercapai dari Flowchart 4

## Output
- Timeline baru (Production Schedule di-update)

## Aktor
- System
- PPC

## Flow

```
○ START

↓

◇ Target Tercapai?

├── Ya → ○ END (Tidak perlu recovery)

└── Tidak

↓

□ Buat Recovery Item (System)

  (Status: waiting_approval)

↓

□ Notifikasi ke PPC (System)

↓

□ Review Recovery Queue (PPC)

↓

◇ Recovery Disetujui?

├── Tidak

│   ↓

│   □ Reject Recovery (PPC)

│   ↓

│   ○ END

│

└── Ya

↓

□ Approve Recovery (PPC)

  (Status: approved → scheduled)

↓

□ Masuk ke Jadwal Produksi Berikutnya

↓

□ Regenerate Timeline (System)

↓

□ Update Production Schedule (System)

↓

○ END → [Kembali ke Flowchart 1: Schedule di-update]
```

## Hubungan dengan Flowchart Lain
- **Input** berasal dari Flowchart 4 (Target Tidak Tercapai saat Finish Job)
- **Output** menjadi **Input** Flowchart 1 (Production Plan di-update dengan recovery items)

## Catatan
- Recovery hanya dibuat ketika actual OK < plan (target tidak tercapai)
- Recovery item status awal: `waiting_approval`
- PPC harus menyetujui sebelum item masuk ke jadwal produksi berikutnya
- Jika ditolak, item tidak masuk ke jadwal
- Ini adalah loop tertutup: recovery → approve → regenerate → schedule baru
- Notifikasi otomatis dikirim ke PPC/Admin saat recovery item dibuat
