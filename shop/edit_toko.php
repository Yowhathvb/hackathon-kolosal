<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Fetch shop
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$shop) {
    header('Location: ' . BASE_URL . '/shop/buat_toko.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_description = trim($_POST['shop_description'] ?? '');
    $shop_address = trim($_POST['shop_address'] ?? '');
    $shop_phone = trim($_POST['shop_phone'] ?? '');
    $shop_email = trim($_POST['shop_email'] ?? '');

    if ($shop_name === '') {
        $error = 'Nama toko tidak boleh kosong.';
    } else {
        $stmt = $pdo->prepare("UPDATE shops SET shop_name = ?, shop_description = ?, shop_address = ?, shop_phone = ?, shop_email = ? WHERE id = ?");
        $stmt->execute([$shop_name, $shop_description, $shop_address, $shop_phone, $shop_email, $shop['id']]);
        $_SESSION['success'] = 'Informasi toko berhasil diperbarui.';
        header('Location: ' . BASE_URL . '/shop/kelola_toko.php');
        exit;
    }
}

?>
<div class="container mt-4">
    <h2>Edit Informasi Toko</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nama Toko</label>
            <input type="text" name="shop_name" class="form-control" value="<?php echo htmlspecialchars($shop['shop_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="shop_description" class="form-control"><?php echo htmlspecialchars($shop['shop_description']); ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="shop_address" class="form-control"><?php echo htmlspecialchars($shop['shop_address']); ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Telepon</label>
                <input type="text" name="shop_phone" class="form-control" value="<?php echo htmlspecialchars($shop['shop_phone']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="shop_email" class="form-control" value="<?php echo htmlspecialchars($shop['shop_email']); ?>">
            </div>
        </div>
        <button class="btn btn-primary">Simpan Perubahan</button>
        <a href="<?php echo BASE_URL; ?>/shop/kelola_toko.php" class="btn btn-link">Batal</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
