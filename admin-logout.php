<?php
require_once __DIR__ . '/includes/admin-auth.php';
qfa_auth_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']); }
session_destroy();
header('Location: admin-login.php');
exit;
