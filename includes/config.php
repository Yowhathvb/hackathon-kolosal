<?php
// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_db');

// Kolosal.ai API Configuration
define('KOLOSAL_API_KEY', 'api_kolosal');
define('KOLOSAL_API_URL', 'https://api.kolosal.ai/v1/chat/completions');
define('KOLOSAL_MODEL', 'Qwen 3 30BA3B');

// Base URL
define('BASE_URL', 'http://localhost:8000/ecommerce');

// File upload configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Koneksi Database
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Include functions
require_once 'functions.php';

// Auto-create logs directory
$logs_dir = __DIR__ . '/../logs';
if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0777, true);
}

// Auto-create upload directories
$directories = ['products', 'shop_logos', 'verification_docs', 'temp'];
foreach ($directories as $dir) {
    $path = UPLOAD_PATH . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}
?>