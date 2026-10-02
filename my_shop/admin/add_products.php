<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once '../config/auth_check.php';

// SERVER ABSOLUTE PATH SETUP (XAMPP/Windows ke liye 100% safe path)
$config_path = dirname(__DIR__) . '/config/db.php';

if (file_exists($config_path)) {
    include($config_path);
} else {
    die("<div class='alert alert-danger'>⚠️ Error: db_connect.php file nahi mili! Sahi path yeh hona chahiye: " . $config_path . "</div>");
}

// DATABASE CONNECTION VARIABLE CHECK
if (!isset($conn) || $conn === null) {
    die("<div class='alert alert-danger'>⚠️ Error: Database connection variable (\$conn) missing ya null hai! Apni 'config/db_connect.php' check karein ke wahan variable ka naam exact \$conn hi ho.</div>");
}

/** @var mysqli $conn */

// Categories dropdown — only if the categories table exists
$categories = [];
$cat_check = $conn->query("SHOW TABLES LIKE 'categories'");
$has_categories = $cat_check && $cat_check->num_rows > 0;
if ($has_categories) {
    $cat_res = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
    if ($cat_res) $categories = $cat_res->fetch_all(MYSQLI_ASSOC);
}
$message = "";
$message_type = "success"; // success | danger | warning

if (isset($_POST['submit_new_arrival']) || isset($_POST['submit_popular'])) {

    // Inputs secure read karna
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $price = isset($_POST['price']) ? filter_var($_POST['price'], FILTER_VALIDATE_FLOAT) : 0.00;

    $old_price = null;
    if (!empty($_POST['old_price'])) {
        $old_price = filter_var($_POST['old_price'], FILTER_VALIDATE_FLOAT);
    }

    $discount_badge = isset($_POST['discount_badge']) ? trim($_POST['discount_badge']) : '';
    $amazon_link_raw = isset($_POST['amazon_link']) ? trim($_POST['amazon_link']) : '';
    $amazon_link = $amazon_link_raw !== '' ? $amazon_link_raw : null;
    $category_id = ($has_categories && !empty($_POST['category_id'])) ? intval($_POST['category_id']) : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $rating_stars = isset($_POST['rating_stars']) ? intval($_POST['rating_stars']) : 5;

    // Button detection logic
    $is_new_arrival = 0;
    $is_popular = 0;

    if (isset($_POST['submit_new_arrival'])) {
        $is_new_arrival = 1; // Orange patti ke uper ke liye
    } elseif (isset($_POST['submit_popular'])) {
        $is_popular = 1; // Orange patti ke niche ke liye
    }

    // File Upload Core
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $uploaded_images = [];
    // MIME-type based allow-list: checks the file's actual content, not its
    // filename, so weird filenames (e.g. "photo.jpg_720x720q80" from some
    // websites) still work as long as the file itself really is an image.
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'image/bmp'  => 'bmp',
        'image/avif' => 'avif',
    ];
    $upload_ok = true;

    for ($i = 1; $i <= 4; $i++) {
        $file_field = "image_" . $i;
        if (!empty($_FILES[$file_field]['name']) && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES[$file_field]['tmp_name'];

            $finfo     = finfo_open(FILEINFO_MIME_TYPE);
            $real_mime = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (isset($allowed_mimes[$real_mime])) {
                $ext = $allowed_mimes[$real_mime];
                $new_filename = bin2hex(random_bytes(10)) . "_" . $i . "." . $ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    $uploaded_images[$file_field] = "uploads/" . $new_filename;
                } else {
                    $upload_ok = false;
                    $message = "⚠️ Image " . $i . " upload nahi ho saki.";
                    $message_type = "danger";
                    break;
                }
            } else {
                $upload_ok = false;
                $message = "⚠️ Image " . $i . " ek supported image type nahi hai (JPG, PNG, WEBP, GIF, BMP, ya AVIF honi chahiye).";
                $message_type = "danger";
                break;
            }
        } else {
            $uploaded_images[$file_field] = null;
        }
    }

    // Database Insert
    if ($upload_ok) {
        if (!empty($uploaded_images['image_1']) && $price !== false) {

            $img1 = $uploaded_images['image_1'];
            $img2 = $uploaded_images['image_2'];
            $img3 = $uploaded_images['image_3'];
            $img4 = $uploaded_images['image_4'];

            if ($has_categories) {
                $sql = "INSERT INTO products (name, price, old_price, discount_badge, amazon_link, description, category_id, image_1, image_2, image_3, image_4, is_new_arrival, is_popular, rating_stars) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sddsssissssiii",
                    $name, $price, $old_price, $discount_badge, $amazon_link, $description, $category_id,
                    $img1, $img2, $img3, $img4, $is_new_arrival, $is_popular, $rating_stars
                );
            } else {
                $sql = "INSERT INTO products (name, price, old_price, discount_badge, amazon_link, description, image_1, image_2, image_3, image_4, is_new_arrival, is_popular, rating_stars) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sddsssssssiii", $name, $price, $old_price, $discount_badge, $amazon_link, $description, $img1, $img2, $img3, $img4, $is_new_arrival, $is_popular, $rating_stars);
            }

            if ($stmt->execute()) {
                $section_name = $is_new_arrival ? "New Arrivals" : "Popular This Week";
                $message = "🎉 Product successfully published to <strong>$section_name</strong>.";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            if (empty($message)) {
                $message = "⚠️ Cover Image 1 aur Price zaroori hain!";
                $message_type = "warning";
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
<title>Nexora — Add Product</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap" rel="stylesheet">

<style>
/* ============================================================
   TOKENS — shared with dashboard for a consistent product suite
   ============================================================ */
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

*{ box-sizing:border-box; }

body{
    margin:0;
    font-family:'Inter', -apple-system, Segoe UI, sans-serif;
    background: var(--paper);
    color: var(--ink);
    font-size:14.5px;
    -webkit-font-smoothing:antialiased;
}

.num, .brand-mark, h1.page-title{ font-family:'Fraunces', Georgia, serif; }
a{ text-decoration:none; color:inherit; }

/* ============================================================
   SIDEBAR (matches dashboard)
   ============================================================ */
.sidebar{
    position:fixed; left:0; top:0; width:252px; height:100vh;
    background: linear-gradient(180deg, var(--evergreen-deep) 0%, var(--evergreen-900) 100%);
    color:#EAF1EC; padding:26px 18px; z-index:1000;
    display:flex; flex-direction:column;
    transition: width .25s ease, padding .25s ease;
}
.sidebar.collapsed{ width:82px; padding:26px 12px; }
.sidebar.collapsed .brand-name,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-section-label,
.sidebar.collapsed .sidebar-foot span{ display:none; }
.sidebar.collapsed .sidebar-menu a{ justify-content:center; }
.sidebar.collapsed .brand{ justify-content:center; }

.brand{ display:flex; align-items:center; gap:10px; padding:4px 8px 26px 8px; }
.brand-mark{
    width:34px; height:34px; border-radius:9px;
    background: var(--amber); color: var(--evergreen-900);
    display:flex; align-items:center; justify-content:center;
    font-weight:600; font-size:16px; flex-shrink:0;
}
.brand-name{ font-size:18px; font-weight:600; letter-spacing:.2px; color:#F6F1E7; white-space:nowrap; }

.nav-section-label{
    font-size:11px; color:#8FAE9E; text-transform:uppercase;
    letter-spacing:1px; margin:18px 12px 8px;
}
.sidebar-menu{ list-style:none; margin:0; padding:0; }
.sidebar-menu li{ margin-bottom:2px; }
.sidebar-menu a{
    position:relative; display:flex; align-items:center; gap:13px;
    padding:10px 12px; border-radius: var(--radius-sm);
    color:#C6D8CD; font-size:14px; font-weight:500;
    transition: background .18s ease, color .18s ease;
}
.sidebar-menu a i{ width:18px; text-align:center; font-size:14px; }
.sidebar-menu a:hover{ background: rgba(255,255,255,0.06); color:#F6F1E7; }
.sidebar-menu a.active{ background: rgba(193,121,31,0.16); color:#F6F1E7; }
.sidebar-menu a.active::before{
    content:""; position:absolute; left:-18px; top:8px; bottom:8px;
    width:3px; border-radius:3px; background: var(--amber);
}
.sidebar.collapsed .sidebar-menu a.active::before{ left:-12px; }

.sidebar-foot{
    margin-top:auto; display:flex; align-items:center; gap:10px;
    padding:12px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.05);
}
.sidebar-foot i{ color:#8FAE9E; }
.sidebar-foot span{ font-size:12.5px; color:#B7CBBE; line-height:1.3; }

/* ============================================================
   MAIN / TOPBAR
   ============================================================ */
.main-content{ margin-left:252px; min-height:100vh; transition: margin-left .25s ease; }
.main-content.expanded{ margin-left:82px; }

.topbar{
    height:72px; background: var(--surface); border-bottom:1px solid var(--line);
    display:flex; align-items:center; justify-content:space-between;
    padding:0 28px; position:sticky; top:0; z-index:900;
}
.topbar-left{ display:flex; align-items:center; gap:16px; }
.sidebar-toggle{
    width:36px; height:36px; border-radius: var(--radius-sm);
    border:1px solid var(--line); background: var(--surface);
    display:flex; align-items:center; justify-content:center;
    color: var(--ink-soft); cursor:pointer;
}
.sidebar-toggle:hover{ background: var(--paper); }
.breadcrumb-trail{ font-size:12.5px; color: var(--muted); }
.breadcrumb-trail strong{ color: var(--ink); font-weight:600; }

.admin-profile{ display:flex; align-items:center; gap:11px; }
.profile-avatar{
    width:38px; height:38px; border-radius:50%;
    background: var(--green-soft); color: var(--evergreen);
    display:flex; align-items:center; justify-content:center;
    font-weight:600; font-size:13px;
}
.profile-text strong{ display:block; font-size:13.5px; font-weight:600; }
.profile-text span{ font-size:11.5px; color: var(--muted); }

/* ============================================================
   PAGE CONTENT
   ============================================================ */
.page-wrap{ padding:30px 28px 60px; max-width:900px; }

.page-head{
    display:flex; align-items:flex-end; justify-content:space-between;
    padding-bottom:20px; margin-bottom:24px; border-bottom:1px solid var(--line);
    flex-wrap:wrap; gap:14px;
}
h1.page-title{ font-size:25px; font-weight:500; margin:0 0 6px; color: var(--evergreen-900); }
.page-sub{ margin:0; color: var(--muted); font-size:13.5px; }

.btn-ghost{
    display:inline-flex; align-items:center; gap:8px;
    border:1px solid var(--line); background: var(--surface);
    color: var(--ink-soft); padding:10px 16px; border-radius:10px;
    font-size:13px; font-weight:600;
}
.btn-ghost:hover{ background: var(--paper); }

/* ---- Alert ---- */
.alert-panel{
    border:none; border-left:3px solid; border-radius: var(--radius-sm);
    padding:13px 16px; font-size:13.5px; font-weight:500; margin-bottom:22px;
}
.alert-panel.success{ background: var(--green-soft); color: var(--evergreen-deep); border-color: var(--evergreen); }
.alert-panel.danger{ background: var(--rose-soft); color: var(--rose); border-color: var(--rose); }
.alert-panel.warning{ background: var(--amber-soft); color: var(--amber); border-color: var(--amber); }

/* ---- Form panels ---- */
.panel{
    background: var(--surface); border:1px solid var(--line);
    border-radius: var(--radius-lg); box-shadow: var(--shadow-card);
    padding:24px; margin-bottom:22px;
}
.panel-title{ font-size:15px; font-weight:600; color: var(--ink); margin-bottom:4px; }
.panel-sub{ font-size:12px; color: var(--muted); margin-bottom:18px; }

.field-label{
    font-size:11.5px; font-weight:600; text-transform:uppercase;
    letter-spacing:.4px; color: var(--ink-soft); margin-bottom:7px; display:block;
}
.field-label .req{ color: var(--rose); }
.field-hint{ font-size:11.5px; color: var(--muted); margin-top:5px; }

.form-control, .form-select{
    border:1.5px solid var(--line); border-radius:10px;
    padding:10px 13px; font-size:13.5px; color: var(--ink);
    background: var(--surface);
}
.form-control:focus, .form-select:focus{
    border-color: var(--evergreen); box-shadow: 0 0 0 3px rgba(31,111,84,0.1);
}
textarea.form-control{ resize:vertical; }

.price-input-wrap{ position:relative; }
.price-input-wrap .currency{
    position:absolute; left:13px; top:50%; transform:translateY(-50%);
    color: var(--muted); font-size:13px; pointer-events:none;
}
.price-input-wrap input{ padding-left:26px; }

.discount-preview{
    display:inline-flex; align-items:center; gap:6px;
    background: var(--amber-soft); color: var(--amber);
    font-size:12px; font-weight:600; padding:5px 11px; border-radius:20px;
    margin-top:8px;
}

/* ---- Upload boxes ---- */
.upload-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.upload-box{
    position:relative; border:1.5px dashed var(--line); border-radius: var(--radius-md);
    background: var(--paper); padding:16px; text-align:center;
    cursor:pointer; transition: border-color .15s ease, background .15s ease;
    min-height:160px; display:flex; flex-direction:column; align-items:center; justify-content:center;
    overflow:hidden;
}
.upload-box:hover{ border-color: var(--evergreen); }
.upload-box.required{ border-color: rgba(193,121,31,0.5); }
.upload-box.has-image{ border-style:solid; border-color: var(--evergreen); padding:0; }
.upload-box input[type=file]{
    position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
}
.upload-box .upload-icon{
    width:36px; height:36px; border-radius:10px; background: var(--surface);
    color: var(--evergreen); display:flex; align-items:center; justify-content:center;
    font-size:14px; margin-bottom:8px; border:1px solid var(--line);
}
.upload-box .upload-label{ font-size:12.5px; font-weight:600; color: var(--ink-soft); }
.upload-box .upload-tag{ font-size:10.5px; color: var(--muted); margin-top:3px; }
.upload-box .upload-tag.tag-req{ color: var(--amber); font-weight:600; }
.upload-box img.preview-img{ width:100%; height:160px; object-fit:cover; display:block; }
.upload-box .remove-badge{
    position:absolute; top:8px; right:8px; z-index:2;
    width:24px; height:24px; border-radius:50%;
    background: rgba(27,31,28,0.65); color:#fff;
    display:flex; align-items:center; justify-content:center; font-size:11px;
}
.upload-box .slot-tag{
    position:absolute; bottom:8px; left:8px; z-index:2;
    background: rgba(27,31,28,0.65); color:#fff;
    font-size:10px; font-weight:600; padding:3px 8px; border-radius:20px;
}

/* ---- Star select preview ---- */
.rating-preview{ color: var(--amber); font-size:15px; letter-spacing:2px; margin-top:8px; }

/* ---- Sticky action bar ---- */
.action-bar{
    position:sticky; bottom:0; background: var(--surface);
    border:1px solid var(--line); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card); padding:18px 22px;
    display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;
}
.action-bar-note{ font-size:12px; color: var(--muted); max-width:280px; }
.action-buttons{ display:flex; gap:10px; flex-wrap:wrap; }

.btn-publish{
    display:inline-flex; align-items:center; gap:9px;
    border:none; border-radius:10px; padding:12px 20px;
    font-size:13.5px; font-weight:600; color:#fff; cursor:pointer;
    transition: filter .15s ease;
}
.btn-publish:hover{ filter:brightness(0.94); }
.btn-publish.amber{ background: var(--amber); }
.btn-publish.evergreen{ background: var(--evergreen); }

@media (max-width: 768px){
    .sidebar{ left:-260px; }
    .sidebar.mobile-open{ left:0; box-shadow: 20px 0 40px rgba(0,0,0,0.15); }
    .main-content, .main-content.expanded{ margin-left:0; }
    .page-wrap{ padding:20px; }
    .upload-grid{ grid-template-columns:1fr; }
    .action-bar{ flex-direction:column; align-items:stretch; }
    .action-buttons{ flex-direction:column; }
}
</style>
</head>

<body>

<!-- ================= SIDEBAR ================= -->
<div class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark">N</div>
        <div class="brand-name">Nexora</div>
    </div>

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
        <li><a href="settings.php"><i class="fa-solid fa-gear"></i><span class="nav-label">Settings</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span class="nav-label">Log out</span></a></li>
    </ul>

    <div class="sidebar-foot">
        <i class="fa-solid fa-circle-info"></i>
        <span>Store synced<br>2 minutes ago</span>
    </div>
</div>

<!-- ================= MAIN ================= -->
<div class="main-content" id="mainContent">

    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="breadcrumb-trail">Nexora / Products / <strong>Add product</strong></div>
        </div>
        <div class="admin-profile">
            <div class="profile-avatar">AD</div>
            <div class="profile-text">
                <strong>Admin</strong>
                <span>Administrator</span>
            </div>
        </div>
    </div>

    <div class="page-wrap">

        <div class="page-head">
            <div>
                <h1 class="page-title">Add a new product</h1>
                <p class="page-sub">Publish directly to New Arrivals or Popular This Week.</p>
            </div>
            <a href="products.php" class="btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to products</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-panel <?= htmlspecialchars($message_type) ?>">
                <?= $message /* built server-side above, not raw user input */ ?>
            </div>
        <?php endif; ?>

        <form action="add_products.php" method="POST" enctype="multipart/form-data" id="productForm">

            <!-- BASIC DETAILS -->
            <div class="panel">
                <div class="panel-title">Basic details</div>
                <div class="panel-sub">The name and description customers will see on the product page.</div>

                <div class="mb-3">
                    <label class="field-label">Product title <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g., Hair Clipper Pro">
                </div>

                <?php if ($has_categories): ?>
                <div class="mb-3">
                    <label class="field-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">— Uncategorized —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-hint">Which section this product appears under in the storefront's Shop menu.</div>
                </div>
                <?php endif; ?>

                <div class="mb-1">
                    <label class="field-label">Description <span class="req">*</span></label>
                    <textarea name="description" rows="4" class="form-control" required placeholder="Key specs, materials, what's in the box…"></textarea>
                </div>
            </div>

            <!-- PRICING -->
            <div class="panel">
                <div class="panel-title">Pricing</div>
                <div class="panel-sub">Set the selling price and, optionally, a strike-through price to show a discount.</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="field-label">Current price <span class="req">*</span></label>
                        <div class="price-input-wrap">
                            <span class="currency">$</span>
                            <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" required placeholder="83.00">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Old price</label>
                        <div class="price-input-wrap">
                            <span class="currency">$</span>
                            <input type="number" step="0.01" min="0" name="old_price" id="old_price" class="form-control" placeholder="90.00">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Discount badge</label>
                        <input type="text" name="discount_badge" class="form-control" placeholder="e.g., 7% off">
                    </div>
                </div>
                <div id="discountPreview"></div>
            </div>

            <!-- SELLING METHOD -->
            <div class="panel">
                <div class="panel-title">Selling method</div>
                <div class="panel-sub">Leave this empty for a normal "Add to Cart" product. Fill it in only if this specific product should send customers to Amazon instead.</div>

                <label class="field-label">Amazon product link (optional)</label>
                <input type="url" name="amazon_link" class="form-control" placeholder="https://www.amazon.com/dp/XXXXXXXXXX">
                <div class="field-hint">
                    Empty → this product shows the normal <strong>Add to Cart</strong> button and opens your product page.<br>
                    Filled in → this product shows a <strong>Buy on Amazon</strong> button that opens this link in a new tab instead.
                </div>
            </div>

            <!-- RATING -->
            <div class="panel">
                <div class="panel-title">Rating</div>
                <div class="panel-sub">Shown as stars on the product card.</div>

                <label class="field-label">Rating stars</label>
                <select name="rating_stars" id="rating_stars" class="form-select" style="max-width:260px;">
                    <option value="5">5 Stars — Excellent</option>
                    <option value="4">4 Stars — Very good</option>
                    <option value="3">3 Stars — Good</option>
                </select>
                <div class="rating-preview" id="ratingPreview">★★★★★</div>
            </div>

            <!-- PHOTOS -->
            <div class="panel">
                <div class="panel-title">Product photos</div>
                <div class="panel-sub">JPG, JPEG, PNG, WEBP, GIF, BMP, or AVIF. Image 1 is required and used as the main cover.</div>

                <div class="upload-grid">

                    <label class="upload-box required" id="box_1">
                        <input type="file" name="image_1" accept="image/*" required data-slot="1">
                        <span class="slot-tag">Cover</span>
                        <div class="upload-body">
                            <div class="upload-icon"><i class="fa-solid fa-image"></i></div>
                            <div class="upload-label">Main cover image</div>
                            <div class="upload-tag tag-req">Required</div>
                        </div>
                    </label>

                    <label class="upload-box" id="box_2">
                        <input type="file" name="image_2" accept="image/*" data-slot="2">
                        <span class="slot-tag">Hover</span>
                        <div class="upload-body">
                            <div class="upload-icon"><i class="fa-solid fa-arrows-rotate"></i></div>
                            <div class="upload-label">Hover-switch image</div>
                            <div class="upload-tag">Optional</div>
                        </div>
                    </label>

                    <label class="upload-box" id="box_3">
                        <input type="file" name="image_3" accept="image/*" data-slot="3">
                        <span class="slot-tag">Gallery</span>
                        <div class="upload-body">
                            <div class="upload-icon"><i class="fa-solid fa-images"></i></div>
                            <div class="upload-label">Gallery photo 3</div>
                            <div class="upload-tag">Optional</div>
                        </div>
                    </label>

                    <label class="upload-box" id="box_4">
                        <input type="file" name="image_4" accept="image/*" data-slot="4">
                        <span class="slot-tag">Gallery</span>
                        <div class="upload-body">
                            <div class="upload-icon"><i class="fa-solid fa-images"></i></div>
                            <div class="upload-label">Gallery photo 4</div>
                            <div class="upload-tag">Optional</div>
                        </div>
                    </label>

                </div>
            </div>

            <!-- ACTIONS -->
            <div class="action-bar">
                <div class="action-bar-note">
                    Choose where this product goes live. You can move it later from the Products list.
                </div>
                <div class="action-buttons">
                    <button type="submit" name="submit_new_arrival" class="btn-publish amber">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Publish to New Arrivals
                    </button>
                    <button type="submit" name="submit_popular" class="btn-publish evergreen">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Publish to Popular This Week
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
// ---- Sidebar collapse / mobile toggle ----
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

// ---- Image preview on upload boxes ----
document.querySelectorAll('.upload-box input[type=file]').forEach(input => {
    input.addEventListener('change', function () {
        const box = this.closest('.upload-box');
        const slot = this.dataset.slot;
        const existingImg = box.querySelector('img.preview-img');
        if (existingImg) existingImg.remove();

        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-img';
                box.classList.add('has-image');
                box.querySelector('.upload-body')?.remove();
                box.insertBefore(img, box.firstChild);

                const removeBtn = document.createElement('span');
                removeBtn.className = 'remove-badge';
                removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                removeBtn.addEventListener('click', ev => {
                    ev.preventDefault();
                    input.value = '';
                    box.classList.remove('has-image');
                    img.remove();
                    removeBtn.remove();
                });
                box.appendChild(removeBtn);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
});

// ---- Rating preview ----
const ratingSelect = document.getElementById('rating_stars');
const ratingPreview = document.getElementById('ratingPreview');
function updateRatingPreview() {
    const n = parseInt(ratingSelect.value, 10);
    ratingPreview.textContent = '★'.repeat(n) + '☆'.repeat(5 - n);
}
ratingSelect.addEventListener('change', updateRatingPreview);
updateRatingPreview();

// ---- Discount preview ----
const priceInput = document.getElementById('price');
const oldPriceInput = document.getElementById('old_price');
const discountPreview = document.getElementById('discountPreview');
function updateDiscountPreview() {
    const price = parseFloat(priceInput.value);
    const oldPrice = parseFloat(oldPriceInput.value);
    if (!isNaN(price) && !isNaN(oldPrice) && oldPrice > price) {
        const pct = Math.round(((oldPrice - price) / oldPrice) * 100);
        discountPreview.innerHTML = `<span class="discount-preview"><i class="fa-solid fa-tag"></i> ${pct}% cheaper than old price</span>`;
    } else {
        discountPreview.innerHTML = '';
    }
}
priceInput.addEventListener('input', updateDiscountPreview);
oldPriceInput.addEventListener('input', updateDiscountPreview);

// ---- Prevent double submit (safe version — doesn't touch button name before submit) ----
const productForm = document.getElementById('productForm');
productForm.addEventListener('submit', function () {
    setTimeout(() => {
        document.querySelectorAll('.btn-publish').forEach(b => b.disabled = true);
    }, 0);
});
</script>

</body>
</html>