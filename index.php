<?php
// Auto-detect directory structure:
// Dev mode: index.php is in public/, everything else is ../
// Flat deploy: index.php is alongside src/, api/, pages/, etc.
if (file_exists(__DIR__ . '/../src/config.php')) {
    define('APP_ROOT', realpath(__DIR__ . '/..'));
} else {
    define('APP_ROOT', __DIR__);
}

// Load core libraries
require_once APP_ROOT . '/src/config.php';
require_once APP_ROOT . '/src/db.php';
require_once APP_ROOT . '/src/helpers.php';
require_once APP_ROOT . '/src/csrf.php';
require_once APP_ROOT . '/src/auth.php';
require_once APP_ROOT . '/src/pco-oauth.php';
require_once APP_ROOT . '/src/pco-api.php';

// Prevent SiteGround nginx proxy from caching dynamic responses
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$route = trim($_GET['route'] ?? '', '/');

// --- API routes ---
if (str_starts_with($route, 'api/')) {
    $apiRoute = substr($route, 4); // strip "api/"

    switch ($apiRoute) {
        case 'checkins':
            require APP_ROOT . '/api/checkins.php';
            break;
        case 'churches':
            require APP_ROOT . '/api/churches.php';
            break;
        case 'token-health':
            require APP_ROOT . '/api/token-health.php';
            break;
        default:
            jsonResponse(['error' => 'Not found'], 404);
    }
    exit;
}

// --- Page routes ---
$pageMap = [
    ''                       => 'pages/admin/dashboard.php',
    'admin'                  => 'pages/admin/dashboard.php',
    'admin/login'            => 'pages/admin/login.php',
    'admin/logout'           => 'pages/admin/logout.php',
    'admin/church/new'       => 'pages/admin/church-edit.php',
    'admin/church/edit'      => 'pages/admin/church-edit.php',
    'admin/church/authorize' => 'pages/admin/church-authorize.php',
    'oauth/callback'         => 'pages/oauth/callback.php',
];

// Check for display route: d/{slug}
if (preg_match('#^d/([a-z0-9-]+)$#', $route, $matches)) {
    $_GET['slug'] = $matches[1];
    require APP_ROOT . '/pages/display.php';
    exit;
}

if (isset($pageMap[$route])) {
    require APP_ROOT . '/' . $pageMap[$route];
    exit;
}

// 404
http_response_code(404);
echo '404 Not Found';
