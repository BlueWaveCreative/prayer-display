<?php
function generateCsrfToken(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrfMeta(): string {
    return '<meta name="csrf-token" content="' . h(generateCsrfToken()) . '">';
}

function csrfField(): string {
    return '<input type="hidden" name="_csrf_token" value="' . h(generateCsrfToken()) . '">';
}

function verifyCsrf(): void {
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(generateCsrfToken(), $token)) {
        http_response_code(403);
        echo 'CSRF validation failed';
        exit;
    }
}
