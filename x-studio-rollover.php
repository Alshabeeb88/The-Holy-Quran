<?php
declare(strict_types=1);

/**
 * Rollover endpoint for the X publishing studio.
 *
 * Moves the studio on to the current week: the plan that is finishing is filed
 * in the archive, and only once that copy is on disk and verified is a fresh
 * week put in its place.
 *
 * It carries no data of its own. Which week is finishing, which week is next,
 * where the archive lives and what the new plan contains are all decided inside
 * the storage layer from the server's own clock and template, so a request
 * cannot name a week, a date, a time zone or a path. Nothing here writes to the
 * filesystem directly, and nothing here talks to X.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');

/**
 * The only way this file produces output: one JSON object with a stable "code"
 * for the interface to branch on. Nothing internal is exposed — no paths, no
 * validator details, no session or token values.
 */
function qfa_x_rollover_reply(int $status, bool $ok, string $code, string $message = '', array $extra = []): void {
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
    qfa_x_rollover_reply(405, false, 'METHOD_NOT_ALLOWED', 'طريقة الطلب غير مسموحة.');
}

/*
 * This request carries an intent and a token and nothing else, so the ceiling is
 * small. Anything larger is a mistake or an attempt to make the server work for
 * nothing.
 */
const QFA_X_ROLLOVER_MAX_BODY = 4096;
const QFA_X_ROLLOVER_MAX_FIELDS = 2;

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > QFA_X_ROLLOVER_MAX_BODY) {
    qfa_x_rollover_reply(422, false, 'INVALID_REQUEST', 'حجم الطلب أكبر من المسموح.');
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
    qfa_x_rollover_reply(401, false, 'AUTH_REQUIRED', 'انتهت الجلسة. سجّل الدخول ثم حاول مرة أخرى.');
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
        qfa_x_rollover_reply(403, false, 'ORIGIN_REJECTED', 'تعذر التحقق من مصدر الطلب.');
    }
}

// CSRF: shape first, so a malformed token never reaches the comparison.
$csrf = (string)($_POST['csrf'] ?? '');
if (preg_match('~^[0-9a-f]{48}$~', $csrf) !== 1 || !qfa_auth_check_csrf($csrf)) {
    qfa_x_rollover_reply(403, false, 'CSRF_FAILED', 'انتهت صلاحية الصفحة. أعد تحميلها ثم حاول مرة أخرى.');
}

// --- the request itself ----------------------------------------------------

if (count($_POST) > QFA_X_ROLLOVER_MAX_FIELDS) {
    qfa_x_rollover_reply(422, false, 'INVALID_REQUEST', 'الطلب يحتوي حقولًا أكثر من المسموح.');
}

/*
 * Two fields, named explicitly. This is where a force, a week_id, a date or a
 * path would have to appear, and none of them can: a field this file does not
 * name is an error by construction, not something filtered out later.
 */
$allowed = ['action', 'csrf'];
foreach (array_keys($_POST) as $field) {
    if (!in_array((string)$field, $allowed, true)) {
        qfa_x_rollover_reply(422, false, 'INVALID_REQUEST', 'الطلب يحتوي حقلًا غير معروف.');
    }
}
foreach ($allowed as $field) {
    if (!array_key_exists($field, $_POST) || !is_string($_POST[$field])) {
        qfa_x_rollover_reply(422, false, 'INVALID_REQUEST', 'الطلب ينقصه حقل مطلوب.');
    }
}

if ((string)$_POST['action'] !== 'rollover_week') {
    qfa_x_rollover_reply(422, false, 'INVALID_ACTION', 'العملية المطلوبة غير معروفة.');
}

// --- roll the week ---------------------------------------------------------

// Called with no arguments: everything it needs comes from the server itself.
$result = qfa_x_store_rollover_week();

switch ($result['status']) {
    case QFA_X_OK:
        qfa_x_rollover_reply(201, true, 'ROLLOVER_COMPLETE', '', [
            'archived_week_id' => $result['archived_week_id'],
            'week' => $result['week'],
            'revision' => $result['revision'],
        ]);

    /*
     * Nothing to roll. This is also the answer when a rollover already happened,
     * because the plan in place is then the current week, so a second request
     * cannot create anything.
     */
    case QFA_X_CURRENT_WEEK_ACTIVE:
        qfa_x_rollover_reply(409, false, 'CURRENT_WEEK_ACTIVE', 'الخطة الحالية تخص الأسبوع الجاري، ولا حاجة للأرشفة.');

    /*
     * A different plan is already filed under this week id. Both copies are left
     * exactly as they are: choosing between them is a person's decision.
     */
    case QFA_X_ARCHIVE_CONFLICT:
        qfa_x_rollover_reply(409, false, 'ARCHIVE_CONFLICT', 'يوجد أسبوع مؤرشف بنفس المعرّف ومحتواه مختلف. لم يُحذف أو يُستبدل شيء.');

    // Someone else changed the plan between reading it and replacing it.
    case QFA_X_CONFLICT:
        qfa_x_rollover_reply(409, false, 'REVISION_CONFLICT', 'تم تعديل الخطة من مكان آخر. أعد تحميل الصفحة.');

    case QFA_X_NOT_FOUND:
        qfa_x_rollover_reply(404, false, 'PLAN_NOT_FOUND', 'لا توجد خطة أسبوعية بعد.');

    case QFA_X_UNREADABLE:
        qfa_x_rollover_reply(500, false, 'STORE_UNREADABLE', 'تعذر قراءة الخطة.');

    case QFA_X_WRITE_FAILED:
        // Nothing was lost: the plan that was there is still there.
        qfa_x_rollover_reply(500, false, 'WRITE_FAILED', 'تعذر إتمام الأرشفة. لم تتغير الخطة الحالية.');

    default:
        // CORRUPT, INVALID or a schema this code does not understand. A plan
        // that cannot be read is never archived and never replaced.
        qfa_x_rollover_reply(500, false, 'STORE_CORRUPT', 'ملف الخطة تالف. لا يمكن الأرشفة حتى تتم مراجعته.');
}
