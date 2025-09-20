<?php
declare(strict_types=1);
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
$base = rtrim(str_replace('\\','/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
if ($base === '' || $base === '.') $base = '';
header('Location: ' . $base . '/');
exit;
