<?php
// session_boot.php
$timeout = 30; // 5 minutes

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', (string)$timeout);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    $_SESSION['__timeout'] = $timeout;
} else {
    if (!isset($_SESSION['__timeout'])) $_SESSION['__timeout'] = $timeout;
}
