<?php
// ==========================================================
// Nexora — Wishlist toggle (AJAX endpoint)
// Session-based: no login required. Works per-browser-session.
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product id']);
    exit;
}

if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

if (in_array($product_id, $_SESSION['wishlist'], true)) {
    $_SESSION['wishlist'] = array_values(array_diff($_SESSION['wishlist'], [$product_id]));
    $status = 'removed';
} else {
    $_SESSION['wishlist'][] = $product_id;
    $status = 'added';
}

echo json_encode([
    'status'  => $status,
    'count'   => count($_SESSION['wishlist']),
    'product' => $product_id,
]);