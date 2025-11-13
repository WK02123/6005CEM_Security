<?php
// Runs before every PHP script
require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/auth_guard.php';

$path = $_SERVER['SCRIPT_NAME'] ?? '';                 // e.g. /patient/index.php
$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

// Skip non-PHP files
if ($ext && $ext !== 'php') return;

// Public PHP routes (no guard)
$public = [
  '/login.php',
  '/create-account.php',
  '/signup.php',
  '/otp-verify.php',
  '/logout.php',
  '/index.php',     // keep/remove as needed
];
if (in_array($path, $public, true)) return;

// Skip common asset prefixes even if routed via PHP
$assetPrefixes = ['/css/', '/js/', '/img/', '/assets/', '/vendor/'];
foreach ($assetPrefixes as $p) {
  if (str_starts_with($path, $p)) return;
}

// Protect folders by role
if (str_starts_with($path, '/admin/'))  { guard_role('a'); return; }
if (str_starts_with($path, '/doctor/')) { guard_role('d'); return; }
if (str_starts_with($path, '/patient/')){ guard_role('p'); return; }

// default: do nothing for other paths
