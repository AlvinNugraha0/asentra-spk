<?php
// ASENTRA SPK — Excel Import Service
// Imports raw operational data from quarterly Excel files into V2 tables.
// Strictly stores raw data: DOES NOT calculate C1/C2/C3, DOES NOT run SAW, DOES NOT confirm.

declare(strict_types=1);

namespace App\Services\Import;

if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
}

use App\Core\Database;
use App\Models\Import;
use App\Models\Kedisiplinan;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\Teknisi;
use App\Models\TanggungJawab;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ExcelImportService
{
    public const MAX_FILE_SIZE_BYTES = 10485760; // 10 MB

    /**
     * Import raw operational data from an Excel workbook into tb_kedisiplinan, tb_pekerjaan, and tb_tanggung_jawab.
     *
     * @param string $filePath Path to uploaded or local file
     * @param int $periodeId Target period ID (must not be legacy)
     * @param int $userId ID of the executing user (must be active Admin)
     * @param string $originalFilename Original name of file uploaded by user
     * @param bool $allowPartial Whether to save valid rows when some rows are invalid
     *
     * @return array{
     *   success: bool,
     *   status: string,
     *   id_import: ?int,
     *   id_periode?: int,
     *   nama_file?: string,
     *   message: string,
     *   errors: array<int, string>,
     *   total_data: int,
     *   data_berhasil: int,
     *   data_gagal: int
     * }
     */
    public function import(
        string $filePath,
        int $periodeId,
        int $userId,
        string $originalFilename = '',
        bool $allowPartial = true
    ): array {
        // 1. Authorization: Only Admin can import
        $user = User::findById($userId);
        if ($user === null || ($user['role'] ?? '') !== 'admin' || ($user['status'] ?? '') !== 'active') {
            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => null,
                'message' => 'Akses ditolak: Hanya Admin yang memiliki hak akses untuk melakukan import data.',
                'errors' => ['Hanya Admin yang memiliki hak akses untuk melakukan import data.'],
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
            ];
        }

        // 2. Period validation: Must exist and must NOT be legacy
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => null,
                'message' => "Periode penilaian ID {$periodeId} tidak ditemukan.",
                'errors' => ["Periode penilaian ID {$periodeId} tidak ditemukan."],
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
            ];
        }

        if (($periode['status'] ?? '') === 'legacy') {
            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => null,
                'message' => 'Import ke periode legacy dilarang untuk menjaga integritas data historis.',
                'errors' => ['Import ke periode legacy dilarang untuk menjaga integritas data historis.'],
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
            ];
        }

        // Phase 6F: LOCK — period already confirmed/finished cannot receive new imports.
        if (Penilaian::isPeriodConfirmed($periodeId) || ($periode['status'] ?? '') === 'selesai') {
            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => null,
                'message' => 'Penilaian pada periode ini sudah dikonfirmasi secara resmi. '
                    . 'Import data baru telah dikunci untuk menjaga integritas hasil yang final.',
                'errors' => ['Periode sudah CONFIRMED — import dikunci.'],
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
            ];
        }

        $effectiveOriginalName = $originalFilename !== '' ? $originalFilename : basename($filePath);

        // 3. File validation (existence, readability, extension, size)
        if (!is_file($filePath) || !is_readable($filePath)) {
            return $this->recordFailedImport(
                $periodeId,
                $userId,
                $effectiveOriginalName,
                'File import tidak ditemukan atau tidak dapat dibaca di server.'
            );
        }

        $ext = strtolower(pathinfo($effectiveOriginalName, PATHINFO_EXTENSION));
        $actualExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext !== 'xlsx' && $actualExt !== 'xlsx') {
            return $this->recordFailedImport(
                $periodeId,
                $userId,
                $effectiveOriginalName,
                "Format file tidak didukung (.{$ext}). File harus berekstensi .xlsx."
            );
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            return $this->recordFailedImport(
                $periodeId,
                $userId,
                $effectiveOriginalName,
                'File import kosong (0 byte).'
            );
        }

        if ($fileSize > self::MAX_FILE_SIZE_BYTES) {
            $maxMb = self::MAX_FILE_SIZE_BYTES / (1024 * 1024);
            return $this->recordFailedImport(
                $periodeId,
                $userId,
                $effectiveOriginalName,
                "Ukuran file melebihi batas maksimum {$maxMb}MB."
            );
        }

        // 4. Secure storage: Copy to storage/imports/ with safe server name
        $storageDir = self::getStorageDir();
        $safeServerName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.xlsx';
        $storedFilePath = $storageDir . DIRECTORY_SEPARATOR . $safeServerName;

        if (!copy($filePath, $storedFilePath)) {
            return $this->recordFailedImport(
                $periodeId,
                $userId,
                $effectiveOriginalName,
                'Gagal menyimpan file ke direktori penyimpanan server.'
            );
        }

        // 5. Create initial record in tb_import with status 'processing'
        $importId = Import::create([
            'id_periode' => $periodeId,
            'nama_file' => $safeServerName,
            'nama_file_asli' => $effectiveOriginalName,
            'total_data' => 0,
            'data_berhasil' => 0,
            'data_gagal' => 0,
            'status' => 'processing',
            'pesan_error' => null,
            'id_user' => $userId,
        ]);

        // 6. Read Spreadsheet using PhpSpreadsheet
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(false); // keep formatting for date inspection if needed
            $spreadsheet = $reader->load($storedFilePath);
        } catch (Throwable $e) {
            Import::update($importId, [
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
                'status' => 'failed',
                'pesan_error' => 'File tidak dapat dibaca sebagai file Excel (.xlsx) yang valid: ' . $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => $importId,
                'message' => 'File Excel rusak atau tidak dapat dibaca oleh PhpSpreadsheet.',
                'errors' => ['PhpSpreadsheet error: ' . $e->getMessage()],
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
            ];
        }

        // 7. Validate Workbook structure & rows using ExcelValidator
        $teknisiList = Teknisi::all();
        $validTeknisiMap = [];
        foreach ($teknisiList as $t) {
            $validTeknisiMap[$t['kode_teknisi']] = (int) $t['id'];
        }

        $valResult = ExcelValidator::validate($spreadsheet, $periode, $validTeknisiMap);

        // If fatal structure error (missing required sheet or bad headers)
        if (!empty($valResult['fatal_error'])) {
            Import::update($importId, [
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
                'status' => 'failed',
                'pesan_error' => $valResult['fatal_error'],
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => $importId,
                'message' => $valResult['fatal_error'],
                'errors' => $valResult['errors'],
                'total_data' => 0,
                'data_berhasil' => 0,
                'data_gagal' => 0,
            ];
        }

        $stats = $valResult['stats'];
        $parsed = $valResult['parsed_data'];
        $errors = $valResult['errors'];

        // If strict mode (allowPartial = false) and there are invalid rows:
        if (!$allowPartial && $stats['invalid_rows'] > 0) {
            Import::update($importId, [
                'total_data' => $stats['total_rows'],
                'data_berhasil' => 0,
                'data_gagal' => $stats['total_rows'],
                'status' => 'failed',
                'pesan_error' => implode("\n", $errors),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => $importId,
                'message' => "Import ditolak karena terdapat {$stats['invalid_rows']} baris data invalid pada mode strict.",
                'errors' => $errors,
                'total_data' => $stats['total_rows'],
                'data_berhasil' => 0,
                'data_gagal' => $stats['total_rows'],
            ];
        }

        // If total valid rows is 0
        if ($stats['valid_rows'] === 0) {
            $errMsg = !empty($errors) ? implode("\n", $errors) : 'Tidak ada data valid yang ditemukan untuk disimpan.';
            Import::update($importId, [
                'total_data' => $stats['total_rows'],
                'data_berhasil' => 0,
                'data_gagal' => $stats['invalid_rows'],
                'status' => 'failed',
                'pesan_error' => $errMsg,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => $importId,
                'message' => 'Tidak ada baris data valid yang dapat disimpan.',
                'errors' => $errors,
                'total_data' => $stats['total_rows'],
                'data_berhasil' => 0,
                'data_gagal' => $stats['invalid_rows'],
            ];
        }

        // 8. Transactional Database Persistence
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // A. Save Kedisiplinan
            if (!empty($parsed['kedisiplinan'])) {
                foreach ($parsed['kedisiplinan'] as $row) {
                    Kedisiplinan::upsert([
                        'id_periode' => $periodeId,
                        'id_teknisi' => $row['id_teknisi'],
                        'bulan' => $row['bulan'],
                        'total_hari_kerja' => $row['total_hari_kerja'],
                        'hadir' => $row['hadir'],
                        'sakit' => $row['sakit'],
                        'izin' => $row['izin'],
                        'alpa' => $row['alpa'],
                        'terlambat' => $row['terlambat'],
                        'pekerjaan_terjadwal' => $row['pekerjaan_terjadwal'],
                        'sesuai_jadwal' => $row['sesuai_jadwal'],
                    ]);
                }
            }

            // B. Save Pekerjaan (Idempotent: update if same teknisi + date + job name exists, else insert)
            if (!empty($parsed['pekerjaan'])) {
                $stmtFindJob = $pdo->prepare(
                    'SELECT id_pekerjaan FROM tb_pekerjaan 
                     WHERE id_periode = ? AND id_teknisi = ? AND tanggal = ? AND nama_pekerjaan = ? 
                     LIMIT 1'
                );
                $stmtUpdateJob = $pdo->prepare(
                    'UPDATE tb_pekerjaan 
                     SET bulan = ?, rapi = ?, presisi = ?, sesuai_desain = ? 
                     WHERE id_pekerjaan = ?'
                );
                $stmtInsertJob = $pdo->prepare(
                    'INSERT INTO tb_pekerjaan 
                     (id_periode, id_teknisi, tanggal, bulan, nama_pekerjaan, rapi, presisi, sesuai_desain) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );

                foreach ($parsed['pekerjaan'] as $row) {
                    $stmtFindJob->execute([$periodeId, $row['id_teknisi'], $row['tanggal'], $row['nama_pekerjaan']]);
                    $existingJobId = $stmtFindJob->fetchColumn();

                    if ($existingJobId !== false && $existingJobId !== null) {
                        $stmtUpdateJob->execute([
                            $row['bulan'],
                            $row['rapi'],
                            $row['presisi'],
                            $row['sesuai_desain'],
                            (int) $existingJobId,
                        ]);
                    } else {
                        $stmtInsertJob->execute([
                            $periodeId,
                            $row['id_teknisi'],
                            $row['tanggal'],
                            $row['bulan'],
                            $row['nama_pekerjaan'],
                            $row['rapi'],
                            $row['presisi'],
                            $row['sesuai_desain'],
                        ]);
                    }
                }
            }

            // C. Save Tanggung Jawab
            if (!empty($parsed['tanggung_jawab'])) {
                foreach ($parsed['tanggung_jawab'] as $row) {
                    TanggungJawab::upsert([
                        'id_periode' => $periodeId,
                        'id_teknisi' => $row['id_teknisi'],
                        'bulan' => $row['bulan'],
                        'perawatan_alat' => $row['perawatan_alat'],
                        'efisiensi_material' => $row['efisiensi_material'],
                        'inisiatif' => $row['inisiatif'],
                        'kepatuhan_prosedur' => $row['kepatuhan_prosedur'],
                    ]);
                }
            }

            // D. Update file_import on period record
            $stmtUpdatePeriode = $pdo->prepare(
                'UPDATE tb_periode_penilaian SET file_import = ? WHERE id_periode = ?'
            );
            $stmtUpdatePeriode->execute([$safeServerName, $periodeId]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            Import::update($importId, [
                'total_data' => $stats['total_rows'],
                'data_berhasil' => 0,
                'data_gagal' => $stats['total_rows'],
                'status' => 'failed',
                'pesan_error' => 'Database error: ' . $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'id_import' => $importId,
                'message' => 'Gagal menyimpan data operasional ke database (transaksi dibatalkan): ' . $e->getMessage(),
                'errors' => ['Database transaction error: ' . $e->getMessage()],
                'total_data' => $stats['total_rows'],
                'data_berhasil' => 0,
                'data_gagal' => $stats['total_rows'],
            ];
        }

        // 9. Determine final status & update tb_import log
        $finalStatus = ($stats['invalid_rows'] === 0) ? 'success' : 'partial';
        $errorLog = !empty($errors) ? implode("\n", $errors) : null;

        Import::update($importId, [
            'total_data' => $stats['total_rows'],
            'data_berhasil' => $stats['valid_rows'],
            'data_gagal' => $stats['invalid_rows'],
            'status' => $finalStatus,
            'pesan_error' => $errorLog,
        ]);

        $message = ($finalStatus === 'success')
            ? "Import data operasional berhasil sepenuhnya ({$stats['valid_rows']} baris tersimpan)."
            : "Import data operasional selesai dengan status parsial: {$stats['valid_rows']} baris berhasil disimpan, {$stats['invalid_rows']} baris ditolak.";

        return [
            'success' => true,
            'status' => $finalStatus,
            'id_import' => $importId,
            'id_periode' => $periodeId,
            'nama_file' => $safeServerName,
            'message' => $message,
            'errors' => $errors,
            'total_data' => $stats['total_rows'],
            'data_berhasil' => $stats['valid_rows'],
            'data_gagal' => $stats['invalid_rows'],
        ];
    }

    /**
     * Static convenience wrapper.
     */
    public static function execute(
        string $filePath,
        int $periodeId,
        int $userId,
        string $originalFilename = '',
        bool $allowPartial = true
    ): array {
        $service = new self();
        return $service->import($filePath, $periodeId, $userId, $originalFilename, $allowPartial);
    }

    /**
     * Helper to record failed import when pre-validation fails after period & user confirmed.
     *
     * @return array{
     *   success: false,
     *   status: 'failed',
     *   id_import: int,
     *   message: string,
     *   errors: array<int, string>,
     *   total_data: 0,
     *   data_berhasil: 0,
     *   data_gagal: 0
     * }
     */
    private function recordFailedImport(
        int $periodeId,
        int $userId,
        string $originalFilename,
        string $errorMessage
    ): array {
        $importId = Import::create([
            'id_periode' => $periodeId,
            'nama_file' => '',
            'nama_file_asli' => $originalFilename,
            'total_data' => 0,
            'data_berhasil' => 0,
            'data_gagal' => 0,
            'status' => 'failed',
            'pesan_error' => $errorMessage,
            'id_user' => $userId,
        ]);

        return [
            'success' => false,
            'status' => 'failed',
            'id_import' => $importId,
            'message' => $errorMessage,
            'errors' => [$errorMessage],
            'total_data' => 0,
            'data_berhasil' => 0,
            'data_gagal' => 0,
        ];
    }

    /**
     * Resolve and ensure storage directory for uploaded files.
     */
    public static function getStorageDir(): string
    {
        $dir = defined('ROOT_PATH')
            ? ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'imports'
            : dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'imports';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
