<?php
/* Called by the deploy pipeline after each upload:
     curl -X POST -H "X-Migrate-Token: <MIGRATE_TOKEN>" https://golocalwheels.com/migrate.php
   1) takes a backup of the database, 2) runs new files from database/migrations.
   Without the right token it does nothing. */
define('GLW', 1);
require (require __DIR__ . '/_app_path.php') . '/lib/db.php';
require GLW_APP . '/lib/migrator.php';
require GLW_APP . '/lib/backup.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-LiteSpeed-Cache-Control: no-cache');

$token = $_SERVER['HTTP_X_MIGRATE_TOKEN'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || strlen(MIGRATE_TOKEN) < 24 || !hash_equals(MIGRATE_TOKEN, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}
try {
    $db = db_admin();
    $pending = pending_migrations($db);
    $backup = $pending ? basename(make_backup($db, 'before-migration')) : null;   // backup before every schema change
    $applied = run_migrations($db);
    echo json_encode(['ok' => true, 'env' => APP_ENV, 'backup' => $backup, 'applied' => $applied]);
} catch (Throwable $e) {
    error_log('GLW migrate: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Migration failed – see the server error log.']);
}
