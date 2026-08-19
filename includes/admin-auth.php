<?php
declare(strict_types=1);

if (!is_file(dirname(__DIR__) . '/.qfa-installed') && PHP_SAPI !== 'cli') {
    header('Location: install.php', true, 302);
    exit;
}

require_once __DIR__ . '/config.php';
define('QFA_ADMIN_EMAIL', defined('ADMIN_EMAIL') ? (string)ADMIN_EMAIL : '');
const QFA_ADMIN_SESSION = 'qfa_admin_authenticated';
// Session cookie name, shared with the public bootstrap so that it can detect an
// existing session without having to open one.
const QFA_SESSION_NAME = 'qfa_admin';
const QFA_AUTH_FILE = __DIR__ . '/admin_auth_store.php';

function qfa_auth_start(): void {
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (in_array($script, ['admin.php','admin-login.php','forgot-password.php','reset-password.php','admin-logout.php','sadaqah-agent.php','admin-settings.php'], true)) {
        header('X-Robots-Tag: noindex, nofollow, noarchive', true);
        header('Cache-Control: no-store, private', true);
        header('X-Frame-Options: DENY', true);
        header('X-Content-Type-Options: nosniff', true);
        header('Referrer-Policy: no-referrer', true);
    }
    if (session_status() === PHP_SESSION_ACTIVE) return;

    /*
     * A session cannot be opened once output has started: the cookie could not
     * be sent, so the session would be useless and PHP would print a warning
     * into the page. Every caller that needs a session runs before any output;
     * this guard only keeps a late call from being noisy.
     */
    if (headers_sent()) return;

    /*
     * Secure cookie flag, derived only from signals a client cannot forge:
     *   - the real TLS state of this request (HTTPS, or port 443);
     *   - the scheme of SITE_URL, which the administrator configured.
     *
     * X-Forwarded-Proto is deliberately not trusted on its own, because any
     * client can send that header. When TLS is terminated by a proxy the
     * configured SITE_URL already states that the site is served over https.
     *
     * The loopback exception keeps local plain-HTTP testing working even when
     * SITE_URL points at an https production domain.
     */
    $httpsRequest = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    $siteIsHttps = defined('SITE_URL') && stripos(trim((string)SITE_URL), 'https://') === 0;
    $loopbackClient = in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
    $secure = $httpsRequest || ($siteIsHttps && !$loopbackClient);

    // Refuse session ids the application never issued, and never read the id
    // from the URL. Both defend the admin session against fixation.
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');

    session_name(QFA_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function qfa_auth_read(): array {
    if (!is_file(QFA_AUTH_FILE)) return [];
    $raw = (string)@file_get_contents(QFA_AUTH_FILE);
    $raw = preg_replace('/^<\?php\s+exit;\s*\?>\s*/', '', $raw);
    $data = json_decode((string)$raw, true);
    return is_array($data) ? $data : [];
}

function qfa_auth_write(array $data): bool {
    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );

    if ($json === false) {
        return false;
    }

    $payload = "<?php exit; ?>\n" . $json;
    $tmp = QFA_AUTH_FILE . '.tmp-' . bin2hex(random_bytes(5));

    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
        return false;
    }

    @chmod($tmp, 0640);

    if (!@rename($tmp, QFA_AUTH_FILE)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

function qfa_auth_csrf(): string {
    qfa_auth_start();
    if (empty($_SESSION['qfa_csrf'])) $_SESSION['qfa_csrf'] = bin2hex(random_bytes(24));
    return (string)$_SESSION['qfa_csrf'];
}

function qfa_auth_check_csrf(string $token): bool {
    qfa_auth_start();
    return isset($_SESSION['qfa_csrf']) && hash_equals((string)$_SESSION['qfa_csrf'], $token);
}

function qfa_auth_logged_in(): bool {
    /*
     * Answer without creating a session when there is nothing to resume. Every
     * public page asks this question in order to decide whether to draw the
     * admin shortcut, and a visitor with no session cookie is definitively not
     * signed in, so opening a session to discover that only issues a useless
     * cookie and leaves a session file behind for each anonymous request.
     */
    if (session_status() !== PHP_SESSION_ACTIVE && !isset($_COOKIE[QFA_SESSION_NAME])) {
        return false;
    }

    qfa_auth_start();
    return !empty($_SESSION[QFA_ADMIN_SESSION]);
}

function qfa_auth_require(): void {
    if (qfa_auth_logged_in()) return;
    $next = rawurlencode((string)($_SERVER['REQUEST_URI'] ?? '/admin.php'));
    header('Location: admin-login.php?next=' . $next, true, 302);
    exit;
}

function qfa_auth_safe_next(string $next): string {
    if (
        $next === '' ||
        $next[0] !== '/' ||
        strncmp($next, '//', 2) === 0 ||
        strpos($next, '\\') !== false
    ) {
        return '/admin.php';
    }

    $parts = parse_url($next);

    if (
        $parts === false ||
        isset($parts['scheme']) ||
        isset($parts['host'])
    ) {
        return '/admin.php';
    }

    return preg_match(
        '~^/[a-zA-Z0-9/_?&=.%-]*$~D',
        $next
    ) ? $next : '/admin.php';
}

function qfa_auth_local(): bool {
    if (PHP_SAPI !== 'cli-server') {
        return false;
    }

    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip === '127.0.0.1' || $ip === '::1';
}

function qfa_rate_dir(): string {
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
}

function qfa_auth_rate_file(string $kind): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return qfa_rate_dir() . DIRECTORY_SEPARATOR . 'qfa_auth_' . $kind . '_' . hash('sha256', $ip) . '.json';
}

/**
 * Remove this application's own stale rate-limit artefacts.
 *
 * Rate-limit counters used to accumulate in the temp directory forever, one
 * file per client address. The sweep below is deliberately narrow:
 *   - it matches only the two exact name patterns this project writes;
 *   - it never recurses and never removes a directory;
 *   - it skips symlinks, so it cannot delete through one;
 *   - it only removes a file whose last modification is older than $maxAge,
 *     which is far beyond the longest rate-limit window in use (30 minutes);
 *   - it stops after a small budget, so a request can never turn into a long
 *     filesystem sweep.
 *
 * Anything not written by this project is therefore out of reach.
 */
function qfa_rate_gc(int $maxAge = 86400, int $budget = 200): int {
    $removed = 0;
    $now = time();
    $dir = qfa_rate_dir();

    $patterns = [
        $dir . DIRECTORY_SEPARATOR . 'qfa_auth_*.json',
        $dir . DIRECTORY_SEPARATOR . 'qfa_contact_*.lock',
    ];

    foreach ($patterns as $pattern) {
        $files = glob($pattern, GLOB_NOSORT);

        if (!is_array($files)) {
            continue;
        }

        foreach ($files as $file) {
            if ($budget-- <= 0) {
                return $removed;
            }

            if (is_link($file) || !is_file($file)) {
                continue;
            }

            $mtime = @filemtime($file);

            if ($mtime === false || ($now - $mtime) <= $maxAge) {
                continue;
            }

            if (@unlink($file)) {
                $removed++;
            }
        }
    }

    return $removed;
}

/**
 * @param bool $failClosed What to report when the counter store is unusable.
 *                         Administrator endpoints leave this at true: refusing
 *                         one attempt is recoverable, silently allowing
 *                         unlimited password guesses is not. Public endpoints
 *                         should pass false so a broken temp directory can
 *                         never lock ordinary visitors out.
 */
/**
 * Why the most recent qfa_auth_rate_limited() call refused a request.
 *
 *   'limit'   the caller genuinely used up its allowance;
 *   'storage' the counter store could not be used and the check refused
 *             fail-closed rather than allow unlimited attempts.
 *
 * Kept separate so the interface can tell an honest "try again shortly" apart
 * from "you have tried too often", without either message revealing a path or
 * any other detail about the server.
 */
function qfa_auth_rate_reason(?string $set = null): string {
    static $reason = '';
    if ($set !== null) {
        $reason = $set;
    }
    return $reason;
}

function qfa_auth_rate_limited(string $kind, int $max, int $window, bool $failClosed = true): bool {
    qfa_auth_rate_reason('');

    if (qfa_auth_local()) {
        return false;
    }

    // Occasionally clear this project's expired counters. One request in fifty
    // pays a small, bounded cost instead of the directory growing without end.
    if (random_int(1, 50) === 1) {
        qfa_rate_gc();
    }

    $file = qfa_auth_rate_file($kind);
    $now = time();

    $handle = @fopen($file, 'c+');
    if (!$handle) {
        qfa_auth_rate_reason('storage');
        error_log('qfa: attempt counter unavailable (cannot open store) for kind=' . $kind);
        return $failClosed;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        qfa_auth_rate_reason('storage');
        error_log('qfa: attempt counter unavailable (cannot lock store) for kind=' . $kind);
        return $failClosed;
    }

    rewind($handle);
    $raw = stream_get_contents($handle);
    $decoded = json_decode((string)$raw, true);
    $attempts = is_array($decoded) ? $decoded : [];

    $attempts = array_values(array_filter(
        $attempts,
        static function ($time) use ($now, $window): bool {
            return is_int($time) && $time > ($now - $window);
        }
    ));

    $limited = count($attempts) >= $max;

    if ($limited) {
        qfa_auth_rate_reason('limit');
    }

    if (!$limited) {
        $attempts[] = $now;
        $json = json_encode($attempts);

        if ($json !== false) {
            rewind($handle);

            if (ftruncate($handle, 0)) {
                fwrite($handle, $json);
                fflush($handle);
                @chmod($file, 0600);
            }
        }
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    return $limited;
}

function qfa_auth_reset_password(string $token, string $newPassword): bool {
    if (strlen($token) !== 64 || strlen($newPassword) < 12) {
        return false;
    }

    $handle = @fopen(QFA_AUTH_FILE, 'c+');
    if (!$handle) {
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    rewind($handle);
    $raw = stream_get_contents($handle);
    $raw = preg_replace('/^<\?php\s+exit;\s*\?>\s*/', '', (string)$raw);

    $data = json_decode((string)$raw, true);

    if (!is_array($data)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    $storedHash = (string)($data['reset_token_hash'] ?? '');
    $expires = (int)($data['reset_expires'] ?? 0);

    $valid = (
        $storedHash !== '' &&
        $expires >= time() &&
        hash_equals($storedHash, hash('sha256', $token))
    );

    if (!$valid) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    /*
     * التوكن يُستهلك داخل نفس القفل قبل إنهاء العملية.
     * لذلك لا يمكن استخدام نفس الرابط مرة ثانية.
     */
    unset($data['reset_token_hash'], $data['reset_expires']);

    $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    $data['email'] = QFA_ADMIN_EMAIL;

    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );

    if ($json === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    $payload = "<?php exit; ?>\n" . $json;

    rewind($handle);

    if (!ftruncate($handle, 0)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    $written = fwrite($handle, $payload);
    fflush($handle);

    flock($handle, LOCK_UN);
    fclose($handle);

    return $written !== false && $written === strlen($payload);
}

function qfa_auth_send_reset(string $url): bool {
    $host = (string)(parse_url((string)SITE_URL, PHP_URL_HOST) ?: 'localhost');

    $fromEmail = defined('MAIL_FROM_EMAIL') && filter_var((string)MAIL_FROM_EMAIL, FILTER_VALIDATE_EMAIL)
        ? (string)MAIL_FROM_EMAIL
        : 'noreply@' . $host;

    $subjectText = 'استعادة كلمة مرور إدارة الموقع';
    $subject = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($subjectText, 'UTF-8')
        : $subjectText;

    $body = "السلام عليكم,\n\n"
        . "لتعيين أو تغيير كلمة مرور إدارة الموقع افتح الرابط التالي:\n{$url}\n\n"
        . "الرابط صالح لمدة 30 دقيقة ويُستخدم مرة واحدة.\n"
        . "إذا لم تطلب ذلك فتجاهل الرسالة.\n";

    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: Quran Website <{$fromEmail}>\r\n";

    return @mail(QFA_ADMIN_EMAIL, $subject, $body, $headers);
}

function qfa_auth_page(string $title, string $content): void {
    $darkScript = '<script src="style/default/js/admin-theme-init.js?v=1.0"></script>';
    echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</title>'.$darkScript.'<link rel="stylesheet" href="style/default/css/all.min.css"><link rel="stylesheet" href="style/default/css/admin-auth.css?v=1.1"></head><body><main class="auth-shell"><a class="auth-brand" href="/"><span><img src="style/default/images/quran-logo.png" alt="القرآن الكريم"></span><strong>القرآن الكريم</strong></a>'.$content.'</main></body></html>';
}
