<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$shop) {
    header('Location: ' . BASE_URL . '/shop/buat_toko.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle logo upload
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['shop_logo'], 'shop_logos');
        if ($upload['success']) {
            $stmt = $pdo->prepare("UPDATE shops SET shop_logo = ? WHERE id = ?");
            $stmt->execute([$upload['filename'], $shop['id']]);
            $_SESSION['success'] = 'Logo berhasil diperbarui.';
            header('Location: ' . BASE_URL . '/shop/kelola_toko.php');
            exit;
        } else {
            $error = $upload['error'];
        }
    }

    // Handle banner upload
    if (isset($_FILES['shop_banner']) && $_FILES['shop_banner']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['shop_banner'], 'shop_banners');
        if ($upload['success']) {
            $stmt = $pdo->prepare("UPDATE shops SET banner = ? WHERE id = ?");
            $stmt->execute([$upload['filename'], $shop['id']]);
            $_SESSION['success'] = 'Banner berhasil diperbarui.';
            header('Location: ' . BASE_URL . '/shop/kelola_toko.php');
            exit;
        } else {
            $error = $upload['error'];
        }
    }
}
?>
<div class="container mt-4">
    <h2>Ubah Logo & Banner</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Logo Toko</label>
            <input type="file" name="shop_logo" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Banner Toko</label>
            <input type="file" name="shop_banner" class="form-control">
        </div>
        <button class="btn btn-primary">Unggah</button>
        <a href="<?php echo BASE_URL; ?>/shop/kelola_toko.php" class="btn btn-link">Batal</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
