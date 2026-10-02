<?php
// ==========================================================
// Nexora Admin — Customers (LIVE DATA)
// Schema used:
//   users(id, name, email, password, role, created_at)
//   orders(id, user_id, total_amount, status, created_at)
// Note: users table has no 'phone' or 'status' column.
//   Status here is DERIVED: >=1 order = Active, 0 orders = Inactive.
// ==========================================================
include '../config/auth_check.php';
include '../config/db.php';

/** @var mysqli $conn */
if (!isset($conn) || $conn === null) {
    die("<div style='padding:20px;font-family:sans-serif;color:#B3432E'>⚠️ Database connection (\$conn) missing. Check config/db.php.</div>");
}

function initials(string $name): string {
    $name = trim($name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

// ---------------------------------------------------------
// Search / filter / sort
// ---------------------------------------------------------
$search        = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';   // '', active, inactive
$sort          = $_GET['sort'] ?? 'newest'; // newest, oldest, name

$where  = "u.role = 'customer'";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

$having = "";
if ($status_filter === 'active') {
    $having = "HAVING order_count > 0";
} elseif ($status_filter === 'inactive') {
    $having = "HAVING order_count = 0";
}

$order = "u.created_at DESC";
if ($sort === 'oldest') $order = "u.created_at ASC";
if ($sort === 'name')   $order = "u.name ASC";

$sql = "
    SELECT u.id, u.name, u.email, u.created_at,
           COUNT(o.id) AS order_count,
           COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE $where
    GROUP BY u.id
    $having
    ORDER BY $order
";
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------------------------------------------------
// Summary stats (real)
// ---------------------------------------------------------
$total_customers = (int) ($conn->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch_assoc()['c'] ?? 0);

$active_customers = (int) ($conn->query("
    SELECT COUNT(DISTINCT u.id) c
    FROM users u
    INNER JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer'
")->fetch_assoc()['c'] ?? 0);

$inactive_customers = $total_customers - $active_customers;

$new_this_month = (int) ($conn->query("
    SELECT COUNT(*) c FROM users
    WHERE role = 'customer'
      AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Customers</title>

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
.brand-mark, h1.page-title{ font-family:'Fraunces', Georgia, serif; }
a{ text-decoration:none; color:inherit; }

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
.admin-profile{ display:flex; align-items:center; gap:11px; }
.profile-avatar{
    width:38px; height:38px; border-radius:50%; background: var(--green-soft); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center; font-weight:600; font-size:13px;
}
.profile-text strong{ display:block; font-size:13.5px; font-weight:600; }
.profile-text span{ font-size:11.5px; color: var(--muted); }

/* CONTENT */
.page-wrap{ padding:30px 28px 50px; }
.page-header{
    display:flex; align-items:flex-end; justify-content:space-between;
    padding-bottom:20px; margin-bottom:22px; border-bottom:1px solid var(--line); flex-wrap:wrap; gap:14px;
}
h1.page-title{ font-size:25px; font-weight:500; margin:0 0 6px; color: var(--evergreen-900); }
.page-sub{ margin:0; color: var(--muted); font-size:13.5px; }

/* STAT CARDS */
.stat-card{
    background: var(--surface); border:1px solid var(--line); border-left:3px solid var(--evergreen);
    border-radius: var(--radius-md); box-shadow: var(--shadow-card); padding:20px 22px; height:100%;
}
.stat-card.accent-amber{ border-left-color: var(--amber); }
.stat-card.accent-rose{ border-left-color: var(--rose); }
.stat-top{ display:flex; align-items:center; justify-content:space-between; }
.stat-label{ font-size:12.5px; color: var(--muted); font-weight:500; }
.stat-icon{
    width:38px; height:38px; border-radius:10px; background: var(--paper); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center; font-size:14px;
}
.stat-card h3{ font-size:26px; font-weight:600; margin:14px 0 6px; font-family:'Fraunces', serif; }
.stat-card p{ color: var(--muted); margin:0; font-size:12px; }

/* TOOLBAR */
.toolbar{
    background: var(--surface); border:1px solid var(--line); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card); padding:16px 18px; margin-top:26px; margin-bottom:20px;
}
.search-box{ position:relative; }
.search-box i{ position:absolute; left:14px; top:50%; transform:translateY(-50%); color: var(--muted); font-size:13px; }
.search-box input{ padding-left:38px; }
.form-control, .form-select{
    height:44px; border:1.5px solid var(--line); border-radius:10px; font-size:13.5px; color: var(--ink);
}
.form-control:focus, .form-select:focus{ border-color: var(--evergreen); box-shadow: 0 0 0 3px rgba(31,111,84,0.1); }
.btn-filter{ height:44px; width:100%; border-radius:10px; border:1.5px solid var(--line); background: var(--surface); color: var(--ink-soft); font-size:13px; font-weight:600; }
.btn-filter:hover{ background: var(--paper); }

/* CUSTOMERS TABLE */
.customers-box{ background: var(--surface); border:1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); overflow:hidden; }
.customers-header{ padding:20px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
.customers-header h5{ margin:0; font-size:16px; font-weight:600; }
.customer-count{ color: var(--muted); font-size:12.5px; }

.table{ margin:0; }
.table th{ background: var(--paper); color: var(--muted); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:14px 15px; border-bottom:1px solid var(--line); }
.table td{ padding:14px 15px; vertical-align:middle; font-size:13.5px; border-bottom:1px solid var(--line); color: var(--ink-soft); }
.table tbody tr:last-child td{ border-bottom:none; }
.table tbody tr:hover{ background: var(--paper); }

.customer{ display:flex; align-items:center; gap:12px; }
.customer-avatar{
    width:42px; height:42px; border-radius:50%; background: var(--green-soft); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center; font-size:12.5px; font-weight:700; flex-shrink:0;
}
.customer-name{ color: var(--ink); font-weight:600; font-size:13.5px; }
.customer-email{ color: var(--muted); font-size:11.5px; margin-top:2px; }

.status{ display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:20px; font-size:11px; font-weight:600; }
.status::before{ content:""; width:6px; height:6px; border-radius:50%; }
.status-active{ background: var(--green-soft); color: var(--evergreen); }
.status-active::before{ background: var(--evergreen); }
.status-inactive{ background: #F0F1EE; color: var(--muted); }
.status-inactive::before{ background: var(--muted); }

.action-btn{
    width:32px; height:32px; border:none; border-radius:8px; display:inline-flex; align-items:center;
    justify-content:center; margin-right:5px; text-decoration:none; font-size:12px;
}
.view-btn{ background: var(--green-soft); color: var(--evergreen); }
.edit-btn{ background: var(--amber-soft); color: var(--amber); }
.action-btn:hover{ opacity:.8; }

.empty-state{ text-align:center; padding:60px 20px; color: var(--muted); }
.empty-state i{ font-size:34px; margin-bottom:12px; color: var(--line); display:block; }

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
    <div class="brand">
        <div class="brand-mark">Z</div>
        <div class="brand-name">Zentury</div>
    </div>

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
        <li><a href="orders.php"><i class="fa-solid fa-cart-shopping"></i><span class="nav-label">Orders</span></a></li>
        <li><a href="customers.php" class="active"><i class="fa-solid fa-users"></i><span class="nav-label">Customers</span></a></li>
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
            <div class="breadcrumb-trail">Zentury / <strong>Customers</strong></div>
        </div>
        <div class="admin-profile">
            <div class="profile-avatar">AD</div>
            <div class="profile-text"><strong>Admin</strong><span>Administrator</span></div>
        </div>
    </div>

    <div class="page-wrap">

        <div class="page-header">
            <div>
                <h1 class="page-title">Customers</h1>
                <p class="page-sub">Everyone who's created an account in your store.</p>
            </div>
        </div>

        <!-- STATS (real) -->
        <div class="row g-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-ever">
                    <div class="stat-top">
                        <span class="stat-label">Total Customers</span>
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    </div>
                    <h3><?= number_format($total_customers) ?></h3>
                    <p>Registered accounts</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-ever">
                    <div class="stat-top">
                        <span class="stat-label">Active Customers</span>
                        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                    </div>
                    <h3><?= number_format($active_customers) ?></h3>
                    <p>Placed at least 1 order</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-amber">
                    <div class="stat-top">
                        <span class="stat-label">New Customers</span>
                        <div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                    </div>
                    <h3><?= number_format($new_this_month) ?></h3>
                    <p>Joined this month</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent-rose">
                    <div class="stat-top">
                        <span class="stat-label">Inactive</span>
                        <div class="stat-icon"><i class="fa-solid fa-user-slash"></i></div>
                    </div>
                    <h3><?= number_format($inactive_customers) ?></h3>
                    <p>No orders yet</p>
                </div>
            </div>
        </div>

        <!-- TOOLBAR (real search/filter/sort) -->
        <form class="toolbar" method="GET" action="customers.php">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search customer name or email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-8">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value=""         <?= $status_filter === '' ? 'selected' : '' ?>>All Customers</option>
                        <option value="active"   <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-lg-2 col-4">
                    <select class="form-select" name="sort" onchange="this.form.submit()">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        <option value="name"   <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                    </select>
                </div>
            </div>
        </form>

        <!-- CUSTOMERS TABLE -->
        <div class="customers-box">
            <div class="customers-header">
                <h5>All Customers</h5>
                <span class="customer-count"><?= count($customers) ?> of <?= $total_customers ?></span>
            </div>

            <?php if (empty($customers)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users"></i>
                    <?= $total_customers === 0 ? 'No customers have registered yet.' : 'No customers match your search/filter.' ?>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c):
                            $is_active = (int) $c['order_count'] > 0;
                        ?>
                        <tr>
                            <td>
                                <div class="customer">
                                    <div class="customer-avatar"><?= htmlspecialchars(initials($c['name'])) ?></div>
                                    <div>
                                        <div class="customer-name"><?= htmlspecialchars($c['name']) ?></div>
                                        <div class="customer-email"><?= htmlspecialchars($c['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= (int) $c['order_count'] ?></td>
                            <td><strong>$<?= number_format((float) $c['total_spent'], 2) ?></strong></td>
                            <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <span class="status <?= $is_active ? 'status-active' : 'status-inactive' ?>">
                                    <?= $is_active ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <a href="customer-detail.php?id=<?= $c['id'] ?>" class="action-btn view-btn" title="View"><i class="fa-solid fa-eye"></i></a>
                                <a href="edit_customer.php?id=<?= $c['id'] ?>" class="action-btn edit-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>
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
