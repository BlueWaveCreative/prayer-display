<?php
startSession();

// Already logged in?
if (!empty($_SESSION['admin_id'])) {
    redirect('/admin');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = loginAdmin($email, $password);
    if ($user) {
        redirect('/admin');
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Prayer Display</title>
  <link rel="stylesheet" href="<?= url('/css/admin.css') ?>">
</head>
<body>
  <div class="login-container">
    <h1>Prayer Display Admin</h1>
    <?php if ($error): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <?= csrfField() ?>
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <button type="submit">Log In</button>
    </form>
  </div>
</body>
</html>
