<?php
// db.php - Database connection configurations and auto-initialization

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'cookingcuisine';

try {
    // Connect to MySQL server without database first (to allow auto-creation if dropped)
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Check if database exists
    $db_check = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'")->fetch();

    if (!$db_check) {
        // Create database if not exists
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    // Connect to the actual database
    $pdo->exec("USE `$dbname`");

    // Double check if tables exist, if not, load from database.sql
    $table_check = $pdo->query("SHOW TABLES")->fetchAll();
    if (count($table_check) === 0) {
        $sqlPath = __DIR__ . '/database.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        }
    } else {
        // Run light schema migration check for existing setups
        $tbls = array_map(function($r) { return array_values($r)[0]; }, $table_check);
        if (!in_array('instructors', $tbls)) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `instructors` (
              `id` varchar(255) NOT NULL,
              `name` varchar(255) NOT NULL,
              `email` varchar(255) NOT NULL,
              `phone` varchar(50) NOT NULL,
              `specialty` varchar(100) NOT NULL,
              `bio` text DEFAULT NULL,
              `status` enum('Active','Inactive') DEFAULT 'Active',
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        if (!in_array('payments', $tbls)) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
              `id` varchar(255) NOT NULL,
              `booking_id` varchar(255) NOT NULL,
              `customer_id` varchar(255) NOT NULL,
              `amount` decimal(10,2) NOT NULL,
              `payment_method` varchar(50) NOT NULL,
              `card_last4` varchar(10) DEFAULT NULL,
              `bank_name` varchar(100) DEFAULT NULL,
              `status` enum('Paid','Pending','Failed','Refunded') NOT NULL DEFAULT 'Paid',
              `transaction_ref` varchar(100) NOT NULL,
              `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `booking_id` (`booking_id`),
              KEY `customer_id` (`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        // Ensure survey admin remark columns exist
        $surveyCols = $pdo->query("SHOW COLUMNS FROM surveys LIKE 'admin_remark'")->fetchAll();
        if (empty($surveyCols)) {
            $pdo->exec("ALTER TABLE surveys ADD COLUMN admin_remark text DEFAULT NULL, ADD COLUMN remarked_at datetime DEFAULT NULL");
        }
        // Ensure payment status in bookings exists
        $bkCols = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'")->fetchAll();
        if (empty($bkCols)) {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_status enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid' AFTER status");
        }
        // Ensure instructor_id column exists
        $classCols = $pdo->query("SHOW COLUMNS FROM cuisineclasses LIKE 'instructor_id'")->fetchAll();
        if (empty($classCols)) {
            $pdo->exec("ALTER TABLE cuisineclasses ADD COLUMN instructor_id varchar(255) DEFAULT NULL AFTER cuisine_type");
        }
    }
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
