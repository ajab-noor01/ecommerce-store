<?php
// ==========================================================
// Nexora — Wishlist page
// Lists products saved in the session-based wishlist.
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php';
/** @var mysqli $conn */

$wishlist_ids = $_SESSION['wishlist'] ?? [];
$products = [];

if (!empty($wishlist_ids) && isset($conn) && !$conn->connect_error) {
    $ids = array_map('intval', $wishlist_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist — Zentury</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container my-5 py-3">

    <div class="text-center mb-5">
        <span class="text-warning text-uppercase small fw-bold" style="letter-spacing: 2px;">Saved For Later</span>
        <h2 class="fw-bold text-dark mt-1" style="color: #0d47a1 !important;">My Wishlist</h2>
    </div>

    <?php if (empty($products)): ?>
        <div id="wishlistEmpty" class="text-center py-5">
            <i class="fa-regular fa-heart" style="font-size:44px; color:#ddd;"></i>
            <p class="text-muted mt-3 mb-4">Your wishlist is empty. Tap the heart on any product to save it here.</p>
            <a href="index.php" class="btn hover-cart-btn" style="position:static; opacity:1; visibility:visible; display:inline-flex; width:auto; padding:12px 26px; border-radius:2px;">
                <i class="fa-solid fa-arrow-left"></i> Continue shopping
            </a>
        </div>
    <?php else: ?>

    <div id="wishlistGrid" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($products as $product):
            $primary_img = htmlspecialchars($product['image_1']);
            $secondary_img = !empty($product['image_2']) ? htmlspecialchars($product['image_2']) : $primary_img;
        ?>
        <div class="col">
            <div class="card product-card border-0 text-center bg-white p-2 h-100 position-relative" data-wishlist-card style="border-radius: 0;">

                <?php if (!empty($product['discount_badge'])): ?>
                    <span class="badge position-absolute top-0 start-0 m-2 text-white fw-bold" style="background-color: #0d47a1 !important; border-radius: 0; padding: 5px 10px; font-size: 0.75rem; z-index: 3;">
                        <?= htmlspecialchars($product['discount_badge']) ?>
                    </span>
                <?php endif; ?>

                <button type="button" class="wishlist-btn active" data-id="<?= (int) $product['id'] ?>" data-removable="1" aria-label="Remove from wishlist">
                    <i class="fa-solid fa-heart"></i>
                </button>

                <div class="product-img-wrapper mb-3">
                    <img src="<?= $primary_img ?>" class="img-fluid img-primary" alt="Primary Cover">
                    <img src="<?= $secondary_img ?>" class="img-fluid img-secondary" alt="Hover Cover">
                </div>

                <div class="card-body p-0 pt-2 text-start d-flex flex-column justify-content-between">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 0.9rem; font-weight: 400; line-height: 1.4; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            <?= htmlspecialchars($product['name']) ?>
                        </p>
                    </div>

                    <div class="card-action-container mt-2">
                        <div class="price-block w-100">
                            <p class="mb-0">
                                <?php if (!empty($product['old_price']) && $product['old_price'] > 0): ?>
                                    <span class="text-muted text-decoration-line-through small me-2">$<?= number_format($product['old_price'], 2) ?></span>
                                <?php endif; ?>
                                <span class="text-dark fw-bold" style="font-size: 1.05rem;">$<?= number_format($product['price'], 2) ?></span>
                            </p>
                        </div>

                        <?php if (!empty($product['amazon_link'])): ?>
                            <a href="<?= htmlspecialchars($product['amazon_link']) ?>" target="_blank" rel="nofollow sponsored noopener" class="btn hover-cart-btn amazon-btn rounded-0 shadow-sm text-decoration-none d-block text-center">
                                <i class="fa-brands fa-amazon"></i> Buy on Amazon
                            </a>
                        <?php else: ?>
                            <a href="product-detail.php?id=<?= (int) $product['id'] ?>" class="btn hover-cart-btn rounded-0 shadow-sm text-decoration-none d-block text-center">
                                <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div id="wishlistEmpty" class="text-center py-5" style="display:none;">
        <i class="fa-regular fa-heart" style="font-size:44px; color:#ddd;"></i>
        <p class="text-muted mt-3">Your wishlist is empty now.</p>
    </div>

    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/wishlist.js"></script>
</body>
</html>