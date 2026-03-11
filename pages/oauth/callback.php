<?php
$code  = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    echo '<h1>Authorization Failed</h1>';
    echo '<p>The authorization was not completed. Error: ' . h($error) . '</p>';
    echo '<p><a href="' . url('/admin') . '">Return to Admin</a></p>';
    exit;
}

if (!$code || !$state) {
    echo '<h1>Invalid Request</h1>';
    echo '<p>Missing authorization code or state.</p>';
    exit;
}

// Validate state and get church ID
$db = getDb();
$stmt = $db->prepare('SELECT church_id FROM oauth_states WHERE state = ?');
$stmt->execute([$state]);
$row = $stmt->fetch();

if (!$row) {
    echo '<h1>Invalid State</h1>';
    echo '<p>This authorization link has expired or was already used.</p>';
    exit;
}

$churchId = (int)$row['church_id'];

// Clean up used state
$db->prepare('DELETE FROM oauth_states WHERE state = ?')->execute([$state]);

// Exchange code for tokens
$tokenData = pcoExchangeCode($code);

if (!$tokenData) {
    echo '<h1>Token Exchange Failed</h1>';
    echo '<p>Unable to complete authorization. Please try again.</p>';
    echo '<p><a href="' . url('/admin') . '">Return to Admin</a></p>';
    exit;
}

// Store tokens
pcoStoreTokens($churchId, $tokenData);

// Get church name for confirmation
$stmt = $db->prepare('SELECT name FROM churches WHERE id = ?');
$stmt->execute([$churchId]);
$church = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Authorization Complete</title>
  <link rel="stylesheet" href="<?= url('/css/admin.css') ?>">
</head>
<body>
  <div class="login-container">
    <h1>Authorization Complete</h1>
    <p><strong><?= h($church['name'] ?? 'Church') ?></strong> has been connected to Planning Center.</p>
    <p><a href="<?= url('/admin') ?>" class="btn btn-primary">Return to Admin</a></p>
  </div>
</body>
</html>
