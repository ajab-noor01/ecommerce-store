<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Zentury - Premium E-Commerce</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

<div id="heroCarousel" class="carousel slide position-relative overflow-hidden style-premium-hero"
     data-bs-ride="carousel"
     data-bs-interval="3000">

    <div class="fixed-hero-content-master unified-hero-drop">
        <span class="hero-sub-badge">We're proud to introduce</span>
        <h1 class="hero-main-title">
            Welcome To<br>
            Our Store Every<br>
            Thing is Available
        </h1>
        <p class="hero-orange-tag">Get Everything</p>
        <div class="hero-btn-wrap">
    <a href="shop-now.php" class="premium-hero-action-btn">Shop Now</a>
</div>
    </div>

    <div class="carousel-inner h-100" style="background: transparent !important;">
        <div class="carousel-item active h-100">
            <div class="hero-bg-stretched-layer" style="background-image: url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1600');"></div>
            <div class="premium-tint-overlay"></div>
        </div>
        <div class="carousel-item h-100">
            <div class="hero-bg-stretched-layer" style="background-image: url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=1600');"></div>
            <div class="premium-tint-overlay"></div>
        </div>
        <div class="carousel-item h-100">
            <div class="hero-bg-stretched-layer" style="background-image: url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1600');"></div>
            <div class="premium-tint-overlay"></div>
        </div>
        <div class="carousel-item h-100">
            <div class="hero-bg-stretched-layer" style="background-image: url('https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?q=80&w=1600');"></div>
            <div class="premium-tint-overlay"></div>
        </div>
    </div>

    <div class="wave-container-master-deep">

    <!-- Layer 1 -->
    <svg class="wave-svg-layer layer-1"
         viewBox="0 0 1440 320"
         preserveAspectRatio="none">

        <path fill="rgba(255,255,255,.18)"
        d="M0,180
        C160,120 330,250 520,190
        C720,130 930,70 1120,140
        C1280,200 1380,170 1440,120
        L1440,320
        L0,320Z"/>

    </svg>

    <!-- Layer 2 -->

    <svg class="wave-svg-layer layer-2"
         viewBox="0 0 1440 320"
         preserveAspectRatio="none">

        <path fill="rgba(255,255,255,.35)"
        d="M0,220
        C220,300 420,110 650,160
        C890,210 1040,280 1220,220
        C1320,185 1380,170 1440,180
        L1440,320
        L0,320Z"/>

    </svg>

    <!-- Layer 3 -->

    <svg class="wave-svg-layer layer-3"
         viewBox="0 0 1440 320"
         preserveAspectRatio="none">

        <path fill="rgba(255,255,255,.60)"
        d="M0,200
        C180,140 380,240 620,200
        C860,160 1050,80 1280,150
        C1360,170 1410,180 1440,160
        L1440,320
        L0,320Z"/>

    </svg>

    <!-- Front Layer -->

    <svg class="wave-svg-layer layer-4"
         viewBox="0 0 1440 320"
         preserveAspectRatio="none">

        <path fill="#ffffff"
        d="M0,170
        C220,120 450,250 700,210
        C960,170 1180,110 1440,170
        L1440,320
        L0,320Z"/>

    </svg>

</div>

</div>



<div class="container my-5  pt-5 overflow-hidden">
    
    <div class="text-center mb-5">
        <p class="text-uppercase fw-semibold tracking-wide text-warning mb-1" style="font-size: 14px;">Passion For Fashion</p>
        <h2 class="display-6 fw-bold text-dark" style="color: #1a237e !important;">Shop By Categories</h2>
    </div>

    <div class="row g-4 reveal-left">
        
        <div class="col-md-6">
            <div class="card border-0 position-relative shadow-sm" style="border-radius: 4px; height: 100%; min-height: 500px; background: url('https://images.unsplash.com/photo-1521572267360-ee0c2909d518?q=80&w=600') center/cover no-repeat;">
                <div class="position-absolute bottom-0 start-0 p-4 w-100" style="background: linear-gradient(to top, rgba(0,0,0,0.5), rgba(0,0,0,0));">
                 
                </div>
            </div>
        </div>

        <div class="col-md-6 d-flex flex-column gap-4">
            
            <div class="card border-0 position-relative shadow-sm" style="border-radius: 4px; height: 240px; background: url('https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?q=80&w=800') center/cover no-repeat;">
                <div class="position-absolute top-50 start-0 translate-middle-y ps-4">
                   
                </div>
            </div>

            <div class="row g-4 flex-grow-1">
                <div class="col-sm-6">
                    <div class="card border-0 position-relative shadow-sm" style="border-radius: 4px; height: 240px; background: url('https://images.unsplash.com/photo-1556911220-e15b29be8c8f?q=80&w=400') center/cover no-repeat;">
                        <div class="position-absolute bottom-0 start-0 p-3 w-100" style="background: linear-gradient(to top, rgba(0,0,0,0.4), rgba(0,0,0,0));">
                           
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card border-0 position-relative shadow-sm" style="border-radius: 4px; height: 240px; background: url('https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=400') center/cover no-repeat;">
                        <div class="position-absolute bottom-0 start-0 p-3 w-100" style="background: linear-gradient(to top, rgba(0,0,0,0.4), rgba(0,0,0,0));">
                           
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- ==================== ORANGE PATTI KE UPER (NEW ARRIVALS SECTION - FIXED) ==================== -->
<section id="shop-section" class="container my-5 py-4">
    <!-- Section Heading -->
    <div class="text-center mb-5">
        <span class="text-warning text-uppercase small fw-bold" style="letter-spacing: 2px;">Let's Shop</span>
        <h2 class="fw-bold text-dark mt-1" style="color: #0d47a1 !important;">New Arrivals</h2>
    </div>
    
    <!-- Dynamic Columns Bootstrap Grid -->
    <div class="row row-cols-2 row-cols-lg-4 g-4">
        <?php
        if (isset($conn) && !$conn->connect_error) {
            // Sirf New Arrivals wale products fetch honge
            $query = "SELECT * FROM products WHERE is_new_arrival = 1 ORDER BY id DESC LIMIT 4";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while($product = $result->fetch_assoc()) {
                    $primary_img = htmlspecialchars($product['image_1']);
                    $secondary_img = !empty($product['image_2']) ? htmlspecialchars($product['image_2']) : $primary_img;
                    ?>
                    
                    <div class="col slide-from-left">
                        <div class="card product-card border-0 text-center bg-white p-2 h-100 position-relative" data-wishlist-card style="border-radius: 0;">
                            
                            <!-- Pure Square Discount Badge -->
                            <?php if(!empty($product['discount_badge'])): ?>
                                <span class="badge position-absolute top-0 start-0 m-2 text-white fw-bold" style="background-color: #0d47a1 !important; border-radius: 0; padding: 5px 10px; font-size: 0.75rem; z-index: 3;">
                                    <?php echo htmlspecialchars($product['discount_badge']); ?>
                                </span>
                            <?php endif; ?>

                            <!-- Wishlist Heart -->
                            <button type="button"
                                    class="wishlist-btn <?php echo in_array($product['id'], $_SESSION['wishlist'] ?? [], true) ? 'active' : ''; ?>"
                                    data-id="<?php echo $product['id']; ?>" data-removable="0"
                                    aria-label="Add to wishlist">
                                <i class="fa-<?php echo in_array($product['id'], $_SESSION['wishlist'] ?? [], true) ? 'solid' : 'regular'; ?> fa-heart"></i>
                            </button>
                            
                            <!-- Image Hover Swap Block -->
                            <div class="product-img-wrapper mb-3">
                                <img src="<?php echo $primary_img; ?>" class="img-fluid img-primary" alt="Primary Cover">
                                <img src="<?php echo $secondary_img; ?>" class="img-fluid img-secondary" alt="Hover Cover">
                            </div>
                            
                            <!-- Content Details Block (Left Aligned Text) -->
                            <div class="card-body p-0 pt-2 text-start d-flex flex-column justify-content-between">
                                <div>
                                    <!-- Product Name (Max 2 Lines Control) -->
                                    <p class="text-muted mb-1" style="font-size: 0.9rem; font-weight: 400; line-height: 1.4; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </p>
                                </div>
                                
                                <!-- HOVER SWITCH ACTION CONTAINER (Prices aur Button Dono Ke Liye) -->
                                <div class="card-action-container mt-2">
                                    
                                    <!-- State 1: Prices Block (Normal Mode me dikhega, Hover par hide hoga) -->
                                    <div class="price-block w-100">
                                        <p class="mb-0">
                                            <?php if(!empty($product['old_price']) && $product['old_price'] > 0): ?>
                                                <span class="text-muted text-decoration-line-through small me-2">$<?php echo number_format($product['old_price'], 2); ?></span>
                                            <?php endif; ?>
                                            <span class="text-dark fw-bold" style="font-size: 1.05rem;">$<?php echo number_format($product['price'], 2); ?></span>
                                        </p>
                                    </div>
                                    
                                    <!-- State 2: Add to Cart / Buy on Amazon (Hover Mode par niche se upar slide hokar aayega) -->
                                    <?php if (!empty($product['amazon_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($product['amazon_link']); ?>" target="_blank" rel="nofollow sponsored noopener" class="btn hover-cart-btn amazon-btn rounded-0 shadow-sm text-decoration-none d-block text-center">
                                            <i class="fa-brands fa-amazon"></i> Buy on Amazon
                                        </a>
                                    <?php else: ?>
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn hover-cart-btn rounded-0 shadow-sm text-decoration-none d-block text-center">
                                            <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                        </a>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <?php
                }
            } else {
                echo "<p class='text-center text-muted w-100'>No new arrival products found.</p>";
            }
        }
        ?>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Intersection Observer Configuration
    const observerOptions = {
        root: null, // Browser window viewport
        rootMargin: "0px",
        threshold: 0.15 // Jab card 15% screen par aayega tabhi left side se slide hoga
    };

    const newArrivalsObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            // Jaise hi user scroll karke section par pahuchega
            if (entry.isIntersecting) {
                entry.target.classList.add("animate"); // CSS Trigger class add karega
                observer.unobserve(entry.target); // Ek baar chalne ke baad process freeze
            }
        });
    }, observerOptions);

    // Har New Arrival card par trigger register karna
    document.querySelectorAll("#shop-section .slide-from-left").forEach(card => {
        newArrivalsObserver.observe(card);
    });
});
</script>

<div class="container-fluid px-0 my-5" style="overflow: hidden; width: 100%;">
    
    <style>
        /* Base Scroll Slide Animation */
        .lifestyle-scroll-bar {
            opacity: 0;
            transform: translateX(-100%); 
            transition: transform 0.45s cubic-bezier(0.25, 1, 0.2, 1), opacity 0.4s ease-in-out;
            margin: 0; 
            width: 100%;
        }

        .lifestyle-scroll-bar.show-now {
            opacity: 1;
            transform: translateX(0) !important;
        }

        /* Zentury Style Typography Override */
        .nexora-feature-title {
            color: #0d3b66 !important; /* Nexora jaisa dark blue heading text */
            font-size: 20px !important;
            font-weight: 700 !important;
            margin-top: 15px !important;
            margin-bottom: 5px !important;
        }

        .nexora-feature-sub {
            color: #1d2d44 !important; /* Slightly dark readable subtext */
            font-size: 14px !important;
            font-weight: 500 !important;
        }

        /* Divider lines for Desktop/Laptop view */
        @media (min-width: 992px) {
            .orange-divider {
                border-left: 1px solid rgba(255, 255, 255, 0.3) !important;
            }
        }
    </style>

    <div id="orangeFeatureBar" class="row py-5 px-3 g-0 lifestyle-scroll-bar" style="background: #fca326 !important;">
        
        <!-- Feature Item 1: Free Shipping -->
        <div class="col-12 col-md-4 d-flex flex-column align-items-center justify-content-center text-center py-4">
            <div class="bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 75px; height: 75px; border-radius: 20px;">
                <span style="font-size: 32px; color: #0d3b66;">🛒</span>
            </div>
            <h5 class="nexora-feature-title">Free Shipping</h5>
            <p class="nexora-feature-sub mb-0">On all orders over $200</p>
        </div>

        <!-- Feature Item 2: Dedicated Support -->
        <div class="col-12 col-md-4 d-flex flex-column align-items-center justify-content-center text-center py-4 orange-divider">
            <div class="bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 75px; height: 75px; border-radius: 20px;">
                <span style="font-size: 32px; color: #0d3b66;">🎧</span>
            </div>
            <h5 class="nexora-feature-title">Dedicated Support</h5>
            <p class="nexora-feature-sub mb-0">Quick response 24/7</p>
        </div>

        <!-- Feature Item 3: Money-Back Guarantee -->
        <div class="col-12 col-md-4 d-flex flex-column align-items-center justify-content-center text-center py-4 orange-divider">
            <div class="bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 75px; height: 75px; border-radius: 20px;">
                <span style="font-size: 32px; color: #0d3b66;">💲</span>
            </div>
            <h5 class="nexora-feature-title">Money-Back Guarantee</h5>
            <p class="nexora-feature-sub mb-0">Worry-free shopping</p>
        </div>

    </div>
</div>

<section id="popular-section" class="container my-5 py-4">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark mt-1" style="color: #0d47a1 !important; font-size: 2rem;">Popular This Week</h2>
    </div>
    
     <div class="row row-cols-2 row-cols-lg-4 g-4">
        <?php
        if (isset($conn) && !$conn->connect_error) {
            $query_pop = "SELECT * FROM products WHERE is_popular = 1 ORDER BY id DESC LIMIT 4";
            $result_pop = $conn->query($query_pop);
            
            if ($result_pop && $result_pop->num_rows > 0) {
                while($product = $result_pop->fetch_assoc()) {
                    $primary_img = htmlspecialchars($product['image_1']);
                    $secondary_img = !empty($product['image_2']) ? htmlspecialchars($product['image_2']) : $primary_img;
                    $stars = intval($product['rating_stars']);
                    ?>
                    
                    <div class="col slide-from-left">
                        <div class="card product-card border-0 text-center bg-white p-2 h-100 position-relative" data-wishlist-card style="border-radius: 0;">
                            
                            <?php if(!empty($product['discount_badge'])): ?>
                                <span class="badge position-absolute top-0 start-0 m-2 text-white fw-bold" style="background-color: #0d47a1 !important; border-radius: 0; padding: 5px 10px; font-size: 0.75rem; z-index: 3;">
                                    <?php echo htmlspecialchars($product['discount_badge']); ?>
                                </span>
                            <?php endif; ?>

                            <!-- Wishlist Heart -->
                            <button type="button"
                                    class="wishlist-btn <?php echo in_array($product['id'], $_SESSION['wishlist'] ?? [], true) ? 'active' : ''; ?>"
                                    data-id="<?php echo $product['id']; ?>" data-removable="0"
                                    aria-label="Add to wishlist">
                                <i class="fa-<?php echo in_array($product['id'], $_SESSION['wishlist'] ?? [], true) ? 'solid' : 'regular'; ?> fa-heart"></i>
                            </button>
                            
                            <div class="product-img-wrapper mb-3">
                                <img src="<?php echo $primary_img; ?>" class="img-fluid img-primary" alt="Primary Cover">
                                <img src="<?php echo $secondary_img; ?>" class="img-fluid img-secondary" alt="Hover Cover">
                            </div>
                            
                            <div class="card-body p-0 pt-2 text-center d-flex flex-column justify-content-between">
                                <div>
                                    <p class="text-muted mb-1 px-1" style="font-size: 0.9rem; font-weight: 400; line-height: 1.4; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </p>
                                    
                                    <div class="text-warning small my-2">
                                        <?php 
                                        for($star_loop = 1; $star_loop <= 5; $star_loop++) {
                                            echo ($star_loop <= $stars) ? "★" : "☆";
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="card-action-container mt-2">
                                    
                                    <div class="price-block w-100">
                                        <p class="mb-0">
                                            <?php if(!empty($product['old_price']) && $product['old_price'] > 0): ?>
                                                <span class="text-muted text-decoration-line-through small me-2">$<?php echo number_format($product['old_price'], 2); ?></span>
                                            <?php endif; ?>
                                            <span class="text-dark fw-bold" style="font-size: 1.05rem;">$<?php echo number_format($product['price'], 2); ?></span>
                                        </p>
                                    </div>
                                    
                                    <?php if (!empty($product['amazon_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($product['amazon_link']); ?>" target="_blank" rel="nofollow sponsored noopener" class="btn hover-cart-btn amazon-btn rounded-0 shadow-sm text-decoration-none d-block text-center">
                                            <i class="fa-brands fa-amazon"></i> Buy on Amazon
                                        </a>
                                    <?php else: ?>
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn hover-cart-btn rounded-0 shadow-sm text-decoration-none d-block text-center">
                                            <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                        </a>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <?php
                }
            } else {
                echo "<p class='text-center text-muted w-100'>No popular products found.</p>";
            }
        }
        ?>
    </div>
</section>


<section id="lifestyle-section" class="w-100 mt-5 mb-0 pt-4 pb-0 overflow-hidden" style="background-color: #fff;">
    <div class="text-center mb-5">
        <span class="text-warning text-uppercase small fw-bold" style="letter-spacing: 2px;">STAY UP-TO-DATE</span>
        <h2 class="fw-bold mt-1" style="color: #0d47a1 !important; font-size: 2rem;">Best Seller Products</h2>
    </div>

    <div class="row g-0 w-100 m-0 align-items-stretch slide-from-left">
        
        <!-- Image 1 -->
        <div class="col-12 col-sm-4 col-md-2-5 col-lg" style="height: 350px; overflow: hidden; position: relative;">
            <img src="uploads/lifestyle1.jpg" 
                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=cover';" 
                 alt="Lifestyle 1" class="w-100 h-100 lifestyle-img" style="object-fit: cover; display: block; transition: all 0.6s ease;">
        </div>
        
        <!-- Image 2 -->
        <div class="col-12 col-sm-4 col-md-2-5 col-lg" style="height: 350px; overflow: hidden; position: relative;">
            <img src="uploads/lifestyle2.jpg" 
                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=600&auto=format&fit=cover';" 
                 alt="Lifestyle 2" class="w-100 h-100 lifestyle-img" style="object-fit: cover; display: block; transition: all 0.6s ease;">
        </div>
        
        <!-- Image 3 -->
        <div class="col-12 col-sm-4 col-md-2-5 col-lg" style="height: 350px; overflow: hidden; position: relative;">
            <img src="uploads/lifestyle3.jpg" 
                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=600&auto=format&fit=cover';" 
                 alt="Lifestyle 3" class="w-100 h-100 lifestyle-img" style="object-fit: cover; display: block; transition: all 0.6s ease;">
        </div>
        
        <!-- Image 4 -->
        <div class="col-12 col-sm-4 col-md-2-5 col-lg" style="height: 350px; overflow: hidden; position: relative;">
            <img src="uploads/lifestyle4.jpg" 
                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1539109136881-3be0616acf4b?q=80&w=600&auto=format&fit=cover';" 
                 alt="Lifestyle 4" class="w-100 h-100 lifestyle-img" style="object-fit: cover; display: block; transition: all 0.6s ease;">
        </div>
        
        <!-- Image 5 -->
        <div class="col-12 col-sm-4 col-md-2-5 col-lg" style="height: 350px; overflow: hidden; position: relative;">
            <img src="uploads/lifestyle5.jpg" 
                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=600&auto=format&fit=cover';" 
                 alt="Lifestyle 5" class="w-100 h-100 lifestyle-img" style="object-fit: cover; display: block; transition: all 0.6s ease;">
        </div>
        
    </div>
</section>
 
<!-- ==================== JAVASCRIPT SCROLL TRIGGER FOR LIFESTYLE ==================== -->
<!-- ==================== GLOBAL SCROLL ANIMATION TRIGGER (NO CONFLICT) ==================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Ek single smart observer configure kiya hai
    const globalScrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            // Jaise hi koi bhi card ya lifestyle patti screen par aayegi
            if (entry.isIntersecting) {
                entry.target.classList.add("animate"); // CSS slide effect trigger hoga
                observer.unobserve(entry.target); // Animation complete hote hi is card ko check karna stop kar do (Performance Fixed)
            }
        });
    }, { 
        root: null, 
        rootMargin: "0px", 
        threshold: 0.10 // Jab element 10% screen par aayega toh smoothly slide hoga
    });

    // Pure page par jahan jahan .slide-from-left class hai, ye un sab par automatic bina conflict ke laagu ho jayega
    document.querySelectorAll(".slide-from-left").forEach(element => {
        globalScrollObserver.observe(element);
    });
});
</script>
            <!-- lifestyle ended -->

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // A. Background Carousel Animation Init
        var myCarousel = document.querySelector('#heroCarousel');
        var carousel = new bootstrap.Carousel(myCarousel, {
            interval: 3000,
            ride: 'carousel',
            wrap: true
        });
        carousel.cycle();

        // B. Dynamic Scroll Entry Observer
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, {
            threshold: 0.15
        });

        var targetElement = document.querySelector('.reveal-left');
        if (targetElement) {
            observer.observe(targetElement);
        }
    });
</script>
<script>
    window.addEventListener("scroll", function () {
        var element = document.getElementById("orangeFeatureBar");
        if (element) {
            var position = element.getBoundingClientRect();
            
            // Checking if element is visible on screen window
            if (position.top < window.innerHeight * 0.85) {
                element.classList.add("show-now");
            }
        }
    });

    // Pehli baar page load hone par bhi check karega (agar user pehle se niche khada ho)
    document.addEventListener("DOMContentLoaded", function () {
        var element = document.getElementById("orangeFeatureBar");
        if (element) {
            var position = element.getBoundingClientRect();
            if (position.top < window.innerHeight) {
                element.classList.add("show-now");
            }
        }
    });

    window.addEventListener("scroll", function () {
    // Best Seller Animation Controller
    var bestSeller = document.getElementById("bestSellerLifestyleSection");
    if (bestSeller) {
        var posSeller = bestSeller.getBoundingClientRect();
        // Screen ke paas aate hi poori patti left side se fly karti hui aayegi
        if (posSeller.top < window.innerHeight * 0.90) {
            bestSeller.classList.add("reveal-now");
        }
    }
});
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/wishlist.js"></script>
</body>
</html>