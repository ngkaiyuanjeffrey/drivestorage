<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$user = require_auth();
$stmt = database_connection()->prepare(
    'SELECT id, original_name, mime_type, size_bytes, created_at
     FROM files
     WHERE user_id = ? AND is_deleted = 0
     ORDER BY created_at DESC, id DESC'
);
$stmt->execute([(int) $user['id']]);
$files = $stmt->fetchAll();

function format_file_size(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 1) . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your files - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(app_url('dashboard.php')) ?>">Nimbus Vault</a>
        <div class="d-flex gap-2">
            <a class="btn btn-primary btn-sm" href="<?= e(app_url('upload.php')) ?>">Upload files</a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(app_url('dashboard.php')) ?>">Dashboard</a>
        </div>
    </div>
</nav>
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="eyebrow mb-1">File management</p>
            <h1 class="h2 mb-0">Your files</h1>
        </div>
        <span class="text-secondary"><?= e(number_format(count($files))) ?> file<?= count($files) === 1 ? '' : 's' ?></span>
    </div>
    <section class="dashboard-card">
        <?php if ($files === []): ?>
            <p class="text-secondary mb-0">No files uploaded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Size</th>
                            <th scope="col">Uploaded</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?= e($file['original_name']) ?></td>
                            <td><?= e($file['mime_type']) ?></td>
                            <td><?= e(format_file_size((int) $file['size_bytes'])) ?></td>
                            <td><?= e((string) $file['created_at']) ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('preview.php?id=' . (int) $file['id'])) ?>">View</a>
                                <a class="btn btn-sm btn-primary" href="<?= e(app_url('download.php?id=' . (int) $file['id'])) ?>">Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
