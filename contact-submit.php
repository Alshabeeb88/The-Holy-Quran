<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'طريقة الطلب غير مسموحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/admin-auth.php';
qfa_auth_start();

function respond($ok, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function text_len($value) {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/**
 * Strip control characters from a value before it is stored or e-mailed.
 *
 * Single-line fields (name, e-mail, subject) lose every control character,
 * including CR and LF, so they can never be used to inject an extra mail
 * header or to forge a second record in the message log. The message body
 * keeps its paragraphs: CRLF and CR are normalised to LF, tabs survive, and
 * every other control character is removed. Runs of blank lines are collapsed
 * so a submission cannot pad the log with thousands of empty lines.
 */
function clean_text($value, $allowNewlines = false) {
    $value = (string)$value;

    if ($allowNewlines) {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        // Drop C0/C7F and the Unicode line/paragraph separators, keeping \n and \t.
        $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value);
        if ($value === null) {
            return '';
        }
        $value = preg_replace('/\n{3,}/', "\n\n", $value);
        if ($value === null) {
            return '';
        }
        // Trailing spaces on a line carry no meaning and only bloat the record.
        $value = preg_replace('/[ \t]+\n/', "\n", $value);
        return $value === null ? '' : trim($value);
    }

    $value = preg_replace('/\p{C}+/u', ' ', $value);
    if ($value === null) {
        return '';
    }
    $value = preg_replace('/\s{2,}/u', ' ', $value);

    return $value === null ? '' : trim($value);
}

/*
 * Retention policy for the stored contact log.
 *
 * Records older than the retention window, and any excess beyond the entry cap,
 * are moved into a companion archive file rather than being destroyed, so a
 * recent message is never dropped unexpectedly. A record whose timestamp is
 * missing or unparseable is never aged out on a guess: it is kept, and can only
 * ever be moved by the entry cap, which uses arrival order.
 */
const QFA_CONTACT_RETENTION_DAYS = 90;
const QFA_CONTACT_MAX_ENTRIES = 2000;
const QFA_CONTACT_SOFT_BYTES = 131072;   // 128 KB: above this, prune on write.
const QFA_CONTACT_ARCHIVE_BYTES = 2097152; // 2 MB ceiling for the archive itself.

function qfa_contact_guard(): string {
    return "<?php exit; ?>\n";
}

/**
 * Add lines to the archive and trim it from its oldest end, so the pair of files
 * can never grow without bound. Returns true only when the records are safely
 * on disk.
 *
 * The whole read-modify-write runs inside one held lock. Previously the append,
 * the size check and the rewrite each took their own lock, so two concurrent
 * rotations could both read the file and then overwrite each other's additions.
 */
function qfa_contact_archive(string $archiveFile, array $lines): bool {
    if ($lines === []) {
        return true;
    }

    // 'c+' creates the file when missing and never truncates an existing one.
    $handle = @fopen($archiveFile, 'c+');
    if (!$handle) {
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    rewind($handle);
    $raw = (string)stream_get_contents($handle);
    $body = preg_replace('/^<\?php\s+exit;\s*\?>\s*/', '', $raw);
    $kept = array_values(array_filter(explode("\n", (string)$body), 'strlen'));

    foreach ($lines as $line) {
        $kept[] = $line;
    }

    // Drop from the oldest end until back under the ceiling. The running total
    // avoids re-joining the whole archive on every iteration.
    $total = 0;
    foreach ($kept as $line) {
        $total += strlen($line) + 1;
    }
    while ($kept !== [] && $total > QFA_CONTACT_ARCHIVE_BYTES) {
        $total -= strlen((string)array_shift($kept)) + 1;
    }

    $payload = qfa_contact_guard() . ($kept === [] ? '' : implode("\n", $kept) . "\n");

    $written = false;
    rewind($handle);
    if (ftruncate($handle, 0)) {
        $written = fwrite($handle, $payload) !== false;
        fflush($handle);
    }

    flock($handle, LOCK_UN);
    fclose($handle);
    @chmod($archiveFile, 0640);

    return $written;
}

/**
 * Enforce the retention window and the entry cap on the live log.
 * Returns the number of records moved out.
 */
function qfa_contact_prune(string $logFile, string $archiveFile): int {
    $handle = @fopen($logFile, 'c+');
    if (!$handle) {
        return 0;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return 0;
    }

    rewind($handle);
    $raw = (string)stream_get_contents($handle);
    $body = preg_replace('/^<\?php\s+exit;\s*\?>\s*/', '', $raw);
    $lines = array_values(array_filter(explode("\n", (string)$body), 'strlen'));

    $cutoff = time() - (QFA_CONTACT_RETENTION_DAYS * 86400);
    $keep = [];
    $moved = [];

    foreach ($lines as $line) {
        $record = json_decode($line, true);
        $stamp = is_array($record) && isset($record['time']) && is_string($record['time'])
            ? strtotime($record['time'])
            : false;

        // No usable timestamp means no guessing: the record stays.
        if ($stamp !== false && $stamp < $cutoff) {
            $moved[] = $line;
            continue;
        }

        $keep[] = $line;
    }

    // Arrival order is chronological, so the surplus is taken from the front.
    $surplus = count($keep) - QFA_CONTACT_MAX_ENTRIES;
    if ($surplus > 0) {
        $moved = array_merge($moved, array_slice($keep, 0, $surplus));
        $keep = array_slice($keep, $surplus);
    }

    if ($moved === []) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return 0;
    }

    /*
     * Write the archive before shortening the live log, while still holding the
     * live-log lock. The previous order released the lock and archived
     * afterwards, so a crash in between left the moved records in neither file.
     * This way the worst case is a duplicate, which is recoverable, instead of
     * a silent loss. The lock is always taken live-log first, then archive, so
     * the two files cannot deadlock against each other.
     */
    if (!qfa_contact_archive($archiveFile, $moved)) {
        // Nothing was preserved, so leave the live log exactly as it is.
        flock($handle, LOCK_UN);
        fclose($handle);
        return 0;
    }

    $payload = qfa_contact_guard() . ($keep === [] ? '' : implode("\n", $keep) . "\n");

    rewind($handle);
    if (ftruncate($handle, 0)) {
        fwrite($handle, $payload);
        fflush($handle);
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    return count($moved);
}

// CSRF protection for the public contact form.
$csrf = (string)($_POST['csrf'] ?? '');

if (!qfa_auth_check_csrf($csrf)) {
    respond(false, 'انتهت صلاحية النموذج. أعد تحميل الصفحة ثم حاول مرة أخرى.', 403);
}

// Same-origin check against the configured site URL.
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $originHost = parse_url((string)$_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    $siteHost = parse_url((string)SITE_URL, PHP_URL_HOST);

    if (
        !$originHost ||
        !$siteHost ||
        strcasecmp((string)$originHost, (string)$siteHost) !== 0
    ) {
        respond(false, 'تعذر التحقق من مصدر الطلب.', 403);
    }
}

// Honeypot for simple bots.
if (!empty($_POST['website'])) {
    respond(true, 'تم استلام رسالتك بنجاح.');
}

$name = clean_text(strip_tags((string)($_POST['name'] ?? '')));
$email = clean_text((string)($_POST['email'] ?? ''));
$subjectInput = clean_text(strip_tags((string)($_POST['subject'] ?? '')));
$message = clean_text(strip_tags((string)($_POST['message'] ?? '')), true);

if ($name === '' || text_len($name) < 2 || text_len($name) > 80) {
    respond(false, 'يرجى كتابة اسم صحيح.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || text_len($email) > 120) {
    respond(false, 'يرجى كتابة بريد إلكتروني صحيح.', 422);
}
if ($subjectInput === '' || text_len($subjectInput) < 3 || text_len($subjectInput) > 120) {
    respond(false, 'يرجى كتابة موضوع بين 3 و120 حرفًا.', 422);
}
if ($message === '' || text_len($message) < 5 || text_len($message) > 2000) {
    respond(false, 'يرجى كتابة رسالة بين 5 و2000 حرف.', 422);
}

// Lightweight rate limit: one submission every 45 seconds per IP.
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');

/*
 * Occasionally clear this project's own expired rate-limit artefacts, so the
 * per-address lock files stop accumulating in the temp directory. The sweep is
 * defined in includes/admin-auth.php and only ever matches this project's own
 * file-name patterns. Failure here is irrelevant to the visitor, hence the
 * silent, bounded call. This endpoint is public, so it stays fail-open: a
 * broken temp directory must never stop a visitor from writing in.
 */
if (function_exists('qfa_rate_gc') && random_int(1, 50) === 1) {
    qfa_rate_gc();
}

$rateFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR
    . 'qfa_contact_'
    . hash('sha256', $ip)
    . '.lock';

$now = time();
$rateHandle = @fopen($rateFile, 'c+');

if ($rateHandle) {
    if (flock($rateHandle, LOCK_EX)) {
        rewind($rateHandle);
        $last = (int)stream_get_contents($rateHandle);

        if ($last > 0 && ($now - $last) < 45) {
            flock($rateHandle, LOCK_UN);
            fclose($rateHandle);

            respond(
                false,
                'تم إرسال رسالة قبل قليل. يرجى الانتظار ثم المحاولة مرة أخرى.',
                429
            );
        }

        rewind($rateHandle);

        if (ftruncate($rateHandle, 0)) {
            fwrite($rateHandle, (string)$now);
            fflush($rateHandle);
            @chmod($rateFile, 0600);
        }

        flock($rateHandle, LOCK_UN);
    }

    fclose($rateHandle);
}

$record = [
    'time' => date('c'),
    'name' => $name,
    'email' => $email,
    'subject' => $subjectInput,
    'message' => $message,
    'ip_hash' => hash('sha256', $ip),
    'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300)
];

$stored = true;
if (defined('CONTACT_STORE_MESSAGES') && CONTACT_STORE_MESSAGES) {
    /*
     * The log holds visitor names, e-mail addresses and message bodies. It is a
     * .php file whose first line exits, so a direct HTTP request returns nothing
     * even where the web-server deny rules do not apply, for example on Nginx.
     * This mirrors the protection already used for admin_auth_store.php.
     */
    $logFile = __DIR__ . '/includes/contact_messages.php';

    if (!is_file($logFile)) {
        // Create-exclusive, so two concurrent submissions cannot both seed the file.
        $seed = @fopen($logFile, 'x');
        if ($seed !== false) {
            fwrite($seed, "<?php exit; ?>\n");
            fclose($seed);
            @chmod($logFile, 0640);
        }
    }

    $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $stored = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) !== false;

    /*
     * Apply retention after the message is safely on disk, so pruning can never
     * cost the visitor their submission. It runs whenever the log has grown past
     * the soft size, and occasionally otherwise, so the age window is still
     * enforced on a quiet site that never reaches that size.
     */
    if ($stored) {
        clearstatcache(true, $logFile);
        if ((int)@filesize($logFile) > QFA_CONTACT_SOFT_BYTES || random_int(1, 25) === 1) {
            qfa_contact_prune($logFile, __DIR__ . '/includes/contact_messages-archive.php');
        }
    }
}

$emailed = false;
$contactEmail = defined('CONTACT_EMAIL') ? trim(CONTACT_EMAIL) : '';
if ($contactEmail !== '' && filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
    $prefix = defined('CONTACT_SUBJECT') ? trim(CONTACT_SUBJECT) : '';
    $subjectText = $prefix !== '' ? $prefix . ' - ' . $subjectInput : $subjectInput;
    $subject = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($subjectText, 'UTF-8') : $subjectText;
    $host = (string)(parse_url((string)SITE_URL, PHP_URL_HOST) ?: 'localhost');
    $host = preg_replace('/^www\\./i', '', $host);
    $configuredFrom = defined('MAIL_FROM_EMAIL') ? trim(MAIL_FROM_EMAIL) : '';
    $from = filter_var($configuredFrom, FILTER_VALIDATE_EMAIL) ? $configuredFrom : ('noreply@' . $host);
    $body = "رسالة جديدة من نموذج التواصل\n\n";
    $body .= "الاسم: {$name}\n";
    $body .= "البريد الإلكتروني: {$email}\n";
    $body .= "الموضوع: {$subjectInput}\n\n";
    $body .= "الرسالة:\n{$message}\n\n";
    $body .= "وقت الإرسال: " . date('Y-m-d H:i:s') . "\n";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: Quran Website <{$from}>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $emailed = filter_var($from, FILTER_VALIDATE_EMAIL) ? @mail($contactEmail, $subject, $body, $headers, '-f ' . $from) : @mail($contactEmail, $subject, $body, $headers);
}

if (!$stored && !$emailed) {
    respond(false, 'تعذر حفظ الرسالة حاليًا. حاول مرة أخرى لاحقًا.', 500);
}

respond(true, 'تم استلام رسالتك بنجاح، شكرًا لتواصلك.');
