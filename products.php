<?php
/**
 * Products Listing Page
 * Tampilkan semua produk dengan filter kategori dan search
 */

session_start();
require_once 'includes/config.php';

// Get query parameters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

// Build query
$where = "WHERE p.status = 'active'";
$params = [];

if (!empty($search)) {
    $where .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category)) {
    $where .= " AND c.slug = ?";
    $params[] = $category;
}

// Sort options
$order = match($sort) {
    'price_low' => 'ORDER BY p.price ASC',
    'price_high' => 'ORDER BY p.price DESC',
    'popular' => 'ORDER BY p.views DESC',
    'rating' => 'ORDER BY p.rating DESC',
    default => 'ORDER BY p.created_at DESC'
};

// Count total
$count_query = "SELECT COUNT(*) as total FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                $where";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get products
$offset = ($page - 1) * $per_page;
$query = "SELECT p.*, s.shop_name, s.shop_slug, c.name as category_name 
          FROM products p 
          JOIN shops s ON p.shop_id = s.id
          LEFT JOIN categories c ON p.category_id = c.id
          $where
          $order
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories untuk filter — fallback if sort_order column missing
$categories_sql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC";
try {
    $stmt = $pdo->prepare($categories_sql);
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Categories query failed (products.php): ' . $e->getMessage());
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Kolosal Marketplace</title>
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
        
        .sidebar {
            position: sticky;
            top: 20px;
        }
        
        .filter-group {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .filter-group h5 {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .filter-item {
            margin-bottom: 10px;
        }
        
        .filter-item label {
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
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
            height: 180px;
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
            font-size: 0.95rem;
        }
        
        .product-shop {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 8px;
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .product-rating {
            font-size: 0.85rem;
            color: #FFC107;
            margin-bottom: 10px;
        }
        
        .btn-detail {
            background-color: var(--primary);
            border: none;
            border-radius: 5px;
            padding: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: auto;
        }
        
        .btn-detail:hover {
            background-color: #E55555;
            color: white;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
        
        .page-title {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 20px;
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
        
        .pagination .page-link {
            color: var(--primary);
        }
        
        .pagination .page-link.active {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .sort-select {
            border-radius: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
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
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>/products.php">Produk</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/cart.php">
                                <i class="fas fa-shopping-cart"></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item active">Produk</li>
                </ol>
            </nav>
            
            <h1 class="page-title">Daftar Produk</h1>
            
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <div class="sidebar">
                        <!-- Search -->
                        <div class="filter-group">
                            <form method="GET" class="d-flex gap-2">
                                <input type="text" class="form-control" name="search" placeholder="Cari produk..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                            </form>
                        </div>
                        
                        <!-- Categories Filter -->
                        <div class="filter-group">
                            <h5>Kategori</h5>
                            <div class="filter-item">
                                <a href="<?php echo BASE_URL; ?>/products.php" class="text-decoration-none">
                                    <?php echo empty($category) ? '<strong>' : ''; ?>
                                    Semua Kategori
                                    <?php echo empty($category) ? '</strong>' : ''; ?>
                                </a>
                            </div>
                            <?php foreach ($categories as $cat): ?>
                            <div class="filter-item">
                                <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $cat['slug']; ?>" class="text-decoration-none">
                                    <?php echo $category === $cat['slug'] ? '<strong>' : ''; ?>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                    <?php echo $category === $cat['slug'] ? '</strong>' : ''; ?>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Sort -->
                        <div class="filter-group">
                            <h5>Urutkan</h5>
                            <form method="GET">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                <select class="form-select sort-select" name="sort" onchange="this.form.submit()">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Paling Populer</option>
                                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Harga Terendah</option>
                                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating Tertinggi</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Products -->
                <div class="col-lg-9">
                    <?php if (!empty($products)): ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-image fa-3x" style="color: #ccc;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h6 class="product-name"><?php echo htmlspecialchars(substr($product['product_name'], 0, 40)); ?></h6>
                                <div class="product-shop"><?php echo htmlspecialchars($product['shop_name']); ?></div>
                                <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i> <?php echo round($product['rating'], 1); ?> (<?php echo $product['sales']; ?> terjual)
                                </div>
                                <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-detail btn-primary mt-auto">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Sebelumnya</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Berikutnya</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>Tidak ada produk</h4>
                        <p>Coba ubah filter atau cari dengan keyword berbeda</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="text-center">
                <p>&copy; 2024 Kolosal - Marketplace UMKM Indonesia. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
