<?php
$pageTitle = 'Church';
$db = getDb();
$error = '';
$success = '';

$churchId = (int)($_GET['id'] ?? 0);
$church = null;

if ($churchId) {
    $stmt = $db->prepare('SELECT * FROM churches WHERE id = ?');
    $stmt->execute([$churchId]);
    $church = $stmt->fetch();
    if (!$church) {
        redirect('/admin');
    }
    $pageTitle = 'Edit ' . $church['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name'] ?? '');
    $slug     = trim($_POST['slug'] ?? '');
    $timezone = trim($_POST['timezone'] ?? 'America/New_York');
    $eventId  = trim($_POST['pco_event_id'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validate
    if (!$name || !$slug) {
        $error = 'Name and slug are required';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $error = 'Slug must be lowercase letters, numbers, and hyphens only';
    } elseif (!in_array($timezone, timezone_identifiers_list())) {
        $error = 'Invalid timezone';
    } else {
        // Handle background image upload
        $bgImage = $church['background_image'] ?? null;
        if (!empty($_FILES['background']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $filename = $slug . '-bg.' . $ext;
                move_uploaded_file(
                    $_FILES['background']['tmp_name'],
                    APP_ROOT . '/uploads/' . $filename
                );
                $bgImage = $filename;
            } else {
                $error = 'Background must be PNG, JPG, or WebP';
            }
        }

        if (!$error) {
            try {
                if ($churchId) {
                    $stmt = $db->prepare(
                        'UPDATE churches SET name = ?, slug = ?, timezone = ?, pco_event_id = ?, background_image = ?, is_active = ? WHERE id = ?'
                    );
                    $stmt->execute([$name, $slug, $timezone, $eventId ?: null, $bgImage, $isActive, $churchId]);
                } else {
                    $stmt = $db->prepare(
                        'INSERT INTO churches (name, slug, timezone, pco_event_id, background_image, is_active) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$name, $slug, $timezone, $eventId ?: null, $bgImage, $isActive]);
                    $churchId = (int)$db->lastInsertId();
                }
                redirect('/admin');
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $error = 'A church with that slug already exists';
                } else {
                    error_log('Church save error: ' . $e->getMessage());
                    $error = 'Failed to save church';
                }
            }
        }
    }

    // Rebuild church array for form re-population
    $church = [
        'id' => $churchId, 'name' => $name, 'slug' => $slug,
        'timezone' => $timezone, 'pco_event_id' => $eventId,
        'background_image' => $bgImage ?? null, 'is_active' => $isActive,
    ];
}

require APP_ROOT . '/templates/admin-header.php';
?>

<div class="church-edit">
  <h2><?= $churchId ? 'Edit Church' : 'Add Church' ?></h2>

  <?php if ($error): ?>
    <div class="error"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <label for="name">Church Name</label>
    <input type="text" id="name" name="name" value="<?= h($church['name'] ?? '') ?>" required>

    <label for="slug">Slug (used in display URL)</label>
    <input type="text" id="slug" name="slug" value="<?= h($church['slug'] ?? '') ?>" required
           pattern="[a-z0-9-]+" placeholder="e.g. living-water-church">
    <small>Display URL will be: <?= h(APP_URL) ?>/d/<strong>{slug}</strong></small>

    <label for="timezone">Timezone</label>
    <select id="timezone" name="timezone">
      <?php foreach (['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'America/Phoenix', 'America/Anchorage', 'Pacific/Honolulu'] as $tz): ?>
        <option value="<?= $tz ?>" <?= ($church['timezone'] ?? 'America/New_York') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
      <?php endforeach; ?>
    </select>

    <label for="pco_event_id">PCO Event ID</label>
    <input type="text" id="pco_event_id" name="pco_event_id" value="<?= h($church['pco_event_id'] ?? '') ?>"
           placeholder="e.g. 945124">

    <label for="background">Background Image</label>
    <?php if (!empty($church['background_image'])): ?>
      <p>Current: <?= h($church['background_image']) ?></p>
    <?php endif; ?>
    <input type="file" id="background" name="background" accept="image/png,image/jpeg,image/webp">

    <label>
      <input type="checkbox" name="is_active" <?= ($church['is_active'] ?? 1) ? 'checked' : '' ?>>
      Active
    </label>

    <button type="submit" class="btn btn-primary"><?= $churchId ? 'Save Changes' : 'Create Church' ?></button>
    <a href="<?= url('/admin') ?>" class="btn">Cancel</a>
  </form>
</div>

<?php require APP_ROOT . '/templates/admin-footer.php'; ?>
