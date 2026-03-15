<?php
/**
 * Cron: Check prayer display health and email alerts.
 * Run every 30 minutes via cron.
 *
 * Usage: php /path/to/cron/health-check.php
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
require_once APP_ROOT . '/src/pco-api.php';

define('ALERT_COOLDOWN_HOURS', 2);
define('ALERT_RECIPIENTS', 'kenny@bluewavecreativedesign.com, josh@bluewavecreativedesign.com');
define('ALERT_FROM', 'Prayer Display <no-reply@bluewavecreativedesign.com>');

echo date('Y-m-d H:i:s') . " — Starting health check\n";

$db = getDb();

// Get all active churches with token info
$churches = $db->query(
    'SELECT c.*, ct.token_expires_at, ct.last_api_success_at, ct.last_api_error,
            ct.last_alert_sent_at, ct.access_token, ct.refresh_token
     FROM churches c
     LEFT JOIN church_tokens ct ON ct.church_id = c.id
     WHERE c.is_active = 1
     ORDER BY c.name'
)->fetchAll();

$problems = [];

foreach ($churches as $church) {
    $churchName = $church['name'];
    $issue = null;

    // Check 1: No token at all
    if (empty($church['token_expires_at'])) {
        $issue = 'No PCO token configured — display cannot fetch check-ins.';
    }
    // Check 2: Token has an error recorded
    elseif (!empty($church['last_api_error'])) {
        $issue = 'API error: ' . $church['last_api_error'];
    }
    // Check 3: Token expired and no recent success
    elseif (strtotime($church['token_expires_at']) < time()) {
        $issue = 'OAuth token expired at ' . $church['token_expires_at'] . '.';
    }
    // Check 4: No successful API call in the last 6 hours
    elseif (!empty($church['last_api_success_at'])
            && strtotime($church['last_api_success_at']) < strtotime('-6 hours')) {
        $issue = 'No successful API call since ' . $church['last_api_success_at'] . '.';
    }
    // Check 5: No event ID configured
    elseif (empty($church['pco_event_id'])) {
        $issue = 'No PCO event ID configured.';
    }

    if (!$issue) {
        echo "  {$churchName}: OK\n";
        continue;
    }

    // Check cooldown — don't re-alert within 2 hours
    if (!empty($church['last_alert_sent_at'])
        && strtotime($church['last_alert_sent_at']) > strtotime('-' . ALERT_COOLDOWN_HOURS . ' hours')) {
        echo "  {$churchName}: PROBLEM (alert cooldown active, skipping)\n";
        continue;
    }

    echo "  {$churchName}: PROBLEM — {$issue}\n";
    $problems[] = [
        'church_id'   => $church['id'],
        'church_name' => $churchName,
        'slug'        => $church['slug'],
        'issue'       => $issue,
    ];
}

if (empty($problems)) {
    echo "All churches healthy.\n";
    exit;
}

// Build email
$subject = 'Prayer Display Alert: ' . count($problems) . ' issue(s) detected';

$body = "Prayer Display Health Check\n";
$body .= "===========================\n\n";
$body .= count($problems) . " church(es) need attention:\n\n";

foreach ($problems as $p) {
    $body .= "Church: {$p['church_name']} ({$p['slug']})\n";
    $body .= "Issue:  {$p['issue']}\n";
    $body .= "Admin:  https://bluewavecreativedesign.com/prayer/admin\n\n";
}

$body .= "---\n";
$body .= "This alert will not repeat for " . ALERT_COOLDOWN_HOURS . " hours per church.\n";
$body .= "Sent by Prayer Display health check cron.\n";

$headers = "From: " . ALERT_FROM . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail(ALERT_RECIPIENTS, $subject, $body, $headers);

if ($sent) {
    echo "Alert email sent to " . ALERT_RECIPIENTS . "\n";

    // Update cooldown timestamps
    foreach ($problems as $p) {
        $check = $db->prepare('SELECT id FROM church_tokens WHERE church_id = ?');
        $check->execute([$p['church_id']]);
        if ($check->fetch()) {
            $db->prepare('UPDATE church_tokens SET last_alert_sent_at = NOW() WHERE church_id = ?')
               ->execute([$p['church_id']]);
        } else {
            // No token row yet — create a placeholder so cooldown works
            $db->prepare('INSERT INTO church_tokens (church_id, access_token, refresh_token, token_expires_at, last_alert_sent_at) VALUES (?, "", "", NOW(), NOW())')
               ->execute([$p['church_id']]);
        }
    }
} else {
    echo "ERROR: Failed to send alert email!\n";
}

echo date('Y-m-d H:i:s') . " — Done\n";
