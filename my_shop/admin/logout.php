<?php
// ==========================================================
// Nexora Admin — Logout
// Fully destroys the session: clears session vars, removes the
// session cookie, and destroys the server-side session data.
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables.
$_SESSION = [];

// Remove the session cookie itself from the browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session data server-side.
session_destroy();

header('Location: login.php?loggedout=1');
exit;
