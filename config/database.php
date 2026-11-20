<?php
declare(strict_types=1);

function database_config(): array
{
    return [
        'host' => getenv('NIMBUS_DB_HOST') ?: 'localhost',
        'port' => getenv('NIMBUS_DB_PORT') ?: '3306',
        'database' => getenv('NIMBUS_DB_NAME') ?: 'drivestorage',
        'username' => getenv('NIMBUS_DB_USER') ?: 'root',
        'password' => getenv('NIMBUS_DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ];
}

function database_connection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    function ensure_database_exists(): void
    {
        $config = database_config();
        $database = (string) $config['database'];
        if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException('The configured database name is invalid.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['charset']
        );
        $server = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $server->exec(
            'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $database) . '` ' .
            'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    $config = database_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ensure_users_username_column($pdo);

    return $pdo;
}

function ensure_users_username_column(PDO $pdo): void
{
    $table = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($table === false) {
        return;
    }

    $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'")->fetch();
    if ($column !== false) {
        return;
    }

    $pdo->exec('ALTER TABLE users ADD COLUMN username VARCHAR(80) NULL UNIQUE AFTER name');
}
