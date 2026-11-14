<?php
// auth.php - Session Management and Authentication Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Sanitizes input data.
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generates a clean sequential-like unique string ID with a prefix.
 * e.g., CUST-1234567, BK-9876543
 */
function generate_id($prefix) {
    return $prefix . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Checks if a customer is logged in.
 */
function is_logged_in() {
    return isset($_SESSION['customer_id']);
}

/**
 * Checks if an admin is logged in.
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

/**
 * Requires the customer to be logged in, otherwise redirects.
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    }
}

/**
 * Requires the admin to be logged in, otherwise redirects.
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Handles customer registration.
 */
function register_user($name, $email, $password, $phone) {
    global $pdo;
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email is already registered.'];
    }
    
    $id = generate_id('CUST');
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("INSERT INTO customers (id, name, email, password, phone) VALUES (?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$id, $name, $email, $hashed_password, $phone]);
        return ['success' => true, 'message' => 'Registration successful! You can now log in.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Handles customer login.
 */
function login_user($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_name'] = $user['name'];
        $_SESSION['customer_email'] = $user['email'];
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Invalid email or password.'];
}

/**
 * Handles admin login.
 */
function login_admin($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Invalid admin username or password.'];
}
?>
