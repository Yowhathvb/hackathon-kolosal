<?php
/**
 * Products Listing Page
 * Tampilkan semua produk dengan filter kategori dan search
 */

require_once 'includes/config.php';

// Get query parameters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

// Build query
$where = "WHERE p.status = 'published'";
$params = [];

if (!empty($search)) {
    $where .= " AND (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
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
    'popular' => 'ORDER BY p.views DESC, p.created_at DESC',
    'rating' => 'ORDER BY p.rating DESC, p.views DESC',
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
$query = "SELECT p.*, s.shop_name, s.shop_slug, c.name as category_name, c.slug as category_slug
          FROM products p 
          JOIN shops s ON p.shop_id = s.id
          LEFT JOIN categories c ON p.category_id = c.id
          $where
          $order
          LIMIT ? OFFSET ?";

$params_before_limit = $params;
$stmt = $pdo->prepare($query);

$idx = 1;
foreach ($params_before_limit as $pval) {
    $stmt->bindValue($idx, $pval);
    $idx++;
}

$stmt->bindValue($idx, (int)$per_page, PDO::PARAM_INT);
$idx++;
$stmt->bindValue($idx, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();

// Get categories untuk filter
$categories_sql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC";
try {
    $stmt = $pdo->prepare($categories_sql);
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Categories query failed (products.php): ' . $e->getMessage());
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
}

// Get random featured products
$featured_sql = "SELECT p.*, s.shop_name FROM products p 
                 JOIN shops s ON p.shop_id = s.id 
                 WHERE p.status = 'published' 
                 ORDER BY RAND() LIMIT 6";
$featured = $pdo->query($featured_sql)->fetchAll();

// Function untuk mendapatkan image pertama dari array JSON
function getFirstImage($images) {
    if (empty($images)) return '';
    
    if (is_string($images) && strpos($images, '[') === 0) {
        $images_array = json_decode($images, true);
        return !empty($images_array) ? $images_array[0] : '';
    }
    
    return $images;
}


// Set page title untuk header
$page_title = !empty($search) ? "Cari: $search" : (!empty($category) ? "Kategori: $category" : "Produk") . ' - Kolosal';
?>

<?php include 'includes/header.php'; ?>

<style>
    :root {
        --primary: #FF6B6B;
        --primary-dark: #e05555;
        --secondary: #4ECDC4;
        --dark: #2C3E50;
        --dark-light: #4A6572;
        --light: #F7F9FC;
        --gray: #E0E6ED;
        --gray-light: #F5F7FA;
        --success: #10B981;
        --warning: #F59E0B;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        background-color: var(--light);
        color: var(--dark);
        line-height: 1.6;
    }
    
    /* Header & Breadcrumb */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 20px 20px;
    }
    
    .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 1rem;
    }
    
    .breadcrumb-item a {
        color: rgba(255,255,255,0.9);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-item a:hover {
        color: white;
    }
    
    .breadcrumb-item.active {
        color: white;
        font-weight: 600;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        color: rgba(255,255,255,0.6);
    }
    
    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    
    .page-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        max-width: 600px;
    }
    
    .product-count {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }
    
    /* Sidebar Filters */
    .sidebar {
        position: sticky;
        top: 100px;
        height: fit-content;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .filter-section {
        margin-bottom: 2rem;
    }
    
    .filter-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--gray);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-title i {
        color: var(--primary);
    }
    
    /* Category Filter */
    .category-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .category-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        background: var(--gray-light);
        border-radius: 10px;
        text-decoration: none;
        color: var(--dark);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .category-item:hover {
        background: white;
        border-color: var(--primary);
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(255,107,107,0.15);
    }
    
    .category-item.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border-color: var(--primary);
        font-weight: 600;
    }
    
    .category-item.active .badge {
        background: rgba(255,255,255,0.3);
        color: white;
    }
    
    .category-icon {
        width: 40px;
        height: 40px;
        background: rgba(255,107,107,0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: var(--primary);
    }
    
    .category-item.active .category-icon {
        background: rgba(255,255,255,0.2);
        color: white;
    }
    
    .category-info {
        flex: 1;
    }
    
    .category-name {
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
    }
    
    .category-count {
        font-size: 0.8rem;
        opacity: 0.7;
    }
    
    /* Products Grid */
    .products-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .sort-select {
        min-width: 200px;
        border: 2px solid var(--gray);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .sort-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(255,107,107,0.1);
        outline: none;
    }
    
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    /* Product Card */
    .product-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.12);
    }
    
    .product-card.featured::before {
        content: 'Unggulan';
        position: absolute;
        top: 15px;
        left: 15px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        z-index: 2;
    }
    
    .product-image-container {
        position: relative;
        width: 100%;
        height: 220px;
        overflow: hidden;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    }
    
    .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }
    
    .product-card:hover .product-image {
        transform: scale(1.05);
    }
    
    .product-wishlist {
        position: absolute;
        bottom: 15px;
        right: 15px;
        width: 36px;
        height: 36px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--dark);
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        z-index: 2;
    }
    
    .product-wishlist:hover {
        background: var(--primary);
        color: white;
        transform: scale(1.1);
    }
    
    .product-info {
        padding: 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .product-category {
        font-size: 0.8rem;
        color: var(--secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .product-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 0.75rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 3em;
    }
    
    .product-description {
        font-size: 0.9rem;
        color: var(--dark-light);
        margin-bottom: 1rem;
        line-height: 1.5;
        flex: 1;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .product-shop {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--dark-light);
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--gray);
    }
    
    .product-shop i {
        color: var(--primary);
    }
    
    .product-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
    }
    
    .product-price {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--primary);
    }
    
    .product-rating {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.9rem;
    }
    
    .product-rating .stars {
        color: var(--warning);
    }
    
    .product-rating .count {
        color: var(--dark-light);
    }
    
    .btn-detail {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255,107,107,0.3);
    }
    
    .btn-detail:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255,107,107,0.4);
        color: white;
    }
    
    /* Pagination */
    .pagination-container {
        display: flex;
        justify-content: center;
        margin: 3rem 0;
    }
    
    .pagination {
        display: flex;
        gap: 0.5rem;
    }
    
    .page-link {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 2px solid var(--gray);
        background: white;
        color: var(--dark);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .page-link:hover {
        border-color: var(--primary);
        background: rgba(255,107,107,0.05);
        transform: translateY(-2px);
    }
    
    .page-link.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-color: var(--primary);
        color: white;
    }
    
    .page-link.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .empty-icon {
        font-size: 5rem;
        color: var(--gray);
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }
    
    .empty-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 0.75rem;
    }
    
    .empty-subtitle {
        font-size: 1.1rem;
        color: var(--dark-light);
        margin-bottom: 2rem;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* IMPROVED Featured Products Sidebar */
    .featured-products {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 2rem;
        color: white;
    }
    
    .featured-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-align: center;
        justify-content: center;
    }
    
    .featured-products-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .featured-product-card {
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        text-decoration: none;
        color: white;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .featured-product-card:hover {
        background: rgba(255,255,255,0.2);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .featured-product-image {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .featured-product-content {
        flex: 1;
        min-width: 0;
    }
    
    .featured-product-name {
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .featured-product-price {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .featured-product-rating {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
        opacity: 0.9;
    }
    
    .featured-product-rating .stars {
        color: var(--warning);
    }
    
    /* Badge Styles */
    .badge {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 600;
        border-radius: 10px;
    }
    
    .badge-primary {
        background: rgba(255,107,107,0.1);
        color: var(--primary);
    }
    
    .badge-success {
        background: rgba(16,185,129,0.1);
        color: var(--success);
    }
    
    /* Search Box */
    .search-box {
        position: relative;
        max-width: 500px;
        margin: 0 auto 2rem;
    }
    
    .search-input {
        width: 100%;
        padding: 1rem 1.5rem 1rem 3rem;
        border: 2px solid var(--gray);
        border-radius: 50px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .search-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(255,107,107,0.1);
        outline: none;
    }
    
    .search-icon {
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--dark-light);
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out;
    }
    
    /* Responsive Design */
    @media (max-width: 1200px) {
        .featured-product-image {
            width: 70px;
            height: 70px;
        }
        
        .featured-product-name {
            font-size: 0.9rem;
        }
        
        .featured-product-price {
            font-size: 1rem;
        }
    }
    
    @media (max-width: 992px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
        
        .sidebar {
            margin-bottom: 2rem;
        }
        
        /* Featured products di tablet */
        .featured-products-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        
        .featured-product-card {
            flex-direction: column;
            text-align: center;
            padding: 1rem 0.75rem;
        }
        
        .featured-product-image {
            width: 60px;
            height: 60px;
            margin-bottom: 0.5rem;
        }
        
        .featured-product-content {
            width: 100%;
        }
        
        .featured-product-name {
            white-space: normal;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
        }
    }
    
    @media (max-width: 768px) {
        .page-header {
            padding: 2rem 0;
        }
        
        .page-title {
            font-size: 2rem;
        }
        
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }
        
        .products-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .sort-select {
            width: 100%;
        }
        
        /* Sidebar mobile improvements */
        .sidebar {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .category-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
        
        .category-item {
            flex-direction: column;
            text-align: center;
            padding: 0.75rem 0.5rem;
        }
        
        .category-icon {
            margin-right: 0;
            margin-bottom: 0.5rem;
            width: 50px;
            height: 50px;
        }
        
        .category-name {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .badge {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
        
        /* Featured products di mobile */
        .featured-products {
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .featured-title {
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .featured-products-grid {
            grid-template-columns: 1fr;
        }
        
        .featured-product-card {
            flex-direction: row;
            text-align: left;
            padding: 0.75rem;
        }
        
        .featured-product-image {
            width: 50px;
            height: 50px;
            margin-bottom: 0;
        }
        
        .featured-product-name {
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .featured-product-price {
            font-size: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .product-grid {
            grid-template-columns: 1fr;
        }
        
        .page-title {
            font-size: 1.8rem;
        }
        
        .category-list {
            grid-template-columns: 1fr;
        }
        
        .category-item {
            flex-direction: row;
            text-align: left;
            padding: 0.75rem 1rem;
        }
        
        .category-icon {
            margin-right: 1rem;
            margin-bottom: 0;
            width: 40px;
            height: 40px;
        }
        
        .badge {
            position: static;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        /* Featured products di very small screens */
        .featured-products {
            padding: 0.75rem;
        }
        
        .featured-product-image {
            width: 45px;
            height: 45px;
        }
    }
    
    /* Untuk touch devices */
    @media (hover: none) and (pointer: coarse) {
        .featured-product-card:active {
            background: rgba(255,255,255,0.2);
            transform: scale(0.98);
        }
        
        .category-item:active {
            transform: scale(0.98);
            opacity: 0.9;
        }
        
        .product-wishlist:active {
            transform: scale(0.95);
        }
    }
</style>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item active">Produk</li>
                </ol>
            </nav>
            
            <h1 class="page-title animate-fade-in">
                <?php if (!empty($search)): ?>
                    <i class="fas fa-search me-2"></i>Hasil Pencarian: "<?php echo htmlspecialchars($search); ?>"
                <?php elseif (!empty($category)): ?>
                    <i class="fas fa-tag me-2"></i>Kategori: <?php echo htmlspecialchars($category); ?>
                <?php else: ?>
                    <i class="fas fa-boxes me-2"></i>Semua Produk
                <?php endif; ?>
            </h1>
            
            <p class="page-subtitle animate-fade-in">
                <?php if (!empty($search)): ?>
                    Temukan produk yang Anda cari dengan mudah dan cepat
                <?php elseif (!empty($category)): ?>
                    Jelajahi koleksi terbaik dalam kategori ini
                <?php else: ?>
                    Temukan produk berkualitas dari berbagai toko terpercaya
                <?php endif; ?>
            </p>
            
            <div class="product-count animate-fade-in">
                <i class="fas fa-box me-1"></i> <?php echo number_format($total); ?> produk ditemukan
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <!-- Search Filter -->
                    <div class="filter-section">
                        <div class="filter-title">
                            <i class="fas fa-search"></i> Cari Produk
                        </div>
                        <form method="GET" class="search-box">
                            <input type="text" 
                                   class="search-input" 
                                   name="search" 
                                   placeholder="Ketik nama produk..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   autocomplete="off">
                            <i class="fas fa-search search-icon"></i>
                            <?php if (!empty($category)): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <!-- Categories Filter -->
                    <div class="filter-section">
                        <div class="filter-title">
                            <i class="fas fa-tags"></i> Kategori
                        </div>
                        <div class="category-list">
                            <a href="products.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" 
                               class="category-item <?php echo empty($category) ? 'active' : ''; ?>">
                                <div class="category-icon">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div class="category-info">
                                    <div class="category-name">Semua Kategori</div>
                                </div>
                                <span class="badge badge-primary"><?php echo number_format($total); ?></span>
                            </a>
                            
                            <?php foreach ($categories as $cat): 
                                // Count products per category
                                $cat_count_sql = "SELECT COUNT(*) as count FROM products p 
                                                 LEFT JOIN categories c ON p.category_id = c.id 
                                                 WHERE p.status = 'published' AND c.slug = ?";
                                $cat_stmt = $pdo->prepare($cat_count_sql);
                                $cat_stmt->execute([$cat['slug']]);
                                $cat_count = $cat_stmt->fetch()['count'];
                            ?>
                            <a href="?category=<?php echo $cat['slug']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="category-item <?php echo $category === $cat['slug'] ? 'active' : ''; ?>">
                                <div class="category-icon">
                                    <i class="fas fa-<?php 
                                        $icon = 'cube'; // default
                                        $cat_name_lower = strtolower($cat['name']);
                                        if (strpos($cat_name_lower, 'elektronik') !== false) $icon = 'laptop';
                                        elseif (strpos($cat_name_lower, 'pakaian') !== false || strpos($cat_name_lower, 'fashion') !== false) $icon = 'tshirt';
                                        elseif (strpos($cat_name_lower, 'makanan') !== false || strpos($cat_name_lower, 'minuman') !== false) $icon = 'utensils';
                                        elseif (strpos($cat_name_lower, 'olahraga') !== false) $icon = 'dumbbell';
                                        elseif (strpos($cat_name_lower, 'buku') !== false) $icon = 'book';
                                        elseif (strpos($cat_name_lower, 'kesehatan') !== false || strpos($cat_name_lower, 'kecantikan') !== false) $icon = 'heart-pulse';
                                        elseif (strpos($cat_name_lower, 'rumah') !== false || strpos($cat_name_lower, 'dekorasi') !== false) $icon = 'home';
                                        echo $icon;
                                    ?>"></i>
                                </div>
                                <div class="category-info">
                                    <div class="category-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                                    <div class="category-count"><?php echo $cat_count; ?> produk</div>
                                </div>
                                <span class="badge badge-primary"><?php echo $cat_count; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Sort Filter -->
                    <div class="filter-section">
                        <div class="filter-title">
                            <i class="fas fa-sort-amount-down"></i> Urutkan
                        </div>
                        <form method="GET" class="sort-form">
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php if (!empty($category)): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <?php endif; ?>
                            <select class="sort-select" name="sort" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Paling Populer</option>
                                <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating Tertinggi</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Harga Terendah</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                            </select>
                        </form>
                    </div>
                    
                    <!-- IMPROVED Featured Products -->
                    <?php if (!empty($featured)): ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <div class="products-header animate-fade-in">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($total); ?> Produk Ditemukan</h3>
                        <small class="text-muted">Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?></small>
                    </div>
                    <div>
                        <form method="GET" class="d-flex gap-2">
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php if (!empty($category)): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <?php endif; ?>
                            <select class="sort-select" name="sort" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Urutkan: Terbaru</option>
                                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Urutkan: Populer</option>
                                <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Urutkan: Rating</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Urutkan: Harga Rendah</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Urutkan: Harga Tinggi</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $index => $product): 
                        $first_image = getFirstImage($product['images']);
                        $is_featured = ($product['is_featured'] ?? 0) == 1;
                    ?>
                    <div class="product-card <?php echo $is_featured ? 'featured' : ''; ?> animate-fade-in" 
                         style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        
                        <div class="product-image-container">
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($first_image); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                 class="product-image"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                            
                            <div class="product-wishlist" onclick="toggleWishlist(this, <?php echo $product['id']; ?>)">
                                <i class="far fa-heart"></i>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-category">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Umum'); ?>
                            </div>
                            
                            <h3 class="product-name" title="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </h3>
                            
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?>...
                            </p>
                            
                            <div class="product-shop">
                                <i class="fas fa-store"></i>
                                <span><?php echo htmlspecialchars($product['shop_name']); ?></span>
                            </div>
                            
                            <div class="product-footer">
                                <div>
                                    <div class="product-price">
                                        <?php echo formatPrice($product['price']); ?>
                                    </div>
                                    <div class="product-rating">
                                        <div class="stars">
                                            <?php 
                                            $rating = $product['rating'] ?? 0;
                                            $full_stars = floor($rating);
                                            $has_half_star = ($rating - $full_stars) >= 0.5;
                                            
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $full_stars):
                                                    echo '<i class="fas fa-star"></i>';
                                                elseif ($i == $full_stars + 1 && $has_half_star):
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                else:
                                                    echo '<i class="far fa-star"></i>';
                                                endif;
                                            endfor;
                                            ?>
                                        </div>
                                        <span class="count ms-1">(<?php echo $product['views'] ?? 0; ?>)</span>
                                    </div>
                                </div>
                                
                                <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $product['id']; ?>" 
                                   class="btn-detail">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                           class="page-link" title="Halaman pertama">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="page-link" title="Sebelumnya">
                            <i class="fas fa-angle-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="page-link" title="Berikutnya">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                           class="page-link" title="Halaman terakhir">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="empty-state animate-fade-in">
                    <div class="empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="empty-title">Tidak ada produk yang ditemukan</h3>
                    <p class="empty-subtitle">
                        <?php if (!empty($search)): ?>
                            Coba gunakan kata kunci yang berbeda atau lihat semua produk
                        <?php elseif (!empty($category)): ?>
                            Produk dalam kategori ini sedang tidak tersedia
                        <?php else: ?>
                            Belum ada produk yang tersedia saat ini
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo BASE_URL; ?>/products.php" class="btn-detail">
                        <i class="fas fa-shopping-bag"></i> Lihat Semua Produk
                    </a>
                </div>
                <?php endif; ?>
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
    <script>
    // Wishlist functionality
    function toggleWishlist(button, productId) {
        const icon = button.querySelector('i');
        if (icon.classList.contains('far')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            button.style.background = 'var(--primary)';
            button.style.color = 'white';
            showToast('Ditambahkan ke wishlist', 'success');
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            button.style.background = 'white';
            button.style.color = 'var(--dark)';
            showToast('Dihapus dari wishlist', 'info');
        }
    }
    
    // Toast notification
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Auto submit search on enter
    document.querySelector('.search-input')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.closest('form').submit();
        }
    });
    
    // Add CSS for toast
    const toastStyle = document.createElement('style');
    toastStyle.textContent = `
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 9999;
            min-width: 300px;
            border-left: 4px solid var(--primary);
        }
        
        .toast-notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast-notification.success {
            border-left-color: var(--success);
        }
        
        .toast-notification.info {
            border-left-color: var(--secondary);
        }
        
        .toast-content i {
            font-size: 1.2rem;
        }
        
        .toast-notification.success .toast-content i {
            color: var(--success);
        }
        
        .toast-notification.info .toast-content i {
            color: var(--secondary);
        }
    `;
    document.head.appendChild(toastStyle);
    
    // Lazy load images
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.product-image');
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    imageObserver.unobserve(img);
                }
            });
        }, { threshold: 0.1 });
        
        images.forEach(img => imageObserver.observe(img));
    });
    
    // Touch device optimizations
    if ('ontouchstart' in window) {
        // Increase touch targets for better UX
        const touchTargets = document.querySelectorAll('.product-wishlist, .btn-detail, .category-item, .featured-product-card');
        touchTargets.forEach(target => {
            target.style.minHeight = '44px';
            target.style.minWidth = '44px';
        });
    }
    </script>
</body>
</html>