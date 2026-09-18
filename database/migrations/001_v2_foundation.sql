-- ============================================================
-- ASENTRA SPK — V2 Foundation Migration
-- ============================================================
-- Date: 2026-09-16
-- Purpose: Add V2 tables and columns while keeping V1 backward-compatible
-- 
-- SAFETY:
-- - No DROP TABLE
-- - No TRUNCATE
-- - No DELETE without specific condition
-- - All new columns are NULLable or have defaults
-- - V1 columns and constraints preserved
-- ============================================================

USE asentra_spk;

-- ============================================================
-- PART 1: NEW TABLES
-- ============================================================

-- 1.1 tb_periode_penilaian — Evaluation periods (quarterly)
CREATE TABLE IF NOT EXISTS tb_periode_penilaian (
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

-- 1.2 tb_import — Import log per period
CREATE TABLE IF NOT EXISTS tb_import (
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

-- 1.3 tb_kedisiplinan — Monthly discipline data (C1 raw data)
CREATE TABLE IF NOT EXISTS tb_kedisiplinan (
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

-- 1.4 tb_pekerjaan — Work quality records (C2 raw data)
CREATE TABLE IF NOT EXISTS tb_pekerjaan (
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

-- 1.5 tb_tanggung_jawab — Monthly responsibility data (C3 raw data)
CREATE TABLE IF NOT EXISTS tb_tanggung_jawab (
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

-- ============================================================
-- PART 2: ALTER tb_penilaian (backward-compatible)
-- ============================================================

-- 2.1 Add V2 columns
ALTER TABLE tb_penilaian
    ADD COLUMN id_periode INT UNSIGNED NULL AFTER id,
    ADD COLUMN status_data ENUM('draft','calculated','confirmed','legacy') NOT NULL DEFAULT 'legacy' AFTER c3,
    ADD COLUMN jumlah_bulan_c1 TINYINT UNSIGNED NULL AFTER status_data,
    ADD COLUMN jumlah_bulan_c2 TINYINT UNSIGNED NULL AFTER jumlah_bulan_c1,
    ADD COLUMN jumlah_bulan_c3 TINYINT UNSIGNED NULL AFTER jumlah_bulan_c2,
    ADD COLUMN warning TEXT NULL AFTER jumlah_bulan_c3,
    ADD COLUMN confirmed_by INT UNSIGNED NULL AFTER warning,
    ADD COLUMN confirmed_at TIMESTAMP NULL AFTER confirmed_by;

-- 2.2 Change c1/c2/c3 from TINYINT to DECIMAL(10,6)
-- Integer values like 4 become 4.000000 — backward compatible
ALTER TABLE tb_penilaian
    MODIFY COLUMN c1 DECIMAL(10,6) NOT NULL,
    MODIFY COLUMN c2 DECIMAL(10,6) NOT NULL,
    MODIFY COLUMN c3 DECIMAL(10,6) NOT NULL;

-- 2.3 Add foreign keys for V2 columns
ALTER TABLE tb_penilaian
    ADD CONSTRAINT fk_penilaian_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT fk_penilaian_confirmed_by FOREIGN KEY (confirmed_by)
        REFERENCES tb_user(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- 2.4 Add index on id_periode
ALTER TABLE tb_penilaian
    ADD INDEX idx_penilaian_id_periode (id_periode);

-- ============================================================
-- PART 3: ALTER tb_hasil (backward-compatible)
-- ============================================================

-- 3.1 Add V2 columns
ALTER TABLE tb_hasil
    ADD COLUMN id_periode INT UNSIGNED NULL AFTER id,
    ADD COLUMN c1 DECIMAL(10,6) NULL AFTER periode,
    ADD COLUMN c2 DECIMAL(10,6) NULL AFTER c1,
    ADD COLUMN c3 DECIMAL(10,6) NULL AFTER c2,
    ADD COLUMN bobot_c1 DECIMAL(3,2) NULL AFTER kontribusi_c3,
    ADD COLUMN bobot_c2 DECIMAL(3,2) NULL AFTER bobot_c1,
    ADD COLUMN bobot_c3 DECIMAL(3,2) NULL AFTER bobot_c2;

-- 3.2 Add foreign key for id_periode
ALTER TABLE tb_hasil
    ADD CONSTRAINT fk_hasil_periode FOREIGN KEY (id_periode)
        REFERENCES tb_periode_penilaian(id_periode) ON DELETE RESTRICT ON UPDATE CASCADE;

-- 3.3 Add index on id_periode
ALTER TABLE tb_hasil
    ADD INDEX idx_hasil_id_periode (id_periode);

-- ============================================================
-- PART 4: LEGACY DATA MIGRATION
-- ============================================================

-- 4.1 Create legacy periode records from existing distinct periodes
INSERT INTO tb_periode_penilaian (kode_periode, nama_periode, tanggal_mulai, tanggal_selesai, status, created_by)
SELECT DISTINCT
    CONCAT('LEGACY-', p.periode) AS kode_periode,
    CONCAT('Legacy ',
        CASE SUBSTRING(p.periode, 6, 2)
            WHEN '01' THEN 'Januari'
            WHEN '02' THEN 'Februari'
            WHEN '03' THEN 'Maret'
            WHEN '04' THEN 'April'
            WHEN '05' THEN 'Mei'
            WHEN '06' THEN 'Juni'
            WHEN '07' THEN 'Juli'
            WHEN '08' THEN 'Agustus'
            WHEN '09' THEN 'September'
            WHEN '10' THEN 'Oktober'
            WHEN '11' THEN 'November'
            WHEN '12' THEN 'Desember'
        END,
        ' ',
        SUBSTRING(p.periode, 1, 4)
    ) AS nama_periode,
    CONCAT(p.periode, '-01') AS tanggal_mulai,
    LAST_DAY(CONCAT(p.periode, '-01')) AS tanggal_selesai,
    'legacy' AS status,
    NULL AS created_by
FROM tb_penilaian p
ORDER BY p.periode;

-- 4.2 Link tb_penilaian to legacy periode records
UPDATE tb_penilaian p
JOIN tb_periode_penilaian pp ON pp.kode_periode = CONCAT('LEGACY-', p.periode)
SET p.id_periode = pp.id_periode,
    p.status_data = 'legacy'
WHERE p.id_periode IS NULL;

-- 4.3 Link tb_hasil to legacy periode records
UPDATE tb_hasil h
JOIN tb_periode_penilaian pp ON pp.kode_periode = CONCAT('LEGACY-', h.periode)
SET h.id_periode = pp.id_periode
WHERE h.id_periode IS NULL;

-- 4.4 Backfill tb_hasil.c1/c2/c3 from tb_penilaian
UPDATE tb_hasil h
JOIN tb_penilaian p ON p.id = h.penilaian_id
SET h.c1 = p.c1,
    h.c2 = p.c2,
    h.c3 = p.c3
WHERE h.c1 IS NULL;

-- 4.5 Backfill tb_hasil.bobot_c1/c2/c3 from tb_kriteria
UPDATE tb_hasil h
SET h.bobot_c1 = (SELECT bobot FROM tb_kriteria WHERE kode = 'C1'),
    h.bobot_c2 = (SELECT bobot FROM tb_kriteria WHERE kode = 'C2'),
    h.bobot_c3 = (SELECT bobot FROM tb_kriteria WHERE kode = 'C3')
WHERE h.bobot_c1 IS NULL;

-- ============================================================
-- END OF MIGRATION
-- ============================================================
