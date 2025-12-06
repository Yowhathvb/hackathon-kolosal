<?php
include 'includes/config.php';
include 'includes/header.php';
include 'includes/auth_check.php';

$page_title = 'Pesanan Saya';
?>
<div class="container mt-4">
    <h2><i class="fas fa-shopping-bag me-2"></i>Pesanan Saya</h2>
    <div class="alert alert-info">Halaman pesanan belum diaktifkan sepenuhnya. Ini adalah placeholder sementara.</div>

    <?php
    // Jika tabel orders ada, tampilkan data sederhana
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll();
    } catch (Exception $e) {
        $orders = [];
    }

    if (empty($orders)): ?>
        <p class="text-muted">Belum ada pesanan yang ditemukan.</p>
    <?php else: ?>
        <div class="list-group">
            <?php foreach($orders as $o): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>Pesanan #<?php echo htmlspecialchars($o['order_number'] ?? $o['id']); ?></strong>
                            <div class="small text-muted">Tanggal: <?php echo htmlspecialchars($o['created_at']); ?></div>
                        </div>
                        <div>
                            <span class="badge bg-secondary">Rp <?php echo number_format($o['total_amount'] ?? 0,0,',','.'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
