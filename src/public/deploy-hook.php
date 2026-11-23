<?php
/* Called by GitHub Actions after a merge:
     POST /deploy-hook.php   header X-Deploy-Token: <DEPLOY_TOKEN>   body {"sha": "<commit>", "branch": "main"}
   The server then downloads that commit from GitHub, installs it,
   takes a database backup (if there are new migrations) and runs the migrations. */
define('GLW', 1);
require (require __DIR__ . '/_app_path.php') . '/lib/db.php';
require GLW_APP . '/lib/deployer.php';
require GLW_APP . '/lib/migrator.php';
require GLW_APP . '/lib/backup.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-LiteSpeed-Cache-Control: no-cache');
@set_time_limit(300);

function hook_out(array $data, int $code = 200): void { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_SLASHES); exit; }

$token = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || strlen(DEPLOY_TOKEN) < 32 || !hash_equals(DEPLOY_TOKEN, $token)) {
    hook_out(['ok' => false, 'error' => 'Forbidden'], 403);
}
$in = json_decode((string)file_get_contents('php://input'), true) ?: [];

try {
    $result = deploy_from_github((string)($in['sha'] ?? ''), (string)($in['branch'] ?? ''), __DIR__);
    // migrations from the newly installed files
    $db = db_admin();
    $pending = pending_migrations($db, GLW_APP . '/migrations');
    $result['backup'] = $pending ? basename(make_backup($db, 'before-migration')) : null;
    $result['migrations'] = run_migrations($db, GLW_APP . '/migrations');
    hook_out(['ok' => true, 'env' => APP_ENV] + $result);
} catch (InvalidArgumentException $e) {
    hook_out(['ok' => false, 'error' => $e->getMessage()], 400);
} catch (DomainException $e) {
    hook_out(['ok' => false, 'skipped' => true, 'error' => $e->getMessage()], 409);
} catch (Throwable $e) {
    error_log('GLW deploy: ' . $e->getMessage());
    hook_out(['ok' => false, 'error' => 'Deploy failed: ' . $e->getMessage()], 500);
}
