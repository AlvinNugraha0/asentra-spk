# ASENTRA SPK

Sistem Pendukung Keputusan (SPK) berbasis web untuk penilaian kinerja teknisi lapangan pada **CV Arsitek Semesta Nusantara (ASENTRA)**.

Metode: **Simple Additive Weighting (SAW)**.

Stack: PHP native, MySQL/MariaDB, HTML, CSS, JavaScript, XAMPP.

---

## Prasyarat

- XAMPP dengan PHP 8.2+ dan MariaDB 10.4+
- Web browser
- (Opsional) Composer hanya jika menambahkan library pihak ketiga

## Instalasi

1. Clone atau copy project ke `C:\xampp\htdocs\asentra-spk`.

2. Salin file environment:
   ```bash
   cp .env.example .env
   ```
   Sesuaikan isi `.env` jika konfigurasi database berbeda.

3. Buat database dan jalankan schema + seed:
   ```bash
   C:/xampp/mysql/bin/mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS asentra_spk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   C:/xampp/mysql/bin/mysql.exe -u root asentra_spk < database/schema.sql
   C:/xampp/mysql/bin/mysql.exe -u root asentra_spk < database/seed.sql
   ```

4. Akses aplikasi:
   ```
   http://localhost/asentra-spk/public/
   ```

## Akun Demo

| Username | Password | Role  |
|----------|----------|-------|
| admin    | admin    | Admin |
| owner    | owner    | Owner |

## Struktur Folder

```
asentra-spk/
├── app/
│   ├── controllers/     # Request handlers
│   ├── models/          # Data access
│   ├── services/        # Business logic (SAW, Auth)
│   ├── views/           # Templates
│   ├── helpers/         # Global helper functions
│   └── core/            # Database, Router
├── config/              # App config + database PDO
├── database/
│   ├── schema.sql       # DDL
│   └── seed.sql         # Demo data
├── public/              # Document root
│   ├── index.php        # Front controller
│   ├── css/             # Design tokens + components
│   └── js/              # Global UI behavior
├── tests/               # Automated tests
└── README.md
```

## Metode SAW (Simple Additive Weighting)

Penilaian kinerja teknisi menggunakan tiga kriteria, seluruhnya bersifat **Benefit**
(nilai lebih tinggi = lebih baik):

| Kode | Kriteria            | Bobot |
|------|---------------------|-------|
| C1   | Kedisiplinan        | 0.30  |
| C2   | Kualitas Hasil Kerja| 0.40  |
| C3   | Tanggung Jawab      | 0.30  |

**Normalisasi:**

```
rij = xij / max(xj)
```

**Nilai preferensi:**

```
Vi = Σ (wj × rij)
```

Hasil diurutkan dari `Vi` terbesar ke terkecil; nilai seri dipertahankan
sama (tie-break berdasarkan `teknisi_id` ascending). Perhitungan dilakukan
oleh `app/services/SawEngine.php` (single source of truth) dan diorkestrasi
serta disimpan oleh `app/services/SawService.php` secara transaksional per
periode (hapus hasil lama periode tersebut, lalu insert hasil baru).

### Golden Dataset

Dengan data seed periode `2026-08`, hasil SAW yang diharapkan:

| Peringkat | Teknisi          | Nilai Preferensi (Vi) |
|-----------|------------------|-----------------------|
| 1         | Toni             | 1.000                 |
| 2         | Aris             | 0.925                 |
| 3         | Rahmat Hidayat   | 0.900                 |
| 4         | Apip             | 0.850                 |
| 5         | Wanto            | 0.750                 |
| 6         | Heri             | 0.750                 |
| 7         | IMADE            | 0.700                 |
| 8         | Ahmad Sahudin    | 0.675                 |
| 9         | Agus Supriyanto  | 0.600                 |
| 10        | Asep             | 0.575                 |

## Laporan & Cetak (Owner)

Owner dapat mencetak laporan evaluasi per periode melalui menu **Laporan**
atau tombol **Cetak Laporan** pada halaman Hasil Ranking.

- Route: `/owner/laporan/{periode}`.
- Laporan menggunakan hasil SAW yang sudah tersimpan (`tb_hasil`) — tidak
  menghitung ulang SAW.
- Layout cetak mandiri (putih, A4 portrait, grayscale-friendly) via
  `public/css/print.css`; sidebar, topbar, navigasi, dan tombol disembunyikan
  saat mencetak (`window.print()`).
- Periode tanpa hasil SAW ditolak dengan pesan yang jelas (tidak membuat
  laporan palsu).

## Testing

```bash
# Database connectivity + seed verification
C:/xampp/php/php.exe tests/db_connect_test.php

# Password hash verification
C:/xampp/php/php.exe tests/password_verify_test.php

# Authentication service
C:/xampp/php/php.exe tests/auth_service_test.php

# SAW engine (17 checks)
C:/xampp/php/php.exe tests/saw_engine_test.php

# SAW service + persistence (12 checks)
C:/xampp/php/php.exe tests/saw_service_test.php

# SAW deep verification vs golden dataset (8 checks)
C:/xampp/php/php.exe tests/saw_deep_verify.php

# Owner UI + ranking + detail + print-link HTTP suite (41 checks)
# Catatan: jalankan dengan server dev aktif (php -S 127.0.0.1:8080) dan .env test
python tests/owner_ui_test.py

# Laporan / report HTTP suite (24 checks)
python tests/laporan_test.py
```

Catatan: `owner_ui_test.py` dan `laporan_test.py` menjalankan pengujian HTTP
terhadap aplikasi yang sedang berjalan; pastikan database sudah di-seed dan
server dev PHP aktif di `127.0.0.1:8080` sebelum menjalankannya.

## Catatan Keamanan

- Jangan commit file `.env` yang berisi kredensial asli.
- Password disimpan dengan `password_hash()`.
- Gunakan prepared statement untuk semua query.
- CSRF token diaktifkan untuk semua form POST.
