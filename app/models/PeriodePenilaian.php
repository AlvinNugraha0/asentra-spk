<?php
// ASENTRA SPK — PeriodePenilaian model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class PeriodePenilaian
{
    /**
     * Get all evaluation periods, optionally filtered by status.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(string $status = ''): array
    {
        $sql = 'SELECT p.*, u.nama AS created_by_nama
                FROM tb_periode_penilaian p
                LEFT JOIN tb_user u ON u.id = p.created_by
                WHERE 1=1';
        $params = [];

        if ($status !== '') {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY p.tanggal_mulai DESC, p.id_periode DESC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Phase 6L: all() enriched with operational counts + can_calculate flags,
     * so the shared periode table renders identically wherever it is shown.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allWithProgress(): array
    {
        $rows = [];

        foreach (self::all() as $p) {
            $id = (int) ($p['id_periode'] ?? 0);
            $canCalc = $id > 0 ? self::canCalculate($id) : ['allowed' => false, 'reason' => ''];
            $rows[] = array_merge($p, [
                'operational_counts' => $id > 0 ? self::getOperationalCounts($id) : ['total' => 0],
                'can_calculate' => (bool) ($canCalc['allowed'] ?? false),
                'cannot_calculate_reason' => (string) ($canCalc['reason'] ?? ''),
            ]);
        }

        return $rows;
    }

    /**
     * Alias for all().
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(string $status = ''): array
    {
        return self::all($status);
    }

    /**
     * Find period by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $sql = 'SELECT p.*, u.nama AS created_by_nama
                FROM tb_periode_penilaian p
                LEFT JOIN tb_user u ON u.id = p.created_by
                WHERE p.id_periode = ? LIMIT 1';
        $row = Database::query($sql, [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Alias for findById().
     *
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return self::findById($id);
    }

    /**
     * Find period by unique code.
     *
     * @return array<string, mixed>|null
     */
    public static function findByKode(string $kode): ?array
    {
        $sql = 'SELECT p.*, u.nama AS created_by_nama
                FROM tb_periode_penilaian p
                LEFT JOIN tb_user u ON u.id = p.created_by
                WHERE p.kode_periode = ? LIMIT 1';
        $row = Database::query($sql, [$kode])->fetch();
        return $row ?: null;
    }

    /**
     * Check if a period code exists, excluding an optional ID.
     */
    public static function exists(string $kode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?';
        $params = [$kode];

        if ($excludeId !== null) {
            $sql .= ' AND id_periode != ?';
            $params[] = $excludeId;
        }

        return (int) Database::query($sql, $params)->fetch()['c'] > 0;
    }

    /**
     * Create a new evaluation period.
     */
    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO tb_periode_penilaian
                (kode_periode, nama_periode, tanggal_mulai, tanggal_selesai, status, file_import, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['kode_periode'],
                $data['nama_periode'],
                $data['tanggal_mulai'],
                $data['tanggal_selesai'],
                $data['status'] ?? 'draft',
                $data['file_import'] ?? null,
                $data['created_by'] ?? null,
            ]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    /**
     * Update an evaluation period.
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE tb_periode_penilaian
             SET kode_periode = ?, nama_periode = ?, tanggal_mulai = ?, tanggal_selesai = ?,
                 status = ?, file_import = ?
             WHERE id_periode = ?',
            [
                $data['kode_periode'],
                $data['nama_periode'],
                $data['tanggal_mulai'],
                $data['tanggal_selesai'],
                $data['status'],
                $data['file_import'] ?? null,
                $id,
            ]
        );
    }

    /**
     * Update period status.
     */
    public static function updateStatus(int $id, string $status): void
    {
        Database::query(
            'UPDATE tb_periode_penilaian SET status = ? WHERE id_periode = ?',
            [$status, $id]
        );
    }

    /**
     * Delete a period by ID.
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$id]);
    }

    /**
     * Count total periods.
     */
    public static function count(string $status = ''): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE 1=1';
        $params = [];
        if ($status !== '') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        return (int) Database::query($sql, $params)->fetch()['c'];
    }

    /**
     * Get counts of raw operational records for a period.
     *
     * @return array{kedisiplinan: int, pekerjaan: int, tanggung_jawab: int, total: int}
     */
    public static function getOperationalCounts(int $periodeId): array
    {
        $kedi = (int) Database::query('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$periodeId])->fetch()['c'];
        $pek = (int) Database::query('SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = ?', [$periodeId])->fetch()['c'];
        $tj = (int) Database::query('SELECT COUNT(*) AS c FROM tb_tanggung_jawab WHERE id_periode = ?', [$periodeId])->fetch()['c'];

        return [
            'kedisiplinan' => $kedi,
            'pekerjaan' => $pek,
            'tanggung_jawab' => $tj,
            'total' => $kedi + $pek + $tj,
        ];
    }

    /**
     * Check if a period has any operational data.
     */
    public static function hasOperationalData(int $periodeId): bool
    {
        $counts = self::getOperationalCounts($periodeId);
        return $counts['total'] > 0;
    }

    /**
     * Normalisasi kode periode kuartal menjadi angka kuartal (1-4).
     * Mendukung format YYYY-QX (misal: '2026-Q1' -> 1).
     *
     * @return int|null 1-4 jika format kuartal terdeteksi, null jika bukan kode kuartal.
     */
    public static function parseQuarterCode(string $kode): ?int
    {
        if (preg_match('/^\s*(\d{4})-Q\s*([1-4])\s*$/i', $kode, $m)) {
            return (int) $m[2];
        }
        return null;
    }

    /**
     * Validasi rentang tanggal terhadap kalender kuartal.
     *
     * Aturan (V2_FEATURE_ROADMAP.md #10 - Calendar Quarter Validation):
     *   Q1 = 01 Jan - 31 Mar
     *   Q2 = 01 Apr - 30 Jun
     *   Q3 = 01 Jul - 30 Sep
     *   Q4 = 01 Okt - 31 Des
     *
     * Jika kode periode bukan format kuartal (misal: LEGACY-2026-08, 2026-08),
     * perubahan tidak diberlakukan — validasi hanya memeriksa kewajaran tanggal
     * (mulai <= selesai).
     *
     * @param string $kodePeriode      Kode periode (misal: '2026-Q1').
     * @param string $tanggalMulai     Format Y-m-d.
     * @param string $tanggalSelesai   Format Y-m-d.
     *
     * @return array{valid: bool, errors: array<int, string>}
     */
    public static function validateQuarterRange(string $kodePeriode, string $tanggalMulai, string $tanggalSelesai): array
    {
        $errors = [];

        $quarter = self::parseQuarterCode($kodePeriode);

        // Kode non-kuartal (legacy, bebas): hanya syarat umum mulai <= selesai.
        if ($quarter === null) {
            if ($tanggalMulai !== '' && $tanggalSelesai !== '' && strtotime($tanggalMulai) > strtotime($tanggalSelesai)) {
                $errors[] = 'Tanggal selesai harus sama atau setelah tanggal mulai.';
            }
            return ['valid' => empty($errors), 'errors' => $errors];
        }

        // Ambil tahun dari kode kuartal.
        preg_match('/^\s*(\d{4})-Q\s*([1-4])\s*$/i', $kodePeriode, $m);
        $year = (int) $m[1];

        $expectedStart = sprintf('%04d-%02d-01', $year, ($quarter - 1) * 3 + 1);
        $expectedEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $quarter * 3)));

        if ($tanggalMulai !== $expectedStart) {
            $bulan = ['Januari', 'April', 'Juli', 'Oktober'][$quarter - 1];
            $errors[] = "Periode kuartal Q{$quarter} harus dimulai tanggal 01 {$bulan} {$year} (saat ini: {$tanggalMulai}).";
        }

        if ($tanggalSelesai !== $expectedEnd) {
            $errors[] = "Tanggal selesai kuartal Q{$quarter} harus sesuai akhir kuartal ({$expectedEnd}). Saat ini: {$tanggalSelesai}.";
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Phase 6G: list periods that have at least one evaluation in tb_penilaian.
     * Used by the ranking selector so V2 quarter periods can be chosen by id.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allWithEvaluations(): array
    {
        $sql = 'SELECT pp.*, COUNT(p.id) AS jumlah_evaluasi
                FROM tb_periode_penilaian pp
                JOIN tb_penilaian p ON p.id_periode = pp.id_periode
                GROUP BY pp.id_periode
                ORDER BY pp.tanggal_mulai DESC, pp.id_periode DESC';

        return Database::query($sql)->fetchAll();
    }

    /**
     * Check whether a period may be calculated.
     *
     * @return array{allowed: bool, reason?: string}
     */
    public static function canCalculate(int $periodeId): array
    {
        $periode = self::findById($periodeId);
        if ($periode === null) {
            return ['allowed' => false, 'reason' => "Periode ID {$periodeId} tidak ditemukan."];
        }

        if (($periode['status'] ?? '') === 'legacy') {
            return ['allowed' => false, 'reason' => 'Periode legacy tidak boleh dihitung ulang untuk menjaga integritas data historis.'];
        }

        if (Penilaian::isPeriodConfirmed($periodeId)) {
            return ['allowed' => false, 'reason' => 'Penilaian pada periode ini sudah dikonfirmasi oleh Owner dan tidak dapat dikalkulasi ulang.'];
        }

        if (!self::hasOperationalData($periodeId)) {
            return ['allowed' => false, 'reason' => 'Periode ini belum memiliki data operasional (kedisiplinan, pekerjaan, atau tanggung jawab). Silakan import Excel terlebih dahulu.'];
        }

        return ['allowed' => true];
    }
}
