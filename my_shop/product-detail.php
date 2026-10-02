<?php
// ==========================================================
// Nexora — Product Detail page
// Schema used: products(id, name, price, old_price, discount_badge,
//              description, category_id, image_1..4, is_new_arrival,
//              is_popular, rating_stars, created_at)
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php';

/** @var mysqli $conn */
$product = null;

if (isset($_GET['id']) && isset($conn) && !$conn->connect_error) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $product ? htmlspecialchars($product['name']) . ' — Zentury' : 'Product not found — Zentury' ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">

<style>
/* ---- Product detail page specific styles ---- */
.pd-breadcrumb{ font-size:.82rem; color:#888; margin-bottom:24px; }
.pd-breadcrumb a{ color:#888; text-decoration:none; }
.pd-breadcrumb a:hover{ color:#0d47a1; }

.pd-gallery-main{
    width:100%; aspect-ratio:1/1; border:1px solid #eee; border-radius:4px;
    display:flex; align-items:center; justify-content:center; overflow:hidden;
    background:#fafafa; position:relative;
}
.pd-gallery-main img{ max-width:100%; max-height:100%; object-fit:contain; transition: opacity .25s ease; }

.pd-thumbs{ display:flex; gap:10px; margin-top:14px; }
.pd-thumb{
    width:70px; height:70px; border:1.5px solid #e5e5e5; border-radius:4px; overflow:hidden;
    cursor:pointer; background:#fafafa; display:flex; align-items:center; justify-content:center;
    transition: border-color .2s ease;
}
.pd-thumb img{ max-width:100%; max-height:100%; object-fit:contain; }
.pd-thumb.active{ border-color:#0d47a1; }

.pd-badge{
    display:inline-block; background:#0d47a1; color:#fff; font-weight:700; font-size:.75rem;
    padding:5px 10px; border-radius:0;
}

.pd-title{ font-size:1.7rem; font-weight:700; color:#1a237e; margin:16px 0 8px; }
.pd-rating{ color:#f5a623; font-size:.95rem; letter-spacing:1px; margin-bottom:14px; }
.pd-rating span{ color:#999; font-size:.8rem; letter-spacing:normal; margin-left:6px; }

.pd-price-row{ display:flex; align-items:baseline; gap:12px; margin-bottom:18px; }
.pd-price{ font-size:1.9rem; font-weight:700; color:#1a1a1a; }
.pd-old-price{ font-size:1.1rem; color:#999; text-decoration:line-through; }

.pd-desc{ color:#555; font-size:.92rem; line-height:1.7; margin-bottom:26px; }

.pd-qty{ display:flex; align-items:center; border:1px solid #ddd; border-radius:4px; width:fit-content; }
.pd-qty button{
    width:40px; height:44px; border:none; background:#fafafa; font-size:1.1rem; color:#333; cursor:pointer;
}
.pd-qty button:hover{ background:#f0f0f0; }
.pd-qty input{
    width:50px; height:44px; border:none; border-left:1px solid #ddd; border-right:1px solid #ddd;
    text-align:center; font-size:.95rem; outline:none;
}

.pd-actions{ display:flex; gap:12px; margin-top:22px; flex-wrap:wrap; }
.pd-add-cart-btn{
    background:#0d47a1; color:#fff; border:none; padding:14px 30px; font-weight:700;
    font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; border-radius:2px;
    display:inline-flex; align-items:center; gap:10px; transition: background .2s ease;
}
.pd-add-cart-btn:hover{ background:#08357e; color:#fff; }
.pd-amazon-btn{ background:#E47911; }
.pd-amazon-btn:hover{ background:#B4590A; color:#fff; }

/* Wishlist heart, sized to sit inline next to the Add to Cart button here
   (overrides the small circular card-overlay version from style.css) */
.pd-wishlist-btn.wishlist-btn{
    position: static;
    width: 52px;
    height: 52px;
    border-radius: 4px;
    border: 1.5px solid #e2e2e2;
    background: #fff;
    color: #555;
    font-size: 18px;
    box-shadow: none;
    opacity: 1;
    transform: none;
}
.pd-wishlist-btn.wishlist-btn:hover{ border-color: #e63950; color:#e63950; transform:none; }
.pd-wishlist-btn.wishlist-btn.active{ color:#e63950; border-color:#e63950; background:#fff5f6; }

.pd-back-link{
    display:inline-flex; align-items:center; gap:8px; color:#555; font-size:.85rem;
    text-decoration:none; margin-top:28px;
}
.pd-back-link:hover{ color:#0d47a1; }

.pd-meta-row{ display:flex; gap:24px; margin-top:26px; padding-top:20px; border-top:1px solid #eee; }
.pd-meta-item{ font-size:.8rem; color:#777; display:flex; align-items:center; gap:8px; }
.pd-meta-item i{ color:#0d47a1; }

.pd-not-found{ text-align:center; padding:100px 20px; }
.pd-not-found i{ font-size:48px; color:#ddd; margin-bottom:18px; }
</style>
</head>

<body>

<?php include 'includes/header.php'; ?>

<div class="container my-5">

    <?php if (!$product): ?>

        <!-- ================= NOT FOUND ================= -->
        <div class="pd-not-found">
            <i class="fa-solid fa-box-open d-block"></i>
            <h4 class="fw-bold mb-2">Product not found</h4>
            <p class="text-muted mb-4">This product may have been removed or the link is incorrect.</p>
            <a href="index.php" class="pd-add-cart-btn"><i class="fa-solid fa-arrow-left"></i> Back to shop</a>
        </div>

    <?php else:
        $stars = intval($product['rating_stars']);
        $images = array_filter([$product['image_1'], $product['image_2'], $product['image_3'], $product['image_4']]);
        $images = array_values($images);
        if (empty($images)) $images = [''];
    ?>

    <div class="pd-breadcrumb">
        <a href="index.php">Home</a> / <a href="index.php#shop-section">Shop</a> / <span class="text-dark"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="row g-5">

        <!-- ================= GALLERY ================= -->
        <div class="col-lg-6">
            <div class="pd-gallery-main">
                <img id="pdMainImage" src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <?php if (count($images) > 1): ?>
            <div class="pd-thumbs">
                <?php foreach ($images as $i => $img): ?>
                    <div class="pd-thumb <?= $i === 0 ? 'active' : '' ?>" onclick="pdSwapImage('<?= htmlspecialchars($img) ?>', this)">
                        <img src="<?= htmlspecialchars($img) ?>" alt="Thumbnail <?= $i + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ================= DETAILS ================= -->
        <div class="col-lg-6">

            <?php if (!empty($product['discount_badge'])): ?>
                <span class="pd-badge"><?= htmlspecialchars($product['discount_badge']) ?> OFF</span>
            <?php endif; ?>

            <h1 class="pd-title"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="pd-rating">
                <?php
                for ($s = 1; $s <= 5; $s++) {
                    echo $s <= $stars ? '★' : '☆';
                }
                ?>
                <span><?= $stars ?>.0 rating</span>
            </div>

            <div class="pd-price-row">
                <span class="pd-price">$<?= number_format((float) $product['price'], 2) ?></span>
                <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                    <span class="pd-old-price">$<?= number_format((float) $product['old_price'], 2) ?></span>
                <?php endif; ?>
            </div>

            <p class="pd-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

            <div class="pd-qty">
                <button type="button" onclick="pdChangeQty(-1)">−</button>
                <input type="text" id="pdQtyInput" value="1" readonly>
                <button type="button" onclick="pdChangeQty(1)">+</button>
            </div>

            <div class="pd-actions">
                <?php if (!empty($product['amazon_link'])): ?>
                    <a href="<?= htmlspecialchars($product['amazon_link']) ?>" target="_blank" rel="nofollow sponsored noopener" class="pd-add-cart-btn pd-amazon-btn">
                        <i class="fa-brands fa-amazon"></i> Buy on Amazon
                    </a>
                <?php else: ?>
                    <button type="button" class="pd-add-cart-btn" onclick="pdAddToCart(<?= (int) $product['id'] ?>)">
                        <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                    </button>
                <?php endif; ?>

                <button type="button"
                        class="pd-wishlist-btn wishlist-btn <?php echo in_array((int) $product['id'], $_SESSION['wishlist'] ?? [], true) ? 'active' : ''; ?>"
                        data-id="<?= (int) $product['id'] ?>" data-removable="0"
                        aria-label="Add to wishlist">
                    <i class="fa-<?php echo in_array((int) $product['id'], $_SESSION['wishlist'] ?? [], true) ? 'solid' : 'regular'; ?> fa-heart"></i>
                </button>
            </div>

            <div class="pd-meta-row">
                <div class="pd-meta-item"><i class="fa-solid fa-truck-fast"></i> Free shipping over $200</div>
                <div class="pd-meta-item"><i class="fa-solid fa-rotate-left"></i> Easy returns</div>
                <div class="pd-meta-item"><i class="fa-solid fa-shield-halved"></i> Secure checkout</div>
            </div>

            <a href="index.php" class="pd-back-link"><i class="fa-solid fa-arrow-left"></i> Continue shopping</a>

        </div>
    </div>

    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

<script>
function pdSwapImage(src, thumbEl) {
    const mainImg = document.getElementById('pdMainImage');
    mainImg.style.opacity = 0;
    setTimeout(() => {
        mainImg.src = src;
        mainImg.style.opacity = 1;
    }, 150);
    document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
    thumbEl.classList.add('active');
}

function pdChangeQty(delta) {
    const input = document.getElementById('pdQtyInput');
    let val = parseInt(input.value, 10) || 1;
    val = Math.max(1, val + delta);
    input.value = val;
}

function pdAddToCart(productId) {
    const qty = document.getElementById('pdQtyInput').value;
    // NOTE: This is a UI-only placeholder — no cart backend is wired up yet.
    // Hook this up to your cart logic (session/DB) when ready.
    alert('Added ' + qty + ' item(s) to cart. (Cart system not yet connected — ask to have this built.)');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/wishlist.js"></script>
</body>
</html>