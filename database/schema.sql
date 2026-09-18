-- ASENTRA SPK Database Schema
-- MySQL / MariaDB
-- Tables:
--   tb_user, tb_teknisi, tb_kriteria, tb_periode_penilaian,
--   tb_penilaian, tb_hasil, tb_import, tb_kedisiplinan,
--   tb_pekerjaan, tb_tanggung_jawab

CREATE DATABASE IF NOT EXISTS asentra_spk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE asentra_spk;

-- Drop in foreign-key reverse dependency order
DROP TABLE IF EXISTS tb_tanggung_jawab;
DROP TABLE IF EXISTS tb_pekerjaan;
DROP TABLE IF EXISTS tb_kedisiplinan;
DROP TABLE IF EXISTS tb_import;
DROP TABLE IF EXISTS tb_hasil;
DROP TABLE IF EXISTS tb_penilaian;
DROP TABLE IF EXISTS tb_periode_penilaian;
DROP TABLE IF EXISTS tb_kriteria;
DROP TABLE IF EXISTS tb_teknisi;
DROP TABLE IF EXISTS tb_user;

-- ============================================================
-- 1. Users / pengguna aplikasi
-- ============================================================
CREATE TABLE tb_user (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin','owner') NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role),
    INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Technicians / teknisi lapangan
-- ============================================================
CREATE TABLE tb_teknisi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_teknisi VARCHAR(10) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    keterangan TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teknisi_status (status),
    INDEX idx_teknisi_kode (kode_teknisi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Criteria / kriteria penilaian
-- ============================================================
CREATE TABLE tb_kriteria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(5) NOT NULL UNIQUE,
    nama_kriteria VARCHAR(100) NOT NULL,
    atribut ENUM('benefit','cost') NOT NULL DEFAULT 'benefit',
    bobot DECIMAL(3,2) NOT NULL,
    deskripsi TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_bobot_nonnegative CHECK (bobot >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Evaluation Periods / periode penilaian (quarterly / legacy)
-- ============================================================
CREATE TABLE tb_periode_penilaian (
    id_periode INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_periode VARCHAR(30) NOT NULL UNIQUE,
    nama_periode VARCHAR(100) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    status ENUM('draft','proses','selesai','legacy') NOT NULL DEFAULT 'draft',
    file_import VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_periode_user FOREIGN KEY (created_by)
        REFERENCES tb_user(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_periode_status (status),
    INDEX idx_periode_tanggal (tanggal_mulai, tanggal_selesai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Evaluations / penilaian kinerja (V1 backward-compatible + V2)
-- ============================================================
CREATE TABLE tb_penilaian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_periode INT UNSIGNED NULL,
    teknisi_id INT UNSIGNED NOT NULL,
    periode VARCHAR(30) NOT NULL,
    c1 DECIMAL(10,6) NOT NULL,
    c2 DECIMAL(10,6) NOT NULL,
    c3 DECIMAL(10,6) NOT NULL,
    status_data ENUM('draft','calculated','partial','confirmed','legacy') NOT NULL DEFAULT 'draft',
    jumlah_bulan_c1 TINYINT UNSIGNED NULL,
    jumlah_bulan_c2 TINYINT UNSIGNED NULL,
    jumlah_bulan_c3 TINYINT UNSIGNED NULL,
    warning TEXT NULL,
    confirmed_by INT UNSIGNED NULL,
    confirmed_at TIMESTAMP NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_teknisi_periode (teknisi_id, periode),
    CONSTRAINT fk_penilaian_teknisi FOREIGN KEY (teknisi_id)
        REFERENCES tb_teknisi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_penilaian_user FOREIGN KEY (created_by)
        REFERENCES tb_user(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_penilaian_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_penilaian_confirmed_by FOREIGN KEY (confirmed_by)
        REFERENCES tb_user(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_penilaian_periode (periode),
    INDEX idx_penilaian_teknisi (teknisi_id),
    INDEX idx_penilaian_id_periode (id_periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. SAW Results / hasil perhitungan (V1 backward-compatible + V2)
-- ============================================================
CREATE TABLE tb_hasil (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_periode INT UNSIGNED NULL,
    penilaian_id INT UNSIGNED NOT NULL,
    teknisi_id INT UNSIGNED NOT NULL,
    periode VARCHAR(30) NOT NULL,
    c1 DECIMAL(10,6) NULL,
    c2 DECIMAL(10,6) NULL,
    c3 DECIMAL(10,6) NULL,
    nilai_c1_normalisasi DECIMAL(10,6) NOT NULL,
    nilai_c2_normalisasi DECIMAL(10,6) NOT NULL,
    nilai_c3_normalisasi DECIMAL(10,6) NOT NULL,
    kontribusi_c1 DECIMAL(10,6) NOT NULL,
    kontribusi_c2 DECIMAL(10,6) NOT NULL,
    kontribusi_c3 DECIMAL(10,6) NOT NULL,
    bobot_c1 DECIMAL(3,2) NULL,
    bobot_c2 DECIMAL(3,2) NULL,
    bobot_c3 DECIMAL(3,2) NULL,
    nilai_preferensi DECIMAL(10,6) NOT NULL,
    ranking INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hasil_penilaian (penilaian_id),
    CONSTRAINT fk_hasil_penilaian FOREIGN KEY (penilaian_id)
        REFERENCES tb_penilaian(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hasil_teknisi FOREIGN KEY (teknisi_id)
        REFERENCES tb_teknisi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hasil_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_hasil_periode (periode),
    INDEX idx_hasil_teknisi (teknisi_id),
    INDEX idx_hasil_ranking (ranking),
    INDEX idx_hasil_id_periode (id_periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. Import Logs / riwayat import file excel
-- ============================================================
CREATE TABLE tb_import (
    id_import INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_periode INT UNSIGNED NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    nama_file_asli VARCHAR(255) NOT NULL,
    tanggal_import TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_data INT UNSIGNED NOT NULL DEFAULT 0,
    data_berhasil INT UNSIGNED NOT NULL DEFAULT 0,
    data_gagal INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending','processing','success','partial','failed') NOT NULL DEFAULT 'pending',
    pesan_error TEXT NULL,
    id_user INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_import_user FOREIGN KEY (id_user)
        REFERENCES tb_user(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_import_periode (id_periode),
    INDEX idx_import_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. Discipline Records / observasi kedisiplinan bulanan (C1)
-- ============================================================
CREATE TABLE tb_kedisiplinan (
    id_kedisiplinan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_periode INT UNSIGNED NOT NULL,
    id_teknisi INT UNSIGNED NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL COMMENT 'Month number 1-12',
    total_hari_kerja SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    hadir SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    sakit SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    izin SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    alpa SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    terlambat SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    pekerjaan_terjadwal SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    sesuai_jadwal SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_kedisiplinan_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kedisiplinan_teknisi FOREIGN KEY (id_teknisi)
        REFERENCES tb_teknisi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unique_kedisiplinan (id_periode, id_teknisi, bulan),
    INDEX idx_kedisiplinan_periode (id_periode),
    INDEX idx_kedisiplinan_teknisi (id_teknisi),
    CONSTRAINT chk_kedisiplinan_bulan CHECK (bulan BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. Work Quality Records / observasi kualitas pekerjaan (C2)
-- ============================================================
CREATE TABLE tb_pekerjaan (
    id_pekerjaan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_periode INT UNSIGNED NOT NULL,
    id_teknisi INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL COMMENT 'Month number 1-12',
    nama_pekerjaan VARCHAR(200) NOT NULL,
    rapi TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1=Ya, 0=Tidak',
    presisi TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1=Ya, 0=Tidak',
    sesuai_desain TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1=Ya, 0=Tidak',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pekerjaan_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pekerjaan_teknisi FOREIGN KEY (id_teknisi)
        REFERENCES tb_teknisi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pekerjaan_periode (id_periode),
    INDEX idx_pekerjaan_teknisi (id_teknisi),
    INDEX idx_pekerjaan_tanggal (tanggal),
    INDEX idx_pekerjaan_bulan (bulan),
    CONSTRAINT chk_pekerjaan_bulan CHECK (bulan BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. Responsibility Records / observasi tanggung jawab (C3)
-- ============================================================
CREATE TABLE tb_tanggung_jawab (
    id_tanggung_jawab INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_periode INT UNSIGNED NOT NULL,
    id_teknisi INT UNSIGNED NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL COMMENT 'Month number 1-12',
    perawatan_alat TINYINT UNSIGNED NOT NULL COMMENT '1-4 scale',
    efisiensi_material TINYINT UNSIGNED NOT NULL COMMENT '1-4 scale',
    inisiatif TINYINT UNSIGNED NOT NULL COMMENT '1-4 scale',
    kepatuhan_prosedur TINYINT UNSIGNED NOT NULL COMMENT '1-4 scale',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tanggung_jawab_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tanggung_jawab_teknisi FOREIGN KEY (id_teknisi)
        REFERENCES tb_teknisi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY unique_tanggung_jawab (id_periode, id_teknisi, bulan),
    INDEX idx_tanggung_jawab_periode (id_periode),
    INDEX idx_tanggung_jawab_teknisi (id_teknisi),
    CONSTRAINT chk_tj_bulan CHECK (bulan BETWEEN 1 AND 12),
    CONSTRAINT chk_tj_perawatan CHECK (perawatan_alat BETWEEN 1 AND 4),
    CONSTRAINT chk_tj_efisiensi CHECK (efisiensi_material BETWEEN 1 AND 4),
    CONSTRAINT chk_tj_inisiatif CHECK (inisiatif BETWEEN 1 AND 4),
    CONSTRAINT chk_tj_kepatuhan CHECK (kepatuhan_prosedur BETWEEN 1 AND 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
