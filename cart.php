<?php
include 'includes/config.php';
include 'includes/header.php';
include 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Handle actions: update quantity, remove
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['update_qty']) && isset($_POST['item_id']) && isset($_POST['quantity'])) {
        $item_id = intval($_POST['item_id']);
        $quantity = max(1, intval($_POST['quantity']));
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $item_id, $user_id]);
        $_SESSION['success'] = 'Keranjang diperbarui.';
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }

    if(isset($_POST['remove_item']) && isset($_POST['item_id'])) {
        $item_id = intval($_POST['item_id']);
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
        $_SESSION['success'] = 'Item dihapus dari keranjang.';
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.id as cart_id, c.quantity, c.price, p.product_name, p.image, p.id as product_id
                       FROM cart_items c
                       JOIN products p ON p.id = c.product_id
                       WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

$subtotal = 0;
foreach($items as $it) {
    $subtotal += ($it['price'] ?? 0) * $it['quantity'];
}
?>

<div class="container mt-4">
    <h2><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h2>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if(count($items) === 0): ?>
        <div class="alert alert-info">Keranjang Anda kosong. <a href="<?php echo BASE_URL; ?>">Kembali berbelanja</a></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $it): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if(!empty($it['image'])): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($it['image']); ?>" alt="" style="width:64px;height:64px;object-fit:cover;" class="me-2">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($it['product_name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>Rp <?php echo number_format($it['price'],0,',','.'); ?></td>
                            <td>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="item_id" value="<?php echo $it['cart_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $it['quantity']; ?>" min="1" class="form-control me-2" style="width:100px">
                                    <button name="update_qty" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                            <td>Rp <?php echo number_format(($it['price'] * $it['quantity']),0,',','.'); ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="item_id" value="<?php echo $it['cart_id']; ?>">
                                    <button name="remove_item" class="btn btn-sm btn-danger" onclick="return confirm('Hapus item ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            <div class="card p-3" style="width:320px;">
                <h5>Ringkasan</h5>
                <p class="mb-1">Subtotal: <strong>Rp <?php echo number_format($subtotal,0,',','.'); ?></strong></p>
                <p class="text-muted small">Biaya pengiriman dan pajak akan dihitung saat checkout.</p>
                <a href="<?php echo BASE_URL; ?>/checkout.php" class="btn btn-success w-100">Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
