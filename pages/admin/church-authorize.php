<?php
$admin = requireAdmin();

$churchId = (int)($_GET['id'] ?? 0);
if (!$churchId) {
    redirect('/admin');
}

$db = getDb();
$stmt = $db->prepare('SELECT id, name FROM churches WHERE id = ?');
$stmt->execute([$churchId]);
$church = $stmt->fetch();

if (!$church) {
    redirect('/admin');
}

// Generate and redirect to PCO OAuth URL
$authorizeUrl = pcoGetAuthorizeUrl($churchId);
redirect($authorizeUrl);
