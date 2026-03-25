<?php
require_once __DIR__ . '/includes/config.php';
// Session ini already configured via db.php when included elsewhere;
// here we use session_start() directly since there is no DB needed.
$_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		  || (($_SERVER['SERVER_PORT'] ?? null) == 443)
		  || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_secure', $_isHttps ? '1' : '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
unset($_isHttps);
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$p = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header("Location: " . ADMIN_LOGIN_PATH);
exit;
