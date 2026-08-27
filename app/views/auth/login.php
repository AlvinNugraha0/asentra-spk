<?php
/** @var string $title */
/** @var ?string $error */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/auth.css') ?>">
</head>
<body class="auth-body">
    <div class="auth-ambient"></div>

    <main class="login-page">
        <div class="login-card">
            <div class="login-brand">
                <div class="logo login-logo">AS</div>
                <h1 class="login-title"><?= e(APP_NAME) ?></h1>
                <p class="login-subtitle">Sistem Pendukung Keputusan Penilaian Kinerja Teknisi</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="auth-error" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= route('/login') ?>" method="POST" class="auth-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="input input-auth" placeholder="Masukkan username" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="input input-auth" placeholder="Masukkan password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-auth w-full">Login</button>
            </form>

            <p class="auth-demo">Demo: <span>admin/admin</span> &middot; <span>owner/owner</span></p>
        </div>
    </main>
</body>
</html>
