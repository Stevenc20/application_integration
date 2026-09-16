# Bug List & Technical Debt

| ID | Issue | Module | Status | Severity | Notes |
|---|---|---|---|---|---|
| BUG-001 | Press C Cingkorak Overlap | Timeline | Under Investigation | High | Membutuhkan UAT Log untuk validasi apakah iterator foreach kehilangan track saat break kedua. |
| DB-001 | Orphan Recovery Items | Database | Unverified | Medium | Harus dicek apakah ada RecoveryItem yang production_plan_id nya sudah terhapus di masa lalu. |
| DB-002 | Duplicate Recovery | Database | Unverified | Medium | Harus dicek apakah upload ulang memicu pembuatan Recovery yang sama berkali-kali. |
