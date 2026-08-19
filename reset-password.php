<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
qfa_auth_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
header('Pragma: no-cache', true);
header('Expires: 0', true);

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
function qfa_reset_token_is_valid(string $token): bool {
    if (strlen($token) !== 64) {
        return false;
    }

    $data = qfa_auth_read();

    if (
        empty($data['reset_token_hash']) ||
        empty($data['reset_expires']) ||
        (int)$data['reset_expires'] < time()
    ) {
        return false;
    }

    return hash_equals(
        (string)$data['reset_token_hash'],
        hash('sha256', $token)
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) {
        $error = 'انتهت صلاحية النموذج. اطلب رابط استعادة جديدًا.';

    } elseif (strlen($password) < 12) {
        $error = 'استخدم كلمة مرور لا تقل عن 12 حرفًا.';

    } elseif ($password !== $confirm) {
        $error = 'كلمتا المرور غير متطابقتين.';

    } elseif (!qfa_auth_reset_password($token, $password)) {
        $error = 'الرابط غير صالح أو انتهت مدته أو سبق استخدامه.';

    } else {
header('Location: admin-login.php?reset=1', true, 303);
        exit;
    }
}

$valid = qfa_reset_token_is_valid($token);

if ($valid) {
    $body =
        '<form method="post" autocomplete="off">'
        . '<input type="hidden" name="csrf" value="' . qfa_auth_csrf() . '">'
        . '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">'
        . '<label>كلمة المرور الجديدة'
        . '<input type="password" name="password" autocomplete="new-password" minlength="12" required>'
        . '</label>'
        . '<label>تأكيد كلمة المرور'
        . '<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" required>'
        . '</label>'
        . '<button type="submit">حفظ كلمة المرور</button>'
        . '</form>';
} else {
    $body =
        '<div class="auth-error">الرابط غير صالح أو انتهت مدته أو سبق استخدامه.</div>'
        . '<a class="auth-link" href="forgot-password.php">طلب رابط جديد</a>';
}

qfa_auth_page(
    'تعيين كلمة المرور',
    '<section class="auth-card">'
    . '<div class="auth-icon"><i class="fas fa-key"></i></div>'
    . '<span class="auth-eyebrow">حماية الحساب</span>'
    . '<h1>تعيين كلمة المرور</h1>'
    . '<p>اختر كلمة قوية لا تقل عن 12 حرفًا.</p>'
    . ($error !== '' ? '<div class="auth-error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>' : '')
    . $body
    . '</section>'
);
