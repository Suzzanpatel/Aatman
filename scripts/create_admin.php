<?php
/**
 * Create (or promote) an administrator account from the command line.
 * Admins can never be created through the public sign-up form.
 *
 *   php scripts/create_admin.php "Full Name" admin@example.org
 *
 * You will be prompted for the password (min. 8 characters).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

[$script, $name, $email] = array_pad($argv, 3, null);
if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php \"Full Name\" admin@example.org\n");
    exit(1);
}
$email = mb_strtolower($email);

$password = getenv('AATMAN_ADMIN_PASSWORD') ?: null;   // for automated setups
if ($password === null) {
    fwrite(STDOUT, 'Password: ');
    if (DIRECTORY_SEPARATOR === '/') { system('stty -echo'); }
    $password = rtrim((string) fgets(STDIN), "\r\n");
    if (DIRECTORY_SEPARATOR === '/') { system('stty echo'); }
    fwrite(STDOUT, PHP_EOL);
}
if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
    fwrite(STDERR, 'Password must be at least ' . PASSWORD_MIN_LENGTH . " characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$existing = db_one('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    db_exec("UPDATE users SET name = ?, password = ?, role = 'admin', status = 'active' WHERE id = ?",
        [$name, $hash, (int) $existing['id']]);
    echo "Updated existing user #{$existing['id']} and granted the admin role.\n";
} else {
    db_exec("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')",
        [$name, $email, $hash]);
    echo 'Created admin user #' . db()->insert_id . ".\n";
}
