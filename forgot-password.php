<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
qfa_auth_start();

$message = '';
$localLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) {
        $message = '<div class="auth-error">انتهت صلاحية النموذج. أعد المحاولة.</div>';

    } elseif (qfa_auth_rate_limited('reset', 4, 1800)) {
        // Refused either way, but say which so the administrator is not told to
        // wait out a limit that was never reached. Neither message reveals a
        // path or any other server detail.
        $message = qfa_auth_rate_reason() === 'storage'
            ? '<div class="auth-error">تعذر التحقق من الطلبات حاليًا لأسباب فنية. حاول مرة أخرى بعد قليل.</div>'
            : '<div class="auth-error">محاولات كثيرة. انتظر قليلًا ثم حاول مجددًا.</div>';

    } else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));

        if ($email === strtolower(QFA_ADMIN_EMAIL)) {

            $token = bin2hex(random_bytes(32));

            $data = qfa_auth_read();
            $data['reset_token_hash'] = hash('sha256', $token);
            $data['reset_expires'] = time() + 1800;
            $data['email'] = QFA_ADMIN_EMAIL;

            if (qfa_auth_write($data)) {
$url = rtrim((string)SITE_URL, '/')
    . '/reset-password.php?token='
    . rawurlencode($token);

                qfa_auth_send_reset($url);

                if (qfa_auth_local()) {
                    $_SESSION['qfa_local_reset_url'] = $url;
                }
            }
        }

        $_SESSION['qfa_reset_sent'] = true;

        header('Location: forgot-password.php?sent=1', true, 303);
        exit;
    }
}

if (
    isset($_GET['sent']) &&
    $_GET['sent'] === '1' &&
    !empty($_SESSION['qfa_reset_sent'])
) {
    unset($_SESSION['qfa_reset_sent']);

    $message = '<div class="auth-success">إذا كان البريد مطابقًا للحساب، فقد أرسلنا رابط الاستعادة.</div>';

    if (
        qfa_auth_local() &&
        !empty($_SESSION['qfa_local_reset_url'])
    ) {
        $url = (string)$_SESSION['qfa_local_reset_url'];
        unset($_SESSION['qfa_local_reset_url']);

        $localLink =
            '<a class="auth-test-link" href="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            . '">فتح رابط الاستعادة التجريبي</a>';
    }
}

$requestForm =
    '<form method="post" action="forgot-password.php">'
    . '<input type="hidden" name="csrf" value="' . qfa_auth_csrf() . '">'
    . '<label>البريد الإلكتروني'
    . '<input type="email" name="email" autocomplete="email" required>'
    . '</label>'
    . '<button type="submit">إرسال رابط الاستعادة</button>'
    . '</form>';

if (
    isset($_GET['sent']) &&
    $_GET['sent'] === '1'
) {
    $requestForm =
        '<a class="auth-link" href="forgot-password.php">طلب رابط استعادة جديد</a>';
}

qfa_auth_page(
    'استعادة كلمة المرور',
    '<section class="auth-card">'
    . '<div class="auth-icon"><i class="fas fa-envelope"></i></div>'
    . '<span class="auth-eyebrow">استعادة آمنة</span>'
    . '<h1>نسيت كلمة المرور؟</h1>'
    . '<p>سنرسل رابطًا صالحًا لمدة 30 دقيقة إلى البريد المسجل.</p>'
    . $message
    . $localLink
    . $requestForm
    . '<a class="auth-link" href="admin-login.php">العودة إلى تسجيل الدخول</a>'
    . '</section>'
);
