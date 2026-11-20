<?php
declare(strict_types=1);

function find_owned_file(int $fileId, int $userId): ?array
{
    $stmt = database_connection()->prepare(
        'SELECT id, original_name, stored_name, physical_path, mime_type, extension, size_bytes, created_at
         FROM files
         WHERE id = ? AND user_id = ? AND is_deleted = 0
         LIMIT 1'
    );
    $stmt->execute([$fileId, $userId]);
    $file = $stmt->fetch();

    return $file ?: null;
}

function file_storage_path(array $file): string
{
    $storedName = basename((string) $file['stored_name']);
    $path = STORAGE_ROOT . DIRECTORY_SEPARATOR . $storedName;
    $root = realpath(STORAGE_ROOT);
    $realPath = realpath($path);

    if ($root === false || $realPath === false || !is_file($realPath)) {
        throw new RuntimeException('The requested file is not available.');
    }

    $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncmp($realPath, $rootPrefix, strlen($rootPrefix)) !== 0) {
        throw new RuntimeException('The requested file is outside the storage directory.');
    }

    return $realPath;
}

function file_download_name(array $file): string
{
    $name = basename((string) $file['original_name']);
    return $name === '' || $name === '.' || $name === '..' ? 'download' : $name;
}

function log_file_activity(array $file, array $user, string $action): void
{
    $stmt = database_connection()->prepare(
        'INSERT INTO activity_logs
            (user_id, action, resource_type, resource_id, description, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int) $user['id'],
        $action,
        'file',
        (int) $file['id'],
        ucfirst($action) . ' ' . file_download_name($file),
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
