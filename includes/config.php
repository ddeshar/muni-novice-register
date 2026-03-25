<?php
// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!empty($name)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function env_value($key, $default = '')
{
    $value = getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
}

// Load .env from src directory
$envPath = __DIR__ . '/../.env';
$envLoaded = false;

if (file_exists($envPath)) {
    loadEnv($envPath);
    $envLoaded = true;
}

if (!$envLoaded) {
    error_log('Warning: .env file not found');
}

// Determine environment
define('ENVIRONMENT', env_value('ENVIRONMENT', 'production'));

// Set error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database configuration
define('DB_HOST', env_value('DB_HOST', 'localhost'));
define('DB_USER', env_value('DB_USER', 'reguser'));
define('DB_PASSWORD', env_value('DB_PASSWORD', ''));
define('DB_NAME', env_value('DB_NAME', 'registration'));
define('ADMIN_LOGIN_PATH', env_value('ADMIN_LOGIN_PATH', 'admin_access_7f3k.php'));

$configuredSecret = env_value('APP_SECRET', '');
if ($configuredSecret === '') {
    $configuredSecret = hash('sha256', __FILE__ . '|' . DB_HOST . '|' . DB_USER . '|' . DB_NAME);
    error_log('APP_SECRET is not set. Using derived fallback secret. Set APP_SECRET explicitly in production.');
}
define('APP_SECRET', $configuredSecret);

// Telegram notifications
define('TELEGRAM_BOT_TOKEN', env_value('TELEGRAM_BOT_TOKEN', ''));
define('TELEGRAM_CHAT_ID', env_value('TELEGRAM_CHAT_ID', ''));

// Security configuration
define('SESSION_LIFETIME', 7200); // 2 hours
// Upload configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png']);
define('UPLOAD_PATH', __DIR__ . '/../uploads');
