<?php
// csrf.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function csrf_token(string $purpose, int $ttlSeconds = 900): string {
    // one token per purpose (e.g., 'login', 'register', 'otp')
    $key = "csrf_$purpose";
    if (empty($_SESSION[$key]) || time() >= ($_SESSION[$key]['exp'] ?? 0)) {
        $_SESSION[$key] = [
            'value' => bin2hex(random_bytes(32)),
            'exp'   => time() + $ttlSeconds,  // 15 minutes default
        ];
    }
    return $_SESSION[$key]['value'];
}

function csrf_validate(string $purpose, ?string $token): bool {
    $key = "csrf_$purpose";
    $ok  = isset($_SESSION[$key]['value'], $_SESSION[$key]['exp'])
        && hash_equals($_SESSION[$key]['value'], (string)$token)
        && time() < $_SESSION[$key]['exp'];

    // rotate token after a successful check (prevents replay)
    if ($ok) unset($_SESSION[$key]);
    return $ok;
}
