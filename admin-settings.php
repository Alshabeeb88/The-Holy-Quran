<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/social-image.php';
qfa_auth_require();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function clean_setting(string $value, int $max): string {
    $value = trim(strip_tags($value));
    $value = preg_replace('/[\r\n\t]+/u', ' ', $value) ?? '';
    return function_exists('mb_substr')
        ? mb_substr($value, 0, $max, 'UTF-8')
        : substr($value, 0, $max);
}

function replace_define(string $code, string $name, string $value): string {
    $replacement = "define('" . $name . "', " . var_export($value, true) . ");";
    $pattern = "~define\\('" . preg_quote($name, '~') . "'\\s*,\\s*.*?\\);~";
    return preg_replace($pattern, $replacement, $code, 1) ?? $code;
}

function replace_define_bool(string $code, string $name, bool $value): string {
    $replacement = "define('" . $name . "', " . ($value ? 'true' : 'false') . ");";
    $pattern = "~define\\('" . preg_quote($name, '~') . "'\\s*,\\s*.*?\\);~";
    return preg_replace($pattern, $replacement, $code, 1) ?? $code;
}

function qfa_theme_label(string $value): string {
    $labels = [
        'emerald' => 'الأخضر الزمردي',
        'navy' => 'الأزرق الكحلي',
        'burgundy' => 'العنابي الملكي',
    ];
    return $labels[$value] ?? $value;
}

$configFile = __DIR__ . '/includes/config.php';
$error = '';
$success = isset($_GET['saved']) && $_GET['saved'] === '1'
    ? 'تم حفظ إعدادات الموقع بنجاح.'
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf'] ?? '');

    if (!qfa_auth_check_csrf($token)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة.';
    } else {
        $oldSiteName = defined('SITE_NAME') ? (string)SITE_NAME : '';
        $oldDescription = defined('SITE_DESCRIPTION') ? (string)SITE_DESCRIPTION : '';
        $oldBrowserTitle = defined('BROWSER_TITLE') ? (string)BROWSER_TITLE : '';
        $oldMemorialEnabled = defined('MEMORIAL_ENABLED') ? (bool)MEMORIAL_ENABLED : false;
        $oldMemorialTitle = defined('MEMORIAL_TITLE') ? (string)MEMORIAL_TITLE : '';
        $oldMemorialDua = defined('MEMORIAL_DUA') ? (string)MEMORIAL_DUA : '';
        $oldColorScheme = defined('COLOR_SCHEME') ? (string)COLOR_SCHEME : 'emerald';
        $oldAdminEmail = defined('ADMIN_EMAIL') ? (string)ADMIN_EMAIL : '';
        $oldContactEmail = defined('CONTACT_EMAIL') ? (string)CONTACT_EMAIL : '';
        $oldMailFromEmail = defined('MAIL_FROM_EMAIL') ? (string)MAIL_FROM_EMAIL : '';

        $newSiteName = clean_setting((string)($_POST['site_name'] ?? ''), 100);
        $newDescription = clean_setting((string)($_POST['site_description'] ?? ''), 220);
        $newBrowserTitle = clean_setting((string)($_POST['browser_title'] ?? ''), 140);
        $newMemorialEnabled = isset($_POST['memorial_enabled']);
        $newMemorialTitle = clean_setting((string)($_POST['memorial_title'] ?? ''), 120);
        $newMemorialDua = clean_setting((string)($_POST['memorial_dua'] ?? ''), 180);

        $allowedColorSchemes = ['emerald', 'navy', 'burgundy'];
        $newColorScheme = (string)($_POST['color_scheme'] ?? 'emerald');

        $newAdminEmail = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $newContactEmail = strtolower(trim((string)($_POST['contact_email'] ?? '')));
        $newMailFromEmail = strtolower(trim((string)($_POST['mail_from_email'] ?? '')));

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newPasswordAgain = (string)($_POST['new_password_again'] ?? '');

        $passwordChangeRequested = (
            $currentPassword !== '' ||
            $newPassword !== '' ||
            $newPasswordAgain !== ''
        );

        $socialImageCheck = inspect_social_image($_FILES['social_share_image'] ?? []);

        $changes = [];

        if ($oldSiteName !== $newSiteName) {
            $changes[] = 'تم تغيير اسم الموقع';
        }

        if ($oldDescription !== $newDescription) {
            $changes[] = 'تم تحديث وصف الموقع';
        }

        if ($oldBrowserTitle !== $newBrowserTitle) {
            $changes[] = 'تم تغيير عنوان المتصفح';
        }

        if ($oldMemorialEnabled !== $newMemorialEnabled) {
            $changes[] = $newMemorialEnabled
                ? 'تم تفعيل شريط الصدقة الجارية'
                : 'تم تعطيل شريط الصدقة الجارية';
        }

        if ($oldMemorialTitle !== $newMemorialTitle) {
            $changes[] = 'تم تحديث عنوان الصدقة الجارية';
        }

        if ($oldMemorialDua !== $newMemorialDua) {
            $changes[] = 'تم تحديث الدعاء المختصر';
        }

        if ($oldColorScheme !== $newColorScheme) {
            $changes[] = 'مظهر الموقع: ' . qfa_theme_label($oldColorScheme) . ' ← ' . qfa_theme_label($newColorScheme);
        }

        if ($oldAdminEmail !== $newAdminEmail) {
            $changes[] = 'تم تغيير بريد المدير';
        }

        if ($oldContactEmail !== $newContactEmail) {
            $changes[] = 'تم تغيير بريد استقبال رسائل التواصل';
        }

        if ($oldMailFromEmail !== $newMailFromEmail) {
            $changes[] = 'تم تغيير بريد الإرسال';
        }

        if ($socialImageCheck['uploaded']) {
            $changes[] = 'تم تحديث صورة المشاركة';
        }

        if ($passwordChangeRequested) {
            $changes[] = 'تم تغيير كلمة مرور المدير';
        }

        if (!in_array($newColorScheme, $allowedColorSchemes, true)) {
            $newColorScheme = 'emerald';
        }

        if ($newSiteName === '' || $newDescription === '') {
            $error = 'اسم الموقع والوصف مطلوبان.';
        } elseif ($newMemorialEnabled && $newMemorialTitle === '') {
            $error = 'اكتب عنوان الصدقة الجارية أو عطّل الشريط.';
        } elseif (!filter_var($newAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'بريد المدير غير صحيح.';
        } elseif ($newContactEmail !== '' && !filter_var($newContactEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'بريد استقبال رسائل التواصل غير صحيح.';
        } elseif ($newMailFromEmail !== '' && !filter_var($newMailFromEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'بريد الإرسال غير صحيح.';
        } elseif ($passwordChangeRequested && strlen($newPassword) < 12) {
            $error = 'كلمة المرور الجديدة يجب ألا تقل عن 12 رمزًا.';
        } elseif ($passwordChangeRequested && $newPassword !== $newPasswordAgain) {
            $error = 'كلمتا المرور الجديدة غير متطابقتين.';
        } elseif ($passwordChangeRequested) {
            $authData = qfa_auth_read();

            if (
                empty($authData['password_hash']) ||
                !password_verify($currentPassword, (string)$authData['password_hash'])
            ) {
                $error = 'كلمة المرور الحالية غير صحيحة.';
            }
        }

        if ($error === '' && !$socialImageCheck['ok']) {
            $error = (string)$socialImageCheck['error'];
        }

        if ($error === '' && (!is_file($configFile) || !is_writable($configFile))) {
            $error = 'تعذر تعديل ملف إعدادات الموقع.';
        }

        if ($error === '') {
            $code = (string)@file_get_contents($configFile);

            if ($code === '') {
                $error = 'تعذر قراءة إعدادات الموقع.';
            } else {
                $socialImageRelative = defined('SOCIAL_SHARE_IMAGE') && SOCIAL_SHARE_IMAGE !== ''
                    ? (string)SOCIAL_SHARE_IMAGE
                    : 'images/og.png';

                if ($socialImageCheck['uploaded']) {
                    $socialImageRelative = 'images/social-share.' . $socialImageCheck['ext'];
                }
                $code = replace_define($code, 'SITE_NAME', $newSiteName);
                $code = replace_define($code, 'SITE_DESCRIPTION', $newDescription);
                $code = replace_define($code, 'BROWSER_TITLE', $newBrowserTitle);
                $code = replace_define_bool($code, 'MEMORIAL_ENABLED', $newMemorialEnabled);
                $code = replace_define($code, 'MEMORIAL_TITLE', $newMemorialTitle);
                $code = replace_define($code, 'MEMORIAL_DUA', $newMemorialDua);
                $code = replace_define($code, 'COLOR_SCHEME', $newColorScheme);
                $code = replace_define($code, 'SOCIAL_SHARE_IMAGE', $socialImageRelative);
                $code = replace_define($code, 'ADMIN_EMAIL', $newAdminEmail);
                $code = replace_define($code, 'CONTACT_EMAIL', $newContactEmail);
                $code = replace_define($code, 'MAIL_FROM_EMAIL', $newMailFromEmail);

                $socialImageSaved = true;

                if ($socialImageCheck['uploaded']) {
                    $socialDir = __DIR__ . '/style/default/images';
                    $socialTarget = $socialDir . '/social-share.' . $socialImageCheck['ext'];

                    if (!is_dir($socialDir) || !is_writable($socialDir)) {
                        $socialImageSaved = false;
                    } else {
                        $uploadTmp = $socialTarget . '.upload-' . bin2hex(random_bytes(5));

                        $socialImageSaved = @move_uploaded_file(
                            (string)($_FILES['social_share_image']['tmp_name'] ?? ''),
                            $uploadTmp
                        );

                        if ($socialImageSaved) {
                            @chmod($uploadTmp, 0644);

                            if (!@rename($uploadTmp, $socialTarget)) {
                                @unlink($uploadTmp);
                                $socialImageSaved = false;
                            } else {
                                foreach (glob($socialDir . '/social-share.*') ?: [] as $oldSocialImage) {
                                    if ($oldSocialImage !== $socialTarget) {
                                        @unlink($oldSocialImage);
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$socialImageSaved) {
                    $error = 'تعذر حفظ صورة المشاركة الجديدة.';
                }

                if ($error !== '') {
                    // Do not modify config.php when the image operation failed.
                } else {

                $tmp = $configFile . '.tmp-' . bin2hex(random_bytes(5));

                if (@file_put_contents($tmp, $code, LOCK_EX) === false) {
                    $error = 'تعذر حفظ الإعدادات الجديدة.';
                } else {
                    @chmod($tmp, 0640);

                    if (!@rename($tmp, $configFile)) {
                        @unlink($tmp);
                        $error = 'تعذر اعتماد الإعدادات الجديدة.';
                    } else {
                        clearstatcache(true, $configFile);
                        $verify = (string)@file_get_contents($configFile);

                        $checks = [
                            "define('SITE_NAME', " . var_export($newSiteName, true) . ");",
                            "define('SITE_DESCRIPTION', " . var_export($newDescription, true) . ");",
                            "define('BROWSER_TITLE', " . var_export($newBrowserTitle, true) . ");",
                            "define('MEMORIAL_ENABLED', " . ($newMemorialEnabled ? 'true' : 'false') . ");",
                            "define('MEMORIAL_TITLE', " . var_export($newMemorialTitle, true) . ");",
                            "define('MEMORIAL_DUA', " . var_export($newMemorialDua, true) . ");",
                            "define('COLOR_SCHEME', " . var_export($newColorScheme, true) . ");",
                            "define('SOCIAL_SHARE_IMAGE', " . var_export($socialImageRelative, true) . ");",
                            "define('ADMIN_EMAIL', " . var_export($newAdminEmail, true) . ");",
                            "define('CONTACT_EMAIL', " . var_export($newContactEmail, true) . ");",
                            "define('MAIL_FROM_EMAIL', " . var_export($newMailFromEmail, true) . ");",
                        ];

                        $verified = true;
                        foreach ($checks as $check) {
                            if (strpos($verify, $check) === false) {
                                $verified = false;
                                break;
                            }
                        }

                        if (!$verified) {
                            $error = 'تعذر التحقق من حفظ الإعدادات. حاول مرة أخرى.';
                        } else {
                            $authNeedsUpdate =
                                $oldAdminEmail !== $newAdminEmail ||
                                $passwordChangeRequested;

                            if ($authNeedsUpdate) {
                                $authData = qfa_auth_read();
                                $authData['email'] = $newAdminEmail;

                                if ($passwordChangeRequested) {
                                    $authData['password_hash'] = password_hash(
                                        $newPassword,
                                        PASSWORD_DEFAULT
                                    );
                                }

                                if (!qfa_auth_write($authData)) {
                                    $rollbackTmp = $configFile . '.rollback-' . bin2hex(random_bytes(5));

                                    if (@file_put_contents($rollbackTmp, $currentConfig, LOCK_EX) !== false) {
                                        @chmod($rollbackTmp, 0640);

                                        if (!@rename($rollbackTmp, $configFile)) {
                                            @unlink($rollbackTmp);
                                        }
                                    }

                                    $error = 'تعذر مزامنة بيانات المدير، وتمت محاولة استعادة الإعدادات السابقة.';
                                }
                            }

                            if ($error === '') {
                                $_SESSION['qfa_settings_changes'] = $changes;
                                header('Location: admin-settings.php?saved=1&t=' . time(), true, 303);
                                exit;
                            }
                        }
                    }
                }
                }
            }
        }
    }
}

$savedChanges = [];

if (!empty($_SESSION['qfa_settings_changes']) && is_array($_SESSION['qfa_settings_changes'])) {
    $savedChanges = $_SESSION['qfa_settings_changes'];
    unset($_SESSION['qfa_settings_changes']);
}

$currentConfig = (string)@file_get_contents($configFile);

$siteName = defined('SITE_NAME') ? (string)SITE_NAME : '';
$siteDescription = defined('SITE_DESCRIPTION') ? (string)SITE_DESCRIPTION : '';
$browserTitle = defined('BROWSER_TITLE') ? (string)BROWSER_TITLE : '';
$memorialEnabled = defined('MEMORIAL_ENABLED') ? (bool)MEMORIAL_ENABLED : false;
$memorialTitle = defined('MEMORIAL_TITLE') ? (string)MEMORIAL_TITLE : '';
$memorialDua = defined('MEMORIAL_DUA') ? (string)MEMORIAL_DUA : '';
$colorScheme = defined('COLOR_SCHEME') ? (string)COLOR_SCHEME : 'emerald';
$socialShareImage = defined('SOCIAL_SHARE_IMAGE') && SOCIAL_SHARE_IMAGE !== ''
    ? (string)SOCIAL_SHARE_IMAGE
    : 'images/og.png';
$socialShareUrl = 'style/default/' . ltrim($socialShareImage, '/');
$adminEmail = defined('ADMIN_EMAIL') ? (string)ADMIN_EMAIL : '';
$contactEmail = defined('CONTACT_EMAIL') ? (string)CONTACT_EMAIL : '';
$mailFromEmail = defined('MAIL_FROM_EMAIL') ? (string)MAIL_FROM_EMAIL : '';

if (preg_match("~define\\('SITE_NAME',\\s*'([^']*)'\\);~u", $currentConfig, $m)) {
    $siteName = $m[1];
}

if (preg_match("~define\\('SITE_DESCRIPTION',\\s*'([^']*)'\\);~u", $currentConfig, $m)) {
    $siteDescription = $m[1];
}

if (preg_match("~define\\('COLOR_SCHEME',\\s*'([^']*)'\\);~u", $currentConfig, $m)) {
    if (in_array($m[1], ['emerald', 'navy', 'burgundy'], true)) {
        $colorScheme = $m[1];
    }
}

$changesModal = '';

if (!empty($savedChanges)) {
    $items = '';

    foreach ($savedChanges as $change) {
        $items .= '<li>' . e((string)$change) . '</li>';
    }

    $changesModal =
        '<div class="settings-modal-backdrop" id="settingsSavedModal">' .
            '<div class="settings-modal" role="dialog" aria-modal="true" aria-labelledby="settings-modal-title">' .
                '<div class="settings-modal-check">✓</div>' .
                '<h2 id="settings-modal-title">تم حفظ التعديلات بنجاح</h2>' .
                '<p>التغييرات التي تمت:</p>' .
                '<ul>' . $items . '</ul>' .
                '<button type="button" id="settingsModalClose">تم</button>' .
            '</div>' .
        '</div>';
}

$content = '
<section class="auth-card settings-page">
  '.$changesModal.'
  <div class="settings-head">
    <div>
      <h1>إعدادات الموقع</h1>
      <p class="auth-note">عدّل إعدادات الموقع الأساسية من لوحة واحدة.</p>
    </div>
  </div>

  '.($error !== '' ? '<div class="settings-error">'.e($error).'</div>' : '').'
  '.($success !== '' ? '<div class="settings-success">'.e($success).'</div>' : '').'

  <form method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="csrf" value="'.e(qfa_auth_csrf()).'">

    <div class="settings-grid">

      <section class="settings-panel">
        <div class="panel-head">
          <span class="panel-icon">✎</span>
          <div>
            <h2>معلومات الموقع</h2>
            <p>اسم الموقع والوصف وعنوان المتصفح.</p>
          </div>
        </div>

        <label>
          اسم الموقع
          <input type="text" name="site_name" maxlength="100" required value="'.e($siteName).'">
        </label>

        <label>
          وصف الموقع
          <textarea name="site_description" maxlength="220" required>'.e($siteDescription).'</textarea>
        </label>

        <label>
          عنوان المتصفح
          <input type="text" name="browser_title" maxlength="140" value="'.e($browserTitle).'">
          <small>يمكن تركه فارغًا ليستخدم اسم الموقع تلقائيًا.</small>
        </label>
      </section>

      <section class="settings-panel">
        <div class="panel-head">
          <span class="panel-icon">☾</span>
          <div>
            <h2>الصدقة الجارية</h2>
            <p>التحكم بالشريط والنص والدعاء.</p>
          </div>
        </div>

        <label class="settings-check">
          <input type="checkbox" name="memorial_enabled" value="1" '.($memorialEnabled ? 'checked' : '').'>
          <span>تفعيل شريط الصدقة الجارية</span>
        </label>

        <label>
          عنوان الصدقة الجارية
          <input type="text" name="memorial_title" maxlength="120" value="'.e($memorialTitle).'">
        </label>

        <label>
          الدعاء المختصر
          <input type="text" name="memorial_dua" maxlength="180" value="'.e($memorialDua).'">
        </label>
      </section>

      <section class="settings-panel">
        <div class="panel-head">
          <span class="panel-icon">✉</span>
          <div>
            <h2>إعدادات البريد</h2>
            <p>إدارة بريد المدير والتواصل والإرسال.</p>
          </div>
        </div>

        <label>
          بريد المدير
          <input type="email" dir="ltr" name="admin_email" required value="'.e($adminEmail).'">
          <small>يستخدم لتسجيل الإدارة واستعادة كلمة المرور.</small>
        </label>

        <label>
          بريد استقبال رسائل التواصل
          <input type="email" dir="ltr" name="contact_email" value="'.e($contactEmail).'">
        </label>

        <label>
          بريد الإرسال
          <input type="email" dir="ltr" name="mail_from_email" value="'.e($mailFromEmail).'">
          <small>يفضل بريدًا تابعًا لنطاق الموقع ومسموحًا للاستضافة بالإرسال منه.</small>
        </label>
      </section>

      <section class="settings-panel">
        <div class="panel-head">
          <span class="panel-icon">🔒</span>
          <div>
            <h2>الأمان</h2>
            <p>تغيير كلمة مرور المدير.</p>
          </div>
        </div>

        <label>
          كلمة المرور الحالية
          <input type="password" name="current_password" autocomplete="current-password">
        </label>

        <label>
          كلمة المرور الجديدة
          <input type="password" name="new_password" minlength="12" autocomplete="new-password">
          <small>اترك الحقول الثلاثة فارغة إذا لم ترغب بتغيير كلمة المرور.</small>
        </label>

        <label>
          تأكيد كلمة المرور الجديدة
          <input type="password" name="new_password_again" minlength="12" autocomplete="new-password">
        </label>

        <div class="security-note">
          في حال نسيان كلمة المرور يمكنك استخدام استعادة كلمة المرور من صفحة تسجيل الدخول.
        </div>
      </section>

      <section class="settings-panel settings-panel-wide">
        <div class="panel-head">
          <span class="panel-icon">▣</span>
          <div>
            <h2>صورة المشاركة</h2>
            <p>الصورة التي تظهر عند مشاركة رابط الموقع في منصات التواصل.</p>
          </div>
        </div>

        <div class="social-image-layout">
          <div class="social-image-preview">
            <img src="'.e($socialShareUrl).'?v='.time().'" alt="معاينة صورة المشاركة">
          </div>

          <div class="social-image-controls">
            <label>
              اختيار صورة جديدة
              <input type="file" name="social_share_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </label>

            <small>PNG أو JPG أو WebP — الحد الأقصى 5 ميجابايت.</small>
            <small>المقاس الموصى به للمشاركة: 1200 × 630 بكسل.</small>
          </div>
        </div>
      </section>

      <section class="settings-panel settings-panel-wide">
        <div class="panel-head">
          <span class="panel-icon">◈</span>
          <div>
            <h2>مظهر الموقع</h2>
            <p>اختر اللون المعتمد للموقع.</p>
          </div>
        </div>

        <div class="theme-options">

          <label class="theme-choice">
            <input type="radio" name="color_scheme" value="emerald" '.($colorScheme === 'emerald' ? 'checked' : '').'>
            <span class="theme-card">
              <i class="theme-preview emerald-preview"></i>
              <span class="theme-text">
                <b>الأخضر الزمردي</b>
                <small>الثيم الأخضر المعتمد</small>
              </span>
              <i class="theme-check">✓</i>
            </span>
          </label>

          <label class="theme-choice">
            <input type="radio" name="color_scheme" value="navy" '.($colorScheme === 'navy' ? 'checked' : '').'>
            <span class="theme-card">
              <i class="theme-preview navy-preview"></i>
              <span class="theme-text">
                <b>الأزرق الكحلي</b>
                <small>الثيم الأزرق المعتمد</small>
              </span>
              <i class="theme-check">✓</i>
            </span>
          </label>

          <label class="theme-choice">
            <input type="radio" name="color_scheme" value="burgundy" '.($colorScheme === 'burgundy' ? 'checked' : '').'>
            <span class="theme-card">
              <i class="theme-preview burgundy-preview"></i>
              <span class="theme-text">
                <b>العنابي الملكي</b>
                <small>الثيم العنابي المعتمد</small>
              </span>
              <i class="theme-check">✓</i>
            </span>
          </label>

        </div>
      </section>

    </div>

    <div class="settings-actions">
      <button type="submit">حفظ التعديلات</button>
      <a href="admin.php">العودة إلى لوحة الإدارة</a>
      <a class="settings-logout" href="admin-logout.php">تسجيل الخروج</a>
    </div>
  </form>
</section>

<script src="style/default/js/admin-settings.js?v=1.0"></script>

<style>
.settings-page{
  width:min(1180px,calc(100vw - 32px));
  max-width:none;
}

.settings-head{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  margin-bottom:20px;
}

.settings-head h1{
  margin:0 0 6px;
}

.settings-page form{
  display:block;
  margin-top:18px;
}

.settings-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:18px;
}

.settings-panel{
  display:grid;
  align-content:start;
  gap:16px;
  padding:20px;
  border:1px solid #dbe7e3;
  border-radius:16px;
  background:#fff;
}

.settings-panel-wide{
  grid-column:1/-1;
}

.panel-head{
  display:flex;
  align-items:center;
  gap:12px;
  padding-bottom:14px;
  border-bottom:1px solid #e7efec;
}

.panel-head h2{
  margin:0 0 4px;
  font-size:19px;
}

.panel-head p{
  margin:0;
  color:#7c8c86;
  font-size:12px;
}

.panel-icon{
  display:grid;
  place-items:center;
  width:42px;
  height:42px;
  border-radius:12px;
  background:#edf7f3;
  color:#0c6654;
  font-size:20px;
  font-weight:800;
  flex:0 0 42px;
}

.settings-panel label{
  display:grid;
  gap:7px;
  font-weight:700;
}

.settings-panel input,
.settings-panel textarea{
  width:100%;
  padding:12px;
  border:1px solid #ccdcd6;
  border-radius:10px;
  font:inherit;
}

.settings-panel textarea{
  min-height:110px;
  resize:vertical;
}

.settings-check{
  display:flex!important;
  align-items:center;
  gap:10px!important;
  min-height:44px;
}

.settings-check input{
  width:auto!important;
  margin:0;
}

.theme-options{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:12px;
}

.theme-choice{
  display:block!important;
  position:relative;
  cursor:pointer;
}

.theme-choice input{
  position:absolute;
  opacity:0;
  pointer-events:none;
}

.theme-card{
  display:flex!important;
  align-items:center;
  gap:12px;
  min-height:72px;
  padding:12px 14px;
  border:2px solid #d7e4df;
  border-radius:14px;
  background:#fff;
}

.theme-choice input:checked + .theme-card{
  border-color:#0c7763;
  box-shadow:0 0 0 3px rgba(12,119,99,.10);
}

.theme-preview{
  display:block;
  width:68px;
  height:38px;
  border-radius:9px;
  flex:0 0 68px;
}

.emerald-preview{background:linear-gradient(135deg,#0c4f42,#0c7763)}
.navy-preview{background:linear-gradient(135deg,#173b5f,#245f91)}
.burgundy-preview{background:linear-gradient(135deg,#6b3141,#914b61)}

.theme-text{
  display:flex;
  flex-direction:column;
  gap:4px;
  flex:1;
}

.theme-text small{
  color:#7b8b85;
}

.theme-check{
  display:none;
  width:28px;
  height:28px;
  border-radius:50%;
  align-items:center;
  justify-content:center;
  background:#0c7763;
  color:#fff;
  font-style:normal;
  font-weight:800;
}

.theme-choice input:checked + .theme-card .theme-check{
  display:flex;
}

.settings-actions{
  display:flex;
  align-items:center;
  gap:14px;
  margin-top:20px;
}

.settings-actions button{
  min-width:190px;
}

.settings-error,.settings-success{
  margin:15px 0;
  padding:12px;
  border-radius:10px;
}

.settings-error{background:#fff0ef;color:#a43d34}
.settings-success{background:#edf8f3;color:#17684f}

html[data-theme="dark"] .settings-panel,
html[data-theme="dark"] .theme-card{
  background:#16211e;
  border-color:#2a3934;
}

html[data-theme="dark"] .panel-head{
  border-color:#2a3934;
}


.social-image-layout{
  display:grid;
  grid-template-columns:minmax(260px,360px) 1fr;
  gap:20px;
  align-items:center;
}

.social-image-preview{
  overflow:hidden;
  border:1px solid #dbe7e3;
  border-radius:14px;
  background:#f5f8f7;
  aspect-ratio:1200/630;
}

.social-image-preview img{
  display:block;
  width:100%;
  height:100%;
  object-fit:cover;
}

.social-image-controls{
  display:grid;
  gap:10px;
}

.social-image-controls small{
  color:#7c8c86;
}

html[data-theme="dark"] .social-image-preview{
  background:#16211e;
  border-color:#2a3934;
}


.settings-modal-backdrop{
  position:fixed;
  inset:0;
  z-index:99999;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:20px;
  background:rgba(9,25,20,.58);
  backdrop-filter:blur(4px);
}

.settings-modal{
  width:min(480px,100%);
  padding:28px;
  border-radius:20px;
  background:#fff;
  box-shadow:0 24px 70px rgba(0,0,0,.22);
  text-align:center;
  animation:settingsModalIn .22s ease-out;
}

.settings-modal-check{
  display:grid;
  place-items:center;
  width:58px;
  height:58px;
  margin:0 auto 14px;
  border-radius:50%;
  background:#e9f7f1;
  color:#0c7763;
  font-size:30px;
  font-weight:900;
}

.settings-modal h2{
  margin:0 0 8px;
  font-size:21px;
}

.settings-modal p{
  margin:0 0 12px;
  color:#71817b;
}

.settings-modal ul{
  margin:0 0 20px;
  padding:0;
  list-style:none;
  text-align:right;
}

.settings-modal li{
  padding:9px 0;
  border-bottom:1px solid #edf1ef;
}

.settings-modal li:last-child{
  border-bottom:0;
}

.settings-modal button{
  min-width:130px;
}

.settings-modal-backdrop.is-closing{
  opacity:0;
  transition:opacity .2s ease;
}

html[data-theme="dark"] .settings-modal{
  background:#16211e;
}

html[data-theme="dark"] .settings-modal li{
  border-color:#2a3934;
}

@keyframes settingsModalIn{
  from{opacity:0;transform:translateY(8px) scale(.98)}
  to{opacity:1;transform:none}
}

@media(max-width:800px){
  .settings-page{
    width:min(100%,calc(100vw - 20px));
  }

  .settings-grid{
    grid-template-columns:1fr;
  }

  .settings-panel-wide{
    grid-column:auto;
  }

  .theme-options{
    grid-template-columns:1fr;
  }

  .social-image-layout{
    grid-template-columns:1fr;
  }

  .settings-actions{
    flex-direction:column;
    align-items:stretch;
  }

  .settings-actions button{
    width:100%;
  }
}


';

qfa_auth_page('إعدادات الموقع', $content);
