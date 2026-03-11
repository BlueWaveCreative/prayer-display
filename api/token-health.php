<?php
requireMethod('GET');
requireAdmin();

$db = getDb();
$results = $db->query(
    'SELECT c.id, c.name, c.slug, ct.token_expires_at, ct.last_refreshed_at, ct.last_api_success_at, ct.last_api_error
     FROM churches c
     LEFT JOIN church_tokens ct ON ct.church_id = c.id
     WHERE c.is_active = 1
     ORDER BY c.name'
)->fetchAll();

$health = [];
foreach ($results as $row) {
    $status = 'no_token';
    if ($row['token_expires_at']) {
        if (!empty($row['last_api_error'])) {
            $status = 'error';
        } elseif (!empty($row['last_api_success_at'])) {
            $status = 'healthy';
        } else {
            $status = 'unknown';
        }
    }

    $health[] = [
        'id'               => $row['id'],
        'name'             => $row['name'],
        'slug'             => $row['slug'],
        'status'           => $status,
        'last_success'     => $row['last_api_success_at'],
        'last_error'       => $row['last_api_error'],
        'token_expires_at' => $row['token_expires_at'],
    ];
}

jsonResponse(['churches' => $health]);
