<?php
// ==========================================================
// Nexora Admin — Auth guard
// Include this at the very top of every protected admin page,
// BEFORE any HTML/output, e.g.:
//     require_once '../config/auth_check.php';
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie before starting it.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,   // JS can't read the session cookie
        'samesite' => 'Lax',  // basic CSRF mitigation for the cookie itself
        // 'secure' => true,   // uncomment once the site runs on HTTPS
    ]);
    session_start();
}

// ---- Idle timeout: auto-logout after 30 minutes of inactivity ----
$idle_limit = 1800; // seconds
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > $idle_limit) {
    $_SESSION = [];
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['admin_last_activity'] = time();

// ---- Require a logged-in admin ----
if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// ---- Regenerate the session ID periodically (session fixation hardening) ----
if (empty($_SESSION['admin_id_regenerated']) || (time() - $_SESSION['admin_id_regenerated']) > 900) {
    session_regenerate_id(true);
    $_SESSION['admin_id_regenerated'] = time();
}
