<?php
/* Shared helpers – loaded by the pages in public/, never opened directly */
defined('GLW') or exit;
define('GLW_APP', dirname(__DIR__));
require GLW_APP . '/config/config.php';

date_default_timezone_set('Asia/Kolkata');

function db_connect(string $user, string $pass): PDO {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+05:30'");
    return $pdo;
}

/* connection used by the website */
function db(): PDO {
    static $pdo = null;
    return $pdo ??= db_connect(DB_USER, DB_PASS);
}

/* connection used only for migrations and backups */
function db_admin(): PDO {
    static $pdo = null;
    return $pdo ??= db_connect(DB_MIGRATE_USER, DB_MIGRATE_PASS);
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

const BIKE_TYPES = ['Scooter', 'Geared', 'Royal Enfield', 'Electric', 'Cycle'];
const CITY_KEYS  = ['pondy' => 'Puducherry', 'chennai' => 'Chennai', 'blr' => 'Bengaluru', 'goa' => 'North Goa'];
const BOOKING_STATUSES = ['new' => 'New', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

function client_ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); }

function send_alert(string $subject, string $body): void {
    if (!filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) return;
    $headers = 'From: ' . SITE_NAME . ' <' . FROM_EMAIL . ">\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail(ADMIN_EMAIL, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

function digits(string $s): string { return preg_replace('/\D/', '', $s); }

function wa_link(string $phone, string $text): string {
    $d = digits($phone);
    if (strlen($d) === 10) $d = '91' . $d;
    return 'https://wa.me/' . $d . '?text=' . rawurlencode($text);
}

function config_ready(): bool {
    return DB_NAME !== '' && DB_USER !== '';
}
