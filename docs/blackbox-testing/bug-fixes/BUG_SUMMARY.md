# Bug Summary — Black Box Testing ASENTRA SPK

---

## Informasi Pengujian

| Item | Keterangan |
|------|-----------|
| Tanggal Pengujian | 10–11 September 2026 |
| Total Test Case | 42 |
| Total PASS | 42 |
| Total FAIL | 0 |

---

## Ringkasan Bug

**Tidak ditemukan bug atau kegagalan fungsi** selama pelaksanaan Black Box Testing terhadap aplikasi ASENTRA SPK.

Seluruh 42 test case yang dijalankan menghasilkan status **PASS**, mencakup kategori:

1. Authentication (7 test case)
2. Authorization / Role Access (5 test case)
3. Admin — Data Teknisi (8 test case)
4. Admin — Kriteria & Bobot (3 test case)
5. Admin — Monitoring Penilaian (2 test case)
6. Owner — Data Teknisi (1 test case)
7. Owner — Input Penilaian (7 test case)
8. Owner — Ranking & SAW (3 test case)
9. Owner — Riwayat (1 test case)
10. Owner — Laporan (3 test case)
11. Validasi & Error Handling (2 test case)

---

## Catatan

- Tahap perbaikan (*bug fix*) dan pengujian ulang (*retest*) tidak diperlukan karena tidak ada test case yang berstatus FAIL.
- Seluruh validasi input (field kosong, data duplikat, kode duplikat, bobot tidak valid, nilai di luar range) telah diuji dan menghasilkan pesan error yang informatif.
- Sistem penanganan error (404, 403) berfungsi dengan baik tanpa memaparkan informasi teknis internal.

---

*Dokumen ini merupakan bagian dari dokumentasi pengujian Black Box Testing ASENTRA SPK.*
