<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Cek apakah user punya toko
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if(!$shop) {
    header("Location: buat_toko.php");
    exit;
}

// Handle status toko
$shop_status = $shop['status'];
$status_message = '';
$status_class = '';
$verification_info = '';

switch($shop_status) {
    case 'pending':
        $status_message = 'Toko Anda sedang dalam proses verifikasi';
        $status_class = 'warning';
        $verification_info = 'Proses verifikasi membutuhkan waktu 1-3 hari kerja.';
        if($shop['verification_documents']) {
            $docs = json_decode($shop['verification_documents'], true);
            $verification_info .= ' Anda telah mengupload ' . count($docs) . ' dokumen verifikasi.';
        }
        break;
    case 'verified':
        $status_message = 'Toko Anda sudah diverifikasi';
        $status_class = 'success';
        $verification_info = 'Toko Anda aktif dan dapat beroperasi.';
        if($shop['verified_at']) {
            $verified_date = date('d M Y H:i', strtotime($shop['verified_at']));
            $verification_info .= ' Terverifikasi pada: ' . $verified_date;
        }
        break;
    case 'rejected':
        $status_message = 'Toko Anda ditolak';
        $status_class = 'danger';
        $verification_info = 'Mohon hubungi admin untuk informasi lebih lanjut.';
        break;
}

// Hitung statistik toko jika sudah verified (VERSI SEDERHANA TANPA ERROR)
$stats = [];
if($shop_status === 'verified') {
    // Hitung total produk (menggunakan status 'active' konsisten dengan skema)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE shop_id = ? AND status = 'active'");
    $stmt->execute([$shop['id']]);
    $stats['total_products'] = $stmt->fetch()['total'];
    
    // Sementara set nilai default untuk orders dan revenue
    // (Nanti bisa diaktifkan setelah tabel orders dibuat)
    $stats['total_orders'] = 0;
    $stats['total_revenue'] = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Toko - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .shop-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .shop-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .action-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            border-left: 4px solid;
        }
        
        .action-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .shop-info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .pending-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .pending-modal {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pending-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        
        .verification-progress {
            margin: 2rem 0;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 2rem;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 600;
        }
        
        .step.active .step-number {
            background: #0d6efd;
            color: white;
        }
        
        .step-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .step.active .step-label {
            color: #0d6efd;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 10px 10px 0 0;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #495057;
            background: #f8f9fa;
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
        }
        
        .tab-content {
            background: white;
            border-radius: 0 15px 15px 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        /* Dropdown Custom Styles */
        .dropdown-toggle {
            transition: all 0.3s ease;
        }
        
        .dropdown-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.1);
            animation: dropdownFade 0.3s ease;
        }
        
        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin: 0.25rem 0.5rem;
            width: calc(100% - 1rem);
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .custom-toast {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-left: 4px solid;
            animation: toastSlide 0.3s ease;
            overflow: hidden;
        }
        
        @keyframes toastSlide {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-success {
            border-left-color: #28a745;
        }
        
        .toast-error {
            border-left-color: #dc3545;
        }
        
        .toast-info {
            border-left-color: #17a2b8;
        }
        
        @media (max-width: 768px) {
            .shop-logo {
                width: 80px;
                height: 80px;
            }
            
            .pending-modal {
                padding: 2rem 1.5rem;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 1rem;
            }
            
            .progress-steps::before {
                display: none;
            }
            
            .step {
                display: flex;
                align-items: center;
                gap: 1rem;
                text-align: left;
            }
            
            .step-number {
                margin: 0;
            }
            
            .nav-tabs .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            
            .dropdown-menu {
                min-width: 200px;
                right: 0;
                left: auto;
            }
        }
        
        @media (max-width: 576px) {
            .dropdown-toggle {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .dropdown-menu {
                position: static !important;
                transform: none !important;
                width: 100%;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-0">
    <!-- Shop Header -->
    <div class="shop-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-auto text-center text-md-start mb-3 mb-md-0">
                    <?php if($shop['shop_logo']): ?>
                        <img src="<?php echo BASE_URL; ?>/uploads/shop_logos/<?php echo htmlspecialchars($shop['shop_logo']); ?>" 
                             alt="<?php echo htmlspecialchars($shop['shop_name']); ?>"
                             class="shop-logo"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/img/shop-default.png'">
                    <?php else: ?>
                        <div class="shop-logo bg-white d-flex align-items-center justify-content-center">
                            <i class="fas fa-store text-primary" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md">
                    <h1 class="h2 mb-2"><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="status-badge bg-<?php echo $status_class; ?>">
                            <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i>
                            <?php echo ucfirst($shop_status); ?>
                        </span>
                        <?php if($shop_status === 'verified'): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Terverifikasi
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="mb-0 opacity-75"><?php echo htmlspecialchars($shop['shop_description'] ?: 'Toko belum memiliki deskripsi'); ?></p>
                </div>
                <div class="col-md-auto text-center text-md-end">
                    <!-- Dropdown Menu -->
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" 
                                id="shopDropdownMenu" data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <i class="fas fa-ellipsis-v me-1"></i> Menu
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="shopDropdownMenu">
                            <?php if($shop_status === 'verified'): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/shop/detail.php?slug=<?php echo $shop['shop_slug']; ?>" target="_blank">
                                        <i class="fas fa-external-link-alt me-2"></i>Lihat Toko
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="#dashboard" data-bs-toggle="tab">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <?php if($shop_status === 'verified'): ?>
                                <li>
                                    <a class="dropdown-item" href="#products" data-bs-toggle="tab">
                                        <i class="fas fa-box me-2"></i>Produk
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#orders" data-bs-toggle="tab">
                                        <i class="fas fa-shopping-cart me-2"></i>Pesanan
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="#settings" data-bs-toggle="tab">
                                    <i class="fas fa-cog me-2"></i>Pengaturan
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="confirmDelete()">
                                    <i class="fas fa-trash me-2"></i>Hapus Toko
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Overlay (Hanya tampil jika status pending) -->
    <?php if($shop_status === 'pending'): ?>
    <div class="pending-overlay" id="pendingOverlay">
        <div class="pending-modal">
            <div class="pending-icon text-warning">
                <i class="fas fa-clock"></i>
            </div>
            <h3 class="mb-3">Toko Sedang Diverifikasi</h3>
            <p class="text-muted mb-4"><?php echo $verification_info; ?></p>
            
            <!-- Progress Steps -->
            <div class="verification-progress">
                <div class="progress-steps">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-label">Pendaftaran</div>
                    </div>
                    <div class="step active">
                        <div class="step-number">2</div>
                        <div class="step-label">Dokumen Diterima</div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-label">Dalam Review</div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-label">Selesai</div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Estimasi Waktu:</strong> 1-3 hari kerja
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-primary" onclick="closePendingOverlay()">
                    <i class="fas fa-check me-2"></i>Mengerti, Lanjutkan
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>'">
                    <i class="fas fa-home me-2"></i>Kembali ke Beranda
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container py-4">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="shopTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </button>
            </li>
            <?php if($shop_status === 'verified'): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                        <i class="fas fa-box me-2"></i>Produk
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                        <i class="fas fa-shopping-cart me-2"></i>Pesanan
                    </button>
                </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                    <i class="fas fa-cog me-2"></i>Pengaturan
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="shopTabContent">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- Status Info -->
                <?php if($shop_status !== 'verified'): ?>
                    <div class="alert alert-<?php echo $status_class; ?> alert-dismissible fade show mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-<?php echo $status_class === 'warning' ? 'clock' : ($status_class === 'danger' ? 'times-circle' : 'check-circle'); ?> me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h5 class="alert-heading mb-2"><?php echo $status_message; ?></h5>
                                <p class="mb-0"><?php echo $verification_info; ?></p>
                            </div>
                        </div>
                        <?php if($shop_status === 'pending' && $shop['verification_documents']): ?>
                            <div class="mt-3">
                                <h6><i class="fas fa-file-alt me-2"></i>Dokumen yang Diupload:</h6>
                                <?php 
                                $docs = json_decode($shop['verification_documents'], true);
                                if($docs): ?>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <?php foreach($docs as $doc): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-file me-1"></i>
                                                <?php echo htmlspecialchars($doc); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($shop_status === 'verified'): ?>
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                                <div class="stat-label">Total Produk</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                                <div class="stat-label">Total Pesanan</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="stat-value">Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></div>
                                <div class="stat-label">Total Pendapatan</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="row">
                    <?php if($shop_status === 'verified'): ?>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="action-card border-left-primary">
                                <div class="action-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <h5 class="text-center mb-3">Tambah Produk</h5>
                                <p class="text-center text-muted small mb-3">Tambahkan produk baru ke toko Anda</p>
                                <a href="<?php echo BASE_URL; ?>/product/create.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Tambah
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="action-card border-left-success">
                                <div class="action-icon bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-list"></i>
                                </div>
                                <h5 class="text-center mb-3">Kelola Produk</h5>
                                <p class="text-center text-muted small mb-3">Lihat dan kelola semua produk</p>
                                <a href="<?php echo BASE_URL; ?>/product/kelola_produk.php" class="btn btn-success w-100">
                                    <i class="fas fa-list me-2"></i>Kelola
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="action-card border-left-warning">
                                <div class="action-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h5 class="text-center mb-3">Pesanan</h5>
                                <p class="text-center text-muted small mb-3">Kelola pesanan pelanggan</p>
                                <a href="<?php echo BASE_URL; ?>/orders.php" class="btn btn-warning w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Lihat
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="action-card border-left-info">
                                <div class="action-icon bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h5 class="text-center mb-3">Analytics</h5>
                                <p class="text-center text-muted small mb-3">Lihat statistik toko Anda</p>
                                <a href="<?php echo BASE_URL; ?>/shop/analytics.php" class="btn btn-info w-100">
                                    <i class="fas fa-chart-line me-2"></i>Analytics
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Actions for non-verified shops -->
                        <div class="col-lg-4 mb-3">
                            <div class="action-card border-left-secondary">
                                <div class="action-icon bg-secondary bg-opacity-10 text-secondary">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <h5 class="text-center mb-3">Status Verifikasi</h5>
                                <p class="text-center text-muted small mb-3">Cek status verifikasi toko Anda</p>
                                <div class="text-center">
                                    <span class="badge bg-<?php echo $status_class; ?> p-2">
                                        <?php echo ucfirst($shop_status); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 mb-3">
                            <div class="action-card border-left-secondary">
                                <div class="action-icon bg-secondary bg-opacity-10 text-secondary">
                                    <i class="fas fa-question-circle"></i>
                                </div>
                                <h5 class="text-center mb-3">Bantuan</h5>
                                <p class="text-center text-muted small mb-3">Butuh bantuan mengenai verifikasi?</p>
                                <a href="#" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-headset me-2"></i>Hubungi Support
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 mb-3">
                            <div class="action-card border-left-secondary">
                                <div class="action-icon bg-secondary bg-opacity-10 text-secondary">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <h5 class="text-center mb-3">Edit Profil</h5>
                                <p class="text-center text-muted small mb-3">Edit informasi toko Anda</p>
                                <a href="#settings" class="btn btn-outline-secondary w-100" data-bs-toggle="tab">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Shop Information -->
                <div class="shop-info-card mt-4">
                    <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>Informasi Toko</h4>
                    
                    <div class="info-item">
                        <div class="info-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Nama Toko</div>
                            <div class="info-value"><?php echo htmlspecialchars($shop['shop_name']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Slug Toko</div>
                            <div class="info-value"><?php echo htmlspecialchars($shop['shop_slug']); ?></div>
                        </div>
                    </div>
                    
                    <?php if($shop['shop_description']): ?>
                    <div class="info-item">
                        <div class="info-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-align-left"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Deskripsi</div>
                            <div class="info-value"><?php echo htmlspecialchars($shop['shop_description']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Alamat</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($shop['shop_address'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Telepon</div>
                            <div class="info-value"><?php echo htmlspecialchars($shop['shop_phone']); ?></div>
                        </div>
                    </div>
                    
                    <?php if($shop['shop_email']): ?>
                    <div class="info-item">
                        <div class="info-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($shop['shop_email']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-icon bg-dark bg-opacity-10 text-dark">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Tanggal Pendaftaran</div>
                            <div class="info-value">
                                <?php echo date('d M Y H:i', strtotime($shop['created_at'])); ?>
                                (<?php echo time_ago($shop['created_at']); ?>)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Tab (Only for verified shops) -->
            <?php if($shop_status === 'verified'): ?>
            <div class="tab-pane fade" id="products" role="tabpanel">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-box-open text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3">Kelola Produk</h4>
                    <p class="text-muted mb-4">Halaman untuk mengelola produk toko Anda</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo BASE_URL; ?>/product/create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Produk Baru
                        </a>
                        <a href="../product/kelola_produk.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Lihat Semua Produk
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Orders Tab (Only for verified shops) -->
            <div class="tab-pane fade" id="orders" role="tabpanel">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-shopping-bag text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3">Kelola Pesanan</h4>
                    <p class="text-muted mb-4">Halaman untuk mengelola pesanan dari pelanggan</p>
                    <a href="pesanan.php" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Buka Halaman Pesanan
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <h4 class="mb-4"><i class="fas fa-cog me-2"></i>Pengaturan Toko</h4>
                
                <?php if($shop_status === 'pending'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Anda hanya dapat mengedit informasi toko saat ini. Fitur lainnya akan tersedia setelah toko diverifikasi.
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="fas fa-store me-2"></i>Informasi Toko</h5>
                                <a href="edit_toko.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-edit me-2"></i>Edit Informasi Toko
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="fas fa-image me-2"></i>Logo & Banner</h5>
                                <a href="edit_logo.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-camera me-2"></i>Ubah Logo & Banner
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($shop_status === 'verified'): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-3"><i class="fas fa-shield-alt me-2"></i>Keamanan</h5>
                                    <a href="keamanan.php" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-lock me-2"></i>Pengaturan Keamanan
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-3"><i class="fas fa-trash me-2"></i>Hapus Toko</h5>
                                    <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDelete()">
                                        <i class="fas fa-trash me-2"></i>Hapus Toko
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if($shop_status === 'pending'): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-question-circle me-2"></i>Pertanyaan Umum</h5>
                        <div class="mt-3">
                            <h6><i class="fas fa-clock me-2"></i>Berapa lama proses verifikasi?</h6>
                            <p class="mb-2">Proses verifikasi membutuhkan waktu 1-3 hari kerja sejak pendaftaran.</p>
                            
                            <h6><i class="fas fa-envelope me-2"></i>Bagaimana jika verifikasi ditolak?</h6>
                            <p class="mb-2">Anda akan mendapatkan notifikasi email dan dapat mengajukan kembali dengan data yang benar.</p>
                            
                            <h6><i class="fas fa-headset me-2"></i>Butuh bantuan?</h6>
                            <p class="mb-0">Hubungi support kami di <a href="mailto:support@kolosal.com">support@kolosal.com</a> atau telepon (021) 1234-5678.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Function to close pending overlay
function closePendingOverlay() {
    const overlay = document.getElementById('pendingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
        localStorage.setItem('pendingOverlayClosed', 'true');
    }
}

// Inisialisasi semua komponen Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - Initializing Bootstrap components');
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize dropdowns - cara yang benar
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    console.log('Found dropdowns:', dropdownElementList.length);
    
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        console.log('Initializing dropdown:', dropdownToggleEl.id || dropdownToggleEl.className);
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Handle pending overlay
    const pendingOverlay = document.getElementById('pendingOverlay');
    if(pendingOverlay) {
        const overlayClosed = localStorage.getItem('pendingOverlayClosed');
        if(overlayClosed === 'true') {
            pendingOverlay.style.display = 'none';
        } else {
            pendingOverlay.style.display = 'flex';
        }
    }
    
    // Tab handling with Bootstrap
    const tabTriggers = [].slice.call(document.querySelectorAll('button[data-bs-toggle="tab"]'));
    tabTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            const target = this.getAttribute('data-bs-target');
            const tab = new bootstrap.Tab(this);
            tab.show();
            
            // Update URL hash
            if (target) {
                const hash = target.substring(1);
                history.pushState(null, null, '#' + hash);
            }
        });
    });
    
    // Restore tab from URL hash
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        const triggerEl = document.querySelector(`button[data-bs-target="#${hash}"]`);
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
    
    // Test dropdown functionality
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    if(dropdownToggle) {
        dropdownToggle.addEventListener('click', function(e) {
            console.log('Dropdown clicked');
        });
        
        dropdownToggle.addEventListener('show.bs.dropdown', function(e) {
            console.log('Dropdown opening');
        });
        
        dropdownToggle.addEventListener('shown.bs.dropdown', function(e) {
            console.log('Dropdown opened');
        });
    }
});

// Function to confirm shop deletion
function confirmDelete() {
    if(confirm('Apakah Anda yakin ingin menghapus toko? Tindakan ini tidak dapat dibatalkan dan semua produk akan dihapus.')) {
        // AJAX request to delete shop
        fetch('hapus_toko.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete'
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showToast('Toko berhasil dihapus', 'success');
                setTimeout(() => {
                    window.location.href = '<?php echo BASE_URL; ?>';
                }, 2000);
            } else {
                showToast('Gagal menghapus toko: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Terjadi kesalahan saat menghapus toko', 'error');
        });
    }
}

// Toast notification function
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        console.error('Toast container not found');
        return;
    }
    
    const toastId = 'toast-' + Date.now();
    const toastClass = type === 'success' ? 'toast-success' : 
                      type === 'error' ? 'toast-error' : 'toast-info';
    
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `custom-toast ${toastClass} mb-3`;
    toast.innerHTML = `
        <div class="toast-body d-flex align-items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                         type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} 
               me-3" style="font-size: 1.5rem;"></i>
            <div class="flex-grow-1">
                <strong>${type === 'success' ? 'Sukses!' : 
                         type === 'error' ? 'Error!' : 'Info'}</strong>
                <div class="text-muted">${message}</div>
            </div>
            <button type="button" class="btn-close ms-3" onclick="document.getElementById('${toastId}').remove()"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (document.getElementById(toastId)) {
            toast.style.animation = 'toastSlide 0.3s ease reverse';
            setTimeout(() => {
                if (document.getElementById(toastId)) {
                    document.getElementById(toastId).remove();
                }
            }, 300);
        }
    }, 5000);
}

// Helper function untuk manual dropdown (fallback)
function toggleDropdown(button) {
    const dropdown = button.closest('.dropdown');
    const menu = dropdown.querySelector('.dropdown-menu');
    
    if (menu.classList.contains('show')) {
        menu.classList.remove('show');
        button.setAttribute('aria-expanded', 'false');
    } else {
        menu.classList.add('show');
        button.setAttribute('aria-expanded', 'true');
        
        // Close when clicking outside
        const closeDropdown = function(e) {
            if (!dropdown.contains(e.target)) {
                menu.classList.remove('show');
                button.setAttribute('aria-expanded', 'false');
                document.removeEventListener('click', closeDropdown);
            }
        };
        
        setTimeout(() => {
            document.addEventListener('click', closeDropdown);
        }, 100);
    }
}

// Fallback for dropdown if Bootstrap doesn't work
document.addEventListener('DOMContentLoaded', function() {
    // Check if dropdowns are working
    setTimeout(() => {
        const dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(dropdown => {
            if (!dropdown.__bootstrap) {
                console.warn('Bootstrap dropdown not initialized, using fallback');
                dropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleDropdown(this);
                });
            }
        });
    }, 1000);
});
</script>

<?php 
// Helper function to show time ago
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' detik yang lalu';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        return date('d M Y', $time);
    }
}

include '../includes/footer.php'; 
?>
</body>
</html>