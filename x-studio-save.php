<?php
declare(strict_types=1);

/**
 * Write endpoint for the X publishing studio.
 *
 * Applies one small, named change to one post of the stored weekly plan. It
 * owns no storage logic of its own: validation of the plan, locking, the
 * atomic replacement and the revision check all live in the storage layer, and
 * this file only decides whether a request is allowed and what it is asking for.
 *
 * It talks to nothing outside this server: no API, no OAuth, no network call.
 * Publishing to X remains something the administrator does by hand.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');

/**
 * The only way this file produces output. Everything it can say is one JSON
 * object with a stable "code" the interface can branch on; "message" is Arabic
 * prose meant for a human and is never a contract.
 *
 * Nothing internal is exposed here: no paths, no validator details, no session
 * or token values.
 */
function qfa_x_reply(int $status, bool $ok, string $code, string $message = '', array $extra = []): void {
    http_response_code($status);

    $body = ['ok' => $ok, 'code' => $code];
    if ($message !== '') {
        $body['message'] = $message;
    }

    echo json_encode($body + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * The part of a post the interface is told about: what it shows and what it
 * needs to decide which controls belong on screen. Never the whole plan.
 */
function qfa_x_post_payload(array $post): array {
    return [
        'post_id' => $post['post_id'],
        'text' => $post['text'],
        'approved' => $post['approved'],
        'approved_at' => $post['approved_at'],
        'published' => $post['published'],
        'published_at' => $post['published_at'],
    ];
}

// A wrong method is refused before any file is loaded or any session touched.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    qfa_x_reply(405, false, 'METHOD_NOT_ALLOWED', 'طريقة الطلب غير مسموحة.');
}

/*
 * Refuse an oversized body before looking at it. The largest legitimate request
 * carries one post's text, so anything approaching this size is a mistake or an
 * attempt to make the server work for nothing.
 */
const QFA_X_MAX_BODY = 65536;
const QFA_X_MAX_FIELDS = 6;

$declaredLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($declaredLength > QFA_X_MAX_BODY) {
    qfa_x_reply(422, false, 'INVALID_REQUEST', 'حجم الطلب أكبر من المسموح.');
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
    qfa_x_reply(401, false, 'AUTH_REQUIRED', 'انتهت الجلسة. سجّل الدخول ثم حاول مرة أخرى.');
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
        qfa_x_reply(403, false, 'ORIGIN_REJECTED', 'تعذر التحقق من مصدر الطلب.');
    }
}

// CSRF: shape first, so a malformed token never reaches the comparison.
$csrf = (string)($_POST['csrf'] ?? '');
if (preg_match('~^[0-9a-f]{48}$~', $csrf) !== 1 || !qfa_auth_check_csrf($csrf)) {
    qfa_x_reply(403, false, 'CSRF_FAILED', 'انتهت صلاحية الصفحة. أعد تحميلها ثم حاول مرة أخرى.');
}

// --- the request itself ----------------------------------------------------

if (count($_POST) > QFA_X_MAX_FIELDS) {
    qfa_x_reply(422, false, 'INVALID_REQUEST', 'الطلب يحتوي حقولًا أكثر من المسموح.');
}

$action = (string)($_POST['action'] ?? '');
if (strlen($action) > 24 || preg_match('~^[a-z_]+$~', $action) !== 1) {
    qfa_x_reply(422, false, 'INVALID_REQUEST', 'الطلب غير صالح.');
}

/*
 * Every field each action accepts, named explicitly. Anything else is refused
 * rather than ignored: a silently dropped field hides a bug on the caller's
 * side, and reading whatever arrives is how mass assignment happens.
 */
$allowedFields = [
    'save_post' => ['action', 'csrf', 'post_id', 'expected_revision', 'text'],
    'approve_post' => ['action', 'csrf', 'post_id', 'expected_revision'],
    // Recording that a post went out carries no data of its own: the moment is
    // taken from the server clock, so there is nothing for the caller to send.
    'mark_published' => ['action', 'csrf', 'post_id', 'expected_revision'],
];

if (!isset($allowedFields[$action])) {
    qfa_x_reply(422, false, 'INVALID_ACTION', 'العملية المطلوبة غير معروفة.');
}

$allowed = $allowedFields[$action];
foreach (array_keys($_POST) as $field) {
    if (!in_array((string)$field, $allowed, true)) {
        qfa_x_reply(422, false, 'INVALID_REQUEST', 'الطلب يحتوي حقلًا غير معروف.');
    }
}
foreach ($allowed as $field) {
    if (!array_key_exists($field, $_POST) || !is_string($_POST[$field])) {
        qfa_x_reply(422, false, 'INVALID_REQUEST', 'الطلب ينقصه حقل مطلوب.');
    }
}

$postId = (string)$_POST['post_id'];
if (strlen($postId) > 32 || preg_match('~^\d{4}-W\d{2}-\d{2}$~', $postId) !== 1) {
    qfa_x_reply(422, false, 'INVALID_REQUEST', 'معرّف المنشور غير صالح.');
}

/*
 * The revision is checked as text before it becomes a number: intval('abc') is
 * 0, which would quietly turn a nonsense value into a plausible revision.
 */
$revisionRaw = (string)$_POST['expected_revision'];
if (preg_match('~^\d{1,9}$~', $revisionRaw) !== 1) {
    qfa_x_reply(422, false, 'INVALID_REQUEST', 'رقم النسخة غير صالح.');
}
$expectedRevision = (int)$revisionRaw;

$text = null;
if ($action === 'save_post') {
    $text = (string)$_POST['text'];

    if (preg_match('//u', $text) !== 1) {
        qfa_x_reply(422, false, 'INVALID_REQUEST', 'النص يحتوي ترميزًا غير صالح.');
    }
    if (trim($text) === '') {
        qfa_x_reply(422, false, 'INVALID_REQUEST', 'النص لا يمكن أن يكون فارغًا.');
    }
    // Counted the same way the storage layer counts, so the two cannot disagree.
    if (qfa_x_text_length($text) > QFA_X_MAX_TEXT) {
        qfa_x_reply(422, false, 'INVALID_REQUEST', 'النص أطول من ' . QFA_X_MAX_TEXT . ' حرفًا.');
    }
}

// --- the stored plan -------------------------------------------------------

$read = qfa_x_store_read();

switch ($read['status']) {
    case QFA_X_OK:
        break;

    /*
     * A missing plan is not created here. Seeding is a separate, deliberate
     * step; quietly generating a week inside a save request would mean the
     * administrator's edit landed on a plan they never saw.
     */
    case QFA_X_NOT_FOUND:
        qfa_x_reply(404, false, 'PLAN_NOT_FOUND', 'لا توجد خطة أسبوعية بعد.');
        // no break: qfa_x_reply exits

    case QFA_X_UNREADABLE:
        qfa_x_reply(500, false, 'STORE_UNREADABLE', 'تعذر قراءة الخطة.');

    default:
        // CORRUPT, INVALID or a schema this code does not understand. Nothing is
        // written, so a damaged file is never made worse by a save.
        qfa_x_reply(500, false, 'STORE_CORRUPT', 'ملف الخطة تالف. لا يمكن الحفظ حتى تتم مراجعته.');
}

$plan = $read['data'];
$currentRevision = (int)$plan['week']['revision'];

// Fail fast on a stale revision. The storage layer checks this again under its
// own lock, which is what actually makes it safe; this only saves the work.
if ($currentRevision !== $expectedRevision) {
    qfa_x_reply(409, false, 'REVISION_CONFLICT', 'تم تعديل الخطة من مكان آخر. أعد تحميل الصفحة.', [
        'current_revision' => $currentRevision,
    ]);
}

// The post is found by its id alone: an index or a day/time pair sent by the
// caller would let a stale page address the wrong post.
$index = null;
foreach ($plan['posts'] as $position => $candidate) {
    if (($candidate['post_id'] ?? null) === $postId) {
        $index = $position;
        break;
    }
}
if ($index === null) {
    qfa_x_reply(404, false, 'POST_NOT_FOUND', 'المنشور غير موجود في الخطة.');
}

$post = $plan['posts'][$index];

// --- apply the one change --------------------------------------------------

if ($action === 'save_post') {
    /*
     * A published post is a record of what actually went out. Editing its text
     * afterwards would leave the plan claiming something was published that
     * never was, so the administrator has to withdraw the published mark first.
     */
    if (($post['published'] ?? false) === true) {
        qfa_x_reply(409, false, 'RULE_VIOLATION', 'لا يمكن تعديل منشور مسجل على أنه منشور. ألغِ حالة النشر أولًا.');
    }

    /*
     * Only the text moves. Approval is withdrawn because it was given to the
     * previous wording, and keeping it would make "معتمدة" describe a text
     * nobody approved. The client's own approval fields are never read.
     */
    $plan['posts'][$index]['text'] = $text;
    $plan['posts'][$index]['approved'] = false;
    $plan['posts'][$index]['approved_at'] = null;
} elseif ($action === 'approve_post') {
    /*
     * Approving something already approved changes nothing, so it is reported
     * as success without a write. That keeps a double click from burning a
     * revision and turning the second click into a conflict.
     */
    if (($post['approved'] ?? false) === true) {
        qfa_x_reply(200, true, 'OK', '', [
            'revision' => $currentRevision,
            'post' => qfa_x_post_payload($post),
        ]);
    }

    $plan['posts'][$index]['approved'] = true;
    $plan['posts'][$index]['approved_at'] = qfa_x_now();   // server clock only
} else {
    /*
     * mark_published records that the administrator published this post by hand
     * on X. It is a statement about something that already happened, which is
     * why nothing here can infer it: opening the Web Intent composer proves only
     * that the composer opened, so only an explicit act can set this.
     */
    if (($post['approved'] ?? false) !== true) {
        // Approval is the review gate. Recording a post as published without it
        // would let unreviewed wording be logged as having gone out.
        qfa_x_reply(409, false, 'RULE_VIOLATION', 'يجب اعتماد المنشور قبل تسجيله كمنشور.');
    }

    /*
     * Already recorded: report success without writing, so a second click
     * neither burns a revision nor moves the published_at that was recorded the
     * first time. That timestamp is the historical fact and must not drift.
     */
    if (($post['published'] ?? false) === true) {
        qfa_x_reply(200, true, 'OK', '', [
            'revision' => $currentRevision,
            'post' => qfa_x_post_payload($post),
        ]);
    }

    $plan['posts'][$index]['published'] = true;
    $plan['posts'][$index]['published_at'] = qfa_x_now();   // server clock only
}

$write = qfa_x_store_write($plan, $expectedRevision);

switch ($write['status']) {
    case QFA_X_OK:
        qfa_x_reply(200, true, 'POST_UPDATED', '', [
            'revision' => $write['revision'],
            'post' => qfa_x_post_payload($plan['posts'][$index]),
        ]);

    case QFA_X_CONFLICT:
        $extra = [];
        if ($write['revision'] !== null) {
            $extra['current_revision'] = (int)$write['revision'];
        }
        qfa_x_reply(409, false, 'REVISION_CONFLICT', 'تم تعديل الخطة من مكان آخر. أعد تحميل الصفحة.', $extra);

    case QFA_X_UNREADABLE:
        qfa_x_reply(500, false, 'STORE_UNREADABLE', 'تعذر قراءة الخطة.');

    case QFA_X_WRITE_FAILED:
        // The plan was left untouched, so retrying is safe.
        qfa_x_reply(500, false, 'WRITE_FAILED', 'تعذر حفظ التغيير. حاول مرة أخرى.');

    default:
        qfa_x_reply(500, false, 'STORE_CORRUPT', 'ملف الخطة تالف. لا يمكن الحفظ حتى تتم مراجعته.');
}
