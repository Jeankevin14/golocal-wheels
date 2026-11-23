<?php
/* Create an admin login.   Usage: php scripts/create-admin.php <username> [password]
   (leave out the password to type it hidden) */
require __DIR__ . '/_cli.php';
$u = $argv[1] ?? '';
if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $u)) fail('Usage: php scripts/create-admin.php <username> [password]');
$p = $argv[2] ?? null;
if ($p === null) { fwrite(STDOUT, 'Password (min 10 characters): '); @system('stty -echo'); $p = trim((string)fgets(STDIN)); @system('stty echo'); fwrite(STDOUT, PHP_EOL); }
if (strlen($p) < 10) fail('Password must be at least 10 characters.');
db_admin()->prepare('INSERT INTO admins (username, pass_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE pass_hash=VALUES(pass_hash)')
    ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
say("Admin '$u' is ready.");
