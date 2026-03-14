<?php
header('Cache-Control: no-store');
header('Content-Type: text/plain');

// Load app config for DB credentials
define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/src/config.php';

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column already exists
    $cols = $db->query("SHOW COLUMNS FROM church_tokens LIKE 'last_alert_sent_at'")->fetchAll();
    if (count($cols) > 0) {
        echo "Column last_alert_sent_at already exists. No changes needed.\n";
    } else {
        $db->exec("ALTER TABLE church_tokens ADD COLUMN last_alert_sent_at TIMESTAMP DEFAULT NULL");
        echo "SUCCESS: Added last_alert_sent_at column to church_tokens.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
