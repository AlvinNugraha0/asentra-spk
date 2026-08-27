<?php
// ASENTRA SPK — Formatting helpers

declare(strict_types=1);

/**
 * Format a numeric SAW score with N decimals.
 */
function scoreFormat(float $value, int $decimals = 3): string
{
    return number_format($value, $decimals, ',', '.');
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
 * Format month period YYYY-MM to human-readable Indonesian month.
 */
function periodLabel(string $periode): string
{
    $parts = explode('-', $periode);
    if (count($parts) !== 2) {
        return $periode;
    }
    $year = $parts[0];
    $monthNum = (int) $parts[1];
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $monthName = $months[$monthNum] ?? $parts[1];
    return $monthName . ' ' . $year;
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
