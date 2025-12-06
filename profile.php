<?php
include 'includes/config.php';
include 'includes/header.php';
include 'includes/auth_check.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, email, full_name, phone FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user already has a shop
$shop = null;
$stmt = $pdo->prepare("SELECT id, shop_name, shop_slug, status FROM shops WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Profil Saya';
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Profil Saya</h5>
                </div>
                <div class="card-body">
                    <?php if(!$user): ?>
                        <div class="alert alert-warning">Data pengguna tidak ditemukan.</div>
                    <?php else: ?>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Nama Lengkap:</strong> <?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Telepon:</strong> <?php echo htmlspecialchars($user['phone'] ?? '-'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Toko Saya</h6>
                </div>
                <div class="card-body text-center">
                    <?php if($shop): ?>
                        <p class="mb-2"><strong><?php echo htmlspecialchars($shop['shop_name']); ?></strong></p>
                        <p class="text-muted mb-3">Status: <?php echo htmlspecialchars($shop['status']); ?></p>
                        <a href="<?php echo BASE_URL; ?>/shop/kelola_toko.php" class="btn btn-primary w-100">Kelola Toko</a>
                    <?php else: ?>
                        <p class="text-muted mb-3">Anda belum memiliki toko.</p>
                        <a href="<?php echo BASE_URL; ?>/shop/buat_toko.php" class="btn btn-success w-100">Buat Toko Sekarang</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
