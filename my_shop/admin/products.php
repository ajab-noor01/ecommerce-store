<?php
// ==========================================================
// Nexora Admin — Products list (LIVE DATA)
// Schema used:
//   products(id, name, price, old_price, discount_badge, description,
//            image_1..4, is_new_arrival, is_popular, rating_stars, created_at)
// ==========================================================
include '../config/auth_check.php';
include '../config/db.php';

/** @var mysqli $conn */
if (!isset($conn) || $conn === null) {
    die("<div style='padding:20px;font-family:sans-serif;color:#B3432E'>⚠️ Database connection (\$conn) missing. Check config/db.php.</div>");
}

// ---------------------------------------------------------
// Handle delete (row + its uploaded images)
// ---------------------------------------------------------
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);

    $imgStmt = $conn->prepare("SELECT image_1, image_2, image_3, image_4 FROM products WHERE id = ?");
    $imgStmt->bind_param("i", $del_id);
    $imgStmt->execute();
    $imgRow = $imgStmt->get_result()->fetch_assoc();
    $imgStmt->close();

    if ($imgRow) {
        foreach ([$imgRow['image_1'], $imgRow['image_2'], $imgRow['image_3'], $imgRow['image_4']] as $img) {
            if (!empty($img)) {
                $path = "../" . $img;
                if (file_exists($path)) { @unlink($path); }
            }
        }
        $delStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delStmt->bind_param("i", $del_id);
        $delStmt->execute();
        $delStmt->close();
        header("Location: products.php?deleted=1");
        exit;
    }
}

// ---------------------------------------------------------
// Search / filter / sort (GET, real query)
// ---------------------------------------------------------
$search  = trim($_GET['search'] ?? '');
$section = $_GET['section'] ?? 'all';   // all | new | popular
$sort    = $_GET['sort'] ?? 'newest';   // newest | price_asc | price_desc

$where  = "1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND name LIKE ?";
    $params[] = "%" . $search . "%";
    $types   .= "s";
}
if ($section === 'new') {
    $where .= " AND is_new_arrival = 1";
} elseif ($section === 'popular') {
    $where .= " AND is_popular = 1";
}

$order = "id DESC";
if ($sort === 'price_asc')  $order = "price ASC";
if ($sort === 'price_desc') $order = "price DESC";

$sql = "SELECT * FROM products WHERE $where ORDER BY $order";
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_all = (int) ($conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zentury — Products</title>

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

.btn-quick{
    display:inline-flex; align-items:center; gap:8px; background: var(--evergreen); color:#fff;
    padding:11px 18px; border-radius:10px; font-size:13.5px; font-weight:600; border:none;
}
.btn-quick:hover{ background: var(--evergreen-deep); color:#fff; }

.alert-panel{
    border:none; border-left:3px solid var(--evergreen); border-radius: var(--radius-sm);
    background: var(--green-soft); color: var(--evergreen-deep);
    padding:12px 16px; font-size:13.5px; font-weight:500; margin-bottom:18px;
}

/* TOOLBAR */
.toolbar{
    background: var(--surface); border:1px solid var(--line); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card); padding:16px 18px; margin-bottom:20px;
}
.search-box{ position:relative; }
.search-box i{ position:absolute; left:14px; top:50%; transform:translateY(-50%); color: var(--muted); font-size:13px; }
.search-box input{ padding-left:38px; }
.form-control, .form-select{
    height:44px; border:1.5px solid var(--line); border-radius:10px; font-size:13.5px; color: var(--ink);
}
.form-control:focus, .form-select:focus{ border-color: var(--evergreen); box-shadow: 0 0 0 3px rgba(31,111,84,0.1); }
.btn-filter{
    height:44px; width:100%; border-radius:10px; border:1.5px solid var(--line); background: var(--surface);
    color: var(--ink-soft); font-size:13px; font-weight:600;
}
.btn-filter:hover{ background: var(--paper); }

/* PRODUCTS PANEL */
.products-box{ background: var(--surface); border:1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); overflow:hidden; }
.products-box-header{ padding:20px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
.products-box-header h5{ margin:0; font-size:16px; font-weight:600; }
.product-count{ color: var(--muted); font-size:12.5px; }

.table{ margin:0; }
.table th{ background: var(--paper); color: var(--muted); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:14px 15px; border-bottom:1px solid var(--line); }
.table td{ padding:14px 15px; vertical-align:middle; font-size:13.5px; border-bottom:1px solid var(--line); color: var(--ink-soft); }
.table tbody tr:last-child td{ border-bottom:none; }
.table tbody tr:hover{ background: var(--paper); }

.product-info{ display:flex; align-items:center; gap:12px; }
.product-image{ width:52px; height:52px; border-radius:9px; object-fit:cover; background: var(--paper); border:1px solid var(--line); flex-shrink:0; }
.product-name{ font-weight:600; color: var(--ink); font-size:13.5px; }
.product-sku{ color: var(--muted); font-size:11.5px; margin-top:2px; font-family:'Fraunces', serif; }

.price-cell .cur{ font-weight:600; color: var(--ink); }
.price-cell .old{ text-decoration: line-through; color: var(--muted); font-size:12px; margin-left:6px; }

.tag{ display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:10.5px; font-weight:600; margin-right:5px; margin-bottom:4px; }
.tag.new{ background: var(--amber-soft); color: var(--amber); }
.tag.popular{ background: var(--green-soft); color: var(--evergreen); }
.tag.none{ color: var(--muted); font-size:11.5px; font-weight:400; }

.rating-cell{ color: var(--amber); font-size:12.5px; letter-spacing:1px; }

.action-btn{
    width:32px; height:32px; border:none; border-radius:8px; display:inline-flex; align-items:center;
    justify-content:center; margin-right:5px; text-decoration:none; font-size:12px;
}
.edit-btn{ background: var(--green-soft); color: var(--evergreen); }
.delete-btn{ background: var(--rose-soft); color: var(--rose); border:none; cursor:pointer; }
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
        <li><a href="products.php" class="active"><i class="fa-solid fa-box"></i><span class="nav-label">Total Products</span></a></li>
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
            <div class="breadcrumb-trail">Zentury / <strong>Products</strong></div>
        </div>
        <div class="admin-profile">
            <div class="profile-avatar">AD</div>
            <div class="profile-text"><strong>Admin</strong><span>Administrator</span></div>
        </div>
    </div>

    <div class="page-wrap">

        <div class="page-header">
            <div>
                <h1 class="page-title">Products</h1>
                <p class="page-sub">Manage everything in your store catalog.</p>
            </div>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert-panel">✅ Product deleted successfully.</div>
        <?php endif; ?>

        <!-- TOOLBAR (real search/filter, submits via GET) -->
        <form class="toolbar" method="GET" action="products.php">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search products by name..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <select class="form-select" name="section" onchange="this.form.submit()">
                        <option value="all"     <?= $section === 'all' ? 'selected' : '' ?>>All Products</option>
                        <option value="new"     <?= $section === 'new' ? 'selected' : '' ?>>New Arrivals</option>
                        <option value="popular" <?= $section === 'popular' ? 'selected' : '' ?>>Popular This Week</option>
                    </select>
                </div>
                <div class="col-lg-2 col-6">
                    <select class="form-select" name="sort" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
                        <option value="price_asc"  <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                    </select>
                </div>
                <div class="col-lg-1 col-6">
                    <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i></button>
                </div>
            </div>
        </form>

        <!-- PRODUCTS TABLE -->
        <div class="products-box">
            <div class="products-box-header">
                <h5>All Products</h5>
                <span class="product-count"><?= count($products) ?> of <?= $total_all ?> product<?= $total_all === 1 ? '' : 's' ?></span>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <?= $total_all === 0 ? 'No products yet — add your first one.' : 'No products match your search/filter.' ?>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Section</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p):
                            $img = !empty($p['image_1']) ? "../" . htmlspecialchars($p['image_1']) : '';
                            $stars = intval($p['rating_stars']);
                        ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <?php if ($img): ?>
                                        <img src="<?= $img ?>" class="product-image" alt="<?= htmlspecialchars($p['name']) ?>">
                                    <?php else: ?>
                                        <div class="product-image d-flex align-items-center justify-content-center text-muted"><i class="fa-solid fa-image"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="product-sku">#NX-P<?= str_pad((string) $p['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="price-cell">
                                <span class="cur">$<?= number_format((float) $p['price'], 2) ?></span>
                                <?php if (!empty($p['old_price']) && $p['old_price'] > $p['price']): ?>
                                    <span class="old">$<?= number_format((float) $p['old_price'], 2) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['discount_badge'])): ?>
                                    <div class="tag new" style="margin-top:5px;"><?= htmlspecialchars($p['discount_badge']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$p['is_new_arrival'] === 1): ?><span class="tag new">New Arrival</span><?php endif; ?>
                                <?php if ((int)$p['is_popular'] === 1): ?><span class="tag popular">Popular</span><?php endif; ?>
                                <?php if ((int)$p['is_new_arrival'] !== 1 && (int)$p['is_popular'] !== 1): ?><span class="tag none">— Unlisted —</span><?php endif; ?>
                            </td>
                            <td class="rating-cell"><?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?></td>
                            <td>
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="action-btn edit-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <a href="products.php?delete=<?= $p['id'] ?>" class="action-btn delete-btn" title="Delete"
                                   onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\'? This cannot be undone.');">
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