<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$user = require_auth();
$message = null;
$error = null;

if (request_is_post()) {
    verify_csrf_or_fail();
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['new_password_confirmation'] ?? '');

    if (strlen($new) < 12 || $new !== $confirm) {
        $error = 'Use a matching password of at least 12 characters.';
    } else {
        $stmt = database_connection()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        $hash = (string) ($stmt->fetchColumn() ?: '');
        if (!password_verify($current, $hash)) {
            $error = 'The current password is incorrect.';
        } else {
            $update = database_connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);
            $message = 'Your password was updated.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<main class="container py-5">
    <a href="<?= e(app_url('dashboard.php')) ?>" class="text-decoration-none">&larr; Dashboard</a>
    <h1 class="h2 mt-4">Profile</h1>
    <p class="text-secondary"><?= e($user['email']) ?></p>
    <?php if ($message !== null): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error !== null): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <section class="dashboard-card mt-4" style="max-width: 560px;">
        <h2 class="h5">Change password</h2>
        <form method="post">
            <?= csrf_field() ?>
            <label class="form-label mt-3" for="current_password">Current password</label>
            <input class="form-control" id="current_password" name="current_password" type="password" required>
            <label class="form-label mt-3" for="new_password">New password</label>
            <input class="form-control" id="new_password" name="new_password" type="password" minlength="12" required>
            <label class="form-label mt-3" for="new_password_confirmation">Confirm new password</label>
            <input class="form-control" id="new_password_confirmation" name="new_password_confirmation" type="password" minlength="12" required>
            <button class="btn btn-primary mt-4" type="submit">Update password</button>
        </form>
    </section>
</main>
</body>
</html>
