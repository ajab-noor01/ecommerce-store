<?php
// ==========================================================
// Nexora — Contact page
// Saves messages to contact_messages if that table exists
// (see contact_messages_migration.sql). Works fine without it too.
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php';
/** @var mysqli $conn */

// Load store info from site_settings if it exists, else use defaults.
$store = [
    'phone' => '+1 (000) 000-0000', 'email' => 'support@yourstore.com',
    'address' => '123 Your Street Address', 'city' => 'Your City', 'state' => 'Your State / ST',
];
$settings_check = $conn->query("SHOW TABLES LIKE 'site_settings'");
if ($settings_check && $settings_check->num_rows > 0) {
    $row = $conn->query("SELECT * FROM site_settings WHERE id = 1")->fetch_assoc();
    if ($row) $store = array_merge($store, $row);
}

$sent = false;
$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            $error = 'Please fill in your name, email, and message.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Save it if the (optional) contact_messages table exists.
            $table_check = $conn->query("SHOW TABLES LIKE 'contact_messages'");
            if ($table_check && $table_check->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $message);
                $stmt->execute();
                $stmt->close();
            }
            $sent = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Zentury</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .contact-hero{
            background:#fca326 !important;
            padding:70px 0;
        }
        .contact-hero h2{
            color:#0d3b66;
            font-weight:800;
            font-size:2.1rem;
            margin-bottom:10px;
        }
        .contact-hero .sub-text{
            color:#1d2d44;
            font-size:.95rem;
            margin-bottom:34px;
        }

        .contact-info-item{
            display:flex;
            align-items:flex-start;
            gap:16px;
            padding-bottom:22px;
            margin-bottom:22px;
            border-bottom:1px solid rgba(255,255,255,.35);
        }
        .contact-info-item:last-child{ border-bottom:none; margin-bottom:0; }
        .contact-info-icon{
            width:48px; height:48px; border-radius:10px; background:#fff;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
            color:#e74c3c; font-size:18px;
        }
        .contact-info-label{
            color:#0d3b66; font-weight:700; font-size:.78rem; text-transform:uppercase;
            letter-spacing:.5px; margin-bottom:4px;
        }
        .contact-info-value{ color:#1d2d44; font-weight:600; font-size:1.05rem; line-height:1.5; }

        .contact-form-card{
            background:#fff;
            border-radius:4px;
            padding:36px;
            box-shadow:0 15px 40px rgba(0,0,0,.12);
        }
        .contact-form-card h3{
            color:#0d3b66; font-weight:800; font-size:1.6rem; margin-bottom:6px;
        }
        .contact-form-card .sub-text{ color:#777; font-size:.9rem; margin-bottom:26px; }

        .contact-form-card .form-control{
            border:1px solid #e2e2e2; border-radius:4px; padding:13px 16px; font-size:.9rem;
        }
        .contact-form-card .form-control:focus{
            border-color:#0d47a1; box-shadow:0 0 0 3px rgba(13,71,161,.1); outline:none;
        }

        .contact-submit-btn{
            background:#0d47a1; color:#fff; border:none; padding:13px 34px;
            font-weight:700; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px;
            border-radius:4px; transition:background .2s ease;
        }
        .contact-submit-btn:hover{ background:#08357e; color:#fff; }

        .contact-alert{
            border:none; border-left:3px solid; border-radius:4px; padding:12px 16px;
            font-size:.88rem; font-weight:500; margin-bottom:20px;
        }
        .contact-alert.success{ background:#e7f8ef; color:#16834d; border-color:#16834d; }
        .contact-alert.danger{ background:#fdecea; color:#c0392b; border-color:#c0392b; }

        .contact-map iframe{
            width:100%; height:380px; border:0; display:block;
            filter: grayscale(15%);
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- ================= CONTACT HERO / INFO + FORM ================= -->
<section class="contact-hero">
    <div class="container">
        <div class="row g-5">

            <!-- LEFT: Contact Info -->
            <div class="col-lg-5 text-white">
                <h2>Contact Info.</h2>
                <p class="sub-text">Some information that you may want to know</p>

                <div class="contact-info-item">
                    <div class="contact-info-icon"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <div class="contact-info-label">Phone Number</div>
                        <div class="contact-info-value"><?= htmlspecialchars($store['phone']) ?></div>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div>
                        <div class="contact-info-label">Address</div>
                        <div class="contact-info-value">
                            <?= htmlspecialchars($store['address']) ?><br><br>
                            <strong>City:</strong> <?= htmlspecialchars($store['city']) ?><br>
                            <strong>State:</strong> <?= htmlspecialchars($store['state']) ?>
                        </div>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <div class="contact-info-label">Email</div>
                        <div class="contact-info-value"><?= htmlspecialchars($store['email']) ?></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Message Form -->
            <div class="col-lg-7">
                <div class="contact-form-card">
                    <h3>Leave Your Message</h3>
                    <p class="sub-text">Feel free to contact with us by using the form below</p>

                    <?php if ($sent): ?>
                        <div class="contact-alert success">✅ Thanks! Your message has been sent — we'll get back to you soon.</div>
                    <?php elseif ($error): ?>
                        <div class="contact-alert danger">⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="contact.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <textarea name="message" rows="6" class="form-control" placeholder="Message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="contact-submit-btn">Send Us</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ================= LOCATION MAP ================= -->
<section class="contact-map">
    <!--
        Replace the src below with your own Google Maps embed:
        Google Maps -> search your address -> Share -> Embed a map -> copy the src URL.
    -->
    <iframe
        src="https://maps.google.com/maps?q=New%20York%2C%20NY&t=&z=12&ie=UTF8&iwloc=&output=embed"
        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</section>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>