<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';
qfa_auth_require();

$content = '
<section class="auth-card admin-dashboard">
  <div class="admin-dashboard-head">
    <div>
      <span class="auth-eyebrow">لوحة الإدارة</span>
      <h1>إدارة الموقع</h1>
      <p>اختر القسم الذي تريد إدارته.</p>
    </div>
  </div>

  <div class="admin-dashboard-grid">

    <a class="admin-dashboard-card" href="admin-settings.php">
      <span class="admin-dashboard-icon"><i class="fas fa-cog"></i></span>
      <div>
        <strong>إعدادات الموقع</strong>
        <small>الاسم، الوصف، الثيم، البريد، صورة المشاركة والأمان.</small>
      </div>
    </a>

    <a class="admin-dashboard-card" href="sadaqah-agent.php">
      <span class="admin-dashboard-icon"><i class="fas fa-feather-alt"></i></span>
      <div>
        <strong>وكيل الصدقة الجارية</strong>
        <small>إدارة ومراجعة محتوى وكيل الصدقة الجارية.</small>
      </div>
    </a>

    <a class="admin-dashboard-card" href="index.php">
      <span class="admin-dashboard-icon"><i class="fas fa-external-link-alt"></i></span>
      <div>
        <strong>عرض الموقع</strong>
        <small>فتح الصفحة الرئيسية للموقع.</small>
      </div>
    </a>

    <a class="admin-dashboard-card admin-dashboard-logout" href="admin-logout.php">
      <span class="admin-dashboard-icon"><i class="fas fa-sign-out-alt"></i></span>
      <div>
        <strong>تسجيل الخروج</strong>
        <small>إنهاء جلسة الإدارة بأمان.</small>
      </div>
    </a>

  </div>
</section>

<style>
.admin-dashboard{width:min(980px,calc(100vw - 30px));max-width:none}
.admin-dashboard-head{margin-bottom:22px}
.admin-dashboard-head h1{margin:4px 0 6px}
.admin-dashboard-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:16px
}
.admin-dashboard-card{
  display:flex;
  align-items:center;
  gap:14px;
  padding:20px;
  border:1px solid #dbe7e3;
  border-radius:16px;
  text-decoration:none;
  color:inherit;
  background:#fff;
  transition:.2s
}
.admin-dashboard-card:hover{
  transform:translateY(-2px);
  border-color:#9fc3b7;
  box-shadow:0 10px 28px rgba(0,0,0,.07)
}
.admin-dashboard-icon{
  display:grid;
  place-items:center;
  width:52px;
  height:52px;
  flex:0 0 52px;
  border-radius:14px;
  background:#edf7f3;
  color:#0c6654;
  font-size:20px
}
.admin-dashboard-card div{display:grid;gap:5px}
.admin-dashboard-card strong{font-size:16px}
.admin-dashboard-card small{color:#7b8b85;line-height:1.7}
.admin-dashboard-logout .admin-dashboard-icon{
  background:#fff0ef;
  color:#a43d34
}
html[data-theme="dark"] .admin-dashboard-card{
  background:#16211e;
  border-color:#2a3934
}
@media(max-width:700px){
  .admin-dashboard-grid{grid-template-columns:1fr}
}
</style>
';

qfa_auth_page('لوحة الإدارة', $content);
