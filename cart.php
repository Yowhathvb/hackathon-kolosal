<?php
include 'includes/config.php';
include 'includes/header.php';
include 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];

// Handle actions
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['update_qty']) && isset($_POST['item_id']) && isset($_POST['quantity'])) {
        $item_id = intval($_POST['item_id']);
        $quantity = max(1, intval($_POST['quantity']));
        
        // Cek stok produk dan validasi
        $stmt = $pdo->prepare("
            SELECT p.stock, p.id as product_id
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ? AND c.user_id = ? AND p.status = 'published'
        ");
        $stmt->execute([$item_id, $user_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            if ($quantity <= $product['stock']) {
                // Update quantity
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $item_id, $user_id]);
                $_SESSION['success'] = 'Jumlah produk berhasil diperbarui.';
            } else {
                $_SESSION['error'] = 'Stok tidak mencukupi. Stok tersedia: ' . $product['stock'];
            }
        } else {
            $_SESSION['error'] = 'Produk tidak ditemukan atau tidak tersedia.';
        }
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }

    if(isset($_POST['remove_item']) && isset($_POST['item_id'])) {
        $item_id = intval($_POST['item_id']);
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        if($stmt->execute([$item_id, $user_id])) {
            $_SESSION['success'] = 'Produk berhasil dihapus dari keranjang.';
        } else {
            $_SESSION['error'] = 'Gagal menghapus produk.';
        }
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }
    
    if(isset($_POST['clear_cart'])) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        if($stmt->execute([$user_id])) {
            $_SESSION['success'] = 'Keranjang berhasil dikosongkan.';
        } else {
            $_SESSION['error'] = 'Gagal mengosongkan keranjang.';
        }
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }
    
    // Update all quantities at once
    if(isset($_POST['update_all'])) {
        $has_error = false;
        foreach($_POST['quantity'] as $cart_id => $quantity) {
            $cart_id = intval($cart_id);
            $quantity = max(1, intval($quantity));
            
            // Cek stok untuk setiap item
            $stmt = $pdo->prepare("
                SELECT p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$cart_id, $user_id]);
            $product = $stmt->fetch();
            
            if ($product && $quantity <= $product['stock']) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cart_id, $user_id]);
            } else if ($product) {
                $_SESSION['error'] = 'Stok tidak mencukupi untuk beberapa produk.';
                $has_error = true;
            }
        }
        
        if (!$has_error) {
            $_SESSION['success'] = 'Keranjang berhasil diperbarui.';
        }
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }
}

// Fetch cart items with product details
$stmt = $pdo->prepare("
    SELECT 
        c.id as cart_id, 
        c.quantity, 
        c.created_at,
        p.id as product_id,
        p.product_name, 
        p.product_slug,
        p.price, 
        p.stock,
        p.category_id,
        p.images,
        p.is_featured,
        s.id as shop_id,
        s.shop_name,
        s.shop_slug
    FROM cart c
    JOIN products p ON p.id = c.product_id
    LEFT JOIN shops s ON p.shop_id = s.id
    WHERE c.user_id = ? AND p.status = 'published'
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

// Function to get first image from JSON array
function getFirstImage($images) {
    if (empty($images)) return '';
    
    if (is_string($images) && strpos($images, '[') === 0) {
        $images_array = json_decode($images, true);
        return !empty($images_array) ? $images_array[0] : '';
    }
    
    return $images;
}

// Calculate totals
$subtotal = 0;
$total_items = 0;

foreach($items as $it) {
    $item_total = $it['price'] * $it['quantity'];
    $subtotal += $item_total;
    $total_items += $it['quantity'];
}

$page_title = 'Keranjang Belanja';
?>

<style>
    .cart-container {
        min-height: 400px;
    }
    .product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .product-image.featured {
        border: 2px solid #ffc107;
    }
    .quantity-control {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .quantity-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .quantity-btn:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    .quantity-input {
        width: 60px;
        text-align: center;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 6px;
        font-weight: 500;
    }
    .cart-summary {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #e9ecef;
    }
    .shop-badge {
        background: #e9ecef;
        color: #495057;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .empty-cart {
        text-align: center;
        padding: 80px 20px;
        background: #f8f9fa;
        border-radius: 10px;
        margin: 40px 0;
    }
    .empty-cart-icon {
        font-size: 4rem;
        color: #adb5bd;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    .cart-item {
        transition: background-color 0.2s;
    }
    .cart-item:hover {
        background-color: #f8f9fa;
    }
    .sticky-summary {
        position: sticky;
        top: 20px;
    }
    .featured-badge {
        background: linear-gradient(45deg, #ffc107, #fd7e14);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .out-of-stock {
        color: #dc3545;
        font-size: 0.85rem;
    }
    .low-stock {
        color: #fd7e14;
        font-size: 0.85rem;
    }
    .product-name-link {
        color: #212529;
        text-decoration: none;
        transition: color 0.2s;
    }
    .product-name-link:hover {
        color: #0d6efd;
    }
    @media (max-width: 768px) {
        .product-image {
            width: 60px;
            height: 60px;
        }
        .table-responsive {
            font-size: 0.9rem;
        }
    }
</style>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Keranjang Belanja</li>
        </ol>
    </nav>

    <h1 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Keranjang Belanja</h1>
    
    <!-- Alert Messages -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(count($items) === 0): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3 class="mb-3 text-muted">Keranjang Belanja Kosong</h3>
            <p class="text-muted mb-4">Belum ada produk di keranjang belanja Anda.</p>
            <a href="<?php echo BASE_URL; ?>/products.php" class="btn btn-primary btn-lg px-4">
                <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
            </a>
        </div>
    <?php else: ?>
        <form method="POST" id="cartForm">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-box me-2"></i><?php echo count($items); ?> Produk di Keranjang
                                </h5>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="updateAllQuantities()">
                                        <i class="fas fa-sync-alt me-1"></i>Update Semua
                                    </button>
                                    <button type="button" name="clear_cart" class="btn btn-outline-danger btn-sm" 
                                            onclick="clearCart()">
                                        <i class="fas fa-trash me-1"></i>Kosongkan
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="80" class="ps-3">Gambar</th>
                                            <th>Produk</th>
                                            <th width="120" class="text-center">Harga</th>
                                            <th width="150" class="text-center">Jumlah</th>
                                            <th width="120" class="text-center">Subtotal</th>
                                            <th width="50" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($items as $it): 
                                            $item_total = $it['price'] * $it['quantity'];
                                            $first_image = getFirstImage($it['images']);
                                            $is_featured = $it['is_featured'] == 1;
                                        ?>
                                            <tr class="cart-item">
                                                <td class="ps-3">
                                                    <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $it['product_id']; ?>">
                                                        <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($first_image); ?>" 
                                                             alt="<?php echo htmlspecialchars($it['product_name']); ?>"
                                                             class="product-image <?php echo $is_featured ? 'featured' : ''; ?>"
                                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/img/no-image.jpg'">
                                                    </a>
                                                </td>
                                                <td>
                                                    <div>
                                                        <a href="<?php echo BASE_URL; ?>/product/detail.php?id=<?php echo $it['product_id']; ?>" 
                                                           class="product-name-link">
                                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($it['product_name']); ?></h6>
                                                        </a>
                                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                            <?php if($is_featured): ?>
                                                                <span class="featured-badge">
                                                                    <i class="fas fa-crown fa-xs"></i> Unggulan
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if($it['shop_name']): ?>
                                                                <a href="<?php echo BASE_URL; ?>/shop/detail.php?slug=<?php echo $it['shop_slug']; ?>" 
                                                                   class="shop-badge text-decoration-none">
                                                                    <i class="fas fa-store fa-xs"></i>
                                                                    <?php echo htmlspecialchars($it['shop_name']); ?>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <?php if($it['stock'] <= 0): ?>
                                                                <span class="out-of-stock">
                                                                    <i class="fas fa-times-circle me-1"></i>Stok Habis
                                                                </span>
                                                            <?php elseif($it['stock'] < $it['quantity']): ?>
                                                                <span class="out-of-stock">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>Jumlah melebihi stok
                                                                </span>
                                                            <?php elseif($it['stock'] <= 10): ?>
                                                                <span class="low-stock">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>Stok terbatas: <?php echo $it['stock']; ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-check-circle me-1"></i>Stok: <?php echo $it['stock']; ?> unit
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="fw-bold text-primary">
                                                        <?php echo formatPrice($it['price']); ?>
                                                    </div>
                                                    <small class="text-muted">/unit</small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="quantity-control justify-content-center">
                                                        <button type="button" class="quantity-btn minus" 
                                                                onclick="updateQuantity(<?php echo $it['cart_id']; ?>, -1)">
                                                            <i class="fas fa-minus fa-xs"></i>
                                                        </button>
                                                        <input type="number" 
                                                               name="quantity[<?php echo $it['cart_id']; ?>]" 
                                                               value="<?php echo $it['quantity']; ?>" 
                                                               min="1" 
                                                               max="<?php echo $it['stock']; ?>"
                                                               class="quantity-input"
                                                               data-id="<?php echo $it['cart_id']; ?>"
                                                               onchange="validateQuantity(<?php echo $it['cart_id']; ?>, <?php echo $it['stock']; ?>)">
                                                        <button type="button" class="quantity-btn plus"
                                                                onclick="updateQuantity(<?php echo $it['cart_id']; ?>, 1)">
                                                            <i class="fas fa-plus fa-xs"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="fw-bold fs-6">
                                                        <?php echo formatPrice($item_total); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="removeItem(<?php echo $it['cart_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>/products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Lanjutkan Belanja
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="sticky-summary">
                        <div class="cart-summary mb-3">
                            <h5 class="mb-3 fw-bold"><i class="fas fa-receipt me-2"></i>Ringkasan Belanja</h5>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Produk:</span>
                                    <span class="fw-bold"><?php echo $total_items; ?> item</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Item:</span>
                                    <span class="fw-bold"><?php echo count($items); ?> jenis</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                                    <span class="text-muted">Subtotal:</span>
                                    <span class="fw-bold fs-5"><?php echo formatPrice($subtotal); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold">Total Belanja:</span>
                                    <span class="fw-bold fs-4 text-primary"><?php echo formatPrice($subtotal); ?></span>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo BASE_URL; ?>/checkout.php" class="btn btn-success btn-lg py-3">
                                    <i class="fas fa-credit-card me-2"></i>Checkout Sekarang
                                </a>
                                <small class="text-muted text-center">
                                    <i class="fas fa-info-circle me-1"></i>Biaya pengiriman akan dihitung saat checkout
                                </small>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-3 fw-bold"><i class="fas fa-shield-alt me-2"></i>Keamanan Belanja</h6>
                                <ul class="list-unstyled small text-muted">
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Transaksi aman & terenkripsi</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Garansi 100% uang kembali</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Dukungan pelanggan 24/7</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Produk original dari seller terverifikasi</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden inputs for form submission -->
            <input type="hidden" name="update_all" value="1">
        </form>
    <?php endif; ?>
</div>

<script>
// Update individual quantity
function updateQuantity(cartId, change) {
    const input = document.querySelector(`input[name="quantity[${cartId}]"]`);
    let value = parseInt(input.value) + change;
    const max = parseInt(input.getAttribute('max'));
    
    if (value < 1) value = 1;
    if (value > max) {
        alert('Jumlah tidak boleh melebihi stok yang tersedia.');
        value = max;
    }
    
    input.value = value;
    
    // Auto-submit after a delay
    clearTimeout(window.updateTimeout);
    window.updateTimeout = setTimeout(() => {
        submitSingleItem(cartId, value);
    }, 500);
}

// Validate quantity input
function validateQuantity(cartId, maxStock) {
    const input = document.querySelector(`input[name="quantity[${cartId}]"]`);
    let value = parseInt(input.value);
    
    if (isNaN(value) || value < 1) {
        input.value = 1;
        value = 1;
    } else if (value > maxStock) {
        input.value = maxStock;
        value = maxStock;
        alert('Jumlah tidak boleh melebihi stok yang tersedia.');
    }
    
    // Auto-submit after validation
    clearTimeout(window.updateTimeout);
    window.updateTimeout = setTimeout(() => {
        submitSingleItem(cartId, value);
    }, 500);
}

// Submit single item update
function submitSingleItem(cartId, quantity) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const itemIdInput = document.createElement('input');
    itemIdInput.type = 'hidden';
    itemIdInput.name = 'item_id';
    itemIdInput.value = cartId;
    
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = quantity;
    
    const updateInput = document.createElement('input');
    updateInput.type = 'hidden';
    updateInput.name = 'update_qty';
    updateInput.value = '1';
    
    form.appendChild(itemIdInput);
    form.appendChild(quantityInput);
    form.appendChild(updateInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Remove item from cart
function removeItem(cartId) {
    if (confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const itemIdInput = document.createElement('input');
        itemIdInput.type = 'hidden';
        itemIdInput.name = 'item_id';
        itemIdInput.value = cartId;
        
        const removeInput = document.createElement('input');
        removeInput.type = 'hidden';
        removeInput.name = 'remove_item';
        removeInput.value = '1';
        
        form.appendChild(itemIdInput);
        form.appendChild(removeInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Update all quantities
function updateAllQuantities() {
    if (confirm('Update semua jumlah produk di keranjang?')) {
        document.getElementById('cartForm').submit();
    }
}

// Clear entire cart
function clearCart() {
    if (confirm('Apakah Anda yakin ingin mengosongkan seluruh keranjang belanja?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const clearInput = document.createElement('input');
        clearInput.type = 'hidden';
        clearInput.name = 'clear_cart';
        clearInput.value = '1';
        
        form.appendChild(clearInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
        document.getElementById('cartForm').submit();
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>