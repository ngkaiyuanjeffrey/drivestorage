<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }

    return $user;
}

function require_admin(): array
{
    $user = require_auth();
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }

    return $user;
}

function authenticate_user(string $identifier, string $password): ?array
{
    $stmt = database_connection()->prepare(
        'SELECT id, name, username, email, password_hash, role, is_active, quota_bytes
         FROM users WHERE username = ? OR email = ? LIMIT 1'
    );
    $identifier = strtolower(trim($identifier));
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !(bool) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    unset($user['password_hash']);
    return $user;
}

function sign_in(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
}

function sign_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
