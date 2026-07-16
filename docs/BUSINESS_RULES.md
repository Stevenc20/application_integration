# Business Rules

Dokumen ini berisi keputusan resmi dari tim Operasional (PPC) terkait perilaku aplikasi penjadwalan. Semua aturan ini MUTLAK dan mendikte algoritma di balik aplikasi.

## BR-001: Prioritas Eksekusi Jadwal
Dalam satu shift, urutan eksekusi pekerjaan pada suatu Line/Mesin adalah:
1. **[TBD]** Recovery Job?
2. **[TBD]** Locked Job?
3. **[TBD]** Manual Job?
4. **[TBD]** Standard PPC Plan?
*(Keputusan menunggu meeting PPC)*

## BR-002: Penempatan Waktu Recovery
- Recovery selalu disisipkan pada jam **07:30** (Awal Shift Pagi) atau **19:30** (Awal Shift Malam).
- Jika jam tersebut sudah terisi oleh Locked Job, maka Recovery diletakkan **setelah** Locked Job selesai.
*(Keputusan menunggu meeting PPC)*

## BR-003: Recovery Overflow (Lintas Shift)
Jika durasi sebuah Recovery melebihi batas jam kerja shift bersangkutan (misal: Shift Pagi berakhir jam 15:30):
- **Opsi A:** Langsung dilanjutkan oleh Shift Malam.
- **Opsi B:** Ditahan dan dipending ke keesokan harinya.
*(Keputusan menunggu meeting PPC)*

## BR-004: Status Reject pada Recovery
Jika sebuah Recovery di-reject oleh SPV/Manager:
- **Opsi A:** Final (Tidak bisa di-approve lagi, tersimpan di History).
- **Opsi B:** Reusable (Kembali ke status Draft/Pending dan bisa direvisi qty-nya).
*(Keputusan menunggu meeting PPC)*

## BR-005: Masa Berlaku Recovery (Expired)
- Recovery yang tidak dieksekusi selama **[TBD]** hari akan otomatis dibatalkan oleh sistem.
*(Keputusan menunggu meeting PPC)*

## BR-006: Setup Time (DCT) pada Job yang Terpotong Istirahat (Split Session)
Jika sebuah job terpotong oleh jam istirahat (break) dan dilanjutkan setelah istirahat (Session B):
- **Opsi A:** Session B dilanjutkan tanpa tambahan waktu setup (DCT = 0) karena setup sudah dilakukan di awal (Session A).
- **Opsi B:** Session B ditambahkan waktu setup (DCT) ulang sesuai SOP pemanasan mesin setelah istirahat.
*(Keputusan menunggu pembuktian via Excel PPC asli)*
