<style>
    /* ==========================================================================
       1. NAVBAR & DROPDOWN STYLES
       ========================================================================== */
    .announcement-bar {
        background-color: #0d47a1;
        font-size: 13px;
        letter-spacing: 1px;
    }

    .nav-link {
        color: #333 !important;
        font-weight: 500;
    }

    .nav-link:hover {
        color: #0d47a1 !important;
    }

    /* Dropdown Speed ko 0.3s se 0.15s kiya taake tezi se aaye */
    .navbar-nav .dropdown-menu {
        display: block;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-20px);
        transition: all 0.15s ease-in-out; /* Tez sliding effect */
        border: 1px solid #eee;
        box-shadow: 0px 4px 10px rgba(0,0,0,0.05);
    }
    
    .navbar-nav .nav-item.dropdown:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    /* Admin-only "Dashboard" pill — only rendered when an admin is logged in */
    .admin-dash-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #0d47a1;
        color: #fff !important;
        font-size: 12.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 7px 14px;
        border-radius: 20px;
        text-decoration: none !important;
        transition: background .15s ease;
    }
    .admin-dash-link:hover {
        background: #08357e;
        color: #fff !important;
    }

    /* ==========================================================================
       MOBILE MENU — search bar, full-width link rows, bottom tab bar
       ========================================================================== */
    .mobile-search-form{
        position: relative;
        margin: 10px 0 16px;
    }
    .mobile-search-form input{
        width: 100%;
        border: 1px solid #e5e5e5;
        background: #f7f7f7;
        border-radius: 8px;
        padding: 12px 14px 12px 40px;
        font-size: 14px;
        outline: none;
    }
    .mobile-search-form input:focus{ border-color: #0d47a1; background: #fff; }
    .mobile-search-form i{
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: #999; font-size: 14px; pointer-events: none;
    }

    @media (max-width: 991.98px){
        #navbarNav .navbar-nav{ align-items: stretch !important; gap: 0 !important; }
        #navbarNav .nav-item{ border-bottom: 1px solid #eee; }
        #navbarNav .nav-item:last-child{ border-bottom: none; }
        #navbarNav .nav-link{
            padding: 14px 4px !important;
            font-size: 16px;
            font-weight: 600;
        }
        /* Mobile: no Shop dropdown at all — "Shop" is a plain link straight to shop-now.php */
        #navbarNav .dropdown-menu{ display: none !important; }
        #navbarNav .dropdown-toggle::after{ display: none; }
        #navbarNav li.nav-item.d-flex{ display: none !important; } /* icon row replaced by bottom tab bar on mobile */
    }

    .mobile-bottom-nav{
        position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030;
        background: #fff; border-top: 1px solid #eee;
        display: flex; align-items: stretch; justify-content: space-around;
        box-shadow: 0 -4px 14px rgba(0,0,0,.06);
        padding-bottom: env(safe-area-inset-bottom, 0px);
    }
    .mobile-bottom-item{
        flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 4px; padding: 9px 4px 8px; color: #555; text-decoration: none;
        font-size: 11px; font-weight: 600; border: none; background: none;
    }
    .mobile-bottom-item i{ font-size: 18px; }
    .mobile-bottom-item:hover{ color: #0d47a1; }

    @media (max-width: 991.98px){
        body{ padding-bottom: 64px; }
    }
    @media (min-width: 992px){
        .mobile-bottom-nav{ display: none !important; }
    }

    /* ==========================================================================
       2. HERO SECTION TEXT & BUTTON ANIMATIONS (Top To Bottom)
       ========================================================================== */
    /* Text ko top se niche lane ki animation */
    @keyframes slideDownText {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Button ko thoda delay ke sath upar se niche lane ki animation */
    @keyframes slideDownBtn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Carousel active hote hi text aur button par animation apply hogi */
    .carousel-item.active .animate-text {
        animation: slideDownText 0.8s ease-out forwards;
    }

    .carousel-item.active .animate-btn {
        animation: slideDownBtn 0.8s ease-out 0.3s forwards; /* 0.3s delay taake heading ke baad aaye */
        opacity: 0; /* Shuru me hidden rahega */
    }

    /* ==========================================================================
       3. SHOP BY CATEGORIES ANIMATIONS (Left To Right & Image Zoom)
       ========================================================================== */
    /* Left se right slide hone wali custom keyframe animation */
    @keyframes slideFromLeft {
        from {
            opacity: 0;
            transform: translateX(-150px); /* 150px left se start hoga */
        }
        to {
            opacity: 1;
            transform: translateX(0); /* Apni normal position par ruk jayega */
        }
    }

    /* Yeh class apply hote hi category grid 1.2 second me sirf ek baar chalegi */
   /* ==========================================================================
       3. SHOP BY CATEGORIES ANIMATIONS (Scroll Based & Image Zoom)
       ========================================================================== */
    /* Shuru me grid hidden rahega aur thoda left me hoga */
    .reveal-left {
        opacity: 0;
        transform: translateX(-150px);
        transition: all 0.45s cubic-bezier(0.25, 1, 0.5, 1); /* Tez, smooth entry */
    }

    /* Jab user scroll karke yahan pahonchega toh yeh class active hogi */
    .reveal-left.active {
        opacity: 1;
        transform: translateX(0);
    }

    /* Product Cards Custom Styling */
.product-card {
    transition: all 0.3s ease-in-out;
}

.text-muted-hover {
    color: #4a4a4a;
    transition: color 0.2s ease;
}

.text-muted-hover:hover {
    color: #0d47a1 !important; /* Title hover karne par branded blue hoga */
}

/* Image zoom inside product card on hover */
.product-card:hover .product-img {
    transform: scale(1.05);
}

/* Subtle card container lifting effect */
.product-card:hover {
    transform: translateY(-5px);
}
/* Tez aur zoor dar entry ke liye custom keyframe */
@keyframes aggressiveSlideLeft {
    0% {
        opacity: 0;
        transform: translateX(-100%); /* Poora screen ke bahar left se start hoga */
    }
    60% {
        transform: translateX(5px); /* Thoda sa aage jhatka marega */
    }
    100% {
        opacity: 1;
        transform: translateX(0); /* Fixed position */
    }
}

.reveal-features-left-direct {
    opacity: 0;
    /* Speed ko 0.8s se 0.5s kiya aur timing curve ko aggressive jhatke ke liye change kiya */
    animation: aggressiveSlideLeft 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) 0.2s forwards;
}

/* Responsive dividers ke liye choti support class */
@media (min-width: 768px) {
    .cs-border {
        border-left: 1px solid rgba(255,255,255,0.2) !important;
    }
}
</style>

<div class="announcement-bar text-white text-center py-2 fw-bold text-uppercase">
    Free Shipping On Orders Over $70
</div>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3 text-uppercase tracking-wider" href="index.php" style="letter-spacing: 2px;">
           ZENTURY
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- Mobile-only search bar, shown at the top of the slide-out menu -->
            <form action="shop-now.php" method="GET" class="d-lg-none mobile-search-form">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" id="mobileSearchInput" placeholder="Search products…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </form>

            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Fashion</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="shop-now.php" id="shopDropdown">
                        Shop
                    </a>
                    <ul class="dropdown-menu p-3" aria-labelledby="shopDropdown" style="min-width: 200px;">
                        <?php
                        // Pull categories live from the database (parent page already
                        // included config/db.php before including this header).
                        $__cat_check = isset($conn) ? $conn->query("SHOW TABLES LIKE 'categories'") : null;
                        if ($__cat_check && $__cat_check->num_rows > 0) {
                            $__cats_res = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
                            if ($__cats_res && $__cats_res->num_rows > 0) {
                                while ($__cat = $__cats_res->fetch_assoc()) {
                                    echo '<li><a class="dropdown-item py-2" href="shop.php?category=' . (int) $__cat['id'] . '">' . htmlspecialchars($__cat['name']) . '</a></li>';
                                }
                            } else {
                                echo '<li><span class="dropdown-item-text text-muted small py-2">No categories yet</span></li>';
                            }
                        }
                        ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 fw-bold" href="shop-now.php">View All Products</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>

                <?php if (!empty($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin'): ?>
                <li class="nav-item ms-2">
                    <a href="admin/index.php" class="admin-dash-link">
                        <i class="fa-solid fa-gauge"></i> Dashboard
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item d-flex gap-3 ms-3 text-secondary fs-5 align-items-center">
                    <a href="#" class="text-secondary"><i class="fa-solid fa-magnifying-glass"></i></a>
                    <a href="wishlist.php" class="text-secondary position-relative">
                        <i class="fa-regular fa-heart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary wishlist-count" style="font-size: 10px;"><?= isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0 ?></span>
                    </a>
                    <?php
                        $is_admin_logged_in = !empty($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin';
                        $account_link = $is_admin_logged_in ? 'admin/index.php' : 'admin/login.php';
                        $account_title = $is_admin_logged_in ? 'Go to Dashboard' : 'Admin sign in';
                    ?>
                    <a href="<?= htmlspecialchars($account_link) ?>" class="text-secondary" title="<?= htmlspecialchars($account_title) ?>"><i class="fa-regular fa-user"></i></a>
                    <a href="cart.php" class="text-secondary position-relative">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size: 10px;">0</span>
                    </a>
                </li>

                <!-- Mobile-only: plain text login row, matching the reference site's slide-out menu -->
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="<?= htmlspecialchars($account_link) ?>">Login / Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ================= MOBILE BOTTOM TAB BAR ================= -->
<nav class="mobile-bottom-nav d-lg-none">
    <a href="shop-now.php" class="mobile-bottom-item">
        <i class="fa-solid fa-store"></i>
        <span>Shop</span>
    </a>
    <a href="wishlist.php" class="mobile-bottom-item position-relative">
        <i class="fa-regular fa-heart"></i>
        <span class="position-absolute badge rounded-pill bg-secondary wishlist-count" style="top:2px; right:18px; font-size:9px;"><?= isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0 ?></span>
        <span>Wishlist</span>
    </a>
    <button type="button" class="mobile-bottom-item mobile-bottom-search-btn" onclick="openMobileSearch()">
        <i class="fa-solid fa-magnifying-glass"></i>
        <span>Search</span>
    </button>
    <a href="cart.php" class="mobile-bottom-item position-relative">
        <i class="fa-solid fa-basket-shopping"></i>
        <span class="position-absolute badge rounded-pill bg-primary" style="top:2px; right:18px; font-size:9px;">0</span>
        <span>Cart</span>
    </a>
</nav>

<script>
function openMobileSearch() {
    const collapseEl = document.getElementById('navbarNav');
    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
    bsCollapse.show();
    setTimeout(() => {
        const input = document.getElementById('mobileSearchInput');
        if (input) input.focus();
    }, 250);
}
</script>
