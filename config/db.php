<?php
ini_set('display_errors', '1');

// Default timezone (can be overridden via .env)
date_default_timezone_set('Asia/Kolkata');

// Attempt to load Composer autoloader for vlucas/phpdotenv if available
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Polyfills for PHP < 8 functions used in the fallback parser
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        if ($needle === '') { return true; }
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') { return true; }
        $needleLen = strlen($needle);
        return $needleLen === 0 ? true : substr($haystack, -$needleLen) === $needle;
    }
}

// Load environment variables from project root if Dotenv is available
$dotenvClass = 'Dotenv\\Dotenv';
if (class_exists($dotenvClass)) {
    $dotenv = call_user_func([$dotenvClass, 'createImmutable'], dirname(__DIR__));
    if (method_exists($dotenv, 'safeLoad')) {
        $dotenv->safeLoad();
    } elseif (method_exists($dotenv, 'load')) {
        $dotenv->load();
    }
} else {
    // Lightweight fallback: load key=value pairs from .env if present (no external deps)
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile) && is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) { continue; }
            // Simple KEY=VALUE parser (no quoted values/expansion)
            $pos = strpos($line, '=');
            if ($pos !== false) {
                $key = trim(substr($line, 0, $pos));
                $val = trim(substr($line, $pos + 1));
                // Strip optional surrounding quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
                putenv($key . '=' . $val);
            }
        }
    }
}

// Read DB settings from env with safe defaults
$host    = $_ENV['DB_HOST'];
$dbUser  = $_ENV['DB_USERNAME'];
$dbpass  = $_ENV['DB_PASSWORD'];
$db      = $_ENV['DB_DATABASE'];
$port    = (int)($_ENV['DB_PORT'] ?? 3306);
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

// Allow overriding timezone from env
if (!empty($_ENV['APP_TIMEZONE'])) {
    @date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

// Establish MySQL connection
$conn = mysqli_connect($host, $dbUser, $dbpass, $db, $port);

if ($conn) {
    // Set connection charset if supported
    @mysqli_set_charset($conn, $charset);
    // echo "Connection Successfully";
} else {
    // echo "Database connection error";
}

function debug($str){
    echo "<pre>";
    print_r($str);
    echo "</pre>";
}
mysqli_set_charset($conn, 'utf8mb4');
?>
