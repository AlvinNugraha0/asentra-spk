# PRD --- Sistem Pendukung Keputusan Penilaian Kinerja Teknisi Lapangan ASENTRA

**Dokumen:** Product Requirements Document + AI Coding Agent
Specification\
**Versi:** 1.0\
**Status:** Baseline untuk implementasi\
**Basis utama:** Proposal Skripsi
`Proposal_Skripsi_Alvin_Revisi_02.docx`\
**Objek penelitian:** CV Arsitek Semesta Nusantara (ASENTRA)\
**Platform:** Web\
**Metode SPK:** Simple Additive Weighting (SAW)\
**Metode pengembangan penelitian:** Waterfall

------------------------------------------------------------------------

## 1. Tujuan Dokumen

Dokumen ini menjadi **source of truth** bagi AI coding agent dalam
membangun aplikasi Sistem Pendukung Keputusan (SPK) untuk penilaian
kinerja teknisi lapangan pada CV Arsitek Semesta Nusantara.

AI agent harus menggunakan dokumen ini sebagai acuan utama dalam
menentukan:

-   fitur aplikasi;
-   role dan hak akses;
-   alur bisnis;
-   struktur data;
-   aturan validasi;
-   proses kuantifikasi nilai;
-   algoritma SAW;
-   hasil ranking;
-   laporan;
-   pengujian;
-   batasan implementasi.

AI agent **tidak boleh mengarang business rule baru** yang mengubah
substansi penelitian.

Jika terdapat requirement yang belum ditentukan secara eksplisit dalam
dokumen ini, agent harus memilih solusi teknis paling sederhana yang
tidak mengubah metodologi penelitian. Jika keputusan tersebut berpotensi
mengubah hasil penelitian, agent harus meminta klarifikasi sebelum
menerapkannya.

------------------------------------------------------------------------

# 2. Product Overview

Aplikasi adalah sistem internal berbasis web untuk membantu manajemen
ASENTRA melakukan evaluasi dan pemeringkatan kinerja teknisi lapangan.

Sistem mengubah hasil observasi kualitatif menjadi rating numerik,
kemudian memproses rating tersebut menggunakan algoritma Simple Additive
Weighting (SAW) untuk menghasilkan nilai preferensi dan ranking teknisi.

Tujuan akhirnya adalah menyediakan instrumen evaluasi yang lebih
terstruktur, terukur, terdokumentasi, dan membantu manajemen dalam
mengambil keputusan terkait prioritas teknisi.

Proposal menetapkan bahwa sistem menggunakan PHP, MySQL, HTML, CSS,
JavaScript, dan XAMPP sebagai lingkungan pengembangan lokal.

------------------------------------------------------------------------

# 3. Latar Belakang Masalah

Evaluasi kinerja teknisi lapangan pada objek penelitian masih bersifat
konvensional dan subjektif. Proses manual menyebabkan manajemen
kesulitan memperoleh data historis kinerja yang terstruktur dan
berpotensi menimbulkan bias dalam pengambilan keputusan.

Aplikasi dibangun untuk mengubah proses tersebut menjadi proses
terkomputerisasi yang:

1.  menyimpan data teknisi secara terpusat;
2.  menggunakan kriteria penilaian yang telah ditentukan;
3.  mengubah penilaian kualitatif menjadi nilai numerik;
4.  menghitung nilai menggunakan SAW;
5.  menghasilkan ranking;
6.  menyediakan hasil yang terdokumentasi;
7.  menyediakan laporan yang dapat dicetak.

------------------------------------------------------------------------

# 4. Tujuan Produk

## 4.1 Tujuan Utama

Membangun SPK berbasis web yang dapat membantu manajemen ASENTRA
mengevaluasi dan meranking kinerja teknisi lapangan menggunakan
algoritma SAW.

## 4.2 Tujuan Fungsional

Sistem harus mampu:

-   melakukan autentikasi pengguna;
-   membedakan hak akses Admin dan Owner;
-   mengelola data teknisi;
-   mengelola kriteria dan bobot;
-   memasukkan nilai evaluasi;
-   menyimpan histori penilaian;
-   mengubah rating menjadi data kuantitatif;
-   melakukan perhitungan SAW;
-   menghasilkan ranking;
-   menampilkan detail perhitungan;
-   menghasilkan laporan siap cetak.

------------------------------------------------------------------------

# 5. Scope

## 5.1 In Scope

-   Authentication/login.
-   Role-based authorization.
-   Dashboard.
-   Manajemen data teknisi.
-   Manajemen kriteria.
-   Manajemen bobot.
-   Input penilaian teknisi.
-   Penyimpanan periode penilaian.
-   Perhitungan SAW.
-   Normalisasi.
-   Nilai preferensi.
-   Ranking teknisi.
-   Detail perhitungan.
-   Riwayat hasil.
-   Laporan/print.
-   Black Box Testing.
-   Verifikasi hasil SAW dengan perhitungan manual.

## 5.2 Out of Scope

Jangan membangun fitur berikut sebagai bagian dari scope penelitian:

-   Payroll/penggajian.
-   Akuntansi.
-   Inventory management penuh.
-   Manajemen proyek penuh.
-   Absensi sebagai sistem tersendiri.
-   Rekrutmen.
-   Penilaian staf administrasi.
-   Mobile application native.
-   Chat internal.
-   Modul keuangan perusahaan.
-   Sistem publik untuk pelanggan.

Proposal secara eksplisit membatasi sistem pada penilaian kinerja
teknisi dan tidak mencakup modul penggajian atau sistem akuntansi
perusahaan secara menyeluruh.

------------------------------------------------------------------------

# 6. Target User

## 6.1 Admin Kantor

Admin adalah pengguna operasional.

Tanggung jawab:

-   login;
-   mengelola teknisi;
-   mengelola kriteria dan bobot;
-   memasukkan nilai evaluasi;
-   melihat data penilaian.

## 6.2 Owner / Pemilik

Owner adalah pengguna pengambil keputusan.

Tanggung jawab:

-   login;
-   melihat hasil evaluasi;
-   menjalankan proses SAW;
-   melihat ranking;
-   melihat detail hasil;
-   mencetak laporan.

------------------------------------------------------------------------

# 7. Permission Matrix

  Fitur                Admin   Owner
  ------------------ ------- -------
  Login                    ✓       ✓
  Logout                   ✓       ✓
  Dashboard                ✓       ✓
  Lihat Teknisi            ✓       ✓
  Tambah Teknisi           ✓      \-
  Edit Teknisi             ✓      \-
  Hapus Teknisi            ✓      \-
  Kelola Kriteria          ✓      \-
  Kelola Bobot             ✓      \-
  Input Penilaian          ✓      \-
  Lihat Penilaian          ✓       ✓
  Proses SAW              \-       ✓
  Lihat Ranking           \-       ✓
  Lihat Detail SAW        \-       ✓
  Riwayat Ranking         \-       ✓
  Cetak Laporan           \-       ✓

**Catatan:** Hak akses harus diperiksa di backend/server-side, bukan
hanya dengan menyembunyikan menu di frontend.

------------------------------------------------------------------------

# 8. Business Rules

## BR-01 --- Objek Penilaian

Sistem mengevaluasi teknisi lapangan ASENTRA.

Proposal menggunakan 10 teknisi aktif sebagai populasi/subjek
penelitian.

## BR-02 --- Kriteria

Sistem menggunakan tepat tiga kriteria penelitian:

-   C1 --- Kedisiplinan
-   C2 --- Kualitas Hasil Kerja
-   C3 --- Tanggung Jawab

## BR-03 --- Jenis Kriteria

Ketiga kriteria merupakan **Benefit**.

Artinya nilai yang lebih besar lebih baik.

## BR-04 --- Bobot

Bobot baseline:

  Kode   Kriteria               Atribut     Bobot
  ------ ---------------------- --------- -------
  C1     Kedisiplinan           Benefit      0.30
  C2     Kualitas Hasil Kerja   Benefit      0.40
  C3     Tanggung Jawab         Benefit      0.30

Total bobot:

`0.30 + 0.40 + 0.30 = 1.00`

atau 100%.

## BR-05 --- Rating

Rating hanya menggunakan skala:

`1, 2, 3, 4`

## BR-06 --- Teknisi Inactive

Teknisi berstatus inactive tidak boleh menjadi alternatif baru dalam
penilaian aktif.

## BR-07 --- Kelengkapan Penilaian

Setiap teknisi yang diproses harus memiliki nilai C1, C2, dan C3.

## BR-08 --- Periode

Penilaian harus terkait dengan periode evaluasi agar histori dapat
dibedakan.

Format periode yang direkomendasikan:

`YYYY-MM`

Contoh:

`2026-08`

## BR-09 --- Ranking

Ranking diurutkan berdasarkan nilai preferensi SAW terbesar ke terkecil.

## BR-10 --- Penyimpanan Hasil

Hasil perhitungan yang telah diproses harus dapat direkam sebagai
histori sehingga laporan periode sebelumnya tetap dapat ditelusuri.

## BR-11 --- Tidak Ada Penilaian Ganda

Untuk satu teknisi pada satu periode, hanya boleh terdapat satu record
penilaian aktif.

Jika perlu diperbaiki, gunakan mekanisme edit/update terhadap record
tersebut.

## BR-12 --- Bobot Tidak Boleh Invalid

Sistem tidak boleh menyimpan konfigurasi bobot apabila total bobot tidak
sama dengan 1.00/100%.

------------------------------------------------------------------------

# 9. Kriteria dan Rating Resmi

## 9.1 C1 --- Kedisiplinan

Bobot: 30%\
Atribut: Benefit

Sub-indikator: kepatuhan jam hadir di lokasi proyek, ketepatan waktu
penyelesaian target harian, dan kepatuhan terhadap jadwal operasional.

  -----------------------------------------------------------------------
                                    Rating Keterangan
  ---------------------------------------- ------------------------------
                                         4 Sangat Baik --- Selalu tepat
                                           waktu dan patuh pada jadwal
                                           operasional

                                         3 Baik --- Sering tepat waktu
                                           dengan toleransi keterlambatan
                                           minim

                                         2 Cukup --- Terkadang terlambat
                                           namun target pekerjaan harian
                                           selesai

                                         1 Kurang --- Sering terlambat di
                                           lokasi proyek dan abai pada
                                           jadwal
  -----------------------------------------------------------------------

## 9.2 C2 --- Kualitas Hasil Kerja

Bobot: 40%\
Atribut: Benefit

Sub-indikator: kerapian pengerjaan fisik, presisi pengukuran/pemasangan,
kekuatan struktural, dan kesesuaian hasil pengerjaan dengan desain.

  -----------------------------------------------------------------------
                                    Rating Keterangan
  ---------------------------------------- ------------------------------
                                         4 Sangat Baik --- Hasil
                                           pengerjaan sangat rapi,
                                           presisi, dan secara struktural
                                           kokoh

                                         3 Baik --- Hasil pengerjaan rapi
                                           dan memenuhi standar kelayakan
                                           perusahaan

                                         2 Cukup --- Hasil pekerjaan
                                           cukup, namun memerlukan
                                           beberapa revisi minor

                                         1 Kurang --- Hasil tidak rapi,
                                           banyak cacat fisik, dan tidak
                                           sesuai standar
  -----------------------------------------------------------------------

## 9.3 C3 --- Tanggung Jawab

Bobot: 30%\
Atribut: Benefit

Sub-indikator: pemeliharaan alat kerja perusahaan, efisiensi penggunaan
material proyek, dan inisiatif di lapangan.

  -----------------------------------------------------------------------
                                    Rating Keterangan
  ---------------------------------------- ------------------------------
                                         4 Sangat Baik --- Sangat peduli
                                           pada efisiensi material dan
                                           merawat alat dengan baik

                                         3 Baik --- Bertanggung jawab
                                           penuh dalam merawat dan
                                           menjaga peralatan kerja

                                         2 Cukup --- Kurang inisiatif
                                           dalam mengelola sisa material
                                           secara efisien

                                         1 Kurang --- Sering merusak alat
                                           kerja atau membuang material
                                           tanpa perhitungan
  -----------------------------------------------------------------------

------------------------------------------------------------------------

# 10. Dataset Penelitian Baseline

Data berikut berasal dari simulasi proposal dan dapat digunakan sebagai
seed/demo serta golden test.

  Kode   Teknisi             C1   C2   C3
  ------ ----------------- ---- ---- ----
  A1     Toni                 4    4    4
  A2     Apip                 3    4    3
  A3     Agus Supriyanto      2    3    2
  A4     Rahmat Hidayat       4    3    4
  A5     Ahmad Sahudin        3    3    2
  A6     Aris                 4    4    3
  A7     IMADE                2    4    2
  A8     Asep                 1    2    4
  A9     Wanto                4    3    2
  A10    Heri                 3    3    3

**Penting:** data ini disebut sebagai contoh/simulasi dalam proposal.
Data implementasi penelitian final harus dapat diganti/validasi
berdasarkan data perusahaan.

------------------------------------------------------------------------

# 11. Golden Expected Result

Dengan dataset baseline dan bobot:

`W = [0.30, 0.40, 0.30]`

serta seluruh kriteria Benefit, hasil ranking yang tercantum dalam
proposal adalah:

    Rank Kode   Teknisi              Skor
  ------ ------ ----------------- -------
       1 A1     Toni                1.000
       2 A6     Aris                0.925
       3 A4     Rahmat Hidayat      0.900
       4 A2     Apip                0.850
       5 A9     Wanto               0.750
       6 A10    Heri                0.750
       7 A7     IMADE               0.700
       8 A5     Ahmad Sahudin       0.675
       9 A3     Agus Supriyanto     0.600
      10 A8     Asep                0.575

**Golden test requirement:** implementasi harus menghasilkan nilai dan
urutan yang konsisten dengan tabel baseline di atas.

**Catatan penting:** terdapat skor yang sama pada Wanto dan Heri
(0.750). Proposal tidak menetapkan aturan tie-breaker tambahan. Agent
tidak boleh mengarang aturan tie-breaker yang mengubah metodologi. Jika
ranking perlu dibedakan secara deterministik di UI/database, gunakan
aturan teknis yang netral dan dokumentasikan bahwa kedua alternatif
memiliki skor SAW yang sama.

------------------------------------------------------------------------

# 12. Algoritma SAW

## 12.1 Input

SAW menerima:

-   daftar alternatif teknisi;
-   nilai C1;
-   nilai C2;
-   nilai C3;
-   bobot kriteria;
-   atribut kriteria.

## 12.2 Matriks Keputusan

Bentuk:

``` text
X = [xij]
```

Baris = alternatif/teknisi.\
Kolom = kriteria.

## 12.3 Normalisasi

Karena C1, C2, dan C3 seluruhnya Benefit:

``` text
rij = xij / max(xj)
```

dengan:

-   `xij` = nilai alternatif i pada kriteria j;
-   `max(xj)` = nilai maksimum pada kriteria j;
-   `rij` = nilai hasil normalisasi.

## 12.4 Nilai Preferensi

Gunakan:

``` text
Vi = Σ(Wj × rij)
```

dengan:

-   `Vi` = nilai preferensi alternatif i;
-   `Wj` = bobot kriteria j;
-   `rij` = nilai normalisasi alternatif i pada kriteria j.

## 12.5 Ranking

Setelah semua `Vi` diperoleh:

``` text
ORDER BY Vi DESC
```

Alternatif dengan nilai preferensi tertinggi menjadi ranking terbaik.

## 12.6 Jangan Mengubah Formula

Agent tidak boleh:

-   mengganti SAW dengan TOPSIS;
-   mengganti SAW dengan SMART;
-   menggunakan perkalian ala Weighted Product;
-   menambahkan normalisasi yang tidak ditentukan;
-   mengubah bobot baseline tanpa instruksi;
-   membalik Benefit menjadi Cost.

------------------------------------------------------------------------

# 13. User Flow

## 13.1 Flow Admin

``` text
Login
  ↓
Validasi credentials
  ↓
Dashboard Admin
  ↓
Pilih modul
  ├── Data Teknisi
  ├── Kriteria & Bobot
  ├── Penilaian
  └── Riwayat
```

### Input Penilaian

``` text
Penilaian
  ↓
Pilih periode
  ↓
Pilih teknisi
  ↓
Pilih rating C1
  ↓
Pilih rating C2
  ↓
Pilih rating C3
  ↓
Validasi
  ↓
Simpan
  ↓
Konfirmasi
```

## 13.2 Flow Owner

``` text
Login
  ↓
Dashboard Owner
  ↓
Pilih periode
  ↓
Hasil SAW
  ↓
Proses Perhitungan
  ↓
Ambil data penilaian
  ↓
Bentuk matriks X
  ↓
Normalisasi R
  ↓
Hitung Vi
  ↓
Urutkan ranking
  ↓
Simpan hasil
  ↓
Tampilkan ranking
  ↓
Detail / Print
```

------------------------------------------------------------------------

# 14. Functional Requirements

## FR-01 Authentication

Sistem harus menyediakan login untuk Admin dan Owner.

Input:

-   username;
-   password.

Output sukses:

-   session dibuat;
-   user diarahkan sesuai role.

Output gagal:

-   pesan error;
-   tetap di halaman login.

## FR-02 Logout

User dapat mengakhiri session.

## FR-03 Role Authorization

Sistem harus menolak akses terhadap route yang tidak sesuai role.

Contoh:

``` text
Admin → tidak boleh menjalankan endpoint Owner.
Owner → tidak boleh melakukan CRUD yang hanya dimiliki Admin.
```

## FR-04 Dashboard Admin

Dashboard menampilkan ringkasan data operasional yang relevan.

Minimal:

-   jumlah teknisi aktif;
-   jumlah penilaian;
-   ringkasan kriteria/bobot;
-   penilaian terbaru.

## FR-05 Data Teknisi

Admin dapat:

-   melihat daftar teknisi;
-   menambah;
-   mengubah;
-   menghapus;
-   mengubah status aktif/nonaktif.

Field minimal:

-   ID;
-   kode teknisi;
-   nama;
-   status;
-   keterangan;
-   created_at;
-   updated_at.

## FR-06 Kriteria dan Bobot

Admin dapat melihat dan mengubah konfigurasi kriteria/bobot sesuai
instruksi manajemen.

Baseline:

``` text
C1 = 0.30
C2 = 0.40
C3 = 0.30
```

Sistem harus memvalidasi total bobot = 1.00.

## FR-07 Input Penilaian

Admin dapat memasukkan:

-   periode;
-   teknisi;
-   rating C1;
-   rating C2;
-   rating C3.

Rating harus berasal dari skala 1--4.

## FR-08 Riwayat Penilaian

Sistem menyimpan histori penilaian berdasarkan periode.

Fitur minimal:

-   filter periode;
-   lihat teknisi;
-   lihat nilai C1/C2/C3;
-   lihat user yang membuat data;
-   lihat tanggal input.

## FR-09 Proses SAW

Owner dapat memilih periode dan menjalankan perhitungan SAW.

Sistem harus:

1.  mengambil data penilaian;
2.  memastikan data lengkap;
3.  membentuk matriks X;
4.  mencari nilai maksimum tiap kriteria;
5.  melakukan normalisasi;
6.  mengalikan dengan bobot;
7.  menjumlahkan nilai;
8.  menghasilkan Vi;
9.  mengurutkan ranking;
10. menyimpan hasil.

## FR-10 Hasil Ranking

Owner dapat melihat:

-   ranking;
-   kode teknisi;
-   nama teknisi;
-   nilai C1/C2/C3;
-   nilai preferensi;
-   status/indikator ranking.

## FR-11 Detail Perhitungan

Owner dapat melihat transparansi proses:

``` text
Nilai asli
→ nilai maksimum
→ normalisasi
→ bobot
→ kontribusi setiap kriteria
→ nilai akhir
→ ranking
```

Fitur ini penting untuk verifikasi akademik dan demonstrasi proses SAW.

## FR-12 Laporan

Owner dapat mencetak laporan evaluasi.

Laporan minimal:

-   identitas perusahaan;
-   judul laporan;
-   periode;
-   bobot;
-   tabel ranking;
-   skor akhir;
-   tanggal laporan.

------------------------------------------------------------------------

# 15. Page Specification

Struktur route yang direkomendasikan:

``` text
/login
/logout

/admin/dashboard
/admin/teknisi
/admin/teknisi/create
/admin/teknisi/{id}/edit
/admin/kriteria
/admin/penilaian
/admin/penilaian/create
/admin/penilaian/{id}/edit
/admin/riwayat

/owner/dashboard
/owner/ranking
/owner/ranking/detail/{id}
/owner/riwayat
/owner/laporan/{periode}
```

Jika implementasi PHP tidak menggunakan framework, struktur URL boleh
disesuaikan selama fungsi dan authorization tetap sama.

------------------------------------------------------------------------

# 16. UI/UX Requirements

## 16.1 Prinsip

-   sederhana;
-   profesional;
-   responsif;
-   mudah digunakan pengguna internal;
-   tabel mudah dibaca;
-   form tidak membingungkan;
-   feedback sukses/error jelas.

## 16.2 Login

Komponen:

-   logo/nama ASENTRA;
-   username;
-   password;
-   tombol Login;
-   pesan error.

## 16.3 Data Teknisi

Komponen:

-   search;
-   tabel;
-   tombol tambah;
-   edit;
-   delete;
-   status badge;
-   pagination jika diperlukan.

## 16.4 Form Penilaian

Gunakan dropdown/radio/select untuk rating 1--4 agar Admin tidak
memasukkan angka sembarang.

Contoh:

``` text
Kedisiplinan
[ 4 - Sangat Baik ]

Kualitas Hasil Kerja
[ 3 - Baik ]

Tanggung Jawab
[ 4 - Sangat Baik ]
```

## 16.5 Ranking

Tampilkan:

``` text
Rank | Teknisi | C1 | C2 | C3 | Nilai SAW
```

Gunakan format skor konsisten, misalnya 3 angka desimal untuk nilai SAW.

------------------------------------------------------------------------

# 17. Database Specification

Proposal menetapkan lima entitas utama:

-   User;
-   Teknisi;
-   Kriteria;
-   Penilaian;
-   Hasil_SAW.

Implementasi database harus tetap merepresentasikan lima entitas
tersebut.

## 17.1 tb_user

``` text
id
username
password
nama
role
status
created_at
updated_at
```

Constraints:

-   `id` primary key;
-   `username` unique;
-   `role` hanya `admin` atau `owner`;
-   password harus disimpan menggunakan password hashing.

## 17.2 tb_teknisi

``` text
id
kode_teknisi
nama
status
keterangan
created_at
updated_at
```

Constraints:

-   `id` primary key;
-   `kode_teknisi` unique;
-   status `active`/`inactive`.

## 17.3 tb_kriteria

``` text
id
kode
nama_kriteria
atribut
bobot
deskripsi
created_at
updated_at
```

Constraints:

-   kode unique;
-   atribut `benefit` untuk baseline;
-   bobot numerik;
-   total bobot aktif harus 1.00.

## 17.4 tb_penilaian

``` text
id
teknisi_id
periode
c1
c2
c3
created_by
created_at
updated_at
```

Foreign key:

``` text
teknisi_id → tb_teknisi.id
created_by → tb_user.id
```

Constraints:

-   C1, C2, C3 integer 1--4;
-   teknisi harus aktif ketika penilaian baru dibuat;
-   kombinasi teknisi + periode harus unik untuk penilaian aktif.

## 17.5 tb_hasil

Minimal:

``` text
id
penilaian_id
teknisi_id
periode
nilai_c1_normalisasi
nilai_c2_normalisasi
nilai_c3_normalisasi
kontribusi_c1
kontribusi_c2
kontribusi_c3
nilai_preferensi
ranking
created_at
```

Foreign key:

``` text
penilaian_id → tb_penilaian.id
teknisi_id → tb_teknisi.id
```

**Catatan:** field `kontribusi_c1/c2/c3` merupakan rancangan engineering
untuk meningkatkan transparansi detail perhitungan. Jika ingin menjaga
schema seminimal mungkin, field tersebut boleh dihitung saat membaca
hasil, tetapi nilai final dan data yang diperlukan untuk audit harus
tetap tersedia.

------------------------------------------------------------------------

# 18. Database Relationship

Relasi konseptual:

``` text
tb_user
   │
   ├───────────────┐
   │               │
   ▼               ▼
tb_penilaian    tb_kriteria
   │
   │
   ▼
tb_teknisi
   │
   ▼
tb_hasil
```

Relasi yang lebih tepat secara data:

``` text
tb_user
  1 ─────── N
tb_penilaian

tb_teknisi
  1 ─────── N
tb_penilaian

tb_penilaian
  1 ─────── N
tb_hasil
```

`tb_kriteria` menjadi master parameter yang digunakan oleh engine SAW.

------------------------------------------------------------------------

# 19. Backend Architecture

Implementasi harus memisahkan minimal:

``` text
Presentation/UI
      ↓
Controller / Request Handler
      ↓
Business Logic
      ↓
SAW Service
      ↓
Data Access
      ↓
MySQL
```

Logika SAW **tidak boleh bercampur dengan HTML/template**.

Disarankan membuat service khusus:

``` text
SawService
```

Tanggung jawab:

-   menerima data alternatif;
-   mengambil bobot;
-   melakukan normalisasi;
-   menghitung Vi;
-   membuat ranking;
-   mengembalikan hasil terstruktur.

------------------------------------------------------------------------

# 20. Struktur Project yang Direkomendasikan

Jika menggunakan PHP native dengan pola MVC sederhana:

``` text
project/
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── TeknisiController.php
│   │   ├── KriteriaController.php
│   │   ├── PenilaianController.php
│   │   └── SawController.php
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Teknisi.php
│   │   ├── Kriteria.php
│   │   ├── Penilaian.php
│   │   └── HasilSaw.php
│   │
│   ├── services/
│   │   └── SawService.php
│   │
│   └── views/
│       ├── auth/
│       ├── admin/
│       └── owner/
│
├── config/
│   └── database.php
│
├── public/
│   ├── css/
│   ├── js/
│   └── assets/
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
└── README.md
```

Struktur boleh disesuaikan jika agent menggunakan framework PHP, tetapi
fungsi, database, role, dan algoritma tetap harus mengikuti PRD.

------------------------------------------------------------------------

# 21. Validation Rules

## Authentication

-   username wajib;
-   password wajib;
-   credentials harus diverifikasi;
-   session wajib dibuat setelah login berhasil.

## Teknisi

-   nama wajib;
-   kode teknisi wajib;
-   kode teknisi unik;
-   status valid.

## Kriteria

-   kode wajib;
-   nama wajib;
-   bobot wajib;
-   bobot tidak boleh negatif;
-   total bobot harus 1.00.

## Penilaian

-   teknisi wajib dipilih;
-   periode wajib;
-   C1 wajib 1--4;
-   C2 wajib 1--4;
-   C3 wajib 1--4;
-   satu teknisi tidak boleh memiliki penilaian aktif ganda pada periode
    yang sama.

## SAW

Sistem tidak boleh menjalankan ranking jika:

-   tidak ada data penilaian;
-   ada penilaian yang tidak lengkap;
-   bobot invalid;
-   kriteria yang dibutuhkan tidak tersedia.

------------------------------------------------------------------------

# 22. Error Handling

Gunakan pesan yang mudah dipahami pengguna.

Contoh:

``` text
Username atau password salah.
Anda tidak memiliki akses ke halaman ini.
Data teknisi berhasil disimpan.
Data penilaian berhasil disimpan.
Nilai penilaian harus berada pada skala 1–4.
Total bobot harus berjumlah 100%.
Penilaian untuk teknisi pada periode tersebut sudah tersedia.
Data penilaian belum lengkap sehingga SAW belum dapat diproses.
Perhitungan SAW berhasil dilakukan.
```

Jangan menampilkan stack trace, SQL error, password, atau informasi
sensitif kepada user.

------------------------------------------------------------------------

# 23. Security Requirements

Minimal:

1.  Password menggunakan hashing.
2.  Session digunakan untuk authentication.
3.  Semua halaman internal membutuhkan authentication.
4.  Authorization diperiksa server-side.
5.  Input user divalidasi server-side.
6.  Query database menggunakan prepared statement/parameterized query.
7.  Output HTML di-escape untuk mencegah XSS.
8.  Form POST sensitif memiliki perlindungan CSRF jika arsitektur
    memungkinkan.
9.  Jangan menyimpan password plaintext.
10. Logout harus menghapus/menginvalidasi session.

------------------------------------------------------------------------

# 24. SAW Service Contract

Secara konseptual:

``` text
calculateSAW(
    alternatives,
    criteria,
    weights
)
```

Input contoh:

``` text
alternatives = [
    {
        technician_id: 1,
        c1: 4,
        c2: 4,
        c3: 4
    }
]
```

Criteria:

``` text
C1 = benefit
C2 = benefit
C3 = benefit
```

Weights:

``` text
C1 = 0.30
C2 = 0.40
C3 = 0.30
```

Output:

``` text
[
    {
        technician_id: 1,
        normalized: {
            c1: 1.0,
            c2: 1.0,
            c3: 1.0
        },
        score: 1.0,
        rank: 1
    }
]
```

------------------------------------------------------------------------

# 25. Detail Perhitungan yang Harus Bisa Ditelusuri

Untuk setiap teknisi, sistem sebaiknya mampu menunjukkan:

``` text
Nilai asli
      ↓
Nilai maksimum per kriteria
      ↓
Normalisasi
      ↓
Normalisasi × bobot
      ↓
Penjumlahan
      ↓
Nilai preferensi
      ↓
Ranking
```

Contoh Toni:

``` text
C1 = 4
C2 = 4
C3 = 4

Max C1 = 4
Max C2 = 4
Max C3 = 4

R1 = 4/4 = 1
R2 = 4/4 = 1
R3 = 4/4 = 1

V =
(1 × 0.30)
+ (1 × 0.40)
+ (1 × 0.30)

V = 1.000
```

------------------------------------------------------------------------

# 26. Dashboard Owner

Dashboard Owner sebaiknya menampilkan:

-   jumlah teknisi aktif;
-   periode evaluasi terakhir;
-   teknisi ranking #1;
-   skor tertinggi;
-   ringkasan ranking;
-   tombol menuju hasil SAW;
-   tombol cetak laporan.

Dashboard harus tetap berfungsi sebagai ringkasan, bukan menggantikan
halaman detail ranking.

------------------------------------------------------------------------

# 27. Laporan

Format laporan minimal:

``` text
CV ARSITEK SEMESTA NUSANTARA

LAPORAN PENILAIAN KINERJA TEKNISI

Periode: [PERIODE]

Kriteria dan Bobot:
C1 Kedisiplinan       30%
C2 Kualitas Hasil     40%
C3 Tanggung Jawab     30%

Ranking:

No | Kode | Teknisi | C1 | C2 | C3 | Nilai SAW | Ranking
----------------------------------------------------------

Kesimpulan:
Teknisi dengan nilai preferensi tertinggi menjadi alternatif
dengan peringkat terbaik berdasarkan metode SAW.

Tanggal cetak:
[DATE]
```

Laporan harus dapat digunakan melalui browser print dialog. Jika PDF
generator digunakan, hasilnya tetap harus mengikuti struktur informasi
di atas.

------------------------------------------------------------------------

# 28. Testing Strategy

Proposal menggunakan Black Box Testing dan membandingkan hasil sistem
dengan perhitungan SAW manual di Microsoft Excel.

## 28.1 Authentication Testing

Test:

-   login valid;
-   login invalid;
-   logout;
-   akses halaman tanpa login;
-   akses route Admin sebagai Owner;
-   akses route Owner sebagai Admin.

## 28.2 CRUD Testing

Test:

-   tambah teknisi;
-   edit teknisi;
-   hapus teknisi;
-   kode teknisi duplikat;
-   data wajib kosong;
-   status teknisi.

## 28.3 Penilaian Testing

Test:

-   rating valid 1--4;
-   rating 0;
-   rating 5;
-   field kosong;
-   teknisi inactive;
-   duplicate periode.

## 28.4 SAW Testing

Test:

-   normalisasi Benefit;
-   bobot;
-   nilai preferensi;
-   ranking descending;
-   dataset baseline;
-   data tidak lengkap.

## 28.5 Report Testing

Test:

-   laporan tersedia;
-   periode benar;
-   ranking benar;
-   skor benar;
-   print berhasil.

------------------------------------------------------------------------

# 29. Golden Test

AI agent harus membuat automated/manual test berdasarkan dataset
baseline.

Expected:

``` text
A1  Toni              1.000
A6  Aris              0.925
A4  Rahmat Hidayat    0.900
A2  Apip              0.850
A9  Wanto             0.750
A10 Heri              0.750
A7  IMADE             0.700
A5  Ahmad Sahudin     0.675
A3  Agus Supriyanto   0.600
A8  Asep              0.575
```

Jika output berbeda, agent harus memeriksa:

1.  data input;
2.  bobot;
3.  tipe Benefit;
4.  nilai maksimum;
5.  rumus normalisasi;
6.  rumus Vi;
7.  proses sorting.

Agent tidak boleh memperbaiki hasil dengan hardcoded ranking.

------------------------------------------------------------------------

# 30. Acceptance Criteria Utama

## AC-01 Login

**Given** user memiliki credentials valid\
**When** user login\
**Then** sistem membuat session dan mengarahkan user berdasarkan role.

## AC-02 Authorization

**Given** user sudah login\
**When** user membuka route role lain\
**Then** sistem menolak akses.

## AC-03 Data Teknisi

**Given** Admin mengisi form valid\
**When** Admin menyimpan teknisi\
**Then** data tersimpan di database dan muncul di daftar.

## AC-04 Penilaian

**Given** Admin memasukkan C1, C2, C3 dalam rentang 1--4\
**When** Admin menyimpan\
**Then** data penilaian tersimpan untuk teknisi dan periode yang
dipilih.

## AC-05 Invalid Rating

**Given** Admin memasukkan nilai di luar 1--4\
**When** form dikirim\
**Then** sistem menolak data.

## AC-06 SAW

**Given** data penilaian lengkap tersedia\
**When** Owner menjalankan SAW\
**Then** sistem melakukan normalisasi, pembobotan, perhitungan Vi, dan
ranking.

## AC-07 Golden Dataset

**Given** dataset baseline proposal\
**When** SAW diproses\
**Then** hasil harus sesuai expected result pada PRD.

## AC-08 Ranking

**Given** semua nilai Vi telah dihitung\
**When** hasil ditampilkan\
**Then** ranking diurutkan dari nilai terbesar ke terkecil.

## AC-09 Detail

**Given** Owner memilih teknisi\
**When** Owner membuka detail\
**Then** sistem menampilkan nilai asli, normalisasi, bobot, kontribusi,
nilai akhir, dan ranking.

## AC-10 Report

**Given** hasil SAW tersedia\
**When** Owner mencetak laporan\
**Then** laporan memuat periode, kriteria, bobot, teknisi, ranking, dan
nilai SAW.

------------------------------------------------------------------------

# 31. Definition of Done

Sebuah fitur dianggap selesai jika:

-   [ ] requirement sudah diimplementasikan;
-   [ ] database terkait sudah tersedia;
-   [ ] backend sudah tersedia;
-   [ ] frontend sudah tersedia;
-   [ ] validation sudah tersedia;
-   [ ] authorization sudah tersedia;
-   [ ] error handling sudah tersedia;
-   [ ] tidak terdapat PHP error;
-   [ ] tidak terdapat JavaScript error yang mengganggu fitur;
-   [ ] data dapat disimpan/dibaca dengan benar;
-   [ ] fitur telah diuji;
-   [ ] acceptance criteria terpenuhi.

Aplikasi dianggap selesai jika seluruh requirement Must telah selesai
dan golden test SAW berhasil.

------------------------------------------------------------------------

# 32. Implementation Order

AI agent harus mengimplementasikan secara bertahap.

## Phase 1 --- Project Setup

-   [ ] Buat project PHP.
-   [ ] Konfigurasi MySQL.
-   [ ] Buat struktur project.
-   [ ] Buat database schema.
-   [ ] Buat seed data.
-   [ ] Buat README setup.

## Phase 2 --- Authentication

-   [ ] Login.
-   [ ] Session.
-   [ ] Role.
-   [ ] Logout.
-   [ ] Authorization middleware/helper.

## Phase 3 --- Master Data

-   [ ] CRUD teknisi.
-   [ ] Kriteria.
-   [ ] Bobot.
-   [ ] Validasi bobot.

## Phase 4 --- Penilaian

-   [ ] Form penilaian.
-   [ ] Rating 1--4.
-   [ ] Periode.
-   [ ] Validasi duplicate.
-   [ ] Riwayat.

## Phase 5 --- SAW Engine

-   [ ] Matriks X.
-   [ ] Max per kriteria.
-   [ ] Normalisasi R.
-   [ ] Weighted score.
-   [ ] Vi.
-   [ ] Ranking.
-   [ ] Penyimpanan hasil.

## Phase 6 --- Result UI

-   [ ] Ranking.
-   [ ] Detail SAW.
-   [ ] Filter periode.
-   [ ] Histori.

## Phase 7 --- Reporting

-   [ ] Layout laporan.
-   [ ] Print.
-   [ ] Validasi data laporan.

## Phase 8 --- Testing

-   [ ] Black Box Testing.
-   [ ] Golden test.
-   [ ] Authorization testing.
-   [ ] Validation testing.
-   [ ] Report testing.

## Phase 9 --- Finalization

-   [ ] Bersihkan error.
-   [ ] Rapikan UI.
-   [ ] Dokumentasikan setup.
-   [ ] Dokumentasikan database.
-   [ ] Dokumentasikan algoritma.
-   [ ] Pastikan aplikasi dapat dijalankan dari environment yang
    ditentukan.

------------------------------------------------------------------------

# 33. AI Coding Agent Rules

Bagian ini bersifat wajib.

### Rule 1 --- PRD adalah Source of Truth

Gunakan PRD sebagai sumber utama requirement.

### Rule 2 --- Jangan Mengarang Business Rule

Jika sesuatu tidak ditentukan, jangan membuat aturan bisnis yang
mengubah hasil penelitian.

### Rule 3 --- Jangan Memperluas Scope

Jangan menambahkan payroll, absensi, inventory, accounting, project
management, atau fitur lain di luar scope.

### Rule 4 --- Jangan Mengganti Algoritma

Gunakan SAW sesuai formula yang ditentukan.

### Rule 5 --- Jangan Hardcode Ranking

Ranking harus berasal dari proses perhitungan.

Contoh yang dilarang:

``` php
$ranking = [
    'Toni' => 1,
    'Aris' => 2
];
```

Ranking harus dihitung berdasarkan data.

### Rule 6 --- Jangan Hardcode Hasil untuk Lulus Test

Golden dataset hanya digunakan untuk memverifikasi implementasi.

### Rule 7 --- Pisahkan SAW Engine

Logika matematika harus berada dalam service/function yang dapat diuji
secara terpisah.

### Rule 8 --- Server-Side Validation

Jangan hanya melakukan validasi di frontend.

### Rule 9 --- Server-Side Authorization

Jangan hanya menyembunyikan menu. Endpoint juga harus melakukan
pengecekan role.

### Rule 10 --- Jangan Menyimpan Password Plaintext

Gunakan password hashing.

### Rule 11 --- Gunakan Prepared Statements

Jangan menyusun query SQL dari input user secara langsung.

### Rule 12 --- Pertahankan Traceability

Setiap hasil ranking harus dapat ditelusuri kembali ke:

``` text
periode
→ teknisi
→ nilai C1/C2/C3
→ bobot
→ normalisasi
→ nilai preferensi
→ ranking
```

### Rule 13 --- Prioritaskan Kesederhanaan

Karena aplikasi merupakan proyek skripsi, jangan menggunakan arsitektur
atau dependensi yang tidak diperlukan.

### Rule 14 --- Jangan Mengubah Data Penelitian

Data baseline proposal boleh digunakan sebagai seed/demo, tetapi jangan
mengklaim data tersebut sebagai data final perusahaan apabila belum
divalidasi.

### Rule 15 --- Test Sebelum Menyatakan Selesai

Sebelum menyatakan implementasi selesai:

1.  jalankan aplikasi;
2.  cek database;
3.  test login;
4.  test role;
5.  test CRUD;
6.  test penilaian;
7.  test SAW;
8.  test golden dataset;
9.  test ranking;
10. test laporan.

------------------------------------------------------------------------

# 34. Engineering Decisions yang Bersifat Rekomendasi

Bagian ini membedakan **ketentuan penelitian** dari keputusan
engineering yang dibuat agar aplikasi lebih baik.

## 34.1 Periode Penilaian

Proposal menyebut input penilaian secara berkala dan menyebut penilaian
secara periodik, tetapi struktur lima tabel yang dijelaskan belum
menetapkan desain periode secara rinci.

Untuk implementasi, gunakan field `periode` pada `tb_penilaian` dan
`tb_hasil`.

Ini adalah keputusan engineering untuk mendukung histori, bukan
perubahan metode penelitian.

## 34.2 Status Teknisi

Proposal berfokus pada 10 teknisi aktif.

Untuk implementasi, gunakan status `active/inactive` agar teknisi lama
tidak harus dihapus ketika tidak lagi aktif.

## 34.3 Detail Perhitungan

Proposal berfokus pada hasil preferensi dan ranking.

Halaman detail normalisasi dan kontribusi disarankan agar proses SAW
transparan dan mudah diverifikasi saat pengujian/sidang.

## 34.4 Tie Score

Proposal menunjukkan Wanto dan Heri sama-sama memiliki skor 0.750.

Tidak ada aturan bisnis tie-breaker yang ditetapkan dalam proposal.

Karena itu jangan mengubah nilai SAW atau mengklaim salah satu lebih
unggul berdasarkan aturan tambahan yang tidak ada dalam penelitian.

------------------------------------------------------------------------

# 35. Traceability terhadap Skripsi

Requirement aplikasi harus dapat ditelusuri ke bagian proposal:

  Requirement         Dasar Proposal
  ------------------- -----------------------------------
  SPK berbasis web    Bab I / batasan dan tujuan
  Admin + Owner       Bab III analisis kebutuhan
  Data teknisi        Bab III analisis kebutuhan
  Kriteria C1/C2/C3   Bab II & Bab III
  Bobot 30/40/30      Bab III bagian SAW
  Rating 1--4         Bab III bagian klasifikasi rating
  SAW                 Bab II & Bab III
  Ranking             Bab III
  Laporan print       Bab III
  MySQL               Bab II & Bab III
  PHP                 Bab II & Bab III
  UML                 Bab II & Bab III
  Waterfall           Bab II & Bab III
  Black Box Testing   Bab II & Bab III

------------------------------------------------------------------------

# 36. Expected Final Application

Pada kondisi selesai, aplikasi harus mampu melakukan alur berikut:

``` text
                 ┌───────────────┐
                 │     LOGIN     │
                 └───────┬───────┘
                         │
                ┌────────┴────────┐
                │                 │
             ADMIN              OWNER
                │                 │
                ▼                 ▼
         ┌─────────────┐   ┌─────────────┐
         │Data Teknisi │   │  Dashboard  │
         └──────┬──────┘   └──────┬──────┘
                │                 │
                ▼                 ▼
         ┌─────────────┐   ┌─────────────┐
         │Kriteria &   │   │ Pilih       │
         │Bobot        │   │ Periode     │
         └──────┬──────┘   └──────┬──────┘
                │                 │
                ▼                 ▼
         ┌─────────────┐   ┌─────────────┐
         │ Penilaian   │──▶│  SAW Engine │
         └─────────────┘   └──────┬──────┘
                                  │
                                  ▼
                           ┌─────────────┐
                           │ Normalisasi │
                           └──────┬──────┘
                                  │
                                  ▼
                           ┌─────────────┐
                           │ Nilai Vi    │
                           └──────┬──────┘
                                  │
                                  ▼
                           ┌─────────────┐
                           │   Ranking   │
                           └──────┬──────┘
                                  │
                         ┌────────┴────────┐
                         ▼                 ▼
                   Detail SAW          Laporan
```

------------------------------------------------------------------------

# 37. Final Success Definition

Project dinyatakan berhasil apabila:

1.  Aplikasi web dapat dijalankan pada environment yang ditentukan.
2.  Admin dapat mengelola teknisi.
3.  Admin dapat mengelola kriteria/bobot.
4.  Admin dapat memasukkan penilaian.
5.  Owner dapat melihat dan memproses hasil.
6.  SAW berjalan sesuai formula penelitian.
7.  Ranking tidak di-hardcode.
8.  Golden dataset menghasilkan ranking dan skor yang sesuai dengan
    proposal.
9.  Hak akses Admin dan Owner berjalan.
10. Laporan dapat dicetak.
11. Black Box Testing dapat dilakukan terhadap fungsi utama.
12. Hasil perhitungan aplikasi dapat dibandingkan dengan perhitungan
    manual.
13. Tidak ada fitur utama yang keluar dari batasan penelitian.

------------------------------------------------------------------------

# 38. Referensi Dokumen Utama

PRD ini disusun berdasarkan proposal skripsi:

**IMPLEMENTASI ALGORITMA SIMPLE ADDITIVE WEIGHTING (SAW) UNTUK PENILAIAN
KINERJA TEKNISI LAPANGAN PADA CV ARSITEK SEMESTA NUSANTARA**

Proposal menetapkan fokus pada SPK berbasis web untuk teknisi lapangan,
tiga kriteria penilaian, metode SAW, PHP/MySQL, UML, Waterfall, serta
Black Box Testing.
