<?php
require_once __DIR__.'/includes/admin-auth.php';
qfa_auth_require();
$_GET['action'] = 'sadaqah_agent';
include __DIR__.'/index.php';
