# ASENTRA SPK - V2 Feature Roadmap

> Dokumen spesifikasi implementasi V2 untuk Hermes Agent.
> 
> Tujuan utama: menjadi sumber instruksi teknis dan checklist agar Hermes memahami fitur V2 yang harus tersedia, apa yang sudah selesai, apa yang perlu diperbaiki, dan bagaimana setiap fitur harus diuji tanpa merusak V1 maupun data legacy.

---

## 1. Identitas Project

**Project:** ASENTRA SPK  
**Perusahaan:** CV Arsitek Semesta Nusantara (ASENTRA), Cirebon  
**Sistem:** Sistem Pendukung Keputusan Penilaian Kinerja Teknisi Lapangan  
**Metode:** Simple Additive Weighting (SAW)  
**Platform:** Web  
**Arsitektur:** PHP MVC custom/lightweight

### Stack

- PHP 8.2+
- MySQL/MariaDB
- HTML/CSS/JavaScript vanilla
- Chart.js 4.4.1
- PhpSpreadsheet 2.4.7
- Local development:
  `C:/xampp/php/php.exe -S 127.0.0.1:8080 -t public public/index.php`

### Aturan arsitektur

1. Jangan migrasi ke React, Vue, Laravel, CodeIgniter, atau framework lain.
2. Pertahankan PHP MVC yang sudah ada.
3. Jangan membuat dua sumber data untuk fitur V2.
4. Jangan mengubah formula SAW yang sudah diuji kecuali ada instruksi eksplisit.
5. Jangan mengubah data legacy.
6. Jangan melakukan git commit otomatis.
7. Sebelum perubahan besar, audit implementasi yang sudah ada.
8. Jangan membuat fitur yang sudah tersedia ulang hanya karena nama class/route berbeda.
9. Gunakan database sebagai sumber data utama, bukan hardcoded data di view.
10. Semua perubahan harus backward-compatible terhadap V1 sejauh tidak bertentangan dengan kebutuhan V2.

---

# 2. Tujuan V2

V2 bukan sekadar penambahan algoritma SAW.

V2 harus membentuk workflow penilaian kinerja teknisi yang lebih lengkap:

```text
ADMIN
  |
  v
Manajemen Periode
  |
  v
Upload Excel Data Operasional
  |
  v
Validasi & Import
  |
  v
Data Operasional
  |
  v
Calculation Engine
  |
  v
C1 Kedisiplinan
C2 Kualitas Hasil Kerja
C3 Tanggung Jawab
  |
  v
OWNER REVIEW
  |
  v
Tabulasi
  |
  v
Grafik
  |
  v
Detail Indikator
  |
  v
Owner Confirmation
  |
  v
SAW V2
  |
  v
Normalisasi
  |
  v
Nilai Preferensi
  |
  v
Ranking
  |
  v
Laporan / Riwayat
```

V2 harus memperlihatkan hubungan antara data operasional, indikator, nilai C1/C2/C3, proses review Owner, SAW, ranking, dan laporan.

---

# 3. Role dan Hak Akses

## Admin

Admin bertanggung jawab terhadap:

- membuat periode penilaian;
- mengatur data periode;
- upload/import Excel;
- melihat hasil validasi import;
- menjalankan calculation;
- melihat hasil calculation;
- memonitor status data;
- mengakses hasil sesuai hak akses yang sudah tersedia.

Admin bukan pihak yang melakukan final confirmation penilaian V2.

## Owner

Owner bertanggung jawab terhadap:

- melihat daftar penilaian;
- melihat tabulasi;
- melihat grafik;
- melihat detail indikator teknisi;
- melakukan review;
- melakukan koreksi/penyesuaian jika workflow memang menyediakan mekanisme tersebut;
- melakukan confirmation;
- melihat hasil SAW;
- melihat ranking;
- melihat riwayat/laporan.

## Teknisi

Teknisi adalah objek penilaian.

Teknisi bukan role login utama V2.

---

# 4. Dataset Teknisi Acuan

Gunakan 10 teknisi berikut sebagai dataset utama untuk pengujian V2:

| ID | Nama |
|---|---|
| A1 | Toni |
| A2 | Apip |
| A3 | Agus Supriyanto |
| A4 | Rahmat Hidayat |
| A5 | Ahmad Sahudin |
| A6 | Aris |
| A7 | IMADE |
| A8 | Asep |
| A9 | Wanto |
| A10 | Heri |

Jangan mengganti nama teknisi pada dataset acuan tanpa instruksi.

---

# 5. Kriteria SAW

V2 menggunakan 3 kriteria utama.

| Kode | Kriteria | Bobot | Sifat |
|---|---|---:|---|
| C1 | Kedisiplinan | 0.30 | Benefit |
| C2 | Kualitas Hasil Kerja | 0.40 | Benefit |
| C3 | Tanggung Jawab | 0.30 | Benefit |

Jumlah bobot:

```text
0.30 + 0.40 + 0.30 = 1.00
```

Formula normalisasi benefit:

```text
rij = xij / max(xj)
```

Nilai preferensi:

```text
Vi = 0.30*rC1 + 0.40*rC2 + 0.30*rC3
```

Ranking:

1. Nilai Vi terbesar berada di ranking lebih tinggi.
2. Jika Vi sama, gunakan `technician_id` ascending sebagai tie-break.
3. Ranking bersifat sequential: 1, 2, 3, ..., n.

---

# 6. Indikator V2

## C1 - Kedisiplinan

Bobot C1 = 30%.

### C1.1 Kehadiran

Formula:

```text
Persentase Kehadiran =
hadir / total_hari_kerja * 100%
```

Rating:

| Persentase | Rating |
|---|---:|
| >= 95% | 4 |
| 85% - <95% | 3 |
| 75% - <85% | 2 |
| <75% | 1 |

Sakit dan izin tidak langsung dianggap sebagai pelanggaran.

Alpa dapat ditampilkan sebagai informasi/peringatan.

### C1.2 Ketepatan Waktu

Berdasarkan jumlah keterlambatan:

| Terlambat | Rating |
|---:|---:|
| 0 - 2 | 4 |
| 3 - 5 | 3 |
| 6 - 8 | 2 |
| >= 9 | 1 |

### C1.3 Kepatuhan Jadwal

Formula:

```text
sesuai_jadwal / pekerjaan_terjadwal * 100%
```

Rating:

| Persentase | Rating |
|---|---:|
| >=95% | 4 |
| 80% - <95% | 3 |
| 65% - <80% | 2 |
| <65% | 1 |

Nilai C1 bulanan:

```text
C1_bulanan =
(C1.1 + C1.2 + C1.3) / 3
```

Pertahankan desimal.

---

# 7. C2 - Kualitas Hasil Kerja

Bobot C2 = 40%.

Indikator:

- C2.1 Kerapian
- C2.2 Presisi
- C2.3 Sesuai Desain

Setiap pekerjaan menggunakan nilai Yes/No.

Persentase:

```text
jumlah_yes / jumlah_pekerjaan * 100%
```

Rating:

| Persentase | Rating |
|---|---:|
| >=90% | 4 |
| 75% - <90% | 3 |
| 60% - <75% | 2 |
| <60% | 1 |

Nilai C2 bulanan:

```text
C2_bulanan =
(C2.1 + C2.2 + C2.3) / 3
```

---

# 8. C3 - Tanggung Jawab

Bobot C3 = 30%.

Indikator:

- C3.1 Perawatan Alat
- C3.2 Efisiensi Material
- C3.3 Inisiatif
- C3.4 Kepatuhan Prosedur

Setiap indikator menggunakan rating 1-4 beserta deskripsi.

Nilai C3 bulanan:

```text
C3_bulanan =
(C3.1 + C3.2 + C3.3 + C3.4) / 4
```

Untuk C3.1 terdapat panduan frekuensi:

| Frekuensi | Rating |
|---:|---:|
| 0 | 4 |
| 1 | 3 |
| 2 - 3 | 2 |
| >=4 | 1 |

Namun database utama menyimpan rating hasil penilaian. Jangan mengarang jumlah kejadian apabila data operasional tidak menyediakannya.

---

# 9. Agregasi Periode

V2 menggunakan periode 3 bulan.

Contoh:

```text
Q1 2026
01 Januari 2026
s/d
31 Maret 2026
```

Untuk setiap teknisi, sistem harus dapat mengetahui jumlah bulan yang memiliki data.

Jika suatu bulan tidak mempunyai data:

- bulan tersebut tidak dimasukkan ke denominator;
- jangan dianggap memiliki nilai 0;
- tampilkan jumlah bulan yang tersedia;
- berikan warning bila data tidak lengkap.

Minimum 1 bulan dapat diproses dengan warning.

Contoh:

```text
3 bulan tersedia -> normal
2 bulan tersedia -> partial/warning
1 bulan tersedia -> partial/warning
0 bulan tersedia -> tidak dapat dinilai
```

---

# 10. Manajemen Periode

## Target

Sistem harus mempunyai periode penilaian yang jelas.

Database:

`tb_periode_penilaian`

Field utama:

- `id_periode`
- `kode_periode`
- `nama_periode`
- `tanggal_mulai`
- `tanggal_selesai`
- `status`
- `file_import`
- `created_by`
- timestamps

Status periode:

```text
draft
proses
selesai
legacy
```

Jangan membuat status `confirmed` pada `tb_periode_penilaian` karena status confirmation berada pada `tb_penilaian.status_data`.

## Calendar Quarter Validation

Periode kuartal harus mengikuti kalender:

```text
Q1 = 01 Jan - 31 Mar
Q2 = 01 Apr - 30 Jun
Q3 = 01 Jul - 30 Sep
Q4 = 01 Okt - 31 Des
```

Jika sistem menggunakan kode periode Q1/Q2/Q3/Q4, validasi harus mencegah konfigurasi seperti:

```text
Q1 = 20 Feb - 20 Feb
```

apabila periode tersebut dimaksudkan sebagai Q1 kalender.

Jangan mengubah tanggal periode secara diam-diam. Tampilkan validation error yang jelas.

## Acceptance Criteria

- periode dapat dibuat;
- tanggal mulai/akhir valid;
- periode kuartal sesuai bulan;
- tidak terjadi overlap yang tidak diperbolehkan;
- periode legacy tidak dapat digunakan untuk workflow V2;
- periode `selesai` tidak dapat diubah sembarangan.

---

# 11. Excel Import V2

Satu file Excel digunakan untuk satu periode 3 bulan.

Required sheets:

```text
DATA_TEKNISI
KEDISIPLINAN
KUALITAS_KERJA
TANGGUNG_JAWAB
PETUNJUK
```

Excel berisi DATA OPERASIONAL.

Excel tidak boleh hanya berisi:

```text
C1
C2
C3
SAW
Ranking
```

C1/C2/C3 dihasilkan melalui calculation engine.

## Validation

Importer harus memvalidasi:

- extension `.xlsx`;
- ukuran file;
- sheet wajib;
- header;
- teknisi;
- tanggal;
- bulan;
- rentang periode;
- nilai operasional non-negatif;
- quality Yes/No atau 0/1 sesuai format;
- responsibility rating 1-4;
- denominator;
- duplicate data;
- transaction;
- import result.

Import log menggunakan:

`tb_import`

---

# 12. Database V2

## `tb_periode_penilaian`

Menyimpan periode.

## `tb_import`

Menyimpan log import.

## `tb_kedisiplinan`

Field penting:

- `id_periode`
- `id_teknisi`
- `bulan`
- `total_hari_kerja`
- `hadir`
- `sakit`
- `izin`
- `alpa`
- `terlambat`
- `pekerjaan_terjadwal`
- `sesuai_jadwal`

Unique:

```text
periode + teknisi + bulan
```

## `tb_pekerjaan`

Field penting:

- `id_periode`
- `id_teknisi`
- `tanggal`
- `bulan`
- `nama_pekerjaan`
- `rapi`
- `presisi`
- `sesuai_desain`

## `tb_tanggung_jawab`

Field penting:

- `id_periode`
- `id_teknisi`
- `bulan`
- `perawatan_alat`
- `efisiensi_material`
- `inisiatif`
- `kepatuhan_prosedur`

Unique:

```text
periode + teknisi + bulan
```

## `tb_penilaian`

Menyimpan hasil C1/C2/C3.

Field penting:

- `id_periode`
- `id_teknisi`
- `c1`
- `c2`
- `c3`
- `status_data`
- `jumlah_bulan_c1`
- `jumlah_bulan_c2`
- `jumlah_bulan_c3`
- `warning`
- `confirmed_by`
- `confirmed_at`

Status:

```text
draft
calculated
partial
confirmed
legacy
```

Compatibility fields V1 yang sudah ada jangan dihapus tanpa audit.

## `tb_hasil`

Menyimpan hasil SAW.

Field penting:

- `id_periode`
- `penilaian_id`
- `id_teknisi`
- C1/C2/C3
- bobot
- normalisasi
- kontribusi
- nilai_preferensi
- ranking

Bobot yang disimpan pada hasil merupakan SNAPSHOT bobot ketika SAW dijalankan.

---

# 13. Calculation Engine

Calculation Engine bertugas mengubah data operasional menjadi C1/C2/C3.

Alur:

```text
Raw Operational Data
        |
        v
Indicator Calculation
        |
        v
Rating
        |
        v
Monthly Indicator
        |
        v
Period Aggregation
        |
        v
C1 / C2 / C3
```

Service yang sudah ada:

- `CalculationEngine`
- `DisciplineCalculator`
- `QualityCalculator`
- `ResponsibilityCalculator`

Jangan mengganti logic yang sudah lulus test tanpa alasan.

---

# 14. Assessment Workflow

Workflow resmi V2:

```text
1. Admin membuat periode
2. Admin upload Excel
3. Sistem validasi Excel
4. Sistem import raw data
5. Admin menjalankan calculation
6. Sistem menghasilkan C1/C2/C3
7. Owner membuka assessment
8. Owner melihat tabulasi
9. Owner melihat grafik
10. Owner melihat detail indikator
11. Owner melakukan review
12. Owner melakukan confirmation
13. Sistem menjalankan SAW untuk data confirmed
14. Sistem menghasilkan ranking
15. Owner/Admin dapat melihat laporan
```

---

# 15. Status Data Penilaian

Gunakan:

```text
draft
calculated
partial
confirmed
legacy
```

Makna:

### draft

Data belum siap.

### calculated

C1/C2/C3 sudah dihitung sistem tetapi belum dikonfirmasi Owner.

### partial

Data periode tidak lengkap.

### confirmed

Owner sudah mengonfirmasi.

### legacy

Data berasal dari sistem/versi lama.

---

# 16. Owner Review

Owner harus dapat melihat:

- nama teknisi;
- C1;
- C2;
- C3;
- jumlah bulan data;
- warning;
- detail indikator;
- bukti/angka operasional yang menjadi dasar perhitungan.

Owner harus memahami dari mana nilai C1/C2/C3 berasal.

Contoh:

```text
Kehadiran:
21 / 22 = 95.45%
Rating = 4
```

Jangan hanya menampilkan:

```text
C1 = 3.67
```

tanpa konteks.

---

# 17. Tabulasi

V2 membutuhkan halaman/tabulasi yang jelas.

Minimal:

```text
[Ringkasan] [Tabulasi] [Grafik]
```

Tabulasi harus menampilkan data per teknisi.

Minimal kolom:

```text
No
Teknisi
C1
C2
C3
Status
Bulan Data
Warning
```

Jika ruang memungkinkan, tambahkan indikator utama.

Data harus berasal dari backend/database.

Jangan hardcode nilai pada HTML.

---

# 18. Grafik

Gunakan Chart.js 4.4.1 yang sudah tersedia.

Minimal grafik:

### Grafik 1

Perbandingan C1, C2, C3 antar teknisi.

### Grafik 2

Nilai preferensi SAW per teknisi setelah SAW tersedia.

### Grafik 3

Detail indikator seorang teknisi.

Grafik harus:

- mengambil data backend;
- mengikuti periode yang sedang dibuka;
- tidak menggunakan data hardcoded;
- tetap berfungsi saat jumlah teknisi berubah;
- memiliki label yang jelas.

---

# 19. Detail Teknisi

Halaman detail Owner harus menjelaskan:

```text
Teknisi
  |
  +-- C1 Kedisiplinan
  |     +-- Kehadiran
  |     +-- Ketepatan Waktu
  |     +-- Kepatuhan Jadwal
  |
  +-- C2 Kualitas
  |     +-- Kerapian
  |     +-- Presisi
  |     +-- Sesuai Desain
  |
  +-- C3 Tanggung Jawab
        +-- Perawatan Alat
        +-- Efisiensi Material
        +-- Inisiatif
        +-- Kepatuhan Prosedur
```

Tampilkan:

- raw evidence;
- persentase;
- rating;
- nilai bulanan;
- agregasi periode;
- warning;
- jumlah bulan data.

---

# 20. Owner Confirmation

Endpoint yang sudah digunakan:

```text
POST /owner/assessment/{id}/confirm
```

Confirmation harus:

- hanya dapat dilakukan oleh Owner;
- mengubah data assessment menjadi `confirmed`;
- menyimpan `confirmed_by`;
- menyimpan `confirmed_at`;
- mengubah status periode menjadi `selesai` sesuai workflow yang sudah ada;
- menggunakan transaction;
- tidak mengubah data legacy.

Partial confirmed diperbolehkan jika workflow V2 memang mengizinkannya, tetapi warning dan status partial harus tetap dapat ditelusuri.

---

# 21. SAW V2

SAW hanya boleh menggunakan:

```text
tb_penilaian.status_data = confirmed
```

Jangan memproses:

```text
draft
calculated
unconfirmed partial
legacy
```

Confirmed partial boleh diproses sesuai aturan yang sudah diterapkan, tanpa menghapus informasi bahwa datanya partial.

## Formula

Normalisasi benefit:

```text
rij = xij / max(xj)
```

Preferensi:

```text
Vi = 0.30*rC1 + 0.40*rC2 + 0.30*rC3
```

Ranking:

```text
ORDER BY nilai_preferensi DESC,
         id_teknisi ASC
```

Ranking sequential.

---

# 22. SAW Traceability

Setiap hasil SAW harus dapat ditelusuri kembali ke penilaian sumber.

Gunakan:

```text
tb_hasil.penilaian_id
```

Relasi:

```text
tb_hasil
   |
   +-- penilaian_id
          |
          +-- tb_penilaian
```

Jangan membuat hasil SAW yang tidak dapat diketahui sumber C1/C2/C3-nya.

---

# 23. SAW Rerun

Rerun SAW harus aman.

Jika SAW dijalankan ulang:

- hasil periode tersebut boleh dihitung ulang;
- hasil periode lain tidak boleh berubah;
- legacy tidak boleh berubah;
- transaction harus digunakan;
- tidak boleh menghasilkan duplicate result untuk periode yang sama;
- snapshot bobot tetap tersimpan pada hasil.

---

# 24. Legacy Protection

Data legacy:

```text
LEGACY-2026-08
LEGACY-2026-09
```

Jangan diubah.

Sebelum dan sesudah V2:

```text
tb_penilaian = 12
tb_hasil = 12
```

setelah test teardown legacy harus tetap 12.

SAW V2 harus menolak periode legacy.

Delete result legacy harus ditolak.

Jangan memetakan data legacy ke format V2 secara palsu.

Legacy harus tetap dapat dibaca oleh fitur lama yang memang menggunakannya.

---

# 25. Ranking

Ranking V2 harus memperlihatkan minimal:

```text
Ranking
Teknisi
C1
C2
C3
Nilai Preferensi
```

Jika diperlukan:

```text
Normalisasi C1
Normalisasi C2
Normalisasi C3
Kontribusi C1
Kontribusi C2
Kontribusi C3
```

Tujuan utamanya adalah transparansi perhitungan.

---

# 26. Laporan dan Riwayat

V2 harus menyediakan akses ke:

- hasil penilaian;
- ranking;
- periode;
- detail teknisi;
- hasil SAW;
- riwayat periode.

Laporan harus menggunakan data database.

Jangan membuat angka laporan secara manual/hardcoded.

Jika export sudah tersedia, pastikan export menggunakan hasil aktual periode yang dipilih.

---

# 27. Route V2 yang Sudah Ada

Admin:

```text
GET  /admin/periode
GET  /admin/periode/create
POST /admin/periode/store
POST /admin/periode/kalkulasi/{id}
GET  /admin/periode/hasil/{id}
```

Import:

```text
GET/POST /admin/import
```

Owner:

```text
GET  /owner/assessment
GET  /owner/assessment/{id}/detail/{teknisiId}
POST /owner/assessment/{id}/confirm
```

Jangan menghapus route V1 tanpa audit.

Route V1 seperti:

```text
/owner/penilaian
/owner/penilaian/create
```

dapat tetap dipertahankan untuk compatibility jika memang masih digunakan.

Namun workflow V2 harus memiliki satu sumber kebenaran yang jelas.

---

# 28. File Penting yang Sudah Ada

Controllers:

```text
AdminDashboardController.php
AuthController.php
KriteriaController.php
LaporanController.php
OwnerDashboardController.php
OwnerPenilaianController.php
OwnerTeknisiController.php
PenilaianController.php
RankingController.php
TeknisiController.php
ImportController.php
AdminPeriodeController.php
OwnerAssessmentController.php
```

Models:

```text
Hasil.php
Kriteria.php
Penilaian.php
Teknisi.php
User.php
PeriodePenilaian.php
Import.php
Kedisiplinan.php
Pekerjaan.php
TanggungJawab.php
```

Services:

```text
SawEngine.php
SawService.php
SawEngineV2.php
SawServiceV2.php
AssessmentWorkflowService.php
CalculationEngine.php
DisciplineCalculator.php
QualityCalculator.php
ResponsibilityCalculator.php
```

Views V2:

```text
admin/periode_list.php
admin/periode_form.php
admin/periode_hasil.php
owner/assessment_preview.php
owner/assessment_detail.php
```

---

# 29. Phase Status Saat Roadmap Dibuat

## Phase 1

Project foundation / V1:

**SELESAI**

## Phase 2

Database + models V2:

**SELESAI**

## Phase 3

Excel importer:

**SELESAI**

Test:

```text
17 scenarios
40/40 assertions PASS
```

## Phase 4

Workflow V2:

**SELESAI**

Mencakup:

- calculation;
- assessment summary;
- detail;
- confirmation;
- status.

## Phase 5

SAW V2 backend:

**SELESAI**

Test:

```text
17 scenarios
21 assertions PASS
```

Regression V1:

**PASS**

Legacy:

```text
12 penilaian
12 hasil
```

tetap aman.

## Phase 6

UI integration dan end-to-end:

**BELUM SELESAI SEPENUHNYA**

Prioritas utama.

---

# 30. Phase 6A - Fix Period Validation

Status:

```text
DONE
```

Target:

- validasi Q1/Q2/Q3/Q4;
- mencegah periode salah;
- pesan error jelas;
- periode import sesuai dengan Excel.

Acceptance test:

```text
Q1 2026 = 2026-01-01 s/d 2026-03-31
Q2 2026 = 2026-04-01 s/d 2026-06-30
Q3 2026 = 2026-07-01 s/d 2026-09-30
Q4 2026 = 2026-10-01 s/d 2026-12-31
```

---

# 31. Phase 6B - End-to-End Excel Test

Status:

```text
DONE
```

Gunakan 10 teknisi.

Gunakan satu periode Q1 2026.

Target:

```text
Excel
 -> Import
 -> Validation
 -> Raw DB
 -> Calculation
 -> tb_penilaian
```

Pastikan:

- 10 teknisi dikenali;
- Januari-Maret diterima;
- data di luar periode ditolak;
- tidak ada duplicate;
- C1/C2/C3 terbentuk;
- jumlah bulan benar;
- warning benar.

---

# 32. Phase 6C - Owner Tabulation

Status:

```text
DONE
```

Buat tabulasi yang jelas.

Target:

```text
Ringkasan
Tabulasi
Grafik
```

Tabulasi harus memakai data hasil calculation.

---

# 33. Phase 6D - Owner Graphs

Status:

```text
DONE
```

Implementasikan Chart.js.

Minimal:

```text
C1 vs C2 vs C3
```

Setelah SAW:

```text
Nilai Preferensi / Vi
```

Detail teknisi:

```text
Indicator breakdown
```

---

# 34. Phase 6E - Owner Review UX

Status:

```text
DONE
```

Perjelas:

- source data;
- nilai indikator;
- warning;
- partial status;
- detail teknisi;
- perbedaan raw data dan derived value.

Owner harus bisa memahami alasan angka yang muncul.

---

# 35. Phase 6F - Confirmation UX

Status:

```text
DONE
```

Sebelum confirm:

Tampilkan:

```text
Periode
Jumlah teknisi
Jumlah data lengkap
Jumlah data partial
Warning
```

Setelah confirm:

```text
confirmed
confirmed_by
confirmed_at
```

Periode berubah sesuai workflow menjadi:

```text
selesai
```

---

# 36. Phase 6G - Integrasi SAW ke UI

Status:

```text
DONE
```

Backend SAW V2 sudah tersedia.

UI harus memanggil service yang benar.

Jangan menulis ulang formula SAW di controller atau view.

Gunakan:

```text
SawServiceV2
SawEngineV2
```

Hasil harus tersimpan di:

```text
tb_hasil
```

---

# 37. Phase 6H - Ranking UI

Status:

```text
DONE
```

Tampilkan:

- ranking;
- teknisi;
- C1;
- C2;
- C3;
- normalized values jika dibutuhkan;
- contribution;
- Vi.

Pastikan ranking berasal dari database.

Catatan implementasi:

```text
Seluruh nilai dibaca dari tb_hasil (Hasil::byPeriodeId / byPeriodeFlexible).
Tidak ada formula SAW di controller maupun view.

 Kolom tabel ranking (V2):
   RANK | TEKNISI | C1 | C2 | C3
   | N1 (C1/max) | N2 | N3
   | K1 (w×N1) | K2 | K3
   | Vi | AKSI

 Normalisasi & kontribusi hanya ditampilkan jika baris tb_hasil
 memuat id_periode + nilai_*_normalisasi (V2). Baris legacy
 menampilkan kolom "legacy" agar tidak ada nilai yang dikarang.

 Periode V2 dipilih via id_periode (numeric). Kode quarter
 ('Q1-2026' maupun '2026-Q3') tetap diterima sebagai fallback.

 Laporan (Cetak Laporan) sekarang menerima id_periode, kode quarter
 V2, dan kode V1 'YYYY-MM' / 'LEGACY-YYYY-MM' lewat
 Hasil::byPeriodeFlexible(). Index laporan tidak lagi menampilkan
 periode yang sama dua kali.

 detail_saw mengambil max(C1/C2/C3) langsung dari tb_hasil
 (Hasil::maxCriteriaByPeriodeId) — sebelumnya dihitung ulang sebagai
 c/normalisasi, yang membulatkan max(C2) menjadi 4 dan bisa
 membagi dengan nol.

 Label periode V2 memakai nama_periode + rentang tanggal
 (PeriodePenilaian::findById), bukan periodLabel() string mentah.

 Test:
   tests/phase6h_ranking_ui_test.php      40/40 PASS
   tests/phase6h_ranking_ui_http_test.py  51/51 PASS
```

Known issue (di luar scope Phase 6H, tidak diperbaiki):

```text
1. tests/owner_ui_test.py baris 74 mengecek "Agustus 2026" ATAU
   "September 2026" di owner dashboard. Sejak periode V2 'Q1-2026'
   menjadi yang terbaru, dashboard menampilkan label quarter
   (periodLabel('Q1-2026') = 'Q1-2026'), sehingga asersi gagal
   (40/41). Bukan regression Phase 6H — asersi test perlu
   memperbolehkan label quarter V2.

2. app/controllers/AuthController.php pada commit HEAD dimulai dengan
   karakter '+' sebelum '<?php' (file: "+<?php"), yang menyebabkan
   fatal "strict_types declaration must be the very first statement".
   Aplikasi tidak bisa login sama sekali pada pristine checkout.
   Sudah diperbaiki di working tree sebelum Phase 6H (untracked fix).
   Hanya terlihat saat git stash ke HEAD.
```

---

# 38. Phase 6I - Reports / History

Status:

```text
DONE
```

Acceptance criteria 6I:

```text
- [x] periode dapat dipilih
- [x] hasil dapat dilihat
- [x] ranking dapat dilihat
- [x] history tetap tersedia
- [x] legacy tetap aman
- [x] export tidak menggunakan data hardcoded
```

Catatan implementasi:

```text
1. app/helpers/format.php periodLabel() sekarang menerjemahkan kode quarter
   V2 ('Q1-2026' maupun '2026-Q3') lewat tb_periode_penilaian:
   nama_periode + rentang tanggal (mis. "Januari - Maret 2026
   (2026-01-01 s/d 2026-03-31)"). Kode quarter tanpa baris periode
   jatuh ke label terbaca "Triwulan I 2026". Perilaku V1
   'YYYY-MM' dan 'LEGACY-YYYY-MM' sama sekali tidak berubah (tidak
   menyentuh DB).

2. app/controllers/LaporanController.php show() sekarang mengirim
   periodInfo (dari id_periode baris tb_hasil pertama) ke view
   owner/laporan.php, sehingga judul laporan memakai nama_periode +
   rentang tanggal untuk periode V2. index() tidak lagi mengirim
   data 'periods' yang tidak dipakai.

3. tests/owner_ui_test.py baris 74 diperbaiki: asersi menerima label
   V1 ("Agustus 2026"), label V2 nama_periode ("Januari - Maret 2026"),
   maupun label quarter ("Triwulan ..."). Sekarang 41/41 (sebelumnya
   40/41). Bukan hack per-data: asersi menerima semua bentuk label
   yang valid, bukan menghardcode "Q1-2026".

4. AuthController.php: working tree sudah benar — diawali persis
   "<?php" (diverifikasi dengan hexdump: 3c3f 7068 70), `php -l` OK,
   dan login terbukti end-to-end melalui built-in server (auth_service_test
   + login live owner/owner -> /owner/dashboard 200). Tidak ada
   perubahan dilakukan; issue '+' hanya ada pada commit HEAD.

5. Laporan verify (Phase 6I):
   - /owner/laporan/{id_periode} dan /owner/laporan/Q1-2026 keduanya
     merender laporan asli (bukan halaman index).
   - /owner/laporan/2026-08 -> "Agustus 2026".
   - /owner/laporan/LEGACY-2026-09 -> "September 2026".
   - Periode tanpa baris tb_hasil (2026-Q3) ditolak bersih (redirect
     ke index + flash error).
   - Index laporan mendaftar V2 by id_periode + legacy tanpa duplikat
     (Q1-2026 muncul tepat 1x).
   - Semua baris laporan dibaca dari tb_hasil; urutan ranking di UI
     dibandingkan langsung dengan query DB (tidak ada hardcoded data).

Test:

```text
tests/phase6i_reports_history_test.php   51/51 PASS
tests/phase6i_http_test.py               41/41 PASS (built-in server)
```

Regression (semua PASS, tidak ada yang mundur):

```text
db_connect_test, saw_engine_test 17/17, saw_service_test 12/12,
saw_v2_test 21/21, saw_deep_verify 8/8, workflow_v2_test 34/34,
phase6b_e2e_excel_test 35/35, phase6c 19/19, phase6d 30/30,
phase6e 49/49, phase6f 52/52, phase6g 33/33, phase6h_ranking_ui_test 40/40,
phase6h_ranking_ui_http_test 51/51, auth_service_test, password_verify_test,
periode_validation_test 35/35, operational_calculator_test 66/66,
excel_import_test 40/40, laporan_test.py 24/24,
owner_ui_test.py 41/41 (sebelumnya 40/41 — bug 6H fixed)
```

Legacy invariant setelah seluruh run:

```text
tb_penilaian legacy = 12  (status_data='legacy')
tb_hasil legacy    = 12   (id_periode pada tb_periode_penilaian status='legacy')
tb_penilaian total = 22, tb_hasil total = 22
SawEngineV2.php / SawServiceV2.php tidak diubah (checksum dibaca, tidak ditulis)
```

Known issue (di luar scope Phase 6I, tidak diperbaiki):

```text
1. app/controllers/AuthController.php pada commit HEAD masih diawali
   '+<?php' (karakter '+' liar). Working tree sudah bersih sejak
   Phase 6H. Perbaikan permanen butuh commit ulang file tersebut;
   dilarang git commit otomatis, jadi issue ini dicatat saja.

2. tb_periode_penilaian id=24 ('2026-Q3', nama "Triwulan VI 2026",
   2026-02-20 s/d 2026-02-20, status=draft) tidak lolos validasi
   calendar quarter (Q3 harus 01 Jul - 30 Sep) dan berlabel aneh
   ("Triwulan VI"). Data sampel Phase 6A, bukan dibuat Phase 6I.
   Tidak ada hasil SAW pada periode ini, jadi tidak memengaruhi
   laporan. record-only.
```

---

# 39. Phase 6J - Final Testing

Status:

```text
DONE
```

Implementation notes:

```text
Test files baru (self-cleaning, throwaway period TEST6J-2026-Q1):

  tests/phase6j_final_e2e_test.php      55/55 PASS
  tests/phase6j_final_e2e_http_test.py  13/13 PASS

E2E chain tervalidasi penuh:
  create period -> Excel -> validate -> import (130 baris, 0 gagal)
  -> calculate (10 teknisi, status_data=calculated)
  -> owner review (summary, tabulasi, indicator detail)
  -> confirm (status_data=confirmed, confirmed_at, periode=selesai)
  -> SawServiceV2::process -> tb_hasil 10 baris
  -> ranking view + laporan view render
  -> teardown, baseline 22/22 restored.

Verifikasi utama:
  - V1 golden 2026-08 unchanged (exact decimal compare, 10 teknisi)
  - V2 golden Q1-2026 unchanged (exact decimal compare, 10 teknisi)
  - Legacy tb_penilaian=12 / tb_hasil=12 sebelum dan sesudah test
  - Legacy tb_hasil content-hash (md5) unchanged
  - SawEngineV2.php + SawServiceV2.php byte-identical (sha256 before/after)
  - Re-confirm ditolak dengan RuntimeException bersih (bukan crash)
  - Re-run SAW idempoten
  - Vi = kontribusi_c1+c2+c3 (0 drift), ranking sekuensial 1..10
  - ChartDataService source: 'preview' pre-SAW, 'saw' post-SAW

HTTP audit (admin + owner + error handling):
  - owner dashboard/ranking/riwayat/laporan: 200, no PHP fatal
  - laporan V2 by id, V2 by quarter code, V1 legacy, LEGACY-: render benar
  - non-existent report id: clean, no raw PHP error
  - invalid period selector: clean
  - unauthenticated -> redirect to /login
  - admin blocked from /owner/* (403)

Cleanup residue (dengan bukti tb_import.nama_file linkage):
  - storage/imports: 111 -> 2 file (109 unreferenced dihapus,
    2 yang ter-referensi tb_import DIPERTAHANKAN)
  - storage/logs/server.log dihapus
  - tests/__pycache__ dihapus
  - PHASE6J_REPORT.md stub dihapus

Known issue (di luar scope 6J, didokumentasikan, tidak diperbaiki):

```text
K1 app/controllers/AuthController.php: working tree bersih (diawali
   "<?php", login owner+admin terbukti via HTTP). Commit HEAD masih
   berawalan "+<?php" -> fatal strict_types di pristine checkout.
   Perbaikan permanen butuh git commit (dilarang di sesi ini).

K2 tb_periode_penilaian id=24 (2026-Q3 "Triwulan VI 2026",
   2026-02-20..2026-02-20, draft): tanggal tidak sesuai kalender kuartal
   + label aneh. Data-level, tidak dimutasi. 0 tb_hasil, no impact.

K3 tb_hasil.periode legacy: '2026-08' (id 1) vs 'LEGACY-2026-09' (id 2)
   ejaan tidak konsisten. Dilindungi, tidak dirapikan.
```

Definition of Done:

```text
[x] seluruh test utama PASS
[x] regression PASS
[x] E2E PASS
[x] V1 tetap kompatibel
[x] V2 berjalan
[x] legacy tetap aman
[x] tidak ada known issue kritis yang belum diselesaikan/didokumentasikan
[x] source code dalam kondisi valid
[x] dokumentasi project diperbarui
```

Wajib menjalankan:

### Unit / service tests

```text
Excel importer tests
SAW V2 tests
Calculation tests
Workflow tests
```

### Regression

Pastikan V1 masih bekerja.

### Legacy test

Pastikan:

```text
tb_penilaian = 12
tb_hasil = 12
```

untuk legacy fixture setelah teardown.

### End-to-end

```text
Create period
   ↓
Upload Excel
   ↓
Import
   ↓
Calculate
   ↓
Owner Review
   ↓
Tabulation
   ↓
Graphs
   ↓
Detail
   ↓
Confirm
   ↓
SAW
   ↓
Ranking
   ↓
Report
```

---

# 40. Acceptance Criteria Utama V2

V2 dianggap siap apabila:

- [ ] Periode kalender valid.
- [ ] Excel Q1/Q2/Q3/Q4 tervalidasi dengan benar.
- [ ] 10 teknisi dapat diimport.
- [ ] Raw operational data tersimpan.
- [ ] Calculation Engine menghasilkan C1/C2/C3.
- [ ] Partial data ditandai dengan benar.
- [ ] Owner dapat melihat assessment.
- [ ] Tabulasi tersedia.
- [ ] Grafik tersedia.
- [ ] Detail indikator tersedia.
- [ ] Owner dapat melakukan confirmation.
- [ ] Hanya confirmed data yang masuk SAW.
- [ ] SAW V2 menggunakan bobot 0.30/0.40/0.30.
- [ ] Normalisasi benefit benar.
- [ ] Nilai Vi benar.
- [ ] Ranking descending.
- [ ] Tie-break technician_id ascending.
- [ ] Ranking sequential.
- [ ] Hasil SAW memiliki `penilaian_id`.
- [ ] Rerun SAW aman.
- [ ] Periode terisolasi.
- [ ] Legacy tidak berubah.
- [ ] Laporan memakai database.
- [ ] Regression test PASS.

---

# 41. Aturan Anti-Regresi untuk Hermes

JANGAN:

1. Menghapus tabel V1 hanya karena V2 sudah ada.
2. Menghapus route V1 tanpa audit.
3. Mengubah data legacy.
4. Mengubah formula SAW tanpa instruksi.
5. Mengubah bobot menjadi nilai lain.
6. Menjadikan subindikator sebagai kriteria SAW baru.
7. Memasukkan C1/C2/C3 ke Excel sebagai data utama.
8. Hardcode angka hasil ke view.
9. Membuat ranking sendiri di JavaScript jika backend sudah menyediakan ranking.
10. Membuat duplicate service untuk fungsi yang sudah ada.
11. Mengubah `tb_periode_penilaian.status` menjadi `confirmed`.
12. Menganggap `partial` otomatis sama dengan `confirmed`.
13. Memproses `draft` atau `calculated` ke SAW.
14. Memproses legacy ke SAW V2.
15. Menghapus warning karena ingin UI terlihat lebih bersih.
16. Mengubah struktur database tanpa migration/backup strategy yang jelas.
17. Melakukan git commit otomatis.

---

# 42. Prosedur Kerja Hermes

Untuk setiap phase:

## Step 1 - Audit

Cari:

- controller terkait;
- model terkait;
- service terkait;
- view terkait;
- route;
- database;
- test.

## Step 2 - Gap Analysis

Tentukan:

```text
SUDAH ADA
SEBAGIAN
BELUM ADA
BUG
```

## Step 3 - Implementation Plan

Sebelum coding, tuliskan:

```text
File yang akan diubah:
File yang akan dibuat:
Database yang terlibat:
Route yang terlibat:
Dependency:
Risiko:
Test yang akan dijalankan:
```

## Step 4 - Implement

Implementasikan hanya scope phase tersebut.

Jangan mengerjakan phase berikutnya sekaligus.

## Step 5 - Test

Jalankan test terkait.

## Step 6 - Regression

Pastikan fitur sebelumnya tetap bekerja.

## Step 7 - Report

Laporkan:

```text
Phase:
Status:
File berubah:
Fitur selesai:
Test:
Regression:
Known issue:
Next phase:
```

---

# 43. Instruksi Utama untuk Hermes

Kamu bekerja pada project ASENTRA SPK.

Gunakan file ini bersama:

```text
PROJECT_CONTEXT_HERMES.md
```

`PROJECT_CONTEXT_HERMES.md` menjelaskan konteks dan kondisi project.

`V2_FEATURE_ROADMAP.md` menjelaskan target implementasi V2.

Keduanya harus dibaca sebelum melakukan perubahan besar.

Jangan berasumsi fitur belum ada hanya karena belum terlihat pada UI. Audit controller, service, model, route, database, dan test terlebih dahulu.

Jangan mengimplementasikan ulang backend yang sudah PASS.

Prioritaskan integrasi dan UI apabila backend sudah tersedia.

Kerjakan phase secara berurutan.

Phase berikutnya tidak boleh dianggap selesai hanya karena file sudah dibuat. Gunakan acceptance criteria dan test.

Jika menemukan konflik antara dokumen dan source code:

1. Jangan diam-diam memilih salah satu.
2. Laporkan konflik.
3. Identifikasi dampaknya.
4. Pertahankan behavior yang sudah teruji.
5. Minta instruksi hanya jika keputusan tersebut benar-benar membutuhkan perubahan desain.

Jangan melakukan perubahan destruktif.

Jangan mengubah legacy.

Jangan melakukan git commit otomatis.

---

# 44. Current Priority

Urutan prioritas saat ini:

```text
1. Phase 6A - Fix Period Validation
2. Phase 6B - End-to-End Excel Test
3. Phase 6C - Owner Tabulation
4. Phase 6D - Owner Graphs
5. Phase 6E - Owner Review UX
6. Phase 6F - Confirmation UX
7. Phase 6G - SAW UI Integration
8. Phase 6H - Ranking UI
9. Phase 6I - Reports / History
10. Phase 6J - Final Testing
```

Jangan langsung melompat ke Phase 6G hanya karena SAW backend sudah selesai. Sistem ini bukan lomba membuat ranking secepat mungkin. Data operasional -> penilaian -> review -> confirmation harus dapat dipahami dan diverifikasi terlebih dahulu.

---

# 45. Definition of Done

Sebuah fitur dianggap DONE hanya jika:

```text
Code exists
+
Route works
+
Database interaction works
+
Authorization works
+
UI works
+
Real data works
+
Error handling works
+
Test passes
+
Regression passes
```

File yang sekadar ada di repository bukan bukti fitur selesai.

---

# 46. Final Target

Target akhir V2:

```text
ADMIN
 |
 +--> Periode
 |
 +--> Import Excel
 |
 +--> Calculation
 |
 +--> Monitoring
 |
 v
OWNER
 |
 +--> Assessment
 |      |
 |      +--> Ringkasan
 |      +--> Tabulasi
 |      +--> Grafik
 |      +--> Detail
 |      +--> Review
 |      +--> Confirmation
 |
 +--> SAW
 |
 +--> Ranking
 |
 +--> Laporan
 |
 +--> Riwayat
```

Sistem harus menghasilkan proses penilaian yang:

- dapat ditelusuri;
- dapat diuji;
- tidak bergantung pada hardcoded data;
- menjaga data legacy;
- memisahkan raw operational data dari hasil penilaian;
- memisahkan calculation engine dari SAW engine;
- memberi Owner informasi yang cukup sebelum confirmation;
- menghasilkan ranking yang konsisten dan dapat dijelaskan.

---

# 47. Phase 6K — Auto-Detect Periode dari Excel

> Improvement Admin V2. Status:

```text
DONE
```

## Tujuan

Admin cukup upload file Excel; sistem otomatis:

1. membaca tanggal/bulan dari data operasional,
2. mendeteksi kuartal dan tahun,
3. memvalidasi tanggal terhadap kalender kuartal,
4. mengecek apakah periode sudah ada,
5. membuat periode otomatis jika belum ada (atau menggunakan yang sudah ada),
6. melanjutkan proses import.

Semua dalam satu halaman `/admin/import`. Input periode manual hanya opsi sekunder/advanced (collapsed), bukan langkah utama.

## Implementasi

```text
File baru:
  app/services/import/PeriodeDetector.php   deteksi quarter/year dari data
                                            (KUALITAS_KERJA tanggal primer + bulan
                                            di 3 sheet), kode YYYY-Qn + label human
                                            ("April - Juni 2026") + bounds kalender,
                                            validateQuarterRange(), tolak
                                            multi-quarter/multi-year; resolveOrCreate()
                                            reuse-by-kode atau create (status=draft,
                                            created_by=admin), race-safe.

  tests/phase6k_auto_periode_test.php       54/54
  tests/run_phase6k_regression.sh           batched runner 26 suite

File diubah:
  app/controllers/ImportController.php      default path = auto-detect -> create-or-reuse
                                            -> import; id_periode jadi optional fallback;
                                            guard tolak periode selesai/confirmed di
                                            kedua path; storePeriode() mini-form.
  app/views/admin/import.php                form upload tidak require periode; manual
                                            select + create form + tabel periode
                                            collapsed di "Opsi lanjutan".
  app/core/Router.php                       +1 route POST /admin/import/periode
```

## Hasil test

```text
phase6k_auto_periode_test.php           54/54 PASS
Regression 26 suite (21 PHP + 5 Python HTTP) semua PASS:
  db_connect OK, auth_service OK, password_verify OK,
  saw_engine 17/17, saw_service 12/12, saw_v2 21/21, saw_deep_verify 8/8,
  workflow_v2 34/34, phase6b 35/35, 6c 19/19, 6d 30/30, 6e 49/49, 6f 52/52,
  6g 33/33, 6h 40/40 + 51/51 HTTP, 6i 51/51 + 41/41 HTTP, 6j 55/55 + 13/13 HTTP,
  periode_validation 35/35, operational_calculator 66/66, excel_import 40/40,
  laporan 24/24, owner_ui 41/41.
```

## Verifikasi UI

```text
GET  /admin/import                200, no PHP fatal, opsi manual collapsed
POST /admin/import (upload xlsx)  200, redirect kembali ke /admin/import
Upload dummy_q2_2026.xlsx -> auto-create 2026-Q2 "April - Juni 2026"
       (2026-04-01 s/d 2026-06-30, status draft) + import 30 baris KEDISIPLINAN
Flash saat periode sudah ada: "sudah ada. Melanjutkan import data."
```

## Guard & validasi

```text
- auto-detect membaca DATA (tanggal + bulan), bukan filename / hidden cell
- quarter -> kode YYYY-Qn -> label human -> bounds kalender asli,
  lolos PeriodePenilaian::validateQuarterRange()
- cross-quarter (menjangkau 2 kuartal) / multi-year -> tolak bersih, tidak nebak
- findByKode() dulu: ada -> reuse (no duplicate); tidak ada -> create()
  race-safe (try/catch + re-read, antisipasi race UNIQUE constraint)
- periode berstatus selesai / confirmed TIDAK bisa diimport ulang,
  di path auto-detect MAUPUN explicit id_periode
- backward compat: POST id_periode eksplisit tetap jalan (130 rows teruji)
- allow_partial checkbox tidak diubah
- controller thin: zero spreadsheet parsing, semua di PeriodeDetector
```

## Invariant yang dijaga

```text
- legacy tb_penilaian=12, tb_hasil=12 tetap
- ids 1/2/24/25 tidak tersentuh
- baseline 22/22 restored setelah test, 0 residue test period
- SawEngineV2.php / SawServiceV2.php byte-identical (sha256, untracked)
- Owner workflow tidak berubah
- php -l clean di semua file berubah
- tidak ada dependency baru, tidak ada git commit
```

## Known issue

```text
- storage/imports/ mengakumulasi ~31 copy .xlsx tak ter-referensi dari run
  test 6K (import service menyimpan copy tiap upload). Di luar scope 6K,
  aman, tidak dihapus. Cleanup ulang butuh perbandingan tb_import.nama_file.
```

## Definition of Done

```text
[x] auto-detect derive quarter dari data (tanggal + bulan)
[x] detected -> 2026-Q2, "April - Juni 2026", 2026-04-01..2026-06-30
[x] belum ada -> dibuat draft; sudah ada -> reuse, no duplicate
[x] cross-quarter / multi-year -> tolak bersih
[x] periode selesai/confirmed tidak bisa reimport (path auto + explicit)
[x] backward compat id_periode eksplisit tetap jalan
[x] allow_partial tidak diubah
[x] controller thin, parsing di service
[x] satu halaman /admin/import, manual = opsi advanced
[x] php -l clean
[x] legacy aman, baseline restored, SAW byte-identical
[x] regression 26 suite PASS
[x] tidak ada git commit
```

---

# 48. Phase 6L — Admin UX Cleanup

> Improvement Admin V2. Status:

```text
DONE
```

## Tujuan

Jadikan Import Data V2 sebagai workflow utama Admin setelah Phase 6K. `/admin/import` menjadi pusat workflow periode — Admin tidak perlu pindah-pindah halaman.

## Scope & implementasi

```text
1. sidebar: menu "Kelola Periode V2" DIHAPUS dari sidebar admin
   (app/views/layouts/app.php, admin block saja; owner sidebar tidak berubah)
2. /admin/import jadi pusat workflow: tabel daftar periode muncul sebagai
   SECTION UTAMA (sebelumnya terkubur di collapsed details), menampilkan
   kode / nama / rentang / data operasional / status / aksi
3. partial shared app/views/admin/periode_table.php (baru): tabel + seluruh
   logika tombol aksi (Hitung V2, Hasil, Read-only) di satu tempat.
   Dipakai /admin/import DAN /admin/periode -> zero duplication
4. "+ Tambah Periode" dihapus dari UI utama. Path buat periode manual tersisa
   hanya di "Opsi lanjutan" (mini-form Phase 6K storePeriode)
5. status & tombol dirapikan: badge konsisten (selesai=success, proses=warning,
   legacy=neutral); Hitung V2 hanya saat can_calculate; Hasil link kondisional;
   legacy tetap Read-only
6. backend + route lama dipertahankan: /admin/periode, /admin/periode/create,
   /admin/periode/store, /admin/periode/kalkulasi/{id}, /admin/periode/hasil/{id}
   semuanya masih 200 (backward compat terverifikasi)
7. auto-detect Phase 6K tidak diubah — reorg UI murni, tanpa perubahan logic
```

## File yang berubah

```text
app/views/layouts/app.php           nav_item "Kelola Periode V2" dihapus (admin block)
app/views/admin/periode_table.php   BARU — shared partial tabel + logika tombol aksi
app/views/admin/import.php          tabel periode jadi section utama via viewPartial;
                                    tabel read-only lama di collapsed details dihapus
app/views/admin/periode_list.php    102 -> 13 baris; header buttons dihapus; render partial
app/controllers/AdminPeriodeController.php  index() pakai allWithProgress() (net -13)
app/controllers/ImportController.php       index() kirim allPeriods ke view (bug fix)
app/models/PeriodePenilaian.php     + allWithProgress(): all() + counts + canCalculate()
tests/phase6l_admin_ux_test.php     BARU 66/66
tests/run_phase6l_regression.sh     BARU — batched runner (6K set + 6L)
```

## Bug ditemukan saat verifikasi & diperbaiki

```text
Verifikasi HTTP independen oleh Lead Agent menemukan bug yang lolos dari PHP
test suite (test render via harness, bukan HTTP nyata):

- gejala: "Warning: Undefined variable $allPeriods in import.php on line 142"
  + tabel "Daftar Periode Penilaian" render KOSONG (4 periode real tak tampil)
- akar: ImportController::index() hanya mengirim 'periods' (subset non-legacy);
  view memakai $allPeriods untuk partial
- fix (1 baris): tambah 'allPeriods' => $allPeriods, ke renderWithLayout
  di ImportController.php:44. Dikerjakan Code-Specialist (deleg_361664fc),
  diverifikasi ulang oleh Lead Agent via HTTP nyata
- pelajaran: render view via harness test TIDAK menangkap undefined-variable
  warning. Verifikasi HTTP nyata wajib untuk perubahan view
```

## Hasil test

```text
phase6l_admin_ux_test.php            66/66 PASS
phase6k_auto_periode_test.php        54/54 PASS (auto-detect tak terpengaruh)

Regression 27 suite (22 PHP + 5 Python HTTP) semua PASS:
  db_connect OK, auth_service OK, password_verify OK,
  saw_engine 17/17, saw_service 12/12, saw_v2 21/21, saw_deep_verify 8/8,
  workflow_v2 34/34, phase6b 35/35, 6c 19/19, 6d 30/30, 6e 49/49, 6f 52/52,
  6g 33/33, 6h 40/40 + 51/51 HTTP, 6i 51/51 + 41/41 HTTP, 6j 55/55 + 13/13 HTTP,
  6k 54/54, 6l 66/66, periode_validation 35/35, operational_calculator 66/66,
  excel_import 40/40, laporan 24/24, owner_ui 41/41.
```

## Verifikasi UI (HTTP nyata, Lead Agent)

```text
GET /admin/import
  HTTP 200, 0 "Warning" string
  sidebar: "Kelola Periode V2" gone
  main UI: "Tambah Periode" gone
  "Opsi lanjutan" (manual create) masih ada
  Daftar Periode table: 4 kode lengkap
    LEGACY-2026-08  + LEGACY-2026-09  + 2026-Q3  + Q1-2026
  legacy rows: 2x "Read-only" (0 action button)
  Q1-2026: "Terkonfirmasi"
  empty-state string: absent (0 match)
GET /admin/periode         200 (backward compat)
GET /admin/periode/create  200 (backward compat)
Owner sidebar: Ranking nav present (tidak berubah)
```

## Invariant yang dijaga

```text
- "Kelola Periode V2" hanya dihapus dari sidebar, bukan dari Router
- legacy tb_penilaian=12, tb_hasil=12 tetap
- ids 1/2/24/25 tidak tersentuh
- baseline 22/22 restored; 4 periode (1, 2, 24, 25); 0 residue
- SawEngineV2.php / SawServiceV2.php byte-identical (untracked)
- Owner workflow tidak berubah (sidebar, views, routes, controllers)
- tidak ada perubahan database schema
- php -l clean di semua 8 file berubah
- tidak ada dependency baru, tidak ada git commit
```

## Definition of Done

```text
[x] menu "Kelola Periode V2" dihapus dari sidebar admin
[x] /admin/import jadi pusat workflow periode (tabel di section utama)
[x] daftar periode pindah ke /admin/import (visible, bukan collapsed)
[x] "+ Tambah Periode" hilang dari UI utama
[x] tb_periode_penilaian + route/backend lama dipertahankan (GET 200)
[x] auto-detect Phase 6K tetap berfungsi (6K 54/54 rerun)
[x] status & tombol aksi periode dirapikan
[x] tidak ada duplikasi logika tombol (partial shared)
[x] SAW, Owner workflow, database schema, legacy data tidak berubah
[x] php -l clean di semua file berubah
[x] phase6l 66/66 + regression 26 suite lainnya semua PASS
[x] verifikasi /admin/import via HTTP nyata (bukan harness saja)
[x] verifikasi upload Excel -> auto-detect -> import (6K guard aktif)
[x] legacy 12/12 unchanged
[x] tidak ada git commit
```

## Catatan

```text
- bug allPeriods (lihat di atas) ditemukan via verifikasi HTTP Lead Agent,
  bukan dari subagent. Fix satu baris via Code-Specialist (deleg_361664fc),
  re-verifikasi hijau
- test assertion mencari baris via <td class="font-bold text-gold">KODE</td>
  karena <option> list manual-periode di halaman import juga memuat setiap kode
- finding: kode 2026-Q2 tak punya row tb_periode_penilaian (row selesai adalah
  Q1-2026); branch reuse test 6L mengakomodasi ketiadaan ini

---

# 49. Phase 6M — Admin Import Workflow Polish

> Polish UI/UX. Status:

```text
DONE
```

## Tujuan

Polish workflow import Admin — fokus HANYA pada /admin/import. Upload Excel lebih modern
dan konsisten dengan tema black-gold ASENTRA, setiap langkah jelas:
pilih file -> deteksi periode -> import -> lihat hasil.

## Perubahan

```text
1. upload zone: native input -> styled dropzone black-gold, dashed -> solid has-file,
   drag/drop highlight, chip nama+ukuran file (#file_excel_name; dua zone: auto + explicit)
2. nama file tampil setelah dipilih (vanilla JS, zero deps)
3. panel #detected-periode: kode (gold), nama, rentang mulai -> selesai,
   badge created-vs-reused (PERIODE BARU DIBUAT / status live / PERIODE DIPILIH MANUAL),
   quarter source, filename asli. Persist di $_SESSION['detected_periode'],
   read-once di index() (pola sama dengan import_result)
4. validasi pre-import: client-side hints (ext .xlsx + ceiling 20MB);
   ringkasan per-row muncul dari hasil import
5. allow_partial: checked -> UNCHECKED (opt-in), label "(opsional, non-aktif secara
   default)" + kalimat risiko. Tetap fungsional (path ON & OFF teruji)
6. result card: 3 state result-success/partial/failed + label human + count row
   + error list styling
7. daftar periode: CSS tokens ganti hardcoded greys; data & logika tak diubah
```

## File yang berubah

```text
app/views/admin/import.php          rewrite: dropzone, panel detected-periode,
                                     result states, partial OFF, vanilla JS inline
app/controllers/ImportController.php +2 session block (detected_periode di path auto
                                     + explicit); index() kirim detectedPeriode +
                                     unset() read-once; dead line 126 dihapus
tests/phase6m_import_polish_test.php BARU 75/75
tests/run_phase6m_regression.sh     BARU — 23 PHP + 5 Python

TIDAK berubah: PeriodeDetector.php, ExcelImportService.php, Router.php
(diff vs HEAD = akumulasi route 6H-6L, bukan 6M), SawEngineV2.php, SawServiceV2.php,
owner views/routes, DB schema.
```

## Desain

```text
- panel detected-periode = source of truth APA yang ditarget import;
  result card = APA yang terjadi. Dipisah agar deteksi tidak tertukar outcome.
- session detected_periode ikut pola import_result/import_errors
  (set di store(), read-once di index())
- JS vanilla inline di kaki view, zero new deps, layout tak butuh page-script slot
```

## Hasil test

```text
phase6m_import_polish_test.php      75/75 PASS (idempotent — re-run 75/75)

Regression 28 suite (23 PHP + 5 Python HTTP) semua PASS:
  phase6m 75/75, phase6l 66/66, phase6k 54/54, 6j 55/55, 6h 40/40,
  6b 35/35, 6c 19/19, 6d 30/30, 6e 49/49, 6f 52/52, 6g 33/33,
  periode_validation 35/35, operational_calculator 66/66, excel_import 40/40,
  saw_engine 17/17, saw_service 12/12, saw_v2 21/21, saw_deep_verify 8/8,
  workflow_v2 34/34, db_connect/auth_service/password_verify OK,
  HTTP: 6h 51/51, 6i 41/41, laporan 24/24, owner_ui 41/41, 6j 13/13.
```

## Verifikasi UI (HTTP nyata, Lead Agent)

```text
GET /admin/import
  HTTP 200, 0 Warning / 0 Fatal error
  upload-zone class present          ok
  #file_excel_name hook present      ok
  allow_partial: UNCHECKED           ok
  opt-in label ("opsional") present  ok
  result-* state classes present     ok
  Daftar Periode table present       ok
  legacy rows: 2x "Read-only"        ok

POST /admin/import (upload dummy_q2_2026.xlsx)
  HTTP 200, redirect kembali
  detected panel rendered: "April - Juni 2026"  ok
  badge: PERIODE BARU DIBUAT / reused            ok
```

Upload via UI membuat 2026-Q2 (id 304) untuk verifikasi — dibersihkan setelahnya,
baseline 4 periode pulih.

## Invariant yang dijaga

```text
- SawEngineV2.php / SawServiceV2.php byte-identical (untracked, sha256 unchanged)
- Router.php: TIDAK ada route baru di 6M (diff vs HEAD = akumulasi 6H-6L)
- PeriodeDetector.php / ExcelImportService.php tidak diubah (6K auto-detect utuh)
- Owner workflow tidak berubah
- legacy tb_penilaian=12, tb_hasil=12 tetap
- ids 1/2/24/25 tidak tersentuh
- baseline 22/22 restored; 4 periode; 0 residue test period
- tidak ada perubahan database schema
- php -l clean di semua file berubah
- tidak ada dependency baru, tidak ada git commit
```

## Definition of Done

```text
[x] upload Excel lebih modern & konsisten black-gold ASENTRA
[x] nama file tampil setelah dipilih
[x] hasil auto-detect tampil jelas: kode, nama periode, rentang tanggal
[x] ringkasan validasi (client-side hints + per-row dari hasil import)
[x] "Izinkan Partial Import" jadi opt-in / default OFF
[x] feedback hasil import jelas: berhasil, gagal, partial
[x] Daftar Periode dirapikan tanpa mengubah data
[x] auto-detect Phase 6K dipertahankan (6K 54/54 rerun)
[x] backward compatibility id_periode eksplisit tetap jalan
[x] php -l clean di semua file berubah
[x] phase6m 75/75 + regression 27 suite lainnya semua PASS
[x] verifikasi HTTP nyata /admin/import + upload Excel -> detect -> import
[x] legacy 12/12 unchanged
[x] SAW, Owner workflow, database schema tidak berubah
[x] tidak ada git commit
```

## Known issue / needs confirmation

```text
- Server-side pre-import validation summary: SKIPPED (disengaja).
  ExcelImportService::import() tidak expose dry-run; pre-check sejati butuh
  ExcelValidator::validate() di method service baru. Sesuai YAGNI (no duplicated
  import logic), yang dibuat: client-side hints (filename/ext/size) + panel
  detected-periode + ringkasan per-row dari hasil import. Tambah route
  /admin/import/preview + reuse ExcelValidator HANYA jika admin meminta
  pre-flight check sungguhan.
```

## Catatan

```text
- panel detected-periode membedakan 3 badge: PERIODE BARU DIBUAT (auto-create),
  badge status live periode (reuse), PERIODE DIPILIH MANUAL (path explicit)
- dead line 126 ($_SESSION['import_result'] = $_SESSION['import_result'] ?? null;)
  dihapus saat 6M — no-op sejak 6K
- sisa upload HTTP dari verifikasi Lead Agent (id 304) dibersihkan manual;
  baseline 22/22 pulih

---

# 50. Phase 6N — Owner UX Cleanup

> Cleanup UI Owner + fix statistik. Status:

```text
DONE
```

## Tujuan

Menjadikan workflow V2 (/owner/assessment) sebagai **satu-satunya workflow penilaian
yang terlihat oleh Owner**. V1 (/owner/penilaian) TIDAK dihapus — disembunyikan dari
UI, tetap dapat diakses langsung sebagai legacy/backward compatibility.

## Latar belakang: bug statistik Dashboard Owner

```php
// SEBELUM
$latestPeriode = Penilaian::latestPeriode();          // = '2026-09' (string V1)
$activePenilaianPeriode = $latestPeriode ?? date('Y-m');
$dinilaiCount = Penilaian::countEvaluatedByPeriode($activePenilaianPeriode);  // = 2
$criteriaAvg = Penilaian::getCriteriaAverages($activePenilaianPeriode);       // dead var
```

Akar masalah (verifikasi DB + source):

```text
Penilaian::latestPeriode() = "SELECT periode FROM tb_penilaian ORDER BY periode DESC LIMIT 1"
tb_penilaian.periode berisi: '2026-08','2026-09' (V1 legacy, 12 baris)
                          + 'Q1-2026' (V2, 10 baris)
ORDER BY DESC pada string campuran -> '2026-09' (V1, hanya 2 baris).
Kartu menampilkan "2 dari 10" + label "September 2026" dari periode
yang TIDAK punya data V2.
```

## Perubahan

```text
1. SEMBUNYIKAN MENU V1 (app/views/layouts/app.php, owner nav block):
   - nav_item "Review Penilaian V2" (/owner/assessment) -> label "Penilaian Kinerja"
   - nav_item "Penilaian Kinerja" (/owner/penilaian)    -> dikomentari (tidak dihapus)
   - nav_item "Riwayat Penilaian" (/owner/riwayat-penilaian) -> dikomentari (V1 history)
   Hasil: tepat SATU menu Owner "Penilaian Kinerja" -> /owner/assessment

2. ALIHKAN CROSS-LINK DASHBOARD (app/views/owner/dashboard.php, 4 link):
   :28   btn-banner "Input Penilaian"     /owner/penilaian/create -> /owner/assessment
   :92   "Lihat Semua" (progress card)    /owner/penilaian        -> /owner/assessment
   :277  quick-action "Input Penilaian"   /owner/penilaian/create -> /owner/assessment
   :283  quick-action "Penilaian Kinerja" /owner/penilaian        -> /owner/assessment?mode=tabulasi

   Total 6 link V1 (4 dashboard + 2 sidebar): 4 dialihkan, 2 disembunyikan.

3. FIX STATISTIK DASHBOARD (app/controllers/OwnerDashboardController.php):
   - progress card driven by Hasil::latestPeriode() = 'Q1-2026' (V2)
   - evaluatedCount = count(Hasil::byPeriode('Q1-2026')) = 10
   - label via periodLabel() V2-aware: "Januari - Maret 2026"
   - dead $criteriaAvg + getCriteriaAverages() dihapus (verified unused di view;
     method tetap utuh — AdminDashboardController masih pakai)
   - TIDAK ada method model baru (data V2 sudah dihitung controller)

4. JUDGMENT CALL: quick-action "Penilaian Kinerja" -> /owner/assessment?mode=tabulasi
   (bukan polos) supaya tetap beda fungsi dari quick-action "Input Penilaian".
   Keduanya mendarat di V2, tak ada yang ke V1.
```

## File yang berubah

```text
app/views/layouts/app.php              label menu V2; 2 nav_item V1 dikomentari
app/views/owner/dashboard.php          4 cross-link; docblock $criteriaAvg dihapus;
                                       heading null-safe
app/controllers/OwnerDashboardController.php  progress pakai Hasil::latestPeriode();
                                       dead $criteriaAvg dihapus
tests/phase6n_owner_ux_test.php        BARU 53/53 (skenario a-j)
tests/phase6l_admin_ux_test.php        ekspektasi label stale -> "Penilaian Kinerja" (69/69)
tests/run_phase6n_regression.sh        BARU — 23 PHP + 5 Python

TIDAK berubah (byte-identical, diverifikasi):
  SawEngineV2.php, SawServiceV2.php, Penilaian.php, OwnerPenilaianController.php,
  Router.php, app/views/owner/penilaian_*.php, app/views/owner/riwayat_penilaian.php,
  Admin views/routes/controllers, DB schema.
```

## Hasil test

```text
phase6n_owner_ux_test.php   53/53 PASS (skenario a-j)
phase6l_admin_ux_test.php   69/69 PASS (diperbarui, sebelumnya 66/66)

Regression 29 suite (24 PHP + 5 Python HTTP) semua PASS:
  phase6n 53/53, phase6l 69/69, phase6m 75/75, phase6k 54/54, 6j 55/55, 6h 40/40,
  6b 35/35, 6c 19/19, 6d 30/30, 6e 49/49, 6f 52/52, 6g 33/33,
  periode_validation 35/35, operational_calculator 66/66, excel_import 40/40,
  saw_engine 17/17, saw_service 12/12, saw_v2 21/21, saw_deep_verify 8/8,
  workflow_v2 34/34, db_connect/auth_service/password_verify OK,
  HTTP: 6h 51/51, 6i 41/41, laporan 24/24, owner_ui 41/41, 6j 13/13.
```

## Verifikasi UI (HTTP nyata, Lead Agent)

```text
GET /owner/dashboard
  href '/owner/penilaian' count: 0            ok  (semua cross-link dialihkan)
  menu 'Penilaian Kinerja': present           ok
  label V2 'Januari - Maret 2026': present    ok
  '10 dari 10': present                       ok
  periode V1 (September/Agustus 2026): absent ok

GET /owner/assessment
  tabulasi C1/C2/C3: present                  ok  (V2 utuh)
  href '/owner/penilaian': 0                  ok

BACKWARD COMPAT (direct access, route tetap ada):
  GET /owner/penilaian          200, 0 fatal, 0 warning
  GET /owner/penilaian/create   200, 0 fatal, 0 warning
  GET /owner/riwayat-penilaian  200, 0 fatal, 0 warning
```

## Invariant yang dijaga

```text
- SawEngineV2.php / SawServiceV2.php byte-identical (untracked)
- Penilaian.php model byte-identical (method V2-critical utuh)
- OwnerPenilaianController.php byte-identical (tak tersentuh, mtime 2026-09-06)
- Router.php byte-identical (7 route V1 tetap terdaftar)
- Admin workflow tidak berubah
- Owner workflow V2 (/owner/assessment) utuh + tabulasi
- legacy tb_penilaian=12, tb_hasil=12 tetap
- ids 1/2/24/25 tidak tersentuh
- baseline 22/22; 4 periode (ids 1,2,24,25); 0 residue test period
- tidak ada perubahan database schema
- php -l clean di semua file berubah
- tidak ada dependency baru, tidak ada git commit
```

## Definition of Done

```text
[x] menu workflow V1 /owner/penilaian disembunyikan dari sidebar Owner
[x] seluruh cross-link Dashboard Owner dialihkan ke /owner/assessment
[x] label "Penilaian Kinerja" dipakai untuk menu utama V2
[x] route, controller, view, method V1 TIDAK dihapus
[x] database tidak diubah
[x] SAW V2 tidak diubah
[x] Admin workflow tidak diubah
[x] legacy data tetap dipertahankan (12/12)
[x] statistik Dashboard Owner diperiksa + diperbaiki ke periode/data V2
[x] tidak ada statistik lain diubah tanpa keperluan
[x] php -l semua file berubah
[x] regression lengkap dijalankan (29 suite hijau)
[x] verifikasi HTTP nyata Dashboard Owner + /owner/assessment
[x] tidak ada link UI aktif menuju /owner/penilaian (count = 0)
[x] /owner/penilaian tetap 200 via akses langsung (backward compat)
[x] periode V2 tampil benar di Dashboard ("Januari - Maret 2026", 10/10)
[x] legacy 12/12 unchanged
[x] tidak ada git commit
```

## Catatan

```text
- Audit V1 lengkap (7 route, dependency model, risiko penghapusan, rencana Opsi A/B)
  di concepts/asentra_v1_owner_audit.md. Phase 6N = implementasi Opsi A dari rencana.
- Menu "Riwayat Penilaian" (/owner/riwayat-penilaian) juga disembunyikan: V1 history
  yang tampilkan string YYYY-MM. Owner tetap punya /owner/riwayat (ranking history)
  + /owner/laporan untuk kebutuhan riwayat.
- tests/phase6l_admin_ux_test.php diperbarui: ekspektasi "Review Penilaian V2"
  jadi "Penilaian Kinerja" (assertion stale setelah konsolidasi menu).
- $criteriaAvg diverifikasi dead di view sebelum dihapus (grep: hanya docblock).




