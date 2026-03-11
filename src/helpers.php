<?php
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a URL with the base path prefix.
 * e.g., url('/admin') => '/prayer/admin'
 */
function url(string $path = ''): string {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect(string $path): void {
    // Only prepend base path for internal paths (starting with /)
    // External URLs (http/https) pass through unchanged
    if (str_starts_with($path, 'http')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

function requireMethod(string ...$methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $methods));
        exit;
    }
}
