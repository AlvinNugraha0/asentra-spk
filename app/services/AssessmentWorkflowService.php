<?php
// ASENTRA SPK — Assessment Workflow Service (V2)
// Orchestrates calculation, evaluation persistence, indicator detailing, and Owner confirmation.
// STRICT: Does NOT run SAW, does NOT rank, protects legacy data.

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Kedisiplinan;
use App\Models\Pekerjaan;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\TanggungJawab;
use App\Models\Teknisi;
use App\Models\User;
use App\Services\CalculationEngine;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AssessmentWorkflowService
{
    /**
     * Trigger V2 calculation for a period and save results to tb_penilaian.
     * Only Admin can execute this.
     *
     * @param int $periodeId
     * @param int $adminId
     * @return array{
     *   success: bool,
     *   message: string,
     *   periode_id: int,
     *   total_teknisi: int,
     *   complete_count: int,
     *   partial_count: int,
     *   draft_count: int,
     *   evaluations: array<int, array<string, mixed>>
     * }
     */
    public function calculatePeriod(int $periodeId, int $adminId): array
    {
        // 1. Authorization check: Admin only
        $user = User::findById($adminId);
        if ($user === null || ($user['role'] ?? '') !== 'admin' || ($user['status'] ?? '') !== 'active') {
            throw new RuntimeException('Akses ditolak: Hanya Admin yang memiliki wewenang menjalankan kalkulasi penilaian.');
        }

        // 2. Period readiness check
        $canCalc = PeriodePenilaian::canCalculate($periodeId);
        if (!$canCalc['allowed']) {
            throw new RuntimeException($canCalc['reason'] ?? 'Periode tidak dapat dikalkulasi.');
        }

        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode ID {$periodeId} tidak ditemukan.");
        }

        // 3. Run CalculationEngine (no calculation logic in controller or duplicate formulas!)
        $calcResults = CalculationEngine::calculatePeriod($periodeId);
        if (empty($calcResults)) {
            throw new RuntimeException('Tidak ada data teknisi yang dapat dihitung pada periode ini.');
        }

        // 4. Transactional save to tb_penilaian
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        $completeCount = 0;
        $partialCount = 0;
        $draftCount = 0;
        $savedEvaluations = [];

        try {
            foreach ($calcResults as $tId => $result) {
                $savedId = CalculationEngine::saveEvaluation($periodeId, (int) $tId, $result, $adminId);

                if ($result['status'] === 'complete') {
                    $completeCount++;
                } elseif ($result['status'] === 'partial') {
                    $partialCount++;
                } else {
                    $draftCount++;
                }

                $savedEvaluations[] = [
                    'penilaian_id' => $savedId,
                    'teknisi_id' => $tId,
                    'kode_teknisi' => $result['kode_teknisi'] ?? '',
                    'nama_teknisi' => $result['nama_teknisi'] ?? '',
                    'c1' => $result['c1_value'],
                    'c2' => $result['c2_value'],
                    'c3' => $result['c3_value'],
                    'status' => $result['status'],
                    'warning' => $result['warning'],
                ];
            }

            // Update period status to 'proses' if currently 'draft'
            if (($periode['status'] ?? '') === 'draft') {
                PeriodePenilaian::updateStatus($periodeId, 'proses');
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Gagal menyimpan hasil kalkulasi ke database: ' . $e->getMessage(), 0, $e);
        }

        $total = count($savedEvaluations);
        $message = "Kalkulasi operasional berhasil: {$total} teknisi diproses ({$completeCount} lengkap, {$partialCount} parsial).";

        return [
            'success' => true,
            'message' => $message,
            'periode_id' => $periodeId,
            'total_teknisi' => $total,
            'complete_count' => $completeCount,
            'partial_count' => $partialCount,
            'draft_count' => $draftCount,
            'evaluations' => $savedEvaluations,
        ];
    }

    /**
     * Get assessment preview summary for a period (used by Owner and Admin).
     *
     * @param int $periodeId
     * @return array<string, mixed>
     */
    public function getPeriodAssessmentSummary(int $periodeId): array
    {
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode ID {$periodeId} tidak ditemukan.");
        }

        $evaluations = Penilaian::findByPeriodeId($periodeId);
        $counts = PeriodePenilaian::getOperationalCounts($periodeId);

        $calculatedCount = 0;
        $partialCount = 0;
        $confirmedCount = 0;
        $draftCount = 0;
        $hasWarnings = false;

        foreach ($evaluations as $e) {
            $status = $e['status_data'] ?? '';
            if ($status === 'confirmed') {
                $confirmedCount++;
            } elseif ($status === 'calculated') {
                $calculatedCount++;
            } elseif ($status === 'partial') {
                $partialCount++;
                $hasWarnings = true;
            } else {
                $draftCount++;
            }
        }

        $totalEvaluations = count($evaluations);
        $isConfirmed = ($totalEvaluations > 0 && $confirmedCount === $totalEvaluations) ||
                       (($periode['status'] ?? '') === 'selesai');
        $canConfirm = ($totalEvaluations > 0) &&
                      !$isConfirmed &&
                      (($periode['status'] ?? '') !== 'legacy');

        return [
            'periode' => $periode,
            'evaluations' => $evaluations,
            'operational_counts' => $counts,
            'total_evaluations' => $totalEvaluations,
            'calculated_count' => $calculatedCount,
            'partial_count' => $partialCount,
            'confirmed_count' => $confirmedCount,
            'draft_count' => $draftCount,
            'has_warnings' => $hasWarnings,
            'is_confirmed' => $isConfirmed,
            'can_confirm' => $canConfirm,
        ];
    }

    /**
     * Get detailed indicator transparent breakdown for a technician in a period.
     * Shows:
     * - C1: Kehadiran %, Ketepatan Waktu (terlambat), Kepatuhan Jadwal %
     * - C2: Kerapian %, Presisi %, Kesesuaian Desain % (work item inspections)
     * - C3: Perawatan Alat, Efisiensi Material, Inisiatif, Kepatuhan Prosedur (ratings 1-4)
     *
     * @param int $periodeId
     * @param int $teknisiId
     * @return array<string, mixed>
     */
    public function getTechnicianIndicatorDetail(int $periodeId, int $teknisiId): array
    {
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode ID {$periodeId} tidak ditemukan.");
        }

        $teknisi = Teknisi::findById($teknisiId);
        if ($teknisi === null) {
            throw new InvalidArgumentException("Teknisi ID {$teknisiId} tidak ditemukan.");
        }

        $penilaian = Penilaian::findByPeriodeAndTeknisi($periodeId, $teknisiId);

        // Calculate detailed breakdown
        $calculation = CalculationEngine::calculateTechnician($periodeId, $teknisiId);

        // Compute C1 quarterly subindicators
        $c1Monthly = $calculation['c1']['monthly'] ?? [];
        $kehadiranVals = [];
        $terlambatVals = [];
        $jadwalVals = [];
        foreach ($c1Monthly as $m) {
            if (($m['status'] ?? '') === 'valid') {
                if (isset($m['c1_1'])) $kehadiranVals[] = $m['c1_1'];
                if (isset($m['c1_2'])) $terlambatVals[] = $m['c1_2'];
                if (isset($m['c1_3'])) $jadwalVals[] = $m['c1_3'];
            }
        }
        $calculation['c1']['subindicators'] = [
            'kehadiran' => !empty($kehadiranVals) ? array_sum($kehadiranVals) / count($kehadiranVals) : 0.0,
            'terlambat' => !empty($terlambatVals) ? array_sum($terlambatVals) / count($terlambatVals) : 0.0,
            'jadwal' => !empty($jadwalVals) ? array_sum($jadwalVals) / count($jadwalVals) : 0.0,
        ];

        // Compute C2 quarterly subindicators
        $c2Monthly = $calculation['c2']['monthly'] ?? [];
        $rapiVals = [];
        $presisiVals = [];
        $desainVals = [];
        foreach ($c2Monthly as $m) {
            if (($m['status'] ?? '') === 'valid') {
                if (isset($m['c2_1'])) $rapiVals[] = $m['c2_1'];
                if (isset($m['c2_2'])) $presisiVals[] = $m['c2_2'];
                if (isset($m['c2_3'])) $desainVals[] = $m['c2_3'];
            }
        }
        $calculation['c2']['subindicators'] = [
            'rapi' => !empty($rapiVals) ? array_sum($rapiVals) / count($rapiVals) : 0.0,
            'presisi' => !empty($presisiVals) ? array_sum($presisiVals) / count($presisiVals) : 0.0,
            'sesuai_desain' => !empty($desainVals) ? array_sum($desainVals) / count($desainVals) : 0.0,
        ];

        // Compute C3 quarterly subindicators
        $c3Monthly = $calculation['c3']['monthly'] ?? [];
        $alatVals = [];
        $matVals = [];
        $iniVals = [];
        $prosVals = [];
        foreach ($c3Monthly as $m) {
            if (($m['status'] ?? '') === 'valid') {
                if (isset($m['c3_1'])) $alatVals[] = $m['c3_1'];
                if (isset($m['c3_2'])) $matVals[] = $m['c3_2'];
                if (isset($m['c3_3'])) $iniVals[] = $m['c3_3'];
                if (isset($m['c3_4'])) $prosVals[] = $m['c3_4'];
            }
        }
        $calculation['c3']['subindicators'] = [
            'perawatan_alat' => !empty($alatVals) ? array_sum($alatVals) / count($alatVals) : 0.0,
            'efisiensi_material' => !empty($matVals) ? array_sum($matVals) / count($matVals) : 0.0,
            'inisiatif' => !empty($iniVals) ? array_sum($iniVals) / count($iniVals) : 0.0,
            'kepatuhan_prosedur' => !empty($prosVals) ? array_sum($prosVals) / count($prosVals) : 0.0,
        ];

        // Fetch raw monthly operational records
        $disiplinRows = Kedisiplinan::findByPeriodeAndTeknisi($periodeId, $teknisiId);
        $pekerjaanRows = Pekerjaan::findByPeriodeAndTeknisi($periodeId, $teknisiId);
        $tanggungJawabRows = TanggungJawab::findByPeriodeAndTeknisi($periodeId, $teknisiId);

        return [
            'periode' => $periode,
            'teknisi' => $teknisi,
            'penilaian' => $penilaian,
            'calculation' => $calculation,
            'raw_data' => [
                'kedisiplinan' => $disiplinRows,
                'pekerjaan' => $pekerjaanRows,
                'tanggung_jawab' => $tanggungJawabRows,
            ],
        ];
    }

    /**
     * Owner confirms evaluation results for a period.
     * Sets confirmed_by = owner, confirmed_at = NOW(), status_data = 'confirmed'.
     * Locks assessment against direct editing.
     *
     * @param int $periodeId
     * @param int $ownerId
     * @return array{success: bool, message: string, confirmed_count: int}
     */
    public function confirmAssessment(int $periodeId, int $ownerId): array
    {
        // 1. Authorization: Owner only
        $owner = User::findById($ownerId);
        if ($owner === null || ($owner['role'] ?? '') !== 'owner' || ($owner['status'] ?? '') !== 'active') {
            throw new RuntimeException('Akses ditolak: Hanya Owner yang memiliki wewenang untuk melakukan konfirmasi penilaian.');
        }

        // 2. Period validation: exists and NOT legacy
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode penilaian ID {$periodeId} tidak ditemukan.");
        }

        if (($periode['status'] ?? '') === 'legacy') {
            throw new RuntimeException('Periode legacy tidak dapat dikonfirmasi untuk menjaga integritas data historis.');
        }

        // 3. Check if already confirmed
        if (Penilaian::isPeriodConfirmed($periodeId) || ($periode['status'] ?? '') === 'selesai') {
            throw new RuntimeException('Penilaian pada periode ini sudah dikonfirmasi sebelumnya.');
        }

        // 4. Must have existing calculated/partial evaluations
        $evaluations = Penilaian::findByPeriodeId($periodeId);
        if (empty($evaluations)) {
            throw new RuntimeException('Tidak ada data penilaian yang dapat dikonfirmasi pada periode ini. Silakan jalankan kalkulasi terlebih dahulu.');
        }

        // 5. Transactional confirmation
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Update evaluations in tb_penilaian
            $stmt = $pdo->prepare(
                'UPDATE tb_penilaian
                 SET status_data = "confirmed",
                     confirmed_by = ?,
                     confirmed_at = CURRENT_TIMESTAMP
                 WHERE id_periode = ? AND status_data != "legacy"'
            );
            $stmt->execute([$ownerId, $periodeId]);
            $confirmedCount = $stmt->rowCount();

            // Update period status in tb_periode_penilaian to 'selesai'
            $stmtPeriode = $pdo->prepare('UPDATE tb_periode_penilaian SET status = "selesai" WHERE id_periode = ?');
            $stmtPeriode->execute([$periodeId]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Gagal mengonfirmasi penilaian (transaksi dibatalkan): ' . $e->getMessage(), 0, $e);
        }

        return [
            'success' => true,
            'message' => "Penilaian periode {$periode['nama_periode']} berhasil dikonfirmasi secara resmi ({$confirmedCount} teknisi).",
            'confirmed_count' => $confirmedCount,
        ];
    }
}
