# Black Box Testing Report
# Aplikasi ASENTRA SPK — Sistem Pendukung Keputusan Penilaian Kinerja Teknisi

---

## 1. Informasi Pengujian

| Item | Keterangan |
|------|-----------|
| Nama Aplikasi | ASENTRA SPK |
| Versi | 1.0 |
| Tanggal Pengujian | 10–11 September 2026 |
| Penguji | Tester (Automated Browser Testing) |
| Metode Pengujian | Black Box Testing |
| Jenis Pengujian | Pengujian Fungsional |

---

## 2. Environment Pengujian

| Komponen | Spesifikasi |
|----------|-------------|
| Sistem Operasi | Windows |
| Web Server | PHP Built-in Development Server |
| PHP Version | 8.2+ |
| Database | MySQL/MariaDB (XAMPP) |
| Browser | Chromium-based (Automated) |
| URL Aplikasi | http://127.0.0.1:8080 |
| Database Name | asentra_spk |

### Akun Pengujian

| Username | Password | Role |
|----------|----------|------|
| admin | admin | Admin |
| owner | owner | Owner |

---

## 3. Tujuan Pengujian

Pengujian Black Box Testing pada aplikasi ASENTRA SPK bertujuan untuk:

1. Memverifikasi seluruh fungsi aplikasi berjalan sesuai dengan spesifikasi kebutuhan fungsional.
2. Menguji perilaku sistem terhadap input valid dan tidak valid.
3. Memastikan mekanisme autentikasi dan otorisasi bekerja dengan benar.
4. Memastikan proses CRUD (Create, Read, Update, Delete) pada setiap modul berfungsi.
5. Memverifikasi proses penilaian kinerja, kalkulasi SAW, dan perangkingan berjalan dengan benar dari sudut pandang pengguna.
6. Menguji penanganan error dan validasi input pada seluruh form.
7. Memastikan fitur laporan/cetak berfungsi sesuai kebutuhan.

---

## 4. Metode Pengujian

Pengujian dilakukan menggunakan metode **Black Box Testing** dengan pendekatan **Equivalence Partitioning** dan **Boundary Value Analysis**.

Setiap test case dijalankan langsung pada aplikasi yang berjalan (*running application*) melalui browser. Penguji berinteraksi dengan antarmuka pengguna (UI) tanpa melihat atau memodifikasi kode sumber.

Pengujian mencakup:
- **Positive Testing**: Menguji fungsi dengan input valid yang diharapkan berhasil.
- **Negative Testing**: Menguji fungsi dengan input tidak valid untuk memverifikasi penanganan error.
- **Boundary Testing**: Menguji batas nilai input yang diperbolehkan.
- **Authorization Testing**: Menguji pembatasan akses berdasarkan role pengguna.

---

## 5. Daftar Test Case

### A. Authentication (BB-001 s.d. BB-007)

| ID | Skenario | Status |
|----|----------|--------|
| BB-001 | Login Admin dengan username dan password valid | PASS |
| BB-002 | Login Owner dengan username dan password valid | PASS |
| BB-003 | Login dengan password salah | PASS |
| BB-004 | Login dengan username yang tidak terdaftar | PASS |
| BB-005 | Login dengan field kosong | PASS |
| BB-006 | Logout Admin | PASS |
| BB-007 | Logout Owner | PASS |

### B. Authorization / Role Access (BB-008 s.d. BB-012)

| ID | Skenario | Status |
|----|----------|--------|
| BB-008 | Admin mengakses halaman Admin (Dashboard, Teknisi, Kriteria, Penilaian) | PASS |
| BB-009 | Owner mengakses halaman Owner (Dashboard, Teknisi, Penilaian, Ranking) | PASS |
| BB-010 | Owner mencoba mengakses halaman Admin — ditolak (403) | PASS |
| BB-011 | Admin mencoba mengakses halaman Owner — ditolak (403) | PASS |
| BB-012 | Pengguna belum login mengakses halaman protected — redirect ke login | PASS |

### C. Admin — Data Teknisi (BB-013 s.d. BB-020)

| ID | Skenario | Status |
|----|----------|--------|
| BB-013 | Admin membuka halaman Data Teknisi | PASS |
| BB-014 | Admin menambah teknisi baru dengan data valid | PASS |
| BB-015 | Admin menambah teknisi dengan kode duplikat | PASS |
| BB-016 | Admin menambah teknisi dengan field wajib kosong | PASS |
| BB-017 | Admin mengedit data teknisi | PASS |
| BB-018 | Admin mengubah status teknisi (aktif/nonaktif) | PASS |
| BB-019 | Admin menghapus teknisi tanpa relasi data penilaian | PASS |
| BB-020 | Admin menggunakan pencarian/filter data teknisi | PASS |

### D. Admin — Kriteria & Bobot (BB-021 s.d. BB-023)

| ID | Skenario | Status |
|----|----------|--------|
| BB-021 | Admin membuka halaman Kriteria & Bobot | PASS |
| BB-022 | Admin menyimpan bobot dengan total valid (100%) | PASS |
| BB-023 | Admin menyimpan bobot dengan total tidak valid (≠100%) | PASS |

### E. Admin — Monitoring Penilaian (BB-024 s.d. BB-025)

| ID | Skenario | Status |
|----|----------|--------|
| BB-024 | Admin membuka halaman monitoring penilaian (read-only) | PASS |
| BB-025 | Admin membuka halaman riwayat penilaian | PASS |

### F. Owner — Data Teknisi (BB-026)

| ID | Skenario | Status |
|----|----------|--------|
| BB-026 | Owner melihat daftar teknisi (read-only, tanpa CRUD) | PASS |

### G. Owner — Input Penilaian Kinerja (BB-027 s.d. BB-033)

| ID | Skenario | Status |
|----|----------|--------|
| BB-027 | Owner membuka halaman daftar penilaian | PASS |
| BB-028 | Owner membuka form input penilaian | PASS |
| BB-029 | Owner menyimpan penilaian dengan data valid (C1=3, C2=4, C3=2) | PASS |
| BB-030 | Owner mencoba penilaian duplikat (teknisi & periode sama) | PASS |
| BB-031 | Owner melihat detail penilaian individual | PASS |
| BB-032 | Owner melihat riwayat penilaian | PASS |
| BB-033 | Owner mengedit penilaian yang sudah ada | PASS |

### H. Owner — Ranking & SAW (BB-034 s.d. BB-036)

| ID | Skenario | Status |
|----|----------|--------|
| BB-034 | Owner membuka halaman ranking dan memilih periode | PASS |
| BB-035 | Owner melihat hasil ranking SAW (golden dataset 2026-08) | PASS |
| BB-036 | Owner melihat detail perhitungan SAW per teknisi | PASS |

### I. Owner — Riwayat (BB-037)

| ID | Skenario | Status |
|----|----------|--------|
| BB-037 | Owner melihat riwayat ranking per periode | PASS |

### J. Owner — Laporan (BB-038 s.d. BB-040)

| ID | Skenario | Status |
|----|----------|--------|
| BB-038 | Owner membuka halaman pemilihan periode laporan | PASS |
| BB-039 | Owner melihat laporan cetak untuk periode valid (2026-08) | PASS |
| BB-040 | Owner mengakses laporan untuk periode tanpa hasil SAW | PASS |

### K. Validasi & Error Handling (BB-041 s.d. BB-042)

| ID | Skenario | Status |
|----|----------|--------|
| BB-041 | Akses halaman yang tidak ada (404) | PASS |
| BB-042 | Halaman 403 (Akses Ditolak) ditampilkan dengan benar | PASS |

---

## 6. Hasil Pengujian Detail

### BB-001: Login Admin dengan Kredensial Valid
- **Precondition**: Halaman login terbuka
- **Input**: Username = `admin`, Password = `admin`
- **Langkah**: Buka `/login` → Isi username → Isi password → Klik tombol Login
- **Expected Result**: Redirect ke `/admin/dashboard`, dashboard Admin tampil
- **Actual Result**: Berhasil redirect ke `/admin/dashboard`. Dashboard Admin menampilkan statistik teknisi aktif, total penilaian, status bobot kriteria, dan penilaian terbaru.
- **Status**: **PASS**
- **Screenshot**: `BB-001-login-admin.png`

### BB-002: Login Owner dengan Kredensial Valid
- **Precondition**: Halaman login terbuka
- **Input**: Username = `owner`, Password = `owner`
- **Langkah**: Buka `/login` → Isi username → Isi password → Klik tombol Login
- **Expected Result**: Redirect ke `/owner/dashboard`, dashboard Owner tampil
- **Actual Result**: Berhasil redirect ke `/owner/dashboard`. Dashboard Owner menampilkan ringkasan evaluasi kinerja, top performer, dan grafik tren.
- **Status**: **PASS**
- **Screenshot**: `BB-003-login-owner.png`

### BB-003: Login dengan Password Salah
- **Precondition**: Halaman login terbuka
- **Input**: Username = `admin`, Password = `wrongpassword`
- **Langkah**: Buka `/login` → Isi username → Isi password salah → Klik Login
- **Expected Result**: Tetap di halaman login, pesan error muncul
- **Actual Result**: Tetap di halaman `/login`. Pesan error "Username atau password salah." ditampilkan.
- **Status**: **PASS**
- **Screenshot**: `BB-005-login-password-salah.png`

### BB-004: Login dengan Username Tidak Terdaftar
- **Precondition**: Halaman login terbuka
- **Input**: Username = `nonexistent`, Password = `test123`
- **Langkah**: Buka `/login` → Isi username tidak terdaftar → Isi password → Klik Login
- **Expected Result**: Tetap di login, pesan error muncul
- **Actual Result**: Tetap di halaman `/login`. Pesan error "Username atau password salah." ditampilkan. Sistem tidak membedakan antara username salah dan password salah (keamanan).
- **Status**: **PASS**
- **Screenshot**: `BB-006-login-user-tidak-ada.png`

### BB-005: Login dengan Field Kosong
- **Precondition**: Halaman login terbuka
- **Input**: Username = (kosong), Password = (kosong)
- **Langkah**: Buka `/login` → Biarkan field kosong → Klik Login
- **Expected Result**: Pesan error validasi muncul
- **Actual Result**: Pesan error "Username dan password wajib diisi." ditampilkan. Form tidak dikirim ke proses autentikasi.
- **Status**: **PASS**
- **Screenshot**: `BB-007-login-field-kosong.png`

### BB-006: Logout Admin
- **Precondition**: Login sebagai Admin
- **Langkah**: Klik tombol Logout di sidebar → Session dihapus
- **Expected Result**: Redirect ke `/login`, session berakhir
- **Actual Result**: Berhasil logout. Redirect ke halaman `/login`. Session dihapus dengan benar.
- **Status**: **PASS**
- **Screenshot**: `BB-002-logout-admin.png`

### BB-007: Logout Owner
- **Precondition**: Login sebagai Owner
- **Langkah**: Klik tombol Logout di sidebar
- **Expected Result**: Redirect ke `/login`, session berakhir
- **Actual Result**: Berhasil logout. Redirect ke halaman `/login`.
- **Status**: **PASS**
- **Screenshot**: `BB-004-logout-owner.png`

### BB-008: Admin Mengakses Halaman Admin
- **Precondition**: Login sebagai Admin
- **Langkah**: Navigasi ke `/admin/dashboard`, `/admin/teknisi`, `/admin/kriteria`, `/admin/penilaian`
- **Expected Result**: Semua halaman tampil tanpa error
- **Actual Result**: Semua halaman Admin berhasil diakses: Dashboard, Data Teknisi, Kriteria & Bobot, Hasil Penilaian.
- **Status**: **PASS**

### BB-009: Owner Mengakses Halaman Owner
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/dashboard`, `/owner/teknisi`, `/owner/penilaian`, `/owner/ranking`
- **Expected Result**: Semua halaman tampil tanpa error
- **Actual Result**: Semua halaman Owner berhasil diakses: Dashboard, Data Teknisi, Penilaian Kinerja, Hasil Ranking.
- **Status**: **PASS**

### BB-010: Owner Mencoba Akses Halaman Admin
- **Precondition**: Login sebagai Owner
- **Input**: Navigasi langsung ke `/admin/dashboard` dan `/admin/teknisi`
- **Expected Result**: Halaman 403 — Akses Ditolak
- **Actual Result**: Sistem menampilkan halaman "403 — Akses Ditolak" dengan pesan "Anda tidak memiliki izin untuk mengakses halaman ini." dan tombol Kembali.
- **Status**: **PASS**
- **Screenshot**: `BB-010-akses-ditolak.png`

### BB-011: Admin Mencoba Akses Halaman Owner
- **Precondition**: Login sebagai Admin
- **Input**: Navigasi langsung ke `/owner/dashboard` dan `/owner/ranking`
- **Expected Result**: Halaman 403 — Akses Ditolak
- **Actual Result**: Sistem menampilkan halaman "403 — Akses Ditolak".
- **Status**: **PASS**

### BB-012: Akses Tanpa Login ke Halaman Protected
- **Precondition**: Tidak ada session aktif (sudah logout)
- **Input**: Navigasi langsung ke `/admin/dashboard`
- **Expected Result**: Redirect ke `/login`
- **Actual Result**: Otomatis redirect ke halaman `/login`.
- **Status**: **PASS**
- **Screenshot**: `BB-012-redirect-login.png`

### BB-013: Admin Membuka Halaman Data Teknisi
- **Precondition**: Login sebagai Admin
- **Langkah**: Navigasi ke `/admin/teknisi`
- **Expected Result**: Tabel daftar teknisi tampil
- **Actual Result**: Halaman Data Teknisi menampilkan tabel dengan kolom No, Kode, Nama, Status, Keterangan, dan Aksi. Terdapat 10 teknisi aktif pada data awal.
- **Status**: **PASS**

### BB-014: Admin Menambah Teknisi dengan Data Valid
- **Precondition**: Login sebagai Admin, halaman Tambah Teknisi terbuka
- **Input**: Kode = `TEST1`, Nama = `Teknisi Testing`, Keterangan = `Data testing`
- **Langkah**: Buka `/admin/teknisi/create` → Isi form → Klik Simpan
- **Expected Result**: Redirect ke daftar teknisi, pesan sukses
- **Actual Result**: Teknisi berhasil ditambahkan. Pesan "Teknisi berhasil ditambahkan." ditampilkan. Data muncul di daftar.
- **Status**: **PASS**

### BB-015: Admin Menambah Teknisi dengan Kode Duplikat
- **Precondition**: Teknisi `TEST1` sudah ada
- **Input**: Kode = `TEST1`, Nama = `Another Teknisi`
- **Expected Result**: Pesan error kode duplikat
- **Actual Result**: Pesan error "Kode teknisi sudah digunakan." ditampilkan. Data tidak tersimpan.
- **Status**: **PASS**

### BB-016: Admin Menambah Teknisi dengan Field Kosong
- **Precondition**: Form tambah teknisi terbuka
- **Input**: Semua field kosong
- **Expected Result**: Pesan validasi error
- **Actual Result**: Pesan validasi "Kode teknisi wajib diisi." dan "Nama teknisi wajib diisi." ditampilkan.
- **Status**: **PASS**

### BB-017: Admin Mengedit Data Teknisi
- **Precondition**: Teknisi `TEST1` ada di daftar
- **Langkah**: Klik Edit → Ubah Nama → Simpan
- **Expected Result**: Data berhasil diperbarui
- **Actual Result**: Data teknisi berhasil diperbarui. Pesan "Teknisi berhasil diperbarui." ditampilkan.
- **Status**: **PASS**

### BB-018: Admin Mengubah Status Teknisi
- **Precondition**: Teknisi `TEST1` berstatus aktif
- **Langkah**: Klik tombol toggle status (Nonaktifkan)
- **Expected Result**: Status berubah dari aktif ke nonaktif
- **Actual Result**: Status berhasil diubah. Pesan "Status teknisi diperbarui." ditampilkan.
- **Status**: **PASS**

### BB-019: Admin Menghapus Teknisi Tanpa Relasi
- **Precondition**: Teknisi `TEST1` tidak memiliki data penilaian
- **Langkah**: Klik tombol Hapus pada teknisi `TEST1`
- **Expected Result**: Teknisi berhasil dihapus
- **Actual Result**: Teknisi berhasil dihapus dari daftar. Pesan "Teknisi berhasil dihapus." ditampilkan.
- **Status**: **PASS**

### BB-020: Admin Pencarian Data Teknisi
- **Precondition**: Data teknisi tersedia
- **Input**: Kata kunci pencarian = `Toni`
- **Expected Result**: Hanya teknisi yang cocok ditampilkan
- **Actual Result**: Hasil pencarian menampilkan hanya teknisi yang namanya mengandung "Toni".
- **Status**: **PASS**

### BB-021: Admin Membuka Halaman Kriteria & Bobot
- **Precondition**: Login sebagai Admin
- **Langkah**: Navigasi ke `/admin/kriteria`
- **Expected Result**: Halaman menampilkan 3 kriteria (C1, C2, C3) dengan bobot
- **Actual Result**: Halaman menampilkan 3 kriteria:
  - C1 — Kedisiplinan (Bobot: 0.30 / 30%) — Benefit
  - C2 — Kualitas Hasil Kerja (Bobot: 0.40 / 40%) — Benefit
  - C3 — Tanggung Jawab (Bobot: 0.30 / 30%) — Benefit
  - Total Bobot: 100% ✓ Valid
- **Status**: **PASS**
- **Screenshot**: `BB-021-kriteria-bobot.png`

### BB-022: Admin Menyimpan Bobot Valid (Total 100%)
- **Precondition**: Halaman kriteria terbuka
- **Input**: C1=0.30, C2=0.40, C3=0.30 (total = 1.00)
- **Expected Result**: Pesan sukses, bobot tersimpan
- **Actual Result**: Pesan "Kriteria dan bobot berhasil diperbarui." ditampilkan.
- **Status**: **PASS**
- **Screenshot**: `BB-022-kriteria-valid.png`

### BB-023: Admin Menyimpan Bobot Tidak Valid (Total ≠ 100%)
- **Precondition**: Halaman kriteria terbuka
- **Input**: C1=0.50, C2=0.40, C3=0.30 (total = 1.20 = 120%)
- **Expected Result**: Pesan error total bobot tidak valid
- **Actual Result**: Pesan error "Gagal menyimpan: total bobot harus 100%." ditampilkan. Bobot tidak tersimpan. Nilai dikembalikan ke sebelumnya.
- **Status**: **PASS**

### BB-024: Admin Monitoring Penilaian (Read-Only)
- **Precondition**: Login sebagai Admin
- **Langkah**: Navigasi ke `/admin/penilaian`
- **Expected Result**: Tabel monitoring penilaian (tanpa tombol create/edit/delete)
- **Actual Result**: Halaman menampilkan tabel daftar penilaian dengan kolom No, Teknisi, Periode, C1, C2, C3, Dinilai Oleh, Tanggal Input. Tidak terdapat tombol Tambah, Edit, atau Hapus penilaian.
- **Status**: **PASS**
- **Screenshot**: `BB-024-monitoring-penilaian.png`

### BB-025: Admin Riwayat Penilaian
- **Precondition**: Login sebagai Admin
- **Langkah**: Navigasi ke `/admin/riwayat`
- **Expected Result**: Halaman riwayat penilaian tampil
- **Actual Result**: Halaman menampilkan tabel histori penilaian per periode dengan filter dropdown.
- **Status**: **PASS**
- **Screenshot**: `BB-025-admin-riwayat.png`

### BB-026: Owner Melihat Daftar Teknisi (Read-Only)
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/teknisi`
- **Expected Result**: Daftar teknisi tampil tanpa tombol CRUD
- **Actual Result**: Tabel daftar teknisi ditampilkan dengan kolom No, Kode, Nama, Status, Keterangan. Tidak terdapat tombol Tambah Teknisi, Edit, atau Hapus.
- **Status**: **PASS**
- **Screenshot**: `BB-037-owner-teknisi-readonly.png`

### BB-027: Owner Membuka Daftar Penilaian
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/penilaian`
- **Expected Result**: Daftar teknisi dengan status penilaian (sudah/belum dinilai)
- **Actual Result**: Halaman menampilkan daftar seluruh teknisi aktif beserta status penilaian per periode. Terdapat statistik "Sudah Dinilai" dan "Belum Dinilai".
- **Status**: **PASS**
- **Screenshot**: `BB-032-owner-penilaian-list.png`

### BB-028: Owner Membuka Form Input Penilaian
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/penilaian/create`
- **Expected Result**: Form input penilaian tampil dengan dropdown teknisi, input periode, dan input C1/C2/C3
- **Actual Result**: Form tampil dengan: dropdown pemilihan teknisi, input periode (bulan/tahun), dan 3 kelompok radio card untuk C1, C2, C3 (masing-masing opsi 1-Kurang, 2-Cukup, 3-Baik, 4-Sangat Baik).
- **Status**: **PASS**
- **Screenshot**: `BB-033-form-input-penilaian.png`

### BB-029: Owner Menyimpan Penilaian dengan Data Valid
- **Precondition**: Form penilaian terbuka
- **Input**: Teknisi = Toni (A1), Periode = 2026-09, C1 = 3 (Baik), C2 = 4 (Sangat Baik), C3 = 2 (Cukup)
- **Expected Result**: Penilaian tersimpan, redirect ke detail, SAW auto-trigger
- **Actual Result**: Penilaian berhasil disimpan. Pesan "Penilaian berhasil disimpan." ditampilkan. Redirect ke halaman detail penilaian. Proses SAW otomatis berjalan.
- **Status**: **PASS**

### BB-030: Owner Mencoba Penilaian Duplikat
- **Precondition**: Penilaian untuk Toni periode 2026-09 sudah ada
- **Input**: Teknisi = Toni (A1), Periode = 2026-09, C1=2, C2=2, C3=2
- **Expected Result**: Pesan error duplikat
- **Actual Result**: Pesan error "Teknisi ini sudah dinilai pada periode tersebut." ditampilkan. Data tidak tersimpan ganda.
- **Status**: **PASS**

### BB-031: Owner Melihat Detail Penilaian
- **Precondition**: Penilaian untuk Toni periode 2026-08 tersedia
- **Langkah**: Navigasi ke `/owner/penilaian/detail/{id}`
- **Expected Result**: Detail penilaian tampil dengan nilai asli, normalisasi, kontribusi, dan ranking
- **Actual Result**: Halaman detail menampilkan: informasi teknisi, nilai asli C1/C2/C3, nilai normalisasi per kriteria, kontribusi terbobot per kriteria, total nilai preferensi, dan ranking.
- **Status**: **PASS**

### BB-032: Owner Melihat Riwayat Penilaian
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/riwayat-penilaian`
- **Expected Result**: Daftar histori penilaian per periode
- **Actual Result**: Tabel riwayat penilaian menampilkan semua data penilaian historis dengan kolom No, Teknisi, Periode, C1, C2, C3, Dinilai Oleh, Tanggal Input. Terdapat filter per periode.
- **Status**: **PASS**
- **Screenshot**: `BB-038-riwayat-penilaian.png`

### BB-033: Owner Mengedit Penilaian
- **Precondition**: Penilaian sudah ada
- **Langkah**: Buka edit form → Ubah nilai → Simpan
- **Expected Result**: Penilaian diperbarui, SAW re-trigger
- **Actual Result**: Penilaian berhasil diperbarui. Pesan "Penilaian berhasil diperbarui." ditampilkan. SAW otomatis dihitung ulang.
- **Status**: **PASS**

### BB-034: Owner Membuka Halaman Ranking
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/ranking` → Pilih periode
- **Expected Result**: Halaman ranking tampil dengan dropdown pemilihan periode
- **Actual Result**: Halaman ranking tampil. Dropdown periode tersedia untuk memilih periode yang akan ditampilkan.
- **Status**: **PASS**

### BB-035: Owner Melihat Ranking Golden Dataset (2026-08)
- **Precondition**: Hasil SAW periode 2026-08 tersedia
- **Langkah**: Navigasi ke `/owner/ranking?periode=2026-08`
- **Expected Result**: 10 teknisi teranking sesuai golden dataset
- **Actual Result**: Tabel ranking menampilkan 10 teknisi dengan urutan dan nilai preferensi sesuai golden dataset:
  1. Toni — 1.000
  2. Aris — 0.925
  3. Rahmat Hidayat — 0.900
  4. Apip — 0.850
  5. Wanto — 0.750
  6. Heri — 0.750
  7. IMADE — 0.700
  8. Ahmad Sahudin — 0.675
  9. Agus Supriyanto — 0.600
  10. Asep — 0.575
- **Status**: **PASS**
- **Screenshot**: `BB-030-ranking-golden-dataset.png`

### BB-036: Owner Melihat Detail Perhitungan SAW
- **Precondition**: Hasil ranking tersedia
- **Langkah**: Klik detail pada Toni (peringkat 1)
- **Expected Result**: Halaman detail SAW menampilkan matriks normalisasi dan kontribusi terbobot
- **Actual Result**: Halaman detail SAW menampilkan:
  - Nilai asli (C1, C2, C3)
  - Nilai maksimum per kriteria
  - Matriks normalisasi (rij = xij / max xj)
  - Bobot per kriteria (W)
  - Kontribusi terbobot (W × r)
  - Total Nilai Preferensi (Vi)
- **Status**: **PASS**
- **Screenshot**: `BB-031-detail-saw.png`

### BB-037: Owner Melihat Riwayat Ranking
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/riwayat`
- **Expected Result**: Data riwayat ranking historis tampil
- **Actual Result**: Halaman riwayat ranking menampilkan dropdown periode dan tabel hasil ranking per periode yang dipilih.
- **Status**: **PASS**
- **Screenshot**: `BB-032-riwayat-ranking.png`

### BB-038: Owner Membuka Halaman Laporan (Pemilihan Periode)
- **Precondition**: Login sebagai Owner
- **Langkah**: Navigasi ke `/owner/laporan`
- **Expected Result**: Halaman pemilihan periode laporan
- **Actual Result**: Halaman "Pilih Periode Laporan" menampilkan daftar periode yang tersedia dengan tombol "Cetak Laporan" untuk setiap periode.
- **Status**: **PASS**

### BB-039: Owner Melihat Laporan Cetak (Periode Valid)
- **Precondition**: Hasil SAW untuk periode 2026-08 tersedia
- **Langkah**: Navigasi ke `/owner/laporan/2026-08`
- **Expected Result**: Halaman laporan format A4 dengan kop surat, tabel ranking, dan informasi lengkap
- **Actual Result**: Laporan cetak ditampilkan dengan:
  - Kop surat CV Arsitek Semesta Nusantara
  - Judul "Laporan Penilaian Kinerja Teknisi"
  - Periode evaluasi
  - Tabel kriteria dan bobot
  - Tabel ranking lengkap (10 teknisi)
  - Nilai preferensi per teknisi
  - Format siap cetak (layout putih A4)
- **Status**: **PASS**
- **Screenshot**: `BB-035-laporan.png`

### BB-040: Owner Mengakses Laporan Periode Tanpa Hasil
- **Precondition**: Login sebagai Owner
- **Input**: Periode = 2025-01 (tidak ada data)
- **Langkah**: Navigasi ke `/owner/laporan/2025-01`
- **Expected Result**: Pesan error/peringatan, redirect ke pemilihan periode
- **Actual Result**: Sistem mendeteksi tidak ada hasil SAW untuk periode tersebut dan otomatis redirect ke `/owner/laporan` dengan pesan peringatan.
- **Status**: **PASS**
- **Screenshot**: `BB-040-laporan-tanpa-hasil.png`

### BB-041: Halaman 404 (URL Tidak Ada)
- **Precondition**: Aplikasi berjalan
- **Input**: URL = `/nonexistent-page`
- **Expected Result**: Halaman 404 custom
- **Actual Result**: Halaman custom "404 — Halaman Tidak Ditemukan" ditampilkan dengan pesan "URL yang Anda tuju tidak tersedia di aplikasi ini." dan tombol "Kembali". Tidak menampilkan error teknis.
- **Status**: **PASS**
- **Screenshot**: `BB-041-halaman-404.png`

### BB-042: Halaman 403 (Akses Ditolak)
- **Precondition**: Login sebagai Owner
- **Input**: URL = `/admin/teknisi/create`
- **Expected Result**: Halaman 403 custom
- **Actual Result**: Halaman custom "403 — Akses Ditolak" ditampilkan dengan pesan "Anda tidak memiliki izin untuk mengakses halaman ini." dan tombol "Kembali".
- **Status**: **PASS**
- **Screenshot**: `BB-010-akses-ditolak.png`

---

## 7. Bug yang Ditemukan

Selama pelaksanaan pengujian Black Box Testing, **tidak ditemukan bug atau kegagalan fungsi** pada seluruh 42 test case yang dijalankan.

Seluruh fungsi sistem beroperasi sesuai dengan spesifikasi kebutuhan fungsional:
- Autentikasi dan otorisasi berjalan dengan benar.
- Validasi input pada seluruh form berfungsi sesuai aturan bisnis.
- Proses CRUD pada data teknisi berjalan tanpa error.
- Mekanisme pencegahan duplikasi kode teknisi dan penilaian aktif.
- Proses penilaian kinerja, kalkulasi SAW otomatis, dan perangkingan menghasilkan output yang benar.
- Penanganan error (404, 403, validasi) menampilkan pesan yang informatif tanpa memaparkan informasi teknis.
- Laporan cetak menampilkan data yang konsisten dengan hasil ranking.

---

## 8. Perbaikan dan Retest

Karena tidak ditemukan bug pada pengujian awal, tahap perbaikan dan retest tidak diperlukan.

---

## 9. Rekapitulasi Hasil Pengujian

| Metrik | Jumlah |
|--------|--------|
| **Total Test Case** | **42** |
| **PASS** | **42** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |

### Persentase Keberhasilan Pengujian

```
Persentase Keberhasilan = (Jumlah PASS / Total Test Case) × 100%
                        = (42 / 42) × 100%
                        = 100%
```

### Distribusi per Kategori

| Kategori | Jumlah Test Case | PASS | FAIL | BLOCKED |
|----------|-----------------|------|------|---------|
| A. Authentication | 7 | 7 | 0 | 0 |
| B. Authorization / Role Access | 5 | 5 | 0 | 0 |
| C. Admin — Data Teknisi | 8 | 8 | 0 | 0 |
| D. Admin — Kriteria & Bobot | 3 | 3 | 0 | 0 |
| E. Admin — Monitoring Penilaian | 2 | 2 | 0 | 0 |
| F. Owner — Data Teknisi | 1 | 1 | 0 | 0 |
| G. Owner — Input Penilaian | 7 | 7 | 0 | 0 |
| H. Owner — Ranking & SAW | 3 | 3 | 0 | 0 |
| I. Owner — Riwayat | 1 | 1 | 0 | 0 |
| J. Owner — Laporan | 3 | 3 | 0 | 0 |
| K. Validasi & Error Handling | 2 | 2 | 0 | 0 |
| **Total** | **42** | **42** | **0** | **0** |

---

## 10. Kesimpulan

Berdasarkan hasil pengujian Black Box Testing yang telah dilaksanakan terhadap aplikasi ASENTRA SPK, diperoleh kesimpulan sebagai berikut:

1. **Seluruh 42 test case berhasil dieksekusi** dengan status PASS. Persentase keberhasilan pengujian mencapai **100%**.

2. **Fungsi autentikasi** bekerja dengan benar. Sistem mampu memvalidasi kredensial yang valid maupun tidak valid, menampilkan pesan error yang sesuai, dan mengelola session login/logout dengan aman.

3. **Otorisasi berbasis role** (Admin dan Owner) berfungsi sesuai spesifikasi. Sistem secara konsisten menolak akses lintas role dan melindungi halaman yang membutuhkan autentikasi.

4. **Fungsi CRUD Data Teknisi** pada modul Admin berjalan sesuai harapan, termasuk validasi field wajib, pencegahan kode duplikat, pencarian data, dan manajemen status aktif/nonaktif.

5. **Manajemen Kriteria dan Bobot** memiliki validasi yang ketat untuk memastikan total bobot selalu berjumlah 100% sebelum disimpan.

6. **Proses penilaian kinerja** oleh Owner berjalan dengan baik. Sistem mendukung input, edit, validasi skala 1-4, pencegahan duplikasi penilaian per teknisi per periode, dan auto-trigger kalkulasi SAW setelah penyimpanan.

7. **Hasil ranking SAW** sesuai dengan golden dataset yang telah ditentukan, mengkonfirmasi bahwa proses perangkingan berjalan benar dari perspektif pengguna.

8. **Fitur laporan cetak** menghasilkan dokumen yang sesuai dengan data ranking dan format yang layak untuk dicetak.

9. **Penanganan error** pada aplikasi memberikan pesan yang informatif dan user-friendly tanpa mengekspos informasi teknis internal.

Dengan demikian, aplikasi ASENTRA SPK telah memenuhi seluruh kebutuhan fungsional yang diuji melalui metode Black Box Testing.

---

*Dokumen ini disusun sebagai bahan pendukung BAB IV Skripsi — Sub-bab 4.4 Hasil Pengujian Black Box Testing.*
