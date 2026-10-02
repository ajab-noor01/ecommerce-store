<?php
// ==========================================================
// Nexora Admin — Orders (LIVE DATA)
// Schema: orders(id, user_id, total_amount, status, created_at)
//         users(id, name, email, role, ...)
// Note: no order_items table, so "Product" column isn't shown
// (there's no real data for it).
// ==========================================================
require_once '../config/auth_check.php';
include '../config/db.php';
 

function initials(string $name): string {
    $name = trim($name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

// ---------------------------------------------------------
// Quick status update (dropdown per row, no page reload needed
// for the query itself — just a simple POST + redirect)
// ---------------------------------------------------------
if (isset($_POST['update_status'])) {
    $oid = intval($_POST['order_id']);
    $new_status = $_POST['new_status'] ?? '';
    if (in_array($new_status, ['pending', 'processing', 'completed', 'cancelled'], true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $oid);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: orders.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// ---------------------------------------------------------
// Search / filter
// ---------------------------------------------------------
$search        = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$date_filter   = $_GET['date'] ?? '';

$where  = "1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND (o.id = ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = is_numeric($search) ? (int) $search : 0;
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "iss";
}
if (in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'], true)) {
    $where   .= " AND o.status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}
if ($date_filter === 'today') {
    $where .= " AND DATE(o.created_at) = CURDATE()";
} elseif ($date_filter === 'week') {
    $where .= " AND YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter === 'month') {
    $where .= " AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())";
}

$sql = "
    SELECT o.id, o.total_amount, o.status, o.created_at, u.name AS customer_name, u.email AS customer_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE $where
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------------------------------------------------
// Summary stats (real, all-time)
// ---------------------------------------------------------
$total_all     = (int) ($conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'] ?? 0);
$total_pending = (int) ($conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'] ?? 0);
$total_done    = (int) ($conn->query("SELECT COUNT(*) c FROM orders WHERE status='completed'")->fetch_assoc()['c'] ?? 0);
$total_cancel  = (int) ($conn->query("SELECT COUNT(*) c FROM orders WHERE status='cancelled'")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Orders</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

.page-wrap{ padding:30px 28px 50px; }
.page-header{ display:flex; align-items:flex-end; justify-content:space-between; padding-bottom:20px; margin-bottom:22px; border-bottom:1px solid var(--line); flex-wrap:wrap; gap:14px; }
h1.page-title{ font-size:25px; font-weight:500; margin:0 0 6px; color:var(--evergreen-900); }
.page-sub{ margin:0; color:var(--muted); font-size:13.5px; }

.stat-card{ background:var(--surface); border:1px solid var(--line); border-left:3px solid var(--evergreen); border-radius:var(--radius-md); box-shadow:var(--shadow-card); padding:20px 22px; height:100%; }
.stat-card.accent-amber{ border-left-color:var(--amber); }
.stat-card.accent-rose{ border-left-color:var(--rose); }
.stat-top{ display:flex; align-items:center; justify-content:space-between; }
.stat-label{ font-size:12.5px; color:var(--muted); font-weight:500; }
.stat-icon{ width:38px; height:38px; border-radius:10px; background:var(--paper); color:var(--evergreen); display:flex; align-items:center; justify-content:center; font-size:14px; }
.stat-card h3{ font-size:26px; font-weight:600; margin:14px 0 0; font-family:'Fraunces', serif; }
.stat-card p{ color:var(--muted); margin:2px 0 0; font-size:12px; }

.toolbar{ background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); padding:16px 18px; margin-top:26px; margin-bottom:20px; }
.search-box{ position:relative; }
.search-box i{ position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:13px; }
.search-box input{ padding-left:38px; }
.form-control, .form-select{ height:44px; border:1.5px solid var(--line); border-radius:10px; font-size:13.5px; color:var(--ink); }
.form-control:focus, .form-select:focus{ border-color:var(--evergreen); box-shadow:0 0 0 3px rgba(31,111,84,.1); }
.btn-filter{ height:44px; width:100%; border-radius:10px; border:1.5px solid var(--line); background:var(--surface); color:var(--ink-soft); font-size:13px; font-weight:600; }
.btn-filter:hover{ background:var(--paper); }

.orders-box{ background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); overflow:hidden; }
.orders-box-head{ padding:20px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
.orders-box-head h5{ margin:0; font-size:16px; font-weight:600; }
.order-count{ color:var(--muted); font-size:12.5px; }

.table{ margin:0; }
.table th{ background:var(--paper); color:var(--muted); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:14px 15px; border-bottom:1px solid var(--line); }
.table td{ padding:14px 15px; vertical-align:middle; font-size:13.5px; border-bottom:1px solid var(--line); color:var(--ink-soft); }
.table tbody tr:last-child td{ border-bottom:none; }
.table tbody tr:hover{ background:var(--paper); }

.order-id{ font-weight:600; color:var(--ink); font-family:'Fraunces', serif; }
.customer{ display:flex; align-items:center; gap:10px; }
.customer-avatar{ width:34px; height:34px; border-radius:50%; background:var(--green-soft); color:var(--evergreen); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:11.5px; flex-shrink:0; }
.customer-name{ font-weight:600; color:var(--ink); font-size:13.5px; }
.customer-email{ font-size:11px; color:var(--muted); }

.status-form select{
    border:1.5px solid var(--line); border-radius:20px; font-size:11px; font-weight:700; padding:5px 26px 5px 11px;
    cursor:pointer; appearance:none; -webkit-appearance:none;
    background-repeat:no-repeat; background-position:right 9px center; background-size:9px;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 6'%3E%3Cpath fill='%237C8781' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
}
.status-form.st-completed select{ background-color:var(--green-soft); color:var(--evergreen); border-color:var(--green-soft); }
.status-form.st-pending select{ background-color:var(--amber-soft); color:var(--amber); border-color:var(--amber-soft); }
.status-form.st-processing select{ background-color:#E7EEFB; color:#2952A3; border-color:#E7EEFB; }
.status-form.st-cancelled select{ background-color:var(--rose-soft); color:var(--rose); border-color:var(--rose-soft); }

.view-btn{ width:32px; height:32px; border:none; border-radius:8px; background:var(--green-soft); color:var(--evergreen); display:inline-flex; align-items:center; justify-content:center; }
.view-btn:hover{ opacity:.8; }

.empty-state{ text-align:center; padding:60px 20px; color:var(--muted); }
.empty-state i{ font-size:34px; margin-bottom:12px; color:var(--line); display:block; }

@media (max-width: 991px){ .sidebar{ width:220px; } .main-content{ margin-left:220px; } }
@media (max-width: 768px){
    .sidebar{ left:-260px; }
    .sidebar.mobile-open{ left:0; box-shadow: 20px 0 40px rgba(0,0,0,0.15); }
    .main-content, .main-content.expanded{ margin-left:0; }
    .page-wrap{ padding:20px; }
    .topbar{ padding:0 16px; }
    .page-header{ align-items:flex-start; }
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
         <li><a href="add_products.php"><i class="fa-solid fa-plus"></i><span class="nav-label">Add Product</span></a></li>
        <li><a href="products.php"><i class="fa-solid fa-box"></i><span class="nav-label">Total Products</span></a></li>
        <li><a href="categories.php"><i class="fa-solid fa-layer-group"></i><span class="nav-label">Categories</span></a></li>
        <li><a href="orders.php" class="active"><i class="fa-solid fa-cart-shopping"></i><span class="nav-label">Orders</span></a></li>
        <li><a href="customers.php"><i class="fa-solid fa-users"></i><span class="nav-label">Customers</span></a></li>
    </ul>
    <div class="nav-section-label">System</div>
    <ul class="sidebar-menu">
        <li><a href="settings.php"><i class="fa-solid fa-gear"></i><span class="nav-label">Settings</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span class="nav-label">Log out</span></a></li>
    </ul>
    <div class="sidebar-foot"><i class="fa-solid fa-circle-info"></i><span>Store synced<br>just now</span></div>
</div>

<div class="main-content" id="mainContent">

    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
            <div class="breadcrumb-trail">Zentury / <strong>Orders</strong></div>
        </div>
        <div class="admin-profile">
            <div class="profile-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
            <div class="profile-text"><strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong><span>Administrator</span></div>
        </div>
    </div>

    <div class="page-wrap">

        <div class="page-header">
            <div>
                <h1 class="page-title">Orders</h1>
                <p class="page-sub">Manage and track customer orders.</p>
            </div>
        </div>

        <!-- STATS (real) -->
        <div class="row g-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-ever">
                    <div class="stat-top"><span class="stat-label">Total Orders</span><div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div></div>
                    <h3><?= number_format($total_all) ?></h3><p>All orders</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-amber">
                    <div class="stat-top"><span class="stat-label">Pending</span><div class="stat-icon"><i class="fa-solid fa-clock"></i></div></div>
                    <h3><?= number_format($total_pending) ?></h3><p>Waiting for processing</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-ever">
                    <div class="stat-top"><span class="stat-label">Completed</span><div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div></div>
                    <h3><?= number_format($total_done) ?></h3><p>Successfully delivered</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-rose">
                    <div class="stat-top"><span class="stat-label">Cancelled</span><div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div></div>
                    <h3><?= number_format($total_cancel) ?></h3><p>Cancelled orders</p>
                </div>
            </div>
        </div>

        <!-- FILTER (real) -->
        <form class="toolbar" method="GET" action="orders.php">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search order ID or customer..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-8">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="" <?= $status_filter === '' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-lg-3 col-4">
                    <select class="form-select" name="date" onchange="this.form.submit()">
                        <option value="" <?= $date_filter === '' ? 'selected' : '' ?>>All Dates</option>
                        <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>This Month</option>
                    </select>
                </div>
            </div>
        </form>

        <!-- ORDERS TABLE -->
        <div class="orders-box">
            <div class="orders-box-head">
                <h5>Orders</h5>
                <span class="order-count"><?= count($orders) ?> of <?= $total_all ?></span>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <?= $total_all === 0 ? 'No orders yet.' : 'No orders match your search/filter.' ?>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="order-id">#NX<?= str_pad((string) $o['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="customer">
                                    <div class="customer-avatar"><?= htmlspecialchars(initials($o['customer_name'] ?? 'Guest')) ?></div>
                                    <div>
                                        <div class="customer-name"><?= htmlspecialchars($o['customer_name'] ?: 'Guest') ?></div>
                                        <div class="customer-email"><?= htmlspecialchars($o['customer_email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            <td><strong>$<?= number_format((float) $o['total_amount'], 2) ?></strong></td>
                            <td>
                                <form method="POST" action="orders.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>" class="status-form st-<?= htmlspecialchars($o['status']) ?>">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $o['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td>
                                <a href="order-detail.php?id=<?= $o['id'] ?>" class="view-btn" title="View"><i class="fa-solid fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
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