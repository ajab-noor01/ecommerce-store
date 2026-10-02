<?php
// Load store info from site_settings if it exists (parent page already
// included config/db.php before including this file, so $conn is available).
$__store = [
    'phone' => '+92 3044304440', 'email' => 'ajabn0414@gmail.com',
    'address' => 'pakistan kpk bannu', 'city' => 'bannu City', 'state' => 'Your State / ST',
];
if (isset($conn) && !$conn->connect_error) {
    $__settings_check = $conn->query("SHOW TABLES LIKE 'site_settings'");
    if ($__settings_check && $__settings_check->num_rows > 0) {
        $__row = $conn->query("SELECT * FROM site_settings WHERE id = 1")->fetch_assoc();
        if ($__row) $__store = array_merge($__store, $__row);
    }
}
?>
<style>
    .site-footer{
        background:#0d3b8c;
        color:#eaf0ff;
        padding:70px 0 0;
    }

    .footer-logo-mark{
        width:62px; height:62px;
        display:flex; align-items:center; justify-content:center;
        margin-bottom:14px;
    }
    .footer-logo-mark svg{ width:100%; height:100%; }

    .footer-brand-name{
        font-weight:800; font-size:1.3rem; letter-spacing:2px; color:#fff;
        margin-bottom:22px;
    }

    .footer-question{
        color:#a9c0ef; font-size:.9rem; margin-bottom:6px;
    }
    .footer-phone{
        color:#fff; font-weight:700; font-size:1.3rem; margin-bottom:20px; display:block;
        text-decoration:none;
    }
    .footer-phone:hover{ color:#ffb74d; }

    .footer-social a{
        width:38px; height:38px; border-radius:50%;
        display:inline-flex; align-items:center; justify-content:center;
        color:#fff; margin-right:10px; font-size:15px;
        transition: transform .15s ease, opacity .15s ease;
    }
    .footer-social a:hover{ transform:translateY(-3px); opacity:.9; }
    .footer-social .fb{ background:#1877f2; }
    .footer-social .tw{ background:#1da1f2; }
    .footer-social .pin{ background:#e60023; }
    .footer-social .yt{ background:#ff0000; }

    .footer-heading{
        font-weight:700; font-size:1.3rem; color:#fff; margin-bottom:22px;
    }

    .footer-links{ list-style:none; padding:0; margin:0; }
    .footer-links li{ margin-bottom:15px; }
    .footer-links a{
        color:#cfdaf5; text-decoration:none; font-size:.95rem;
        transition: color .15s ease;
    }
    .footer-links a:hover{ color:#ffb74d; }

    .footer-biz p{
        color:#cfdaf5; font-size:.95rem; margin-bottom:15px; line-height:1.6;
    }
    .footer-biz strong{ color:#fff; }

    .footer-news p{ color:#cfdaf5; font-size:.9rem; margin-bottom:20px; }
    .footer-news-form{
        display:flex; background:#fff; border-radius:6px; overflow:hidden;
        max-width:360px;
    }
    .footer-news-form input{
        border:none; outline:none; padding:14px 16px; font-size:.85rem; flex:1; min-width:0;
    }
    .footer-news-form button{
        border:none; background:#fff; color:#0d3b8c; font-weight:800;
        font-size:.78rem; letter-spacing:.5px; padding:0 20px; text-transform:uppercase;
        cursor:pointer;
    }
    .footer-news-form button:hover{ background:#f0f0f0; }

    .footer-bottom{
        border-top:1px solid rgba(255,255,255,.15);
        margin-top:50px;
        padding:22px 0;
        text-align:center;
        font-size:.85rem;
        color:#b9c8ec;
    }
    .footer-bottom a{ color:#b9c8ec; text-decoration:underline; }

    .scroll-top-btn{
        position:fixed; right:24px; bottom:24px; z-index:999;
        width:46px; height:46px; border-radius:8px;
        background:#0d3b8c; color:#fff; border:none;
        display:none; align-items:center; justify-content:center;
        font-size:16px; box-shadow:0 6px 18px rgba(0,0,0,.25);
        cursor:pointer; transition: background .15s ease;
    }
    .scroll-top-btn:hover{ background:#08357e; }
    .scroll-top-btn.show{ display:flex; }

    @media (max-width: 767px){
        .site-footer{ text-align:center; }
        .footer-social{ display:flex; justify-content:center; }
        .footer-news-form{ margin:0 auto; }
    }
</style>

<footer class="site-footer">
    <div class="container">
        <div class="row g-5">

            <!-- BRAND -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-logo-mark">
                    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="32,4 58,18 58,46 32,60 6,46 6,18"
                                 fill="none" stroke="#5bc8ff" stroke-width="3"/>
                        <rect x="22" y="22" width="20" height="20" rx="3" fill="#5bc8ff"/>
                    </svg>
                </div>
                <div class="footer-brand-name"><?= htmlspecialchars(strtoupper($__store['store_name'] ?? 'ZENTURY')) ?></div>

                <div class="footer-question">Got a question? Call us 24/7</div>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $__store['phone'])) ?>" class="footer-phone"><?= htmlspecialchars($__store['phone']) ?></a>

                <div class="footer-social">
                    <a href="#" class="fb" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="tw" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="pin" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a>
                    <a href="#" class="yt" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>

            <!-- QUICK LINKS -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-heading">Quick Links</div>
                <ul class="footer-links">
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="#">Shipping Policy</a></li>
                    <li><a href="#">Returns &amp; Refunds</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>

            <!-- BUSINESS INFO -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-heading">Business Information</div>
                <div class="footer-biz">
                    <p><strong>Phone Number:</strong> <?= htmlspecialchars($__store['phone']) ?></p>
                    <p><strong>Email Address:</strong><br><?= htmlspecialchars($__store['email']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($__store['address']) ?></p>
                    <p><strong>City:</strong> <?= htmlspecialchars($__store['city']) ?></p>
                    <p><strong>State:</strong> <?= htmlspecialchars($__store['state']) ?></p>
                </div>
            </div>

            <!-- NEWSLETTER -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-heading">Join Our Newsletter Now</div>
                <div class="footer-news">
                    <p>Be the first to know. Sign up to our newsletter today.</p>
                    <form class="footer-news-form" onsubmit="event.preventDefault(); this.querySelector('input').value=''; alert('Thanks for subscribing!');">
                        <input type="email" placeholder="your@email.com" required>
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            &copy; <?= date('Y') ?>ZENTURY. <a href="#">Privacy Policy</a>. All rights reserved.
        </div>
    </div>
</footer>

<button id="scrollTopBtn" class="scroll-top-btn" aria-label="Back to top">
    <i class="fa-solid fa-chevron-up"></i>
</button>

<script>
(function(){
    const btn = document.getElementById('scrollTopBtn');
    window.addEventListener('scroll', function(){
        if (window.scrollY > 400) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    });
    btn.addEventListener('click', function(){
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
</script>