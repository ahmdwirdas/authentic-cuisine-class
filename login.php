<?php
// login.php - Combined Login Portal (Customer & Admin)
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header("Location: customer-dashboard.php");
    exit;
} elseif (is_admin_logged_in()) {
    header("Location: admin-dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? ''); // email or username
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your email / username and password.';
    } else {
        // 1. Try Admin login first (by username)
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$identifier]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Admin authenticated
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role']     = $admin['role'];
            header("Location: admin-dashboard.php");
            exit;
        }

        // 2. Try Customer login (by email)
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Customer authenticated
            $_SESSION['customer_id']    = $user['id'];
            $_SESSION['customer_name']  = $user['name'];
            $_SESSION['customer_email'] = $user['email'];
            $redirect = $_SESSION['login_redirect'] ?? 'customer-dashboard.php';
            unset($_SESSION['login_redirect']);
            header("Location: $redirect");
            exit;
        }

        // Neither matched
        $error = 'Invalid credentials. Please check your email / username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Authentic Cuisine Class</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-icon {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--text-dim);
            font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }
        .info-box {
            background: var(--accent-gold-dim);
            border: 1px solid var(--border-gold);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
            color: var(--accent-gold-light);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Authentic <span>Cuisine</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php" class="active">Login</a></li>
            </ul>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="login-icon">🍽️</div>

            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account — works for both students and administrators.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <span>ℹ️</span>
                <div>
                    <strong>Students</strong>: use your registered <strong>email address</strong>.<br>
                    <strong>Administrators</strong>: use your <strong>username</strong> (e.g. <code>admin</code>).
                </div>
            </div>

            <form method="POST" action="login.php" onsubmit="return validateForm()">

                <div class="form-group">
                    <label for="identifier">Email Address or Username</label>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        class="form-control"
                        placeholder="Enter your email or username"
                        required
                        autocomplete="username"
                        value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary" id="login-btn" style="width: 100%; margin-top: 1.25rem; padding: 0.85rem;">
                    Sign In
                </button>
            </form>

            <div class="divider">or</div>

            <div class="form-footer">
                New student? <a href="register.php">Create a free account</a>
            </div>

        </div>
    </div>

    <footer>
        <div class="footer-container">
            <p>&copy; 2026 Authentic Cuisine Class Academy. All rights reserved.</p>
            <p>Malaysian Culinary Excellence: Bangi | Klang | Damansara | Putrajaya | Shah Alam | KL</p>
        </div>
    </footer>

    <script>
    function validateForm() {
        const identifier = document.getElementById('identifier').value.trim();
        const password   = document.getElementById('password').value;

        if (!identifier || !password) {
            alert('Please fill in both fields before signing in.');
            return false;
        }
        return true;
    }
    </script>
</body>
</html>
