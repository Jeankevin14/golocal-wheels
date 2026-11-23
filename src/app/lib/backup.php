<?php
/* Database backup written in plain PHP (works on shared hosting without mysqldump).
   Files go to app/backups/ (outside the website folder) as .sql.gz and are kept KEEP_DAYS days. */
defined('GLW') or exit;

function backups_dir(): string { return GLW_APP . '/backups'; }

/** @return string path of the backup file */
function make_backup(PDO $db, string $label = 'daily', int $keepDays = 14): string {
    $dir = backups_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) throw new RuntimeException("Cannot create $dir");
    @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    $file = sprintf('%s/%s-%s-%s.sql.gz', $dir, DB_NAME, date('Ymd-His'), preg_replace('/[^a-z0-9-]/i', '', $label));
    $gz = gzopen($file, 'wb6');
    if (!$gz) throw new RuntimeException("Cannot write $file");
    gzwrite($gz, "-- GoLocal Wheels backup of " . DB_NAME . " at " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    foreach ($db->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM) as [$table]) {
        $create = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
        gzwrite($gz, "DROP TABLE IF EXISTS `$table`;\n$create;\n\n");
        $rows = $db->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), array_values($row));
            gzwrite($gz, "INSERT INTO `$table` (`" . implode('`,`', array_keys($row)) . "`) VALUES (" . implode(',', $vals) . ");\n");
        }
        gzwrite($gz, "\n");
    }
    gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
    gzclose($gz);
    @chmod($file, 0600);
    // remove old backups
    foreach (glob($dir . '/*.sql.gz') ?: [] as $old) {
        if (filemtime($old) < time() - $keepDays * 86400) @unlink($old);
    }
    return $file;
}
