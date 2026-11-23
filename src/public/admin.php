<?php
/* GoLocal Wheels – ADMIN PANEL (golocalwheels.com/admin.php) */
define('GLW', 1);
require (require __DIR__ . '/_app_path.php') . '/lib/db.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-LiteSpeed-Cache-Control: no-cache');
// keep admin sessions in a private folder next to public_html (more reliable on shared hosting)
$sp = GLW_APP . '/sessions';
if (!is_dir($sp)) @mkdir($sp, 0700, true);
if (is_dir($sp) && is_writable($sp)) session_save_path($sp);

session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
session_name('glw_admin');
session_start();

if (!config_ready()) { exit('Setup is not finished. Create app/.env (see .env.example) and run the migrations first.'); }
$db = db();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { $_SESSION['flash'] = '!Your session expired. Please try again.'; header('Location: admin.php'); exit; } }
function go(string $q = '', string $flash = ''): void { if ($flash) $_SESSION['flash'] = $flash; header('Location: admin.php' . ($q ? '?' . $q : '')); exit; }

/* ---------------- login / logout ---------------- */
$view = $_GET['view'] ?? 'bookings';
if (($_GET['do'] ?? '') === 'logout') { $_SESSION = []; session_destroy(); go(); }

if (empty($_SESSION['admin_id'])) {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
        if ($_SESSION['tries'] > 8) { sleep(3); }
        $q = $db->prepare('SELECT id, pass_hash FROM admins WHERE username=?');
        $q->execute([trim($_POST['username'] ?? '')]);
        $a = $q->fetch();
        if ($a && password_verify($_POST['password'] ?? '', $a['pass_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$a['id']; $_SESSION['tries'] = 0;
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            go();
        }
        usleep(700000);
        $err = 'Wrong username or password.';
    }
    $flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
    if (!$err && $flash) $err = ltrim($flash, '!');
    page_head('Admin login'); ?>
    <div class="login"><h1><span class="go">GoLocal</span> <span class="wh">Wheels</span></h1><p class="muted">Admin login</p>
    <?php if ($err): ?><div class="flash bad"><?= h($err) ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>">
    <label>Username</label><input name="username" required autocomplete="username">
    <label>Password</label><input type="password" name="password" required autocomplete="current-password">
    <button class="btn">Log in</button></form></div>
    <?php page_foot(); exit;
}

/* ---------------- actions (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['act'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($act === 'booking_status' && isset(BOOKING_STATUSES[$_POST['status'] ?? ''])) {
        $db->prepare('UPDATE bookings SET status=?, admin_note=? WHERE id=?')->execute([$_POST['status'], mb_substr(trim($_POST['note'] ?? ''), 0, 255), $id]);
        go('view=bookings&f=' . urlencode($_GET['f'] ?? ''), 'Booking updated.');
    }
    if ($act === 'shop_status' && in_array($_POST['status'] ?? '', ['approved', 'hidden', 'pending'], true)) {
        if ($_POST['status'] === 'approved') {
            $q = $db->prepare('SELECT verified_at, is_sample FROM shops WHERE id=?'); $q->execute([$id]); $s = $q->fetch();
            if (!$s || (!$s['verified_at'] && !$s['is_sample'])) go('view=shop&id=' . $id . '#calls', '!Call the shop first, then write your comment below and tick “Approve”.');
        }
        $db->prepare('UPDATE shops SET status=? WHERE id=?')->execute([$_POST['status'], $id]);
        go('view=shops', 'Shop updated.');
    }
    if ($act === 'comment') {
        $note = mb_substr(trim($_POST['note'] ?? ''), 0, 2000);
        $approve = !empty($_POST['approve']);
        if (mb_strlen($note) < 3) go('view=shop&id=' . $id . '#calls', '!Please write a comment about your call.');
        if ($approve) {
            $q = $db->prepare('SELECT COUNT(*) FROM bikes WHERE shop_id=?'); $q->execute([$id]);
            if ((int)$q->fetchColumn() === 0) { $_SESSION['note_draft'] = $note; go('view=shop&id=' . $id . '#calls', '!Add at least one bike with its price (below) and click Save shop, then approve.'); }
        }
        $db->prepare('INSERT INTO shop_comments (shop_id, note, approved) VALUES (?,?,?)')->execute([$id, $note, $approve ? 1 : 0]);
        if ($approve) {
            $db->prepare("UPDATE shops SET status='approved', verified_at=NOW(), is_sample=0 WHERE id=?")->execute([$id]);
            go('view=shops&f=approved', 'Approved ✓ The shop is now on the website.');
        }
        go('view=shop&id=' . $id . '#calls', 'Comment saved.');
    }
    if ($act === 'import_research') {
        $file = GLW_APP . '/data/shops-research.json';
        $list = is_file($file) ? json_decode(file_get_contents($file), true) : null;
        if (!is_array($list)) go('view=shops', '!The file shops-research.json was not found.');
        $db->beginTransaction();
        $removed = $db->exec('DELETE FROM shops WHERE is_sample=1');
        $exists = $db->prepare('SELECT 1 FROM shops WHERE city=? AND name=?');
        $ins = $db->prepare("INSERT INTO shops (city,name,area,lat,lng,phone,notes,status) VALUES (?,?,?,?,?,?,?,'pending')");
        $added = 0;
        foreach ($list as $x) {
            if (!isset(CITY_KEYS[$x['city'] ?? ''])) continue;
            $exists->execute([$x['city'], $x['name']]);
            if ($exists->fetch()) continue;
            $ins->execute([$x['city'], mb_substr($x['name'], 0, 120), mb_substr($x['area'], 0, 120), (float)$x['lat'], (float)$x['lng'], mb_substr($x['phone'] ?? '', 0, 20), mb_substr($x['notes'] ?? '', 0, 2000)]);
            $added++;
        }
        $db->commit();
        go('view=shops&f=pending', "$added shops added to “To call”. $removed sample shops removed. None of them are on the website yet.");
    }
    if ($act === 'shop_delete') {
        $db->prepare('DELETE FROM shops WHERE id=?')->execute([$id]);
        go('view=shops', 'Shop deleted (with its bikes and bookings).');
    }
    if ($act === 'delete_samples') {
        $n = $db->exec('DELETE FROM shops WHERE is_sample=1');
        go('view=shops', "$n sample shops deleted.");
    }
    if ($act === 'shop_save') {
        $f = fn($k, $m) => mb_substr(trim($_POST[$k] ?? ''), 0, $m);
        $city = $_POST['city'] ?? ''; $err = '';
        $loc = preg_match('/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/', $_POST['loc'] ?? '', $m) ? [(float)$m[1], (float)$m[2]] : null;
        if (!isset(CITY_KEYS[$city])) $err = 'Choose a city.';
        elseif (mb_strlen($f('name', 120)) < 2) $err = 'Enter the shop name.';
        elseif (!$loc || $loc[0] < 6 || $loc[0] > 37 || $loc[1] < 68 || $loc[1] > 98) $err = 'Location must look like: 11.93452, 79.82851';
        elseif (!preg_match('/^\d{2}:\d{2}$/', $_POST['open'] ?? '') || !preg_match('/^\d{2}:\d{2}$/', $_POST['close'] ?? '')) $err = 'Enter opening and closing time.';
        $bikes = [];
        foreach ((array)($_POST['bike_model'] ?? []) as $i => $model) {
            $model = mb_substr(trim($model), 0, 80); if ($model === '') continue;
            $type = $_POST['bike_type'][$i] ?? ''; $day = (int)($_POST['bike_day'][$i] ?? 0);
            if (!in_array($type, BIKE_TYPES, true) || $day < 1) { $err = "Check bike \"$model\": type and price per day are needed."; break; }
            $bikes[] = [(int)($_POST['bike_id'][$i] ?? 0), $type, $model, $day, max(0, (int)($_POST['bike_hr'][$i] ?? 0)), max(0, (int)($_POST['bike_dep'][$i] ?? 0)), max(1, (int)($_POST['bike_qty'][$i] ?? 1))];
        }
        $status = in_array($_POST['status'] ?? '', ['approved', 'hidden', 'pending'], true) ? $_POST['status'] : 'pending';
        $verified = false;
        if ($id) { $q = $db->prepare('SELECT verified_at FROM shops WHERE id=?'); $q->execute([$id]); $verified = (bool)$q->fetchColumn(); }
        $notice = '';
        if ($status === 'approved' && !$verified) { $status = 'pending'; $notice = ' It stays off the website until you call and approve it.'; }
        if (!$err && !$bikes && $status === 'approved') $err = 'An approved shop needs at least one bike.';
        if ($err) { $_SESSION['form'] = $_POST; go('view=shop&id=' . $id, '!' . $err); }
        $rating = ($_POST['rating'] ?? '') === '' ? null : max(0, min(5, round((float)$_POST['rating'], 1)));
        $vals = [$city, $f('name', 120), $f('area', 120), $loc[0], $loc[1], $f('phone', 20), $f('whatsapp', 20), $f('owner_name', 80), $_POST['open'], $_POST['close'], $rating, $status, $f('notes', 2000)];
        $db->beginTransaction();
        if ($id) {
            $db->prepare('UPDATE shops SET city=?,name=?,area=?,lat=?,lng=?,phone=?,whatsapp=?,owner_name=?,open_time=?,close_time=?,rating=?,status=?,notes=?,is_sample=0 WHERE id=?')->execute([...$vals, $id]);
        } else {
            $db->prepare('INSERT INTO shops (city,name,area,lat,lng,phone,whatsapp,owner_name,open_time,close_time,rating,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals);
            $id = (int)$db->lastInsertId();
        }
        $keep = [];
        foreach ($bikes as [$bid, $type, $model, $day, $hr, $dep, $qty]) {
            if ($bid) {
                $db->prepare('UPDATE bikes SET type=?,model=?,price_day=?,price_hour=?,deposit=?,qty=? WHERE id=? AND shop_id=?')->execute([$type, $model, $day, $hr, $dep, $qty, $bid, $id]);
                $keep[] = $bid;
            } else {
                $db->prepare('INSERT INTO bikes (shop_id,type,model,price_day,price_hour,deposit,qty) VALUES (?,?,?,?,?,?,?)')->execute([$id, $type, $model, $day, $hr, $dep, $qty]);
                $keep[] = (int)$db->lastInsertId();
            }
        }
        if ($keep) {
            $ph = implode(',', array_fill(0, count($keep), '?'));
            $db->prepare("DELETE FROM bikes WHERE shop_id=? AND id NOT IN ($ph)")->execute([$id, ...$keep]);
        } else {
            $db->prepare('DELETE FROM bikes WHERE shop_id=?')->execute([$id]);
        }
        $db->commit();
        go('view=shop&id=' . $id . '#calls', 'Shop saved.' . $notice);
    }
    if ($act === 'password') {
        $q = $db->prepare('SELECT pass_hash FROM admins WHERE id=?'); $q->execute([$_SESSION['admin_id']]);
        if (!password_verify($_POST['old'] ?? '', $q->fetchColumn())) go('view=account', '!Current password is wrong.');
        if (strlen($_POST['new'] ?? '') < 10 || ($_POST['new'] ?? '') !== ($_POST['new2'] ?? '')) go('view=account', '!New password: at least 10 characters, typed the same twice.');
        $db->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $_SESSION['admin_id']]);
        go('view=account', 'Password changed.');
    }
    go();
}

/* ---------------- pages ---------------- */
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$counts = $db->query("SELECT (SELECT COUNT(*) FROM bookings WHERE status='new') nb, (SELECT COUNT(*) FROM shops WHERE status='pending') ps")->fetch();
page_head('Admin');
?>
<header><div class="logo"><img src="logo-icon.png" alt=""> <span class="go">GoLocal</span><span class="wh">Wheels</span> <small>Admin</small></div>
<nav>
  <a class="<?= $view === 'bookings' ? 'on' : '' ?>" href="?view=bookings">Bookings<?= $counts['nb'] ? ' <b class="badge">' . (int)$counts['nb'] . '</b>' : '' ?></a>
  <a class="<?= in_array($view, ['shops', 'shop']) ? 'on' : '' ?>" href="?view=shops">Shops<?= $counts['ps'] ? ' <b class="badge">' . (int)$counts['ps'] . '</b>' : '' ?></a>
  <a class="<?= $view === 'account' ? 'on' : '' ?>" href="?view=account">Account</a>
  <a href="index.html" target="_blank">View site ↗</a>
  <a href="?do=logout">Log out</a>
</nav></header>
<main>
<?php if ($flash): ?><div class="flash <?= $flash[0] === '!' ? 'bad' : 'good' ?>"><?= h(ltrim($flash, '!')) ?></div><?php endif; ?>

<?php if ($view === 'bookings'):
    $f = $_GET['f'] ?? '';
    $sql = 'SELECT b.*, s.name shop_name, s.area, s.phone shop_phone, s.whatsapp shop_wa FROM bookings b JOIN shops s ON s.id=b.shop_id';
    $args = [];
    if (isset(BOOKING_STATUSES[$f])) { $sql .= ' WHERE b.status=?'; $args[] = $f; }
    $q = $db->prepare($sql . ' ORDER BY b.created_at DESC LIMIT 300'); $q->execute($args);
    $rows = $q->fetchAll(); ?>
  <h2>Bookings</h2>
  <div class="tabs"><a class="<?= $f === '' ? 'on' : '' ?>" href="?view=bookings">All</a>
  <?php foreach (BOOKING_STATUSES as $k => $v): ?><a class="<?= $f === $k ? 'on' : '' ?>" href="?view=bookings&f=<?= $k ?>"><?= $v ?></a><?php endforeach; ?></div>
  <?php if (!$rows): ?><p class="empty">No bookings yet.</p><?php endif; ?>
  <?php foreach ($rows as $b):
      $msgShop = "Hello from GoLocal Wheels! New booking {$b['code']}: {$b['bike_model']}, pickup {$b['pickup_date']} for {$b['days']} day(s). Customer: {$b['customer_name']}, {$b['customer_phone']}. Total Rs. {$b['total']}. Please confirm.";
      $msgCust = "Hello {$b['customer_name']}, your GoLocal Wheels booking {$b['code']} ({$b['bike_model']} at {$b['shop_name']}, {$b['area']}) on {$b['pickup_date']} is confirmed. Total Rs. {$b['total']}. Please bring your driving licence and ID.";
  ?>
  <div class="card">
    <div class="row"><div><b class="code"><?= h($b['code']) ?></b> <span class="st st-<?= h($b['status']) ?>"><?= h(BOOKING_STATUSES[$b['status']]) ?></span>
      <div class="muted">Booked <?= h(date('d M Y, g:i A', strtotime($b['created_at']))) ?></div></div>
      <div class="total">₹<?= number_format($b['total']) ?></div></div>
    <div class="grid">
      <div><span class="muted">Customer</span><br><b><?= h($b['customer_name']) ?></b><br><?= h($b['customer_phone']) ?></div>
      <div><span class="muted">Bike</span><br><b><?= h($b['bike_model']) ?></b><br><?= h($b['shop_name']) ?>, <?= h($b['area']) ?></div>
      <div><span class="muted">Pickup</span><br><b><?= h(date('D d M Y', strtotime($b['pickup_date']))) ?></b><br><?= (int)$b['days'] ?> day(s)<?= $b['helmet'] ? ' · extra helmet' : '' ?></div>
    </div>
    <div class="actions">
      <a class="btn sm" target="_blank" rel="noopener" href="<?= h(wa_link($b['shop_wa'] ?: $b['shop_phone'], $msgShop)) ?>">💬 Send to shop</a>
      <a class="btn sm ghost" target="_blank" rel="noopener" href="<?= h(wa_link($b['customer_phone'], $msgCust)) ?>">💬 Confirm to customer</a>
      <a class="btn sm ghost" href="tel:<?= h(digits($b['customer_phone'])) ?>">📞 Call customer</a>
      <form method="post" action="?view=bookings&f=<?= h($f) ?>" class="inline">
        <input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="booking_status"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
        <select name="status"><?php foreach (BOOKING_STATUSES as $k => $v): ?><option value="<?= $k ?>" <?= $b['status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
        <input name="note" placeholder="Note (optional)" value="<?= h($b['admin_note']) ?>">
        <button class="btn sm dark">Save</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>

<?php elseif ($view === 'shops'):
    $f = $_GET['f'] ?? 'pending';
    $FILTERS = ['pending' => 'To call', 'approved' => 'On website', 'hidden' => 'Hidden', 'all' => 'All'];
    if (!isset($FILTERS[$f])) $f = 'pending';
    $counts = $db->query("SELECT status, COUNT(*) n FROM shops GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $sql = "SELECT s.*, (SELECT COUNT(*) FROM bikes WHERE shop_id=s.id) nbikes, (SELECT MIN(price_day) FROM bikes WHERE shop_id=s.id) minp,
                   (SELECT note FROM shop_comments WHERE shop_id=s.id ORDER BY id DESC LIMIT 1) last_note,
                   (SELECT created_at FROM shop_comments WHERE shop_id=s.id ORDER BY id DESC LIMIT 1) last_at,
                   (SELECT COUNT(*) FROM shop_comments WHERE shop_id=s.id) ncomments
            FROM shops s" . ($f === 'all' ? '' : ' WHERE s.status=' . $db->quote($f)) . " ORDER BY s.city DESC, s.name";
    $rows = $db->query($sql)->fetchAll();
    $nSample = (int)$db->query('SELECT COUNT(*) FROM shops WHERE is_sample=1')->fetchColumn();
    $hasResearch = is_file(GLW_APP . '/data/shops-research.json'); ?>
  <div class="row"><h2>Shops</h2><a class="btn" href="?view=shop">+ Add shop</a></div>
  <?php $nr = 0;
    if ($hasResearch) { $chk = $db->prepare('SELECT 1 FROM shops WHERE city=? AND name=?');
        foreach (json_decode(file_get_contents(GLW_APP . '/data/shops-research.json'), true) ?: [] as $x) { $chk->execute([$x['city'] ?? '', $x['name'] ?? '']); if (!$chk->fetch()) $nr++; } }
    if ($nr): ?>
  <div class="flash warn">📋 A list of <?= $nr ?> real bike rental shops (Pondicherry + Chennai) is ready to add. They go to “To call” and do NOT show on the website until you call and approve them.<?= $nSample ? ' The ' . $nSample . ' sample shops will be removed.' : '' ?>
    <form method="post" class="inline" onsubmit="return confirm('Add the researched shops<?= $nSample ? ' and remove the sample shops' : '' ?>?')"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="import_research"><button class="btn sm dark">Add shop list</button></form></div>
  <?php elseif ($nSample): ?>
  <div class="flash warn"><?= $nSample ?> sample shops are on the site.
    <form method="post" class="inline" onsubmit="return confirm('Delete all <?= $nSample ?> sample shops? This cannot be undone.')"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="delete_samples"><button class="btn sm dark">Delete sample shops</button></form></div>
  <?php endif; ?>
  <p class="muted">A shop appears on the website only after you call it, write a comment and tick “Approve”.</p>
  <div class="tabs"><?php foreach ($FILTERS as $k => $v): $n = $k === 'all' ? array_sum($counts) : ($counts[$k] ?? 0); ?><a class="<?= $f === $k ? 'on' : '' ?>" href="?view=shops&f=<?= $k ?>"><?= $v ?> (<?= (int)$n ?>)</a><?php endforeach; ?></div>
  <?php if (!$rows): ?><p class="empty">No shops here.</p><?php else: ?>
  <table><tr><th>Shop</th><th>City</th><th>Bikes</th><th>Last call comment</th><th>Status</th><th></th></tr>
  <?php foreach ($rows as $s): ?>
    <tr><td><b><?= h($s['name']) ?></b><?= $s['is_sample'] ? ' <span class="tag">sample</span>' : '' ?><br><span class="muted"><?= h($s['area']) ?></span><br>
      <?php if ($s['phone']): ?><a href="tel:<?= h(digits($s['phone'])) ?>">📞 <?= h($s['phone']) ?></a><?php else: ?><span class="muted">No phone yet</span><?php endif; ?></td>
    <td><?= h(CITY_KEYS[$s['city']] ?? $s['city']) ?></td>
    <td><?= (int)$s['nbikes'] ?><?= $s['minp'] ? '<br><span class="muted">from ₹' . (int)$s['minp'] . '</span>' : '' ?></td>
    <td class="note"><?php if ($s['last_note']): ?><?= h(mb_strimwidth($s['last_note'], 0, 90, '…')) ?><br><span class="muted"><?= h(date('d M, g:i A', strtotime($s['last_at']))) ?> · <?= (int)$s['ncomments'] ?> comment(s)</span><?php else: ?><span class="muted">Not called yet</span><?php endif; ?></td>
    <td><span class="st st-<?= h($s['status']) ?>"><?= h($s['status'] === 'pending' ? 'To call' : ($s['status'] === 'approved' ? 'On website' : 'Hidden')) ?></span></td>
    <td class="nowrap"><a class="btn sm" href="?view=shop&id=<?= (int)$s['id'] ?>#calls">📞 Call &amp; comment</a>
      <a class="btn sm ghost" href="?view=shop&id=<?= (int)$s['id'] ?>">Edit</a>
      <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="shop_status"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
      <?php if ($s['status'] === 'approved'): ?><button class="btn sm ghost" name="status" value="hidden">Hide</button>
      <?php elseif ($s['status'] === 'hidden' && ($s['verified_at'] || $s['is_sample'])): ?><button class="btn sm ghost" name="status" value="approved">Show again</button><?php endif; ?></form>
      <form method="post" class="inline" onsubmit="return confirm('Delete this shop, its bikes, comments and bookings?')"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="shop_delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn sm ghost">Delete</button></form></td></tr>
  <?php endforeach; ?></table>
  <?php endif; ?>

<?php elseif ($view === 'shop'):
    $id = (int)($_GET['id'] ?? 0);
    $s = ['city' => 'pondy', 'name' => '', 'area' => '', 'lat' => '', 'lng' => '', 'phone' => '', 'whatsapp' => '', 'owner_name' => '', 'open_time' => '08:00', 'close_time' => '20:00', 'rating' => '', 'status' => 'pending', 'notes' => '', 'verified_at' => null];
    $bikes = []; $comments = [];
    if ($id) {
        $q = $db->prepare('SELECT * FROM shops WHERE id=?'); $q->execute([$id]); $s = $q->fetch() ?: $s;
        $q = $db->prepare('SELECT * FROM bikes WHERE shop_id=? ORDER BY id'); $q->execute([$id]); $bikes = $q->fetchAll();
        $q = $db->prepare('SELECT * FROM shop_comments WHERE shop_id=? ORDER BY id DESC'); $q->execute([$id]); $comments = $q->fetchAll();
    }
    $loc = $s['lat'] !== '' ? rtrim(rtrim($s['lat'], '0'), '.') . ', ' . rtrim(rtrim($s['lng'], '0'), '.') : '';
    if (!empty($_SESSION['form'])) { $F = $_SESSION['form']; unset($_SESSION['form']);
        foreach (['city', 'name', 'area', 'phone', 'whatsapp', 'owner_name', 'rating', 'status', 'notes'] as $k) $s[$k] = $F[$k] ?? $s[$k];
        $s['open_time'] = $F['open'] ?? $s['open_time']; $s['close_time'] = $F['close'] ?? $s['close_time']; $loc = $F['loc'] ?? $loc;
        $bikes = []; foreach ((array)($F['bike_model'] ?? []) as $i => $m) $bikes[] = ['id' => $F['bike_id'][$i] ?? 0, 'type' => $F['bike_type'][$i] ?? '', 'model' => $m, 'price_day' => $F['bike_day'][$i] ?? '', 'price_hour' => $F['bike_hr'][$i] ?? '', 'deposit' => $F['bike_dep'][$i] ?? '', 'qty' => $F['bike_qty'][$i] ?? 1]; }
    $nb = count($bikes);
    if (!$bikes) $bikes[] = ['id' => 0, 'type' => 'Scooter', 'model' => '', 'price_day' => '', 'price_hour' => '', 'deposit' => '', 'qty' => 1];
    $draft = $_SESSION['note_draft'] ?? ''; unset($_SESSION['note_draft']); ?>
  <div class="row"><h2><?= $id ? h($s['name']) : 'Add shop' ?></h2><a class="btn sm ghost" href="?view=shops">← All shops</a></div>
  <?php if ($id): ?>
  <div class="card" id="calls">
    <div class="row"><h3 style="margin:0">📞 Call notes</h3>
      <span class="st st-<?= h($s['status']) ?>"><?= $s['status'] === 'approved' ? 'On website' : ($s['status'] === 'hidden' ? 'Hidden' : 'Not on website yet') ?></span></div>
    <p class="muted" style="margin:6px 0 10px"><?php if ($s['phone']): ?>Call: <a href="tel:<?= h(digits($s['phone'])) ?>"><b><?= h($s['phone']) ?></b></a><?php else: ?>No phone number yet – find it on Google Maps and add it below.<?php endif; ?>
      <?= $s['verified_at'] ? ' · Approved after call on ' . h(date('d M Y', strtotime($s['verified_at']))) : '' ?></p>
    <form method="post" class="form"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="comment"><input type="hidden" name="id" value="<?= $id ?>">
      <textarea name="note" rows="3" required placeholder="What did they say? e.g. “Approved by calling – spoke to owner Ravi, 12 scooters, ₹350/day, OK to list.” or “No answer, call again tomorrow.”"><?= h($draft) ?></textarea>
      <?php if ($s['status'] !== 'approved'): ?>
      <label class="approve"><input type="checkbox" name="approve" value="1"> ✅ <b>I called this shop and they agreed to be listed.</b> Approve it and show it on the website.</label>
      <?php if (!$nb): ?><div class="muted">⚠️ To approve, first add at least one bike with its price below and click <b>Save shop</b>.</div><?php endif; ?>
      <?php endif; ?>
      <div class="actions"><button class="btn dark">Save comment</button></div>
    </form>
    <?php if ($comments): ?><div class="history"><?php foreach ($comments as $c): ?>
      <div class="cm<?= $c['approved'] ? ' ok' : '' ?>"><div class="muted"><?= h(date('d M Y, g:i A', strtotime($c['created_at']))) ?><?= $c['approved'] ? ' · ✅ Approved' : '' ?></div><?= nl2br(h($c['note'])) ?></div>
    <?php endforeach; ?></div><?php endif; ?>
  </div>
  <?php endif; ?>
  <form method="post" class="card form"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="shop_save"><input type="hidden" name="id" value="<?= $id ?>">
    <h3 style="margin-top:0">Shop details</h3>
    <div class="g2">
      <div><label>Shop name</label><input name="name" required value="<?= h($s['name']) ?>"></div>
      <div><label>City</label><select name="city"><?php foreach (CITY_KEYS as $k => $v): ?><option value="<?= $k ?>" <?= $s['city'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
      <div><label>Area / street</label><input name="area" required value="<?= h($s['area']) ?>"></div>
      <div><label>Location (latitude, longitude)</label><input name="loc" required placeholder="11.93452, 79.82851" value="<?= h($loc) ?>"><small class="muted">Google Maps → right-click the shop → copy the numbers. <?php if ($loc): ?><a target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= h(rawurlencode($loc)) ?>">Check this pin ↗</a><?php endif; ?></small></div>
      <div><label>Phone</label><input name="phone" value="<?= h($s['phone']) ?>"></div>
      <div><label>WhatsApp (if different)</label><input name="whatsapp" value="<?= h($s['whatsapp']) ?>"></div>
      <div><label>Owner name</label><input name="owner_name" value="<?= h($s['owner_name']) ?>"></div>
      <div class="g2"><div><label>Opens</label><input type="time" name="open" required value="<?= h($s['open_time']) ?>"></div><div><label>Closes</label><input type="time" name="close" required value="<?= h($s['close_time']) ?>"></div></div>
      <div><label>Rating (0–5, optional)</label><input type="number" name="rating" min="0" max="5" step="0.1" value="<?= h($s['rating']) ?>"></div>
      <div><label>Status</label><select name="status">
        <?php foreach (['pending' => 'To call (not on website)', 'approved' => 'On website', 'hidden' => 'Hidden'] as $k => $v): if ($k === 'approved' && !$s['verified_at'] && $s['status'] !== 'approved') continue; ?>
        <option value="<?= $k ?>" <?= $s['status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
        <?php if (!$s['verified_at'] && $s['status'] !== 'approved'): ?><small class="muted">To put it on the website, use the call comment box above.</small><?php endif; ?></div>
    </div>
    <label>Notes (address, sources – only you see these)</label><textarea name="notes" rows="4"><?= h($s['notes'] ?? '') ?></textarea>
    <h3>Bikes</h3>
    <div class="bikes" id="bikes">
      <div class="bh"><span>Type</span><span>Model</span><span>₹/day</span><span>₹/hour</span><span>Deposit ₹</span><span>Qty</span><span></span></div>
      <?php foreach ($bikes as $b): ?>
      <div class="br"><input type="hidden" name="bike_id[]" value="<?= (int)$b['id'] ?>">
        <select name="bike_type[]"><?php foreach (BIKE_TYPES as $t): ?><option <?= $b['type'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
        <input name="bike_model[]" placeholder="e.g. Honda Activa 6G" value="<?= h($b['model']) ?>">
        <input type="number" name="bike_day[]" min="1" value="<?= h($b['price_day']) ?>">
        <input type="number" name="bike_hr[]" min="0" value="<?= h($b['price_hour']) ?>">
        <input type="number" name="bike_dep[]" min="0" value="<?= h($b['deposit']) ?>">
        <input type="number" name="bike_qty[]" min="1" value="<?= h($b['qty']) ?>">
        <button type="button" class="btn sm ghost" onclick="rm(this)">✕</button></div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn sm ghost" onclick="addBike()">+ Add bike</button>
    <div class="actions"><button class="btn">Save shop</button> <a class="btn ghost" href="?view=shops">Cancel</a></div>
  </form>
  <script>
  function addBike(){const r=document.querySelector('.br').cloneNode(true);r.querySelectorAll('input').forEach(i=>i.value=i.name==='bike_qty[]'?1:(i.name==='bike_id[]'?0:''));document.getElementById('bikes').appendChild(r);}
  function rm(b){const rows=document.querySelectorAll('.br');if(rows.length>1)b.parentNode.remove();else b.parentNode.querySelectorAll('input:not([type=hidden])').forEach(i=>i.value='');}
  </script>

<?php elseif ($view === 'account'): ?>
  <h2>Change password</h2>
  <form method="post" class="card form narrow"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="act" value="password">
    <label>Current password</label><input type="password" name="old" required autocomplete="current-password">
    <label>New password (at least 10 characters)</label><input type="password" name="new" required minlength="10" autocomplete="new-password">
    <label>New password again</label><input type="password" name="new2" required minlength="10" autocomplete="new-password">
    <div class="actions"><button class="btn">Change password</button></div></form>
<?php endif; ?>
</main>
<?php page_foot();

/* ---------------- layout ---------------- */
function page_head(string $title): void { ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title><?= h($title) ?> – GoLocal Wheels</title><link rel="icon" href="logo-icon.png">
<style>
:root{--ink:#14100c;--orange:#d2461f;--mustard:#e8a21e;--muted:#667085;--line:#e6e1d6;--bg:#fbf8f1}
*{box-sizing:border-box}body{margin:0;font-family:system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--ink);font-size:15px}
header{background:var(--ink);color:#fff;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;border-bottom:3px solid var(--orange)}
.logo{font-weight:800;font-size:20px;display:flex;align-items:center;gap:8px}.logo img{height:34px}.logo small{font-weight:500;color:#aaa;font-size:13px}
.go{color:var(--orange)}.wh{color:var(--mustard)}
nav{display:flex;gap:4px;flex-wrap:wrap}nav a{color:#fff;text-decoration:none;padding:8px 12px;border-radius:10px}nav a.on,nav a:hover{background:#ffffff1f}
.badge{background:var(--orange);color:#fff;border-radius:999px;padding:1px 7px;font-size:12px}
main{max-width:1100px;margin:0 auto;padding:20px}h2{margin:6px 0 14px}
.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:12px}
.row{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:12px 0;font-size:14px}
.muted{color:var(--muted);font-size:13px}.code{font-size:17px}.total{font-size:22px;font-weight:800}
.st{font-size:12px;padding:2px 9px;border-radius:999px;font-weight:600;background:#eee}
.st-new{background:#fde7b0}.st-confirmed,.st-approved{background:#d6f0e0;color:#1d6b45}.st-completed{background:#dde7fb;color:#1a56b8}.st-cancelled,.st-hidden{background:#f3d6d2;color:#8a1c12}.st-pending{background:#fde7b0}
.tag{font-size:11px;background:#eee;border-radius:6px;padding:1px 6px;color:var(--muted)}
.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px}
.btn{background:var(--mustard);border:none;padding:10px 16px;border-radius:10px;font-weight:600;cursor:pointer;color:var(--ink);text-decoration:none;display:inline-block;font:inherit;font-weight:600}
.btn.sm{padding:6px 11px;font-size:13px}.btn.ghost{background:#fff;border:1px solid var(--line)}.btn.dark{background:var(--ink);color:#fff}
form.inline{display:inline-flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0}
input,select,textarea{padding:8px 10px;border:1px solid var(--line);border-radius:9px;font:inherit;background:#fff;max-width:100%}
.form label{display:block;font-size:13px;color:var(--muted);margin:10px 0 4px}.form input,.form select,.form textarea{width:100%}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}@media(max-width:700px){.g2{grid-template-columns:1fr}}
.bikes{display:flex;flex-direction:column;gap:6px;margin:8px 0}.bh,.br{display:grid;grid-template-columns:130px 1fr 80px 80px 90px 60px 40px;gap:6px;align-items:center}
.bh{font-size:12px;color:var(--muted)}.br input,.br select{width:100%}
@media(max-width:700px){.bh{display:none}.br{grid-template-columns:1fr 1fr;border:1px solid var(--line);padding:8px;border-radius:10px}}
table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden}
th,td{text-align:left;padding:10px;border-bottom:1px solid var(--line);vertical-align:top;font-size:14px}th{background:#f3eee3;font-size:13px;color:var(--muted)}
.nowrap{white-space:nowrap}
.tabs{display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap}.tabs a{padding:6px 12px;border-radius:999px;border:1px solid var(--line);text-decoration:none;color:var(--ink);background:#fff;font-size:14px}.tabs a.on{background:var(--ink);color:#fff}
.flash{padding:12px;border-radius:10px;margin-bottom:12px}.good{background:#e8f7ef;color:#1d6b45}.bad{background:#fdecea;color:#8a1c12}.warn{background:#fff4d6;color:#7a5600;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.empty{color:var(--muted);padding:30px;text-align:center;background:#fff;border:1px dashed var(--line);border-radius:14px}
.login{max-width:360px;margin:12vh auto;background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px}.login h1{margin:0}.login label{display:block;font-size:13px;color:var(--muted);margin:12px 0 4px}.login input{width:100%}.login .btn{width:100%;margin-top:16px}
.narrow{max-width:420px}
.note{max-width:280px;font-size:13px}.approve{display:flex!important;gap:8px;align-items:flex-start;color:var(--ink)!important;font-size:14px!important;background:#e8f7ef;padding:10px;border-radius:10px;margin:10px 0}.approve input{width:auto!important;margin-top:3px}
.history{margin-top:14px;display:flex;flex-direction:column;gap:8px}.cm{border-left:3px solid var(--line);padding:6px 10px;background:#faf8f3;border-radius:6px;font-size:14px}.cm.ok{border-left-color:#2e9e6a;background:#eef8f2}
</style></head><body>
<?php }
function page_foot(): void { echo '</body></html>'; }
