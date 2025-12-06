<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Determine shop for seller; admin can choose
$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'];

// If seller, load their shop id
$shop_id = null;
if ($role === 'seller') {
    $stmt = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($s) $shop_id = $s['id'];
}

// Admin: list shops
$shops = [];
if ($role === 'admin') {
    $shops = $pdo->query("SELECT id, shop_name FROM shops ORDER BY shop_name")->fetchAll(PDO::FETCH_ASSOC);
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Determine final shop id
    if ($role === 'admin') {
        $shop_id = intval($_POST['shop_id'] ?? 0);
    }

    // Handle images: either uploaded now, or provided by AI (temp)
    $images_array = [];
    
    // Handle AI generated image (move from temp to products folder)
    if (!empty($_POST['ai_image_filename']) && empty($_FILES['product_image']['name'])) {
        $tempName = basename($_POST['ai_image_filename']);
        $src = UPLOAD_PATH . 'temp/' . $tempName;
        if (file_exists($src)) {
            $productsDir = UPLOAD_PATH . 'products/';
            if (!is_dir($productsDir)) {
                if (!mkdir($productsDir, 0755, true) && !is_dir($productsDir)) {
                    error_log('product/create.php: failed to create products upload dir: ' . $productsDir);
                    $error = 'Gagal membuat folder upload untuk produk. Periksa permission.';
                }
            }

            if (empty($error)) {
                $dest = $productsDir . $tempName;
                if (rename($src, $dest)) {
                    $images_array[] = $tempName;
                } else {
                    error_log('product/create.php: failed to move temp image ' . $src . ' -> ' . $dest);
                    $error = 'Gagal memindahkan gambar AI ke folder produk. Periksa permission.';
                }
            }
        } else {
            error_log('product/create.php: AI temp image not found: ' . $src);
            // not fatal: continue, user may upload manually
        }
    }
    
    // Handle uploaded image
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['product_image'], 'products');
        if ($upload['success']) {
            $images_array[] = $upload['filename'];
        } else {
            $error = $upload['error'];
        }
    }
    
    // Handle multiple image uploads
    if (!empty($_FILES['product_images']['name'][0])) {
        foreach ($_FILES['product_images']['name'] as $key => $name) {
            if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['product_images']['name'][$key],
                    'type' => $_FILES['product_images']['type'][$key],
                    'tmp_name' => $_FILES['product_images']['tmp_name'][$key],
                    'error' => $_FILES['product_images']['error'][$key],
                    'size' => $_FILES['product_images']['size'][$key]
                ];
                
                $upload = uploadFile($file, 'products');
                if ($upload['success']) {
                    $images_array[] = $upload['filename'];
                }
            }
        }
    }

    if (!$shop_id) {
        $error = 'Tentukan toko terlebih dahulu.';
    }

    if (empty($product_name) || empty($description) || $price <= 0) {
        $error = $error ?: 'Nama, deskripsi dan harga harus diisi dengan benar.';
    }

    // Auto-fill SEO fields if empty (avoid relying on SITE_NAME constant)
    if (empty($seo_title)) $seo_title = $product_name;
    if (empty($seo_description)) $seo_description = substr($description, 0, 150) . (strlen($description) > 150 ? '...' : '');

    if (empty($error)) {
        try {
            $product_slug = createSlug($product_name) . '-' . uniqid();
            $images_json = json_encode($images_array);
            $tags_json = json_encode(array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))));
            
            // Mark if AI generated any content
            $ai_generated = (!empty($_POST['ai_image_filename']) || 
                           !empty($_POST['product_name']) && isset($_POST['ai_generated_name']) ||
                           !empty($_POST['description']) && isset($_POST['ai_generated_desc']) ||
                           !empty($_POST['tags']) && isset($_POST['ai_generated_tags'])) ? 1 : 0;

            // Insert query sesuai dengan struktur tabel Anda
            $stmt = $pdo->prepare("INSERT INTO products (
                shop_id, product_name, product_slug, description, price, stock, 
                images, category_id, tags, ai_generated, seo_title, seo_description, 
                seo_keywords, status, is_featured, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', ?, NOW(), NOW())");
            
            $stmt->execute([
                $shop_id,
                $product_name,
                $product_slug,
                $description,
                $price,
                $stock,
                $images_json,
                $category_id ?: null,
                $tags_json,
                $ai_generated,
                $seo_title,
                $seo_description,
                $seo_keywords,
                $is_featured
            ]);

            $_SESSION['success'] = 'Produk berhasil ditambahkan.';
            header('Location: ' . BASE_URL . '/product/kelola_produk.php');
            exit;
        } catch (PDOException $e) {
            error_log('product/create.php: insert failed: ' . $e->getMessage());
            $error = 'Gagal menyimpan produk: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Kolosal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .preview-img {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 3px;
            margin: 5px;
            background: #f8f9fa;
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .ai-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .ai-btn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview-item {
            position: relative;
            display: inline-block;
        }
        .remove-image {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            text-align: center;
            line-height: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle text-success"></i> Tambah Produk Baru</h2>
        <a href="<?php echo BASE_URL; ?>/product/kelola_produk.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Produk
        </a>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form id="productForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="ai_image_filename" id="ai_image_filename" value="">
        <input type="hidden" name="ai_generated_name" id="ai_generated_name" value="0">
        <input type="hidden" name="ai_generated_desc" id="ai_generated_desc" value="0">
        <input type="hidden" name="ai_generated_tags" id="ai_generated_tags" value="0">
        
        <div class="row">
            <!-- Left Column - Form Fields -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Informasi Produk
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Produk <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="product_name" id="product_name" class="form-control" required 
                                       placeholder="Masukkan nama produk" value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
                                <button type="button" id="gen_name" class="btn ai-btn">
                                    <i class="fas fa-robot"></i> AI
                                </button>
                            </div>
                            <small class="text-muted">Nama produk yang jelas dan menarik</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi Produk <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <textarea name="description" id="description" class="form-control" rows="5" required 
                                          placeholder="Deskripsikan produk secara detail"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <button type="button" id="gen_desc" class="btn ai-btn align-self-start">
                                    <i class="fas fa-robot"></i>
                                </button>
                            </div>
                            <small class="text-muted">Jelaskan fitur, keunggulan, dan spesifikasi produk</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Harga (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="price" class="form-control" min="0" step="1000" required 
                                           value="<?php echo $_POST['price'] ?? ''; ?>" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Stok <span class="text-danger">*</span></label>
                                <input type="number" name="stock" class="form-control" min="0" required 
                                       value="<?php echo $_POST['stock'] ?? 0; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kategori</label>
                                <select name="category_id" class="form-select">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach($categories as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" 
                                            <?php echo (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tags</label>
                                <div class="input-group">
                                    <input type="text" name="tags" id="tags" class="form-control" 
                                           placeholder="tag1, tag2, tag3" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>">
                                    <button type="button" id="gen_tags" class="btn ai-btn">
                                        <i class="fas fa-robot"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Pisahkan dengan koma</small>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" 
                                   <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">
                                <strong>Tandai sebagai Produk Unggulan</strong>
                            </label>
                            <small class="text-muted d-block">Produk unggulan akan ditampilkan di halaman utama</small>
                        </div>
                    </div>
                </div>

                <!-- SEO Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-search"></i> SEO Optimization
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">SEO Title</label>
                            <input type="text" name="seo_title" id="seo_title" class="form-control" 
                                   placeholder="Judul untuk mesin pencari" value="<?php echo htmlspecialchars($_POST['seo_title'] ?? ''); ?>">
                            <small class="text-muted">Akan terisi otomatis dari nama produk (50-60 karakter optimal)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">SEO Description</label>
                            <textarea name="seo_description" id="seo_description" class="form-control" rows="2" 
                                      placeholder="Deskripsi untuk mesin pencari"><?php echo htmlspecialchars($_POST['seo_description'] ?? ''); ?></textarea>
                            <small class="text-muted">Akan terisi otomatis dari deskripsi produk (150-160 karakter optimal)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">SEO Keywords</label>
                            <input type="text" name="seo_keywords" id="seo_keywords" class="form-control" 
                                   placeholder="keyword1, keyword2, keyword3" value="<?php echo htmlspecialchars($_POST['seo_keywords'] ?? ''); ?>">
                            <small class="text-muted">Kata kunci untuk SEO, pisahkan dengan koma</small>
                        </div>
                    </div>
                </div>

                <!-- Shop Selection for Admin -->
                <?php if($role === 'admin'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-store"></i> Pilih Toko
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Toko <span class="text-danger">*</span></label>
                            <select name="shop_id" class="form-select" required>
                                <option value="">Pilih Toko</option>
                                <?php foreach($shops as $sh): ?>
                                    <option value="<?php echo $sh['id']; ?>" 
                                        <?php echo (($_POST['shop_id'] ?? '') == $sh['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sh['shop_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Image & Actions -->
            <div class="col-md-4">
                <!-- Image Upload Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-image"></i> Gambar Produk
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gambar Utama</label>
                            <input type="file" id="product_image" name="product_image" accept="image/*" class="form-control">
                            <small class="text-muted">Format: JPG, PNG, GIF. Max: 2MB</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gambar Tambahan (Multiple)</label>
                            <input type="file" id="product_images" name="product_images[]" accept="image/*" class="form-control" multiple>
                            <small class="text-muted">Pilih beberapa gambar sekaligus</small>
                        </div>
                        
                        <div id="preview" class="text-center mb-3">
                            <img src="<?php echo BASE_URL; ?>/assets/img/no-image.jpg" 
                                 class="preview-img" id="previewImage" alt="Preview">
                        </div>
                        
                        <div id="multiplePreview" class="image-preview-container"></div>
                        
                        <button type="button" id="gen_from_image" class="btn ai-btn w-100 mb-2">
                            <i class="fas fa-magic"></i> Generate Semua dari Gambar (AI)
                        </button>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Upload gambar untuk generate otomatis nama, deskripsi, dan tags menggunakan AI.
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cogs"></i> Aksi
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Simpan Produk
                            </button>
                            <button type="reset" class="btn btn-outline-secondary" id="resetForm">
                                <i class="fas fa-redo"></i> Reset Form
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global variables to track SEO field edits
let seoTitleEdited = false;
let seoDescEdited = false;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const seoTitleInput = document.getElementById('seo_title');
    const seoDescInput = document.getElementById('seo_description');
    const productNameInput = document.getElementById('product_name');
    const descriptionInput = document.getElementById('description');
    
    // Check if SEO fields already have values
    if (seoTitleInput && seoTitleInput.value.trim() !== '') {
        seoTitleEdited = true;
    }
    if (seoDescInput && seoDescInput.value.trim() !== '') {
        seoDescEdited = true;
    }
    
    // Listen for manual edits on SEO fields
    if (seoTitleInput) {
        seoTitleInput.addEventListener('input', function() {
            seoTitleEdited = true;
        });
    }
    
    if (seoDescInput) {
        seoDescInput.addEventListener('input', function() {
            seoDescEdited = true;
        });
    }
    
    // Auto-fill SEO Title from product name
    if (productNameInput && seoTitleInput) {
        productNameInput.addEventListener('input', function() {
            if (!seoTitleEdited) {
                const name = this.value.trim();
                if (name) {
                    // Create SEO Title: Product Name + Brand/Site
                    const seoTitle = name + ' - Kolosal';
                    seoTitleInput.value = seoTitle.substring(0, 60); // Limit to 60 chars
                }
            }
        });
        
        // Trigger on page load if product name has value
        if (productNameInput.value.trim() !== '') {
            productNameInput.dispatchEvent(new Event('input'));
        }
    }
    
    // Auto-fill SEO Description from product description
    if (descriptionInput && seoDescInput) {
        descriptionInput.addEventListener('input', function() {
            if (!seoDescEdited) {
                const desc = this.value.trim();
                if (desc) {
                    // Create SEO Description (limit to 160 chars for SEO)
                    let seoDesc = desc.substring(0, 160);
                    if (desc.length > 160) {
                        seoDesc = seoDesc.substring(0, seoDesc.lastIndexOf(' ')) + '...';
                    }
                    seoDescInput.value = seoDesc;
                }
            }
        });
        
        // Trigger on page load if description has value
        if (descriptionInput.value.trim() !== '') {
            descriptionInput.dispatchEvent(new Event('input'));
        }
    }
    
    // Preview main image
    const imageInput = document.getElementById('product_image');
    const previewImage = document.getElementById('previewImage');
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                previewImage.src = '<?php echo BASE_URL; ?>/assets/img/no-image.jpg';
            }
        });
    }
    
    // Preview multiple images
    const multipleImageInput = document.getElementById('product_images');
    const multiplePreview = document.getElementById('multiplePreview');
    
    if (multipleImageInput) {
        multipleImageInput.addEventListener('change', function() {
            multiplePreview.innerHTML = '';
            
            if (this.files) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'preview-img';
                            img.style.maxWidth = '150px';
                            img.style.maxHeight = '150px';
                            
                            const container = document.createElement('div');
                            container.className = 'image-preview-item';
                            container.appendChild(img);
                            
                            multiplePreview.appendChild(container);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    }
    
    // Reset form button
    const resetBtn = document.getElementById('resetForm');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            // Reset all AI generated flags
            document.getElementById('ai_generated_name').value = '0';
            document.getElementById('ai_generated_desc').value = '0';
            document.getElementById('ai_generated_tags').value = '0';
            document.getElementById('ai_image_filename').value = '';
            
            // Reset SEO flags
            seoTitleEdited = false;
            seoDescEdited = false;
            
            // Reset preview images
            previewImage.src = '<?php echo BASE_URL; ?>/assets/img/no-image.jpg';
            multiplePreview.innerHTML = '';
        });
    }
});

// Function to send image to AI API
async function postImageToAI(file, field = 'all') {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('field', field);
    
    // Add fallback description if available
    const productName = document.getElementById('product_name').value;
    if (productName && field !== 'all') {
        formData.append('fallback_description', productName);
    }
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/product/generate_ai.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}

// Generate all from image button
document.getElementById('gen_from_image').addEventListener('click', async function() {
    const imageInput = document.getElementById('product_image');
    if (!imageInput.files || imageInput.files.length === 0) {
        alert('Silakan pilih gambar terlebih dahulu!');
        return;
    }
    
    const button = this;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menganalisis gambar...';
    
    try {
        const result = await postImageToAI(imageInput.files[0], 'all');
        
        if (result.success) {
            // Fill form fields with AI results
            if (result.product_name) {
                document.getElementById('product_name').value = result.product_name;
                document.getElementById('ai_generated_name').value = '1';
                document.getElementById('product_name').dispatchEvent(new Event('input'));
            }
            
            if (result.description) {
                document.getElementById('description').value = result.description;
                document.getElementById('ai_generated_desc').value = '1';
                document.getElementById('description').dispatchEvent(new Event('input'));
            }
            
            if (result.tags && result.tags.length > 0) {
                document.getElementById('tags').value = result.tags.join(', ');
                document.getElementById('ai_generated_tags').value = '1';
            }
            
            if (result.seo_title) {
                document.getElementById('seo_title').value = result.seo_title;
                seoTitleEdited = true; // Mark as edited since AI filled it
            }
            
            if (result.seo_description) {
                document.getElementById('seo_description').value = result.seo_description;
                seoDescEdited = true; // Mark as edited since AI filled it
            }
            
            // Set AI image filename
            if (result.image_filename) {
                document.getElementById('ai_image_filename').value = result.image_filename;
            }
            
            // Show AI generated image preview
            if (result.image_url) {
                document.getElementById('previewImage').src = result.image_url;
            }
            
            // Show success message
            showAlert('success', 'AI berhasil menganalisis gambar dan mengisi form!');
        } else {
            throw new Error(result.error || 'Gagal menganalisis gambar');
        }
    } catch (error) {
        console.error('AI Error:', error);
        showAlert('danger', 'Error: ' + error.message);
    } finally {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    }
});

// Individual field generation buttons
async function generateField(field) {
    const imageInput = document.getElementById('product_image');
    const button = document.getElementById(`gen_${field}`);
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    try {
        let result;
        
        if (imageInput.files && imageInput.files.length > 0) {
            // Use image for generation
            result = await postImageToAI(imageInput.files[0], field);
        } else {
            // Use text-based generation
            const productName = document.getElementById('product_name').value;
            if (!productName && field !== 'name') {
                throw new Error('Silakan isi nama produk terlebih dahulu atau upload gambar');
            }
            
            const formData = new FormData();
            formData.append('field', field);
            formData.append('fallback_description', productName || '');
            
            const response = await fetch('<?php echo BASE_URL; ?>/product/generate_ai.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            result = await response.json();
        }
        
        if (result.success) {
            switch(field) {
                case 'name':
                    if (result.product_name) {
                        document.getElementById('product_name').value = result.product_name;
                        document.getElementById('ai_generated_name').value = '1';
                        document.getElementById('product_name').dispatchEvent(new Event('input'));
                    }
                    break;
                case 'desc':
                    if (result.description) {
                        document.getElementById('description').value = result.description;
                        document.getElementById('ai_generated_desc').value = '1';
                        document.getElementById('description').dispatchEvent(new Event('input'));
                    }
                    break;
                case 'tags':
                    if (result.tags && result.tags.length > 0) {
                        document.getElementById('tags').value = result.tags.join(', ');
                        document.getElementById('ai_generated_tags').value = '1';
                    }
                    break;
            }
            
            // Handle AI image if generated
            if (result.image_filename) {
                document.getElementById('ai_image_filename').value = result.image_filename;
            }
            
            if (result.image_url) {
                document.getElementById('previewImage').src = result.image_url;
            }
            
            showAlert('success', `AI berhasil generate ${field === 'desc' ? 'deskripsi' : field}!`);
        } else {
            throw new Error(result.error || 'Gagal generate');
        }
    } catch (error) {
        console.error('AI Error:', error);
        showAlert('warning', 'AI Error: ' + error.message);
    } finally {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

// Event listeners for individual AI buttons
document.getElementById('gen_name').addEventListener('click', () => generateField('name'));
document.getElementById('gen_desc').addEventListener('click', () => generateField('desc'));
document.getElementById('gen_tags').addEventListener('click', () => generateField('tags'));

// Utility function to show alerts
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert-dismissible:not(.alert-danger)');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> 
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.querySelector('.container.mt-4');
    const firstChild = container.firstElementChild;
    if (firstChild && firstChild.classList.contains('d-flex')) {
        // Insert after the header
        firstChild.insertAdjacentHTML('afterend', alertHtml);
    } else {
        // Insert at the beginning of container
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector(`.alert-${type}`);
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// Form submission validation
document.getElementById('productForm').addEventListener('submit', function(e) {
    const productName = document.getElementById('product_name').value.trim();
    const description = document.getElementById('description').value.trim();
    const price = document.querySelector('input[name="price"]').value;
    
    if (!productName) {
        e.preventDefault();
        showAlert('danger', 'Nama produk harus diisi!');
        document.getElementById('product_name').focus();
        return;
    }
    
    if (!description) {
        e.preventDefault();
        showAlert('danger', 'Deskripsi produk harus diisi!');
        document.getElementById('description').focus();
        return;
    }
    
    if (!price || parseFloat(price) <= 0) {
        e.preventDefault();
        showAlert('danger', 'Harga harus lebih dari 0!');
        document.querySelector('input[name="price"]').focus();
        return;
    }
    
    <?php if($role === 'admin'): ?>
    const shopId = document.querySelector('select[name="shop_id"]').value;
    if (!shopId) {
        e.preventDefault();
        showAlert('danger', 'Silakan pilih toko!');
        return;
    }
    <?php endif; ?>
    
    // Show loading on submit
    const submitButton = this.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>