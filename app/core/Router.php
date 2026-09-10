<?php
// ASENTRA SPK — Simple router

declare(strict_types=1);

return function (): void {
    $path = currentRoute();
    $method = requestMethod();

    // Strip query string from path
    $path = parse_url($path, PHP_URL_PATH) ?? $path;

    $routes = [
        // Auth
        ['GET', '/login', 'App\\Controllers\\AuthController@showLogin'],
        ['POST', '/login', 'App\\Controllers\\AuthController@login'],
        ['POST', '/logout', 'App\\Controllers\\AuthController@logout'],

        // Admin
        ['GET', '/admin/dashboard', 'App\\Controllers\\AdminDashboardController@index'],
        ['GET', '/admin/teknisi', 'App\\Controllers\\TeknisiController@index'],
        ['GET', '/admin/teknisi/create', 'App\\Controllers\\TeknisiController@create'],
        ['POST', '/admin/teknisi/store', 'App\\Controllers\\TeknisiController@store'],
        ['GET', '/admin/teknisi/edit/{id}', 'App\\Controllers\\TeknisiController@edit'],
        ['POST', '/admin/teknisi/update', 'App\\Controllers\\TeknisiController@update'],
        ['POST', '/admin/teknisi/delete', 'App\\Controllers\\TeknisiController@delete'],
        ['POST', '/admin/teknisi/toggle-status', 'App\\Controllers\\TeknisiController@toggleStatus'],

        ['GET', '/admin/kriteria', 'App\\Controllers\\KriteriaController@index'],
        ['POST', '/admin/kriteria/update', 'App\\Controllers\\KriteriaController@update'],

        // Admin penilaian — monitoring only (read-only)
        ['GET', '/admin/penilaian', 'App\\Controllers\\PenilaianController@index'],
        ['GET', '/admin/riwayat', 'App\\Controllers\\PenilaianController@history'],

        // Owner
        ['GET', '/owner/dashboard', 'App\\Controllers\\OwnerDashboardController@index'],

        // Owner — Teknisi (read-only)
        ['GET', '/owner/teknisi', 'App\\Controllers\\OwnerTeknisiController@index'],

        // Owner — Penilaian Kinerja
        ['GET', '/owner/penilaian', 'App\\Controllers\\OwnerPenilaianController@index'],
        ['GET', '/owner/penilaian/create', 'App\\Controllers\\OwnerPenilaianController@create'],
        ['POST', '/owner/penilaian/store', 'App\\Controllers\\OwnerPenilaianController@store'],
        ['GET', '/owner/penilaian/edit/{id}', 'App\\Controllers\\OwnerPenilaianController@edit'],
        ['POST', '/owner/penilaian/update', 'App\\Controllers\\OwnerPenilaianController@update'],
        ['GET', '/owner/penilaian/detail/{id}', 'App\\Controllers\\OwnerPenilaianController@detail'],
        ['GET', '/owner/riwayat-penilaian', 'App\\Controllers\\OwnerPenilaianController@history'],

        // Owner — Ranking & SAW
        ['GET', '/owner/ranking', 'App\\Controllers\\RankingController@index'],
        ['POST', '/owner/ranking/process', 'App\\Controllers\\RankingController@process'],
        ['GET', '/owner/ranking/detail/{id}', 'App\\Controllers\\RankingController@detail'],
        ['GET', '/owner/riwayat', 'App\\Controllers\\RankingController@history'],
        ['GET', '/owner/laporan', 'App\\Controllers\\LaporanController@index'],
        ['GET', '/owner/laporan/{periode}', 'App\\Controllers\\LaporanController@show'],
    ];

    foreach ($routes as [$httpMethod, $route, $handler]) {
        if ($httpMethod !== $method) {
            continue;
        }

        $pattern = '#^' . preg_replace('/\{[^}]+\}/', '([^/]+)', $route) . '$#';
        if (preg_match($pattern, $path, $matches)) {
            array_shift($matches);
            dispatch($handler, $matches);
            return;
        }
    }

    // Default: home redirects based on role, otherwise login
    if ($path === '/' || $path === '') {
        if (isAuthenticated()) {
            redirect(hasRole('admin') ? '/admin/dashboard' : '/owner/dashboard');
        }
        redirect('/login');
    }

    http_response_code(404);
    render('errors/404', ['title' => 'Halaman Tidak Ditemukan']);
};

/**
 * Resolve controller action and call it.
 *
 * @param string $handler Format: Namespace\Controllers\ControllerName@method
 * @param array<int, string> $params
 */
function dispatch(string $handler, array $params = []): void
{
    [$className, $method] = explode('@', $handler, 2);

    if (!class_exists($className)) {
        http_response_code(500);
        exit("Controller not found: {$className}");
    }

    $controller = new $className();

    if (!method_exists($controller, $method)) {
        http_response_code(500);
        exit("Action not found: {$className}@{$method}");
    }

    $controller->$method(...$params);
}

/**
 * Render a view with optional data.
 *
 * @param string $view Path relative to app/views without .php
 * @param array<string, mixed> $data
 */
function render(string $view, array $data = []): void
{
    $viewFile = APP_PATH . '/views/' . str_replace('.', '/', $view) . '.php';

    if (!is_file($viewFile)) {
        http_response_code(500);
        exit("View not found: {$view}");
    }

    extract($data, EXTR_SKIP);
    require $viewFile;
}

/**
 * Render a view inside the app layout.
 *
 * @param string $view
 * @param array<string, mixed> $data
 * @param string $layout
 */
function renderWithLayout(string $view, array $data = [], string $layout = 'layouts/app'): void
{
    $content = captureView($view, $data);
    $data['content'] = $content;
    render($layout, $data);
}

/**
 * Capture view output to a string.
 *
 * @param string $view
 * @param array<string, mixed> $data
 */
function captureView(string $view, array $data = []): string
{
    $viewFile = APP_PATH . '/views/' . str_replace('.', '/', $view) . '.php';

    if (!is_file($viewFile)) {
        http_response_code(500);
        exit("View not found: {$view}");
    }

    extract($data, EXTR_SKIP);
    ob_start();
    require $viewFile;
    return ob_get_clean();
}
