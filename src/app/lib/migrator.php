<?php
/* Runs the SQL files in database/migrations that have not run yet.
   Each file runs once; its name is recorded in the table schema_migrations. */
defined('GLW') or exit;

/** Split a .sql file into statements (our files use one statement per ';' at line end). */
function sql_statements(string $sql): array {
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $parts = preg_split('/;\s*(\r?\n|$)/', $sql);
    return array_values(array_filter(array_map('trim', $parts), fn($s) => $s !== ''));
}

/** Folder that holds the migration files: app/migrations on the server, database/migrations in the repo. */
function migrations_dir(): string {
    $server = GLW_APP . '/migrations';
    return is_dir($server) ? $server : dirname(GLW_APP, 2) . '/database/migrations';
}

/** @return string[] names of the migrations that were applied now */
function run_migrations(PDO $db, ?string $dir = null): array {
    $dir = $dir ?? migrations_dir();
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(150) NOT NULL PRIMARY KEY,
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $done = $db->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files = glob(rtrim($dir, '/') . '/*.sql') ?: [];
    sort($files, SORT_STRING);
    $applied = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $done, true)) continue;
        foreach (sql_statements(file_get_contents($file)) as $stmt) $db->exec($stmt);
        $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$name]);
        $applied[] = $name;
    }
    return $applied;
}

/** @return string[] migrations not applied yet */
function pending_migrations(PDO $db, ?string $dir = null): array {
    $dir = $dir ?? migrations_dir();
    try { $done = $db->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN); }
    catch (Throwable $e) { $done = []; }
    $files = array_map('basename', glob(rtrim($dir, '/') . '/*.sql') ?: []);
    sort($files, SORT_STRING);
    return array_values(array_diff($files, $done));
}
