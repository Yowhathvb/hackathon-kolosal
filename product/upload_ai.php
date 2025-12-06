<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/ai_assistant.php';
include '../includes/auth_check.php';

 $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if(!$shop) {
    $_SESSION['error'] = "Anda harus memiliki toko yang terverifikasi untuk upload produk!";
    header("Location: ../shop/buat_toko.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ai_assistant = new KolosalAIAssistant();
    
    // Handle AI Analysis
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        
        // Upload gambar produk
        $upload_result = uploadFile($_FILES['product_image'], 'products');
        
        if($upload_result['success']) {
            $filename = $upload_result['filename'];
            
            // Simulasi deskripsi gambar dari analisis (dalam produksi, gunakan Google Vision API)
            $image_description = pathinfo($_FILES['product_image']['name'], PATHINFO_FILENAME);
            $image_description = str_replace(['-', '_'], ' ', $image_description);
            
            // Generate product details dengan Kolosal.ai
            try {
                $ai_response = $ai_assistant->generateProductDetails($image_description);

                // Ensure ai_response is array with expected keys
                if (!is_array($ai_response) || empty($ai_response['product_name'])) {
                    // Local fallback if AI doesn't return expected JSON
                    $ai_response = [
                        'product_name' => 'Produk Berkualitas',
                        'description' => 'Produk unggulan dengan kualitas terbaik, nyaman digunakan dan tahan lama.',
                        'tags' => 'unggulan,berkualitas,rekomendasi'
                    ];
                }

                // Generate SEO data (may return fallback if API fails)
                $seo_data = $ai_assistant->generateSeoData(
                    $ai_response['product_name'] ?? 'Produk Baru', 
                    $ai_response['description'] ?? ''
                );

                // Normalize tags: if comma-separated string -> array -> json
                $tags_raw = $ai_response['tags'] ?? '';
                if (is_string($tags_raw)) {
                    $tags_arr = array_filter(array_map('trim', explode(',', $tags_raw)));
                } elseif (is_array($tags_raw)) {
                    $tags_arr = $tags_raw;
                } else {
                    $tags_arr = [];
                }

                // Simpan ke session untuk form
                $_SESSION['ai_generated'] = [
                    'product_name' => $ai_response['product_name'] ?? 'Produk AI',
                    'description' => $ai_response['description'] ?? '',
                    'tags' => $tags_arr,
                    'image_path' => $filename,
                    'seo_title' => $seo_data['seo_title'] ?? '',
                    'seo_description' => $seo_data['seo_description'] ?? '',
                    'seo_keywords' => $seo_data['seo_keywords'] ?? ''
                ];

                $_SESSION['success'] = "✅ AI berhasil menganalisis gambar! Silakan review dan simpan produk.";
                header("Location: upload_ai.php");
                exit;

            } catch(Exception $e) {
                $error = "❌ Gagal menghubungi AI assistant: " . $e->getMessage();
            }
        } else {
            $error = "❌ Gagal upload gambar: " . $upload_result['error'];
        }
    }
    
    // Handle Final Save
    if(isset($_POST['final_save'])) {
        $product_name = trim($_POST['product_name']);
        $description = trim($_POST['description']);
        $tags = trim($_POST['tags']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $category_id = intval($_POST['category_id']);
        
            if($product_name && $description && $price > 0 && $stock >= 0 && $category_id) {
            try {
                $product_slug = createSlug($product_name) . '-' . uniqid();
                $image_single = $_SESSION['ai_generated']['image_path'] ?? null;
                $gallery = json_encode(array_values((array)($_SESSION['ai_generated']['image_path'] ? [$_SESSION['ai_generated']['image_path']] : [])));
                $tags_json = json_encode(array_values((array)($_SESSION['ai_generated']['tags'] ?? [])));

                // Insert into products table using schema-compatible columns
                $stmt = $pdo->prepare("INSERT INTO products (shop_id, category_id, product_name, product_slug, description, price, stock, image, gallery, tags, seo_title, seo_description, seo_keywords, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

                $stmt->execute([
                    $shop['id'],
                    $category_id,
                    $product_name,
                    $product_slug,
                    $description,
                    $price,
                    $stock,
                    $image_single,
                    $gallery,
                    $tags_json,
                    $_SESSION['ai_generated']['seo_title'] ?? '',
                    $_SESSION['ai_generated']['seo_description'] ?? '',
                    $_SESSION['ai_generated']['seo_keywords'] ?? ''
                ]);

                $product_id = $pdo->lastInsertId();

                unset($_SESSION['ai_generated']);
                $_SESSION['success'] = "🎉 Produk berhasil ditambahkan dengan bantuan AI!";
                header("Location: kelola_produk.php");
                exit;

            } catch(PDOException $e) {
                $error = "❌ Gagal menyimpan produk: " . $e->getMessage();
            }
        } else {
            $error = "❌ Harap isi semua field dengan benar!";
        }
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="container mt-4">
    <h2><i class="fas fa-robot"></i> Upload Produk dengan AI Assistant</h2>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Upload Gambar Produk</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="mb-3">
                            <label for="product_image" class="form-label">Pilih Gambar Produk</label>
                            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*" required>
                            <div class="form-text">Unggah foto produk yang jelas untuk dianalisis AI (maks. 5MB)</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" id="analyzeBtn">
                            <i class="fas fa-robot"></i> Analisis dengan AI Kolosal
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if(isset($_SESSION['ai_generated'])): ?>
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-image"></i> Preview Gambar</h5>
                </div>
                <div class="card-body text-center">
                    <img src="../uploads/products/<?php echo $_SESSION['ai_generated']['image_path']; ?>" 
                         class="img-fluid rounded" style="max-height: 300px;" 
                         alt="Preview Produk">
                    <p class="mt-2 text-muted">
                        <small>Gambar siap diproses oleh AI</small>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <?php if(isset($_SESSION['ai_generated'])): ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-magic"></i> Hasil Analisis AI Kolosal
                        <span class="badge bg-warning float-end">AI Generated</span>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="final_save" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" name="product_name" 
                                   value="<?php echo htmlspecialchars($_SESSION['ai_generated']['product_name']); ?>" required>
                            <div class="form-text">Nama produk yang dihasilkan AI</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($_SESSION['ai_generated']['description']); ?></textarea>
                            <div class="form-text">Deskripsi persuasif hasil AI</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tags</label>
                <input type="text" class="form-control" name="tags" 
                    value="<?php echo htmlspecialchars(is_array($_SESSION['ai_generated']['tags']) ? implode(',', $_SESSION['ai_generated']['tags']) : $_SESSION['ai_generated']['tags']); ?>" required>
                            <div class="form-text">Keyword tags untuk SEO (pisahkan dengan koma)</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga (Rp)</label>
                                    <input type="number" class="form-control" name="price" min="0" step="1000" required placeholder="Contoh: 150000">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stok</label>
                                    <input type="number" class="form-control" name="stock" min="0" required placeholder="Contoh: 100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- SEO Data Preview -->
                        <div class="alert alert-secondary">
                            <h6><i class="fas fa-search"></i> Data SEO (Auto-generated):</h6>
                            <p class="mb-1"><strong>Title:</strong> <?php echo htmlspecialchars($_SESSION['ai_generated']['seo_title']); ?></p>
                            <p class="mb-1"><strong>Description:</strong> <?php echo htmlspecialchars($_SESSION['ai_generated']['seo_description']); ?></p>
                            <p class="mb-0"><strong>Keywords:</strong> <?php echo htmlspecialchars($_SESSION['ai_generated']['seo_keywords']); ?></p>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-edit"></i>
                                <strong>Tips:</strong> Review dan edit hasil AI sesuai kebutuhan sebelum menyimpan. Pastikan harga dan stok sudah benar.
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save"></i> Simpan Produk
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Cara Menggunakan AI Assistant</h5>
                </div>
                <div class="card-body">
                    <h6>Langkah-langkah:</h6>
                    <ol>
                        <li><strong>Upload foto produk</strong> yang jelas dan profesional</li>
                        <li><strong>AI Kolosal akan menganalisis</strong> dan menghasilkan:</li>
                        <ul>
                            <li>Nama produk yang menarik</li>
                            <li>Deskripsi produk yang persuasif</li>
                            <li>Tag/keyword relevan untuk SEO</li>
                            <li>Data SEO otomatis</li>
                        </ul>
                        <li><strong>Review dan edit</strong> hasil AI sesuai kebutuhan</li>
                        <li><strong>Tambahkan harga, stok, dan kategori</strong></li>
                        <li><strong>Simpan produk</strong> dan langsung live di toko</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i> 
                        <strong>Tips untuk hasil terbaik:</strong>
                        <ul class="mb-0">
                            <li>Gunakan foto dengan background bersih</li>
                            <li>Pencahayaan yang baik dan jelas</li>
                            <li>Foto dari berbagai angle (bisa upload multiple nanti)</li>
                            <li>Hindari gambar yang blur atau gelap</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Loading indicator untuk AI analysis
document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('analyzeBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menganalisis dengan AI...';
    btn.disabled = true;
});
</script>

<?php include '../includes/footer.php'; ?>