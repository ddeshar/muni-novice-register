<?php
// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        
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

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_USER', getenv('DB_USER') ?: 'reguser');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'regpass');
define('DB_NAME', getenv('DB_NAME') ?: 'registration');
