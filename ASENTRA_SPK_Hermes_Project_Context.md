# ASENTRA SPK — PROJECT CONTEXT FOR HERMES

## 1. Identitas Project

Nama sistem: ASENTRA SPK  
Perusahaan: CV Arsitek Semesta Nusantara (ASENTRA), Cirebon  
Tujuan: Sistem Pendukung Keputusan (SPK) untuk penilaian kinerja teknisi lapangan berbasis web menggunakan metode Simple Additive Weighting (SAW).

Konteks skripsi:
Implementasi Algoritma Simple Additive Weighting (SAW) untuk Penilaian Kinerja Teknisi Lapangan pada CV Arsitek Semesta Nusantara Berbasis Web.

Tujuan sistem:
- mengelola data teknisi;
- mengelola kriteria dan bobot;
- mengelola periode penilaian;
- mengimpor data operasional melalui Excel;
- mengolah data operasional menjadi indikator;
- menghasilkan C1, C2, C3;
- menyediakan tabulasi, detail, dan grafik untuk Owner;
- melakukan review dan konfirmasi;
- menghitung SAW;
- menghasilkan nilai preferensi dan ranking;
- menyediakan riwayat dan laporan.

---

## 2. Stack

Jangan migrasikan project ke framework baru.

Gunakan:
- PHP 8.2+
- custom lightweight PHP MVC
- MySQL/MariaDB
- XAMPP
- vanilla HTML/CSS/JavaScript
- Chart.js 4.4.1
- PhpSpreadsheet 2.4.7

Local server:

```bash
C:/xampp/php/php.exe -S 127.0.0.1:8080 -t public public/index.php
```

Jangan mengganti ke React, Vue, Laravel, atau framework baru.

---

## 3. Konsep Bisnis Utama

Alur utama V2:

```text
ADMIN
  ↓
Buat periode
  ↓
Upload Excel data operasional
  ↓
Validasi import
  ↓
Calculation Engine
  ↓
C1 / C2 / C3
  ↓
OWNER REVIEW
  ↓
Tabulasi + grafik + detail indikator
  ↓
Owner Confirmation
  ↓
SAW V2
  ↓
Normalisasi
  ↓
Pembobotan
  ↓
Nilai Preferensi Vi
  ↓
Ranking
  ↓
Laporan
```

Penting:
SAW bukan tahap awal. SAW adalah tahap keputusan setelah C1, C2, C3 tersedia dan dikonfirmasi.

---

## 4. Role

### ADMIN

Admin menyiapkan data sistem.

Tugas:
- login;
- CRUD teknisi;
- mengelola kriteria dan bobot;
- membuat periode;
- upload Excel;
- melihat hasil import;
- menjalankan kalkulasi indikator;
- monitoring data;
- melihat hasil/ranking/laporan sesuai authorization.

Admin = pihak yang menyiapkan dan mengolah data operasional.

### OWNER

Owner melakukan review dan konfirmasi.

Tugas:
- login;
- memilih periode;
- melihat hasil C1/C2/C3;
- melihat tabulasi;
- melihat grafik;
- melihat detail indikator;
- review hasil;
- mengisi/menyesuaikan bagian C3 yang memang membutuhkan rating Owner;
- konfirmasi penilaian;
- melihat hasil SAW;
- melihat ranking;
- melihat riwayat;
- melihat laporan.

Owner bukan pihak yang memasukkan seluruh data operasional.

### TEKNISI

Teknisi adalah objek yang dinilai, bukan user utama aplikasi.

Dataset utama:
- A1 Toni
- A2 Apip
- A3 Agus Supriyanto
- A4 Rahmat Hidayat
- A5 Ahmad Sahudin
- A6 Aris
- A7 IMADE
- A8 Asep
- A9 Wanto
- A10 Heri

Jangan mengganti nama dataset utama tanpa instruksi.

---

## 5. Kriteria SAW

Tetap 3 kriteria:

| Kode | Kriteria | Bobot | Jenis |
|---|---|---:|---|
| C1 | Kedisiplinan | 0.30 | Benefit |
| C2 | Kualitas Hasil Kerja | 0.40 | Benefit |
| C3 | Tanggung Jawab | 0.30 | Benefit |

Total:

```text
0.30 + 0.40 + 0.30 = 1.00
```

Jangan mengubah bobot tanpa instruksi eksplisit.

---

## 6. Subkriteria / Indikator

Subkriteria membantu membentuk C1/C2/C3.

Jangan menjadikan seluruh subkriteria sebagai kriteria SAW tambahan.

Struktur:

```text
C1 Kedisiplinan
├── C1.1 Kehadiran
├── C1.2 Ketepatan Waktu
└── C1.3 Kepatuhan terhadap Jadwal

C2 Kualitas Hasil Kerja
├── C2.1 Kerapian
├── C2.2 Ketepatan / Presisi
└── C2.3 Kesesuaian dengan Desain

C3 Tanggung Jawab
├── C3.1 Perawatan Alat
├── C3.2 Efisiensi Material
├── C3.3 Inisiatif
└── C3.4 Kepatuhan Prosedur
```

---

## 7. Aturan C1

### C1.1 Kehadiran

```text
Persentase = hadir / total_hari_kerja × 100%
```

Kategori:

```text
>=95%       → 4
85-<95%     → 3
75-<85%     → 2
<75%        → 1
```

Sakit dan izin tidak otomatis dipenalti. Alpa dapat disimpan sebagai warning/informasi, bukan otomatis dikurangi dari nilai.

### C1.2 Ketepatan Waktu

```text
0-2 kali    → 4
3-5 kali    → 3
6-8 kali    → 2
>=9 kali    → 1
```

### C1.3 Kepatuhan Jadwal

```text
persentase = sesuai_jadwal / pekerjaan_terjadwal × 100%
```

Kategori:

```text
>=95%       → 4
80-<95%     → 3
65-<80%     → 2
<65%        → 1
```

Nilai bulanan:

```text
C1_bulanan = (C1.1 + C1.2 + C1.3) / 3
```

Pertahankan decimal.

---

## 8. Aturan C2

Indikator:
- C2.1 Kerapian
- C2.2 Presisi
- C2.3 Kesesuaian dengan Desain

Setiap pekerjaan dinilai Ya/Tidak.

```text
persentase = jumlah memenuhi / total pekerjaan × 100%
```

Kategori:

```text
>=90%       → 4
75-<90%     → 3
60-<75%     → 2
<60%        → 1
```

Nilai bulanan:

```text
C2_bulanan = (C2.1 + C2.2 + C2.3) / 3
```

---

## 9. Aturan C3

Indikator:
- C3.1 Perawatan Alat
- C3.2 Efisiensi Material
- C3.3 Inisiatif
- C3.4 Kepatuhan Prosedur

C3 menggunakan rating 1-4.

Pedoman Perawatan Alat:

```text
0 kejadian    → 4
1 kejadian    → 3
2-3 kejadian  → 2
>=4 kejadian  → 1
```

Tetapi jangan mengarang jumlah kejadian jika database hanya menyimpan rating.

Nilai bulanan:

```text
C3_bulanan = (C3.1 + C3.2 + C3.3 + C3.4) / 4
```

---

## 10. Periode

Model utama adalah triwulan.

Contoh Q1:

```text
2026-Q1
2026-01-01
sampai
2026-03-31
```

Konvensi:

```text
Q1 = Januari-Maret
Q2 = April-Juni
Q3 = Juli-September
Q4 = Oktober-Desember
```

Satu Excel = satu periode triwulan.

Data tidak boleh keluar dari rentang periode.

Jika hanya tersedia 1 atau 2 bulan:
- status assessment dapat menjadi `partial`;
- warning dan jumlah bulan harus dipertahankan;
- jangan mengubah partial menjadi complete secara palsu.

---

## 11. Excel V2

Satu Excel harus memiliki 5 sheet:

```text
DATA_TEKNISI
KEDISIPLINAN
KUALITAS_KERJA
TANGGUNG_JAWAB
PETUNJUK
```

Excel berisi data operasional/raw data, bukan hanya C1/C2/C3.

Identifier mengikuti importer/master teknisi, misalnya:

```text
A1 | Toni
A2 | Apip
...
A10 | Heri
```

Jangan membuat format Excel yang bertentangan dengan ExcelValidator.

---

## 12. Database V2

Tabel penting:

```text
tb_user
tb_teknisi
tb_kriteria
tb_periode_penilaian
tb_import
tb_kedisiplinan
tb_pekerjaan
tb_tanggung_jawab
tb_penilaian
tb_hasil
```

Alur database:

```text
tb_kedisiplinan
tb_pekerjaan
tb_tanggung_jawab
        ↓
CalculationEngine
        ↓
tb_penilaian
        ↓
Owner confirmation
        ↓
SawEngineV2
        ↓
tb_hasil
```

Traceability:

```text
Ranking
 ↓
tb_hasil
 ↓
penilaian_id
 ↓
tb_penilaian
 ↓
C1/C2/C3
 ↓
indikator
 ↓
raw operational data
```

Jangan menghilangkan `penilaian_id` atau `id_periode` dari hasil V2.

---

## 13. Legacy / V1

Project memiliki V1/legacy yang harus tetap aman.

Legacy period yang sudah ada:
- LEGACY-2026-08
- LEGACY-2026-09

Kondisi legacy yang harus dipertahankan:
- 12 tb_penilaian
- 12 tb_hasil

Jangan:
- menghapus legacy;
- mengubah legacy;
- memproses legacy menggunakan SAW V2;
- mencampur legacy dengan V2;
- memasukkan legacy ke max normalisasi V2.

Legacy harus read-only dari workflow V2.

---

## 14. SAW V1

Golden Dataset 2026-08:

| Teknisi | Vi | Rank |
|---|---:|---:|
| Toni | 1.000 | 1 |
| Aris | 0.925 | 2 |
| Rahmat Hidayat | 0.900 | 3 |
| Apip | 0.850 | 4 |
| Wanto | 0.750 | 5 |
| Heri | 0.750 | 6 |
| IMADE | 0.700 | 7 |
| Ahmad Sahudin | 0.675 | 8 |
| Agus Supriyanto | 0.600 | 9 |
| Asep | 0.575 | 10 |

Wanto dan Heri tie. Tie-break menggunakan `teknisi_id ASC`.

Ranking sequential:

```text
1,2,3,4,5,6...
```

Jangan membuat ranking 5,5.

---

## 15. SAW V2

File utama:

```text
app/services/SawEngineV2.php
app/services/SawServiceV2.php
tests/saw_v2_test.php
```

Hanya `status_data = confirmed` yang boleh diproses.

Ditolak:
- draft
- calculated
- partial yang belum confirmed
- legacy

Partial yang sudah confirmed boleh diproses jika C1/C2/C3 valid.

Formula:

```text
rij = xij / max(xj)
```

Semua benefit.

`max(xj)` dihitung hanya dari teknisi dalam periode yang sedang diproses.

Preference:

```text
Vi = 0.30*rC1 + 0.40*rC2 + 0.30*rC3
```

Ranking:

```text
Vi DESC
teknisi_id ASC
```

Ranking sequential 1-based.

SAW V2 wajib:
- transaction;
- rollback saat gagal;
- period isolation;
- legacy protection;
- idempotent re-run;
- menyimpan `id_periode`;
- menyimpan `penilaian_id`;
- mencegah division by zero.

Jika `max <= 0`, hentikan proses dengan error jelas.

---

## 16. Workflow Owner V2

Route utama:

```text
/owner/assessment
```

Alur:

```text
Owner
 ↓
Pilih periode
 ↓
Review summary
 ↓
Lihat tabulasi
 ↓
Lihat grafik
 ↓
Pilih teknisi
 ↓
Lihat detail indikator
 ↓
Review C1/C2/C3
 ↓
Confirm
 ↓
SAW
 ↓
Ranking
```

Owner tidak seharusnya memasukkan C1/C2 secara manual jika nilai sudah dihitung dari data operasional.

---

## 17. Tabulasi V2

Owner harus dapat melihat data pendukung seperti:

```text
Teknisi
Kehadiran
Terlambat
Kepatuhan Jadwal
Kerapian
Presisi
Sesuai Desain
Perawatan Alat
Efisiensi Material
Inisiatif
Kepatuhan Prosedur
C1
C2
C3
```

Tujuan tabulasi:

Owner dapat memahami asal nilai C1/C2/C3.

Contoh:

```text
Kehadiran = 21/22 = 95.45%
Kepatuhan Jadwal = 21/22 = 95.45%
Kerapian = 11/12 = 91.67%
...
C1 = ...
C2 = ...
C3 = ...
```

---

## 18. Grafik V2

Gunakan Chart.js yang sudah tersedia.

Grafik 1:
- perbandingan C1, C2, C3 antar teknisi;
- bar chart.

Grafik 2:
- Vi per teknisi;
- hanya tampil jika SAW sudah diproses.

Grafik 3:
- detail indikator satu teknisi;
- gunakan bar/radar sesuai kemampuan library existing.

Data grafik harus berasal dari backend/database.

Jangan hardcode hasil.

---

## 19. Halaman V1 yang Masih Ada

Route:

```text
/owner/penilaian
/owner/penilaian/create
```

Masih memiliki input manual C1/C2/C3.

Ini adalah workflow V1/legacy.

Jangan langsung menghapus route tersebut karena compatibility dan regression.

Tetapi workflow utama V2 harus menggunakan:

```text
/owner/assessment
```

Jangan membuat dua sumber kebenaran untuk assessment V2.

---

## 20. Masalah Periode yang Pernah Terjadi

Pernah ada periode:

```text
2026-02-20 → 2026-02-20
```

Akibatnya Excel Jan-Mar ditolak:

```text
bulan 1 di luar rentang kuartal
bulan 3 di luar rentang kuartal
tanggal Januari/Maret berada di luar periode
```

Ini adalah masalah konfigurasi periode, bukan masalah SAW.

Untuk Q1 2026 gunakan:

```text
2026-01-01 → 2026-03-31
```

Jika memungkinkan, validasi UI agar Q1/Q2/Q3/Q4 konsisten dengan bulan kuartalnya.

Jangan mengubah Excel untuk menyesuaikan periode yang salah.

---

## 21. Partial

Partial berarti data kurang dari 3 bulan.

Contoh:

```text
3 bulan → complete
2 bulan → partial
1 bulan → partial
```

Jika Owner mengonfirmasi partial:
- status tetap partial;
- warning tetap;
- jumlah bulan tetap;
- SAW boleh memproses jika C1/C2/C3 valid.

Jangan memalsukan status lengkap.

---

## 22. Testing

Testing yang digunakan:
- Black Box Testing
- Equivalence Partitioning
- Boundary Value Analysis
- Unit tests
- Integration tests
- Regression tests
- Browser/UI tests

Phase 5 SAW V2:
- 17 skenario
- 21 assertions
- 21/21 PASS

Regression V1 juga PASS.

Setiap perubahan besar harus menjaga:
- Auth
- DB
- Excel Import
- Operational Calculator
- Workflow V2
- SAW V1
- SAW V2
- Legacy integrity

Jangan membuat test palsu hanya untuk mendapatkan PASS.

---

## 23. Phase Status

Phase 1 — Audit: selesai.

Phase 2 — Database V2 foundation: selesai.

Phase 3 — Excel Importer: selesai.

Phase 4 — Workflow V2: selesai.

Phase 5 — SAW Engine V2: selesai.

Phase 6 — target pengembangan berikutnya:

```text
Tabulasi
+
Grafik
+
Detail indikator
+
Owner Review UX
+
Confirmation UX
+
Integrasi end-to-end
```

Jangan menganggap Phase 6 selesai hanya karena route `/owner/assessment` sudah ada.

---

## 24. Prioritas Sekarang

Prioritas 1:
Pastikan periode Q1/Q2/Q3/Q4 valid.

Prioritas 2:
Pastikan Excel 10 teknisi dapat diimport ke Q1 2026.

Prioritas 3:
Pastikan:

```text
Import
 ↓
Calculation
 ↓
Owner Review
```

berjalan.

Prioritas 4:
Selesaikan Phase 6:

```text
Tabulasi
Grafik
Detail
Review
Confirmation
```

Prioritas 5:
Verifikasi:

```text
Confirm
 ↓
SAW V2
 ↓
Ranking
 ↓
Laporan
```

---

## 25. Prinsip Coding

Sebelum coding:
1. baca source code terkait;
2. cari route/controller/model/service;
3. pahami dependency;
4. gunakan service existing jika sudah sesuai;
5. jangan membuat duplicate logic;
6. jangan merusak V1;
7. jangan mengubah formula SAW tanpa instruksi;
8. jangan hardcode data penelitian;
9. jangan hardcode ranking/grafik;
10. gunakan migration untuk perubahan schema;
11. jalankan test;
12. jalankan regression setelah perubahan besar;
13. jangan git commit otomatis kecuali diminta.

---

## 26. Prinsip UI

Gunakan desain existing:
- dark charcoal;
- warm gold;
- clean;
- premium;
- modern;
- tidak terlalu ramai.

Tabel besar boleh horizontal scroll.

Jangan mengganti seluruh UI tanpa alasan.

---

## 27. Definisi Fitur Selesai

Fitur tidak dianggap selesai hanya karena route dapat dibuka.

Minimal harus diperiksa:

```text
Database
+
Backend
+
UI
+
Validation
+
Authorization
+
Integration
+
Automated Test
+
Regression
```

Contoh Phase 6 selesai jika:

```text
Admin upload Excel
 ↓
Data valid
 ↓
Calculation
 ↓
10 teknisi muncul
 ↓
Owner Review
 ↓
Tabulasi muncul
 ↓
Detail indikator muncul
 ↓
Grafik muncul
 ↓
Owner confirm
 ↓
SAW dapat diproses
```

---

## 28. Jawaban Singkat untuk Memahami Sistem

Jika perlu menjelaskan project:

> Admin menyiapkan data teknisi, kriteria, periode, dan data operasional melalui Excel. Sistem memvalidasi serta mengolah data tersebut menjadi indikator dan nilai C1, C2, dan C3. Owner kemudian melihat tabulasi, grafik, dan detail indikator untuk melakukan review dan konfirmasi. Setelah dikonfirmasi, data diproses menggunakan metode SAW dengan bobot C1 30%, C2 40%, dan C3 30% untuk menghasilkan nilai preferensi dan ranking teknisi.

---

## 29. Instruksi Utama untuk Hermes

Selalu bedakan:

```text
V1 / Legacy
```

dengan:

```text
V2 / Current Workflow
```

V1 harus tetap kompatibel.

V2 adalah workflow utama yang sedang dikembangkan.

Jika ada konflik:
1. jangan langsung menghapus;
2. audit source code;
3. identifikasi apakah V1 atau V2;
4. pertahankan legacy;
5. jangan mengubah formula;
6. jangan mengarang data;
7. prioritaskan integritas data dan traceability.

Urutan prioritas:

```text
Correct Data
    ↓
Correct Calculation
    ↓
Traceability
    ↓
Clear Workflow
    ↓
Good UI
    ↓
Testing
```

Dokumen ini adalah konteks kerja. Jika ada instruksi baru dari pemilik project yang secara eksplisit mengubah keputusan sebelumnya, ikuti instruksi terbaru tersebut dan dokumentasikan perubahan yang berdampak pada arsitektur, database, workflow, atau skripsi.
