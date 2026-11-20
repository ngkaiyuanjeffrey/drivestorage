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

try {
    $path = file_storage_path($file);
} catch (Throwable $exception) {
    log_error($exception);
    http_response_code(404);
    exit('File not found.');
}

log_file_activity($file, $user, 'download');
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', file_download_name($file)) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
