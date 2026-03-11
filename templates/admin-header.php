<?php $admin = requireAdmin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= csrfMeta() ?>
  <title><?= h($pageTitle ?? 'Admin') ?> — Prayer Display</title>
  <link rel="stylesheet" href="<?= url('/css/admin.css') ?>">
</head>
<body>
  <header class="admin-header">
    <h1>Prayer Display</h1>
    <nav>
      <a href="<?= url('/admin') ?>">Churches</a>
      <span class="admin-user"><?= h($admin['name']) ?></span>
      <a href="<?= url('/admin/logout') ?>">Logout</a>
    </nav>
  </header>
  <main class="admin-main">
