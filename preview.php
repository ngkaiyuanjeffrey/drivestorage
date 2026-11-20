<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/file_helpers.php';

$user = require_auth();
$fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($fileId === false || $fileId === null || $fileId < 1) {
    http_response_code(400);
    exit('A valid file is required.');
}

$file = find_owned_file((int) $fileId, (int) $user['id']);
if ($file === null) {
    http_response_code(404);
    exit('File not found.');
}

$previewableMimeTypes = [
    'application/pdf',
    'audio/mpeg',
    'image/gif',
    'image/jpeg',
    'image/png',
    'image/webp',
    'text/plain',
    'video/mp4',
];

try {
    $path = file_storage_path($file);
} catch (Throwable $exception) {
    log_error($exception);
    http_response_code(404);
    exit('File not found.');
}

if (in_array($file['mime_type'], $previewableMimeTypes, true)) {
    log_file_activity($file, $user, 'preview');
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Length: ' . (string) filesize($path));
    header('Content-Disposition: inline; filename="' . str_replace('"', '', file_download_name($file)) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<main class="container py-5">
    <section class="dashboard-card mx-auto" style="max-width: 640px">
        <p class="eyebrow mb-1">File preview</p>
        <h1 class="h2"><?= e(file_download_name($file)) ?></h1>
        <p class="text-secondary">This file type cannot be previewed in the browser.</p>
        <a class="btn btn-primary" href="<?= e(app_url('download.php?id=' . (int) $file['id'])) ?>">Download file</a>
        <a class="btn btn-outline-secondary" href="<?= e(app_url('files.php')) ?>">Back to files</a>
    </section>
</main>
</body>
</html>
