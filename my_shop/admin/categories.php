<?php
// ==========================================================
// Nexora Admin — Categories (LIVE DATA + CRUD)
// Requires: categories table + products.category_id
// (see categories_migration.sql)
// ==========================================================
include '../config/auth_check.php';
include '../config/db.php';

/** @var mysqli $conn */
if (!isset($conn) || $conn === null) {
    die("<div style='padding:20px;font-family:sans-serif;color:#B3432E'>⚠️ Database connection (\$conn) missing. Check config/db.php.</div>");
}

// Friendly check: does the categories table exist yet?
$table_check = $conn->query("SHOW TABLES LIKE 'categories'");
$categories_table_exists = $table_check && $table_check->num_rows > 0;

$message = "";
$message_type = "success";

if ($categories_table_exists) {

    // ---------------------------------------------------------
    // ADD category
    // ---------------------------------------------------------
    if (isset($_POST['add_category'])) {
        $name   = trim($_POST['name'] ?? '');
        $icon   = trim($_POST['icon'] ?? '') ?: 'fa-solid fa-layer-group';
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            $message = "⚠️ Category name zaroori hai.";
            $message_type = "warning";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, icon, status) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $icon, $status);
            if ($stmt->execute()) {
                $message = "🎉 Category \"$name\" add ho gayi.";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    }

    // ---------------------------------------------------------
    // EDIT category
    // ---------------------------------------------------------
    if (isset($_POST['edit_category'])) {
        $id     = intval($_POST['id']);
        $name   = trim($_POST['name'] ?? '');
        $icon   = trim($_POST['icon'] ?? '') ?: 'fa-solid fa-layer-group';
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            $message = "⚠️ Category name zaroori hai.";
            $message_type = "warning";
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $icon, $status, $id);
            if ($stmt->execute()) {
                $message = "✅ Category update ho gayi.";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    }

    // ---------------------------------------------------------
    // DELETE category (products.category_id auto SET NULL via FK)
    // ---------------------------------------------------------
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: categories.php?deleted=1");
        exit;
    }

    // ---------------------------------------------------------
    // TOGGLE status (quick active/inactive switch)
    // ---------------------------------------------------------
    if (isset($_GET['toggle'])) {
        $id = intval($_GET['toggle']);
        $conn->query("UPDATE categories SET status = IF(status='active','inactive','active') WHERE id = $id");
        header("Location: categories.php");
        exit;
    }

    // ---------------------------------------------------------
    // Search / filter
    // ---------------------------------------------------------
    $search = trim($_GET['search'] ?? '');
    $status_filter = $_GET['status'] ?? '';

    $where = "1=1";
    $params = [];
    $types = "";
    if ($search !== '') {
        $where .= " AND c.name LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }
    if ($status_filter === 'active' || $status_filter === 'inactive') {
        $where .= " AND c.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    $sql = "
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        WHERE $where
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ---------------------------------------------------------
    // Stats (real)
    // ---------------------------------------------------------
    $total_categories    = (int) ($conn->query("SELECT COUNT(*) c FROM categories")->fetch_assoc()['c'] ?? 0);
    $total_products      = (int) ($conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'] ?? 0);
    $active_categories   = (int) ($conn->query("SELECT COUNT(*) c FROM categories WHERE status='active'")->fetch_assoc()['c'] ?? 0);
    $inactive_categories = (int) ($conn->query("SELECT COUNT(*) c FROM categories WHERE status='inactive'")->fetch_assoc()['c'] ?? 0);

    // Row being edited (if any), for pre-filling the modal
    $editing = null;
    if (isset($_GET['edit'])) {
        $eid = intval($_GET['edit']);
        $es = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $es->bind_param("i", $eid);
        $es->execute();
        $editing = $es->get_result()->fetch_assoc();
        $es->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Categories</title>

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
.num, .brand-mark, h1.page-title{ font-family:'Fraunces', Georgia, serif; }
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
.btn-quick{
    display:inline-flex; align-items:center; gap:8px; background: var(--evergreen); color:#fff;
    padding:11px 18px; border-radius:10px; font-size:13.5px; font-weight:600; border:none; cursor:pointer;
}
.btn-quick:hover{ background: var(--evergreen-deep); color:#fff; }

.alert-panel{
    border:none; border-left:3px solid; border-radius: var(--radius-sm);
    padding:12px 16px; font-size:13.5px; font-weight:500; margin-bottom:18px;
}
.alert-panel.success{ background: var(--green-soft); color: var(--evergreen-deep); border-color: var(--evergreen); }
.alert-panel.danger{ background: var(--rose-soft); color: var(--rose); border-color: var(--rose); }
.alert-panel.warning{ background: var(--amber-soft); color: var(--amber); border-color: var(--amber); }

/* STAT CARDS */
.stat-card{
    background: var(--surface); border:1px solid var(--line); border-left:3px solid var(--evergreen);
    border-radius: var(--radius-md); box-shadow: var(--shadow-card); padding:20px 22px; height:100%;
}
.stat-card.accent-amber{ border-left-color: var(--amber); }
.stat-card.accent-rose{ border-left-color: var(--rose); }
.stat-icon{
    width:38px; height:38px; border-radius:10px; background: var(--paper); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center; font-size:14px; margin-bottom:14px;
}
.stat-card h3{ font-size:26px; font-weight:600; margin:0 0 4px; font-family:'Fraunces', serif; }
.stat-card p{ color: var(--muted); margin:0; font-size:12.5px; }

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

/* CATEGORY TABLE */
.category-box{ background: var(--surface); border:1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); overflow:hidden; }
.category-box-head{ padding:20px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
.category-box-head h5{ margin:0; font-size:16px; font-weight:600; }
.category-count{ color: var(--muted); font-size:12.5px; }

.table{ margin:0; }
.table th{ background: var(--paper); color: var(--muted); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:14px 15px; border-bottom:1px solid var(--line); }
.table td{ padding:14px 15px; vertical-align:middle; font-size:13.5px; border-bottom:1px solid var(--line); color: var(--ink-soft); }
.table tbody tr:last-child td{ border-bottom:none; }
.table tbody tr:hover{ background: var(--paper); }

.category-info{ display:flex; align-items:center; }
.category-icon{
    width:40px; height:40px; border-radius:9px; background: var(--green-soft); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center; margin-right:12px; flex-shrink:0; font-size:14px;
}
.category-name{ font-weight:600; color: var(--ink); font-size:13.5px; }

.status{ display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:20px; font-size:11px; font-weight:600; cursor:pointer; border:none; }
.status::before{ content:""; width:6px; height:6px; border-radius:50%; }
.status-active{ background: var(--green-soft); color: var(--evergreen); }
.status-active::before{ background: var(--evergreen); }
.status-inactive{ background: var(--rose-soft); color: var(--rose); }
.status-inactive::before{ background: var(--rose); }

.action-btn{
    width:32px; height:32px; border:none; border-radius:8px; display:inline-flex; align-items:center;
    justify-content:center; margin-right:5px; text-decoration:none; font-size:12px; cursor:pointer;
}
.edit-btn{ background: var(--green-soft); color: var(--evergreen); }
.delete-btn{ background: var(--rose-soft); color: var(--rose); }
.action-btn:hover{ opacity:.8; }

.empty-state{ text-align:center; padding:60px 20px; color: var(--muted); }
.empty-state i{ font-size:34px; margin-bottom:12px; color: var(--line); display:block; }

/* SETUP NOTICE (when categories table doesn't exist yet) */
.setup-card{
    background: var(--surface); border:1px solid var(--line); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card); padding:32px; text-align:center; margin-top:20px;
}
.setup-card i{ font-size:36px; color: var(--amber); margin-bottom:14px; }
.setup-card code{ background: var(--paper); padding:2px 8px; border-radius:6px; color: var(--evergreen); }

/* MODAL tweaks */
.modal-content{ border-radius: var(--radius-lg); border:none; }
.modal-header{ border-bottom:1px solid var(--line); }
.modal-footer{ border-top:1px solid var(--line); }
.field-label{ font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; color: var(--ink-soft); margin-bottom:6px; display:block; }

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
        <li><a href="categories.php" class="active"><i class="fa-solid fa-layer-group"></i><span class="nav-label">Categories</span></a></li>
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
            <div class="breadcrumb-trail">Zentury / <strong>Categories</strong></div>
        </div>
        <div class="admin-profile">
            <div class="profile-avatar">AD</div>
            <div class="profile-text"><strong>Admin</strong><span>Administrator</span></div>
        </div>
    </div>

    <div class="page-wrap">

        <div class="page-header">
            <div>
                <h1 class="page-title">Categories</h1>
                <p class="page-sub">Organize your catalog into browsable categories.</p>
            </div>
            <?php if ($categories_table_exists): ?>
                <button type="button" class="btn-quick" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> Add Category
                </button>
            <?php endif; ?>
        </div>

        <?php if (!$categories_table_exists): ?>

            <!-- ================= SETUP NOTICE ================= -->
            <div class="setup-card">
                <i class="fa-solid fa-database"></i>
                <h5 class="mb-2">Categories table not found yet</h5>
                <p class="text-muted mb-1" style="max-width:520px; margin:0 auto;">
                    Run the <code>categories_migration.sql</code> file in phpMyAdmin (SQL tab, on the
                    <code>ecommerce_db</code> database) to create the <code>categories</code> table and
                    link it to <code>products</code>. Then refresh this page.
                </p>
            </div>

        <?php else: ?>

            <?php if (!empty($message)): ?>
                <div class="alert-panel <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert-panel success">✅ Category deleted. Its products are now uncategorized.</div>
            <?php endif; ?>

            <!-- ================= STATS ================= -->
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card accent-ever">
                        <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <h3><?= $total_categories ?></h3>
                        <p>Total Categories</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card accent-amber">
                        <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
                        <h3><?= $total_products ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card accent-ever">
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <h3><?= $active_categories ?></h3>
                        <p>Active Categories</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card accent-rose">
                        <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                        <h3><?= $inactive_categories ?></h3>
                        <p>Inactive Categories</p>
                    </div>
                </div>
            </div>

            <!-- ================= TOOLBAR ================= -->
            <form class="toolbar" method="GET" action="categories.php">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search categories..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-lg-3 col-9">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="" <?= $status_filter === '' ? 'selected' : '' ?>>All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-lg-1 col-3">
                        <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i></button>
                    </div>
                </div>
            </form>

            <!-- ================= TABLE ================= -->
            <div class="category-box">
                <div class="category-box-head">
                    <h5>All Categories</h5>
                    <span class="category-count"><?= count($categories) ?> of <?= $total_categories ?></span>
                </div>

                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-layer-group"></i>
                        <?= $total_categories === 0 ? 'No categories yet — add your first one.' : 'No categories match your search/filter.' ?>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Products</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                            <tr>
                                <td>
                                    <div class="category-info">
                                        <div class="category-icon"><i class="<?= htmlspecialchars($c['icon']) ?>"></i></div>
                                        <span class="category-name"><?= htmlspecialchars($c['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= (int) $c['product_count'] ?></td>
                                <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <a href="categories.php?toggle=<?= $c['id'] ?>" class="status status-<?= $c['status'] ?>" title="Click to toggle">
                                        <?= ucfirst($c['status']) ?>
                                    </a>
                                </td>
                                <td>
                                    <button type="button" class="action-btn edit-btn" title="Edit"
                                        onclick='openEditModal(<?= json_encode($c) ?>)'
                                        data-bs-toggle="modal" data-bs-target="#categoryModal">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="categories.php?delete=<?= $c['id'] ?>" class="action-btn delete-btn" title="Delete"
                                       onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($c['name'])) ?>\'? Products in it will become uncategorized.');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- ================= ADD / EDIT MODAL ================= -->
            <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="categories.php">
                            <div class="modal-header">
                                <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="cat_id">

                                <div class="mb-3">
                                    <label class="field-label">Category name <span style="color:var(--rose)">*</span></label>
                                    <input type="text" name="name" id="cat_name" class="form-control" required placeholder="e.g., Fashion">
                                </div>

                                <div class="mb-3">
                                    <label class="field-label">Icon (Font Awesome class)</label>
                                    <input type="text" name="icon" id="cat_icon" class="form-control" placeholder="fa-solid fa-shirt">
                                    <div class="form-text">Examples: fa-solid fa-shirt, fa-solid fa-shoe-prints, fa-solid fa-mobile-screen</div>
                                </div>

                                <div class="mb-1">
                                    <label class="field-label">Status</label>
                                    <select name="status" id="cat_status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_category" id="cat_submit_add" class="btn-quick">Save Category</button>
                                <button type="submit" name="edit_category" id="cat_submit_edit" class="btn-quick" style="display:none;">Update Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const toggleBtn = document.getElementById('sidebarToggle');
if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('mobile-open');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    });
}

function openAddModal() {
    document.getElementById('categoryModalTitle').textContent = 'Add Category';
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_icon').value = '';
    document.getElementById('cat_status').value = 'active';
    document.getElementById('cat_submit_add').style.display = 'inline-flex';
    document.getElementById('cat_submit_edit').style.display = 'none';
}

function openEditModal(cat) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('cat_name').value = cat.name;
    document.getElementById('cat_icon').value = cat.icon;
    document.getElementById('cat_status').value = cat.status;
    document.getElementById('cat_submit_add').style.display = 'none';
    document.getElementById('cat_submit_edit').style.display = 'inline-flex';
}
</script>

</body>
</html>
