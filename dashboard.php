<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$user = require_auth();
$flashes = consume_flashes();
$fileStats = ['file_count' => 0, 'used_bytes' => 0];
try {
    $statsStmt = database_connection()->prepare(
        'SELECT COUNT(*) AS file_count, COALESCE(SUM(size_bytes), 0) AS used_bytes
         FROM files WHERE user_id = ? AND is_deleted = 0'
    );
    $statsStmt->execute([(int) $user['id']]);
    $fileStats = $statsStmt->fetch() ?: $fileStats;
} catch (Throwable $exception) {
    log_error($exception);
    $flashes[] = ['type' => 'warning', 'message' => 'File statistics are temporarily unavailable.'];
}
$usedBytes = (int) $fileStats['used_bytes'];
$quotaBytes = max(1, (int) $user['quota_bytes']);
$usagePercent = min(100, (int) round(($usedBytes / $quotaBytes) * 100));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(app_url('dashboard.php')) ?>">Nimbus Vault</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-secondary"><?= e($user['name']) ?></span>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(app_url('logout.php')) ?>">Sign out</a>
        </div>
    </div>
</nav>
<main class="container py-5">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="eyebrow mb-1">Workspace</p>
            <h1 class="h2 mb-0">Your storage overview</h1>
        </div>
        <span class="badge text-bg-light"><?= e(ucfirst((string) $user['role'])) ?></span>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <section class="dashboard-card">
                <p class="text-secondary mb-2">Storage limit</p>
                <strong><?= e(number_format(((int) $user['quota_bytes']) / 1073741824, 1)) ?> GB</strong>
                <div class="progress mt-3" role="progressbar" aria-label="Storage used" aria-valuenow="<?= e((string) $usagePercent) ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: <?= e((string) $usagePercent) ?>%"></div>
                </div>
                <small class="text-secondary"><?= e(number_format($usedBytes / 1048576, 1)) ?> MB used</small>
            </section>
        </div>
        <div class="col-md-4">
            <section class="dashboard-card">
                <p class="text-secondary mb-2">Files</p>
                <strong><?= e(number_format((int) $fileStats['file_count'])) ?></strong>
                <p class="text-secondary">Files in your workspace.</p>
                <a class="btn btn-outline-primary btn-sm" href="<?= e(app_url('files.php')) ?>">View files</a>
            </section>
        </div>
        <div class="col-md-4">
            <section class="dashboard-card">
                <p class="text-secondary mb-2">Quick action</p>
                <a class="btn btn-primary" href="<?= e(app_url('upload.php')) ?>">Upload files</a>
            </section>
        </div>
    </div>
</main>
</body>
</html>
