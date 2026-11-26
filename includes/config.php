<?php
/**
 * Application configuration.
 *
 * Values are read from environment variables, or from a `.env` file in the
 * project root (see `.env.example`). Nothing secret is ever hard-coded here.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('INCLUDES_PATH', __DIR__);

/**
 * Minimal .env loader: KEY=VALUE per line, `#` comments, optional quotes.
 * Real environment variables always win over the file.
 */
function load_env_file(string $file): void
{
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

load_env_file(ROOT_PATH . '/.env');

define('APP_ENV', env('APP_ENV', 'production'));       // 'development' shows errors
define('APP_DEBUG', APP_ENV === 'development');

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', (int) env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'aatman'));
define('DB_USER', env('DB_USER', 'aatman_app'));
define('DB_PASS', env('DB_PASS', ''));

define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));
define('GEMINI_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash'));

// Security tunables
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS_PER_EMAIL', 5);   // failed attempts ...
define('LOGIN_MAX_ATTEMPTS_PER_IP', 20);
define('LOGIN_LOCKOUT_MINUTES', 15);         // ... within this window
define('CHAT_MAX_MESSAGES', 20);             // chatbot messages per session ...
define('CHAT_WINDOW_SECONDS', 600);          // ... per 10 minutes
define('CHAT_MAX_LENGTH', 500);              // characters per message
