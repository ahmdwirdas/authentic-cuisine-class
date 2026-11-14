<?php
// index.php - Main Landing Homepage
require_once __DIR__ . '/auth.php';

$contact_success = '';
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_inquiry') {
    $c_name = sanitize($_POST['name'] ?? '');
    $c_email = sanitize($_POST['email'] ?? '');
    $c_msg = sanitize($_POST['message'] ?? '');

    if (empty($c_name) || empty($c_email) || empty($c_msg)) {
        $contact_error = 'Please fill in all fields before sending your message.';
    } elseif (!filter_var($c_email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = 'Please provide a valid email address.';
    } else {
        try {
            $inq_id = generate_id('INQ');
            $stmt = $pdo->prepare("INSERT INTO inquiries (id, name, email, message, status, submitted_at) VALUES (?, ?, ?, ?, 'New', NOW())");
            $stmt->execute([$inq_id, $c_name, $c_email, $c_msg]);
            $contact_success = 'Thank you! Your message has been received by our chefs and academic team.';
        } catch (PDOException $e) {
            $contact_error = 'Could not submit message. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentic Cuisine Class - Culinary Academy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cuisine-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .branch-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .contact-container {
            max-width: 800px;
            margin: 4rem auto 0 auto;
            text-align: center;
        }
        .contact-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            text-align: left;
            margin-top: 2rem;
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Authentic <span>Cuisine</span></a>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="customer-dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php elseif (is_admin_logged_in()): ?>
                    <li><a href="admin-dashboard.php">Admin Console</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <span class="section-subtitle">Premium Culinary Academy</span>
                <h1>Master the Fine Art of Gourmet Cooking</h1>
                <p>Learn specialized cooking techniques from award-winning master chefs. Explore distinct cuisine disciplines at Malaysia's premier culinary facilities.</p>
                <div class="hero-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="customer-dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php elseif (is_admin_logged_in()): ?>
                        <a href="admin-dashboard.php" class="btn btn-primary">Go to Admin Console</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Begin Registration</a>
                        <a href="login.php" class="btn btn-secondary">Student Log In</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image-container">
                <img src="hero.png" alt="Luxury Cooking Class" class="hero-image">
            </div>
        </section>

        <!-- Cuisine Sections -->
        <section style="margin-top: 6rem;">
            <div class="section-title-wrap">
                <span class="section-subtitle">Our Specializations</span>
                <h2 class="section-title">Explore Cuisine Masterclasses</h2>
                <p class="section-desc">We offer four meticulously structured modules designed to elevate your expertise from enthusiast to culinary artist.</p>
            </div>
            
            <div class="cuisine-showcase">
                <div class="card feature-card">
                    <span class="feature-badge">Haute Cuisine</span>
                    <h3>High Culinary Art</h3>
                    <p>Focuses on meticulous preparation and presentation of gourmet foods. Master classical French mother sauces, precision knife skills, and high-end plating aesthetics.</p>
                </div>
                <div class="card feature-card">
                    <span class="feature-badge">Vegetarian Cuisine</span>
                    <h3>Plant-Based Mastery</h3>
                    <p>Unlock the rich textures and intense flavors of heritage plant cooking. Dive deep into complex spices, meat alternatives, and gourmet vegetarian pastry.</p>
                </div>
                <div class="card feature-card">
                    <span class="feature-badge">Novella Cuisine</span>
                    <h3>Modernist Gastronomy</h3>
                    <p>Embrace modern technology in the kitchen. Experiment with molecular gastronomy, gels, spherification, foaming, and low-temperature sous vide methods.</p>
                </div>
                <div class="card feature-card">
                    <span class="feature-badge">Fusion Cuisine</span>
                    <h3>Cross-Cultural Innovation</h3>
                    <p>Learn to pair distinct culinary cultures harmoniously. Bridge East-West flavors, innovate contemporary fusion recipes, and create signature culinary crossovers.</p>
                </div>
            </div>
        </section>

        <!-- Branches Sections -->
        <section style="margin-top: 6rem;">
            <div class="section-title-wrap">
                <span class="section-subtitle">Our Academy Branches</span>
                <h2 class="section-title">Locate A Studio Near You</h2>
                <p class="section-desc">Our branches feature state-of-the-art individual cooking stations, professional gas ranges, and tools to ensure premium safety and comfort.</p>
            </div>

            <div class="branch-grid">
                <div class="card">
                    <h3 style="color: var(--accent-gold-light); margin-bottom: 0.5rem;">Bangi Branch</h3>
                    <p class="text-dim" style="font-size: 0.9rem; margin-bottom: 1rem;">12, Jalan 15/1A, Seksyen 15, 43650 Bangi, Selangor</p>
                    <div style="font-size: 0.85rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <span>📞 Phone: +60 3-8925 1111</span>
                    </div>
                </div>
                <div class="card">
                    <h3 style="color: var(--accent-gold-light); margin-bottom: 0.5rem;">Klang Branch</h3>
                    <p class="text-dim" style="font-size: 0.9rem; margin-bottom: 1rem;">45, Jalan Batu Tiga Lama, 41300 Klang, Selangor</p>
                    <div style="font-size: 0.85rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <span>📞 Phone: +60 3-3344 2222</span>
                    </div>
                </div>
                <div class="card">
                    <h3 style="color: var(--accent-gold-light); margin-bottom: 0.5rem;">Damansara Branch</h3>
                    <p class="text-dim" style="font-size: 0.9rem; margin-bottom: 1rem;">8A, Jalan SS21/39, Damansara Utama, 47400 PJ</p>
                    <div style="font-size: 0.85rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <span>📞 Phone: +60 3-7722 3333</span>
                    </div>
                </div>
                <div class="card">
                    <h3 style="color: var(--accent-gold-light); margin-bottom: 0.5rem;">Putrajaya Branch</h3>
                    <p class="text-dim" style="font-size: 0.9rem; margin-bottom: 1rem;">Level 2, Shaftsbury Square, 63000 Putrajaya</p>
                    <div style="font-size: 0.85rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <span>📞 Phone: +60 3-8888 4444</span>
                    </div>
                </div>
                <div class="card">
                    <h3 style="color: var(--accent-gold-light); margin-bottom: 0.5rem;">Shah Alam Branch</h3>
                    <p class="text-dim" style="font-size: 0.9rem; margin-bottom: 1rem;">15, Jalan Utas 15/D, Seksyen 15, 40200 Shah Alam</p>
                    <div style="font-size: 0.85rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <span>📞 Phone: +60 3-5511 5555</span>
                    </div>
                </div>
                <div class="card">
                    <h3 style="color: var(--accent-gold-light); margin-bottom: 0.5rem;">Kuala Lumpur Branch</h3>
                    <p class="text-dim" style="font-size: 0.9rem; margin-bottom: 1rem;">102, Jalan Ampang, 50450 Kuala Lumpur</p>
                    <div style="font-size: 0.85rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <span>📞 Phone: +60 3-2161 6666</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-container" id="contact-section">
            <div class="section-title-wrap" style="margin-bottom: 2rem;">
                <span class="section-subtitle">Get In Touch</span>
                <h2 class="section-title">Have Questions? Ask Our Chefs</h2>
                <p class="section-desc">Send us a message and our academic team will get back to you within 24 hours.</p>
            </div>

            <?php if (!empty($contact_success)): ?>
                <div class="alert alert-success" style="max-width: 600px; margin: 0 auto 1.5rem auto;">
                    <span>✅</span> <?php echo $contact_success; ?>
                </div>
            <?php elseif (!empty($contact_error)): ?>
                <div class="alert alert-danger" style="max-width: 600px; margin: 0 auto 1.5rem auto;">
                    <span>⚠️</span> <?php echo $contact_error; ?>
                </div>
            <?php endif; ?>
            
            <form class="card text-left" style="max-width: 600px; margin: 0 auto;" method="POST" action="index.php#contact-section">
                <input type="hidden" name="action" value="submit_inquiry">
                <div class="form-group">
                    <label for="c_name">Name</label>
                    <input type="text" id="c_name" name="name" class="form-control" required placeholder="Your name" value="<?php echo isset($contact_success) && empty($contact_success) ? htmlspecialchars($_POST['name'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="c_email">Email</label>
                    <input type="email" id="c_email" name="email" class="form-control" required placeholder="Your email address" value="<?php echo isset($contact_success) && empty($contact_success) ? htmlspecialchars($_POST['email'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="c_msg">Message</label>
                    <textarea id="c_msg" name="message" class="form-control" rows="4" required placeholder="What would you like to know?"><?php echo isset($contact_success) && empty($contact_success) ? htmlspecialchars($_POST['message'] ?? '') : ''; ?></textarea>
                </div>
                <button type="submit" class="btn btn-gold-outline" style="width: 100%;">Send Message</button>
            </form>
        </section>
    </div>

    <footer>
        <div class="footer-container">
            <p>&copy; 2026 Authentic Cuisine Class Academy. All rights reserved.</p>
            <p>Malaysian Culinary Excellence Branches: Bangi | Klang | Damansara | Putrajaya | Shah Alam | Kuala Lumpur</p>
        </div>
    </footer>

</body>
</html>
