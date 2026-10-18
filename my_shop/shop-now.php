<?php
// ==========================================================
// Nexora — Shop Now page
// Tabs: All Products / New Arrivals / Popular This Week
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php';

$category = $_GET['category'] ?? 'all';
$search   = trim($_GET['search'] ?? '');

if (!in_array($category, ['all', 'new', 'popular'], true)) {
    $category = 'all';
}

$total_products = 0;
$total_new_arrivals = 0;
$total_popular = 0;

if (isset($conn) && !$conn->connect_error) {

    $count_query = "SELECT COUNT(*) AS total FROM products";
    $count_result = $conn->query($count_query);
    if ($count_result) {
        $total_products = (int) $count_result->fetch_assoc()['total'];
    }

    $new_count_result = $conn->query("SELECT COUNT(*) AS total FROM products WHERE is_new_arrival = 1");
    if ($new_count_result) {
        $total_new_arrivals = (int) $new_count_result->fetch_assoc()['total'];
    }

    // Fixed: this used to count ALL products instead of only is_popular = 1,
    // so the "X Products" label didn't match what the tab actually showed.
    $popular_count_result = $conn->query("SELECT COUNT(*) AS total FROM products WHERE is_popular = 1");
    if ($popular_count_result) {
        $total_popular = (int) $popular_count_result->fetch_assoc()['total'];
    }
}

/**
 * Renders one product card. Kept as a function so All/New/Popular
 * render identically instead of three copy-pasted blocks drifting
 * out of sync over time.
 */
function render_shop_product_card(array $product): void
{
    $primary_img   = htmlspecialchars($product['image_1']);
    $secondary_img = !empty($product['image_2']) ? htmlspecialchars($product['image_2']) : $primary_img;
    $stars         = (int) $product['rating_stars'];
    $is_wishlisted = in_array((int) $product['id'], $_SESSION['wishlist'] ?? [], true);
    ?>
    <div class="col">
        <div class="shop-product-card">

            <?php if (!empty($product['discount_badge'])): ?>
                <span class="shop-discount"><?= htmlspecialchars($product['discount_badge']) ?></span>
            <?php endif; ?>

            <button type="button"
                    class="shop-wishlist wishlist-btn <?= $is_wishlisted ? 'active' : '' ?>"
                    data-id="<?= (int) $product['id'] ?>" data-removable="0"
                    aria-label="Add to wishlist">
                <i class="fa-<?= $is_wishlisted ? 'solid' : 'regular' ?> fa-heart"></i>
            </button>

            <div class="shop-product-image">
                <img src="<?= $primary_img ?>" class="img-primary" alt="<?= htmlspecialchars($product['name']) ?>">
                <img src="<?= $secondary_img ?>" class="img-secondary" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <div class="shop-product-info">
                <div class="shop-product-name"><?= htmlspecialchars($product['name']) ?></div>

                <div class="shop-rating">
                    <?php for ($s = 1; $s <= 5; $s++) echo $s <= $stars ? '★' : '☆'; ?>
                </div>

                <div class="shop-price">
                    <?php if (!empty($product['old_price']) && $product['old_price'] > 0): ?>
                        <span class="old-price">$<?= number_format($product['old_price'], 2) ?></span>
                    <?php endif; ?>
                    $<?= number_format($product['price'], 2) ?>
                </div>

                <?php if (!empty($product['amazon_link'])): ?>
                    <a href="<?= htmlspecialchars($product['amazon_link']) ?>" target="_blank" rel="nofollow sponsored noopener" class="shop-cart-btn amazon">
                        <i class="fa-brands fa-amazon"></i> Buy on Amazon
                    </a>
                <?php else: ?>
                    <a href="product-detail.php?id=<?= (int) $product['id'] ?>" class="shop-cart-btn">
                        <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop All Products — Nexora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .shop-page { background: #fff; min-height: 100vh; }

        .shop-header { padding: 60px 20px 35px; text-align: center; }
        .shop-header span { letter-spacing: 2px; font-size: 13px; font-weight: 700; color: #f5a623; text-transform: uppercase; }
        .shop-header h1 { color: #0d47a1; font-size: 38px; font-weight: 700; margin-top: 8px; }
        .shop-header p { color: #777; margin-top: 10px; }

        .shop-tabs { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 45px; }
        .shop-tab {
            border: 1px solid #e1e1e1; background: #fff; color: #333;
            padding: 11px 24px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s;
        }
        .shop-tab:hover { background: #0d47a1; color: #fff; border-color: #0d47a1; }
        .shop-tab.active { background: #0d47a1; color: #fff; border-color: #0d47a1; }

        .shop-product-card { height: 100%; border: none; background: #fff; position: relative; }

        .shop-product-image { height: 330px; overflow: hidden; position: relative; background: #f5f5f5; }
        .shop-product-image img { width: 100%; height: 100%; object-fit: cover; display: block; transition: 0.5s ease; }
        .shop-product-image .img-secondary { position: absolute; top: 0; left: 0; opacity: 0; }
        .shop-product-card:hover .img-primary { opacity: 0; }
        .shop-product-card:hover .img-secondary { opacity: 1; }

        .shop-product-info { padding: 15px 5px 0; text-align: left; }
        .shop-product-name { color: #666; font-size: 14px; line-height: 1.4; min-height: 40px; margin-bottom: 6px; }
        .shop-rating { color: #f5a623; font-size: 12px; margin-bottom: 8px; letter-spacing: 1px; }
        .shop-price { font-size: 16px; font-weight: 700; color: #222; }
        .old-price { color: #999; font-size: 13px; margin-right: 8px; text-decoration: line-through; }

        .shop-discount { position: absolute; top: 10px; left: 10px; background: #0d47a1; color: #fff; padding: 6px 10px; font-size: 11px; font-weight: 700; z-index: 5; }

        /* Wishlist button — reuses the site-wide .wishlist-btn behavior (hover reveal,
           active/red state, AJAX toggle via wishlist.js) with this page's own sizing. */
        .shop-wishlist.wishlist-btn {
            position: absolute; top: 10px; right: 10px; width: 38px; height: 38px;
            border: none; background: #fff; border-radius: 50%; z-index: 5;
            color: #555; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .shop-wishlist.wishlist-btn:hover { color: #0d47a1; transform: scale(1.05); background: #fff; }
        .shop-wishlist.wishlist-btn.active { color: #e63950; }

        .shop-cart-btn {
            margin-top: 12px; width: 100%; background: #0d47a1; color: #fff; border: none;
            border-radius: 0; padding: 10px; font-size: 13px; font-weight: 600;
            text-decoration: none; display: block; text-align: center;
        }
        .shop-cart-btn:hover { background: #083579; color: #fff; }
        .shop-cart-btn.amazon { background: #E47911; }
        .shop-cart-btn.amazon:hover { background: #B4590A; }

        .product-section { margin-bottom: 70px; scroll-margin-top: 30px; }
        .product-section-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; }
        .product-section-title h2 { color: #0d47a1; font-size: 24px; font-weight: 700; margin: 0; }
        .product-count { color: #888; font-size: 13px; }

        @media (max-width: 767px) {
            .shop-header { padding: 40px 15px 25px; }
            .shop-header h1 { font-size: 30px; }
            .shop-product-image { height: 230px; }
            .shop-product-info { padding-left: 2px; padding-right: 2px; }
            .shop-tabs { gap: 7px; }
            .shop-tab { padding: 9px 14px; font-size: 12px; }
        }
    </style>

</head>

<body>

<?php include 'includes/header.php'; ?>

<main class="shop-page">

    <section class="shop-header">
        <span>Let's Shop</span>
        <h1>Shop All Products</h1>
        <p>Discover our latest products and collections</p>
    </section>

    <div class="container">

        <?php if ($search !== ''): ?>
        <section id="search-results" class="product-section">
            <div class="product-section-title">
                <h2>Results for “<?= htmlspecialchars($search) ?>”</h2>
                <a href="shop-now.php" class="product-count text-decoration-none">Clear search ✕</a>
            </div>

            <div class="row row-cols-2 row-cols-lg-4 g-4">
                <?php
                if (isset($conn) && !$conn->connect_error) {
                    $like = '%' . $search . '%';
                    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC");
                    $stmt->bind_param("ss", $like, $like);
                    $stmt->execute();
                    $search_result = $stmt->get_result();
                    if ($search_result && $search_result->num_rows > 0) {
                        while ($product = $search_result->fetch_assoc()) {
                            render_shop_product_card($product);
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5"><p class="text-muted">No products matched your search.</p></div>';
                    }
                    $stmt->close();
                }
                ?>
            </div>
        </section>
        <?php else: ?>

        <div class="shop-tabs">
            <a href="shop-now.php?category=all" class="shop-tab <?= $category === 'all' ? 'active' : '' ?>">All Products</a>
            <a href="shop-now.php?category=new" class="shop-tab <?= $category === 'new' ? 'active' : '' ?>">New Arrivals</a>
            <a href="shop-now.php?category=popular" class="shop-tab <?= $category === 'popular' ? 'active' : '' ?>">Popular This Week</a>
        </div>

        <?php if ($category === 'all'): ?>
        <section id="all-products" class="product-section">
            <div class="product-section-title">
                <h2>All Products</h2>
                <span class="product-count"><?= $total_products ?> Products</span>
            </div>

            <div class="row row-cols-2 row-cols-lg-4 g-4">
                <?php
                if (isset($conn) && !$conn->connect_error) {
                    $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
                    if ($result && $result->num_rows > 0) {
                        while ($product = $result->fetch_assoc()) {
                            render_shop_product_card($product);
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5"><p class="text-muted">No products found.</p></div>';
                    }
                }
                ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($category === 'new'): ?>
        <section id="new-arrivals" class="product-section">
            <div class="product-section-title">
                <h2>New Arrivals</h2>
                <span class="product-count"><?= $total_new_arrivals ?> Products</span>
            </div>

            <div class="row row-cols-2 row-cols-lg-4 g-4">
                <?php
                if (isset($conn) && !$conn->connect_error) {
                    $new_result = $conn->query("SELECT * FROM products WHERE is_new_arrival = 1 ORDER BY id DESC");
                    if ($new_result && $new_result->num_rows > 0) {
                        while ($product = $new_result->fetch_assoc()) {
                            render_shop_product_card($product);
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5"><p class="text-muted">No new arrival products found.</p></div>';
                    }
                }
                ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($category === 'popular'): ?>
        <section id="popular-products" class="product-section">
            <div class="product-section-title">
                <h2>Popular This Week</h2>
                <span class="product-count"><?= $total_popular ?> Products</span>
            </div>

            <div class="row row-cols-2 row-cols-lg-4 g-4">
                <?php
                if (isset($conn) && !$conn->connect_error) {
                    $popular_result = $conn->query("SELECT * FROM products WHERE is_popular = 1 ORDER BY id DESC");
                    if ($popular_result && $popular_result->num_rows > 0) {
                        while ($product = $popular_result->fetch_assoc()) {
                            render_shop_product_card($product);
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5"><p class="text-muted">No popular products found.</p></div>';
                    }
                }
                ?>
            </div>
        </section>
        <?php endif; ?>

        <?php endif; /* end search / tabs switch */ ?>

    </div>

</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/wishlist.js"></script>

</body>
</html>
