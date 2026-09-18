-- ============================================================
-- ASENTRA SPK — V2 Migration 002
-- Add 'partial' enum value to tb_penilaian.status_data
-- ============================================================

USE asentra_spk;

ALTER TABLE tb_penilaian
    MODIFY COLUMN status_data ENUM('draft','calculated','partial','confirmed','legacy') NOT NULL DEFAULT 'draft';
