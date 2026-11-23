<?php
/* Pull-based deploy: the server downloads one exact commit of the GitHub repository
   and copies it into place. No FTP account or hosting password is needed.

   Safety checks:
   - the caller must know DEPLOY_TOKEN (checked in deploy-hook.php)
   - the commit must be the CURRENT head of DEPLOY_BRANCH on GitHub (so an old or
     unknown commit can never be installed, even with the token)
   - private things on the server are never overwritten: .env, backups, sessions,
     data (shop list) and _app_path.php */
defined('GLW') or exit;

define('DEPLOY_REPO', env('DEPLOY_REPO', 'Jeankevin14/golocal-wheels'));
define('DEPLOY_BRANCH', env('DEPLOY_BRANCH', APP_ENV === 'staging' ? 'develop' : 'main'));
define('DEPLOY_TOKEN', env('DEPLOY_TOKEN', ''));

/** Download a URL into a string (or a file when $toFile is given). */
function deploy_http_get(string $url, ?string $toFile = null): string {
    $ch = curl_init($url);
    $fh = null;
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'golocal-wheels-deployer',
        CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
        CURLOPT_FAILONERROR => true,
    ]);
    if ($ca = env('CURL_CA_BUNDLE')) curl_setopt($ch, CURLOPT_CAINFO, $ca);
    if ($toFile) { $fh = fopen($toFile, 'wb'); curl_setopt($ch, CURLOPT_FILE, $fh); }
    else curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $out = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($fh) fclose($fh);
    if ($out === false) throw new RuntimeException("Download failed: $url ($err)");
    return $toFile ? '' : (string)$out;
}

/** The commit that is currently at the top of $branch on GitHub. */
function deploy_branch_head(string $branch): string {
    $j = json_decode(deploy_http_get('https://api.github.com/repos/' . DEPLOY_REPO . '/commits/' . rawurlencode($branch)), true);
    $sha = $j['sha'] ?? '';
    if (!preg_match('/^[0-9a-f]{40}$/', $sha)) throw new RuntimeException('Could not read the branch from GitHub.');
    return $sha;
}

function deploy_rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    @rmdir($dir);
}

/** Copy a folder tree; $skip lists top-level names (relative to $src) that are never touched. */
function deploy_copy_tree(string $src, string $dst, array $skip = []): int {
    $n = 0;
    if (!is_dir($dst) && !@mkdir($dst, 0755, true)) throw new RuntimeException("Cannot create $dst");
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $f) {
        $rel = substr($f->getPathname(), strlen($src) + 1);
        $top = explode('/', str_replace('\\', '/', $rel))[0];
        if (in_array($top, $skip, true)) continue;
        $target = $dst . '/' . $rel;
        if ($f->isDir()) { if (!is_dir($target)) @mkdir($target, 0755, true); continue; }
        $tmp = $target . '.deploy-tmp';
        if (!copy($f->getPathname(), $tmp) || !rename($tmp, $target)) throw new RuntimeException("Cannot write $target");
        @chmod($target, 0644);
        $n++;
    }
    return $n;
}

/** Install commit $sha of DEPLOY_BRANCH. $publicDir is the web root (where deploy-hook.php lives). */
function deploy_from_github(string $sha, string $branch, string $publicDir): array {
    if (!preg_match('/^[0-9a-f]{40}$/', $sha)) throw new InvalidArgumentException('Invalid commit id.');
    if ($branch !== DEPLOY_BRANCH) throw new InvalidArgumentException('This server only deploys the branch ' . DEPLOY_BRANCH . '.');
    $head = deploy_branch_head($branch);
    if (!hash_equals($head, $sha)) throw new DomainException('That commit is not the latest one on ' . $branch . ' – a newer deploy will follow.');

    $work = GLW_APP . '/tmp';
    if (!is_dir($work) && !@mkdir($work, 0700, true)) throw new RuntimeException('Cannot create app/tmp.');
    $lock = fopen($work . '/deploy.lock', 'c');
    if (!flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Another deploy is running.');

    $zip = "$work/$sha.zip"; $dir = "$work/$sha";
    try {
        deploy_http_get('https://codeload.github.com/' . DEPLOY_REPO . "/zip/$sha", $zip);
        deploy_rrmdir($dir);
        $za = new ZipArchive();
        if ($za->open($zip) !== true || !$za->extractTo($dir)) throw new RuntimeException('Cannot unpack the download.');
        $za->close();
        $roots = glob("$dir/*", GLOB_ONLYDIR);
        $root = $roots[0] ?? '';
        foreach (['src/public/index.html', 'src/public/api.php', 'src/app/lib/db.php', 'database/migrations'] as $must) {
            if (!file_exists("$root/$must")) throw new RuntimeException("Download is incomplete ($must missing).");
        }
        // private app folder first, then the website, then the migrations
        $nApp = deploy_copy_tree("$root/src/app", GLW_APP, ['.env', 'backups', 'sessions', 'data', 'tmp', 'migrations']);
        $nMig = deploy_copy_tree("$root/database/migrations", GLW_APP . '/migrations');
        $nPub = deploy_copy_tree("$root/src/public", $publicDir, ['_app_path.php']);
        file_put_contents(GLW_APP . '/deployed.json', json_encode(['sha' => $sha, 'branch' => $branch, 'at' => date('c')], JSON_PRETTY_PRINT));
        return ['sha' => $sha, 'branch' => $branch, 'files' => ['app' => $nApp, 'migrations' => $nMig, 'public' => $nPub]];
    } finally {
        @unlink($zip);
        deploy_rrmdir($dir);
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
