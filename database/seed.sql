-- ASENTRA SPK Seed Data
-- Baseline dataset sesuai PRD section 10 & 11

USE asentra_spk;

-- Clear dependent tables first (TRUNCATE resets auto-increment)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE tb_hasil;
TRUNCATE TABLE tb_penilaian;
TRUNCATE TABLE tb_teknisi;
TRUNCATE TABLE tb_kriteria;
TRUNCATE TABLE tb_user;
SET FOREIGN_KEY_CHECKS = 1;

-- Users (default demo credentials)
-- admin / admin
-- owner / owner
INSERT INTO tb_user (id, username, password, nama, role, status) VALUES
(1, 'admin', '$2y$10$9OGfhhTM.Xc22KZOXefRS.1czPAP8TEj/LlUh3ozRq1rGQMUOdpYy', 'Administrator', 'admin', 'active'),
(2, 'owner', '$2y$10$LvO2Xci06uHBMh6AA9lG9Oe7f1hwCodvXJugj./9Dsic460hEH7WC', 'Owner ASENTRA', 'owner', 'active');

-- Criteria
INSERT INTO tb_kriteria (id, kode, nama_kriteria, atribut, bobot, deskripsi) VALUES
(1, 'C1', 'Kedisiplinan', 'benefit', 0.30, 'Kepatuhan jam hadir, ketepatan waktu, dan kepatuhan jadwal operasional.'),
(2, 'C2', 'Kualitas Hasil Kerja', 'benefit', 0.40, 'Kerapian, presisi, kekuatan struktural, dan kesesuaian dengan desain.'),
(3, 'C3', 'Tanggung Jawab', 'benefit', 0.30, 'Pemeliharaan alat, efisiensi material, dan inisiatif di lapangan.');

-- Technicians
INSERT INTO tb_teknisi (id, kode_teknisi, nama, status, keterangan) VALUES
(1,  'A1',  'Toni',            'active', 'Teknisi senior'),
(2,  'A2',  'Apip',            'active', ''),
(3,  'A3',  'Agus Supriyanto', 'active', ''),
(4,  'A4',  'Rahmat Hidayat',  'active', ''),
(5,  'A5',  'Ahmad Sahudin',   'active', ''),
(6,  'A6',  'Aris',            'active', ''),
(7,  'A7',  'IMADE',           'active', ''),
(8,  'A8',  'Asep',            'active', ''),
(9,  'A9',  'Wanto',           'active', ''),
(10, 'A10', 'Heri',            'active', '');

-- Evaluations for period 2026-08
INSERT INTO tb_penilaian (id, teknisi_id, periode, c1, c2, c3, created_by) VALUES
(1,  1,  '2026-08', 4, 4, 4, 1),
(2,  2,  '2026-08', 3, 4, 3, 1),
(3,  3,  '2026-08', 2, 3, 2, 1),
(4,  4,  '2026-08', 4, 3, 4, 1),
(5,  5,  '2026-08', 3, 3, 2, 1),
(6,  6,  '2026-08', 4, 4, 3, 1),
(7,  7,  '2026-08', 2, 4, 2, 1),
(8,  8,  '2026-08', 1, 2, 4, 1),
(9,  9,  '2026-08', 4, 3, 2, 1),
(10, 10, '2026-08', 3, 3, 3, 1);
