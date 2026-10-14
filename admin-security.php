<?php
declare(strict_types=1);

/*
 * Two-step sign-in for the administrator: switch it on with an authenticator
 * app, replace the recovery codes, or switch it off. Every change asks for the
 * current password, and switching on or off signs out all other sessions.
 */

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/totp.php';
qfa_auth_require();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Store a change made by $change and, when it ends other sessions, keep this
 * one signed in under the new epoch (as a password change does).
 */
function qfa_security_save(callable $change, bool $signOutOthers): bool {
    $saved = null;
    $ok = qfa_auth_update(static function (array $data) use ($change, $signOutOthers, &$saved) {
        $data = $change($data);
        if ($signOutOthers) {
            $data['session_epoch'] = qfa_auth_epoch($data) + 1;
        }
        $saved = $data;
        return $data;
    });
    if ($ok && $signOutOthers && is_array($saved)) {
        session_regenerate_id(true);
        $_SESSION['qfa_epoch'] = qfa_auth_epoch($saved);
    }
    return $ok;
}

$data = qfa_auth_read();
$enabled = qfa_totp_enabled($data);
$error = '';
$success = '';
$showCodes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $code = (string)($_POST['code'] ?? '');
    $passwordOk = !empty($data['password_hash']) && password_verify($password, (string)$data['password_hash']);

    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة.';
    } elseif (qfa_auth_rate_limited('security', 8, 900)) {
        $error = 'محاولات كثيرة. انتظر 15 دقيقة ثم حاول مجددًا.';
    } elseif (!$passwordOk) {
        $error = 'كلمة المرور الحالية غير صحيحة.';
    } elseif ($action === 'enable' && !$enabled) {
        $secret = (string)($_SESSION['qfa_2fa_setup'] ?? '');
        $step = $secret !== '' ? qfa_totp_match($secret, $code) : null;
        if ($step === null) {
            $error = 'الرمز غير صحيح. تأكد من إضافة الحساب في التطبيق ومن ضبط وقت الجوال تلقائيًا.';
        } else {
            [$plain, $hashes] = qfa_recovery_codes_new();
            $ok = qfa_security_save(static function (array $d) use ($secret, $step, $hashes) {
                $d['totp'] = ['secret' => $secret, 'last_step' => $step, 'recovery' => $hashes, 'enabled_at' => gmdate('c')];
                return $d;
            }, true);
            if ($ok) {
                unset($_SESSION['qfa_2fa_setup']);
                $showCodes = $plain;
                $success = 'تم تفعيل التحقق بخطوتين، وسُجّل الخروج من الأجهزة الأخرى.';
            } else {
                $error = 'تعذر حفظ الإعداد. تحقق من صلاحيات مجلد includes.';
            }
        }
    } elseif ($action === 'disable' && $enabled) {
        if (qfa_totp_consume($code) === '') {
            $error = 'رمز التحقق غير صحيح.';
        } elseif (qfa_security_save(static function (array $d) { unset($d['totp']); return $d; }, true)) {
            $success = 'تم إيقاف التحقق بخطوتين.';
        } else {
            $error = 'تعذر حفظ الإعداد.';
        }
    } elseif ($action === 'recovery' && $enabled) {
        if (qfa_totp_consume($code) === '') {
            $error = 'رمز التحقق غير صحيح.';
        } else {
            [$plain, $hashes] = qfa_recovery_codes_new();
            if (qfa_security_save(static function (array $d) use ($hashes) { $d['totp']['recovery'] = $hashes; return $d; }, false)) {
                $showCodes = $plain;
                $success = 'تم إنشاء رموز استرداد جديدة، والقديمة لم تعد تعمل.';
            } else {
                $error = 'تعذر حفظ الإعداد.';
            }
        }
    }
    $data = qfa_auth_read();
    $enabled = qfa_totp_enabled($data);
}

$csrf = e(qfa_auth_csrf());
$passwordField = '<label>كلمة المرور الحالية<input type="password" name="password" autocomplete="current-password" required></label>';
$codeField = '<label>رمز التطبيق (6 أرقام)<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="12" required></label>';

$codesBlock = '';
if ($showCodes) {
    $codesBlock = '<div class="recovery-box"><strong>رموز الاسترداد (تظهر مرة واحدة فقط)</strong>'
        . '<p>احفظها في مكان آمن خارج الجوال. كل رمز يُستخدم مرة واحدة إذا فقدت الجوال.</p>'
        . '<ol dir="ltr">' . implode('', array_map(static function ($c) { return '<li><code>' . e($c) . '</code></li>'; }, $showCodes)) . '</ol></div>';
}

if ($enabled) {
    $remaining = is_array($data['totp']['recovery'] ?? null) ? count($data['totp']['recovery']) : 0;
    $since = (string)($data['totp']['enabled_at'] ?? '');
    $warnRecovery = !empty($_SESSION['qfa_recovery_used']) || $remaining <= 3;
    $panel = '
    <div class="settings-success">مفعّل' . ($since !== '' ? ' منذ ' . e(substr($since, 0, 10)) : '') . '. رموز الاسترداد المتبقية: ' . $remaining . '.</div>
    ' . ($warnRecovery ? '<div class="settings-error">' . ($remaining <= 3 ? 'رموز الاسترداد قاربت على النفاد. أنشئ رموزًا جديدة.' : 'سجّلت الدخول برمز استرداد. إذا فقدت الجوال، أعد ربط التطبيق.') . '</div>' : '') . '
    ' . $codesBlock . '
    <form method="post" class="security-form">
      <input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="action" value="recovery">
      <h3>رموز استرداد جديدة</h3>' . $passwordField . $codeField . '
      <button type="submit">إنشاء رموز جديدة</button>
    </form>
    <form method="post" class="security-form">
      <input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="action" value="disable">
      <h3>إيقاف التحقق بخطوتين</h3>' . $passwordField . $codeField . '
      <button type="submit" class="danger">إيقاف</button>
    </form>';
} else {
    if (empty($_SESSION['qfa_2fa_setup'])) {
        $_SESSION['qfa_2fa_setup'] = qfa_totp_new_secret();
    }
    $secret = (string)$_SESSION['qfa_2fa_setup'];
    $issuer = defined('SITE_NAME') && (string)SITE_NAME !== '' ? (string)SITE_NAME : 'Quran';
    $uri = qfa_totp_uri($secret, QFA_ADMIN_EMAIL !== '' ? QFA_ADMIN_EMAIL : 'admin', $issuer);
    $panel = '
    <ol class="setup-steps">
      <li>ثبّت تطبيق مصادقة على جوالك، مثل Google Authenticator أو Microsoft Authenticator.</li>
      <li>امسح الرمز التالي من التطبيق، أو أدخل المفتاح يدويًا.</li>
      <li>أدخل الرمز الذي يظهر في التطبيق مع كلمة المرور الحالية.</li>
    </ol>
    <div class="qr-wrap"><div id="qfa-qr" data-uri="' . e($uri) . '"></div><a class="qr-link" href="' . e($uri) . '">فتح في تطبيق المصادقة على هذا الجهاز</a><code class="secret" dir="ltr">' . e(trim(chunk_split($secret, 4, ' '))) . '</code></div>
    <form method="post" class="security-form">
      <input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="action" value="enable">
      ' . $passwordField . $codeField . '
      <button type="submit">تفعيل</button>
    </form>
    <p class="auth-note">إذا فقدت الجوال ورموز الاسترداد معًا: احذف قسم "totp" من الملف includes/admin_auth_store.php عبر مدير الملفات في الاستضافة، ثم سجّل الدخول بكلمة المرور.</p>';
}

$content = '
<section class="auth-card settings-page">
  <div class="settings-head">
    <div>
      <h1>التحقق بخطوتين</h1>
      <p class="auth-note">بعد كلمة المرور يُطلب رمز متغير كل 30 ثانية من تطبيق على جوالك، فلا تكفي كلمة المرور المسروقة وحدها للدخول.</p>
    </div>
  </div>

  ' . ($error !== '' ? '<div class="settings-error">' . e($error) . '</div>' : '') . '
  ' . ($success !== '' && !$enabled ? '<div class="settings-success">' . e($success) . '</div>' : '') . '
  ' . ($success !== '' && $enabled ? '<div class="settings-success">' . e($success) . '</div>' : '') . '

  <section class="settings-panel">
    <div class="panel-head">
      <span class="panel-icon">🔐</span>
      <div>
        <h2>' . ($enabled ? 'الحالة: مفعّل' : 'الحالة: غير مفعّل') . '</h2>
        <p>' . ($enabled ? 'يُطلب الرمز عند كل تسجيل دخول.' : 'ننصح بتفعيله.') . '</p>
      </div>
    </div>
    ' . $panel . '
  </section>

  <div class="settings-actions">
    <a href="admin.php">العودة إلى لوحة الإدارة</a>
    <a class="settings-logout" href="admin-logout.php">تسجيل الخروج</a>
  </div>
</section>

<link rel="stylesheet" href="style/default/css/admin-settings.css?v=1.1">
<style>
.setup-steps{margin:0 0 14px;padding-inline-start:20px;line-height:2}
.qr-wrap{display:flex;flex-direction:column;align-items:center;gap:10px;margin:10px 0 18px}
#qfa-qr{background:#fff;padding:10px;border-radius:12px;min-height:40px}
#qfa-qr img,#qfa-qr svg{display:block;width:200px;height:200px}
.qr-link{font-size:14px}
.secret{font-size:15px;letter-spacing:1px;background:#f3f6f5;padding:6px 10px;border-radius:8px;overflow-wrap:anywhere;text-align:center}
.security-form{display:grid;gap:10px;margin:16px 0;padding-top:14px;border-top:1px solid #e6efec}
.security-form h3{margin:0;font-size:16px}
.security-form button{justify-self:start}
.security-form button.danger{background:#a43d34}
.recovery-box{border:1px dashed #c79a2e;border-radius:12px;padding:12px 16px;margin:12px 0}
.recovery-box ol{columns:2;margin:8px 0 0;font-size:15px}
html[data-theme="dark"] .secret{background:#1c2925}
html[data-theme="dark"] .security-form{border-color:#2a3934}
</style>
<script src="style/default/js/qrcode.js?v=1.4.4"></script>
<script src="style/default/js/admin-2fa-qr.js?v=1.0"></script>
';

qfa_auth_page('التحقق بخطوتين', $content);
