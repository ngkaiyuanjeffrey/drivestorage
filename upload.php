<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$user = require_auth();
$errors = [];
$uploadedCount = 0;

function upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file exceeds the server upload limit.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was selected.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded file.',
        UPLOAD_ERR_EXTENSION => 'The upload was stopped by a server extension.',
        default => 'The file could not be uploaded.',
    };
}

function normalized_uploads(array $files): array
{
    $normalized = [];
    $names = $files['name'] ?? [];
    $tmpNames = $files['tmp_name'] ?? [];
    $errors = $files['error'] ?? [];
    $sizes = $files['size'] ?? [];

    foreach ((array) $names as $index => $name) {
        $normalized[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($tmpNames[$index] ?? ''),
            'error' => (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($sizes[$index] ?? 0),
        ];
    }

    return $normalized;
}

function allowed_upload_mime_types(): array
{
    return [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'txt' => ['text/plain'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'mp3' => ['audio/mpeg'],
        'mp4' => ['video/mp4'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];
}

if (request_is_post()) {
    verify_csrf_or_fail();
    $uploads = normalized_uploads($_FILES['files'] ?? []);

    if ($uploads === []) {
        $errors[] = 'Select at least one file to upload.';
    }

    $validUploads = [];
    $incomingBytes = 0;
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($uploads as $upload) {
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $upload['name'] . ': ' . upload_error_message($upload['error']);
            continue;
        }

        $originalName = basename($upload['name']);
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($originalName === '' || $originalName === '.' || $originalName === '..') {
            $errors[] = 'A file has an invalid name.';
            continue;
        }
        if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
            $errors[] = $originalName . ': this file type is not allowed.';
            continue;
        }
        if ($upload['size'] < 1 || $upload['size'] > MAX_UPLOAD_BYTES) {
            $errors[] = $originalName . ': the file must be between 1 byte and ' . number_format(MAX_UPLOAD_BYTES / 1048576) . ' MB.';
            continue;
        }
        if (!is_uploaded_file($upload['tmp_name'])) {
            $errors[] = $originalName . ': the upload could not be verified.';
            continue;
        }

        $mimeType = $finfo->file($upload['tmp_name']);
        if ($mimeType === false) {
            $errors[] = $originalName . ': the file type could not be detected.';
            continue;
        }
        $allowedMimeTypes = allowed_upload_mime_types();
        if (!in_array($mimeType, $allowedMimeTypes[$extension] ?? [], true)) {
            $errors[] = $originalName . ': the file contents do not match its extension.';
            continue;
        }

        $validUploads[] = [
            'original_name' => $originalName,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $upload['size'],
            'tmp_name' => $upload['tmp_name'],
        ];
        $incomingBytes += $upload['size'];
    }

    if ($errors === [] && $validUploads !== []) {
        $pdo = database_connection();
        $usageStmt = $pdo->prepare(
            'SELECT u.quota_bytes, COALESCE(SUM(f.size_bytes), 0) AS used_bytes
             FROM users u
             LEFT JOIN files f ON f.user_id = u.id AND f.is_deleted = 0
             WHERE u.id = ?
             GROUP BY u.id, u.quota_bytes'
        );
        $usageStmt->execute([(int) $user['id']]);
        $usage = $usageStmt->fetch();
        $quotaBytes = (int) ($usage['quota_bytes'] ?? $user['quota_bytes']);
        $usedBytes = (int) ($usage['used_bytes'] ?? 0);

        if ($usedBytes + $incomingBytes > $quotaBytes) {
            $errors[] = 'The selected files exceed your available storage quota.';
        }
    }

    if ($errors === [] && $validUploads !== []) {
        $createdPaths = [];
        try {
            if (!is_dir(STORAGE_ROOT) && !mkdir(STORAGE_ROOT, 0750, true) && !is_dir(STORAGE_ROOT)) {
                throw new RuntimeException('The storage directory could not be created.');
            }

            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                'INSERT INTO files
                    (user_id, original_name, stored_name, physical_path, mime_type, extension, size_bytes)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $log = $pdo->prepare(
                'INSERT INTO activity_logs
                    (user_id, action, resource_type, resource_id, description, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($validUploads as $file) {
                $storedName = bin2hex(random_bytes(16)) . '.' . $file['extension'];
                $physicalPath = STORAGE_ROOT . DIRECTORY_SEPARATOR . $storedName;
                if (!move_uploaded_file($file['tmp_name'], $physicalPath)) {
                    throw new RuntimeException('The server could not move ' . $file['original_name'] . ' into storage.');
                }
                $createdPaths[] = $physicalPath;

                $insert->execute([
                    (int) $user['id'],
                    $file['original_name'],
                    $storedName,
                    $physicalPath,
                    $file['mime_type'],
                    $file['extension'],
                    $file['size'],
                ]);
                $fileId = (int) $pdo->lastInsertId();
                $log->execute([
                    (int) $user['id'],
                    'upload',
                    'file',
                    $fileId,
                    'Uploaded ' . $file['original_name'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
                $uploadedCount++;
            }

            $pdo->commit();
            flash('success', $uploadedCount . ' file' . ($uploadedCount === 1 ? '' : 's') . ' uploaded successfully.');
            redirect('dashboard.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($createdPaths as $createdPath) {
                if (is_file($createdPath)) {
                    unlink($createdPath);
                }
            }
            log_error($exception);
            $errors[] = 'The upload could not be completed. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload files - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(app_url('dashboard.php')) ?>">Nimbus Vault</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(app_url('dashboard.php')) ?>">Back to dashboard</a>
    </div>
</nav>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <section class="dashboard-card">
                <p class="eyebrow mb-1">File management</p>
                <h1 class="h2 mb-4">Upload files</h1>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endforeach; ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="files">Choose files</label>
                        <input class="form-control" type="file" id="files" name="files[]" multiple required>
                        <div class="form-text">
                            Allowed: <?= e(implode(', ', ALLOWED_EXTENSIONS)) ?>.
                            Maximum <?= e(number_format(MAX_UPLOAD_BYTES / 1048576)) ?> MB per file.
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Upload files</button>
                </form>
            </section>
        </div>
    </div>
</main>
</body>
</html>
