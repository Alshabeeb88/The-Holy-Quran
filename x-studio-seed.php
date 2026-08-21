<?php
declare(strict_types=1);

/**
 * Seed endpoint for the X publishing studio.
 *
 * Creates the plan for the current week, and only when there is none. It is
 * create-if-absent and nothing else: there is no reset, no repair, no replace
 * and no force switch, because an existing plan may hold work that only the
 * administrator can judge.
 *
 * Everything about what a week is stays in the storage layer: the time zone,
 * where the week starts and ends, the ISO identifier, the post ids and the
 * template itself. Nothing here is taken from the request but the intent to
 * create, so a caller cannot ask for a different week, a different template or
 * a different file.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');

/**
 * The only way this file produces output: one JSON object carrying a stable
 * "code" for the interface to branch on. Nothing internal is exposed, no paths,
 * no validator details, no session or token values.
 */
function qfa_x_seed_reply(int $status, bool $ok, string $code, string $message = '', array $extra = []): void {
    http_response_code($status);

    $body = ['ok' => $ok, 'code' => $code];
    if ($message !== '') {
        $body['message'] = $message;
    }

    echo json_encode($body + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

// A wrong method is refused before any file is loaded or any session touched.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    qfa_x_seed_reply(405, false, 'METHOD_NOT_ALLOWED', 'طريقة الطلب غير مسموحة.');
}

/*
 * This request carries an intent and a token and nothing else, so the ceiling
 * is small. Anything larger is a mistake or an attempt to make the server work
 * for nothing.
 */
const QFA_X_SEED_MAX_BODY = 4096;
const QFA_X_SEED_MAX_FIELDS = 2;

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > QFA_X_SEED_MAX_BODY) {
    qfa_x_seed_reply(422, false, 'INVALID_REQUEST', 'حجم الطلب أكبر من المسموح.');
}

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/x-studio-store.php';

/*
 * Admin session, checked without qfa_auth_require(): that helper answers with a
 * 302 to the login page, which a fetch() would follow and then read the login
 * HTML as if it were a successful reply. An unauthenticated caller gets a plain
 * 401 instead, and no redirect.
 */
if (!qfa_auth_logged_in()) {
    qfa_x_seed_reply(401, false, 'AUTH_REQUIRED', 'انتهت الجلسة. سجّل الدخول ثم حاول مرة أخرى.');
}

/*
 * Same-origin check against the configured site. Only SITE_URL is trusted for
 * this; the Host header is attacker-controlled and would defeat the purpose.
 * A missing Origin is not treated as failure on its own, because the CSRF token
 * and the SameSite=Strict session cookie remain the primary defence.
 */
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $requestHost = parse_url((string)$_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    $siteHost = defined('SITE_URL') ? parse_url((string)SITE_URL, PHP_URL_HOST) : null;

    if (!$requestHost || !$siteHost || strcasecmp((string)$requestHost, (string)$siteHost) !== 0) {
        qfa_x_seed_reply(403, false, 'ORIGIN_REJECTED', 'تعذر التحقق من مصدر الطلب.');
    }
}

// CSRF: shape first, so a malformed token never reaches the comparison.
$csrf = (string)($_POST['csrf'] ?? '');
if (preg_match('~^[0-9a-f]{48}$~', $csrf) !== 1 || !qfa_auth_check_csrf($csrf)) {
    qfa_x_seed_reply(403, false, 'CSRF_FAILED', 'انتهت صلاحية الصفحة. أعد تحميلها ثم حاول مرة أخرى.');
}

// --- the request itself ----------------------------------------------------

if (count($_POST) > QFA_X_SEED_MAX_FIELDS) {
    qfa_x_seed_reply(422, false, 'INVALID_REQUEST', 'الطلب يحتوي حقولًا أكثر من المسموح.');
}

/*
 * Two fields, named explicitly. Anything else is refused rather than ignored:
 * this is where a "force" or "week_id" parameter would have to appear, and it
 * cannot, because a field this file does not name is an error by construction.
 */
$allowed = ['action', 'csrf'];
foreach (array_keys($_POST) as $field) {
    if (!in_array((string)$field, $allowed, true)) {
        qfa_x_seed_reply(422, false, 'INVALID_REQUEST', 'الطلب يحتوي حقلًا غير معروف.');
    }
}
foreach ($allowed as $field) {
    if (!array_key_exists($field, $_POST) || !is_string($_POST[$field])) {
        qfa_x_seed_reply(422, false, 'INVALID_REQUEST', 'الطلب ينقصه حقل مطلوب.');
    }
}

if ((string)$_POST['action'] !== 'seed_week') {
    qfa_x_seed_reply(422, false, 'INVALID_ACTION', 'العملية المطلوبة غير معروفة.');
}

// --- create, if there is nothing there -------------------------------------

/*
 * Called with no arguments on purpose. The week, its dates, its identifier, the
 * post ids and the template all come from the storage layer, and the absence
 * check is that layer's compare-and-swap under an exclusive lock rather than a
 * look before the write, so two callers racing here cannot both create a plan.
 */
$seed = qfa_x_store_seed_week();

/** Small, safe summary of a plan. Never the posts themselves. */
function qfa_x_seed_week_summary(): array {
    $read = qfa_x_store_read();
    if ($read['status'] !== QFA_X_OK) {
        return [];
    }

    $plan = $read['data'];

    return [
        'week_id' => $plan['week']['week_id'],
        'start_date' => $plan['week']['start_date'],
        'end_date' => $plan['week']['end_date'],
        'status' => $plan['week']['status'],
        'revision' => (int)$plan['week']['revision'],
        'post_count' => count($plan['posts']),
    ];
}

switch ($seed['status']) {
    case QFA_X_OK:
        $summary = qfa_x_seed_week_summary();
        qfa_x_seed_reply(201, true, 'WEEK_CREATED', '', $summary === [] ? [] : ['week' => $summary]);

    case QFA_X_ALREADY_EXISTS:
        // Reported, not repaired: the plan that is there stays exactly as it is.
        $summary = qfa_x_seed_week_summary();
        if ($summary !== []) {
            unset($summary['post_count'], $summary['status']);
        }
        qfa_x_seed_reply(409, false, 'WEEK_ALREADY_EXISTS', 'توجد خطة لهذا الأسبوع بالفعل.',
            $summary === [] ? [] : ['week' => $summary]);

    case QFA_X_UNREADABLE:
        qfa_x_seed_reply(500, false, 'STORE_UNREADABLE', 'تعذر قراءة الخطة.');

    case QFA_X_WRITE_FAILED:
        // Nothing was written, so retrying is safe.
        qfa_x_seed_reply(500, false, 'WRITE_FAILED', 'تعذر إنشاء الخطة. حاول مرة أخرى.');

    default:
        // CORRUPT, INVALID or a schema this code does not understand. The file
        // is left untouched: a damaged plan is never overwritten by a new one.
        qfa_x_seed_reply(500, false, 'STORE_CORRUPT', 'ملف الخطة تالف. لا يمكن الإنشاء حتى تتم مراجعته.');
}
