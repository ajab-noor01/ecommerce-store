<?php
// ==========================================================
// Nexora Admin — Login
// Security measures:
//   - Passwords checked with password_verify() against a bcrypt hash
//     (users.password must be created with password_hash(), see
//     create_admin.php)
//   - Prepared statements (no SQL injection)
//   - CSRF token on the form
//   - Session-based brute-force lockout (5 failed tries -> 60s cooldown)
//   - Session ID regenerated on successful login (fixation hardening)
//   - Generic error message (doesn't reveal whether the email exists)
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Already logged in? Skip straight to the dashboard.
if (!empty($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin') {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
/** @var mysqli $conn */

// ---- CSRF token ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$timed_out  = isset($_GET['timeout']);
$logged_out = isset($_GET['loggedout']);

// ---- Brute-force lockout config ----
$max_attempts   = 5;
$lockout_second = 60;

if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
    $elapsed = time() - ($_SESSION['login_locked_at'] ?? 0);
    if ($elapsed < $lockout_second) {
        $error = 'Too many failed attempts. Please wait ' . ($lockout_second - $elapsed) . ' seconds and try again.';
    } else {
        // Lockout window passed — reset the counter.
        $_SESSION['login_attempts'] = 0;
    }
}

if ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Same generic error whether the email doesn't exist or the
            // password is wrong — don't leak which one it was.
            if ($user && password_verify($password, $user['password'])) {

                // ---- Success ----
                session_regenerate_id(true);
                $_SESSION['admin_id']    = $user['id'];
                $_SESSION['admin_name']  = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_role']  = $user['role'];
                $_SESSION['admin_last_activity']   = time();
                $_SESSION['admin_id_regenerated']  = time();
                unset($_SESSION['login_attempts'], $_SESSION['login_locked_at']);

                header('Location: index.php');
                exit;
            } else {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                if ($_SESSION['login_attempts'] >= $max_attempts) {
                    $_SESSION['login_locked_at'] = time();
                    $error = 'Too many failed attempts. Please wait ' . $lockout_second . ' seconds and try again.';
                } else {
                    $error = 'Incorrect email or password.';
                }
                // Small fixed delay slows down automated brute-force attempts.
                usleep(400000);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Admin Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap" rel="stylesheet">

<style>
:root{
    --ink:#1B1F1C; --ink-soft:#4B564F; --muted:#7C8781; --line:#E4E7E1;
    --paper:#F4F6F2; --surface:#FFFFFF;
    --evergreen:#1F6F54; --evergreen-deep:#123A2C; --evergreen-900:#0E241C;
    --amber:#C1791F; --amber-soft:#FBEEDD; --rose:#B3432E; --rose-soft:#FBEAE6;
    --radius-lg:18px; --shadow-card:0 1px 2px rgba(18,58,44,.04), 0 8px 24px rgba(18,58,44,.05);
}
*{ box-sizing:border-box; }
body{
    margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
    font-family:'Inter', -apple-system, Segoe UI, sans-serif;
    background: linear-gradient(180deg, var(--evergreen-deep) 0%, var(--evergreen-900) 100%);
    padding:20px;
}
.login-card{
    width:100%; max-width:380px; background:var(--surface); border-radius:var(--radius-lg);
    box-shadow:0 20px 60px rgba(0,0,0,.25); padding:36px 32px;
}
.brand{ display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:26px; }
.brand-mark{
    width:38px; height:38px; border-radius:10px; background:var(--amber); color:var(--evergreen-900);
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px;
    font-family:'Fraunces', serif;
}
.brand-name{ font-size:20px; font-weight:600; color:var(--evergreen-900); font-family:'Fraunces', serif; }

h1{ font-size:19px; font-weight:600; text-align:center; margin:0 0 4px; color:var(--ink); }
.sub{ text-align:center; color:var(--muted); font-size:13px; margin-bottom:24px; }

.alert{ border:none; border-left:3px solid; border-radius:8px; padding:11px 14px; font-size:13px; font-weight:500; margin-bottom:18px; }
.alert.danger{ background:var(--rose-soft); color:var(--rose); border-color:var(--rose); }
.alert.info{ background:#EAF1EC; color:var(--evergreen-deep); border-color:var(--evergreen); }

.field-label{ font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; color:var(--ink-soft); margin-bottom:6px; display:block; }
.form-control{
    width:100%; border:1.5px solid var(--line); border-radius:10px; padding:11px 13px;
    font-size:13.5px; color:var(--ink); margin-bottom:16px; outline:none;
}
.form-control:focus{ border-color:var(--evergreen); box-shadow:0 0 0 3px rgba(31,111,84,.1); }

.pw-wrap{ position:relative; }
.pw-wrap .form-control{ padding-right:40px; }
.pw-toggle{
    position:absolute; right:12px; top:11px; background:none; border:none; color:var(--muted);
    cursor:pointer; font-size:13px;
}

.btn-login{
    width:100%; background:var(--evergreen); color:#fff; border:none; padding:12px;
    border-radius:10px; font-weight:600; font-size:13.5px; cursor:pointer; margin-top:6px;
    transition: background .15s ease;
}
.btn-login:hover{ background:var(--evergreen-deep); }
.btn-login:disabled{ opacity:.6; cursor:not-allowed; }

.foot-note{ text-align:center; font-size:11.5px; color:var(--muted); margin-top:22px; }
</style>
</head>
<body>

<div class="login-card">
    <div class="brand">
        <div class="brand-mark">Z</div>
        <div class="brand-name">Zentury</div>
    </div>

    <h1>Admin sign in</h1>
    <p class="sub">Enter your admin credentials to continue.</p>

    <?php if ($timed_out): ?>
        <div class="alert info">You were signed out after being idle. Please sign in again.</div>
    <?php elseif ($logged_out): ?>
        <div class="alert info">You've been signed out.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <label class="field-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus placeholder="admin@nexora.com">

        <label class="field-label">Password</label>
        <div class="pw-wrap">
            <input type="password" name="password" id="pwField" class="form-control" required placeholder="••••••••">
            <button type="button" class="pw-toggle" onclick="togglePw()"><i class="fa-solid fa-eye" id="pwIcon"></i></button>
        </div>

        <button type="submit" class="btn-login">Sign in</button>
    </form>

    <p class="foot-note">Access is restricted to Nexora store administrators.</p>
</div>

<script>
function togglePw() {
    const field = document.getElementById('pwField');
    const icon = document.getElementById('pwIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}
</script>

</body>
</html>
