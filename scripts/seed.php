<?php
/* Load sample data (database/seeds). LOCAL and STAGING only.   Usage: php scripts/seed.php */
require __DIR__ . '/_cli.php';
require GLW_APP . '/lib/migrator.php';
if (APP_ENV === 'production') fail('Seeds are not allowed on production (APP_ENV=production).');
$db = db_admin();
foreach (glob(dirname(__DIR__) . '/database/seeds/*.sql') ?: [] as $file) {
    foreach (sql_statements(file_get_contents($file)) as $stmt) $db->exec($stmt);
    say('Seeded: ' . basename($file));
}
