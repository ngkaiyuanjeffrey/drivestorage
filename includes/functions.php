<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

function request_is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function log_error(Throwable $exception): void
{
    $line = sprintf("[%s] %s\n", gmdate('c'), $exception->getMessage());
    error_log($line, 3, LOG_ROOT . DIRECTORY_SEPARATOR . 'application.log');
}

function old_input(string $key): string
{
    return e((string) ($_SESSION['old_input'][$key] ?? ''));
}

function remember_input(array $input): void
{
    $_SESSION['old_input'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['old_input']);
}
