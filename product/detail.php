<?php
include '../includes/config.php';
include '../includes/header.php';
// Start session untuk mendapatkan user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(404);
    echo "<div class='container mt-4'><div class='alert alert-danger'>Produk tidak ditemukan.</div></div>";
    include '../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, s.shop_name, s.shop_slug, c.name as category_name FROM products p
    LEFT JOIN shops s ON p.shop_id = s.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.status = 'published' LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo "<div class='container mt-4'><div class='alert alert-danger'>Produk tidak ditemukan atau belum aktif.</div></div>";
    include '../includes/footer.php';
    exit;
}

// Cek apakah produk sudah ada di keranjang user
$in_cart = false;
$cart_quantity = 0;
if (isset($_SESSION['user_id'])) {
    $cart_stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $cart_stmt->execute([$_SESSION['user_id'], $id]);
    $cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC);
    if ($cart_item) {
        $in_cart = true;
        $cart_quantity = $cart_item['quantity'];
    }
}

$gallery = [];
if (!empty($product['gallery'])) {
    $decoded = json_decode($product['gallery'], true);
    if (is_array($decoded)) $gallery = $decoded;
}

// Jika images field berupa JSON, decode juga
$product_images = [];
if (!empty($product['images']) && strpos($product['images'], '[') === 0) {
    $images_decoded = json_decode($product['images'], true);
    if (is_array($images_decoded)) {
        $product_images = $images_decoded;
    }
}

// Set page title
$page_title = htmlspecialchars($product['seo_title'] ?: $product['product_name']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?> - Kolosal</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['seo_description'] ?: substr($product['description'],0,160)); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-main-img { 
            width: 100%; 
            height: 500px; 
            object-fit: contain; 
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px;
        }
        .thumb-img { 
            width: 80px; 
            height: 80px; 
            object-fit: cover; 
            cursor: pointer; 
            border: 2px solid transparent;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .thumb-img:hover, .thumb-img.active {
            border-color: #007bff;
        }
        .product-price {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
        }
        .stock-badge {
            font-size: 0.9rem;
        }
        .quantity-input {
            width: 100px;
        }
        .btn-add-cart {
            min-width: 200px;
        }
        .product-rating {
            display: inline-flex;
            align-items: center;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .product-meta {
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
        }
        .shop-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #007bff;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .product-main-img {
                height: 300px;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/products.php">Produk</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo urlencode($product['category_name'] ?? ''); ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'Kategori'); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['product_name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Image Gallery -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <?php 
                    // Tentukan gambar utama
                    $main_image = !empty($product['image']) ? $product['image'] : 
                                 (!empty($product_images) ? $product_images[0] : 'no-image.jpg');
                    ?>
                    <img id="mainImage" src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($main_image); ?>" 
                         class="product-main-img" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                         onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                </div>
                
                <!-- Thumbnails -->
                <?php if(count($gallery) > 0 || count($product_images) > 1): ?>
                <div class="card-footer bg-white">
                    <div class="d-flex gap-2 flex-wrap">
                        <?php 
                        // Tampilkan gambar utama sebagai thumbnail pertama
                        ?>
                        <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($main_image); ?>" 
                             class="thumb-img active" 
                             onclick="changeMainImage(this.src)" 
                             alt="Thumbnail 1"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                        
                        <?php 
                        // Tampilkan gallery images
                        foreach($gallery as $index => $g): ?>
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($g); ?>" 
                                 class="thumb-img" 
                                 onclick="changeMainImage(this.src)" 
                                 alt="Thumbnail <?php echo $index + 2; ?>"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                        <?php endforeach; ?>
                        
                        <?php 
                        // Tampilkan images dari field images (kecuali yang sudah jadi main image)
                        foreach($product_images as $index => $img):
                            if ($index === 0 && $main_image === $img) continue;
                        ?>
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($img); ?>" 
                                 class="thumb-img" 
                                 onclick="changeMainImage(this.src)" 
                                 alt="Thumbnail <?php echo $index + 2 + count($gallery); ?>"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h2 mb-2"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    
                    <!-- Rating -->
                    <div class="mb-3">
                        <?php 
                        $rating = $product['rating'] ?? 0;
                        $full_stars = floor($rating);
                        $has_half_star = ($rating - $full_stars) >= 0.5;
                        ?>
                        <div class="product-rating mb-2">
                            <?php for ($i = 1; $i <= 5; $i++):
                                if ($i <= $full_stars):
                                    echo '<i class="fas fa-star text-warning"></i>';
                                elseif ($i == $full_stars + 1 && $has_half_star):
                                    echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                else:
                                    echo '<i class="far fa-star text-warning"></i>';
                                endif;
                            endfor; ?>
                            <span class="ms-2"><?php echo number_format($rating, 1); ?> (<?php echo $product['views'] ?? 0; ?> views)</span>
                        </div>
                    </div>
                    
                    <!-- Price -->
                    <div class="product-price mb-3">
                        Rp <?php echo number_format($product['price'],0,',','.'); ?>
                    </div>
                    
                    <!-- Stock -->
                    <div class="mb-4">
                        <?php if($product['stock'] > 10): ?>
                            <span class="badge bg-success stock-badge"><i class="fas fa-check"></i> Stok Tersedia</span>
                        <?php elseif($product['stock'] > 0 && $product['stock'] <= 10): ?>
                            <span class="badge bg-warning text-dark stock-badge"><i class="fas fa-exclamation-triangle"></i> Stok Terbatas: <?php echo $product['stock']; ?></span>
                        <?php else: ?>
                            <span class="badge bg-danger stock-badge"><i class="fas fa-times"></i> Stok Habis</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Shop Info -->
                    <div class="shop-info mb-4">
                        <h5 class="mb-2"><i class="fas fa-store"></i> Informasi Toko</h5>
                        <p class="mb-2"><strong>Nama Toko:</strong> <?php echo htmlspecialchars($product['shop_name']); ?></p>
                        <a href="<?php echo BASE_URL; ?>/shop/detail.php?slug=<?php echo htmlspecialchars($product['shop_slug']); ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i> Kunjungi Toko
                        </a>
                    </div>
                    
                    <!-- Add to Cart Form -->
                    <form method="POST" action="<?php echo BASE_URL; ?>/product/add_to_cart.php" id="addToCartForm">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <div class="row g-3 align-items-center mb-4">
                            <div class="col-auto">
                                <label class="form-label"><strong>Jumlah:</strong></label>
                            </div>
                            <div class="col-auto">
                                <input type="number" name="quantity" value="<?php echo $in_cart ? max(1, $cart_quantity) : 1; ?>" 
                                       min="1" max="<?php echo $product['stock']; ?>" 
                                       class="form-control quantity-input" id="quantityInput">
                            </div>
                            <div class="col-auto">
                                <small class="text-muted">Stok: <?php echo $product['stock']; ?> unit</small>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3 mb-3">
                            <?php if($product['stock'] > 0): ?>
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <button type="submit" class="btn btn-success btn-lg btn-add-cart">
                                        <i class="fas fa-cart-plus"></i> 
                                        <?php echo $in_cart ? 'Update Keranjang' : 'Tambah ke Keranjang'; ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-success btn-lg btn-add-cart" onclick="showLoginAlert()">
                                        <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-lg btn-add-cart" disabled>
                                    <i class="fas fa-ban"></i> Stok Habis
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-outline-primary btn-lg">
                                <i class="far fa-heart"></i> Wishlist
                            </button>
                        </div>
                        
                        <?php if($in_cart): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Produk ini sudah ada di keranjang Anda (<?php echo $cart_quantity; ?> unit).
                            </div>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Product Meta -->
                    <div class="product-meta">
                        <p class="mb-2"><strong>Kategori:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Tidak dikategorikan'); ?></p>
                        <p class="mb-2"><strong>Berat:</strong> <?php echo $product['weight'] ?? 0; ?> gram</p>
                        <p class="mb-0"><strong>Kondisi:</strong> <?php echo $product['condition'] ?? 'Baru'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Description Tabs -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="productTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                                <i class="fas fa-align-left"></i> Deskripsi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="specification-tab" data-bs-toggle="tab" data-bs-target="#specification" type="button" role="tab">
                                <i class="fas fa-list"></i> Spesifikasi
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content p-3" id="productTabContent">
                        <div class="tab-pane fade show active" id="description" role="tabpanel">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                        <div class="tab-pane fade" id="specification" role="tabpanel">
                            <?php if(!empty($product['specifications'])): ?>
                                <?php echo nl2br(htmlspecialchars($product['specifications'])); ?>
                            <?php else: ?>
                                <p class="text-muted">Tidak ada spesifikasi tambahan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fungsi untuk mengganti gambar utama
function changeMainImage(src) {
    document.getElementById('mainImage').src = src;
    
    // Update active class pada thumbnails
    document.querySelectorAll('.thumb-img').forEach(thumb => {
        thumb.classList.remove('active');
    });
    event.target.classList.add('active');
}

// Validasi quantity input
document.getElementById('quantityInput').addEventListener('change', function() {
    const maxStock = <?php echo $product['stock']; ?>;
    if (this.value > maxStock) {
        this.value = maxStock;
        alert('Jumlah tidak boleh melebihi stok yang tersedia.');
    }
    if (this.value < 1) {
        this.value = 1;
    }
});

// Tampilkan alert untuk login jika belum login
function showLoginAlert() {
    if (confirm('Anda perlu login untuk menambahkan produk ke keranjang. Apakah Anda ingin login sekarang?')) {
        window.location.href = '<?php echo BASE_URL; ?>/auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>';
    }
}

// Ajax untuk menambahkan ke keranjang (optional enhancement)
document.getElementById('addToCartForm')?.addEventListener('submit', function(e) {
    // Optional: tambahkan AJAX submission di sini
    // e.preventDefault();
    // ... AJAX code ...
});
</script>

<?php 
// Update view count
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$id]);
include '../includes/footer.php'; 
?>
</body>
</html>