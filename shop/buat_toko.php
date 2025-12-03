<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Cek apakah user sudah punya toko
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$existing_shop = $stmt->fetch();

if($existing_shop) {
    header("Location: kelola_toko.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = $_POST['shop_name'];
    $shop_description = $_POST['shop_description'];
    $shop_address = $_POST['shop_address'];
    $shop_phone = $_POST['shop_phone'];
    $shop_email = $_POST['shop_email'];
    
    // Generate slug
    $shop_slug = strtolower(str_replace(' ', '-', $shop_name));
    
    // Handle file upload untuk dokumen verifikasi
    $verification_docs = [];
    
    if(isset($_FILES['verification_docs'])) {
        $upload_dir = "../uploads/verification_docs/";
        
        foreach($_FILES['verification_docs']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['verification_docs']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = time() . '_' . $_FILES['verification_docs']['name'][$key];
                $file_path = $upload_dir . $file_name;
                
                if(move_uploaded_file($tmp_name, $file_path)) {
                    $verification_docs[] = $file_name;
                }
            }
        }
    }
    
    try {
        // Insert toko dengan status pending
        $stmt = $pdo->prepare("INSERT INTO shops (user_id, shop_name, shop_slug, shop_description, shop_address, shop_phone, shop_email, status, verification_documents) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$_SESSION['user_id'], $shop_name, $shop_slug, $shop_description, $shop_address, $shop_phone, $shop_email, json_encode($verification_docs)]);
        
        // Update role user menjadi seller
        $stmt = $pdo->prepare("UPDATE users SET role = 'seller' WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['role'] = 'seller';
        
        $_SESSION['success'] = "Toko berhasil dibuat! Menunggu verifikasi admin.";
        header("Location: status_toko.php");
        exit;
    } catch(PDOException $e) {
        $error = "Gagal membuat toko: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-store"></i> Buat Toko Anda</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Informasi Verifikasi Toko</h5>
                        <p class="mb-0">Toko Anda akan melalui proses verifikasi oleh admin sebelum dapat beroperasi. Pastikan data yang diisi valid.</p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="shop_name" class="form-label">Nama Toko *</label>
                            <input type="text" class="form-control" id="shop_name" name="shop_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shop_description" class="form-label">Deskripsi Toko</label>
                            <textarea class="form-control" id="shop_description" name="shop_description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shop_address" class="form-label">Alamat Toko *</label>
                            <textarea class="form-control" id="shop_address" name="shop_address" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shop_phone" class="form-label">Telepon Toko *</label>
                                    <input type="tel" class="form-control" id="shop_phone" name="shop_phone" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shop_email" class="form-label">Email Toko</label>
                                    <input type="email" class="form-control" id="shop_email" name="shop_email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="verification_docs" class="form-label">Dokumen Verifikasi (KTP, SIUP, dll.) *</label>
                            <input type="file" class="form-control" id="verification_docs" name="verification_docs[]" multiple required>
                            <div class="form-text">Unggah dokumen-dokumen yang diperlukan untuk verifikasi toko (maks. 5 file)</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle"></i>
                                Pastikan semua data yang diisi benar dan dokumen yang diunggah jelas terbaca. 
                                Proses verifikasi membutuhkan waktu 1-3 hari kerja.
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-paper-plane"></i> Ajukan Pendaftaran Toko
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>