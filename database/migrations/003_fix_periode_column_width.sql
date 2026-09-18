-- ASENTRA SPK — Migration 003: Fix periode column width for V2 quarter codes
-- Phase 6G
--
-- PROBLEM:
--   tb_penilaian.periode and tb_hasil.periode are VARCHAR(7).
--   V1 codes 'YYYY-MM' fit (7 chars), but V2 quarter codes 'YYYY-QX' are 8 chars
--   and get silently truncated by substr(kode_periode,0,7) in
--   SawServiceV2 / CalculationEngine — producing invalid '2026-Q' values that
--   break NOT NULL + period lookups (RankingController, LaporanController).
--
-- FIX:
--   1. Widen both columns to VARCHAR(30) so V1 and V2 codes fit as-is.
--   2. Repair any already-truncated data where the correct code is recoverable.
--
-- SAFETY:
--   - V1 'YYYY-MM' rows are untouched (length already fits).
--   - Legacy rows are never modified.
--   - Fully reversible: shrinking back to VARCHAR(7) restores the old limit
--     (but truncated data must be re-derived from tb_periode_penilaian).

-- 1. Widen the period-code columns.
ALTER TABLE tb_penilaian MODIFY COLUMN periode VARCHAR(30) NOT NULL;
ALTER TABLE tb_hasil    MODIFY COLUMN periode VARCHAR(30) NOT NULL;

-- 2. Repair truncated period codes: '2026-Q' + quarter digit recovery.
--    A truncated V2 row has periode like '2026-Q' (6 chars); the true code is
--    rebuilt from tb_periode_penilaian via id_periode when the period exists.
UPDATE tb_penilaian p
JOIN tb_periode_penilaian pp ON pp.id_periode = p.id_periode
SET p.periode = pp.kode_periode
WHERE p.id_periode IS NOT NULL
  AND p.status_data <> 'legacy'
  AND p.periode <> pp.kode_periode;

UPDATE tb_hasil h
JOIN tb_periode_penilaian pp ON pp.id_periode = h.id_periode
SET h.periode = pp.kode_periode
WHERE h.id_periode IS NOT NULL
  AND h.periode <> pp.kode_periode;
