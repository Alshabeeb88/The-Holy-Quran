<?php
require_once __DIR__ . '/includes/admin-auth.php';
qfa_auth_start();
if (qfa_auth_logged_in()) { header('Location: admin.php'); exit; }
$error = '';
$next = qfa_auth_safe_next((string)($_GET['next'] ?? $_POST['next'] ?? '/admin.php'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) $error = 'انتهت صلاحية النموذج. أعد المحاولة.';
    elseif (qfa_auth_rate_limited('login', 6, 900)) {
        // Refused either way, but say which: telling the administrator to wait
        // out a limit that was never reached would send them down the wrong
        // path. Neither message discloses a filesystem path or server detail.
        $error = qfa_auth_rate_reason() === 'storage'
            ? 'تعذر التحقق من محاولات الدخول حاليًا لأسباب فنية. حاول مرة أخرى بعد قليل.'
            : 'محاولات كثيرة. انتظر 15 دقيقة ثم حاول مجددًا.';
    }
    else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $data = qfa_auth_read();
        if ($email === strtolower(QFA_ADMIN_EMAIL) && !empty($data['password_hash']) && password_verify($password, (string)$data['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION[QFA_ADMIN_SESSION] = true;
            $_SESSION['qfa_admin_email'] = QFA_ADMIN_EMAIL;
            header('Location: ' . $next); exit;
        }
        $error = 'بيانات الدخول غير صحيحة.';
    }
}
$notice = isset($_GET['reset']) ? '<div class="auth-success">تم تحديث كلمة المرور. يمكنك تسجيل الدخول الآن.</div>' : '';
qfa_auth_page('دخول المدير', '<section class="auth-card"><div class="auth-icon"><i class="fas fa-lock"></i></div><span class="auth-eyebrow">منطقة خاصة</span><h1>دخول مدير الوكيل</h1><p>هذه الصفحة خاصة بصاحب الحساب فقط.</p>'.$notice.($error?'<div class="auth-error">'.htmlspecialchars($error).'</div>':'').'<form method="post"><input type="hidden" name="csrf" value="'.qfa_auth_csrf().'"><input type="hidden" name="next" value="'.htmlspecialchars($next,ENT_QUOTES).'"><label>البريد الإلكتروني<input type="email" name="email" autocomplete="username" required></label><label>كلمة المرور<input type="password" name="password" autocomplete="current-password" required></label><button type="submit">تسجيل الدخول</button></form><a class="auth-link" href="forgot-password.php">نسيت كلمة المرور أو تريد إنشاءها أول مرة؟</a></section>');
