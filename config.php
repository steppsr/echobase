<?php
// config.php

// Load local config if exists
$envFile = __DIR__ . '/.env';
$envVars = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }

        // Skip if no = sign
        if (strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Handle quoted values if someone adds quotes (optional but nice)
        if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }

        $envVars[$key] = $value;
        putenv("$key=$value");       // legacy support
        $_ENV[$key] = $value;         // modern & reliable
    }
}

// Fallback defaults (for fresh clones)
define('DB_HOST',    $envVars['DB_HOST']    ?? getenv('DB_HOST')    ?? 'localhost');
define('DB_NAME',    $envVars['DB_NAME']    ?? getenv('DB_NAME')    ?: 'echobase');
define('DB_USER',    $envVars['DB_USER']    ?? getenv('DB_USER')    ?: 'root');
define('DB_PASS',    $envVars['DB_PASS']    ?? getenv('DB_PASS')    ?: '');
define('DB_CHARSET', $envVars['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4');
define('APP_LOGO',   $envVars['APP_LOGO']   ?? getenv('APP_LOGO')   ?: 'echobase.png');

// Other constants
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', '/echobase/uploads/');  // adjust if not in root

// PDO connection
function getDb() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    return new PDO($dsn, DB_USER, DB_PASS, $options);
}

// Ensure upload dir exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}