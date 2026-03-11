<?php
/**
 * Cron: Refresh PCO OAuth tokens that are expiring soon.
 * Run every hour via cron.
 *
 * Usage: php /path/to/cron/refresh-tokens.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

define('APP_ROOT', realpath(__DIR__ . '/..'));
require_once APP_ROOT . '/src/config.php';
require_once APP_ROOT . '/src/db.php';
require_once APP_ROOT . '/src/helpers.php';
require_once APP_ROOT . '/src/pco-oauth.php';

echo date('Y-m-d H:i:s') . " — Starting token refresh\n";

$db = getDb();

// Find tokens expiring within 30 minutes
$stmt = $db->prepare(
    'SELECT ct.*, c.name as church_name
     FROM church_tokens ct
     JOIN churches c ON c.id = ct.church_id
     WHERE c.is_active = 1
       AND ct.token_expires_at <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)'
);
$stmt->execute();
$expiring = $stmt->fetchAll();

if (empty($expiring)) {
    echo "No tokens need refreshing.\n";
    exit;
}

foreach ($expiring as $token) {
    echo "Refreshing token for {$token['church_name']} (ID: {$token['church_id']})... ";

    $newTokens = pcoRefreshToken($token['refresh_token']);

    if ($newTokens) {
        pcoStoreTokens((int)$token['church_id'], $newTokens);
        echo "OK\n";
    } else {
        echo "FAILED\n";
        $db->prepare('UPDATE church_tokens SET last_api_error = ? WHERE church_id = ?')
           ->execute(['Cron refresh failed at ' . date('Y-m-d H:i:s'), $token['church_id']]);
    }
}

echo date('Y-m-d H:i:s') . " — Done\n";
