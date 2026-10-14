<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/social-image.php';
require_once __DIR__ . '/includes/config-writer.php';
require_once __DIR__ . '/includes/youtube.php';
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

function qfa_theme_label(string $value): string {
    $labels = [
        'emerald' => 'الأخضر الزمردي',
        'navy' => 'الأزرق الكحلي',
        'burgundy' => 'العنابي الملكي',
        'mushaf' => 'مصحف',
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
        $oldHaramVideo = defined('HARAM_LIVE_VIDEO') ? (string)HARAM_LIVE_VIDEO : '';
        $haramVideoInput = trim((string)($_POST['haram_live_video'] ?? ''));
        $newHaramVideo = qfa_youtube_id($haramVideoInput);

        $allowedColorSchemes = ['emerald', 'navy', 'burgundy', 'mushaf'];
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

        if ($oldHaramVideo !== $newHaramVideo) {
            $changes[] = $newHaramVideo === '' ? 'تم إخفاء البث المباشر من الحرم' : 'تم تحديث فيديو البث المباشر من الحرم';
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
        } elseif ($haramVideoInput !== '' && $newHaramVideo === '') {
            $error = 'رابط البث المباشر غير صحيح. الصق رابط فيديو من يوتيوب.';
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
            $originalCode = (string)@file_get_contents($configFile);

            if ($originalCode === '') {
                $error = 'تعذر قراءة إعدادات الموقع.';
            } else {
                $socialImageRelative = defined('SOCIAL_SHARE_IMAGE') && SOCIAL_SHARE_IMAGE !== ''
                    ? (string)SOCIAL_SHARE_IMAGE
                    : 'images/social-share-default.jpg';

                if ($socialImageCheck['uploaded']) {
                    $socialImageRelative = 'images/social-share.' . $socialImageCheck['ext'];
                }

                $newValues = [
                    'SITE_NAME' => $newSiteName,
                    'SITE_DESCRIPTION' => $newDescription,
                    'BROWSER_TITLE' => $newBrowserTitle,
                    'MEMORIAL_ENABLED' => $newMemorialEnabled,
                    'MEMORIAL_TITLE' => $newMemorialTitle,
                    'MEMORIAL_DUA' => $newMemorialDua,
                    'HARAM_LIVE_VIDEO' => $newHaramVideo,
                    'COLOR_SCHEME' => $newColorScheme,
                    'SOCIAL_SHARE_IMAGE' => $socialImageRelative,
                    'ADMIN_EMAIL' => $newAdminEmail,
                    'CONTACT_EMAIL' => $newContactEmail,
                    'MAIL_FROM_EMAIL' => $newMailFromEmail,
                ];

                $code = $originalCode;
                foreach ($newValues as $name => $value) {
                    $code = qfa_config_set($code, $name, $value);
                }

                if (!qfa_config_parses($code)) {
                    $error = 'تعذر حفظ الإعدادات: القيم المدخلة تحتوي على رموز غير مسموحة.';
                }

                /*
                 * The new image is stored next to the old one first; old images
                 * are removed only once the config pointing at the new file has
                 * been committed, so a failed save never leaves the site without
                 * its share image.
                 */
                $socialDir = __DIR__ . '/style/default/images';
                $socialTarget = '';
                $socialUploadTmp = '';

                if ($error === '' && $socialImageCheck['uploaded']) {
                    $socialTarget = $socialDir . '/social-share.' . $socialImageCheck['ext'];

                    if (!is_dir($socialDir) || !is_writable($socialDir)) {
                        $error = 'تعذر حفظ صورة المشاركة الجديدة.';
                    } else {
                        $socialUploadTmp = $socialTarget . '.upload-' . bin2hex(random_bytes(5));

                        if (!@move_uploaded_file((string)($_FILES['social_share_image']['tmp_name'] ?? ''), $socialUploadTmp)) {
                            $socialUploadTmp = '';
                            $error = 'تعذر حفظ صورة المشاركة الجديدة.';
                        } else {
                            @chmod($socialUploadTmp, 0644);
                        }
                    }
                }

                $previousImage = '';
                if ($error === '' && $socialUploadTmp !== '') {
                    // Keep whatever currently sits at the target name so it can be
                    // put back if the config commit fails.
                    if (is_file($socialTarget)) {
                        $previousImage = $socialTarget . '.previous-' . bin2hex(random_bytes(5));
                        if (!@rename($socialTarget, $previousImage)) {
                            $previousImage = '';
                        }
                    }
                    if (!@rename($socialUploadTmp, $socialTarget)) {
                        @unlink($socialUploadTmp);
                        if ($previousImage !== '') {
                            @rename($previousImage, $socialTarget);
                        }
                        $error = 'تعذر حفظ صورة المشاركة الجديدة.';
                    }
                }

                if ($error === '' && !qfa_config_commit($configFile, $code, $newValues)) {
                    $error = 'تعذر حفظ الإعدادات الجديدة، ولم يتغير شيء في الموقع.';
                    // The live file may have been replaced before verification failed.
                    qfa_config_commit($configFile, $originalCode, []);
                    if ($socialUploadTmp !== '') {
                        @unlink($socialTarget);
                        if ($previousImage !== '') {
                            @rename($previousImage, $socialTarget);
                        }
                    }
                }

                if ($error === '') {
                    $authNeedsUpdate =
                        $oldAdminEmail !== $newAdminEmail ||
                        $passwordChangeRequested;

                    if ($authNeedsUpdate) {
                        $newAuthData = null;
                        $authSaved = qfa_auth_update(static function (array $authData) use ($newAdminEmail, $passwordChangeRequested, $newPassword, &$newAuthData) {
                            $authData['email'] = $newAdminEmail;

                            if ($passwordChangeRequested) {
                                $authData['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                                // Sign out every other session that used the old password.
                                $authData['session_epoch'] = qfa_auth_epoch($authData) + 1;
                            }

                            $newAuthData = $authData;
                            return $authData;
                        });

                        if (!$authSaved) {
                            qfa_config_commit($configFile, $originalCode, []);
                            if ($socialUploadTmp !== '') {
                                @unlink($socialTarget);
                                if ($previousImage !== '') {
                                    @rename($previousImage, $socialTarget);
                                }
                            }
                            $error = 'تعذر مزامنة بيانات المدير، وتمت استعادة الإعدادات السابقة.';
                        } elseif ($passwordChangeRequested && is_array($newAuthData)) {
                            // This session made the change, so it stays signed in.
                            session_regenerate_id(true);
                            $_SESSION['qfa_epoch'] = qfa_auth_epoch($newAuthData);
                        }
                    }
                }

                if ($error === '') {
                    if ($previousImage !== '') {
                        @unlink($previousImage);
                    }
                    if ($socialUploadTmp !== '') {
                        foreach (glob($socialDir . '/social-share.*') ?: [] as $oldSocialImage) {
                            if ($oldSocialImage !== $socialTarget && preg_match('~/social-share\.(jpg|jpeg|png|webp)$~', $oldSocialImage)) {
                                @unlink($oldSocialImage);
                            }
                        }
                    }

                    $_SESSION['qfa_settings_changes'] = $changes;
                    header('Location: admin-settings.php?saved=1&t=' . time(), true, 303);
                    exit;
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
$haramVideo = defined('HARAM_LIVE_VIDEO') ? (string)HARAM_LIVE_VIDEO : '';
$colorScheme = defined('COLOR_SCHEME') ? (string)COLOR_SCHEME : 'emerald';
$socialShareImage = defined('SOCIAL_SHARE_IMAGE') && SOCIAL_SHARE_IMAGE !== ''
    ? (string)SOCIAL_SHARE_IMAGE
    : 'images/social-share-default.jpg';
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
    // The TOA Digital scheme was retired in v1.2.0; sites that used it now show "Mushaf".
    if ($m[1] === 'toa') {
        $m[1] = 'mushaf';
    }
    if (in_array($m[1], ['emerald', 'navy', 'burgundy', 'mushaf'], true)) {
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
          <span class="panel-icon">▶</span>
          <div>
            <h2>البث المباشر من الحرم</h2>
            <p>فيديو يوتيوب يظهر في الصفحة الرئيسية. اتركه فارغًا لإخفاء القسم.</p>
          </div>
        </div>

        <label>
          رابط الفيديو على يوتيوب
          <input type="text" name="haram_live_video" dir="ltr" maxlength="200" placeholder="https://www.youtube.com/watch?v=..." value="'.e($haramVideo !== '' ? 'https://www.youtube.com/watch?v='.$haramVideo : '').'">
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

          <label class="theme-choice">
            <input type="radio" name="color_scheme" value="mushaf" '.($colorScheme === 'mushaf' ? 'checked' : '').'>
            <span class="theme-card">
              <i class="theme-preview mushaf-preview"></i>
              <span class="theme-text">
                <b>مصحف</b>
                <small>ورق دافئ للقراءة نهارًا، وفحمي هادئ ليلًا</small>
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

<link rel="stylesheet" href="style/default/css/admin-settings.css?v=1.2">
';

qfa_auth_page('إعدادات الموقع', $content);
