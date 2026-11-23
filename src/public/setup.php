<?php
/* =========================================================
   GoLocal Wheels – create the FIRST admin login.
   Works only while no admin exists. Tables are created by
   the migrations (database/migrations), not by this page.
   ========================================================= */
define('GLW', 1);
require (require __DIR__ . '/_app_path.php') . '/lib/db.php';
require GLW_APP . '/lib/migrator.php';
header('Cache-Control: no-store');
header('X-LiteSpeed-Cache-Control: no-cache');

$msg = ''; $ok = false; $form = false;
try {
    $db = db();
    if (pending_migrations($db)) {
        $msg = 'The database is not ready yet: the migrations have not run. Deploy again or run scripts/migrate.php.';
    } elseif ((int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        $ok = true; $msg = 'An admin login already exists. Go to the admin panel to log in.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
        if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $u)) { $msg = 'Username: 3–60 letters or numbers.'; $form = true; }
        elseif (strlen($p) < 10) { $msg = 'Password must be at least 10 characters.'; $form = true; }
        elseif ($p !== ($_POST['password2'] ?? '')) { $msg = 'The two passwords do not match.'; $form = true; }
        else {
            $db->prepare('INSERT INTO admins (username, pass_hash) VALUES (?, ?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
            $ok = true; $msg = 'Done! Your admin login is ready.';
        }
    } else { $form = true; }
} catch (Throwable $e) {
    error_log('GLW setup: ' . $e->getMessage());
    $msg = 'Could not connect to the database. Check the DB_ values in app/.env.';
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>GoLocal Wheels – Setup</title>
<style>body{font-family:system-ui,sans-serif;background:#fbf8f1;color:#14100c;display:grid;place-items:center;min-height:100vh;margin:0;padding:16px}
.box{background:#fff;border:1px solid #e6e1d6;border-radius:16px;padding:24px;max-width:420px;width:100%}
h1{margin:0 0 8px;color:#d2461f;font-size:22px}label{display:block;font-size:13px;color:#667085;margin:12px 0 4px}
input{width:100%;padding:10px;border:1px solid #e6e1d6;border-radius:10px;font:inherit;box-sizing:border-box}
button{margin-top:16px;background:#e8a21e;border:none;padding:12px 16px;border-radius:12px;font-weight:700;cursor:pointer;width:100%;font:inherit}
.msg{padding:12px;border-radius:10px;margin:12px 0;font-size:14px}.bad{background:#fdecea;color:#8a1c12}.good{background:#e8f7ef;color:#1d6b45}a{color:#d2461f}</style></head>
<body><div class="box"><h1>GoLocal Wheels setup</h1>
<?php if ($msg): ?><div class="msg <?= $ok ? 'good' : 'bad' ?>"><?= h($msg) ?></div><?php endif; ?>
<?php if ($ok): ?><p><a href="admin.php">Go to admin panel →</a></p><?php endif; ?>
<?php if ($form): ?>
<form method="post"><p style="font-size:14px;color:#667085">Create the first admin login.</p>
<label>Admin username</label><input name="username" required autocomplete="username">
<label>Password (at least 10 characters)</label><input type="password" name="password" required minlength="10" autocomplete="new-password">
<label>Type the password again</label><input type="password" name="password2" required minlength="10" autocomplete="new-password">
<button type="submit">Create admin login</button></form>
<?php endif; ?></div></body></html>
