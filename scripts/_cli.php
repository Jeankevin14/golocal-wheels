<?php
/* Shared start for the command-line scripts in scripts/ */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('GLW', 1);
require dirname(__DIR__) . '/src/app/lib/db.php';
function say(string $s): void { fwrite(STDOUT, $s . PHP_EOL); }
function fail(string $s): void { fwrite(STDERR, "ERROR: $s" . PHP_EOL); exit(1); }
