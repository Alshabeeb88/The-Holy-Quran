<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/config-writer.php';
require_once __DIR__ . '/includes/pages.php';
qfa_auth_require();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/*
 * Cached pages and sitemaps still carry links to a page that was just switched
 * off (or lack links to one switched back on), so both are cleared. Only this
 * application's own artefacts are removed: *.html.php in cache/ and the three
 * generated sitemap files, which are rebuilt on the next home page visit.
 */
function qfa_pages_clear_generated(): void {
    foreach (glob(__DIR__ . '/cache/*.html.php') ?: [] as $file) {
        if (is_file($file) && !is_link($file)) {
            @unlink($file);
        }
    }
    foreach (['sitemap-surah.xml', 'sitemap-tafseer.xml', 'sitemap-language.xml'] as $name) {
        $file = __DIR__ . '/sitemaps/' . $name;
        if (is_file($file) && !is_link($file)) {
            @unlink($file);
        }
    }
}

$configFile = __DIR__ . '/includes/config.php';
$pages = qfa_pages();
$error = '';
$success = isset($_GET['saved']) && $_GET['saved'] === '1' ? 'تم حفظ حالة الصفحات.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة.';
    } else {
        $enabled = isset($_POST['enabled']) && is_array($_POST['enabled']) ? array_map('strval', $_POST['enabled']) : [];
        $disabled = [];
        foreach (array_keys($pages) as $key) {
            if (!in_array($key, $enabled, true)) {
                $disabled[] = $key;
            }
        }

        $original = (string)@file_get_contents($configFile);
        if ($original === '' || !is_writable($configFile)) {
            $error = 'تعذر تعديل ملف إعدادات الموقع.';
        } else {
            $code = qfa_config_set($original, 'DISABLED_PAGES', $disabled);
            if (!qfa_config_commit($configFile, $code, ['DISABLED_PAGES' => $disabled])) {
                qfa_config_commit($configFile, $original, []);
                $error = 'تعذر حفظ حالة الصفحات، ولم يتغير شيء في الموقع.';
            } else {
                qfa_pages_clear_generated();
                header('Location: admin-pages.php?saved=1', true, 303);
                exit;
            }
        }
    }
}

$disabledNow = qfa_disabled_pages();
$rows = '';
foreach ($pages as $key => $page) {
    $on = !in_array($key, $disabledNow, true);
    $rows .= '
        <label class="settings-check page-toggle">
          <input type="checkbox" name="enabled[]" value="' . e($key) . '" ' . ($on ? 'checked' : '') . '>
          <span><strong>' . e($page['label']) . '</strong><small>' . e($page['hint']) . '</small></span>
          <em class="page-state ' . ($on ? 'is-on' : 'is-off') . '">' . ($on ? 'مفعّلة' : 'معطّلة') . '</em>
        </label>';
}

$content = '
<section class="auth-card settings-page">
  <div class="settings-head">
    <div>
      <h1>إدارة الصفحات</h1>
      <p class="auth-note">فعّل أو عطّل صفحات الموقع الرئيسية. الصفحة المعطّلة تختفي من القوائم والصفحة الرئيسية، ومن يفتح رابطها يرى أنها غير متاحة.</p>
    </div>
  </div>

  ' . ($error !== '' ? '<div class="settings-error">' . e($error) . '</div>' : '') . '
  ' . ($success !== '' ? '<div class="settings-success">' . e($success) . '</div>' : '') . '

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="' . e(qfa_auth_csrf()) . '">

    <section class="settings-panel">
      <div class="panel-head">
        <span class="panel-icon">☰</span>
        <div>
          <h2>الصفحات الرئيسية</h2>
          <p>الصفحة الرئيسية تبقى مفعّلة دائمًا.</p>
        </div>
      </div>
      ' . $rows . '
    </section>

    <div class="settings-actions">
      <button type="submit">حفظ التعديلات</button>
      <a href="admin.php">العودة إلى لوحة الإدارة</a>
      <a class="settings-logout" href="admin-logout.php">تسجيل الخروج</a>
    </div>
  </form>
</section>

<link rel="stylesheet" href="style/default/css/admin-settings.css?v=1.0">
<style>
.page-toggle{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #e6efec}
.page-toggle:last-of-type{border-bottom:0}
.page-toggle span{display:grid;gap:3px;flex:1}
.page-toggle small{color:#7b8b85;font-weight:400}
.page-state{font-style:normal;font-size:13px;padding:3px 10px;border-radius:999px}
.page-state.is-on{background:#edf7f3;color:#0c6654}
.page-state.is-off{background:#fff0ef;color:#a43d34}
html[data-theme="dark"] .page-toggle{border-color:#2a3934}
</style>
';

qfa_auth_page('إدارة الصفحات', $content);
