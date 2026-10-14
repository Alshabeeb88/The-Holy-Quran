<?php
/*
 * Read-only health and security check for a live install, meant for the
 * hosting terminal (cPanel > Terminal):
 *
 *   curl -fsSLo /tmp/qfa-audit.php https://raw.githubusercontent.com/Alshabeeb88/The-Holy-Quran/main/tools/server-audit.php
 *   php /tmp/qfa-audit.php [site folder] [git ref]
 *
 * It changes nothing on the server. It compares every file with the
 * repository, looks for unknown or suspicious PHP files, checks permissions,
 * and probes the public site for headers and exposed private files. Secrets
 * (admin email, password hash, messages) are never printed.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$started = microtime(true);
$ref = $argv[2] ?? 'main';
$repo = 'Alshabeeb88/The-Holy-Quran';
$warnings = 0;
$problems = 0;

function line(string $mark, string $text): void {
    global $warnings, $problems;
    if ($mark === 'WARN') $warnings++;
    if ($mark === 'FAIL') $problems++;
    $icon = ['OK' => '[ok]  ', 'WARN' => '[!]   ', 'FAIL' => '[XX]  ', 'INFO' => '      '][$mark] ?? '      ';
    echo $icon . $text . "\n";
}

function section(string $title): void {
    echo "\n== $title ==\n";
}

function http_get(string $url, bool $follow = false, int $max = 65536): array {
    $headers = [];
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'ignore_errors' => true,
            'follow_location' => $follow ? 1 : 0,
            'header' => "User-Agent: qfa-audit\r\nAccept: */*\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'capture_peer_cert' => true],
    ]);
    $fp = @fopen($url, 'r', false, $ctx);
    if (!$fp) {
        return ['status' => 0, 'headers' => [], 'body' => '', 'cert' => null];
    }
    $meta = stream_get_meta_data($fp);
    $body = (string)stream_get_contents($fp, $max);
    $params = stream_context_get_params($fp);
    fclose($fp);
    $status = 0;
    foreach ($meta['wrapper_data'] ?? [] as $h) {
        if (preg_match('~^HTTP/\S+\s+(\d{3})~', $h, $m)) {
            $status = (int)$m[1];
            $headers = [];
            continue;
        }
        $p = strpos($h, ':');
        if ($p !== false) {
            $headers[strtolower(trim(substr($h, 0, $p)))] = trim(substr($h, $p + 1));
        }
    }
    return ['status' => $status, 'headers' => $headers, 'body' => $body, 'cert' => $params['options']['ssl']['peer_certificate'] ?? null];
}

function git_blob_sha(string $file): string {
    $data = (string)file_get_contents($file);
    return sha1('blob ' . strlen($data) . "\0" . $data);
}

/** Walk the site, leaving out .git and any other site installed inside it. */
function site_files(string $root, array $skip, bool $dirs = false): Iterator {
    $filter = new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $f) use ($root, $skip): bool {
            $rel = substr($f->getPathname(), strlen($root) + 1);
            return $rel !== '.git' && !in_array($rel, $skip, true);
        }
    );
    return new RecursiveIteratorIterator($filter, $dirs ? RecursiveIteratorIterator::SELF_FIRST : RecursiveIteratorIterator::LEAVES_ONLY);
}

/* ---------- where is the site ---------- */

$root = $argv[1] ?? '';
if ($root === '') {
    $home = getenv('HOME') ?: '.';
    foreach (array_merge([$home . '/public_html'], glob($home . '/*', GLOB_ONLYDIR) ?: [], glob($home . '/public_html/*', GLOB_ONLYDIR) ?: [], glob($home . '/*/*', GLOB_ONLYDIR) ?: []) as $dir) {
        if (is_file($dir . '/includes/class.php') && is_dir($dir . '/api/quran')) {
            $root = $dir;
            break;
        }
    }
}
$root = rtrim((string)realpath($root ?: '.'), '/');
if (!is_file($root . '/includes/class.php')) {
    fwrite(STDERR, "Site folder not found. Run: php qfa-audit.php /path/to/site\n");
    exit(1);
}

// Other sites kept inside this folder (cPanel addon domains) are left out and
// can be checked on their own.
$nested = [];
foreach (array_merge(glob($root . '/*', GLOB_ONLYDIR) ?: [], glob($root . '/*/*', GLOB_ONLYDIR) ?: []) as $dir) {
    if (is_file($dir . '/includes/class.php') || is_file($dir . '/wp-config.php') || is_file($dir . '/artisan')) {
        $nested[] = substr($dir, strlen($root) + 1);
    }
}

echo "Quran site audit (read-only)\n";
echo "Folder: $root\n";
echo "Compared with: github.com/$repo @ $ref\n";
foreach ($nested as $dir) {
    echo "Skipped (separate site): $dir   -> check it with: php " . $argv[0] . " " . $root . "/" . $dir . "\n";
}

/* ---------- environment ---------- */

section('Server');
line('INFO', 'CLI PHP ' . PHP_VERSION . ' (the website may use a different version, see below)');
$handlers = [];
foreach ([$root . '/.htaccess', dirname($root) . '/.htaccess'] as $ht) {
    if (is_file($ht) && preg_match_all('~(?:AddHandler|SetHandler)\s+application/x-httpd-(ea-php\d+|php\d+)~i', (string)file_get_contents($ht), $m)) {
        $handlers = array_merge($handlers, $m[1]);
    }
}
if ($handlers) {
    $ver = end($handlers);
    line(preg_match('~(\d)(\d+)$~', $ver, $v) && ((int)$v[1] > 8 || ((int)$v[1] === 8 && (int)$v[2] >= 2)) ? 'OK' : 'WARN', 'Website PHP handler: ' . $ver . ' (8.2 or newer recommended)');
} else {
    line('INFO', 'Website PHP version is set in cPanel > MultiPHP Manager (not pinned in .htaccess)');
}
$installed = glob('/opt/cpanel/ea-php*/root/usr/bin/php') ?: [];
if ($installed) {
    line('INFO', 'PHP versions on this server: ' . implode(', ', array_map(static function ($p) { return preg_replace('~.*/(ea-php\d+)/.*~', '$1', $p); }, $installed)));
}
foreach (['curl', 'mbstring', 'json', 'openssl', 'zlib'] as $ext) {
    if (!extension_loaded($ext)) line('WARN', "PHP extension missing in CLI: $ext");
}
$free = @disk_free_space($root);
if ($free !== false) line('INFO', 'Free disk: ' . round($free / 1073741824, 1) . ' GB');

$version = is_file($root . '/VERSION') ? trim((string)file_get_contents($root . '/VERSION')) : 'unknown';
line('INFO', 'Installed version: ' . $version);
line(is_file($root . '/.qfa-installed') ? 'OK' : 'WARN', is_file($root . '/.qfa-installed') ? 'Installer is locked (.qfa-installed present)' : 'Installer is NOT locked: install.php may be usable by anyone');

/* ---------- files against the repository ---------- */

section('Files compared with the repository');
// QFA_AUDIT_TREE points at a saved copy of the listing, for offline testing.
$tree = getenv('QFA_AUDIT_TREE')
    ? ['status' => 200, 'body' => (string)file_get_contents((string)getenv('QFA_AUDIT_TREE'))]
    : http_get("https://api.github.com/repos/$repo/git/trees/" . rawurlencode($ref) . '?recursive=1', true, 64 * 1048576);
$known = [];
if ($tree['status'] === 200) {
    $json = json_decode($tree['body'], true);
    foreach ($json['tree'] ?? [] as $item) {
        if (($item['type'] ?? '') === 'blob') {
            $known[$item['path']] = $item['sha'];
        }
    }
    if (!empty($json['truncated'])) line('WARN', 'Repository listing was truncated; some files were not compared');
}
if (!$known) {
    line('WARN', 'Could not download the repository file list (HTTP ' . $tree['status'] . '); file comparison skipped');
} else {
    // Files the site creates while running; they are expected to differ or be extra.
    $runtime = '~^(cache/|includes/config\.php|includes/admin_auth_store\.php|includes/contact_messages|includes/x_studio_current|includes/\.admin_auth\.lock|includes/\.?[a-z_]*rate|\.qfa-installed|\.qfa-install\.lock|sitemaps?/|sitemap[^/]*\.xml|.*error_log$|\.well-known/|cgi-bin/|images/(upload|uploads|custom)/|style/default/images/(og|social|logo|share)[^/]*$|\.htaccess$|\.user\.ini$|php\.ini$|robots\.txt$)~i';
    $data = '~^api/(quran|books)/includes/~';
    $dataTotal = $dataBad = 0;
    $modified = $missing = $extra = [];

    $it = site_files($root, $nested);
    $seen = [];
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $rel = substr($file->getPathname(), strlen($root) + 1);
        if (strpos($rel, '.git/') === 0) continue;
        $seen[$rel] = true;
        $isData = (bool)preg_match($data, $rel);
        if (isset($known[$rel])) {
            $same = git_blob_sha($file->getPathname()) === $known[$rel];
            if ($isData) {
                $dataTotal++;
                if (!$same) { $dataBad++; $modified[] = $rel; }
            } elseif (!$same && !preg_match($runtime, $rel)) {
                $modified[] = $rel;
            }
        } elseif (!preg_match($runtime, $rel)) {
            $extra[] = $rel;
        }
    }
    foreach ($known as $rel => $sha) {
        if (!isset($seen[$rel]) && !preg_match('~^(tests/|tools/|\.github/|README|CHANGELOG|ATTRIBUTION|NOTICE|LICENSE|\.gitignore)~', $rel)) {
            $missing[] = $rel;
        }
    }

    line($dataBad === 0 ? 'OK' : 'FAIL', "Quran, tafseer, translation and book data: $dataTotal files checked, $dataBad differ from the repository");
    line(count($modified) - $dataBad === 0 ? 'OK' : 'WARN', 'Program files that differ from the repository: ' . (count($modified) - $dataBad));
    foreach (array_slice(array_values(array_filter($modified, static function ($f) use ($data) { return !preg_match($data, $f); })), 0, 40) as $f) line('INFO', '  changed: ' . $f);
    if ($dataBad) foreach (array_slice(array_values(array_filter($modified, static function ($f) use ($data) { return (bool)preg_match($data, $f); })), 0, 20) as $f) line('INFO', '  data changed: ' . $f);
    $extraPhp = array_values(array_filter($extra, static function ($f) { return (bool)preg_match('~\.(php\d?|phtml|phar|inc)$~i', $f); }));
    line($extraPhp ? 'FAIL' : 'OK', 'PHP files that are not part of the site: ' . count($extraPhp));
    foreach (array_slice($extraPhp, 0, 40) as $f) line('INFO', '  unknown: ' . $f . '  (' . date('Y-m-d H:i', (int)filemtime($root . '/' . $f)) . ')');
    $extraOther = count($extra) - count($extraPhp);
    line('INFO', 'Other extra files (images, backups, etc.): ' . $extraOther);
    foreach (array_slice(array_values(array_filter($extra, static function ($f) { return (bool)preg_match('#\.(zip|tar|gz|sql|bak|old|orig|swp|log)$|~$#i', $f); })), 0, 20) as $f) line('WARN', '  backup or archive inside the web folder: ' . $f);
    line(count($missing) === 0 ? 'OK' : 'INFO', 'Repository files missing on the server: ' . count($missing));
    foreach (array_slice($missing, 0, 15) as $f) line('INFO', '  missing: ' . $f);
}

/* ---------- suspicious code ---------- */

section('Suspicious code patterns');
$patterns = '~(eval\s*\(\s*(base64_decode|gzinflate|gzuncompress|str_rot13|\$_(POST|GET|REQUEST|COOKIE))|assert\s*\(\s*\$_|preg_replace\s*\(\s*[\'"].{1,40}/e[\'"]|(shell_exec|passthru|proc_open|popen|system|exec)\s*\(\s*\$_(POST|GET|REQUEST|COOKIE)|move_uploaded_file\s*\([^)]*\$_FILES[^)]*\.php|FilesMan|c99shell|r57shell)~i';
$hits = [];
$it = site_files($root, $nested);
foreach ($it as $file) {
    $rel = substr($file->getPathname(), strlen($root) + 1);
    if (!$file->isFile() || !preg_match('~\.(php\d?|phtml|inc)$~i', $rel) || preg_match('~(^|/)api/(quran|books)/includes/|^tools/server-audit\.php$~', $rel) || $file->getRealPath() === __FILE__ || $file->getSize() > 2097152) continue;
    if (preg_match($patterns, (string)file_get_contents($file->getPathname()))) $hits[] = $rel;
}
line($hits ? 'FAIL' : 'OK', 'Files with web-shell style code: ' . count($hits));
foreach (array_slice($hits, 0, 30) as $f) line('INFO', '  check: ' . $f);
$inCache = glob($root . '/cache/*.php') ?: [];
$oddCache = array_filter($inCache, static function ($f) { return !preg_match('~\.html\.php$|/index\.php$~', $f); });
line($oddCache ? 'WARN' : 'OK', 'Unexpected PHP files in cache/: ' . count($oddCache));

/* ---------- permissions ---------- */

section('Permissions');
$ww = [];
$it = site_files($root, $nested, true);
foreach ($it as $file) {
    if (($file->getPerms() & 0002) && !$file->isLink()) $ww[] = substr($file->getPathname(), strlen($root) + 1);
}
line($ww ? 'WARN' : 'OK', 'World-writable files or folders (777/666): ' . count($ww));
foreach (array_slice($ww, 0, 20) as $f) line('INFO', '  ' . $f);
foreach (['includes/config.php', 'includes/admin_auth_store.php', 'includes/contact_messages.php'] as $f) {
    if (is_file("$root/$f")) {
        $mode = fileperms("$root/$f") & 0777;
        line(($mode & 0004) ? 'WARN' : 'OK', sprintf('%s mode %o', $f, $mode) . (($mode & 0004) ? ' (readable by other accounts; 640 or 600 is enough)' : ''));
    }
}

/* ---------- configuration (names only) ---------- */

section('Configuration');
$siteUrl = '';
$cfg = is_file($root . '/includes/config.php') ? (string)file_get_contents($root . '/includes/config.php') : '';
if (preg_match("~define\(\s*'SITE_URL'\s*,\s*'([^']*)'~", $cfg, $m)) $siteUrl = rtrim($m[1], '/');
line($siteUrl !== '' ? 'INFO' : 'WARN', 'SITE_URL: ' . ($siteUrl ?: 'not found'));
line(stripos($siteUrl, 'https://') === 0 ? 'OK' : 'WARN', 'SITE_URL uses https');
foreach (['CACHE' => 'page cache', 'TRUSTED_PROXIES' => 'trusted proxies', 'DISABLED_PAGES' => 'page toggles'] as $name => $label) {
    line('INFO', $label . ': ' . (preg_match("~define\(\s*'$name'\s*,\s*([^;]{0,80})\);~", $cfg, $m) ? trim($m[1]) : 'not set'));
}
$logs = [];
$it = site_files($root, $nested);
foreach ($it as $file) {
    if ($file->isFile() && $file->getFilename() === 'error_log') $logs[] = $file->getPathname();
}
foreach ($logs as $log) {
    $size = filesize($log);
    line($size > 5 * 1048576 ? 'WARN' : 'INFO', 'error_log ' . substr($log, strlen($root) + 1) . ': ' . round($size / 1024) . ' KB');
    $fh = fopen($log, 'r');
    fseek($fh, max(0, $size - 200000));
    $tail = (string)stream_get_contents($fh);
    fclose($fh);
    $kinds = [];
    foreach (explode("\n", $tail) as $l) {
        if (preg_match('~PHP (Fatal error|Warning|Deprecated|Notice|Parse error):\s*(.{0,140})~', $l, $m)) {
            $key = $m[1] . ': ' . preg_replace('~ in /\S+~', '', $m[2]);
            $kinds[$key] = ($kinds[$key] ?? 0) + 1;
        }
    }
    arsort($kinds);
    foreach (array_slice($kinds, 0, 8, true) as $k => $n) line('INFO', "  {$n}x $k");
}

/* ---------- the public site ---------- */

section('Public site');
if ($siteUrl === '') {
    line('WARN', 'No SITE_URL, public checks skipped');
} else {
    $host = (string)parse_url($siteUrl, PHP_URL_HOST);
    $home = http_get($siteUrl . '/');
    line($home['status'] === 200 ? 'OK' : 'FAIL', 'Home page: HTTP ' . $home['status']);
    $h = $home['headers'];
    foreach ([
        'strict-transport-security' => 'HSTS',
        'content-security-policy' => 'Content-Security-Policy',
        'x-content-type-options' => 'X-Content-Type-Options',
        'x-frame-options' => 'X-Frame-Options',
        'referrer-policy' => 'Referrer-Policy',
        'permissions-policy' => 'Permissions-Policy',
    ] as $k => $label) {
        line(isset($h[$k]) ? 'OK' : 'WARN', $label . (isset($h[$k]) ? ': ' . substr($h[$k], 0, 90) : ' header missing'));
    }
    if (isset($h['x-powered-by'])) line('WARN', 'X-Powered-By reveals: ' . $h['x-powered-by']);
    if (isset($h['server'])) line('INFO', 'Server: ' . $h['server']);
    if (preg_match('~V(\d+\.\d+\.\d+)~', $home['body'] . '', $m)) line('INFO', 'Footer version on the live page: V' . $m[1]);

    $plain = http_get('http://' . $host . '/');
    $loc = $plain['headers']['location'] ?? '';
    line($plain['status'] >= 300 && $plain['status'] < 400 && stripos($loc, 'https://') === 0 ? 'OK' : 'WARN', 'http:// redirects to https:// (HTTP ' . $plain['status'] . ($loc ? ' -> ' . $loc : '') . ')');

    if (is_array($home['cert']) || is_resource($home['cert']) || is_object($home['cert'])) {
        $info = @openssl_x509_parse($home['cert']);
        if ($info) {
            $days = (int)floor(($info['validTo_time_t'] - time()) / 86400);
            line($days > 14 ? 'OK' : 'WARN', 'TLS certificate valid for ' . $days . ' more days (' . ($info['issuer']['O'] ?? $info['issuer']['CN'] ?? '?') . ')');
        }
    }

    // Private files must not be readable from the web. A 200 with an empty
    // body is fine for PHP files that exit at once.
    foreach ([
        '/includes/config.php', '/includes/admin_auth_store.php', '/includes/contact_messages.php',
        '/.qfa-installed', '/.git/HEAD', '/error_log', '/includes/error_log', '/cache/',
        '/api/quran/includes/', '/composer.json', '/.env', '/tests/config_writer_test.php',
    ] as $path) {
        $r = http_get($siteUrl . $path, false, 4096);
        $exposed = $r['status'] === 200 && trim($r['body']) !== '' && !preg_match('~<html|<!doctype~i', $r['body']);
        $listing = $r['status'] === 200 && stripos($r['body'], 'Index of') !== false;
        if ($r['status'] === 0) continue;
        line($exposed || $listing ? 'FAIL' : 'OK', $path . ' -> HTTP ' . $r['status'] . ($listing ? ' (directory listing!)' : ($exposed ? ' (content readable!)' : '')));
    }
    $studio = http_get($siteUrl . '/index.php?action=sadaqah_agent', false, 300000);
    $open = $studio['status'] === 200 && preg_match('~x-studio|sadaqah|الخطة الأسبوعية~iu', $studio['body']) && !preg_match('~admin-login~', $studio['headers']['location'] ?? '');
    line($open ? 'FAIL' : 'OK', 'X studio via index.php without login: ' . ($open ? 'OPEN to the public (fixed in v1.2.0)' : 'closed (HTTP ' . $studio['status'] . ')'));
    $inst = http_get($siteUrl . '/install.php', false, 20000);
    line(preg_match('~name="admin_password"~', $inst['body']) ? 'FAIL' : 'OK', 'install.php form reachable: ' . (preg_match('~name="admin_password"~', $inst['body']) ? 'YES' : 'no') . ' (HTTP ' . $inst['status'] . ')');
    $robots = http_get($siteUrl . '/robots.txt', false, 8192);
    if (preg_match('~Sitemap:\s*(\S+)~i', $robots['body'], $m)) line(stripos($m[1], $host) !== false ? 'OK' : 'WARN', 'robots.txt sitemap: ' . $m[1]);
}

/* ---------- scheduled jobs ---------- */

section('Scheduled jobs');
$cron = @shell_exec('crontab -l 2>/dev/null');
line('INFO', trim((string)$cron) === '' ? 'No cron jobs' : count(array_filter(explode("\n", trim((string)$cron)), static function ($l) { return $l !== '' && $l[0] !== '#'; })) . ' cron job(s):');
foreach (array_slice(array_filter(explode("\n", trim((string)$cron)), static function ($l) { return $l !== '' && $l[0] !== '#'; }), 0, 10) as $l) line('INFO', '  ' . preg_replace('~(pass(word)?|token|key)=\S+~i', '$1=***', $l));

echo "\nDone in " . round(microtime(true) - $started, 1) . "s: $problems problem(s), $warnings warning(s).\n";
echo "Nothing was changed. Copy everything above and send it back.\n";
