<?php
/* =========================================================
   GoLocal Wheels – settings
   Values come from environment variables, or from the file
   app/.env on the server (never committed to Git).
   See .env.example in the repository for the names.
   ========================================================= */
defined('GLW') or exit;

(function (): void {
    $file = dirname(__DIR__) . '/.env';
    if (!is_file($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if (strlen($v) > 1 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) $v = substr($v, 1, -1);
        if (getenv($k) === false) $_ENV[$k] = $v;   // real environment variables win
    }
})();

function env(string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v !== false) return $v;
    return $_ENV[$key] ?? $default;
}

define('APP_ENV', env('APP_ENV', 'production'));     // local | staging | production

// Database used by the website (limited user: SELECT, INSERT, UPDATE, DELETE)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));

// Database user for migrations (may CREATE/ALTER). Falls back to DB_USER.
define('DB_MIGRATE_USER', env('DB_MIGRATE_USER', '') ?: DB_USER);
define('DB_MIGRATE_PASS', env('DB_MIGRATE_USER', '') ? env('DB_MIGRATE_PASS', '') : DB_PASS);

// Secret that lets the deploy pipeline run migrations (long random string)
define('MIGRATE_TOKEN', env('MIGRATE_TOKEN', ''));

define('ADMIN_EMAIL', env('ADMIN_EMAIL', ''));
define('FROM_EMAIL', env('FROM_EMAIL', 'hello@golocalwheels.com'));
define('SITE_NAME', env('SITE_NAME', 'GoLocal Wheels'));
define('SITE_URL', rtrim(env('SITE_URL', 'https://golocalwheels.com'), '/'));

define('MAX_BOOKINGS_PER_HOUR', (int)env('MAX_BOOKINGS_PER_HOUR', '5'));
