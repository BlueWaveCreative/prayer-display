<?php
requireMethod('GET');
$appVersion = trim(@file_get_contents(APP_ROOT . '/version.txt')) ?: 'dev';

$slug = $_GET['church'] ?? '';
if (!$slug) {
    jsonResponse(['success' => false, 'error' => 'Missing church parameter', 'names' => [], 'version' => $appVersion], 400);
}

// Simulate modes for testing
$simulate = $_GET['simulate'] ?? '';
if ($simulate === 'empty') {
    jsonResponse(['success' => true, 'names' => [], 'lastUpdated' => date('c'), 'version' => $appVersion]);
}
if ($simulate === 'error') {
    jsonResponse(['success' => false, 'error' => 'Simulated error for testing', 'names' => [], 'version' => $appVersion]);
}

// Look up church
$db = getDb();
$stmt = $db->prepare('SELECT id, pco_event_id, timezone, is_active FROM churches WHERE slug = ?');
$stmt->execute([$slug]);
$church = $stmt->fetch();

if (!$church || !$church['is_active']) {
    jsonResponse(['success' => false, 'error' => 'Church not found', 'names' => [], 'version' => $appVersion], 404);
}

if (!$church['pco_event_id']) {
    jsonResponse(['success' => false, 'error' => 'Church not configured', 'names' => [], 'version' => $appVersion], 500);
}

// Fetch check-ins from PCO
$names = pcoFetchCheckIns((int)$church['id'], $church['pco_event_id'], $church['timezone']);

if ($names === null) {
    jsonResponse(['success' => false, 'error' => 'Unable to fetch check-ins', 'names' => [], 'version' => $appVersion]);
}

jsonResponse([
    'success'     => true,
    'names'       => $names,
    'lastUpdated' => date('c'),
    'version'     => $appVersion,
]);
