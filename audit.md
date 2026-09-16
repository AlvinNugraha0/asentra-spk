Anda bertindak sebagai Senior Software Architect dan Database Engineer untuk proyek ini.

PROYEK:
ASENTRA SPK
Sistem Pendukung Keputusan Penilaian Kinerja Teknisi Lapangan
CV Arsitek Semesta Nusantara (ASENTRA)

STACK YANG WAJIB DIPERTAHANKAN:
- PHP 8.2+
- Custom PHP MVC yang sudah ada
- MySQL/MariaDB
- HTML/CSS/Vanilla JavaScript
- Chart.js yang sudah digunakan
- Jangan migrasi ke React, Vue, Laravel, Node.js, atau framework frontend/backend baru.
- Pertahankan struktur arsitektur proyek yang sudah ada selama masih layak digunakan.

==================================================
TUJUAN TAHAP INI
==================================================

Lakukan AUDIT MENYELURUH terhadap project ASENTRA SPK yang sedang terbuka.

PENTING:
TAHAP INI HANYA AUDIT DAN PERENCANAAN.

JANGAN:
- mengubah source code
- mengubah database
- menjalankan migration
- menghapus tabel
- menghapus kolom
- mengubah struktur existing
- membuat fitur baru
- mengubah UI
- melakukan refactor besar
- mengganti framework
- mengubah rumus SAW existing

Jangan melakukan perubahan apa pun sebelum saya memberikan instruksi tahap berikutnya.

==================================================
BAGIAN 1 - AUDIT STRUKTUR PROJECT
==================================================

Baca dan pahami struktur project secara menyeluruh.

Identifikasi:

1. Entry point aplikasi
2. Struktur MVC
3. Folder controller
4. Folder model
5. Folder view
6. Routing
7. Middleware/authentication jika ada
8. Konfigurasi database
9. Helper/library/service jika ada
10. Asset CSS
11. Asset JavaScript
12. Library pihak ketiga
13. File konfigurasi
14. File migration/seeder jika ada
15. Struktur template/layout

Buat peta struktur project.

Contoh format:

PROJECT
├── public/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── views/
│   └── ...
├── config/
└── ...

Gunakan struktur AKTUAL project, jangan membuat asumsi.

==================================================
BAGIAN 2 - AUDIT DATABASE AKTUAL
==================================================

Temukan schema database yang sedang digunakan project.

Identifikasi seluruh tabel yang berkaitan dengan sistem.

Secara khusus periksa:

- tb_user
- tb_teknisi
- tb_kriteria
- tb_penilaian
- tb_hasil

Tetapi jangan berasumsi hanya tabel tersebut yang ada.

Untuk SETIAP tabel, tampilkan:

- nama tabel
- primary key
- seluruh kolom
- tipe data
- nullable
- default value
- unique/index
- foreign key
- relasi
- fungsi tabel dalam aplikasi

Jika terdapat schema.sql, migration, seeder, atau database dump, baca juga file tersebut.

==================================================
BAGIAN 3 - AUDIT PENGGUNAAN tb_penilaian
==================================================

Ini bagian yang sangat penting.

Cari seluruh source code yang menggunakan:

tb_penilaian

Identifikasi:

- controller yang menggunakannya
- model yang menggunakannya
- view yang menggunakannya
- query INSERT
- query UPDATE
- query SELECT
- query DELETE
- relasi dengan teknisi
- relasi dengan kriteria
- bagaimana nilai C1/C2/C3 saat ini disimpan
- apakah data penilaian bersifat bulanan atau tidak
- apakah ada tanggal/periode
- bagaimana Owner memasukkan penilaian
- bagaimana Admin menggunakan data tersebut

Jelaskan fungsi tb_penilaian dalam aplikasi CURRENT VERSION.

==================================================
BAGIAN 4 - AUDIT tb_hasil DAN PERHITUNGAN SAW
==================================================

Cari seluruh source code yang berkaitan dengan:

tb_hasil

Kemudian cari implementasi algoritma SAW.

Identifikasi secara detail:

1. sumber nilai C1, C2, C3
2. proses normalisasi
3. formula normalisasi
4. bobot
5. perhitungan nilai preferensi
6. sorting ranking
7. tie handling
8. penyimpanan hasil
9. tampilan detail perhitungan
10. kapan perhitungan dilakukan

Tampilkan rumus SAW yang BENAR-BENAR digunakan oleh kode saat ini.

Jangan mengganti dengan rumus yang menurut Anda lebih baik.

Jika terdapat perbedaan antara kode, database, dan dokumentasi, laporkan perbedaannya.

==================================================
BAGIAN 5 - AUDIT ROLE DAN PERMISSION
==================================================

Periksa alur role:

ADMIN
OWNER

Identifikasi fitur yang dapat diakses masing-masing role.

Khususnya:

ADMIN:
- teknisi
- kriteria
- bobot
- import/data
- monitoring penilaian

OWNER:
- teknisi
- penilaian
- detail penilaian
- ranking
- detail SAW
- history
- laporan

Jangan mengubah permission.

Hanya laporkan kondisi existing.

==================================================
BAGIAN 6 - AUDIT VALIDASI EXISTING
==================================================

Periksa validasi yang sudah ada untuk:

- login
- teknisi
- kriteria
- bobot
- penilaian
- angka
- form input
- upload file jika sudah ada

Identifikasi juga:

- validation server-side
- validation client-side
- sanitization
- transaction database
- error handling

==================================================
BAGIAN 7 - COCOKKAN DENGAN SPESIFIKASI SISTEM V2
==================================================

Berikut adalah TARGET DATABASE DESIGN V2 yang harus digunakan sebagai blueprint.

JANGAN IMPLEMENTASIKAN.

Hanya lakukan GAP ANALYSIS antara kondisi existing dengan target berikut.

TARGET:

1. tb_user
2. tb_teknisi
3. tb_kriteria
4. tb_periode_penilaian
5. tb_import
6. tb_kedisiplinan
7. tb_pekerjaan
8. tb_tanggung_jawab
9. tb_penilaian
10. tb_hasil

Konsep data:

DATA MENTAH
↓
PERHITUNGAN INDIKATOR
↓
C1 / C2 / C3
↓
OWNER CONFIRMATION
↓
SAW
↓
HASIL / RANKING

==================================================
TARGET tb_periode_penilaian
==================================================

Konsep:

id_periode
kode_periode
nama_periode
tanggal_mulai
tanggal_selesai
status
file_import
created_by
created_at
updated_at

Status yang direncanakan:

draft
proses
selesai

==================================================
TARGET tb_import
==================================================

Konsep:

id_import
id_periode
nama_file
nama_file_asli
tanggal_import
total_data
data_berhasil
data_gagal
status
pesan_error
id_user
created_at

==================================================
TARGET tb_kedisiplinan
==================================================

Konsep:

id_kedisiplinan
id_periode
id_teknisi
bulan
total_hari_kerja
hadir
sakit
izin
alpa
terlambat
pekerjaan_terjadwal
sesuai_jadwal
created_at
updated_at

==================================================
TARGET tb_pekerjaan
==================================================

Konsep:

id_pekerjaan
id_periode
id_teknisi
tanggal
bulan
nama_pekerjaan
rapi
presisi
sesuai_desain
created_at
updated_at

==================================================
TARGET tb_tanggung_jawab
==================================================

Konsep:

id_tanggung_jawab
id_periode
id_teknisi
bulan
perawatan_alat
efisiensi_material
inisiatif
kepatuhan_prosedur
created_at
updated_at

==================================================
TARGET tb_penilaian
==================================================

Digunakan sebagai rekap nilai C1/C2/C3 per teknisi per periode.

Konsep:

id_penilaian
id_periode
id_teknisi
c1
c2
c3
status_data
jumlah_bulan_c1
jumlah_bulan_c2
jumlah_bulan_c3
warning
confirmed_by
confirmed_at
created_at
updated_at

PENTING:

Subindikator TIDAK menjadi kriteria SAW baru.

Struktur:

C1
├── C1.1 Kehadiran
├── C1.2 Ketepatan Waktu
└── C1.3 Kepatuhan terhadap Jadwal

C2
├── C2.1 Kerapian
├── C2.2 Ketepatan/Presisi
└── C2.3 Kesesuaian dengan Desain

C3
├── C3.1 Perawatan Alat
├── C3.2 Efisiensi Material
├── C3.3 Inisiatif
└── C3.4 Kepatuhan Prosedur

SAW tetap menggunakan:
C1
C2
C3

==================================================
TARGET tb_hasil
==================================================

Konsep:

id_hasil
id_periode
id_teknisi

c1
c2
c3

r_c1
r_c2
r_c3

bobot_c1
bobot_c2
bobot_c3

nilai_c1
nilai_c2
nilai_c3

nilai_preferensi
ranking

created_at

==================================================
ATURAN PENILAIAN V2
==================================================

Jangan mengimplementasikan sekarang.

Gunakan hanya untuk GAP ANALYSIS.

C1:
Kedisiplinan
Bobot 0.30
Benefit

C1.1 Kehadiran:

Persentase:

(Hadir / Total Hari Kerja) × 100%

Konversi:

>=95%       = 4
85%-<95%    = 3
75%-<85%    = 2
<75%        = 1

Sakit dan izin tidak dikurangi langsung sebagai penalti.

Alpa disimpan sebagai informasi/peringatan tambahan.

C1.2 Ketepatan Waktu:

0-2 keterlambatan = 4
3-5               = 3
6-8               = 2
>=9               = 1

C1.3 Kepatuhan Jadwal:

(Sesuai Jadwal / Pekerjaan Terjadwal) × 100%

>=95%       = 4
80%-<95%    = 3
65%-<80%    = 2
<65%        = 1

C1 bulanan:

(C1.1 + C1.2 + C1.3) / 3

==================================================

C2:
Kualitas Hasil Kerja
Bobot 0.40
Benefit

C2.1 Kerapian
C2.2 Ketepatan/Presisi
C2.3 Kesesuaian dengan Desain

Input setiap pekerjaan:

Ya / Tidak

Konversi persentase:

>=90%       = 4
75%-<90%    = 3
60%-<75%    = 2
<60%        = 1

C2 bulanan:

(C2.1 + C2.2 + C2.3) / 3

==================================================

C3:
Tanggung Jawab
Bobot 0.30
Benefit

C3.1 Perawatan Alat
C3.2 Efisiensi Material
C3.3 Inisiatif
C3.4 Kepatuhan Prosedur

Masing-masing bernilai 1-4.

C3 bulanan:

(C3.1 + C3.2 + C3.3 + C3.4) / 4

==================================================
PERIODE
==================================================

Penilaian dilakukan bulanan.

Periode normal terdiri dari 3 bulan.

Nilai periode:

C1 periode = rata-rata C1 bulanan yang memiliki data
C2 periode = rata-rata C2 bulanan yang memiliki data
C3 periode = rata-rata C3 bulanan yang memiliki data

Jika hanya 1 bulan memiliki data:
- tetap dapat dinilai
- tetap dapat masuk ranking
- sistem memberikan warning bahwa data hanya tersedia 1 dari 3 bulan.

Jika bulan tidak memiliki data relevan:
- bulan tersebut tidak dimasukkan ke denominator rata-rata.

==================================================
SAW
==================================================

Ketiga kriteria merupakan benefit.

Normalisasi:

rij = xij / max(xij)

Preferensi:

Vi =
(0.30 × rC1)
+
(0.40 × rC2)
+
(0.30 × rC3)

Nilai C1/C2/C3 desimal tidak boleh dibulatkan sebelum proses SAW.

Ranking:
- nilai preferensi descending
- jika nilai sama, gunakan tie-break teknis yang deterministic berdasarkan teknisi_id ascending
- jangan mengubah nilai preferensi hanya karena tie.

==================================================
BAGIAN 8 - ANALISIS KOMPATIBILITAS
==================================================

Buat tabel GAP ANALYSIS:

| Komponen | Existing | Target V2 | Gap | Dampak | Rekomendasi |
|----------|----------|-----------|-----|--------|-------------|

Minimal analisis:

- tb_user
- tb_teknisi
- tb_kriteria
- tb_penilaian
- tb_hasil
- periode
- import
- C1
- C2
- C3
- SAW
- ranking
- history
- laporan
- Owner confirmation
- grafik/tabulasi

==================================================
BAGIAN 9 - RISIKO MIGRASI
==================================================

Identifikasi risiko apabila database diubah.

Khususnya:

- data existing
- foreign key
- query existing
- controller
- model
- view
- ranking
- history
- report
- seeder
- test existing

Jelaskan apa yang berpotensi rusak.

==================================================
BAGIAN 10 - MIGRATION PLAN
==================================================

Buat rencana migrasi bertahap.

JANGAN menjalankan migration.

Rencana minimal:

PHASE 1
Backup dan audit

PHASE 2
Penambahan struktur periode/import

PHASE 3
Penambahan struktur data operasional

PHASE 4
Penyesuaian tb_penilaian

PHASE 5
Penyesuaian tb_hasil

PHASE 6
Penyesuaian model/controller

PHASE 7
Excel import

PHASE 8
Calculation engine

PHASE 9
Owner confirmation

PHASE 10
SAW dan ranking

PHASE 11
Dashboard/tabulasi/grafik

PHASE 12
Regression testing

Untuk setiap phase jelaskan:
- file yang kemungkinan berubah
- tabel yang berubah
- risiko
- dependensi
- cara validasi

==================================================
BAGIAN 11 - KEPUTUSAN YANG MASIH HARUS SAYA SETUJUI
==================================================

Di akhir audit, buat bagian:

"DECISIONS REQUIRED BEFORE IMPLEMENTATION"

Isi hanya keputusan yang benar-benar belum dapat ditentukan dari project existing atau spesifikasi di atas.

Jangan membuat pertanyaan yang sebenarnya sudah terjawab oleh spesifikasi.

==================================================
OUTPUT AKHIR
==================================================

Berikan laporan dalam urutan:

1. Executive Summary
2. Struktur Project Aktual
3. Database Aktual
4. Audit tb_penilaian
5. Audit tb_hasil dan SAW
6. Audit Role/Permission
7. Audit Validation
8. Gap Analysis terhadap Database V2
9. Risiko Migrasi
10. Migration Plan
11. File yang kemungkinan perlu diubah
12. Decisions Required Before Implementation

==================================================
ATURAN KERAS
==================================================

- Jangan coding.
- Jangan mengubah file.
- Jangan mengubah database.
- Jangan membuat migration.
- Jangan menghapus data.
- Jangan melakukan refactor.
- Jangan mengganti framework.
- Jangan mengubah UI.
- Jangan mengubah rumus.
- Jangan mengarang struktur project.
- Gunakan kondisi aktual project sebagai dasar audit.
- Jika ada ketidaksesuaian, laporkan secara eksplisit.
- Jika informasi tidak ditemukan, tulis "Tidak ditemukan", jangan menebak.
- Jangan menyelesaikan GAP secara otomatis.
- Setelah laporan selesai, BERHENTI dan tunggu instruksi berikutnya.

Tujuan utama tahap ini adalah mendapatkan pemahaman akurat mengenai project ASENTRA SPK sebelum implementasi Database Design V2.