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
    }
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
