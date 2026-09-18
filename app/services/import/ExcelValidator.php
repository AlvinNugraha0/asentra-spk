<?php
// ASENTRA SPK — Excel Validator

declare(strict_types=1);

namespace App\Services\Import;

if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
}

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelValidator
{
    /**
     * Validate an open spreadsheet workbook against expected sheets, headers, and row data.
     *
     * @param Spreadsheet $spreadsheet
     * @param array<string, mixed> $periode Target period record from tb_periode_penilaian
     * @param array<string, int> $validTeknisiMap Map of [kode_teknisi => id_teknisi]
     *
     * @return array{
     *   is_valid: bool,
     *   fatal_error: ?string,
     *   errors: array<int, string>,
     *   parsed_data: array<string, mixed>,
     *   stats: array{total_rows: int, valid_rows: int, invalid_rows: int}
     * }
     */
    public static function validate(
        Spreadsheet $spreadsheet,
        array $periode,
        array $validTeknisiMap
    ): array {
        $errors = [];
        $totalRows = 0;
        $validRows = 0;
        $invalidRows = 0;

        // 1. Validate mandatory sheets
        $sheetNames = $spreadsheet->getSheetNames();
        $sheetMap = [];
        foreach ($sheetNames as $name) {
            $sheetMap[strtoupper(trim($name))] = $name;
        }

        foreach (ExcelTemplateHelper::REQUIRED_SHEETS as $reqSheet) {
            if (!isset($sheetMap[$reqSheet])) {
                return [
                    'is_valid' => false,
                    'fatal_error' => "Sheet wajib '{$reqSheet}' tidak ditemukan dalam file Excel.",
                    'errors' => ["Sheet wajib '{$reqSheet}' tidak ditemukan."],
                    'parsed_data' => [],
                    'stats' => ['total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0],
                ];
            }
        }

        // Expected date range and months for the period
        $startDate = !empty($periode['tanggal_mulai']) ? (string) $periode['tanggal_mulai'] : '';
        $endDate = !empty($periode['tanggal_selesai']) ? (string) $periode['tanggal_selesai'] : '';
        $startTs = $startDate !== '' ? strtotime($startDate) : 0;
        $endTs = $endDate !== '' ? strtotime($endDate) : PHP_INT_MAX;

        $startMonth = (int) date('n', $startTs);
        $endMonth = (int) date('n', $endTs);
        $expectedMonths = [];
        if ($startMonth > 0 && $endMonth >= $startMonth) {
            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $expectedMonths[] = $m;
            }
        }

        // 2. Validate Sheet DATA_TEKNISI
        $sheetTeknisi = $spreadsheet->getSheetByName($sheetMap[ExcelTemplateHelper::SHEET_TEKNISI]);
        $headersTeknisi = self::extractHeaders($sheetTeknisi);
        $headerErr = self::verifyHeaders(ExcelTemplateHelper::SHEET_TEKNISI, $headersTeknisi, ExcelTemplateHelper::HEADERS_TEKNISI);
        if ($headerErr !== null) {
            return self::fatalHeaderError($headerErr);
        }

        $teknisiRows = self::readSheetRows($sheetTeknisi);
        $parsedTeknisi = [];
        foreach ($teknisiRows as $rowIdx => $row) {
            $totalRows++;
            $kode = trim((string) ($row['kode_teknisi'] ?? ''));
            $nama = trim((string) ($row['nama_teknisi'] ?? ''));

            if ($kode === '') {
                $errors[] = "Sheet DATA_TEKNISI Baris {$rowIdx}: kode_teknisi wajib diisi.";
                $invalidRows++;
                continue;
            }

            if (!isset($validTeknisiMap[$kode])) {
                $errors[] = "Sheet DATA_TEKNISI Baris {$rowIdx}: Teknisi dengan kode '{$kode}' tidak terdaftar di sistem.";
                $invalidRows++;
                continue;
            }

            $parsedTeknisi[$kode] = [
                'id_teknisi' => $validTeknisiMap[$kode],
                'kode_teknisi' => $kode,
                'nama_teknisi' => $nama,
            ];
            $validRows++;
        }

        // 3. Validate Sheet KEDISIPLINAN
        $sheetDisiplin = $spreadsheet->getSheetByName($sheetMap[ExcelTemplateHelper::SHEET_KEDISIPLINAN]);
        $headersDisiplin = self::extractHeaders($sheetDisiplin);
        $headerErr = self::verifyHeaders(ExcelTemplateHelper::SHEET_KEDISIPLINAN, $headersDisiplin, ExcelTemplateHelper::HEADERS_KEDISIPLINAN);
        if ($headerErr !== null) {
            return self::fatalHeaderError($headerErr);
        }

        $disiplinRows = self::readSheetRows($sheetDisiplin);
        $parsedDisiplin = [];
        $seenDisiplin = [];

        foreach ($disiplinRows as $rowIdx => $row) {
            $totalRows++;
            $kode = trim((string) ($row['kode_teknisi'] ?? ''));
            $bulan = filter_var($row['bulan'] ?? null, FILTER_VALIDATE_INT);

            $rowErrors = [];

            if ($kode === '' || !isset($validTeknisiMap[$kode])) {
                $rowErrors[] = "Teknisi '{$kode}' tidak ditemukan atau tidak valid.";
            }

            if ($bulan === false || $bulan < 1 || $bulan > 12) {
                $rowErrors[] = "bulan harus berupa angka 1-12.";
            } elseif (!empty($expectedMonths) && !in_array($bulan, $expectedMonths, true)) {
                $expectedStr = implode(', ', $expectedMonths);
                $rowErrors[] = "bulan {$bulan} di luar rentang kuartal periode ({$expectedStr}).";
            }

            // Numeric non-negative validation
            $totalHari = self::parseNonNegativeInt($row['total_hari_kerja'] ?? null);
            $hadir = self::parseNonNegativeInt($row['hadir'] ?? null);
            $sakit = self::parseNonNegativeInt($row['sakit'] ?? 0);
            $izin = self::parseNonNegativeInt($row['izin'] ?? 0);
            $alpa = self::parseNonNegativeInt($row['alpa'] ?? 0);
            $terlambat = self::parseNonNegativeInt($row['terlambat'] ?? 0);
            $pekerjaanTerjadwal = self::parseNonNegativeInt($row['pekerjaan_terjadwal'] ?? null);
            $sesuaiJadwal = self::parseNonNegativeInt($row['sesuai_jadwal'] ?? null);

            if ($totalHari === null) $rowErrors[] = "total_hari_kerja harus berupa angka non-negatif.";
            if ($hadir === null) $rowErrors[] = "hadir harus berupa angka non-negatif.";
            if ($sakit === null) $rowErrors[] = "sakit harus berupa angka non-negatif.";
            if ($izin === null) $rowErrors[] = "izin harus berupa angka non-negatif.";
            if ($alpa === null) $rowErrors[] = "alpa harus berupa angka non-negatif.";
            if ($terlambat === null) $rowErrors[] = "terlambat harus berupa angka non-negatif.";
            if ($pekerjaanTerjadwal === null) $rowErrors[] = "pekerjaan_terjadwal harus berupa angka non-negatif.";
            if ($sesuaiJadwal === null) $rowErrors[] = "sesuai_jadwal harus berupa angka non-negatif.";

            // Denominator consistency
            if ($totalHari !== null && $hadir !== null && $hadir > $totalHari) {
                $rowErrors[] = "hadir ({$hadir}) tidak boleh melebihi total_hari_kerja ({$totalHari}).";
            }
            if ($pekerjaanTerjadwal !== null && $sesuaiJadwal !== null && $sesuaiJadwal > $pekerjaanTerjadwal) {
                $rowErrors[] = "sesuai_jadwal ({$sesuaiJadwal}) tidak boleh melebihi pekerjaan_terjadwal ({$pekerjaanTerjadwal}).";
            }

            // Duplicate check
            $key = "{$kode}_{$bulan}";
            if (isset($seenDisiplin[$key])) {
                $rowErrors[] = "Duplikasi data kedisiplinan untuk teknisi '{$kode}' pada bulan {$bulan}.";
            } else {
                $seenDisiplin[$key] = true;
            }

            if (!empty($rowErrors)) {
                $invalidRows++;
                foreach ($rowErrors as $err) {
                    $errors[] = "Sheet KEDISIPLINAN Baris {$rowIdx}: {$err}";
                }
            } else {
                $validRows++;
                $parsedDisiplin[] = [
                    'id_teknisi' => $validTeknisiMap[$kode],
                    'kode_teknisi' => $kode,
                    'bulan' => $bulan,
                    'total_hari_kerja' => $totalHari,
                    'hadir' => $hadir,
                    'sakit' => $sakit,
                    'izin' => $izin,
                    'alpa' => $alpa,
                    'terlambat' => $terlambat,
                    'pekerjaan_terjadwal' => $pekerjaanTerjadwal,
                    'sesuai_jadwal' => $sesuaiJadwal,
                ];
            }
        }

        // 4. Validate Sheet KUALITAS_KERJA
        $sheetKualitas = $spreadsheet->getSheetByName($sheetMap[ExcelTemplateHelper::SHEET_KUALITAS]);
        $headersKualitas = self::extractHeaders($sheetKualitas);
        $headerErr = self::verifyHeaders(ExcelTemplateHelper::SHEET_KUALITAS, $headersKualitas, ExcelTemplateHelper::HEADERS_KUALITAS);
        if ($headerErr !== null) {
            return self::fatalHeaderError($headerErr);
        }

        $kualitasRows = self::readSheetRows($sheetKualitas, true);
        $parsedKualitas = [];
        $seenPekerjaan = [];

        foreach ($kualitasRows as $rowIdx => $row) {
            $totalRows++;
            $kode = trim((string) ($row['kode_teknisi'] ?? ''));
            $namaPekerjaan = trim((string) ($row['nama_pekerjaan'] ?? ''));
            $rawDate = $row['tanggal'] ?? null;
            $bulan = filter_var($row['bulan'] ?? null, FILTER_VALIDATE_INT);

            $rowErrors = [];

            if ($kode === '' || !isset($validTeknisiMap[$kode])) {
                $rowErrors[] = "Teknisi '{$kode}' tidak ditemukan atau tidak valid.";
            }

            if ($namaPekerjaan === '') {
                $rowErrors[] = "nama_pekerjaan wajib diisi.";
            }

            // Parse Date
            $normalizedDate = self::normalizeDate($rawDate);
            if ($normalizedDate === null) {
                $rowErrors[] = "tanggal tidak valid (format YYYY-MM-DD).";
            } else {
                $dateTs = strtotime($normalizedDate);
                if ($dateTs < $startTs || $dateTs > $endTs) {
                    $rowErrors[] = "tanggal '{$normalizedDate}' berada di luar rentang periode ({$startDate} s/d {$endDate}).";
                }

                $dateMonth = (int) date('n', $dateTs);
                if ($bulan !== false && $bulan !== $dateMonth) {
                    $rowErrors[] = "bulan ({$bulan}) tidak cocok dengan bulan pada tanggal ({$dateMonth}).";
                }
            }

            if ($bulan === false || $bulan < 1 || $bulan > 12) {
                $rowErrors[] = "bulan harus berupa angka 1-12.";
            }

            // Unreasonable duplicate check: same technician, same date, same job name
            if ($kode !== '' && $normalizedDate !== null && $namaPekerjaan !== '') {
                $jobKey = "{$kode}_{$normalizedDate}_" . mb_strtolower($namaPekerjaan);
                if (isset($seenPekerjaan[$jobKey])) {
                    $rowErrors[] = "Duplikasi data pekerjaan untuk teknisi '{$kode}' pada tanggal {$normalizedDate} dengan pekerjaan '{$namaPekerjaan}'.";
                } else {
                    $seenPekerjaan[$jobKey] = true;
                }
            }

            // Quality booleans: 0 or 1
            $rapi = self::parseBooleanIndicator($row['rapi'] ?? null);
            $presisi = self::parseBooleanIndicator($row['presisi'] ?? null);
            $sesuaiDesain = self::parseBooleanIndicator($row['sesuai_desain'] ?? null);

            if ($rapi === null) $rowErrors[] = "rapi harus bernilai 1 (Ya) atau 0 (Tidak).";
            if ($presisi === null) $rowErrors[] = "presisi harus bernilai 1 (Ya) atau 0 (Tidak).";
            if ($sesuaiDesain === null) $rowErrors[] = "sesuai_desain harus bernilai 1 (Ya) atau 0 (Tidak).";

            if (!empty($rowErrors)) {
                $invalidRows++;
                foreach ($rowErrors as $err) {
                    $errors[] = "Sheet KUALITAS_KERJA Baris {$rowIdx}: {$err}";
                }
            } else {
                $validRows++;
                $parsedKualitas[] = [
                    'id_teknisi' => $validTeknisiMap[$kode],
                    'kode_teknisi' => $kode,
                    'tanggal' => $normalizedDate,
                    'bulan' => $bulan,
                    'nama_pekerjaan' => $namaPekerjaan,
                    'rapi' => $rapi,
                    'presisi' => $presisi,
                    'sesuai_desain' => $sesuaiDesain,
                ];
            }
        }

        // 5. Validate Sheet TANGGUNG_JAWAB
        $sheetTJ = $spreadsheet->getSheetByName($sheetMap[ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB]);
        $headersTJ = self::extractHeaders($sheetTJ);
        $headerErr = self::verifyHeaders(ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB, $headersTJ, ExcelTemplateHelper::HEADERS_TANGGUNG_JAWAB);
        if ($headerErr !== null) {
            return self::fatalHeaderError($headerErr);
        }

        $tjRows = self::readSheetRows($sheetTJ);
        $parsedTJ = [];
        $seenTJ = [];

        foreach ($tjRows as $rowIdx => $row) {
            $totalRows++;
            $kode = trim((string) ($row['kode_teknisi'] ?? ''));
            $bulan = filter_var($row['bulan'] ?? null, FILTER_VALIDATE_INT);

            $rowErrors = [];

            if ($kode === '' || !isset($validTeknisiMap[$kode])) {
                $rowErrors[] = "Teknisi '{$kode}' tidak ditemukan atau tidak valid.";
            }

            if ($bulan === false || $bulan < 1 || $bulan > 12) {
                $rowErrors[] = "bulan harus berupa angka 1-12.";
            } elseif (!empty($expectedMonths) && !in_array($bulan, $expectedMonths, true)) {
                $expectedStr = implode(', ', $expectedMonths);
                $rowErrors[] = "bulan {$bulan} di luar rentang kuartal periode ({$expectedStr}).";
            }

            // Ratings must be integer in [1, 2, 3, 4]
            $alat = self::parseRating($row['perawatan_alat'] ?? null);
            $material = self::parseRating($row['efisiensi_material'] ?? null);
            $inisiatif = self::parseRating($row['inisiatif'] ?? null);
            $prosedur = self::parseRating($row['kepatuhan_prosedur'] ?? null);

            if ($alat === null) $rowErrors[] = "perawatan_alat harus bernilai skala 1-4.";
            if ($material === null) $rowErrors[] = "efisiensi_material harus bernilai skala 1-4.";
            if ($inisiatif === null) $rowErrors[] = "inisiatif harus bernilai skala 1-4.";
            if ($prosedur === null) $rowErrors[] = "kepatuhan_prosedur harus bernilai skala 1-4.";

            // Duplicate check
            $key = "{$kode}_{$bulan}";
            if (isset($seenTJ[$key])) {
                $rowErrors[] = "Duplikasi data tanggung jawab untuk teknisi '{$kode}' pada bulan {$bulan}.";
            } else {
                $seenTJ[$key] = true;
            }

            if (!empty($rowErrors)) {
                $invalidRows++;
                foreach ($rowErrors as $err) {
                    $errors[] = "Sheet TANGGUNG_JAWAB Baris {$rowIdx}: {$err}";
                }
            } else {
                $validRows++;
                $parsedTJ[] = [
                    'id_teknisi' => $validTeknisiMap[$kode],
                    'kode_teknisi' => $kode,
                    'bulan' => $bulan,
                    'perawatan_alat' => $alat,
                    'efisiensi_material' => $material,
                    'inisiatif' => $inisiatif,
                    'kepatuhan_prosedur' => $prosedur,
                ];
            }
        }

        return [
            'is_valid' => empty($errors),
            'fatal_error' => null,
            'errors' => $errors,
            'parsed_data' => [
                'teknisi' => $parsedTeknisi,
                'kedisiplinan' => $parsedDisiplin,
                'pekerjaan' => $parsedKualitas,
                'tanggung_jawab' => $parsedTJ,
            ],
            'stats' => [
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
            ],
        ];
    }

    /**
     * Extract header values from row 1.
     *
     * @return array<int, string>
     */
    public static function extractHeaders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $headers = [];
        $highestColumn = $sheet->getHighestDataColumn();
        $rowRange = $sheet->rangeToArray("A1:{$highestColumn}1", null, true, true, false);

        if (!empty($rowRange[0])) {
            foreach ($rowRange[0] as $cellValue) {
                $headers[] = strtolower(trim((string) $cellValue));
            }
        }

        return $headers;
    }

    /**
     * Check if extracted headers contain all required headers.
     */
    public static function verifyHeaders(string $sheetName, array $actualHeaders, array $expectedHeaders): ?string
    {
        $actualSet = array_flip($actualHeaders);
        foreach ($expectedHeaders as $expected) {
            $expectedLower = strtolower($expected);
            if (!isset($actualSet[$expectedLower])) {
                return "Header kolom '{$expected}' tidak ditemukan pada sheet {$sheetName}.";
            }
        }
        return null;
    }

    /**
     * Read data rows (starting from row 2) into associative arrays keyed by header names.
     *
     * @return array<int, array<string, mixed>> Keyed by actual 1-indexed Excel row number
     */
    public static function readSheetRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, bool $handleDates = false): array
    {
        $headers = self::extractHeaders($sheet);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        $rows = [];
        for ($r = 2; $r <= $highestRow; $r++) {
            $rowData = [];
            $hasData = false;

            foreach ($headers as $colIdx => $colName) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $cell = $sheet->getCell("{$colLetter}{$r}");
                $val = $cell->getValue();

                // Special handling for date cells in Excel
                if ($handleDates && $colName === 'tanggal' && ExcelDate::isDateTime($cell)) {
                    $dateTime = ExcelDate::excelToDateTimeObject($val);
                    $val = $dateTime->format('Y-m-d');
                }

                if ($val !== null && trim((string) $val) !== '') {
                    $hasData = true;
                }
                $rowData[$colName] = $val;
            }

            // Skip completely empty rows
            if ($hasData) {
                $rows[$r] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Parse non-negative integer. Returns null if invalid or negative.
     */
    public static function parseNonNegativeInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $intVal = (int) $value;
        return $intVal >= 0 ? $intVal : null;
    }

    /**
     * Parse rating (integer 1-4).
     */
    public static function parseRating(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $intVal = (int) $value;
        return ($intVal >= 1 && $intVal <= 4) ? $intVal : null;
    }

    /**
     * Parse boolean indicator (0, 1, 'Ya', 'Tidak', true, false).
     */
    public static function parseBooleanIndicator(mixed $value): ?int
    {
        if ($value === true) {
            return 1;
        }
        if ($value === false) {
            return 0;
        }
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $num = (int) $value;
            if ($num === 0 || $num === 1) {
                return $num;
            }
            return null;
        }
        $str = strtolower(trim((string) $value));
        if ($str === '1' || $str === 'ya' || $str === 'yes' || $str === 'y' || $str === 'true') {
            return 1;
        }
        if ($str === '0' || $str === 'tidak' || $str === 'no' || $str === 't' || $str === 'false') {
            return 0;
        }
        return null;
    }

    /**
     * Normalize date to YYYY-MM-DD string.
     */
    public static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            // Excel serial date number
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        $str = trim((string) $value);
        $ts = strtotime($str);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * Helper for fatal header errors.
     */
    private static function fatalHeaderError(string $message): array
    {
        return [
            'is_valid' => false,
            'fatal_error' => $message,
            'errors' => [$message],
            'parsed_data' => [],
            'stats' => ['total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0],
        ];
    }
}
