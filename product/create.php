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

    // Determine final shop id
    if ($role === 'admin') {
        $shop_id = intval($_POST['shop_id'] ?? 0);
    }

    // handle optional image: either uploaded now, or provided by AI (temp)
    $image_filename = null;
    if (!empty($_POST['ai_image_filename']) && empty($_FILES['product_image']['name'])) {
        // move file from temp to products
        $tempName = $_POST['ai_image_filename'];
        $src = UPLOAD_PATH . 'temp/' . $tempName;
        if (file_exists($src)) {
            $dest = UPLOAD_PATH . 'products/' . $tempName;
            if (rename($src, $dest)) {
                $image_filename = $tempName;
            } else {
                error_log('product/create.php: failed to move temp image ' . $src);
            }
        }
    } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['product_image'], 'products');
        if ($upload['success']) {
            $image_filename = $upload['filename'];
        } else {
            $error = $upload['error'];
        }
    }

    if (!$shop_id) {
        $error = 'Tentukan toko terlebih dahulu.';
    }

    if (empty($product_name) || empty($description) || $price <= 0) {
        $error = $error ?: 'Nama, deskripsi dan harga harus diisi dengan benar.';
    }

    if (empty($error)) {
        try {
            $product_slug = createSlug($product_name) . '-' . uniqid();
            $gallery = json_encode($image_filename ? [$image_filename] : []);
            $tags_json = json_encode(array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))));

            $stmt = $pdo->prepare("INSERT INTO products (shop_id, category_id, product_name, product_slug, description, price, stock, image, gallery, tags, seo_title, seo_description, seo_keywords, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([
                $shop_id,
                $category_id ?: null,
                $product_name,
                $product_slug,
                $description,
                $price,
                $stock,
                $image_filename,
                $gallery,
                $tags_json,
                $seo_title,
                $seo_description,
                $seo_keywords
            ]);

            $_SESSION['success'] = 'Produk berhasil ditambahkan.';
            header('Location: ' . BASE_URL . '/product/kelola_produk.php');
            exit;
        } catch (PDOException $e) {
            error_log('product/create.php: insert failed: ' . $e->getMessage());
            $error = 'Gagal menyimpan produk. Cek log.';
        }
    }
}
?>

<div class="container mt-4">
    <h2><i class="fas fa-plus"></i> Tambah Produk</h2>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form id="productForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="ai_image_filename" id="ai_image_filename" value="">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <div class="input-group">
                        <input type="text" name="product_name" id="product_name" class="form-control" required>
                        <button type="button" id="gen_name" class="btn btn-outline-secondary">Generate with AI</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <div class="input-group">
                        <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                        <button type="button" id="gen_desc" class="btn btn-outline-secondary">Generate with AI</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="price" class="form-control" min="0" step="1000" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stock" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Pilih Kategori</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tags (pisah koma)</label>
                    <div class="input-group">
                        <input type="text" name="tags" id="tags" class="form-control">
                        <button type="button" id="gen_tags" class="btn btn-outline-secondary">Generate with AI</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">SEO Title</label>
                    <input type="text" name="seo_title" id="seo_title" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">SEO Description</label>
                    <textarea name="seo_description" id="seo_description" class="form-control" rows="2"></textarea>
                </div>

                <?php if($role === 'admin'): ?>
                <div class="mb-3">
                    <label class="form-label">Pilih Toko</label>
                    <select name="shop_id" class="form-select">
                        <option value="">Pilih Toko</option>
                        <?php foreach($shops as $sh): ?>
                            <option value="<?php echo $sh['id']; ?>"><?php echo htmlspecialchars($sh['shop_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <button class="btn btn-success" type="submit">Simpan Produk</button>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">Gambar Produk</div>
                    <div class="card-body">
                        <input type="file" id="product_image" name="product_image" accept="image/*" class="form-control mb-2">
                        <div id="preview" class="text-center mt-2"></div>
                        <button type="button" id="gen_from_image" class="btn btn-primary w-100 mt-2">Generate from Image (AI)</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
async function postImageToAI(file) {
    const fd = new FormData();
    fd.append('image', file);

    const resp = await fetch('<?php echo BASE_URL; ?>/product/generate_ai.php', {
        method: 'POST', body: fd
    });
    return resp.json();
}

document.getElementById('gen_from_image').addEventListener('click', async function(){
    const input = document.getElementById('product_image');
    if (!input.files || input.files.length === 0) {
        alert('Pilih gambar terlebih dahulu');
        return;
    }
    const btn = this;
    btn.disabled = true;
    const prevHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menganalisis...';
    try {
        const res = await postImageToAI(input.files[0]);
        if (res.success) {
            document.getElementById('product_name').value = res.product_name || '';
            document.getElementById('description').value = res.description || '';
            document.getElementById('tags').value = (res.tags || []).join(',');
            document.getElementById('seo_title').value = res.seo_title || '';
            document.getElementById('seo_description').value = res.seo_description || '';
            // set hidden ai image filename so it will be used on save
            if (res.image_filename) {
                document.getElementById('ai_image_filename').value = res.image_filename;
            }
            // show preview
            if (res.image_url) {
                document.getElementById('preview').innerHTML = '<img src="' + res.image_url + '" style="max-width:100%;height:auto;" />';
            }
        } else {
            alert('AI gagal: ' + (res.error || 'unknown'));
        }
    } catch(err) {
        alert('Error contacting AI: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = prevHtml;
    }
});

// Quick buttons for individual fields (generate using current image filename/description)
async function generateField(field) {
    const input = document.getElementById('product_image');
    const btn = document.getElementById('gen_' + (field === 'description' ? 'desc' : field));
    const prev = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ...';

    const fd = new FormData();
    // if an image is selected, include it
    if (input.files && input.files.length > 0) {
        fd.append('image', input.files[0]);
    } else {
        // send fallback_description from product_name so AI has some context
        fd.append('fallback_description', document.getElementById('product_name').value || '');
    }
    fd.append('field', field);

    try {
        const resp = await fetch('<?php echo BASE_URL; ?>/product/generate_ai.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            if (res.image_filename) document.getElementById('ai_image_filename').value = res.image_filename;
            if (field === 'name') document.getElementById('product_name').value = res.product_name || '';
            if (field === 'description') document.getElementById('description').value = res.description || '';
            if (field === 'tags') document.getElementById('tags').value = (res.tags || []).join(',');
            if (res.image_url) document.getElementById('preview').innerHTML = '<img src="' + res.image_url + '" style="max-width:100%;height:auto;" />';
        } else {
            alert('AI gagal: ' + (res.error || 'unknown'));
        }
    } catch (err) {
        alert('AI error: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = prev;
    }
}

document.getElementById('gen_name').addEventListener('click', () => generateField('name'));
document.getElementById('gen_desc').addEventListener('click', () => generateField('description'));
document.getElementById('gen_tags').addEventListener('click', () => generateField('tags'));

// Preview image selection
document.getElementById('product_image').addEventListener('change', function(){
    const p = document.getElementById('preview');
    p.innerHTML = '';
    if (this.files && this.files[0]) {
        const url = URL.createObjectURL(this.files[0]);
        p.innerHTML = '<img src="'+url+'" style="max-width:100%;height:auto;" />';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
