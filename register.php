<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$error = null;
if (request_is_post()) {
    verify_csrf_or_fail();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid name and email address.';
    } elseif (strlen($password) < 12 || $password !== $confirmation) {
        $error = 'Passwords must match and contain at least 12 characters.';
    } else {
        try {
            $stmt = database_connection()->prepare(
                'INSERT INTO users (name, email, password_hash, role, is_active, quota_bytes)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'user',
                1,
                DEFAULT_USER_QUOTA_BYTES,
            ]);
            flash('success', 'Registration complete. You can now sign in.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'That email address is already registered.';
            } elseif (str_contains($exception->getMessage(), 'SQLSTATE[HY000] [2002]')) {
                $error = 'MySQL is not running. Start MySQL in XAMPP, complete installation, and try again.';
            } else {
                log_error($exception);
                $error = 'Registration could not be completed. Please try again later.' . $exception->getMessage();
            }
        } catch (Throwable $exception) {
            log_error($exception);
            $error = 'Registration could not be completed. Please try again later.' .$exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-page">
<main class="auth-card">
    <div class="brand-mark">NV</div>
    <p class="eyebrow">Create an account</p>
    <h1>Register</h1>
    <p class="text-secondary">Create a standard user account for <?= e(APP_NAME) ?>.</p>
    <?php if ($error !== null): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label class="form-label" for="name">Name</label>
        <input class="form-control mb-3" id="name" name="name" maxlength="120" required>
        <label class="form-label" for="email">Email address</label>
        <input class="form-control mb-3" id="email" name="email" type="email" autocomplete="email" required>
        <label class="form-label" for="password">Password</label>
        <input class="form-control mb-3" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
        <label class="form-label" for="password_confirmation">Confirm password</label>
        <input class="form-control mb-4" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
        <button class="btn btn-primary w-100" type="submit">Create account</button>
    </form>
    <p class="text-center mt-4 mb-0"><a href="<?= e(app_url('login.php')) ?>">Already have an account? Sign in</a></p>
</main>
</body>
</html>
