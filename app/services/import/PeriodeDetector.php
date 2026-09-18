<?php
// ASENTRA SPK — Periode Detector
// Phase 6K: derives the evaluation quarter from the DATA inside an Excel workbook.
// Keeps all spreadsheet reading here — never in the controller.

declare(strict_types=1);

namespace App\Services\Import;

if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
}

use App\Models\PeriodePenilaian;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class PeriodeDetector
{
    /**
     * Month names, 1-indexed, Indonesian.
     */
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Read the workbook and derive the quarter from its data.
     *
     * Detection source (in order of authority):
     *   1. tanggal column of KUALITAS_KERJA (min/max real dates)
     *   2. bulan column of KEDISIPLINAN / TANGGUNG_JAWAB
     *
     * Never reads the filename or any hidden cell.
     *
     * @return array{
     *   ok: bool,
     *   error: string,
     *   year: int|null,
     *   quarter: int|null,
     *   kode_periode: string|null,
     *   nama_periode: string|null,
     *   tanggal_mulai: string|null,
     *   tanggal_selesai: string|null,
     *   months: array<int, int>,
     *   dates: array{min: string|null, max: string|null}
     * }
     */
    public static function detect(string $filePath): array
    {
        $fail = static fn (string $msg): array => [
            'ok' => false,
            'error' => $msg,
            'year' => null,
            'quarter' => null,
            'kode_periode' => null,
            'nama_periode' => null,
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'months' => [],
            'dates' => ['min' => null, 'max' => null],
        ];

        if (!is_file($filePath) || !is_readable($filePath)) {
            return $fail('File Excel tidak ditemukan atau tidak dapat dibaca.');
        }

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (Throwable $e) {
            return $fail('File tidak dapat dibaca sebagai Excel (.xlsx) yang valid: ' . $e->getMessage());
        }

        $sheetMap = [];
        foreach ($spreadsheet->getSheetNames() as $name) {
            $sheetMap[strtoupper(trim($name))] = $name;
        }
        foreach (ExcelTemplateHelper::REQUIRED_SHEETS as $reqSheet) {
            if (!isset($sheetMap[$reqSheet])) {
                return $fail("Sheet wajib '{$reqSheet}' tidak ditemukan dalam file Excel.");
            }
        }

        $months = [];
        $dateStrings = [];

        // 1. Real dates from KUALITAS_KERJA (primary source).
        $sheetKualitas = $spreadsheet->getSheetByName($sheetMap[ExcelTemplateHelper::SHEET_KUALITAS]);
        $colsKualitas = self::columnMap($sheetKualitas);
        $tglCol = $colsKualitas['tanggal'] ?? null;
        $bulanKualitasCol = $colsKualitas['bulan'] ?? null;
        if ($tglCol !== null) {
            foreach (self::rows($sheetKualitas, $tglCol) as $raw) {
                $iso = self::normalizeDate($raw);
                if ($iso !== null) {
                    $dateStrings[$iso] = true;
                    $months[(int) substr($iso, 5, 2)] = true;
                }
            }
        }
        if ($bulanKualitasCol !== null) {
            foreach (self::rows($sheetKualitas, $bulanKualitasCol) as $raw) {
                $m = filter_var($raw, FILTER_VALIDATE_INT);
                if ($m !== false && $m >= 1 && $m <= 12) {
                    $months[$m] = true;
                }
            }
        }

        // 2. Month numbers from the bulan-only sheets (secondary source).
        foreach ([ExcelTemplateHelper::SHEET_KEDISIPLINAN, ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB] as $sheetKey) {
            $sheet = $spreadsheet->getSheetByName($sheetMap[$sheetKey]);
            $cols = self::columnMap($sheet);
            $bulanCol = $cols['bulan'] ?? null;
            if ($bulanCol === null) {
                continue;
            }
            foreach (self::rows($sheet, $bulanCol) as $raw) {
                $m = filter_var($raw, FILTER_VALIDATE_INT);
                if ($m !== false && $m >= 1 && $m <= 12) {
                    $months[$m] = true;
                }
            }
        }

        $monthList = array_keys($months);
        sort($monthList);

        if (empty($monthList)) {
            return $fail('Tidak ada data tanggal/bulan yang dapat dibaca dari file Excel.');
        }

        // 3. All observed months must sit inside exactly one calendar quarter.
        $quarterOf = static function (int $m): int {
            return intdiv($m - 1, 3) + 1;
        };
        $quarters = array_unique(array_map($quarterOf, $monthList));

        if (count($quarters) !== 1) {
            $labelled = array_map(
                static fn (int $m): string => self::MONTHS[$m],
                $monthList
            );
            return $fail(
                'Data menjangkau lebih dari satu kuartal (bulan: '
                . implode(', ', $labelled) . '). Satu file Excel hanya boleh berisi data satu triwulan.'
            );
        }

        $quarter = $quarters[0];

        // 4. Year: from real dates when available, else from bulan rows are yearless
        //    so fall back to the workbook's implicit year is impossible — require dates.
        $year = null;
        if (!empty($dateStrings)) {
            $sorted = array_keys($dateStrings);
            sort($sorted);
            $year = (int) substr($sorted[0], 0, 4);
            foreach ($sorted as $iso) {
                $y = (int) substr($iso, 0, 4);
                if ($y !== $year) {
                    return $fail(
                        'Data menjangkau lebih dari satu tahun (' . $year . ' dan ' . $y
                        . '). Satu file Excel hanya boleh berisi data satu triwulan.'
                    );
                }
            }
        }
        if ($year === null || $year < 2000 || $year > 2100) {
            return $fail('Tahun tidak dapat ditentukan dari data — sheet KUALITAS_KERJA wajib berisi kolom tanggal.');
        }

        // 5. Months must not be spread across two quarters (already proven above);
        //    partial coverage (only 2 months of data) is fine — ExcelValidator
        //    checks each row against the quarter, absent months are simply empty data.
        $kode = sprintf('%04d-Q%d', $year, $quarter);
        $start = sprintf('%04d-%02d-01', $year, ($quarter - 1) * 3 + 1);
        $end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $quarter * 3)));

        // 6. Calendar validation (same rule as AdminPeriodeController::store).
        $rangeCheck = PeriodePenilaian::validateQuarterRange($kode, $start, $end);
        if (!$rangeCheck['valid']) {
            return $fail('Periode terdeteksi tidak sesuai kalender kuartal: ' . implode(' ', $rangeCheck['errors']));
        }

        return [
            'ok' => true,
            'error' => '',
            'year' => $year,
            'quarter' => $quarter,
            'kode_periode' => $kode,
            'nama_periode' => self::MONTHS[($quarter - 1) * 3 + 1] . ' - ' . self::MONTHS[$quarter * 3] . ' ' . $year,
            'tanggal_mulai' => $start,
            'tanggal_selesai' => $end,
            'months' => $monthList,
            'dates' => ['min' => $dateStrings ? min(array_keys($dateStrings)) : null, 'max' => $dateStrings ? max(array_keys($dateStrings)) : null],
        ];
    }

    /**
     * Resolve a detected quarter into an existing period, or create it (status=draft).
     * Race-safe: UNIQUE(kode_periode) violation -> re-read the winner, never a duplicate.
     *
     * @return array{ok: bool, error: string, id_periode: int|null, created: bool, periode: array<string, mixed>|null}
     */
    public static function resolveOrCreate(array $detected, int $adminId): array
    {
        $kode = (string) ($detected['kode_periode'] ?? '');

        if ($kode === '' || !preg_match('/^\d{4}-Q[1-4]$/', $kode)) {
            return ['ok' => false, 'error' => 'Hasil deteksi periode tidak valid.', 'id_periode' => null, 'created' => false, 'periode' => null];
        }

        $existing = PeriodePenilaian::findByKode($kode);
        if ($existing !== null) {
            return ['ok' => true, 'error' => '', 'id_periode' => (int) $existing['id_periode'], 'created' => false, 'periode' => $existing];
        }

        try {
            $newId = PeriodePenilaian::create([
                'kode_periode' => $kode,
                'nama_periode' => (string) ($detected['nama_periode'] ?? $kode),
                'tanggal_mulai' => (string) ($detected['tanggal_mulai'] ?? ''),
                'tanggal_selesai' => (string) ($detected['tanggal_selesai'] ?? ''),
                'status' => 'draft',
                'created_by' => $adminId,
            ]);
            return ['ok' => true, 'error' => '', 'id_periode' => $newId, 'created' => true, 'periode' => PeriodePenilaian::findByKode($kode)];
        } catch (Throwable $e) {
            // Lost the race against a concurrent create: reuse the winner.
            $winner = PeriodePenilaian::findByKode($kode);
            if ($winner !== null) {
                return ['ok' => true, 'error' => '', 'id_periode' => (int) $winner['id_periode'], 'created' => false, 'periode' => $winner];
            }
            return ['ok' => false, 'error' => 'Gagal membuat periode: ' . $e->getMessage(), 'id_periode' => null, 'created' => false, 'periode' => null];
        }
    }

    /**
     * Map header names (row 1) to 0-based column indexes for a sheet.
     *
     * @return array<string, int>
     */
    private static function columnMap(Worksheet $sheet): array
    {
        $map = [];
        $highestColumn = $sheet->getHighestDataColumn();
        $rowRange = $sheet->rangeToArray("A1:{$highestColumn}1", null, true, true, false);
        if (!empty($rowRange[0])) {
            foreach ($rowRange[0] as $idx => $value) {
                $map[strtolower(trim((string) $value))] = (int) $idx;
            }
        }
        return $map;
    }

    /**
     * Yield raw values of one column for every non-empty data row (from row 2).
     *
     * @return iterable<int, mixed>
     */
    private static function rows(Worksheet $sheet, int $colIdx): iterable
    {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
        $highestRow = $sheet->getHighestDataRow();
        for ($r = 2; $r <= $highestRow; $r++) {
            $cell = $sheet->getCell("{$colLetter}{$r}");
            if ($cell === null) {
                continue;
            }
            $value = $cell->getValue();
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }
            // Real Excel date cells arrive as serial numbers — normalise to Y-m-d.
            if (ExcelDate::isDateTime($cell)) {
                try {
                    yield ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
                } catch (Throwable) {
                    yield $value;
                }
                continue;
            }
            yield $value;
        }
    }

    /**
     * Normalize a raw cell value to a Y-m-d string, or null.
     */
    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }
        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }
        $ts = strtotime($str);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
