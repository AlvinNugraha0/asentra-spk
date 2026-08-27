-- ASENTRA SPK Database Schema
-- MySQL / MariaDB
-- Tables: tb_user, tb_teknisi, tb_kriteria, tb_penilaian, tb_hasil

CREATE DATABASE IF NOT EXISTS asentra_spk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE asentra_spk;

-- Users / pengguna aplikasi
DROP TABLE IF EXISTS tb_hasil;
DROP TABLE IF EXISTS tb_penilaian;
DROP TABLE IF EXISTS tb_kriteria;
DROP TABLE IF EXISTS tb_teknisi;
DROP TABLE IF EXISTS tb_user;

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

-- Technicians / teknisi lapangan
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

-- Criteria / kriteria penilaian
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

-- Evaluations / penilaian kinerja
CREATE TABLE tb_penilaian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teknisi_id INT UNSIGNED NOT NULL,
    periode VARCHAR(7) NOT NULL,
    c1 TINYINT UNSIGNED NOT NULL,
    c2 TINYINT UNSIGNED NOT NULL,
    c3 TINYINT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_teknisi_periode (teknisi_id, periode),
    CONSTRAINT fk_penilaian_teknisi FOREIGN KEY (teknisi_id)
        REFERENCES tb_teknisi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_penilaian_user FOREIGN KEY (created_by)
        REFERENCES tb_user(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_c1_range CHECK (c1 BETWEEN 1 AND 4),
    CONSTRAINT chk_c2_range CHECK (c2 BETWEEN 1 AND 4),
    CONSTRAINT chk_c3_range CHECK (c3 BETWEEN 1 AND 4),
    INDEX idx_penilaian_periode (periode),
    INDEX idx_penilaian_teknisi (teknisi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SAW results / hasil perhitungan
CREATE TABLE tb_hasil (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    penilaian_id INT UNSIGNED NOT NULL,
    teknisi_id INT UNSIGNED NOT NULL,
    periode VARCHAR(7) NOT NULL,
    nilai_c1_normalisasi DECIMAL(10,6) NOT NULL,
    nilai_c2_normalisasi DECIMAL(10,6) NOT NULL,
    nilai_c3_normalisasi DECIMAL(10,6) NOT NULL,
    kontribusi_c1 DECIMAL(10,6) NOT NULL,
    kontribusi_c2 DECIMAL(10,6) NOT NULL,
    kontribusi_c3 DECIMAL(10,6) NOT NULL,
    nilai_preferensi DECIMAL(10,6) NOT NULL,
    ranking INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hasil_penilaian (penilaian_id),
    CONSTRAINT fk_hasil_penilaian FOREIGN KEY (penilaian_id)
        REFERENCES tb_penilaian(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hasil_teknisi FOREIGN KEY (teknisi_id)
        REFERENCES tb_teknisi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_hasil_periode (periode),
    INDEX idx_hasil_teknisi (teknisi_id),
    INDEX idx_hasil_ranking (ranking)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
