<?php
// ASENTRA SPK — Excel Template Helper

declare(strict_types=1);

namespace App\Services\Import;

if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelTemplateHelper
{
    public const SHEET_TEKNISI = 'DATA_TEKNISI';
    public const SHEET_KEDISIPLINAN = 'KEDISIPLINAN';
    public const SHEET_KUALITAS = 'KUALITAS_KERJA';
    public const SHEET_TANGGUNG_JAWAB = 'TANGGUNG_JAWAB';
    public const SHEET_PETUNJUK = 'PETUNJUK';

    public const REQUIRED_SHEETS = [
        self::SHEET_TEKNISI,
        self::SHEET_KEDISIPLINAN,
        self::SHEET_KUALITAS,
        self::SHEET_TANGGUNG_JAWAB,
        self::SHEET_PETUNJUK,
    ];

    public const HEADERS_TEKNISI = [
        'kode_teknisi',
        'nama_teknisi',
    ];

    public const HEADERS_KEDISIPLINAN = [
        'kode_teknisi',
        'bulan',
        'total_hari_kerja',
        'hadir',
        'sakit',
        'izin',
        'alpa',
        'terlambat',
        'pekerjaan_terjadwal',
        'sesuai_jadwal',
    ];

    public const HEADERS_KUALITAS = [
        'kode_teknisi',
        'tanggal',
        'bulan',
        'nama_pekerjaan',
        'rapi',
        'presisi',
        'sesuai_desain',
    ];

    public const HEADERS_TANGGUNG_JAWAB = [
        'kode_teknisi',
        'bulan',
        'perawatan_alat',
        'efisiensi_material',
        'inisiatif',
        'kepatuhan_prosedur',
    ];

    /**
     * Create a standard blank or sample Excel workbook.
     *
     * @param array<string, array<int, array<mixed>>>|null $customData Optional custom rows per sheet.
     */
    public static function createSpreadsheet(?array $customData = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // 1. DATA_TEKNISI
        $sheetTeknisi = $spreadsheet->getActiveSheet();
        $sheetTeknisi->setTitle(self::SHEET_TEKNISI);
        $sheetTeknisi->fromArray(self::HEADERS_TEKNISI, null, 'A1', true);
        if (isset($customData[self::SHEET_TEKNISI])) {
            $sheetTeknisi->fromArray($customData[self::SHEET_TEKNISI], null, 'A2', true);
        }

        // 2. KEDISIPLINAN
        $sheetDisiplin = $spreadsheet->createSheet();
        $sheetDisiplin->setTitle(self::SHEET_KEDISIPLINAN);
        $sheetDisiplin->fromArray(self::HEADERS_KEDISIPLINAN, null, 'A1', true);
        if (isset($customData[self::SHEET_KEDISIPLINAN])) {
            $sheetDisiplin->fromArray($customData[self::SHEET_KEDISIPLINAN], null, 'A2', true);
        }

        // 3. KUALITAS_KERJA
        $sheetKualitas = $spreadsheet->createSheet();
        $sheetKualitas->setTitle(self::SHEET_KUALITAS);
        $sheetKualitas->fromArray(self::HEADERS_KUALITAS, null, 'A1', true);
        if (isset($customData[self::SHEET_KUALITAS])) {
            $sheetKualitas->fromArray($customData[self::SHEET_KUALITAS], null, 'A2', true);
        }

        // 4. TANGGUNG_JAWAB
        $sheetTJ = $spreadsheet->createSheet();
        $sheetTJ->setTitle(self::SHEET_TANGGUNG_JAWAB);
        $sheetTJ->fromArray(self::HEADERS_TANGGUNG_JAWAB, null, 'A1', true);
        if (isset($customData[self::SHEET_TANGGUNG_JAWAB])) {
            $sheetTJ->fromArray($customData[self::SHEET_TANGGUNG_JAWAB], null, 'A2', true);
        }

        // 5. PETUNJUK
        $sheetPetunjuk = $spreadsheet->createSheet();
        $sheetPetunjuk->setTitle(self::SHEET_PETUNJUK);
        $petunjukRows = [
            ['PETUNJUK PENGISIAN DATA OPERASIONAL ASENTRA SPK'],
            ['1. Satu file Excel untuk satu periode triwulan (3 bulan).'],
            ['2. Jangan mengubah nama sheet atau susunan kolom header.'],
            ['3. Sheet DATA_TEKNISI: cantumkan kode teknisi yang telah terdaftar di sistem.'],
            ['4. Sheet KEDISIPLINAN: bulan (1-12), hadir <= total_hari_kerja, sesuai_jadwal <= pekerjaan_terjadwal.'],
            ['5. Sheet KUALITAS_KERJA: tanggal (YYYY-MM-DD), rapi/presisi/sesuai_desain bernilai 1 (Ya) atau 0 (Tidak).'],
            ['6. Sheet TANGGUNG_JAWAB: nilai perawatan_alat, efisiensi_material, inisiatif, kepatuhan_prosedur berupa rating 1-4.'],
        ];
        $sheetPetunjuk->fromArray($petunjukRows, null, 'A1');

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Save spreadsheet to file.
     */
    public static function saveToFile(Spreadsheet $spreadsheet, string $filePath): string
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }
}
