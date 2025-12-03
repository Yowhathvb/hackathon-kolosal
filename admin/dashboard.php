<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Only admin
if(($_SESSION['role'] ?? '') != 'admin') {
    error_log('admin/dashboard.php: access denied, role=' . ($_SESSION['role'] ?? 'NULL'));
    header('Location: ' . BASE_URL);
    exit;
}

// Gather some quick stats
try {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $pending_shops = $pdo->query("SELECT COUNT(*) FROM shops WHERE status = 'pending'")->fetchColumn();
} catch(PDOException $e) {
    error_log('admin/dashboard.php: stats query failed: ' . $e->getMessage());
    $total_users = $total_products = $total_orders = $pending_shops = 0;
}
?>

<div class="container mt-4">
    <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>

    <div class="row mt-3">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <p class="card-text display-6"><?php echo (int)$total_users; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Products</h5>
                    <p class="card-text display-6"><?php echo (int)$total_products; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Orders</h5>
                    <p class="card-text display-6"><?php echo (int)$total_orders; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Shops Pending</h5>
                    <p class="card-text display-6"><?php echo (int)$pending_shops; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Recent Actions</div>
        <div class="card-body">
            <p class="text-muted">Quick admin panel — expand with management links (users, orders, products).</p>
            <a href="verifikasi_toko.php" class="btn btn-outline-primary">Verifikasi Toko</a>
            <a href="users.php" class="btn btn-outline-secondary ms-2">Manage Users</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
