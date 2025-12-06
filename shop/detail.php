<?php
include '../includes/config.php';
include '../includes/header.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">Toko tidak ditemukan.</div>';
    echo '</div>';
    include '../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM shops WHERE shop_slug = ? LIMIT 1");
$stmt->execute([$slug]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">Toko tidak ditemukan.</div>';
    echo '</div>';
    include '../includes/footer.php';
    exit;
}

// Fetch published products for this shop
$stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.shop_id = ? AND p.status = 'published' ORDER BY p.created_at DESC");
$stmt->execute([$shop['id']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Toko - ' . htmlspecialchars($shop['shop_name']);
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($shop['shop_name']); ?></li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-4 mb-4">
        <div>
            <?php if ($shop['shop_logo']): ?>
                <img src="<?php echo BASE_URL; ?>/uploads/shop_logos/<?php echo htmlspecialchars($shop['shop_logo']); ?>" alt="<?php echo htmlspecialchars($shop['shop_name']); ?>" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e9ecef;" onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
            <?php else: ?>
                <div style="width:120px;height:120px;background:#f1f3f5;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#6c757d;">No Logo</div>
            <?php endif; ?>
        </div>
        <div>
            <h2 class="mb-1"><?php echo htmlspecialchars($shop['shop_name']); ?></h2>
            <p class="text-muted mb-1"><?php echo nl2br(htmlspecialchars($shop['shop_description'])); ?></p>
            <div class="small text-muted">
                <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($shop['shop_address']); ?></div>
                <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($shop['shop_phone']); ?></div>
                <?php if ($shop['shop_email']): ?><div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($shop['shop_email']); ?></div><?php endif; ?>
            </div>
        </div>

        <div class="ms-auto">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $shop['user_id']): ?>
                <a href="<?php echo BASE_URL; ?>/shop/kelola_toko.php" class="btn btn-outline-primary">Kelola Toko</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/products.php?shop=<?php echo urlencode($shop['shop_slug']); ?>" class="btn btn-outline-secondary">Lihat Semua Produk</a>
            <?php endif; ?>
        </div>
    </div>

    <h4 class="mb-3">Produk dari <?php echo htmlspecialchars($shop['shop_name']); ?></h4>
    <?php if (empty($products)): ?>
        <div class="alert alert-info">Belum ada produk yang dipublikasikan di toko ini.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach($products as $p):
                $images = [];
                if (!empty($p['images'])) {
                    $images = json_decode($p['images'], true);
                }
                $thumb = $images && is_array($images) && count($images) ? $images[0] : '';
            ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $p['id']; ?>">
                            <?php if ($thumb): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($thumb); ?>" class="card-img-top" style="height:180px;object-fit:cover;" onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                            <?php else: ?>
                                <img src="<?php echo BASE_URL; ?>/assets/img/no-image.jpg" class="card-img-top" style="height:180px;object-fit:cover;">
                            <?php endif; ?>
                        </a>
                        <div class="card-body p-2">
                            <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-dark">
                                <h6 class="mb-1 small fw-bold"><?php echo htmlspecialchars($p['product_name']); ?></h6>
                            </a>
                            <div class="text-primary fw-bold small"><?php echo formatPrice($p['price']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
