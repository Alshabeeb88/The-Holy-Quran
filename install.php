<?php
declare(strict_types=1);

/*
 * The installer session carries only the CSRF token, but that token is the one
 * thing between a visitor and a first-time admin account, so the cookie gets
 * the same hardening as the admin session. SITE_URL does not exist yet, so the
 * Secure flag follows the real TLS state of this request only, which keeps
 * plain-HTTP installs on a local machine working.
 */
@ini_set('session.use_strict_mode', '1');
@ini_set('session.use_only_cookies', '1');
session_name('qfa_install');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private', true);
header('X-Frame-Options: DENY', true);
header('X-Content-Type-Options: nosniff', true);

$root = __DIR__;
$lockFile = $root . '/.qfa-installed';
$configFile = $root . '/includes/config.php';
$authFile = $root . '/includes/admin_auth_store.php';
$error = '';
$done = false;

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function clean_line(string $value, int $max): string {
    $value = trim(strip_tags($value));
    $value = preg_replace('/[\r\n\t]+/u', ' ', $value) ?? '';
    return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
}

function write_atomic(string $path, string $contents): bool {
    $tmp = $path . '.tmp-' . bin2hex(random_bytes(5));
    if (@file_put_contents($tmp, $contents, LOCK_EX) === false) return false;
    @chmod($tmp, 0640);
    if (!@rename($tmp, $path)) { @unlink($tmp); return false; }
    return true;
}

require_once __DIR__ . '/includes/social-image.php';

function config_code(array $v): string {
    $bool = static function ($value): string { return $value ? 'true' : 'false'; };
    return "<?php\n"
        . "// تم إنشاء هذا الملف بواسطة المثبّت.\n"
        . "// Application-level guard: refuse to serve this file directly, on top\n"
        . "// of the web-server rules in includes/.htaccess. Including the file\n"
        . "// normally is unaffected.\n"
        . "if (isset(\$_SERVER['SCRIPT_FILENAME']) && @realpath(\$_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__)) { http_response_code(403); exit; }\n"
        . "define('SITE_NAME', " . var_export($v['site_name'], true) . ");\n"
        . "define('SITE_DESCRIPTION', " . var_export($v['site_description'], true) . ");\n"
        . "define('BROWSER_TITLE', " . var_export($v['site_name'], true) . ");\n"
        . "define('SITE_URL', " . var_export($v['site_url'], true) . ");\n"
        . "define('SOCIAL_SHARE_IMAGE', " . var_export($v['social_share_image'], true) . ");\n"
        . "define('MEMORIAL_ENABLED', " . $bool($v['memorial_enabled']) . ");\n"
        . "define('MEMORIAL_TITLE', " . var_export($v['memorial_title'], true) . ");\n"
        . "define('MEMORIAL_DUA', " . var_export($v['memorial_dua'], true) . ");\n"
        . "define('ADMIN_EMAIL', " . var_export($v['admin_email'], true) . ");\n"
        . "define('THEME', 'style/default');\n"
        . "define('APPEARANCE_STYLE', " . var_export($v['appearance_style'], true) . ");\n"
        . "define('COLOR_SCHEME', " . var_export($v['color_scheme'], true) . ");\n"
        . "define('LANGUAGE', 'ar');\n"
        . "define('REWRITE_RULES', " . $bool($v['rewrite_rules']) . ");\n"
        . "define('RANDOM_BOOKS', 5);\n"
        . "define('BREADCRUMB', true);\n"
        . "define('READERS', true);\n"
        . "define('LISTEN_SURAH', true);\n"
        . "define('SURAH_FORM', true);\n"
        . "define('DEFAULT_READER', 37);\n"
        . "define('DEFAULT_AYAH_READER', 16);\n"
        . "define('TWITTER', '');\n"
        . "define('HOME_SORT', ['language', 'tafseer', 'quran']);\n"
        . "define('HOME_QURAN', true);\n"
        . "define('HOME_TAFSEER', true);\n"
        . "define('HOME_LANGUAGE', true);\n"
        . "define('HOME_BOOK', true);\n"
        . "define('CACHE', false);\n"
        . "define('CACHE_TIME', 864000);\n"
        . "define('QURANCOLUMN', 0);\n"
        . "define('TAFSEERCOLUMN', 0);\n"
        . "define('LANGUAGECOLUMN', 0);\n"
        . "define('HEADER_CODE', '');\n"
        . "define('CONTACT_EMAIL', " . var_export($v['contact_email'], true) . ");\n"
        . "define('MAIL_FROM_EMAIL', " . var_export($v['mail_from_email'], true) . ");\n"
        . "define('CONTACT_SUBJECT', '');\n"
        . "define('CONTACT_STORE_MESSAGES', true);\n"
        . "define('FOOTER_CODE', '');\n"
        . "define('HEADER_TEXT', '');\n"
        . "define('BODY_CODE', '');\n";
}

/*
 * Installation counts as complete when ANY of its artefacts is present, not the
 * lock file alone. Previously a missing or failed lock left the installer fully
 * usable while config.php and admin_auth_store.php were already written, so a
 * visitor could re-run it and overwrite the administrator password.
 */
$installedArtefacts = array_keys(array_filter([
    '.qfa-installed' => is_file($lockFile),
    'includes/config.php' => is_file($configFile),
    'includes/admin_auth_store.php' => is_file($authFile),
]));
$alreadyInstalled = $installedArtefacts !== [];

if ($alreadyInstalled) {
    $error = 'الموقع مثبت بالفعل ولا يمكن إعادة تشغيل المثبّت. لإعادة التثبيت من جديد احذف يدويًا: '
        . implode('، ', $installedArtefacts);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['install_csrf']) || !hash_equals((string)$_SESSION['install_csrf'], $token)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة.';
    } elseif (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $error = 'يتطلب السكربت PHP 7.4 أو أحدث.';
    } else {
        $siteName = clean_line((string)($_POST['site_name'] ?? ''), 100);
        $description = clean_line((string)($_POST['site_description'] ?? ''), 220);
        $siteUrl = rtrim(trim((string)($_POST['site_url'] ?? '')), '/');
        $adminEmail = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $contactEmail = strtolower(trim((string)($_POST['contact_email'] ?? '')));
        $mailFromEmail = strtolower(trim((string)($_POST['mail_from_email'] ?? '')));
        $socialImageCheck = inspect_social_image($_FILES['social_share_image'] ?? []);
        $socialImageRelative = $socialImageCheck['uploaded']
            ? 'images/social-share.' . $socialImageCheck['ext']
            : 'images/social-share.png';
        $socialImageTarget = $root . '/style/default/' . $socialImageRelative;
        $password = (string)($_POST['admin_password'] ?? '');
        $passwordAgain = (string)($_POST['admin_password_again'] ?? '');
        $memorialEnabled = isset($_POST['memorial_enabled']);
        $memorialTitle = clean_line((string)($_POST['memorial_title'] ?? ''), 120);
        $memorialDua = clean_line((string)($_POST['memorial_dua'] ?? ''), 180);
        // The approved interface is fixed to the modern layout. Installers
        // choose colors only, so typography, spacing and component sizing stay consistent.
        $appearanceStyle = 'modern';
        $colorScheme = (string)($_POST['color_scheme'] ?? 'emerald');
        $allowedStyles = ['modern'];
        $allowedColors = ['emerald', 'navy', 'burgundy'];

        if ($siteName === '' || $description === '') $error = 'اكتب اسم الموقع ووصفه.';
        elseif (!filter_var($siteUrl, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $siteUrl)) $error = 'رابط الموقع غير صحيح.';
        elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $error = 'بريد المدير غير صحيح.';
        elseif ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) $error = 'بريد التواصل غير صحيح.';
        elseif ($mailFromEmail !== '' && filter_var($mailFromEmail, FILTER_VALIDATE_EMAIL) === false) $error = 'بريد الإرسال غير صحيح.';
        elseif (!$socialImageCheck['ok']) $error = $socialImageCheck['error'];
        elseif (strlen($password) < 12) $error = 'كلمة مرور المدير يجب ألا تقل عن 12 رمزًا.';
        elseif ($password !== $passwordAgain) $error = 'كلمتا المرور غير متطابقتين.';
        elseif ($memorialEnabled && $memorialTitle === '') $error = 'اكتب نص الصدقة الجارية أو عطّل الشريط.';
        elseif (!in_array($appearanceStyle, $allowedStyles, true) || !in_array($colorScheme, $allowedColors, true)) $error = 'اختيار مظهر الموقع غير صحيح.';
        elseif (!is_writable(dirname($configFile)) || !is_writable($root)) $error = 'لا يمكن الكتابة داخل مجلد الموقع. راجع الصلاحيات.';
        else {
            /*
             * Serialise the whole write sequence. Two concurrent installs could
             * otherwise interleave and leave config.php from one request beside
             * the administrator password of another. LOCK_NB means the second
             * request is told to retry instead of blocking a PHP worker.
             */
            $mutexFile = $root . '/.qfa-install.lock';
            $mutexHandle = @fopen($mutexFile, 'c');
            $mutexHeld = $mutexHandle !== false && flock($mutexHandle, LOCK_EX | LOCK_NB);

            if (!$mutexHeld) {
                if ($mutexHandle !== false) { fclose($mutexHandle); }
                $error = 'هناك عملية تثبيت جارية بالفعل. انتظر قليلًا ثم أعد المحاولة.';
            } elseif (is_file($lockFile) || is_file($configFile) || is_file($authFile)) {
                // Another request completed the installation while this one was
                // still validating its input.
                flock($mutexHandle, LOCK_UN);
                fclose($mutexHandle);
                $error = 'الموقع مثبت بالفعل ولا يمكن إعادة تشغيل المثبّت.';
            } else {
                $values = [
                    'site_name' => $siteName,
                    'site_description' => $description,
                    'site_url' => $siteUrl,
                    'social_share_image' => $socialImageRelative,
                    'memorial_enabled' => $memorialEnabled,
                    'memorial_title' => $memorialTitle,
                    'memorial_dua' => $memorialDua,
                    'admin_email' => $adminEmail,
                    'contact_email' => $contactEmail,
                    'mail_from_email' => $mailFromEmail,
                    'appearance_style' => $appearanceStyle,
                    'color_scheme' => $colorScheme,
                    'rewrite_rules' => isset($_POST['rewrite_rules']),
                ];
                $auth = "<?php exit; ?>\n" . json_encode([
                    'email' => $adminEmail,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

                $socialImageSaved = true;
                if ($socialImageCheck['uploaded']) {
                    $socialDir = dirname($socialImageTarget);
                    if (!is_dir($socialDir) || !is_writable($socialDir)) {
                        $socialImageSaved = false;
                    } else {
                        foreach (glob($socialDir . '/social-share.*') ?: [] as $oldSocialImage) {
                            @unlink($oldSocialImage);
                        }
                        $socialImageSaved = @move_uploaded_file(
                            (string)($_FILES['social_share_image']['tmp_name'] ?? ''),
                            $socialImageTarget
                        );
                        if ($socialImageSaved) {
                            @chmod($socialImageTarget, 0644);
                        }
                    }
                }

                if (!$socialImageSaved) {
                    $error = 'تعذر حفظ صورة المشاركة داخل الموقع.';
                } elseif (!write_atomic($configFile, config_code($values))) {
                    $error = 'تعذر حفظ إعدادات الموقع.';
                } elseif (!write_atomic($authFile, $auth)) {
                    $error = 'تعذر إنشاء حساب المدير.';
                } else {
                    // Contact messages hold visitor names, e-mail addresses and message
                    // bodies. Storing them in a .php file that starts with an exit
                    // statement means a direct request returns nothing even when the
                    // web-server rules are absent, e.g. on Nginx.
                    @file_put_contents($root . '/includes/contact_messages.php', "<?php exit; ?>\n", LOCK_EX);
                    @chmod($root . '/includes/contact_messages.php', 0640);
                    $oldDomain = 'https://example.com';
                    foreach (glob($root . '/sitemaps/*.xml') ?: [] as $map) {
                        $content = (string)@file_get_contents($map);
                        if ($content !== '') @file_put_contents($map, str_replace($oldDomain, $siteUrl, $content), LOCK_EX);
                    }
                    $mapIndex = (string)@file_get_contents($root . '/sitemap.xml');
                    if ($mapIndex !== '') @file_put_contents($root . '/sitemap.xml', str_replace($oldDomain, $siteUrl, $mapIndex), LOCK_EX);
                    @file_put_contents($root . '/robots.txt', "User-agent: *\nAllow: /\nDisallow: /includes/\nDisallow: /cache/\nDisallow: /admin-login.php\nDisallow: /sadaqah-agent.php\n\nSitemap: {$siteUrl}/sitemap.xml\n", LOCK_EX);
                    // Atomic write, so a crash can never leave a truncated lock file
                    // that would read as "not installed" on the next request.
                    if (!write_atomic($lockFile, "installed=" . date('c') . "\n")) {
                        $error = 'اكتملت الإعدادات لكن تعذر إنشاء قفل التثبيت. '
                            . 'احذف includes/config.php وincludes/admin_auth_store.php يدويًا ثم أعد التثبيت.';
                    } else {
                        @chmod($lockFile, 0640);
                        clearstatcache(true, $lockFile);
                        $done = true;
                        unset($_SESSION['install_csrf']);
                    }
                }

                flock($mutexHandle, LOCK_UN);
                fclose($mutexHandle);
                @unlink($mutexFile);
            }
        }
    }
}

if (empty($_SESSION['install_csrf'])) $_SESSION['install_csrf'] = bin2hex(random_bytes(24));
$detectedScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
/*
 * Only pre-fills the site URL field, which the administrator reviews before
 * submitting. Restrict it to a plausible host so a forged Host header cannot
 * seed the value that becomes SITE_URL, the trust anchor for every later link.
 */
$rawHost = (string)($_SERVER['HTTP_HOST'] ?? '');
$detectedHost = preg_match('~^[A-Za-z0-9.-]{1,253}(:[0-9]{1,5})?$~D', $rawHost) ? $rawHost : 'example.com';
$detectedPath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$detectedUrl = $detectedScheme . '://' . $detectedHost . ($detectedPath === '' ? '' : $detectedPath);
?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0c4f42"><title>تثبيت موقع القرآن الكريم</title><style>
*{box-sizing:border-box}body{margin:0;background:#f3f7f5;color:#17372f;font-family:Tahoma,Arial,sans-serif}.shell{width:min(760px,calc(100% - 24px));margin:35px auto}.head,.card{border:1px solid #d8e5e0;border-radius:20px;background:#fff;box-shadow:0 15px 40px rgba(13,67,55,.08)}.head{margin-bottom:18px;padding:24px;text-align:center}.mark{display:grid;place-items:center;width:58px;height:58px;margin:0 auto 12px;border-radius:17px;background:#0c4f42;color:#e4c977;font-size:30px}.head h1{margin:0 0 7px;font-size:25px}.head p{margin:0;color:#70817b;line-height:1.8}.card{padding:24px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.full{grid-column:1/-1}label{display:block;font-weight:700;font-size:13px}input,textarea{display:block;width:100%;margin-top:7px;padding:12px;border:1px solid #cbdcd5;border-radius:11px;font:inherit;outline:none}textarea{min-height:85px;resize:vertical}input:focus,textarea:focus{border-color:#0c7763;box-shadow:0 0 0 3px rgba(12,119,99,.1)}.check{display:flex;align-items:center;gap:9px;padding:10px 0}.check input{width:auto;margin:0}.notice{margin-bottom:15px;padding:12px;border-radius:10px;background:#fff0ef;color:#a43d34}.success{padding:25px;text-align:center}.success a,button{display:inline-flex;justify-content:center;padding:13px 23px;border:0;border-radius:11px;background:#0c5c4c;color:#fff;font:inherit;font-weight:700;text-decoration:none;cursor:pointer}button{width:100%;margin-top:10px}.hint{margin-top:5px;color:#84928d;font-size:11px;line-height:1.6}.appearance-title{margin:4px 0 0;font-size:16px}.choice-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:10px}.choice{position:relative}.choice input{position:absolute;opacity:0;pointer-events:none}.choice span{display:flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:9px;border:2px solid #dce7e3;border-radius:12px;background:#fff;cursor:pointer;text-align:center;transition:.2s}.choice input:checked+span{border-color:#0c7763;background:#edf7f3;box-shadow:0 0 0 3px rgba(12,119,99,.09)}.swatch{width:20px;height:20px;border-radius:50%;box-shadow:inset 0 0 0 1px rgba(0,0,0,.1)}.emerald{background:#0c5c4c}.navy{background:#17456b}.burgundy{background:#743947}.indigo{background:#4a4788}.sand{background:#8a6638}@media(max-width:650px){.shell{margin:16px auto}.head,.card{padding:17px;border-radius:15px}.grid{grid-template-columns:1fr}.full{grid-column:auto}.choice-grid{grid-template-columns:1fr}}
</style></head><body><main class="shell"><section class="head"><span class="mark">ق</span><h1>تثبيت موقع القرآن الكريم</h1><p>أدخل بيانات الموقع الجديد مرة واحدة، وسيتولى المثبّت بقية الإعدادات.</p></section><section class="card">
<?php if ($done): ?><div class="success"><h2>تم التثبيت بنجاح</h2><p>أصبح الموقع جاهزًا. احتفظ ببيانات المدير في مكان آمن.</p><a href="./">فتح الموقع</a></div>
<?php else: ?><?php if ($error !== ''): ?><div class="notice"><?=h($error)?></div><?php endif; ?>
<?php if (!$alreadyInstalled): ?><form method="post" enctype="multipart/form-data" autocomplete="off"><input type="hidden" name="csrf" value="<?=h((string)$_SESSION['install_csrf'])?>"><div class="grid">
<label>اسم الموقع<input name="site_name" maxlength="100" required value="<?=h((string)($_POST['site_name'] ?? 'موقع القرآن الكريم'))?>"></label>
<label>رابط الموقع الكامل<input name="site_url" dir="ltr" required value="<?=h((string)($_POST['site_url'] ?? $detectedUrl))?>"></label>
<label class="full">وصف الموقع<textarea name="site_description" maxlength="220" required><?=h((string)($_POST['site_description'] ?? 'القرآن الكريم قراءة واستماع وتفسير وترجمة بعدة لغات'))?></textarea></label>
<label class="full">صورة مشاركة الموقع
<input name="social_share_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
<span class="hint">اختياري. إذا لم ترفع صورة فسيستخدم الموقع الصورة الافتراضية. المقاس الموصى به 1200×630 بكسل، والحد الأقصى 5 ميجابايت.</span>
</label>
<label>بريد المدير<input name="admin_email" type="email" dir="ltr" required value="<?=h((string)($_POST['admin_email'] ?? ''))?>"></label>
<label>بريد استقبال رسائل التواصل<input name="contact_email" type="email" dir="ltr" value="<?=h((string)($_POST['contact_email'] ?? ''))?>"><span class="hint">يمكن تركه فارغًا؛ ستُحفظ الرسائل داخل الموقع.</span></label>
<label>بريد الإرسال<input name="mail_from_email" type="email" dir="ltr" value="<?=h((string)($_POST['mail_from_email'] ?? ''))?>"><span class="hint">يفضل بريدًا تابعًا لنطاق الموقع مثل noreply@example.com لتحسين وصول الرسائل.</span></label>
<label>كلمة مرور المدير<input name="admin_password" type="password" minlength="12" required></label>
<label>تأكيد كلمة المرور<input name="admin_password_again" type="password" minlength="12" required></label>
<label class="full check"><input name="memorial_enabled" type="checkbox" value="1" <?=isset($_POST['memorial_enabled'])?'checked':''?>> تفعيل شريط الصدقة الجارية</label>
<label>نص الصدقة الجارية<input name="memorial_title" maxlength="120" value="<?=h((string)($_POST['memorial_title'] ?? 'صدقة جارية'))?>"></label>
<label>الدعاء المختصر<input name="memorial_dua" maxlength="180" value="<?=h((string)($_POST['memorial_dua'] ?? 'اللهم اغفر له وارحمه واجعل القرآن نورًا له'))?>"></label>
<section class="full"><h2 class="appearance-title">لون الموقع</h2><span class="hint">التصميم العصري والخطوط والمقاسات ثابتة؛ اختر اللون المعتمد فقط.</span>
<div class="choice-grid">
<?php foreach (['emerald'=>'أخضر زمردي','navy'=>'أزرق كحلي','burgundy'=>'عنابي ملكي'] as $key=>$label): ?><label class="choice"><input type="radio" name="color_scheme" value="<?=$key?>" <?=((string)($_POST['color_scheme'] ?? 'emerald')===$key)?'checked':''?>><span><i class="swatch <?=$key?>"></i><?=$label?></span></label><?php endforeach; ?>
</div></section>
<label class="full check"><input name="rewrite_rules" type="checkbox" value="1" checked> تفعيل الروابط المختصرة على Apache/cPanel</label>
</div><button type="submit">تثبيت الموقع الآن</button></form><?php endif; ?><?php endif; ?>
</section></main></body></html>
