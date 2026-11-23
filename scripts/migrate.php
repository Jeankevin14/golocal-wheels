<?php
/* Run new database migrations.   Usage: php scripts/migrate.php */
require __DIR__ . '/_cli.php';
require GLW_APP . '/lib/migrator.php';
$applied = run_migrations(db_admin(), dirname(__DIR__) . '/database/migrations');
say($applied ? 'Applied: ' . implode(', ', $applied) : 'Nothing to migrate – database is up to date.');
