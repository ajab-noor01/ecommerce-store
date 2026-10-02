<?php
// ==========================================================
// Nexora Admin — Settings
// Three panels:
//   1. Store Information (site_settings table) — feeds contact.php & footer.php
//   2. My Account (admin name/email)
//   3. Change Password (bcrypt)
// ==========================================================
require_once '../config/auth_check.php';
include '../config/db.php';
/** @var mysqli $conn */

$messages = []; // ['panel' => 'store'|'account'|'password', 'text' => ..., 'type' => success|danger|warning]

// ---------------------------------------------------------
// Ensure site_settings table + row exist (graceful, no crash
// if the migration hasn't been run yet)
// ---------------------------------------------------------
$settings_table_check = $conn->query("SHOW TABLES LIKE 'site_settings'");
$has_settings_table = $settings_table_check && $settings_table_check->num_rows > 0;

$settings = [
    'store_name' => 'Nexora', 'phone' => '', 'email' => '', 'address' => '', 'city' => '', 'state' => '',
];
if ($has_settings_table) {
    $row = $conn->query("SELECT * FROM site_settings WHERE id = 1")->fetch_assoc();
    if ($row) $settings = array_merge($settings, $row);
}

// ---------------------------------------------------------
// 1. Update store information
// ---------------------------------------------------------
if (isset($_POST['update_store'])) {
    if (!$has_settings_table) {
        $messages[] = ['panel' => 'store', 'text' => '⚠️ site_settings table not found. Run site_settings_migration.sql first.', 'type' => 'danger'];
    } else {
        $store_name = trim($_POST['store_name'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $address    = trim($_POST['address'] ?? '');
        $city       = trim($_POST['city'] ?? '');
        $state      = trim($_POST['state'] ?? '');

        if ($store_name === '') {
            $messages[] = ['panel' => 'store', 'text' => '⚠️ Store name is required.', 'type' => 'warning'];
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messages[] = ['panel' => 'store', 'text' => '⚠️ Enter a valid email address.', 'type' => 'warning'];
        } else {
            $stmt = $conn->prepare("
                INSERT INTO site_settings (id, store_name, phone, email, address, city, state)
                VALUES (1, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    store_name = VALUES(store_name), phone = VALUES(phone), email = VALUES(email),
                    address = VALUES(address), city = VALUES(city), state = VALUES(state)
            ");
            $stmt->bind_param("ssssss", $store_name, $phone, $email, $address, $city, $state);
            if ($stmt->execute()) {
                $messages[] = ['panel' => 'store', 'text' => '✅ Store information updated.', 'type' => 'success'];
                $settings = compact('store_name', 'phone', 'email', 'address', 'city', 'state');
            } else {
                $messages[] = ['panel' => 'store', 'text' => 'Error: ' . $stmt->error, 'type' => 'danger'];
            }
            $stmt->close();
        }
    }
}

// ---------------------------------------------------------
// 2. Update admin account (name / email)
// ---------------------------------------------------------
if (isset($_POST['update_account'])) {
    $name  = trim($_POST['admin_name'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');

    if ($name === '' || $email === '') {
        $messages[] = ['panel' => 'account', 'text' => '⚠️ Name and email are required.', 'type' => 'warning'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messages[] = ['panel' => 'account', 'text' => '⚠️ Enter a valid email address.', 'type' => 'warning'];
    } else {
        // Make sure no OTHER admin already uses this email.
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->bind_param("si", $email, $_SESSION['admin_id']);
        $chk->execute();
        $taken = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($taken) {
            $messages[] = ['panel' => 'account', 'text' => '⚠️ That email is already in use by another account.', 'type' => 'warning'];
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                $_SESSION['admin_name']  = $name;
                $_SESSION['admin_email'] = $email;
                $messages[] = ['panel' => 'account', 'text' => '✅ Account details updated.', 'type' => 'success'];
            } else {
                $messages[] = ['panel' => 'account', 'text' => 'Error: ' . $stmt->error, 'type' => 'danger'];
            }
            $stmt->close();
        }
    }
}

// ---------------------------------------------------------
// 3. Change password
// ---------------------------------------------------------
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password'])) {
        $messages[] = ['panel' => 'password', 'text' => '⚠️ Current password is incorrect.', 'type' => 'danger'];
    } elseif (strlen($new) < 8) {
        $messages[] = ['panel' => 'password', 'text' => '⚠️ New password must be at least 8 characters.', 'type' => 'warning'];
    } elseif ($new !== $confirm) {
        $messages[] = ['panel' => 'password', 'text' => '⚠️ New passwords do not match.', 'type' => 'warning'];
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $_SESSION['admin_id']);
        if ($stmt->execute()) {
            $messages[] = ['panel' => 'password', 'text' => '✅ Password changed successfully.', 'type' => 'success'];
        } else {
            $messages[] = ['panel' => 'password', 'text' => 'Error: ' . $stmt->error, 'type' => 'danger'];
        }
        $stmt->close();
    }
}

function panel_message(array $messages, string $panel) {
    foreach ($messages as $m) {
        if ($m['panel'] === $panel) {
            echo '<div class="alert-panel ' . htmlspecialchars($m['type']) . '">' . htmlspecialchars($m['text']) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Settings</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;600&display=swap" rel="stylesheet">

<style>
:root{
    --ink:#1B1F1C; --ink-soft:#4B564F; --muted:#7C8781; --line:#E4E7E1; --paper:#F4F6F2; --surface:#FFFFFF;
    --evergreen:#1F6F54; --evergreen-deep:#123A2C; --evergreen-900:#0E241C;
    --amber:#C1791F; --amber-soft:#FBEEDD; --rose:#B3432E; --rose-soft:#FBEAE6; --green-soft:#E6F2EB;
    --radius-sm:8px; --radius-md:12px; --radius-lg:18px;
    --shadow-card:0 1px 2px rgba(18,58,44,0.04), 0 8px 24px rgba(18,58,44,0.05);
}
*{ box-sizing:border-box; }
body{ margin:0; font-family:'Inter', -apple-system, Segoe UI, sans-serif; background:var(--paper); color:var(--ink); font-size:14.5px; -webkit-font-smoothing:antialiased; }
.brand-mark, h1.page-title{ font-family:'Fraunces', Georgia, serif; }
a{ text-decoration:none; color:inherit; }

.sidebar{ position:fixed; left:0; top:0; width:252px; height:100vh; background:linear-gradient(180deg, var(--evergreen-deep) 0%, var(--evergreen-900) 100%); color:#EAF1EC; padding:26px 18px; z-index:1000; display:flex; flex-direction:column; transition: width .25s ease, padding .25s ease; }
.sidebar.collapsed{ width:82px; padding:26px 12px; }
.sidebar.collapsed .brand-name, .sidebar.collapsed .nav-label, .sidebar.collapsed .nav-section-label, .sidebar.collapsed .sidebar-foot span{ display:none; }
.sidebar.collapsed .sidebar-menu a{ justify-content:center; }
.sidebar.collapsed .brand{ justify-content:center; }
.brand{ display:flex; align-items:center; gap:10px; padding:4px 8px 26px 8px; }
.brand-mark{ width:34px; height:34px; border-radius:9px; background:var(--amber); color:var(--evergreen-900); display:flex; align-items:center; justify-content:center; font-weight:600; font-size:16px; flex-shrink:0; }
.brand-name{ font-size:18px; font-weight:600; letter-spacing:.2px; color:#F6F1E7; white-space:nowrap; }
.nav-section-label{ font-size:11px; color:#8FAE9E; text-transform:uppercase; letter-spacing:1px; margin:18px 12px 8px; }
.sidebar-menu{ list-style:none; margin:0; padding:0; }
.sidebar-menu li{ margin-bottom:2px; }
.sidebar-menu a{ position:relative; display:flex; align-items:center; gap:13px; padding:10px 12px; border-radius:var(--radius-sm); color:#C6D8CD; font-size:14px; font-weight:500; transition: background .18s ease, color .18s ease; }
.sidebar-menu a i{ width:18px; text-align:center; font-size:14px; }
.sidebar-menu a:hover{ background: rgba(255,255,255,0.06); color:#F6F1E7; }
.sidebar-menu a.active{ background: rgba(193,121,31,0.16); color:#F6F1E7; }
.sidebar-menu a.active::before{ content:""; position:absolute; left:-18px; top:8px; bottom:8px; width:3px; border-radius:3px; background:var(--amber); }
.sidebar.collapsed .sidebar-menu a.active::before{ left:-12px; }
.sidebar-foot{ margin-top:auto; display:flex; align-items:center; gap:10px; padding:12px; border-radius:var(--radius-sm); background: rgba(255,255,255,0.05); }
.sidebar-foot i{ color:#8FAE9E; }
.sidebar-foot span{ font-size:12.5px; color:#B7CBBE; line-height:1.3; }

.main-content{ margin-left:252px; min-height:100vh; transition: margin-left .25s ease; }
.main-content.expanded{ margin-left:82px; }
.topbar{ height:72px; background:var(--surface); border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 28px; position:sticky; top:0; z-index:900; }
.topbar-left{ display:flex; align-items:center; gap:16px; }
.sidebar-toggle{ width:36px; height:36px; border-radius:var(--radius-sm); border:1px solid var(--line); background:var(--surface); display:flex; align-items:center; justify-content:center; color:var(--ink-soft); cursor:pointer; }
.sidebar-toggle:hover{ background:var(--paper); }
.breadcrumb-trail{ font-size:12.5px; color:var(--muted); }
.breadcrumb-trail strong{ color:var(--ink); font-weight:600; }
.admin-profile{ display:flex; align-items:center; gap:11px; }
.profile-avatar{ width:38px; height:38px; border-radius:50%; background:var(--green-soft); color:var(--evergreen); display:flex; align-items:center; justify-content:center; font-weight:600; font-size:13px; }
.profile-text strong{ display:block; font-size:13.5px; font-weight:600; }
.profile-text span{ font-size:11.5px; color:var(--muted); }

.page-wrap{ padding:30px 28px 60px; max-width:820px; }
.page-head{ padding-bottom:20px; margin-bottom:24px; border-bottom:1px solid var(--line); }
h1.page-title{ font-size:25px; font-weight:500; margin:0 0 6px; color:var(--evergreen-900); }
.page-sub{ margin:0; color:var(--muted); font-size:13.5px; }

.alert-panel{ border:none; border-left:3px solid; border-radius:var(--radius-sm); padding:12px 16px; font-size:13.5px; font-weight:500; margin-bottom:18px; }
.alert-panel.success{ background:var(--green-soft); color:var(--evergreen-deep); border-color:var(--evergreen); }
.alert-panel.danger{ background:var(--rose-soft); color:var(--rose); border-color:var(--rose); }
.alert-panel.warning{ background:var(--amber-soft); color:var(--amber); border-color:var(--amber); }

.panel{ background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); padding:26px; margin-bottom:24px; }
.panel-title{ font-size:16px; font-weight:600; color:var(--ink); margin-bottom:4px; display:flex; align-items:center; gap:10px; }
.panel-title i{ color:var(--evergreen); }
.panel-sub{ font-size:12.5px; color:var(--muted); margin-bottom:20px; }

.field-label{ font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; color:var(--ink-soft); margin-bottom:7px; display:block; }
.form-control{ border:1.5px solid var(--line); border-radius:10px; padding:10px 13px; font-size:13.5px; color:var(--ink); width:100%; }
.form-control:focus{ outline:none; border-color:var(--evergreen); box-shadow:0 0 0 3px rgba(31,111,84,.1); }

.btn-save{ display:inline-flex; align-items:center; gap:8px; background:var(--evergreen); color:#fff; border:none; padding:11px 20px; border-radius:10px; font-size:13.5px; font-weight:600; cursor:pointer; transition: background .15s ease; }
.btn-save:hover{ background:var(--evergreen-deep); }

.setup-note{ background:var(--amber-soft); border-left:3px solid var(--amber); border-radius:var(--radius-sm); padding:12px 16px; font-size:12.5px; color:#7a5010; margin-bottom:18px; }
.setup-note code{ background:rgba(255,255,255,.6); padding:1px 6px; border-radius:5px; }

@media (max-width: 768px){
    .sidebar{ left:-260px; }
    .sidebar.mobile-open{ left:0; box-shadow: 20px 0 40px rgba(0,0,0,0.15); }
    .main-content, .main-content.expanded{ margin-left:0; }
    .page-wrap{ padding:20px; }
    .topbar{ padding:0 16px; }
}
</style>
</head>

<body>

<div class="sidebar" id="sidebar">
    <div class="brand"><div class="brand-mark">Z</div><div class="brand-name">Zentury</div></div>
    <div class="nav-section-label">Overview</div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><i class="fa-solid fa-chart-line"></i><span class="nav-label">Dashboard</span></a></li>
        <li><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i><span class="nav-label">Analytics</span></a></li>
    </ul>
    <div class="nav-section-label">Catalog &amp; Sales</div>
    <ul class="sidebar-menu">
        <li><a href="add_products.php" class="active"><i class="fa-solid fa-plus"></i><span class="nav-label">Add Product</span></a></li>
        <li><a href="products.php"><i class="fa-solid fa-box"></i><span class="nav-label">Total Products</span></a></li>
        <li><a href="categories.php"><i class="fa-solid fa-layer-group"></i><span class="nav-label">Categories</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-cart-shopping"></i><span class="nav-label">Orders</span></a></li>
        <li><a href="customers.php"><i class="fa-solid fa-users"></i><span class="nav-label">Customers</span></a></li>
    </ul>
    <div class="nav-section-label">System</div>
    <ul class="sidebar-menu">
        <li><a href="settings.php" class="active"><i class="fa-solid fa-gear"></i><span class="nav-label">Settings</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span class="nav-label">Log out</span></a></li>
    </ul>
    <div class="sidebar-foot"><i class="fa-solid fa-circle-info"></i><span>Store synced<br>just now</span></div>
</div>

<div class="main-content" id="mainContent">

    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
            <div class="breadcrumb-trail">Zenturya / <strong>Settings</strong></div>
        </div>
        <div class="admin-profile">
            <div class="profile-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
            <div class="profile-text"><strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong><span>Administrator</span></div>
        </div>
    </div>

    <div class="page-wrap">

        <div class="page-head">
            <h1 class="page-title">Settings</h1>
            <p class="page-sub">Manage your store details and account.</p>
        </div>

        <?php if (!$has_settings_table): ?>
            <div class="setup-note">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Store Information isn't saved yet because the <code>site_settings</code> table doesn't exist.
                Run <code>site_settings_migration.sql</code> in phpMyAdmin, then refresh this page.
            </div>
        <?php endif; ?>

        <!-- ========== STORE INFORMATION ========== -->
        <div class="panel">
            <div class="panel-title"><i class="fa-solid fa-store"></i> Store information</div>
            <div class="panel-sub">Shown on your Contact page and in the site footer.</div>

            <?php panel_message($messages, 'store'); ?>

            <form method="POST" action="settings.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="field-label">Store name</label>
                        <input type="text" name="store_name" class="form-control" value="<?= htmlspecialchars($settings['store_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Phone number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Support email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Street address</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($settings['address']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($settings['city']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">State</label>
                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($settings['state']) ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="update_store" class="btn-save"><i class="fa-solid fa-check"></i> Save store info</button>
                </div>
            </form>
        </div>

        <!-- ========== MY ACCOUNT ========== -->
        <div class="panel">
            <div class="panel-title"><i class="fa-solid fa-user"></i> My account</div>
            <div class="panel-sub">Your admin login name and email.</div>

            <?php panel_message($messages, 'account'); ?>

            <form method="POST" action="settings.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="field-label">Full name</label>
                        <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Email</label>
                        <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="update_account" class="btn-save"><i class="fa-solid fa-check"></i> Save account</button>
                </div>
            </form>
        </div>

        <!-- ========== CHANGE PASSWORD ========== -->
        <div class="panel">
            <div class="panel-title"><i class="fa-solid fa-lock"></i> Change password</div>
            <div class="panel-sub">Use a strong password you don't use anywhere else.</div>

            <?php panel_message($messages, 'password'); ?>

            <form method="POST" action="settings.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="field-label">Current password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">New password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Confirm new password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="update_password" class="btn-save"><i class="fa-solid fa-key"></i> Change password</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }
});
</script>

</body>
</html>