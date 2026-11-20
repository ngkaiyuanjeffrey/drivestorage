<?php
declare(strict_types=1);

const APP_NAME = 'Nimbus Vault';
const APP_VERSION = '0.1.0';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
if ($basePath === '/') {
    $basePath = '';
}

define('BASE_PATH', $basePath);
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'objects');
define('LOG_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs');
define('INSTALL_LOCK', APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.installed');

const MAX_UPLOAD_BYTES = 104857600;
const DEFAULT_USER_QUOTA_BYTES = 10737418240;
const ALLOWED_EXTENSIONS = [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp3', 'mp4', 'zip'
];

date_default_timezone_set('UTC');

function app_url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}
