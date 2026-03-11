<?php
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => true,
            'httponly'  => true,
            'samesite'  => 'Lax',
        ]);
        session_start();
    }
}

function requireAdmin(): array {
    startSession();
    if (empty($_SESSION['admin_id'])) {
        redirect('/admin/login');
    }
    $db = getDb();
    $stmt = $db->prepare('SELECT id, email, name FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        session_destroy();
        redirect('/admin/login');
    }
    return $user;
}

function loginAdmin(string $email, string $password): ?array {
    $db = getDb();
    $stmt = $db->prepare('SELECT id, email, name, password_hash FROM admin_users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }
    startSession();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $user['id'];
    return $user;
}

function logoutAdmin(): void {
    startSession();
    session_destroy();
}
