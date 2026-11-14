<?php
// register.php - Customer Registration
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header("Location: customer-dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $result = register_user($name, $email, $password, $phone);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us - Authentic Cuisine Class</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Authentic <span>Cuisine</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="register.php" class="active">Register</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Begin your professional culinary journey with us today.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?php echo $success; ?>
                    <p class="mt-2" style="font-size: 0.85rem;"><a href="login.php">Click here to log in.</a></p>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="register.php" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. +60123456789" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register Now</button>
            </form>

            <div class="form-footer">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-container">
            <p>&copy; 2026 Authentic Cuisine Class Academy. All rights reserved.</p>
            <p>Malaysian Culinary Excellence Branches: Bangi | Klang | Damansara | Putrajaya | Shah Alam | Kuala Lumpur</p>
        </div>
    </footer>

    <script>
    function validateForm() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const phone = document.getElementById('phone').value;
        
        if (password.length < 6) {
            alert("Password must be at least 6 characters long.");
            return false;
        }
        
        if (password !== confirmPassword) {
            alert("Passwords do not match. Please re-enter.");
            return false;
        }

        // Quick check for Malaysian phone pattern (e.g. +60 or 01)
        const phoneRegex = /^(?:\+60|01)[0-9]{8,10}$/;
        if (!phoneRegex.test(phone.replace(/[\s\-]/g, ""))) {
            alert("Please enter a valid phone number (e.g., +60123456789 or 0123456789).");
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html>
