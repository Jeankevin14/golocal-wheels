<?php
/* Daily database backup – run by a DirectAdmin cron job:
     php /home/<user>/domains/golocalwheels.com/app/scripts/backup.php
   Keeps 14 days of backups in app/backups/. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('GLW', 1);
require dirname(__DIR__) . '/lib/db.php';
require GLW_APP . '/lib/backup.php';
$file = make_backup(db_admin(), 'daily', 14);
echo 'Backup written: ' . basename($file) . PHP_EOL;
