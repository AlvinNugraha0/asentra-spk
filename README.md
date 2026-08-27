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

## Testing

```bash
# Database connectivity + seed verification
C:/xampp/php/php.exe tests/db_connect_test.php

# Password hash verification
C:/xampp/php/php.exe tests/password_verify_test.php

# Authentication service
C:/xampp/php/php.exe tests/auth_service_test.php
```

## Catatan Keamanan

- Jangan commit file `.env` yang berisi kredensial asli.
- Password disimpan dengan `password_hash()`.
- Gunakan prepared statement untuk semua query.
- CSRF token diaktifkan untuk semua form POST.
