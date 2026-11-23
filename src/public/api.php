<?php
/* GoLocal Wheels – data for the website (shops, bookings, new shop requests) */
define('GLW', 1);
require (require __DIR__ . '/_app_path.php') . '/lib/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function out($data, int $code = 200): void { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $code = 400): void { out(['ok' => false, 'error' => $msg], $code); }
function body(): array {
    $raw = file_get_contents('php://input');
    if (strlen($raw) > 20000) fail('Request too large.');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}
function str($v, int $max): string { return mb_substr(trim((string)$v), 0, $max); }

if (!config_ready()) fail('Website setup is not finished.', 503);

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = db();

    /* ---------- list approved shops with their bikes ---------- */
    if ($action === 'shops' && $method === 'GET') {
        $shops = $db->query("SELECT id, city, name, area, lat, lng, phone, whatsapp, open_time, close_time, rating, is_sample
                             FROM shops WHERE status='approved' ORDER BY id")->fetchAll();
        $bikes = $db->query("SELECT b.id, b.shop_id, b.type, b.model, b.price_day, b.price_hour, b.deposit
                             FROM bikes b JOIN shops s ON s.id=b.shop_id WHERE s.status='approved' ORDER BY b.price_day")->fetchAll();
        $by = [];
        foreach ($bikes as $b) $by[$b['shop_id']][] = ['id' => (int)$b['id'], 'type' => $b['type'], 'model' => $b['model'],
            'day' => (int)$b['price_day'], 'hr' => (int)$b['price_hour'], 'deposit' => (int)$b['deposit']];
        $res = []; $anySample = false;
        foreach ($shops as $s) {
            if (empty($by[$s['id']])) continue;
            $anySample = $anySample || $s['is_sample'];
            $res[] = ['id' => (int)$s['id'], 'city' => $s['city'], 'name' => $s['name'], 'area' => $s['area'],
                'lat' => (float)$s['lat'], 'lng' => (float)$s['lng'], 'phone' => $s['phone'], 'whatsapp' => $s['whatsapp'],
                'open' => $s['open_time'], 'close' => $s['close_time'], 'rating' => $s['rating'] === null ? 0 : (float)$s['rating'],
                'sample' => (bool)$s['is_sample'], 'bikes' => $by[$s['id']]];
        }
        out(['ok' => true, 'sample' => $anySample, 'shops' => $res]);
    }

    /* ---------- create a booking ---------- */
    if ($action === 'book' && $method === 'POST') {
        $in = body();
        if (!empty($in['website'])) fail('Rejected.');                       // hidden spam trap
        $bikeId = (int)($in['bike_id'] ?? 0);
        $name   = str($in['name'] ?? '', 80);
        $phone  = digits(str($in['phone'] ?? '', 20));
        $date   = str($in['date'] ?? '', 10);
        $days   = (int)($in['days'] ?? 0);
        $helmet = !empty($in['helmet']) ? 1 : 0;

        if (mb_strlen($name) < 2) fail('Please enter your name.');
        if (strlen($phone) < 10 || strlen($phone) > 13) fail('Please enter a valid mobile number.');
        $d = DateTime::createFromFormat('Y-m-d', $date);
        $today = new DateTime('today');
        if (!$d || $d->format('Y-m-d') !== $date || $d < $today || $d > (clone $today)->modify('+90 days')) fail('Please choose a pickup date within the next 90 days.');
        if ($days < 1 || $days > 30) fail('Number of days must be 1 to 30.');

        $q = $db->prepare("SELECT COUNT(*) FROM bookings WHERE ip=? AND created_at > NOW() - INTERVAL 1 HOUR");
        $q->execute([client_ip()]);
        if ((int)$q->fetchColumn() >= MAX_BOOKINGS_PER_HOUR) fail('Too many bookings from this device. Please try again later.', 429);

        $q = $db->prepare("SELECT b.*, s.name AS shop_name, s.area, s.phone AS shop_phone, s.whatsapp AS shop_wa
                           FROM bikes b JOIN shops s ON s.id=b.shop_id WHERE b.id=? AND s.status='approved'");
        $q->execute([$bikeId]);
        $bike = $q->fetch();
        if (!$bike) fail('This bike is no longer available.');

        $total = $bike['price_day'] * $days + ($helmet ? 50 * $days : 0);   // price always taken from the database
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code = 'GL' . random_int(100000, 999999);
            $c = $db->prepare('SELECT 1 FROM bookings WHERE code=?'); $c->execute([$code]);
            if (!$c->fetch()) break;
        }
        $db->prepare("INSERT INTO bookings (code, shop_id, bike_id, bike_model, customer_name, customer_phone, pickup_date, days, helmet, price_day, total, ip)
                      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([$code, $bike['shop_id'], $bike['id'], $bike['model'], $name, $phone, $date, $days, $helmet, $bike['price_day'], $total, client_ip()]);

        send_alert("New booking $code – {$bike['shop_name']}",
            "New booking on " . SITE_NAME . "\n\nBooking ID: $code\nShop: {$bike['shop_name']} ({$bike['area']})\nShop phone: {$bike['shop_phone']}\n" .
            "Bike: {$bike['model']}\nPickup: $date for $days day(s)\nExtra helmet: " . ($helmet ? 'Yes' : 'No') . "\nTotal: Rs. $total\n\n" .
            "Customer: $name\nCustomer phone: $phone\n\nOpen the admin panel: " . SITE_URL . "/admin.php");

        out(['ok' => true, 'booking' => ['code' => $code, 'shop' => $bike['shop_name'], 'bike' => $bike['model'],
            'date' => $date, 'days' => $days, 'total' => $total, 'status' => 'new']]);
    }

    /* ---------- check status of my bookings ---------- */
    if ($action === 'status' && $method === 'POST') {
        $in = body();
        $codes = array_slice(array_filter((array)($in['codes'] ?? []), fn($c) => is_string($c) && preg_match('/^GL\d{6}$/', $c)), 0, 20);
        if (!$codes) out(['ok' => true, 'bookings' => []]);
        $ph = implode(',', array_fill(0, count($codes), '?'));
        $q = $db->prepare("SELECT b.code, b.bike_model AS bike, b.pickup_date AS date, b.days, b.total, b.status, s.name AS shop
                           FROM bookings b JOIN shops s ON s.id=b.shop_id WHERE b.code IN ($ph) ORDER BY b.created_at DESC");
        $q->execute(array_values($codes));
        out(['ok' => true, 'bookings' => $q->fetchAll()]);
    }

    /* ---------- a shop owner asks to be listed ---------- */
    if ($action === 'register' && $method === 'POST') {
        $in = body();
        if (!empty($in['website'])) fail('Rejected.');
        $city = $in['city'] ?? '';
        $name = str($in['name'] ?? '', 120);
        $area = str($in['area'] ?? '', 120);
        $phone = str($in['phone'] ?? '', 20);
        $wa = str($in['whatsapp'] ?? '', 20);
        $lat = (float)($in['lat'] ?? 0); $lng = (float)($in['lng'] ?? 0);
        $open = str($in['open'] ?? '08:00', 5); $close = str($in['close'] ?? '20:00', 5);
        $type = $in['type'] ?? ''; $model = str($in['model'] ?? '', 80);
        $day = (int)($in['day'] ?? 0); $hr = (int)($in['hr'] ?? 0);

        if (!isset(CITY_KEYS[$city])) fail('Please choose a city.');
        if (mb_strlen($name) < 2 || mb_strlen($area) < 2) fail('Please enter the shop name and area.');
        if (strlen(digits($phone)) < 10) fail('Please enter a valid phone number.');
        if ($lat < 6 || $lat > 37 || $lng < 68 || $lng > 98) fail('Please enter a location inside India (latitude, longitude).');
        if (!preg_match('/^\d{2}:\d{2}$/', $open) || !preg_match('/^\d{2}:\d{2}$/', $close)) fail('Please enter opening times.');
        if (!in_array($type, BIKE_TYPES, true) || mb_strlen($model) < 2 || $day < 50 || $day > 20000 || $hr < 0 || $hr > 5000) fail('Please check the bike details.');

        $q = $db->prepare("SELECT COUNT(*) FROM shops WHERE status='pending' AND created_at > NOW() - INTERVAL 1 DAY");
        $q->execute();
        if ((int)$q->fetchColumn() > 50) fail('Too many requests today. Please try again tomorrow.', 429);

        $db->beginTransaction();
        $db->prepare("INSERT INTO shops (city,name,area,lat,lng,phone,whatsapp,open_time,close_time,status) VALUES (?,?,?,?,?,?,?,?,?,'pending')")
           ->execute([$city, $name, $area, $lat, $lng, $phone, $wa, $open, $close]);
        $sid = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (?,?,?,?,?)')->execute([$sid, $type, $model, $day, $hr]);
        $db->commit();

        send_alert("New shop request – $name", "A shop asked to be listed on " . SITE_NAME . ":\n\n$name\n$area, " . CITY_KEYS[$city] .
            "\nPhone: $phone\n\nApprove it in the admin panel: " . SITE_URL . "/admin.php");
        out(['ok' => true]);
    }

    fail('Unknown request.', 404);
} catch (Throwable $e) {
    error_log('GLW api: ' . $e->getMessage());
    fail('Server error. Please try again.', 500);
}
