<?php
require_once __DIR__ . '/session_boot.php';

function guard_role(string $requiredRole) {
    if (empty($_SESSION['user']) || empty($_SESSION['usertype'])) {
        header('Location: /login.php'); exit;
    }
    if ($_SESSION['usertype'] !== $requiredRole) {
        header('Location: /login.php'); exit;
    }

    $timeout = $_SESSION['__timeout'] ?? 900;
    $now = time();

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
    } else {
        if (($now - (int)$_SESSION['last_activity']) > $timeout) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
            }
            session_destroy();
            header('Location: /login.php?expired=1'); exit;
        }
        $_SESSION['last_activity'] = $now;
    }

    // rotate id every 5 mins
    if (!isset($_SESSION['__created'])) $_SESSION['__created'] = $now;
    elseif ($now - $_SESSION['__created'] > 300) {
        session_regenerate_id(true);
        $_SESSION['__created'] = $now;
    }
}
