# Black Box Testing — Ringkasan Hasil
# Aplikasi ASENTRA SPK

---

## Informasi Pengujian

| Item | Keterangan |
|------|-----------|
| Nama Aplikasi | ASENTRA SPK |
| Metode Pengujian | Black Box Testing |
| Tanggal Pengujian | 10–11 September 2026 |
| Environment | PHP 8.2+, MySQL/MariaDB, Windows, Chromium Browser |
| URL Aplikasi | http://127.0.0.1:8080 |

---

## Rekapitulasi Hasil

| Metrik | Jumlah |
|--------|--------|
| **Total Test Case** | **42** |
| **PASS** | **42** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |

### Persentase Keberhasilan Pengujian

```
Persentase = (Jumlah PASS / Total Test Case) × 100%
           = (42 / 42) × 100%
           = 100%
```

---

## Distribusi per Kategori

| No | Kategori | Jumlah TC | PASS | FAIL | BLOCKED |
|----|----------|-----------|------|------|---------|
| 1 | Authentication | 7 | 7 | 0 | 0 |
| 2 | Authorization / Role Access | 5 | 5 | 0 | 0 |
| 3 | Admin — Data Teknisi | 8 | 8 | 0 | 0 |
| 4 | Admin — Kriteria & Bobot | 3 | 3 | 0 | 0 |
| 5 | Admin — Monitoring Penilaian | 2 | 2 | 0 | 0 |
| 6 | Owner — Data Teknisi | 1 | 1 | 0 | 0 |
| 7 | Owner — Input Penilaian | 7 | 7 | 0 | 0 |
| 8 | Owner — Ranking & SAW | 3 | 3 | 0 | 0 |
| 9 | Owner — Riwayat | 1 | 1 | 0 | 0 |
| 10 | Owner — Laporan | 3 | 3 | 0 | 0 |
| 11 | Validasi & Error Handling | 2 | 2 | 0 | 0 |
| | **Total** | **42** | **42** | **0** | **0** |

---

## Fungsi Utama yang Diuji

1. **Autentikasi** — Login (valid, invalid, empty), Logout, Session management
2. **Otorisasi** — Pembatasan akses Admin/Owner, proteksi halaman, halaman 403
3. **CRUD Data Teknisi** — Create, Read, Update, Toggle Status, Delete, Search
4. **Kriteria & Bobot** — Tampilkan, Update valid, Validasi total 100%
5. **Monitoring Penilaian Admin** — Tampilkan read-only, Riwayat
6. **Input Penilaian Owner** — Form input, Simpan valid, Duplikat dicegah, Edit, Detail
7. **Proses SAW & Ranking** — Tampilkan ranking, Detail SAW, Riwayat ranking
8. **Laporan Cetak** — Periode valid, Periode tanpa data
9. **Error Handling** — 404, 403, Validasi input, Pesan informatif

---

## Bug yang Ditemukan

**Tidak ditemukan bug** selama pelaksanaan pengujian.

---

## Kesimpulan

Aplikasi ASENTRA SPK telah diuji menggunakan metode Black Box Testing dengan total **42 test case** yang mencakup seluruh modul fungsional (Authentication, Authorization, Admin, Owner, Penilaian, SAW, Ranking, Laporan, dan Error Handling).

Seluruh test case menghasilkan status **PASS** dengan persentase keberhasilan **100%**.

Hasil pengujian menunjukkan bahwa seluruh fungsi aplikasi beroperasi sesuai dengan spesifikasi kebutuhan fungsional yang telah ditetapkan.

---

*Dokumen ini merupakan bagian dari hasil pengujian untuk BAB IV Skripsi.*
