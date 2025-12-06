<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Basic placeholder analytics page
$stmt = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();
if (!$shop) {
    header('Location: ' . BASE_URL . '/shop/buat_toko.php');
    exit;
}

$page_title = 'Analytics Toko';
?>
<div class="container mt-4">
    <h2><i class="fas fa-chart-line me-2"></i>Analytics Toko</h2>
    <div class="alert alert-info">Halaman analytics sederhana. Fitur lanjutan bisa diaktifkan setelah integrasi metrik.</div>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 mb-3">
                <h5>Total View</h5>
                <p class="display-6">-</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 mb-3">
                <h5>Total Penjualan</h5>
                <p class="display-6">-</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 mb-3">
                <h5>Pendapatan</h5>
                <p class="display-6">Rp 0</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
