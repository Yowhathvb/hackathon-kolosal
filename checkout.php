<?php
include 'includes/config.php';
include 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Fetch cart items
    $stmt = $pdo->prepare("SELECT c.id as cart_id, c.quantity, p.id as product_id, p.price, p.stock, p.product_name FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        $_SESSION['error'] = 'Keranjang kosong. Tidak ada yang dibeli.';
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }

    // Check stock availability
    foreach($items as $it) {
        if ($it['quantity'] > $it['stock']) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Stok tidak mencukupi untuk produk: ' . $it['product_name'];
            header('Location: ' . BASE_URL . '/cart.php');
            exit;
        }
    }

    // Create orders and order_items tables if not exist (safe migration)
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_number VARCHAR(100) NOT NULL,
        total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'paid',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (order_id),
        CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Calculate total
    $total = 0;
    foreach($items as $it) {
        $total += $it['price'] * $it['quantity'];
    }

    // Insert order
    $order_number = 'ORD' . time() . rand(100,999);
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, status) VALUES (?, ?, ?, 'paid')");
    $stmt->execute([$user_id, $order_number, $total]);
    $order_id = $pdo->lastInsertId();

    // Insert order items and reduce stock
    $insertItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $updateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

    foreach($items as $it) {
        $insertItem->execute([$order_id, $it['product_id'], $it['quantity'], $it['price']]);
        $updateStock->execute([$it['quantity'], $it['product_id'], $it['quantity']]);
    }

    // Clear cart for user
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();

    $_SESSION['success'] = 'Pembelian berhasil. Pesanan Anda telah dibuat.';
    header('Location: ' . BASE_URL . '/orders.php');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('checkout failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.';
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

?>
