<?php
// ==========================================================
// Nexora Admin — Analytics (LIVE DATA)
// Note: orders table has no line-items (no order_items table),
// so "sales by category" can't be computed from real order data.
// This page shows "Products by Category" (catalog composition)
// instead of inventing fake sales-share numbers.
// ==========================================================
require_once '../config/auth_check.php';
include '../config/db.php';
 global $conn;

function scalar_query(mysqli $conn, string $sql) {
    $result = $conn->query($sql);
    if ($result && ($row = $result->fetch_row())) return $row[0];
    return 0;
}

function pct_change($current, $previous): array {
    $current = (float) $current; $previous = (float) $previous;
    if ($previous == 0.0) return ['value' => $current > 0 ? 100.0 : 0.0, 'trend' => 'up'];
    $change = (($current - $previous) / $previous) * 100;
    return ['value' => round(abs($change), 1), 'trend' => $change >= 0 ? 'up' : 'down'];
}

// ---------------------------------------------------------
// Date range selector
// ---------------------------------------------------------
$range = $_GET['range'] ?? '30';
$range_labels = ['7' => 'Last 7 Days', '30' => 'Last 30 Days', '90' => 'Last 3 Months', 'year' => 'This Year'];
if (!isset($range_labels[$range])) $range = '30';

if ($range === 'year') {
    $start_date = date('Y-01-01');
    $days_in_range = (int) ((strtotime('today') - strtotime($start_date)) / 86400) + 1;
} else {
    $days_in_range = (int) $range;
    $start_date = date('Y-m-d', strtotime("-" . ($days_in_range - 1) . " days"));
}
$prev_start_date = date('Y-m-d', strtotime($start_date . " -$days_in_range days"));
$prev_end_date   = date('Y-m-d', strtotime($start_date . " -1 days"));

// ---------------------------------------------------------
// Stat cards (current range vs previous equal-length range)
// ---------------------------------------------------------
$revenue_now  = (float) scalar_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled' AND DATE(created_at) >= '$start_date'");
$revenue_prev = (float) scalar_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled' AND DATE(created_at) BETWEEN '$prev_start_date' AND '$prev_end_date'");
$revenue_chg  = pct_change($revenue_now, $revenue_prev);

$orders_now  = (int) scalar_query($conn, "SELECT COUNT(*) FROM orders WHERE DATE(created_at) >= '$start_date'");
$orders_prev = (int) scalar_query($conn, "SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN '$prev_start_date' AND '$prev_end_date'");
$orders_chg  = pct_change($orders_now, $orders_prev);

$customers_now  = (int) scalar_query($conn, "SELECT COUNT(*) FROM users WHERE role='customer' AND DATE(created_at) >= '$start_date'");
$customers_prev = (int) scalar_query($conn, "SELECT COUNT(*) FROM users WHERE role='customer' AND DATE(created_at) BETWEEN '$prev_start_date' AND '$prev_end_date'");
$customers_chg  = pct_change($customers_now, $customers_prev);

$aov_now  = $orders_now  > 0 ? $revenue_now  / $orders_now  : 0;
$aov_prev = $orders_prev > 0 ? $revenue_prev / $orders_prev : 0;
$aov_chg  = pct_change($aov_now, $aov_prev);

// ---------------------------------------------------------
// Sales chart — bucketed to fit the selected range
// ---------------------------------------------------------
$chart_labels = [];
$chart_values = [];

if ($range === '7' || $range === '30') {
    for ($i = $days_in_range - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i day"));
        $chart_labels[] = date('M j', strtotime($day));
        $val = (float) scalar_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled' AND DATE(created_at) = '$day'");
        $chart_values[] = round($val, 2);
    }
} elseif ($range === '90') {
    for ($w = 12; $w >= 0; $w--) {
        $week_start = date('Y-m-d', strtotime("-" . ($w * 7 + 6) . " days"));
        $week_end   = date('Y-m-d', strtotime("-" . ($w * 7) . " days"));
        $chart_labels[] = date('M j', strtotime($week_start));
        $val = (float) scalar_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled' AND DATE(created_at) BETWEEN '$week_start' AND '$week_end'");
        $chart_values[] = round($val, 2);
    }
} else { // year
    for ($m = 0; $m < 12; $m++) {
        $month_num = $m + 1;
        if ($month_num > (int) date('n')) break; // don't show future months
        $chart_labels[] = date('M', mktime(0,0,0,$month_num,1));
        $val = (float) scalar_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = $month_num");
        $chart_values[] = round($val, 2);
    }
}

// ---------------------------------------------------------
// Order status breakdown (within range)
// ---------------------------------------------------------
$status_labels = [];
$status_values = [];
$status_colors_map = ['completed' => '#1F6F54', 'pending' => '#C1791F', 'cancelled' => '#B3432E'];
$res = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders WHERE DATE(created_at) >= '$start_date' GROUP BY status");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $status_labels[] = ucfirst($row['status']);
        $status_values[] = (int) $row['cnt'];
    }
}
$status_colors = array_map(fn($l) => $status_colors_map[strtolower($l)] ?? '#7C8781', $status_labels);

// ---------------------------------------------------------
// Products by category (real catalog composition, not sales)
// ---------------------------------------------------------
$category_rows = [];
$cat_table_check = $conn->query("SHOW TABLES LIKE 'categories'");
$has_categories = $cat_table_check && $cat_table_check->num_rows > 0;

if ($has_categories) {
    $total_products_all = (int) scalar_query($conn, "SELECT COUNT(*) FROM products");
    $res = $conn->query("
        SELECT c.name, COUNT(p.id) AS cnt
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        GROUP BY c.id
        HAVING cnt > 0
        ORDER BY cnt DESC
        LIMIT 6
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $pct = $total_products_all > 0 ? round(($row['cnt'] / $total_products_all) * 100) : 0;
            $category_rows[] = ['name' => $row['name'], 'count' => (int) $row['cnt'], 'pct' => $pct];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Analytics</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap" rel="stylesheet">

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
.num, .brand-mark, h1.page-title{ font-family:'Fraunces', Georgia, serif; }
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
.form-select{ height:44px; border:1.5px solid var(--line); border-radius:10px; font-size:13.5px; color:var(--ink); padding:0 14px; }
.form-select:focus{ outline:none; border-color:var(--evergreen); box-shadow:0 0 0 3px rgba(31,111,84,.1); }

.stat-card{ background:var(--surface); border:1px solid var(--line); border-left:3px solid var(--evergreen); border-radius:var(--radius-md); box-shadow:var(--shadow-card); padding:20px 22px; height:100%; }
.stat-card.accent-amber{ border-left-color:var(--amber); }
.stat-card.accent-rose{ border-left-color:var(--rose); }
.stat-icon{ width:38px; height:38px; border-radius:10px; background:var(--paper); color:var(--evergreen); display:flex; align-items:center; justify-content:center; font-size:14px; margin-bottom:14px; }
.stat-card h3{ font-size:26px; font-weight:600; margin:0 0 4px; font-family:'Fraunces', serif; }
.stat-card p{ color:var(--muted); margin:0 0 8px; font-size:12.5px; }
.stat-change{ display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--evergreen); }
.stat-change.down{ color:var(--rose); }
.stat-change .note{ font-weight:400; color:var(--muted); margin-left:2px; }

.panel{ background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); padding:22px; margin-top:24px; }
.panel-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
.panel-title{ font-size:16px; font-weight:600; color:var(--ink); }
.panel-sub{ font-size:12px; color:var(--muted); margin-top:2px; }
.chart-wrapper{ height:320px; }

.cat-row{ margin-bottom:18px; }
.cat-row:last-child{ margin-bottom:0; }
.cat-head{ display:flex; justify-content:space-between; margin-bottom:7px; font-size:13.5px; }
.cat-head strong{ color:var(--ink); }
.cat-head span{ color:var(--muted); }
.cat-bar{ height:8px; border-radius:10px; background:var(--paper); overflow:hidden; }
.cat-bar-fill{ height:100%; background:var(--evergreen); border-radius:10px; }
.empty-note{ text-align:center; color:var(--muted); font-size:13px; padding:30px 0; }

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
        <li><a href="analytics.php" class="active"><i class="fa-solid fa-chart-pie"></i><span class="nav-label">Analytics</span></a></li>
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
    <div class="sidebar-foot"><i class="fa-solid fa-circle-info"></i><span>Store synced<br>just now</span></div>
</div>

<div class="main-content" id="mainContent">

    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
            <div class="breadcrumb-trail">Zentury / <strong>Analytics</strong></div>
        </div>
        <div class="admin-profile">
            <div class="profile-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
            <div class="profile-text"><strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong><span>Administrator</span></div>
        </div>
    </div>

    <div class="page-wrap">

        <form method="GET" action="analytics.php" class="page-header">
            <div>
                <h1 class="page-title">Analytics overview</h1>
                <p class="page-sub">Track your store performance and sales, from real order data.</p>
            </div>
            <select name="range" class="form-select" style="max-width:200px;" onchange="this.form.submit()">
                <?php foreach ($range_labels as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $range === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- STATS -->
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card accent-ever">
                    <div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div>
                    <h3>$<?= number_format($revenue_now, 2) ?></h3>
                    <p>Total Revenue</p>
                    <div class="stat-change <?= $revenue_chg['trend'] === 'down' ? 'down' : '' ?>">
                        <i class="fa-solid fa-arrow-<?= $revenue_chg['trend'] ?>"></i> <?= $revenue_chg['value'] ?>%
                        <span class="note">vs previous period</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card accent-amber">
                    <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    <h3><?= number_format($orders_now) ?></h3>
                    <p>Total Orders</p>
                    <div class="stat-change <?= $orders_chg['trend'] === 'down' ? 'down' : '' ?>">
                        <i class="fa-solid fa-arrow-<?= $orders_chg['trend'] ?>"></i> <?= $orders_chg['value'] ?>%
                        <span class="note">vs previous period</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card accent-ever">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <h3><?= number_format($customers_now) ?></h3>
                    <p>New Customers</p>
                    <div class="stat-change <?= $customers_chg['trend'] === 'down' ? 'down' : '' ?>">
                        <i class="fa-solid fa-arrow-<?= $customers_chg['trend'] ?>"></i> <?= $customers_chg['value'] ?>%
                        <span class="note">vs previous period</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card accent-rose">
                    <div class="stat-icon"><i class="fa-solid fa-chart-simple"></i></div>
                    <h3>$<?= number_format($aov_now, 2) ?></h3>
                    <p>Average Order Value</p>
                    <div class="stat-change <?= $aov_chg['trend'] === 'down' ? 'down' : '' ?>">
                        <i class="fa-solid fa-arrow-<?= $aov_chg['trend'] ?>"></i> <?= $aov_chg['value'] ?>%
                        <span class="note">vs previous period</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SALES CHART -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <div class="panel-title">Sales overview</div>
                    <div class="panel-sub"><?= htmlspecialchars($range_labels[$range]) ?> — real revenue from orders</div>
                </div>
            </div>
            <?php if (array_sum($chart_values) == 0): ?>
                <div class="empty-note">No revenue recorded in this period yet.</div>
            <?php else: ?>
                <div class="chart-wrapper"><canvas id="salesChart"></canvas></div>
            <?php endif; ?>
        </div>

        <!-- TWO COLUMN -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="panel" style="margin-top:24px;">
                    <div class="panel-head">
                        <div>
                            <div class="panel-title">Products by category</div>
                            <div class="panel-sub">Share of your catalog, not sales</div>
                        </div>
                    </div>
                    <?php if (!$has_categories): ?>
                        <div class="empty-note">No categories set up yet.</div>
                    <?php elseif (empty($category_rows)): ?>
                        <div class="empty-note">No products assigned to categories yet.</div>
                    <?php else: ?>
                        <?php foreach ($category_rows as $c): ?>
                        <div class="cat-row">
                            <div class="cat-head">
                                <strong><?= htmlspecialchars($c['name']) ?></strong>
                                <span><?= $c['count'] ?> product<?= $c['count'] === 1 ? '' : 's' ?> · <?= $c['pct'] ?>%</span>
                            </div>
                            <div class="cat-bar"><div class="cat-bar-fill" style="width:<?= $c['pct'] ?>%"></div></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel" style="margin-top:24px;">
                    <div class="panel-head">
                        <div>
                            <div class="panel-title">Order status</div>
                            <div class="panel-sub"><?= htmlspecialchars($range_labels[$range]) ?></div>
                        </div>
                    </div>
                    <?php if (empty($status_labels)): ?>
                        <div class="empty-note">No orders in this period.</div>
                    <?php else: ?>
                        <div class="chart-wrapper" style="height:280px;"><canvas id="orderChart"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

<?php if (array_sum($chart_values) != 0): ?>
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode($chart_values) ?>,
            borderColor: '#1F6F54',
            backgroundColor: 'rgba(31,111,84,0.08)',
            fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: '#1F6F54', borderWidth: 2.5,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#EEF1EC' }, ticks: { color: '#7C8781', font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { color: '#7C8781', font: { size: 11 } } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($status_labels)): ?>
new Chart(document.getElementById('orderChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($status_labels) ?>,
        datasets: [{ data: <?= json_encode($status_values) ?>, backgroundColor: <?= json_encode($status_colors) ?>, borderWidth: 0 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { position: 'bottom', labels: { color: '#4B564F', font: { size: 11.5 }, padding: 16, usePointStyle: true } } }
    }
});
<?php endif; ?>
</script>

</body>
</html>