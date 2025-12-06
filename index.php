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

// Ambil featured products
$sql = "SELECT p.*, s.shop_name, s.shop_slug FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status = 'published' ORDER BY p.created_at DESC LIMIT 12";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Index query failed: " . $e->getMessage());
    $stmt = $pdo->prepare("SELECT p.*, s.shop_name, s.shop_slug FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status = 'published' ORDER BY p.created_at DESC LIMIT 12");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
}

// Ambil categories
$categories_sql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC LIMIT 6";
try {
    $stmt = $pdo->prepare($categories_sql);
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories query failed: " . $e->getMessage());
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC LIMIT 6");
    $stmt->execute();
    $categories = $stmt->fetchAll();
}

// Function untuk mendapatkan icon berdasarkan kategori
function getCategoryIcon($category_name) {
    $icons = [
        'elektronik' => 'fas fa-laptop',
        'pakaian' => 'fas fa-tshirt',
        'makanan' => 'fas fa-utensils',
        'olahraga' => 'fas fa-dumbbell',
        'kesehatan' => 'fas fa-heartbeat',
        'buku' => 'fas fa-book',
        'mainan' => 'fas fa-gamepad',
        'perhiasan' => 'fas fa-gem',
        'rumah' => 'fas fa-home',
        'kendaraan' => 'fas fa-car',
        'kantor' => 'fas fa-briefcase',
        'kecantikan' => 'fas fa-spa'
    ];
    
    $category_lower = strtolower($category_name);
    
    foreach ($icons as $key => $icon) {
        if (strpos($category_lower, $key) !== false) {
            return $icon;
        }
    }
    
    // Default icon
    return 'fas fa-cube';
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
        
        .product-images {
            width: 100%;
            height: 200px;
            background: #eee;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-images img {
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
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
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
        
        /* STYLE BARU UNTUK KATEGORI YANG LEBIH RESPONSIVE */
        .categories-section {
            padding: 40px 0;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
        }
        
        .category-item {
            background: white;
            border-radius: 10px;
            padding: 20px 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid #e0e0e0;
            height: 100%;
            min-height: 140px;
            text-decoration: none;
            color: inherit;
        }
        
        .category-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: var(--primary);
            text-decoration: none;
            color: inherit;
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        
        .category-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .category-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
            margin: 0;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            max-height: 2.6em;
        }
        
        /* Responsive untuk kategori */
        @media (max-width: 1200px) {
            .category-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }
            
            .category-item {
                padding: 15px 10px;
                min-height: 120px;
            }
            
            .category-icon {
                width: 40px;
                height: 40px;
                margin-bottom: 8px;
            }
            
            .category-icon i {
                font-size: 1.2rem;
            }
            
            .category-name {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .category-item {
                padding: 12px 8px;
                min-height: 110px;
            }
            
            .category-icon {
                width: 36px;
                height: 36px;
                margin-bottom: 6px;
            }
            
            .category-icon i {
                font-size: 1rem;
            }
            
            .category-name {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 380px) {
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            
            .category-item {
                padding: 10px 6px;
                min-height: 100px;
            }
            
            .category-icon {
                width: 32px;
                height: 32px;
                margin-bottom: 5px;
            }
            
            .category-icon i {
                font-size: 0.9rem;
            }
            
            .category-name {
                font-size: 0.7rem;
            }
        }
        
        /* Style untuk produk (tambahan responsif) */
        @media (max-width: 1200px) {
            .product-images {
                height: 180px;
            }
        }
        
        @media (max-width: 768px) {
            .product-images {
                height: 160px;
            }
            
            .product-name {
                font-size: 0.9rem;
                min-height: 36px;
            }
            
            .product-price {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section {
                padding: 40px 0;
            }
            
            .hero-section h1 {
                font-size: 1.8rem;
            }
            
            .hero-section p {
                font-size: 1rem;
            }
            
            .product-images {
                height: 140px;
            }
            
            .product-info {
                padding: 12px;
            }
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
            position: relative;
            padding-bottom: 15px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
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
        
        /* Perbaikan untuk grid produk */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        
        /* Search form styling */
        .search-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .search-form .form-control {
            border-radius: 50px 0 0 50px;
            border: none;
            padding: 12px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-form .btn {
            border-radius: 0 50px 50px 0;
            padding: 12px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Footer improvements */
        footer {
            margin-top: 60px;
        }
        
        footer a {
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: var(--secondary) !important;
        }
        
        .footer-social a {
            display: inline-block;
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 36px;
            margin-right: 8px;
            transition: all 0.3s ease;
        }
        
        .footer-social a:hover {
            background: var(--primary);
            transform: translateY(-3px);
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
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>">Home</a>
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
            <form class="search-form d-flex">
                <input type="text" class="form-control" placeholder="Cari produk, kategori, atau toko...">
                <button type="submit" class="btn btn-light"><i class="fas fa-search"></i> Cari</button>
            </form>
        </div>
    </div>

    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Kategori Populer</h2>
            <div class="category-grid">
                <?php foreach ($categories as $cat): ?>
                <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $cat['slug']; ?>" class="category-item">
                    <div class="category-icon">
                        <i class="<?php echo getCategoryIcon($cat['name']); ?>"></i>
                    </div>
                    <h5 class="category-name"><?php echo htmlspecialchars($cat['name']); ?></h5>
                </a>
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
            <div class="products-grid">
                <?php foreach ($featured_products as $product): 
                    // Decode images jika dalam format JSON
                    $images = $product['images'];
                    if (is_string($images) && strpos($images, '[') === 0) {
                        $images_array = json_decode($images, true);
                        $first_image = !empty($images_array) ? $images_array[0] : '';
                    } else {
                        $first_image = $images;
                    }
                ?>
                <div class="product-card">
                    <div class="product-images">
                        <?php if ($first_image): ?>
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($first_image); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                        <?php else: ?>
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                <i class="fas fa-image fa-3x" style="color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h6 class="product-name" title="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </h6>
                        <div class="product-shop">
                            <i class="fas fa-store"></i> <?php echo htmlspecialchars($product['shop_name']); ?>
                        </div>
                        <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                        <div class="product-rating">
                            <i class="fas fa-star"></i> 
                            <?php 
                                $rating = $product['rating'] ?? 0;
                                echo number_format($rating, 1);
                            ?>
                            <span class="text-muted ms-1">(<?php echo $product['views'] ?? 0; ?> dilihat)</span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-sm btn-primary mt-auto">
                            <i class="fas fa-eye"></i> Lihat Detail
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>Belum ada produk featured</h4>
                <p>Produk unggulan akan segera hadir</p>
                <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] === 'seller' || $_SESSION['role'] === 'admin')): ?>
                    <a href="<?php echo BASE_URL; ?>/product/create.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- View All Products Button -->
            <?php if (!empty($featured_products)): ?>
            <div class="text-center mt-5">
                <a href="<?php echo BASE_URL; ?>/products.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i> Lihat Semua Produk
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stats Section (Optional) -->
    <section class="py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4">
                    <div class="display-4 fw-bold text-primary"><?php echo count($featured_products); ?>+</div>
                    <p class="text-muted">Produk Tersedia</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="display-4 fw-bold text-primary">50+</div>
                    <p class="text-muted">Toko Terdaftar</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="display-4 fw-bold text-primary">1K+</div>
                    <p class="text-muted">Transaksi Sukses</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="display-4 fw-bold text-primary">24/7</div>
                    <p class="text-muted">Dukungan Pelanggan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3"><i class="fas fa-store"></i> Kolosal</h5>
                    <p class="small text-white-50">Marketplace terpercaya untuk UMKM Indonesia. Belanja mudah, aman, dan nyaman.</p>
                    <div class="footer-social">
                        <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="small">Tentang Kami</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Tentang Kolosal</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Blog & Berita</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Karir</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Kebijakan Privasi</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="small">Bantuan</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Hubungi Kami</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">FAQ</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Syarat & Ketentuan</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Cara Berbelanja</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="small">Untuk Penjual</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/auth/register.php?type=seller" class="text-white-50 text-decoration-none">Daftar Jadi Seller</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Panduan Seller</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Komisi & Biaya</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Pusat Seller</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center small text-white-50">
                <p class="mb-0">&copy; 2024 Kolosal Marketplace. Semua hak dilindungi.</p>
                <p class="mb-0">Dibuat dengan <i class="fas fa-heart text-danger"></i> untuk UMKM Indonesia</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // JavaScript untuk interaksi tambahan
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll untuk link kategori
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Search form submission
        const searchForm = document.querySelector('.search-form');
        if(searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const searchInput = this.querySelector('input[type="text"]');
                if(searchInput.value.trim() !== '') {
                    window.location.href = '<?php echo BASE_URL; ?>/products.php?search=' + encodeURIComponent(searchInput.value.trim());
                }
            });
        }
        
        // Hover effect untuk category items
        const categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Product card hover effect
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
    
    // Fallback untuk gambar yang error
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                this.src = '<?php echo BASE_URL; ?>/assets/img/no-image.jpg';
                this.onerror = null; // Prevent infinite loop
            });
        });
    });
    </script>
</body>
</html>