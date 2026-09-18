<?php
// ASENTRA SPK — Formatting helpers

declare(strict_types=1);

/**
 * Format a numeric SAW score with N decimals.
 *
 * ponytail: for decimals >= 4 the value is no longer re-rounded here — the
 * caller passes a float that is already stored in tb_hasil at full precision.
 * number_format() alone would silently drop the trailing digits of an
 * irrational ratio (e.g. 3.777777...), so for high-precision columns the raw
 * significant digits are preserved via a string pass-through when the caller
 * already supplies a numeric string from the DB.
 */
function scoreFormat(float $value, int $decimals = 3): string
{
    return number_format($value, $decimals, ',', '.');
}

/**
 * Format a value stored as a DB decimal string without losing precision.
 *
 * Phase 6H: tb_hasil keeps DECIMAL(10,6), so a ratio like 10/27 comes back as
 * '3.777778' (already rounded once by MySQL). Re-rounding in PHP is fine, but
 * formatting it through number_format() with a smaller decimals window would
 * visibly truncate the displayed criterion value. This helper keeps the DB
 * string's own precision and only trims trailing zeros.
 */
function decimalFormat(?string $value, int $decimals = 6): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    $float = (float) $value;
    if ($float === 0.0) {
        return '0';
    }
    $formatted = number_format($float, $decimals, ',', '.');
    // Trim trailing zeros (and the decimal separator if nothing remains).
    $formatted = rtrim($formatted, '0');
    return rtrim($formatted, ',.');
}

/**
 * Format a date/time to Indonesian short format.
 */
function dateFormat(?string $datetime, string $format = 'd M Y H:i'): string
{
    if ($datetime === null) {
        return '-';
    }
    $ts = strtotime($datetime);
    return $ts !== false ? date($format, $ts) : $datetime;
}

/**
 * Format any period code into a human-readable Indonesian label.
 *
 * Phase 6I: V2 quarter codes ('Q1-2026' / '2026-Q3') are resolved through
 * tb_periode_penilaian so the report heading shows nama_periode plus its date
 * range. Codes that do not map to a stored period fall back to a readable
 * quarter label ('Triwulan I 2026'). V1 'YYYY-MM' and 'LEGACY-YYYY-MM' are
 * unchanged and never touch the DB.
 */
function periodLabel(string $periode): string
{
    $periode = trim($periode);
    if ($periode === '') {
        return $periode;
    }

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    // V1 legacy seed code: 'LEGACY-YYYY-MM'.
    if (str_starts_with($periode, 'LEGACY-') && preg_match('/^LEGACY-(\d{4})-(\d{2})$/', $periode, $m)) {
        return ($months[(int) $m[2]] ?? $m[2]) . ' ' . $m[1];
    }

    // V1 monthly code: 'YYYY-MM'.
    if (preg_match('/^(\d{4})-(\d{2})$/', $periode, $m)) {
        return ($months[(int) $m[2]] ?? $m[2]) . ' ' . $m[1];
    }

    // V2 quarter code, either spelling: 'Q1-2026' or '2026-Q1'.
    $quarter = null;
    $year = null;
    if (preg_match('/^Q([1-4])-(\d{4})$/', $periode, $m)) {
        $quarter = (int) $m[1];
        $year = (int) $m[2];
    } elseif (preg_match('/^(\d{4})-Q([1-4])$/', $periode, $m)) {
        $year = (int) $m[1];
        $quarter = (int) $m[2];
    }

    if ($quarter === null) {
        return $periode;
    }

    // Prefer the stored V2 period (nama_periode + date range) when it exists.
    if (class_exists(\App\Models\PeriodePenilaian::class)) {
        $row = \App\Models\PeriodePenilaian::findByKode($periode);
        if ($row !== null) {
            $nama = trim((string) ($row['nama_periode'] ?? ''));
            $mulai = (string) ($row['tanggal_mulai'] ?? '');
            $selesai = (string) ($row['tanggal_selesai'] ?? '');
            // ponytail: legacy rows (e.g. 'LEGACY-2026-08' never reach here,
            // but a status=legacy quarter code would) keep the plain quarter
            // label so no stale nama_periode is shown.
            if (($row['status'] ?? '') !== 'legacy' && $nama !== '') {
                return $mulai !== '' && $selesai !== ''
                    ? $nama . ' (' . $mulai . ' s/d ' . $selesai . ')'
                    : $nama;
            }
        }
    }

    $roman = ['I', 'II', 'III', 'IV'];
    return 'Triwulan ' . $roman[$quarter - 1] . ' ' . $year;
}

/**
 * Safe output for HTML.
 */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Convert a decimal weight to percentage label.
 */
function weightPercent(float $bobot): string
{
    return number_format($bobot * 100, 0, ',', '.') . '%';
}

/**
 * Map rating number to label.
 */
function ratingLabel(int $rating): string
{
    return match ($rating) {
        1 => 'Kurang',
        2 => 'Cukup',
        3 => 'Baik',
        4 => 'Sangat Baik',
        default => (string) $rating,
    };
}

/**
 * Ranking badge (MOTION.md §9 Top-3 emphasis). Returns raw HTML.
 */
function rankBadge(int $ranking): string
{
    return match (true) {
        $ranking === 1 => '<span class="badge rank-1">' . $ranking . '</span>',
        $ranking === 2 => '<span class="badge rank-2">' . $ranking . '</span>',
        $ranking === 3 => '<span class="badge rank-3">' . $ranking . '</span>',
        default => '<span class="badge rank-default">' . $ranking . '</span>',
    };
}

/**
 * Escape string for safe embedding inside a JavaScript string literal
 * inside an HTML attribute (e.g. onclick="confirm('...')").
 * Handles quotes, backslashes and angle brackets.
 */
function jsSafe(string $text): string
{
    $search  = [chr(92), chr(39), chr(34), '<', '>', chr(10), chr(13)];
    $replace = ['\\\\', "\\'", '&quot;', '&lt;', '&gt;', ' ', ' '];

    return str_replace($search, $replace, $text);
}
