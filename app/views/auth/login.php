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
    <link rel="stylesheet" href="<?= asset('css/auth.css') ?>">
</head>
<body class="auth-body">

    <button type="button" class="login-theme-toggle theme-toggle-btn" data-theme-toggle aria-label="Ganti mode tema (Terang / Gelap)" aria-pressed="false" title="Ganti Mode Tema">
        <i class="ph ph-sun theme-icon-light text-xl" aria-hidden="true"></i>
        <i class="ph ph-moon theme-icon-dark text-xl" aria-hidden="true"></i>
    </button>

    <main class="login-page" data-reveal-group>

        <div class="login-card">

            <!-- Left Panel: Branding -->
            <div class="login-brand-panel">
                <div class="login-brand-dots" aria-hidden="true">
                    <span></span>
                    <span></span>
                </div>

                <div class="login-brand-logo" data-reveal="up">
                    <img src="<?= asset('img/logo-asentra.png') ?>" alt="ASENTRA" class="login-brand-full-logo">
                </div>

                <div class="login-brand-content" data-reveal="up">
                    <h1>Selamat<br>Datang!</h1>
                    <p>Sistem Pendukung Keputusan Penilaian Kinerja Teknisi Lapangan. Silakan masuk untuk melanjutkan.</p>
                </div>
            </div>

            <!-- Right Panel: Login Form -->
            <div class="login-form-panel">

                <!-- Mobile Logo (visible only on small screens) -->
                <div class="login-mobile-logo" data-reveal="up">
                    <img src="<?= asset('img/logo-asentra.png') ?>" alt="ASENTRA" class="login-mobile-full-logo">
                </div>

                <div class="login-form-inner">

                    <?php if (!empty($error)): ?>
                        <div class="auth-error" role="alert" data-reveal="up">
                            <i class="ph ph-warning-circle text-lg"></i>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="<?= route('/login') ?>" method="POST" class="auth-form" data-reveal="up">
                        <?= csrfField() ?>

                        <!-- Username Field -->
                        <div class="soft-input-group">
                            <div class="input-icon-box">
                                <i class="ph ph-user text-xl"></i>
                            </div>
                            <div class="soft-input-content">
                                <label class="soft-input-label" for="username">Username</label>
                                <input type="text" id="username" name="username" class="soft-input" placeholder="Masukkan username" required autofocus autocomplete="username">
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="soft-input-group">
                            <div class="input-icon-box">
                                <i class="ph ph-lock-key text-xl"></i>
                            </div>
                            <div class="soft-input-content">
                                <label class="soft-input-label" for="password">Password</label>
                                <input type="password" id="password" name="password" class="soft-input" placeholder="••••••••••" required autocomplete="current-password">
                            </div>
                            <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Tampilkan password">
                                <i id="eyeIcon" class="ph ph-eye text-xl"></i>
                            </button>
                        </div>

                        <!-- Login Button -->
                        <div style="margin-top: var(--space-6);">
                            <button type="submit" class="btn-login" id="submitBtn">
                                <span id="btnText">Masuk</span>
                                <div id="btnSpinner" class="btn-spinner" style="display: none;"></div>
                            </button>
                        </div>
                    </form>

                    <p class="auth-demo" data-reveal="up">
                        Demo: <span>admin / admin</span> &middot; <span>owner / owner</span>
                    </p>
                </div>
            </div>

        </div>
    </main>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        function togglePassword() {
            var passInput = document.getElementById('password');
            var eyeIcon = document.getElementById('eyeIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.className = 'ph ph-eye-slash text-xl';
            } else {
                passInput.type = 'password';
                eyeIcon.className = 'ph ph-eye text-xl';
            }
        }
    </script>
</body>
</html>
