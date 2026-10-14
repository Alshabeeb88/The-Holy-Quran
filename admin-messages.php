<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
qfa_auth_require();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$logFile = __DIR__ . '/includes/contact_messages.php';

/**
 * Read the stored messages under a shared lock. Each record is keyed by the
 * hash of its stored line, which is stable for as long as the line exists and
 * lets a delete name exactly one record without trusting an index.
 *
 * @return array<string, array>
 */
function qfa_messages_read(string $file): array {
    if (!is_file($file)) {
        return [];
    }
    $handle = @fopen($file, 'r');
    if (!$handle) {
        return [];
    }
    flock($handle, LOCK_SH);
    $raw = (string)stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    $body = (string)preg_replace('/^<\?php\s+exit;\s*\?>\s*/', '', $raw);
    $records = [];
    foreach (explode("\n", $body) as $line) {
        if ($line === '') {
            continue;
        }
        $record = json_decode($line, true);
        if (is_array($record)) {
            $records[hash('sha256', $line)] = $record;
        }
    }
    return $records;
}

/** Remove the records whose line hashes are listed, under an exclusive lock. */
function qfa_messages_delete(string $file, array $ids): bool {
    $handle = @fopen($file, 'c+');
    if (!$handle) {
        return false;
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }
    rewind($handle);
    $raw = (string)stream_get_contents($handle);
    $body = (string)preg_replace('/^<\?php\s+exit;\s*\?>\s*/', '', $raw);

    $keep = [];
    foreach (explode("\n", $body) as $line) {
        if ($line !== '' && !in_array(hash('sha256', $line), $ids, true)) {
            $keep[] = $line;
        }
    }
    $payload = "<?php exit; ?>\n" . ($keep === [] ? '' : implode("\n", $keep) . "\n");

    rewind($handle);
    $ok = ftruncate($handle, 0) && fwrite($handle, $payload) === strlen($payload);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return $ok;
}

$error = '';
$deletedCount = (int)($_GET['deleted'] ?? 0);
$success = $deletedCount === 1
    ? 'تم حذف الرسالة.'
    : ($deletedCount > 1 ? 'تم حذف ' . $deletedCount . ' رسائل.' : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A row's own delete button sends one id; "delete selected" sends the
    // ticked ids. Every id must be a 64-hex line hash or nothing is deleted.
    $single = $_POST['id'] ?? null;
    $ids = is_string($single) && $single !== ''
        ? [$single]
        : (is_array($_POST['ids'] ?? null) ? array_values($_POST['ids']) : []);
    $idsValid = $ids !== [] && count($ids) <= 1000;
    foreach ($ids as $id) {
        if (!is_string($id) || !preg_match('/^[0-9a-f]{64}$/D', $id)) {
            $idsValid = false;
            break;
        }
    }
    $ids = array_values(array_unique($ids));

    if (!qfa_auth_check_csrf((string)($_POST['csrf'] ?? ''))) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة.';
    } elseif ($ids === []) {
        $error = 'حدد رسالة واحدة على الأقل.';
    } elseif (!$idsValid || !qfa_messages_delete($logFile, $ids)) {
        $error = 'تعذر حذف الرسائل.';
    } else {
        header('Location: admin-messages.php?deleted=' . count($ids), true, 303);
        exit;
    }
}

$records = array_reverse(qfa_messages_read($logFile), true);
$csrf = e(qfa_auth_csrf());
$items = '';
foreach ($records as $id => $record) {
    $time = (string)($record['time'] ?? '');
    $stamp = $time !== '' ? strtotime($time) : false;
    $items .= '
      <article class="message-item">
        <header>
          <label class="message-pick"><input type="checkbox" name="ids[]" value="' . e($id) . '" form="messages-form" aria-label="تحديد الرسالة"></label>
          <strong>' . e((string)($record['subject'] ?? '') !== '' ? (string)$record['subject'] : 'بدون عنوان') . '</strong>
          <time>' . e($stamp ? date('Y-m-d H:i', $stamp) : $time) . '</time>
        </header>
        <p class="message-from">' . e((string)($record['name'] ?? '')) . ' &lt;<span dir="ltr">' . e((string)($record['email'] ?? '')) . '</span>&gt;</p>
        <p class="message-body">' . nl2br(e((string)($record['message'] ?? ''))) . '</p>
        <div class="message-delete">
          <button type="submit" name="id" value="' . e($id) . '" form="messages-form" data-confirm="حذف هذه الرسالة نهائيًا؟">حذف</button>
        </div>
      </article>';
}
if ($items === '') {
    $items = '<p class="auth-note">لا توجد رسائل محفوظة.</p>';
}

$content = '
<section class="auth-card settings-page">
  <div class="settings-head">
    <div>
      <h1>رسائل التواصل</h1>
      <p class="auth-note">الرسائل المرسلة من نموذج التواصل، الأحدث أولًا. تُنقل الرسائل الأقدم من 90 يومًا إلى الأرشيف تلقائيًا، وتُحذف من الأرشيف نهائيًا بعد سنة.</p>
    </div>
  </div>

  ' . ($error !== '' ? '<div class="settings-error">' . e($error) . '</div>' : '') . '
  ' . ($success !== '' ? '<div class="settings-success">' . e($success) . '</div>' : '') . '

  <section class="settings-panel">
    <div class="panel-head">
      <span class="panel-icon">✉</span>
      <div>
        <h2>الرسائل (' . count($records) . ')</h2>
        <p>اضغط حذف لإزالة رسالة نهائيًا، أو حدد عدة رسائل واحذفها معًا.</p>
      </div>
    </div>
    ' . ($records !== [] ? '
    <form method="post" id="messages-form" class="messages-toolbar">
      <input type="hidden" name="csrf" value="' . $csrf . '">
      <label class="messages-select-all"><input type="checkbox" id="messages-select-all"> تحديد الكل</label>
      <button type="submit" class="messages-delete-selected" data-confirm="حذف الرسائل المحددة نهائيًا؟" data-needs-selection="1">حذف المحدد</button>
    </form>' : '') . '
    ' . $items . '
  </section>

  <div class="settings-actions">
    <a href="admin.php">العودة إلى لوحة الإدارة</a>
    <a class="settings-logout" href="admin-logout.php">تسجيل الخروج</a>
  </div>
</section>

<link rel="stylesheet" href="style/default/css/admin-settings.css?v=1.3">
<script src="style/default/js/admin-messages.js?v=1.0" defer></script>
<style>
.message-item{padding:14px 0;border-bottom:1px solid #e6efec}
.message-item:last-child{border-bottom:0}
.message-item header{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.message-item header strong{flex:1;min-width:0;overflow-wrap:anywhere}
.message-pick input,.messages-select-all input{width:18px;height:18px;min-height:0;margin:0;accent-color:#0b6555;cursor:pointer}
.messages-toolbar{display:flex!important;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 0 12px;border-bottom:1px solid #e6efec}
.messages-select-all{display:flex!important;align-items:center;gap:8px;font-size:13px;cursor:pointer}
.messages-toolbar .messages-delete-selected{min-height:40px;padding:0 18px;background:#fff0ef;color:#a43d34;border:1px solid #f1c9c5}
.messages-toolbar .messages-delete-selected:disabled{opacity:.5;cursor:not-allowed}
html[data-theme="dark"] .messages-toolbar{border-color:#2a3934}
html[data-theme="dark"] .message-delete button,html[data-theme="dark"] .messages-toolbar .messages-delete-selected{background:#321d19;color:#ffb3a9;border-color:#673a34}
.message-item time{color:#7b8b85;font-size:13px;direction:ltr}
.message-from{margin:6px 0;color:#4f625c;font-size:14px}
.message-body{margin:8px 0;line-height:1.9;white-space:normal;overflow-wrap:anywhere}
.message-delete button{min-height:0;background:#fff0ef;color:#a43d34;border:1px solid #f1c9c5;border-radius:10px;padding:6px 16px;cursor:pointer}
html[data-theme="dark"] .message-item{border-color:#2a3934}
</style>
';

qfa_auth_page('رسائل التواصل', $content);
