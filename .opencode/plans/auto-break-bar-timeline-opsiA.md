# Opsi A — Perbaikan Anchor Timeline Auto Break (bar produksi hilang)

Ditetapkan 2026-10-11. Audit selesai; akar masalah terbukti (RC1/RC2). Implementasi Opsi A minimal.

## Lingkup
Frontend + blade saja. TIDAK menyentuh: lifecycle timer, resumeJob, DB/migration, logika bisnis lain.
Tidak ada deployment sampai seluruh pemeriksaan lulus.

## Perubahan File

### 1. resources/views/operational/input_harian.blade.php
Tambahkan field `prod_start` (JobMaster.started_at — immutable anchor produksi) di BOTH blok `jobMasterData`:

Blok per-job, sisipkan setelah blok `started_at:` (di atas `finished_at:`):
```
prod_start: {{ $jd->started_at ? \Carbon\Carbon::parse($jd->started_at)->timestamp * 1000 : 'null' }},
```

Blok active-job, sisipkan setelah blok `started_at:` (di atas `finished_at:`):
```
prod_start: {{ $activeJob->started_at ? \Carbon\Carbon::parse($activeJob->started_at)->timestamp * 1000 : 'null' }},
```

Catatan: `started_at` (session) tetap dipertahankan untuk timer; `prod_start` dipakai murni untuk anchor render timeline.

### 2. resources/js/operational/production-engine.js

a) Init — capture anchor stabil (dekat `syncRunningFromHistory`, sebelum `updateTimeline(true)`):
```js
for (const jobId in window.jobMasterData) {
    const job = window.jobMasterData[jobId];
    if (job && (job.prod_start || job.started_at)) {
        job._prodStart = job.prod_start || job.started_at;
    }
}
```

b) updateTimeline anchorTime (baris ~654-658) — masukkan `_prodStart` ke min:
```js
const anchorTime = Math.min(
    job._prodStart ? new Date(job._prodStart).getTime() : Infinity,
    jS ? jS.getTime() : Infinity,
    firstDandori ? firstDandori.getTime() : Infinity,
    ...historyStarts
) || pS_time;
```

c) updateTimeline activeStart (baris ~667-672) — masukkan `_prodStart` ke min:
```js
if (jS || firstDandori || job._prodStart) {
    activeStart = Math.min(
        job._prodStart ? new Date(job._prodStart).getTime() : Infinity,
        jS ? jS.getTime() : Infinity,
        firstDandori ? firstDandori.getTime() : Infinity
    );
}
```

d) renderSegmentedTimeline actualStartMs (baris ~1143) — anchor stabil menang atas started_at yang bergeser:
```js
const stableAnchorMs = job._prodStart ? new Date(job._prodStart).getTime() : null;
const actualStartMs = stableAnchorMs || (jS ? (jS instanceof Date ? jS.getTime() : new Date(jS).getTime()) : null);
```

e) renderSegmentedTimeline firstProdHistory (baris ~1154) — exclude 'break time'/'break' agar deadline tidak ber-anchor di jam break:
```js
const firstProdHistory = normalizedHistory.find(h => h.type !== 'dandori' && h.type !== 'setup' && h.type !== 'break time' && h.type !== 'break')?.start;
```

f) renderSegmentedTimeline effectiveProductionStart (baris ~1156) — utamakan anchor produksi asli sebelum history:
```js
let effectiveProductionStart = actualStartMs || firstProdHistory || firstAnyHistory || effectiveActualStart;
```

g) updateTimers marker anchor (baris ~187-191) — konsistensi posisi marker dengan anchor stabil:
```js
const anchorTime = Math.min(
    job._prodStart ? new Date(job._prodStart).getTime() : Infinity,
    jS ? jS.getTime() : Infinity,
    firstDandori ? firstDandori.getTime() : Infinity,
    ...historyStarts
) || pS_time;
```

## Verifikasi
- Baseline (sebelum): Unit 9 lulus; Feature target 15 lulus / 19 gagal (pra-ada: fixture line_code NOT NULL, spec backend active-job/resume yang belum diimplementasi, BreakTimelineValidator/EndShift). Bukan regresi dari perubahan ini.
- Setelah: jalankan test yang sama + unit regresi baru => jumlah gagal IDENTIK / lebih baik; tidak ada failure baru.
- Test regresi baru (server-invariant yang mendukung anchor): pauseJob+resumeJob tidak mengubah JobMaster.started_at/finished_at; finishDowntime menutup dan akumulasi duration_seconds; resumeJob tidak mengubah downtime terbuka (perilaku saat ini).
- Verifikasi manual-scenario (analisis + UAT): break aktif+refresh, break selesai+refresh, multi-line, integritas segmen sebelum/sesudah break, overtime konsisten.
- TANPA deploy.