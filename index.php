<?php
/**
 * Ecommerce Platform - Homepage
 * Halaman utama dengan daftar produk featured dan kategori
 */

// session_start();
require_once 'includes/config.php';

// Debugging: Tampilkan error PHP jika ada
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil featured products — fallback: jika kolom is_featured tidak ada, tampilkan produk aktif terbaru
$sql = "SELECT p.*, s.shop_name, s.shop_slug FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 12";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika query gagal (mis. kolom is_featured tidak ada pada beberapa instalasi), fallback ke query lebih sederhana
    error_log("Index query failed: " . $e->getMessage());
    $stmt = $pdo->prepare("SELECT p.*, s.shop_name, s.shop_slug FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 12");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
}

// Ambil categories — beberapa instalasi mungkin tidak punya kolom sort_order, pakai fallback
$categories_sql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC LIMIT 6";
try {
    $stmt = $pdo->prepare($categories_sql);
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback: order by name jika kolom sort_order tidak ditemukan
    error_log("Categories query failed: " . $e->getMessage());
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC LIMIT 6");
    $stmt->execute();
    $categories = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolosal - Marketplace UMKM Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B6B;
            --secondary: #4ECDC4;
            --dark: #2C3E50;
            --light: #F7F9FC;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero-section p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            background: #eee;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
            min-height: 40px;
        }
        
        .product-shop {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .product-rating {
            font-size: 0.9rem;
            color: #FFC107;
            margin-bottom: 10px;
        }
        
        .category-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: scale(1.05);
        }
        
        .category-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .category-card h5 {
            color: var(--dark);
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #E55555;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-store"></i> Kolosal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/products.php">Produk</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/cart.php">
                                <i class="fas fa-shopping-cart"></i> Keranjang
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php">Profil</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/orders.php">Pesanan Saya</a></li>
                                <?php if ($_SESSION['role'] === 'seller' || $_SESSION['role'] === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/shop/kelola_toko.php">Kelola Toko</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/product/kelola_produk.php">Kelola Produk</a></li>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>Selamat Datang di Kolosal</h1>
            <p>Marketplace terpercaya untuk produk UMKM Indonesia</p>
            <form class="d-flex gap-2 justify-content-center">
                <input type="text" class="form-control" style="max-width: 400px" placeholder="Cari produk...">
                <button type="submit" class="btn btn-light"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Kategori Populer</h2>
            <div class="row g-4">
                <?php foreach ($categories as $cat): ?>
                <div class="col-md-2 col-sm-3 col-6">
                    <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $cat['slug']; ?>" class="text-decoration-none">
                        <div class="category-card">
                            <i class="fas fa-cube"></i>
                            <h5><?php echo htmlspecialchars($cat['name']); ?></h5>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <h2 class="section-title">Produk Unggulan</h2>
            <?php if (!empty($featured_products)): ?>
            <div class="row g-4">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-image fa-3x" style="color: #ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h6 class="product-name"><?php echo htmlspecialchars(substr($product['product_name'], 0, 50)); ?></h6>
                            <div class="product-shop"><?php echo htmlspecialchars($product['shop_name']); ?></div>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            <div class="product-rating">
                                <i class="fas fa-star"></i> <?php echo round($product['rating'], 1); ?>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary mt-auto">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>Belum ada produk featured</h4>
                <p>Produk unggulan akan segera hadir</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5><i class="fas fa-store"></i> Kolosal</h5>
                    <p class="small">Marketplace terpercaya untuk UMKM Indonesia</p>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="small">Tentang Kami</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-decoration-none text-white-50">Tentang Kolosal</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Blog</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Karir</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="small">Bantuan</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-decoration-none text-white-50">Hubungi Kami</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">FAQ</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Syarat & Ketentuan</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="small">Ikuti Kami</h5>
                    <div class="gap-2">
                        <a href="#" class="text-white-50 text-decoration-none"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white-50 text-decoration-none"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white-50 text-decoration-none"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center small text-white-50">
                &copy; 2024 Kolosal. Semua hak dilindungi.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
