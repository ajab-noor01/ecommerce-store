<?php
// ==========================================================
// Zentury Admin — Dashboard (LIVE DATA)
// Schema used:
//   orders(id, user_id, total_amount, status, created_at)
//   users(id, name, email, password, role, created_at)
//   products(id, name, price, old_price, discount_badge, description,
//             image_1..4, is_new_arrival, is_popular, rating_stars, created_at)
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/auth_check.php';
include '../config/db.php';

/** @var mysqli $conn */
if (!isset($conn) || $conn === null) {
    die("<div style='padding:20px;font-family:sans-serif;color:#B3432E'>⚠️ Database connection (\$conn) missing. Check config/db.php.</div>");
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// ---------------------------------------------------------
// Helpers
// ---------------------------------------------------------
function scalar_query(mysqli $conn, string $sql) {
    $result = $conn->query($sql);
    if ($result && ($row = $result->fetch_row())) {
        return $row[0];
    }
    return 0;
}

// Percentage change between two numeric periods
function pct_change($current, $previous): array {
    $current  = (float) $current;
    $previous = (float) $previous;
    if ($previous == 0.0) {
        return ['value' => $current > 0 ? 100.0 : 0.0, 'trend' => 'up'];
    }
    $change = (($current - $previous) / $previous) * 100;
    return ['value' => round(abs($change), 1), 'trend' => $change >= 0 ? 'up' : 'down'];
}

function initials(string $name): string {
    $name = trim($name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

// ---------------------------------------------------------
// Totals (all-time)
// ---------------------------------------------------------
$total_revenue   = (float) scalar_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'");
$total_orders    = (int)   scalar_query($conn, "SELECT COUNT(*) FROM orders");
$total_customers = (int)   scalar_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'");
$total_products  = (int)   scalar_query($conn, "SELECT COUNT(*) FROM products");

// ---------------------------------------------------------
// This month vs last month comparisons
// ---------------------------------------------------------
$rev_this_month = (float) scalar_query($conn, "
    SELECT COALESCE(SUM(total_amount),0) FROM orders
    WHERE status != 'cancelled'
      AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
$rev_last_month = (float) scalar_query($conn, "
    SELECT COALESCE(SUM(total_amount),0) FROM orders
    WHERE status != 'cancelled'
      AND YEAR(created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)
      AND MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)");
$revenue_change = pct_change($rev_this_month, $rev_last_month);

$orders_this_month = (int) scalar_query($conn, "
    SELECT COUNT(*) FROM orders
    WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
$orders_last_month = (int) scalar_query($conn, "
    SELECT COUNT(*) FROM orders
    WHERE YEAR(created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)
      AND MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)");
$orders_change = pct_change($orders_this_month, $orders_last_month);

$cust_this_month = (int) scalar_query($conn, "
    SELECT COUNT(*) FROM users
    WHERE role = 'customer'
      AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
$cust_last_month = (int) scalar_query($conn, "
    SELECT COUNT(*) FROM users
    WHERE role = 'customer'
      AND YEAR(created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)
      AND MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)");
$customers_change = pct_change($cust_this_month, $cust_last_month);

$products_this_week = (int) scalar_query($conn, "
    SELECT COUNT(*) FROM products WHERE created_at >= (CURDATE() - INTERVAL 7 DAY)");

// ---------------------------------------------------------
// Stat cards (real data)
// ---------------------------------------------------------
$stats = [
    [
        'label'  => 'Revenue',
        'value'  => '$' . number_format($total_revenue, 2),
        'change' => ($revenue_change['trend'] === 'up' ? '+' : '-') . $revenue_change['value'] . '%',
        'trend'  => $revenue_change['trend'],
        'note'   => 'vs last month',
        'icon'   => 'fa-sack-dollar',
    ],
    [
        'label'  => 'Orders',
        'value'  => number_format($total_orders),
        'change' => ($orders_change['trend'] === 'up' ? '+' : '-') . $orders_change['value'] . '%',
        'trend'  => $orders_change['trend'],
        'note'   => 'vs last month',
        'icon'   => 'fa-bag-shopping',
    ],
    [
        'label'  => 'Customers',
        'value'  => number_format($total_customers),
        'change' => ($customers_change['trend'] === 'up' ? '+' : '-') . $customers_change['value'] . '%',
        'trend'  => $customers_change['trend'],
        'note'   => 'vs last month',
        'icon'   => 'fa-users',
    ],
    [
        'label'  => 'Products',
        'value'  => number_format($total_products),
        'change' => '+' . $products_this_week,
        'trend'  => 'up',
        'note'   => 'new this week',
        'icon'   => 'fa-box-open',
    ],
];

// ---------------------------------------------------------
// Recent orders (join users for customer name)
// ---------------------------------------------------------
$orders = [];
$res = $conn->query("
    SELECT o.id, o.total_amount, o.status, o.created_at, u.name AS customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 6
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $orders[] = [
            'id'       => '#NX' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT),
            'customer' => $row['customer_name'] ?: 'Guest',
            'amount'   => '$' . number_format((float) $row['total_amount'], 2),
            'status'   => strtolower($row['status'] ?: 'pending'),
            'date'     => date('M j, g:i A', strtotime($row['created_at'])),
        ];
    }
}

// ---------------------------------------------------------
// Revenue trend — last 7 days
// ---------------------------------------------------------
$chart_labels = [];
$chart_values = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $chart_labels[] = date('D', strtotime($day));
    $val = (float) scalar_query($conn, "
        SELECT COALESCE(SUM(total_amount),0) FROM orders
        WHERE status != 'cancelled' AND DATE(created_at) = '$day'");
    $chart_values[] = round($val, 2);
}

// ---------------------------------------------------------
// Order status breakdown (whatever statuses actually exist)
// ---------------------------------------------------------
$status_labels = [];
$status_values = [];
$status_colors_map = [
    'completed' => '#1F6F54',
    'pending'   => '#C1791F',
    'cancelled' => '#B3432E',
];
$res = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $status_labels[] = ucfirst($row['status']);
        $status_values[] = (int) $row['cnt'];
    }
}
$status_colors = array_map(
    fn($label) => $status_colors_map[strtolower($label)] ?? '#7C8781',
    $status_labels
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap" rel="stylesheet">

<style>
:root{
    --ink:        #1B1F1C;
    --ink-soft:   #4B564F;
    --muted:      #7C8781;
    --line:       #E4E7E1;
    --paper:      #F4F6F2;
    --surface:    #FFFFFF;

    --evergreen:      #1F6F54;
    --evergreen-deep: #123A2C;
    --evergreen-900:  #0E241C;
    --amber:      #C1791F;
    --amber-soft: #FBEEDD;
    --rose:       #B3432E;
    --rose-soft:  #FBEAE6;
    --green-soft: #E6F2EB;

    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 18px;
    --shadow-card: 0 1px 2px rgba(18,58,44,0.04), 0 8px 24px rgba(18,58,44,0.05);
}
*{ box-sizing: border-box; }
body{
    margin:0;
    font-family:'Inter', -apple-system, Segoe UI, sans-serif;
    background: var(--paper);
    color: var(--ink);
    font-size: 14.5px;
    -webkit-font-smoothing: antialiased;
}
.num, .brand-mark, .welcome h1{ font-family: 'Fraunces', Georgia, serif; }
a{ text-decoration:none; color:inherit; }
button{ font-family:inherit; }

/* SIDEBAR */
.sidebar{
    position:fixed; left:0; top:0; width:252px; height:100vh;
    background: linear-gradient(180deg, var(--evergreen-deep) 0%, var(--evergreen-900) 100%);
    color:#EAF1EC; padding:26px 18px; z-index:1000;
    display:flex; flex-direction:column;
    transition: width .25s ease, padding .25s ease;
}
.sidebar.collapsed{ width:82px; padding:26px 12px; }
.sidebar.collapsed .brand-name, .sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-section-label, .sidebar.collapsed .sidebar-foot span{ display:none; }
.sidebar.collapsed .sidebar-menu a{ justify-content:center; }
.sidebar.collapsed .brand{ justify-content:center; }
.brand{ display:flex; align-items:center; gap:10px; padding:4px 8px 26px 8px; }
.brand-mark{
    width:34px; height:34px; border-radius:9px; background: var(--amber); color: var(--evergreen-900);
    display:flex; align-items:center; justify-content:center; font-weight:600; font-size:16px; flex-shrink:0;
}
.brand-name{ font-size:18px; font-weight:600; letter-spacing:.2px; color:#F6F1E7; white-space:nowrap; }
.nav-section-label{ font-size:11px; color:#8FAE9E; text-transform:uppercase; letter-spacing:1px; margin:18px 12px 8px; }
.sidebar-menu{ list-style:none; margin:0; padding:0; }
.sidebar-menu li{ margin-bottom:2px; }
.sidebar-menu a{
    position:relative; display:flex; align-items:center; gap:13px; padding:10px 12px;
    border-radius: var(--radius-sm); color:#C6D8CD; font-size:14px; font-weight:500;
    transition: background .18s ease, color .18s ease;
}
.sidebar-menu a i{ width:18px; text-align:center; font-size:14px; }
.sidebar-menu a:hover{ background: rgba(255,255,255,0.06); color:#F6F1E7; }
.sidebar-menu a.active{ background: rgba(193,121,31,0.16); color:#F6F1E7; }
.sidebar-menu a.active::before{
    content:""; position:absolute; left:-18px; top:8px; bottom:8px; width:3px; border-radius:3px; background: var(--amber);
}
.sidebar.collapsed .sidebar-menu a.active::before{ left:-12px; }
.sidebar-foot{
    margin-top:auto; display:flex; align-items:center; gap:10px; padding:12px;
    border-radius: var(--radius-sm); background: rgba(255,255,255,0.05);
}
.sidebar-foot i{ color:#8FAE9E; }
.sidebar-foot span{ font-size:12.5px; color:#B7CBBE; line-height:1.3; }

/* MAIN */
.main-content{ margin-left:252px; min-height:100vh; transition: margin-left .25s ease; }
.main-content.expanded{ margin-left:82px; }
.topbar{
    height:72px; background: var(--surface); border-bottom:1px solid var(--line);
    display:flex; align-items:center; justify-content:space-between; padding:0 28px;
    position:sticky; top:0; z-index:900;
}
.topbar-left{ display:flex; align-items:center; gap:16px; }
.sidebar-toggle{
    width:36px; height:36px; border-radius: var(--radius-sm); border:1px solid var(--line);
    background: var(--surface); display:flex; align-items:center; justify-content:center;
    color: var(--ink-soft); cursor:pointer;
}
.sidebar-toggle:hover{ background: var(--paper); }
.breadcrumb-trail{ font-size:12.5px; color: var(--muted); }
.breadcrumb-trail strong{ color: var(--ink); font-weight:600; }
.topbar-search{
    display:flex; align-items:center; gap:9px; background: var(--paper); border:1px solid var(--line);
    border-radius:10px; padding:8px 14px; width:280px; color: var(--muted);
}
.topbar-search input{ border:none; background:transparent; outline:none; font-size:13.5px; width:100%; color:var(--ink); }
.topbar-right{ display:flex; align-items:center; gap:18px; }
.icon-btn{
    position:relative; width:38px; height:38px; border-radius:10px; border:1px solid var(--line);
    background: var(--surface); display:flex; align-items:center; justify-content:center;
    color: var(--ink-soft); cursor:pointer;
}
.icon-btn:hover{ background: var(--paper); }
.icon-btn .dot{
    position:absolute; top:7px; right:7px; width:7px; height:7px; border-radius:50%;
    background: var(--rose); border:1.5px solid var(--surface);
}
.admin-profile{ display:flex; align-items:center; gap:11px; cursor:pointer; }
.profile-avatar{
    width:38px; height:38px; border-radius:50%; background: var(--green-soft); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center; font-weight:600; font-size:13px;
}
.profile-text strong{ display:block; font-size:13.5px; font-weight:600; }
.profile-text span{ font-size:11.5px; color: var(--muted); }

/* CONTENT */
.dashboard-content{ padding:30px 28px 40px; }
.welcome{
    display:flex; align-items:flex-end; justify-content:space-between; padding-bottom:22px;
    margin-bottom:24px; border-bottom:1px solid var(--line); flex-wrap:wrap; gap:16px;
}
.welcome h1{ font-size:26px; font-weight:500; margin:0 0 6px; color: var(--evergreen-900); }
.welcome p{ margin:0; color: var(--muted); font-size:13.5px; }
.btn-quick{
    display:inline-flex; align-items:center; gap:8px; background: var(--evergreen); color:#fff;
    padding:11px 18px; border-radius:10px; font-size:13.5px; font-weight:600; border:none;
    cursor:pointer; transition: background .15s ease;
}
.btn-quick:hover{ background: var(--evergreen-deep); color:#fff; }

.btn-visit-store{
    display:inline-flex; align-items:center; gap:8px;
    border:1px solid var(--line); background: var(--surface); color: var(--ink-soft);
    padding:9px 15px; border-radius:10px; font-size:13px; font-weight:600;
}
.btn-visit-store:hover{ background: var(--paper); color: var(--evergreen-deep); }

.stat-card{
    background: var(--surface); border:1px solid var(--line); border-left:3px solid var(--evergreen);
    border-radius: var(--radius-md); box-shadow: var(--shadow-card); padding:20px 22px; height:100%;
}
.stat-card.accent-amber{ border-left-color: var(--amber); }
.stat-card.accent-rose{ border-left-color: var(--rose); }
.stat-card.accent-ever{ border-left-color: var(--evergreen); }
.stat-top{ display:flex; align-items:center; justify-content:space-between; }
.stat-label{ font-size:12.5px; color: var(--muted); font-weight:500; }
.stat-icon{
    width:38px; height:38px; border-radius:10px; background: var(--paper); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center; font-size:14px;
}
.stat-card .num{ font-size:29px; font-weight:500; margin:14px 0 6px; color: var(--ink); }
.stat-change{ display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color: var(--evergreen); }
.stat-change.down{ color: var(--rose); }
.stat-change .note{ font-weight:400; color: var(--muted); margin-left:2px; }

.panel{
    background: var(--surface); border:1px solid var(--line); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card); padding:22px;
}
.panel-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
.panel-title{ font-size:16px; font-weight:600; color:var(--ink); }
.panel-sub{ font-size:12px; color:var(--muted); margin-top:2px; }
.link-quiet{ font-size:12.5px; font-weight:600; color: var(--evergreen); }
.link-quiet:hover{ text-decoration:underline; }

.table{ margin:0; }
.table thead th{
    color: var(--muted); font-size:11.5px; font-weight:600; text-transform:uppercase;
    letter-spacing:.4px; border-bottom:1px solid var(--line); padding-bottom:12px;
}
.table tbody td{ font-size:13.5px; vertical-align:middle; padding:14px 8px; border-bottom:1px solid var(--line); color: var(--ink-soft); }
.table tbody tr:last-child td{ border-bottom:none; }
.cust-cell{ display:flex; align-items:center; gap:10px; }
.cust-avatar{
    width:30px; height:30px; border-radius:50%; background: var(--green-soft); color: var(--evergreen);
    font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.cust-name{ font-weight:600; color: var(--ink); font-size:13.5px; }
.cust-date{ font-size:11.5px; color: var(--muted); }
.order-id{ font-weight:600; color: var(--ink); font-family:'Fraunces', serif; }
.status{ display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:20px; font-size:11px; font-weight:600; }
.status::before{ content:""; width:6px; height:6px; border-radius:50%; }
.status.completed{ background: var(--green-soft); color: var(--evergreen); }
.status.completed::before{ background: var(--evergreen); }
.status.pending{ background: var(--amber-soft); color: var(--amber); }
.status.pending::before{ background: var(--amber); }
.status.cancelled{ background: var(--rose-soft); color: var(--rose); }
.status.cancelled::before{ background: var(--rose); }
.empty-note{ text-align:center; color: var(--muted); font-size:13px; padding:24px 0; }

@media (max-width: 991px){
    .sidebar{ width:220px; } .main-content{ margin-left:220px; } .topbar-search{ width:200px; }
}
@media (max-width: 768px){
    .sidebar{ left:-260px; }
    .sidebar.mobile-open{ left:0; box-shadow: 20px 0 40px rgba(0,0,0,0.15); }
    .main-content, .main-content.expanded{ margin-left:0; }
    .topbar-search{ display:none; }
    .dashboard-content{ padding:20px; }
    .topbar{ padding:0 16px; }
    .welcome{ align-items:flex-start; }
}
</style>
</head>

<body>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark">Z</div>
        <div class="brand-name">Zentury</div>
    </div>

    <div class="nav-section-label">Overview</div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="active"><i class="fa-solid fa-chart-line"></i><span class="nav-label">Dashboard</span></a></li>
        <li><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i><span class="nav-label">Analytics</span></a></li>
    </ul>

    <div class="nav-section-label">Catalog &amp; Sales</div>
    <ul class="sidebar-menu">
         <li><a href="add_products.php"><i class="fa-solid fa-plus"></i><span class="nav-label">Add Product</span></a></li>
        <li><a href="products.php"><i class="fa-solid fa-box"></i><span class="nav-label">Total Products</span></a></li>
        <li><a href="categories.php"><i class="fa-solid fa-layer-group"></i><span class="nav-label">Categories</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-cart-shopping"></i><span class="nav-label">Orders</span></a></li>
        <li><a href="customers.php"><i class="fa-solid fa-users"></i><span class="nav-label">Customers</span></a></li>
    </ul>

    <div class="nav-section-label">System</div>
    <ul class="sidebar-menu">
        <li><a href="settings.php"><i class="fa-solid fa-gear"></i><span class="nav-label">Settings</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span class="nav-label">Log out</span></a></li>
    </ul>

    <div class="sidebar-foot">
        <i class="fa-solid fa-circle-info"></i>
        <span>Store synced<br>just now</span>
    </div>
</div>

<div class="main-content" id="mainContent">

    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
            <div class="breadcrumb-trail">Zentury / <strong>Dashboard</strong></div>
        </div>
        <div class="topbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search orders, products, customers…">
        </div>
        <div class="topbar-right">
            <a href="../index.php" target="_blank" class="btn-visit-store"><i class="fa-solid fa-arrow-up-right-from-square"></i> Visit Store</a>
            <button class="icon-btn" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="dot"></span></button>
            <div class="admin-profile">
                <div class="profile-avatar"><?= htmlspecialchars(initials($admin_name)) ?></div>
                <div class="profile-text">
                    <strong><?= htmlspecialchars($admin_name) ?></strong>
                    <span>Administrator</span>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted)"></i>
            </div>
        </div>
    </div>

    <div class="dashboard-content">

        <div class="welcome">
            <div>
                <h1>Welcome back, <?= htmlspecialchars(explode(' ', $admin_name)[0]) ?></h1>
                <p><?= date('l, F j') ?> — here's how the store is performing today.</p>
            </div>
        </div>

        <!-- STAT CARDS (live data) -->
        <div class="row g-4">
            <?php
            $accents = ['accent-ever', 'accent-amber', 'accent-ever', 'accent-rose'];
            foreach ($stats as $i => $s):
            ?>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card <?= $accents[$i % count($accents)] ?>">
                    <div class="stat-top">
                        <span class="stat-label"><?= htmlspecialchars($s['label']) ?></span>
                        <div class="stat-icon"><i class="fa-solid <?= htmlspecialchars($s['icon']) ?>"></i></div>
                    </div>
                    <div class="num"><?= htmlspecialchars($s['value']) ?></div>
                    <div class="stat-change <?= $s['trend'] === 'down' ? 'down' : '' ?>">
                        <i class="fa-solid fa-arrow-<?= $s['trend'] === 'down' ? 'down' : 'up' ?>"></i>
                        <?= htmlspecialchars($s['change']) ?>
                        <span class="note"><?= htmlspecialchars($s['note']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CHARTS (live data) -->
        <div class="row g-4" style="margin-top:2px;">
            <div class="col-xl-7">
                <div class="panel" style="margin-top:24px;">
                    <div class="panel-head">
                        <div>
                            <div class="panel-title">Revenue trend</div>
                            <div class="panel-sub">Gross sales, last 7 days</div>
                        </div>
                    </div>
                    <canvas id="revenueChart" height="210"></canvas>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="panel" style="margin-top:24px;">
                    <div class="panel-head">
                        <div>
                            <div class="panel-title">Order status</div>
                            <div class="panel-sub">All-time breakdown</div>
                        </div>
                    </div>
                    <?php if (empty($status_labels)): ?>
                        <div class="empty-note">No orders yet.</div>
                    <?php else: ?>
                        <canvas id="statusChart" height="210"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RECENT ORDERS (live data) -->
        <div class="panel" style="margin-top:24px;">
            <div class="panel-head">
                <div>
                    <div class="panel-title">Recent orders</div>
                    <div class="panel-sub">Latest activity across the store</div>
                </div>
                <a href="orders.php" class="link-quiet">View all orders →</a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-note">No orders yet.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="order-id"><?= htmlspecialchars($o['id']) ?></td>
                            <td>
                                <div class="cust-cell">
                                    <div class="cust-avatar"><?= htmlspecialchars(initials($o['customer'])) ?></div>
                                    <div>
                                        <div class="cust-name"><?= htmlspecialchars($o['customer']) ?></div>
                                        <div class="cust-date"><?= htmlspecialchars($o['date']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($o['amount']) ?></td>
                            <td><span class="status <?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars(ucfirst($o['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }
});

// ---- Revenue chart (from PHP: last 7 days of real orders) ----
const revCtx = document.getElementById('revenueChart');
new Chart(revCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode($chart_values) ?>,
            borderColor: '#1F6F54',
            backgroundColor: 'rgba(31,111,84,0.08)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: '#1F6F54',
            borderWidth: 2.5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#EEF1EC' }, ticks: { color: '#7C8781', font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { color: '#7C8781', font: { size: 11 } } }
        }
    }
});

<?php if (!empty($status_labels)): ?>
// ---- Order status donut (from PHP: real GROUP BY status) ----
const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($status_labels) ?>,
        datasets: [{
            data: <?= json_encode($status_values) ?>,
            backgroundColor: <?= json_encode($status_colors) ?>,
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: {
            legend: { position: 'bottom', labels: { color: '#4B564F', font: { size: 11.5 }, padding: 16, usePointStyle: true } }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>