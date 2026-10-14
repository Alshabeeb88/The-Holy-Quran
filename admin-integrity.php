<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/data-integrity.php';
qfa_auth_require();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$result = null;

// Hashing every data file takes a second or two, so it runs on request only.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة.';
    } else {
        @set_time_limit(120);
        $result = qfa_data_verify(__DIR__);
    }
}

$report = '<p class="auth-note">اضغط "افحص الآن" لمطابقة ملفات المصادر مع البصمات المعتمدة.</p>';
if ($result !== null) {
    if (!$result['manifest']) {
        $report = '<div class="settings-error">ملف البصمات includes/data-manifest.json غير موجود أو تالف. ارفعه من آخر إصدار.</div>';
    } elseif ($result['ok']) {
        $report = '<div class="settings-success">سليمة: ' . (int)$result['checked'] . ' ملفًا من القرآن والتفاسير والترجمات والكتب تطابق النسخة المعتمدة حرفًا بحرف.</div>';
    } else {
        $list = '';
        foreach (['changed' => 'معدّل', 'missing' => 'مفقود', 'added' => 'ملف إضافي'] as $kind => $label) {
            foreach (array_slice($result[$kind], 0, 50) as $path) {
                $list .= '<li><span class="integrity-tag">' . e($label) . '</span> <code dir="ltr">' . e($path) . '</code></li>';
            }
        }
        $report = '<div class="settings-error">تنبيه: '
            . count($result['changed']) . ' ملف معدّل، '
            . count($result['missing']) . ' مفقود، '
            . count($result['added']) . ' إضافي. أعد رفع هذه الملفات من المستودع الرسمي، وراجع سبب التغيير.</div>'
            . '<ul class="integrity-list">' . $list . '</ul>';
    }
}

$content = '
<section class="auth-card settings-page">
  <div class="settings-head">
    <div>
      <h1>سلامة المصادر</h1>
      <p class="auth-note">يتحقق من أن نص القرآن الكريم والتفاسير والترجمات والكتب لم يتغير فيه حرف واحد، بمقارنة بصمة SHA-256 لكل ملف مع النسخة المعتمدة.</p>
    </div>
  </div>

  ' . ($error !== '' ? '<div class="settings-error">' . e($error) . '</div>' : '') . '

  <section class="settings-panel">
    <div class="panel-head">
      <span class="panel-icon">✓</span>
      <div>
        <h2>فحص الملفات</h2>
        <p>الفحص للقراءة فقط ولا يغيّر أي ملف.</p>
      </div>
    </div>
    ' . $report . '
    <form method="post" class="settings-actions">
      <input type="hidden" name="csrf" value="' . e(qfa_auth_csrf()) . '">
      <button type="submit">افحص الآن</button>
    </form>
  </section>

  <div class="settings-actions">
    <a href="admin.php">العودة إلى لوحة الإدارة</a>
    <a class="settings-logout" href="admin-logout.php">تسجيل الخروج</a>
  </div>
</section>

<link rel="stylesheet" href="style/default/css/admin-settings.css?v=1.0">
<style>
.integrity-list{list-style:none;margin:12px 0 0;padding:0;max-height:320px;overflow:auto}
.integrity-list li{padding:6px 0;border-bottom:1px solid #e6efec;overflow-wrap:anywhere}
.integrity-tag{display:inline-block;min-width:70px;color:#a43d34;font-weight:700}
html[data-theme="dark"] .integrity-list li{border-color:#2a3934}
</style>
';

qfa_auth_page('سلامة المصادر', $content);
