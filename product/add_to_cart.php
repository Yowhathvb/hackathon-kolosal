<?php
session_start();
require_once '../includes/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Anda harus login terlebih dahulu untuk menambahkan produk ke keranjang.';
    header('Location: ' . BASE_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/products.php'));
    exit;
}

// Validasi input
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    $_SESSION['error'] = 'Data tidak lengkap.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/products.php'));
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);
$user_id = $_SESSION['user_id'];

// Validasi quantity
if ($quantity < 1) {
    $_SESSION['error'] = 'Jumlah produk minimal 1.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Cek apakah produk ada dan stok mencukupi
$stmt = $pdo->prepare("SELECT id, product_name, price, stock FROM products WHERE id = ? AND status = 'published'");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['error'] = 'Produk tidak ditemukan atau tidak tersedia.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Cek stok
if ($product['stock'] < $quantity) {
    $_SESSION['error'] = 'Stok tidak mencukupi. Stok tersedia: ' . $product['stock'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

try {
    // Mulai transaksi
    $pdo->beginTransaction();
    
    // Cek apakah produk sudah ada di keranjang user
    $check_stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check_stmt->execute([$user_id, $product_id]);
    $existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_item) {
        // Update quantity jika sudah ada
        $new_quantity = $existing_item['quantity'] + $quantity;
        
        // Cek lagi stok untuk update
        if ($product['stock'] < $new_quantity) {
            $_SESSION['error'] = 'Stok tidak mencukupi untuk menambah jumlah. Stok tersedia: ' . $product['stock'] . ', Jumlah di keranjang: ' . $existing_item['quantity'];
            $pdo->rollBack();
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        
        $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$new_quantity, $existing_item['id']]);
        $message = 'Jumlah produk di keranjang berhasil diperbarui.';
    } else {
        // Insert baru jika belum ada
        $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $insert_stmt->execute([$user_id, $product_id, $quantity]);
        $message = 'Produk berhasil ditambahkan ke keranjang.';
    }
    
    // Commit transaksi
    $pdo->commit();
    
    $_SESSION['success'] = $message;
    
} catch (PDOException $e) {
    // Rollback jika terjadi error
    $pdo->rollBack();
    $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

// Redirect kembali ke halaman sebelumnya
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;