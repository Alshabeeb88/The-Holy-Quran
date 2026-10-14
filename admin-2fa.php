<?php
/*
 * Second sign-in step: the password was accepted by admin-login.php and the
 * administrator now enters the code from the authenticator app, or one of
 * the recovery codes. The pending state expires after five minutes.
 */
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/totp.php';
qfa_auth_start();
if (qfa_auth_logged_in()) { header('Location: admin.php'); exit; }

$pending = $_SESSION['qfa_2fa_pending'] ?? null;
$data = qfa_auth_read();
if (!is_array($pending)
    || time() - (int)($pending['at'] ?? 0) > 300
    || (int)($pending['epoch'] ?? -1) !== qfa_auth_epoch($data)
    || !qfa_totp_enabled($data)) {
    unset($_SESSION['qfa_2fa_pending']);
    header('Location: admin-login.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) $error = 'انتهت صلاحية النموذج. أعد المحاولة.';
    elseif (qfa_auth_rate_limited('totp', 6, 900)) {
        $error = qfa_auth_rate_reason() === 'storage'
            ? 'تعذر التحقق من المحاولات حاليًا لأسباب فنية. حاول مرة أخرى بعد قليل.'
            : 'محاولات كثيرة. انتظر 15 دقيقة ثم حاول مجددًا.';
    } else {
        $used = qfa_totp_consume((string)($_POST['code'] ?? ''));
        if ($used !== '') {
            $next = qfa_auth_safe_next((string)($pending['next'] ?? '/admin.php'));
            unset($_SESSION['qfa_2fa_pending']);
            session_regenerate_id(true);
            qfa_auth_mark_signed_in(qfa_auth_read());
            if ($used === 'recovery') {
                $_SESSION['qfa_recovery_used'] = true;
            }
            header('Location: ' . $next); exit;
        }
        $error = 'الرمز غير صحيح أو انتهت صلاحيته.';
    }
}

qfa_auth_page('التحقق بخطوتين', '<section class="auth-card"><div class="auth-icon"><i class="fas fa-mobile-alt"></i></div><span class="auth-eyebrow">الخطوة الثانية</span><h1>رمز التحقق</h1><p>أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة، أو أحد رموز الاسترداد.</p>'.($error?'<div class="auth-error">'.htmlspecialchars($error).'</div>':'').'<form method="post"><input type="hidden" name="csrf" value="'.qfa_auth_csrf().'"><label>الرمز<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="12" required autofocus></label><button type="submit">تأكيد</button></form><a class="auth-link" href="admin-logout.php">إلغاء</a></section>');
