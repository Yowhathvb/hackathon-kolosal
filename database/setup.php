<?php
// database/setup.php
header('Content-Type: text/html; charset=utf-8');

// Koneksi ke MySQL tanpa database
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE ecommerce_db");
    
    echo "Database berhasil dibuat/ditemukan!<br>";
    
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// SQL untuk membuat tabel
$sql = "
SET FOREIGN_KEY_CHECKS=0;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(500) DEFAULT NULL,
    role ENUM('customer', 'seller', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Toko
CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shop_name VARCHAR(255) UNIQUE NOT NULL,
    shop_slug VARCHAR(255) UNIQUE NOT NULL,
    shop_description TEXT,
    shop_logo VARCHAR(500) DEFAULT NULL,
    shop_banner VARCHAR(500) DEFAULT NULL,
    shop_address TEXT NOT NULL,
    shop_phone VARCHAR(20) NOT NULL,
    shop_email VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verification_documents JSON,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Kategori
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    images JSON NOT NULL,
    category_id INT,
    tags TEXT,
    ai_generated BOOLEAN DEFAULT FALSE,
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Insert sample categories
INSERT IGNORE INTO categories (id, name, slug, description) VALUES
(1, 'Fashion', 'fashion', 'Pakaian dan aksesoris fashion'),
(2, 'Elektronik', 'elektronik', 'Produk elektronik dan gadget'),
(3, 'Kesehatan & Kecantikan', 'kesehatan-kecantikan', 'Produk kesehatan dan kecantikan'),
(4, 'Rumah Tangga', 'rumah-tangga', 'Peralatan rumah tangga'),
(5, 'Olahraga', 'olahraga', 'Perlengkapan olahraga');

-- Insert sample admin user (password: admin123)
INSERT IGNORE INTO users (id, username, email, password, full_name, role) VALUES
(1, 'admin', 'admin@myshopee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

SET FOREIGN_KEY_CHECKS=1;
";

try {
    // Eksekusi SQL
    $pdo->exec($sql);
    echo "Semua tabel berhasil dibuat!<br>";
    echo "User admin default: <strong>admin / admin123</strong><br>";
    echo "<a href='../index.php'>Lanjut ke Website</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>