<?php
$pageTitle = 'Churches';

$db = getDb();
$churches = $db->query(
    'SELECT c.*, ct.last_api_success_at, ct.last_api_error, ct.token_expires_at
     FROM churches c
     LEFT JOIN church_tokens ct ON ct.church_id = c.id
     ORDER BY c.name'
)->fetchAll();

require APP_ROOT . '/templates/admin-header.php';
?>

<div class="dashboard">
  <div class="dashboard-header">
    <h2>Churches</h2>
    <a href="<?= url('/admin/church/new') ?>" class="btn btn-primary">+ Add Church</a>
  </div>

  <table class="church-table">
    <thead>
      <tr>
        <th>Church</th>
        <th>Slug</th>
        <th>Timezone</th>
        <th>Event ID</th>
        <th>Token Status</th>
        <th>Active</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($churches as $church): ?>
        <?php
          $hasToken = !empty($church['token_expires_at']);
          $tokenOk = $hasToken && empty($church['last_api_error']) && !empty($church['last_api_success_at']);
          $tokenExpired = $hasToken && strtotime($church['token_expires_at']) < time() && !empty($church['last_api_error']);
        ?>
        <tr>
          <td><?= h($church['name']) ?></td>
          <td><code><?= h($church['slug']) ?></code></td>
          <td><?= h($church['timezone']) ?></td>
          <td><?= h($church['pco_event_id'] ?? '—') ?></td>
          <td>
            <?php if (!$hasToken): ?>
              <span class="status status-none">No token</span>
            <?php elseif ($tokenOk): ?>
              <span class="status status-ok">Healthy</span>
            <?php elseif ($tokenExpired): ?>
              <span class="status status-error">Error</span>
            <?php else: ?>
              <span class="status status-warning">Unknown</span>
            <?php endif; ?>
          </td>
          <td><?= $church['is_active'] ? 'Yes' : 'No' ?></td>
          <td>
            <a href="<?= url('/admin/church/edit?id=' . $church['id']) ?>">Edit</a>
            <?php if (!$hasToken || $tokenExpired): ?>
              | <a href="<?= url('/admin/church/authorize?id=' . $church['id']) ?>">Authorize</a>
            <?php endif; ?>
            | <a href="<?= url('/d/' . h($church['slug'])) ?>" target="_blank">View Display</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require APP_ROOT . '/templates/admin-footer.php'; ?>
