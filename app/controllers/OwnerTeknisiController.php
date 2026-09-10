<?php
// ASENTRA SPK — Owner Teknisi controller (read-only)

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Teknisi;

class OwnerTeknisiController
{
    /**
     * Daftar teknisi — read-only untuk Owner.
     */
    public function index(): void
    {
        requireOwner();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $list = Teknisi::all($search, $status);

        renderWithLayout('owner/teknisi_list', [
            'title' => 'Data Teknisi',
            'subtitle' => 'Daftar teknisi lapangan ASENTRA.',
            'list' => $list,
            'search' => $search,
            'status' => $status,
        ]);
    }
}
