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
$roleName = $role === 'admin' ? 'Admin Panel' : ($role === 'owner' ? 'Owner Panel' : 'Panel');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('asentra_theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = savedTheme ? savedTheme : (prefersDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>
    <link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
</head>
<body>
    <a href="#main-content" class="skip-link">Langsung ke konten</a>

    <div class="app-shell">
        <div class="sidebar-backdrop" data-sidebar-close></div>

        <aside class="sidebar" id="app-sidebar">
            <!-- Brand -->
            <div class="sidebar-brand">
                <img src="<?= asset('img/logo-asentra.png') ?>" alt="ASENTRA" class="sidebar-brand-full-logo">
            </div>

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

            <!-- Logout + User at bottom -->
            <div class="sidebar-user">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar"><?= e($nameInitials) ?></div>
                    <div class="flex-1" style="min-width:0;">
                        <div class="sidebar-user-name"><?= e($user['nama'] ?? 'Pengguna') ?></div>
                        <div class="sidebar-user-role"><?= e(ucfirst($role)) ?></div>
                    </div>
                </div>
                <form action="<?= route('/logout') ?>" method="POST" class="form-reset w-full" style="margin-top: var(--space-3);">
                    <?= csrfField() ?>
                    <button type="submit" class="nav-link" style="justify-content: flex-start;">
                        <i class="ph ph-sign-out text-xl"></i>
                        <span class="nav-label">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <div class="flex items-center gap-4">
                    <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Buka menu navigasi" aria-controls="app-sidebar" aria-expanded="false">
                        <i class="ph ph-list text-xl"></i>
                    </button>
                    <div class="topbar-left">
                        <h2 class="topbar-title"><?= e($title ?? 'Dashboard') ?></h2>
                        <?php if (!empty($subtitle)): ?>
                            <p class="topbar-subtitle"><?= e($subtitle) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="search-bar">
                        <i class="ph ph-magnifying-glass text-lg"></i>
                        <input type="text" placeholder="Cari..." aria-label="Pencarian">
                    </div>

                    <button type="button" class="icon-btn theme-toggle-btn" data-theme-toggle aria-label="Ganti mode tema (Terang / Gelap)" aria-pressed="false" title="Ganti Mode Tema">
                        <i class="ph ph-sun theme-icon-light text-xl" aria-hidden="true"></i>
                        <i class="ph ph-moon theme-icon-dark text-xl" aria-hidden="true"></i>
                    </button>

                    <button type="button" class="icon-btn" aria-label="Notifikasi">
                        <i class="ph ph-bell text-xl"></i>
                    </button>
                    
                    <button type="button" class="icon-btn" aria-label="Pengaturan">
                        <i class="ph ph-gear text-xl"></i>
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
