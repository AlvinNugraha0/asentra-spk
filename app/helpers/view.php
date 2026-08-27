<?php
// ASENTRA SPK — View partial helpers

declare(strict_types=1);

/**
 * Render a reusable view partial with data.
 *
 * @param string $partial Partial path relative to app/views without .php
 * @param array<string, mixed> $data
 */
function viewPartial(string $partial, array $data = []): string
{
    $file = APP_PATH . '/views/' . str_replace('.', '/', $partial) . '.php';

    if (!is_file($file)) {
        // Return empty string if partial missing to avoid fatal on foundation stage
        return '';
    }

    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    return ob_get_clean();
}

/**
 * Render a global notification area if flash message exists in session.
 */
function renderFlash(): void
{
    $flash = $_SESSION['flash'] ?? null;
    if (!$flash) {
        return;
    }

    $type = $flash['type'] ?? 'success';
    $message = $flash['message'] ?? '';
    unset($_SESSION['flash']);

    echo '<script>window._toast = ' . json_encode(['type' => $type, 'message' => $message], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';</script>';
}

/**
 * Set a flash message in session.
 */
function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
