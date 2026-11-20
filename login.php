<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$error = null;
if (request_is_post()) {
    verify_csrf_or_fail();
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    remember_input(['identifier' => $identifier]);

    if ($identifier === '' || $password === '') {
        $error = 'Enter a username or email address and password.';
    } else {
        try {
            $user = authenticate_user($identifier, $password);
            if ($user === null) {
                $error = 'The username/email or password is incorrect, or the account is inactive.';
            } else {
                sign_in($user);
                clear_old_input();
                flash('success', 'Welcome back, ' . $user['name'] . '.');
                redirect('dashboard.php');
            }
        } catch (Throwable $exception) {
            log_error($exception);
            $error = 'The service is not configured yet. Run the installer first.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-page">
<main class="auth-card">
    <div class="brand-mark">NV</div>
    <p class="eyebrow">Secure digital storage</p>
    <h1>Welcome back</h1>
    <p class="text-secondary">Sign in to continue to <?= e(APP_NAME) ?>.</p>
    <?php if ($error !== null): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="identifier">Username or email address</label>
            <input class="form-control" id="identifier" name="identifier" type="text" autocomplete="username" value="<?= old_input('identifier') ?>" required>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Sign in</button>
    </form>
    <p class="text-center mt-4 mb-0"><a href="<?= e(app_url('register.php')) ?>">Create an account</a></p>
</main>
</body>
</html>
