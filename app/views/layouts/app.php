<?php
/**
 * Main application layout.
 * Variables expected:
 * @var string $content Captured page content.
 * @var ?string $title Page title (optional).
 * @var ?string $subtitle Page subtitle (optional).
 */
$user = currentUser() ?? [];
$role = $user['role'] ?? '';
$nameInitials = strtoupper(mb_substr(($user['nama'] ?? 'U'), 0, 2));
$pageTitle = ($title ?? 'ASENTRA SPK');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
</head>
<body>
    <a href="#main-content" class="skip-link">Langsung ke konten</a>

    <div class="app-shell">
        <div class="sidebar-backdrop" data-sidebar-close></div>

        <aside class="sidebar" id="app-sidebar">
            <div class="logo">AS</div>

            <?php if ($role === 'admin'): ?>
                <ul class="nav-list">
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/admin/dashboard',
                        'icon' => 'LayoutDashboard',
                        'label' => 'Dashboard',
                    ]) ?>
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/admin/teknisi',
                        'icon' => 'Users',
                        'label' => 'Data Teknisi',
                    ]) ?>
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/admin/kriteria',
                        'icon' => 'SlidersHorizontal',
                        'label' => 'Kriteria & Bobot',
                    ]) ?>
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/admin/penilaian',
                        'icon' => 'ClipboardCheck',
                        'label' => 'Penilaian',
                    ]) ?>
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/admin/riwayat',
                        'icon' => 'History',
                        'label' => 'Riwayat',
                    ]) ?>
                </ul>
            <?php elseif ($role === 'owner'): ?>
                <ul class="nav-list">
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/owner/dashboard',
                        'icon' => 'LayoutDashboard',
                        'label' => 'Dashboard',
                    ]) ?>
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/owner/ranking',
                        'icon' => 'Trophy',
                        'label' => 'Hasil Ranking',
                    ]) ?>
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/owner/riwayat',
                        'icon' => 'History',
                        'label' => 'Riwayat',
                    ]) ?>
                    <?= viewPartial('partials.nav_item', [
                        'route' => '/owner/laporan',
                        'icon' => 'Printer',
                        'label' => 'Laporan',
                    ]) ?>
                </ul>
            <?php endif; ?>

            <ul class="nav-list" style="margin-top: auto;">
                <li class="nav-item">
                    <form action="<?= route('/logout') ?>" method="POST" style="margin: 0; width: 100%;">
                        <?= csrfField() ?>
                        <button type="submit" class="nav-link" aria-label="Logout" style="width: 100%; border: none; background: transparent;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        </button>
                    </form>
                    <span class="nav-tooltip">Logout</span>
                </li>
            </ul>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Buka menu navigasi" aria-controls="app-sidebar" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <div class="search-bar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" placeholder="Cari..." aria-label="Pencarian">
                </div>
                <div class="topbar-right">
                    <button type="button" class="icon-btn" aria-label="Notifikasi">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    </button>
                    <button type="button" class="icon-btn" aria-label="Pengaturan">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                    <div class="user-avatar" title="<?= e($user['nama'] ?? 'Pengguna') ?>"><?= e($nameInitials) ?></div>
                </div>
            </header>

            <main class="content" id="main-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <?php renderFlash(); ?>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/rating.js') ?>"></script>
</body>
</html>
