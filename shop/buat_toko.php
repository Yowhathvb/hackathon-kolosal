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

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = trim($_POST['shop_name']);
    $shop_description = trim($_POST['shop_description']);
    $shop_address = trim($_POST['shop_address']);
    $shop_phone = trim($_POST['shop_phone']);
    $shop_email = trim($_POST['shop_email']);
    
    // Validasi input
    if(empty($shop_name) || empty($shop_address) || empty($shop_phone)) {
        $error = "Nama toko, alamat, dan telepon wajib diisi!";
    } else {
        // Generate slug (unik)
        $base_slug = strtolower(str_replace(' ', '-', $shop_name));
        $shop_slug = $base_slug;
        $counter = 1;
        
        // Cek apakah slug sudah ada
        while(true) {
            $check_stmt = $pdo->prepare("SELECT id FROM shops WHERE shop_slug = ?");
            $check_stmt->execute([$shop_slug]);
            if($check_stmt->fetch()) {
                $shop_slug = $base_slug . '-' . $counter;
                $counter++;
            } else {
                break;
            }
        }
        
        // Handle file upload untuk logo toko
        $shop_logo = '';
        if(isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
            $logo_file = $_FILES['shop_logo'];
            
            // Validasi file logo
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if(in_array($logo_file['type'], $allowed_types) && $logo_file['size'] <= $max_size) {
                $logo_ext = pathinfo($logo_file['name'], PATHINFO_EXTENSION);
                $logo_name = 'logo_' . time() . '_' . uniqid() . '.' . $logo_ext;
                $logo_path = '../uploads/shop_logos/' . $logo_name;
                
                if(move_uploaded_file($logo_file['tmp_name'], $logo_path)) {
                    $shop_logo = $logo_name;
                } else {
                    $error = "Gagal mengupload logo toko.";
                }
            } else {
                $error = "Format logo tidak didukung atau terlalu besar. Maksimal 2MB (JPEG, PNG, GIF, WebP).";
            }
        } else {
            // Logo tidak wajib, bisa kosong
            $shop_logo = null;
        }
        
        // Handle file upload untuk dokumen verifikasi
        $verification_docs = [];
        
        if(isset($_FILES['verification_docs']) && empty($error)) {
            $upload_dir = "../uploads/verification_docs/";
            $allowed_doc_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'image/webp'];
            $max_doc_size = 5 * 1024 * 1024; // 5MB
            
            foreach($_FILES['verification_docs']['tmp_name'] as $key => $tmp_name) {
                if($_FILES['verification_docs']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['verification_docs']['type'][$key];
                    $file_size = $_FILES['verification_docs']['size'][$key];
                    
                    if(in_array($file_type, $allowed_doc_types) && $file_size <= $max_doc_size) {
                        $file_ext = pathinfo($_FILES['verification_docs']['name'][$key], PATHINFO_EXTENSION);
                        $file_name = 'doc_' . time() . '_' . uniqid() . '.' . $file_ext;
                        $file_path = $upload_dir . $file_name;
                        
                        if(move_uploaded_file($tmp_name, $file_path)) {
                            $verification_docs[] = $file_name;
                        }
                    } else {
                        $error = "Dokumen verifikasi harus berupa gambar (JPEG, PNG, WebP) atau PDF, maksimal 5MB.";
                        break;
                    }
                }
            }
        }
        
        if(empty($error)) {
            try {
                // Insert toko dengan status pending
                $stmt = $pdo->prepare("INSERT INTO shops (
                    user_id, 
                    shop_name, 
                    shop_slug, 
                    shop_description, 
                    shop_address, 
                    shop_phone, 
                    shop_email, 
                    shop_logo,
                    status, 
                    verification_documents,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
                
                $verification_json = !empty($verification_docs) ? json_encode($verification_docs) : null;
                
                $stmt->execute([
                    $_SESSION['user_id'], 
                    $shop_name, 
                    $shop_slug, 
                    $shop_description, 
                    $shop_address, 
                    $shop_phone, 
                    $shop_email,
                    $shop_logo,
                    $verification_json
                ]);
                
                // Update role user menjadi seller
                $stmt = $pdo->prepare("UPDATE users SET role = 'seller' WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $_SESSION['role'] = 'seller';
                
                $_SESSION['success'] = "Toko berhasil dibuat! Menunggu verifikasi admin.";
                header("Location: status_toko.php");
                exit;
            } catch(PDOException $e) {
                error_log("Gagal membuat toko: " . $e->getMessage());
                $error = "Gagal membuat toko. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Toko - Kolosal</title>
    <style>
        .logo-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 15px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .logo-preview:hover {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        
        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .logo-preview-placeholder {
            text-align: center;
            color: #6c757d;
        }
        
        .logo-preview-placeholder i {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .file-info {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .uploaded-files {
            margin-top: 15px;
        }
        
        .uploaded-file-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 5px;
            border-left: 4px solid #0d6efd;
        }
        
        .uploaded-file-item i {
            margin-right: 10px;
            color: #0d6efd;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            counter-reset: step;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 25px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        
        .step.active:not(:last-child)::after {
            background: #0d6efd;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background: #dee2e6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            font-size: 1.2rem;
            color: #6c757d;
            position: relative;
            z-index: 2;
        }
        
        .step.active .step-number {
            background: #0d6efd;
            color: white;
        }
        
        .step-label {
            font-weight: 500;
            color: #6c757d;
        }
        
        .step.active .step-label {
            color: #0d6efd;
            font-weight: 600;
        }
        
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-section-title {
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 15px;
            margin-bottom: 25px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .step-indicator {
                flex-direction: column;
                gap: 20px;
            }
            
            .step:not(:last-child)::after {
                display: none;
            }
            
            .step {
                display: flex;
                align-items: center;
                gap: 15px;
                text-align: left;
            }
            
            .step-number {
                margin: 0;
                flex-shrink: 0;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/shop/">Toko</a></li>
            <li class="breadcrumb-item active" aria-current="page">Buat Toko</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Informasi Toko</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Verifikasi</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Selesai</div>
                </div>
            </div>

            <h1 class="mb-4 text-center"><i class="fas fa-store me-2"></i>Buat Toko Anda</h1>
            
            <?php if(isset($error) && !empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>Informasi Penting</h5>
                <p class="mb-0">
                    Toko Anda akan melalui proses verifikasi oleh admin sebelum dapat beroperasi. 
                    Pastikan data yang diisi valid dan lengkap. Proses verifikasi membutuhkan waktu 1-3 hari kerja.
                </p>
            </div>
            
            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-store me-2"></i>Data Toko</h4>
                
                <form method="POST" enctype="multipart/form-data" id="shopForm">
                    <div class="row">
                        <!-- Logo Upload -->
                        <div class="col-lg-4 mb-4">
                            <label class="form-label">Logo Toko</label>
                            <div class="logo-preview" id="logoPreview" onclick="document.getElementById('shop_logo').click()">
                                <div class="logo-preview-placeholder">
                                    <i class="fas fa-camera"></i>
                                    <span>Klik untuk upload logo</span>
                                </div>
                            </div>
                            <input type="file" class="form-control d-none" id="shop_logo" name="shop_logo" accept="image/*" onchange="previewLogo(event)">
                            <div class="file-info">
                                Format: JPG, PNG, GIF, WebP<br>
                                Maksimal: 2MB<br>
                                Rekomendasi: 300x300px
                            </div>
                        </div>
                        
                        <!-- Form Fields -->
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label for="shop_name" class="form-label required">Nama Toko</label>
                                <input type="text" class="form-control" id="shop_name" name="shop_name" 
                                       value="<?php echo isset($_POST['shop_name']) ? htmlspecialchars($_POST['shop_name']) : ''; ?>" 
                                       required
                                       placeholder="Contoh: Toko Maju Jaya">
                                <div class="form-text">Nama toko akan menjadi identitas utama toko Anda</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="shop_description" class="form-label">Deskripsi Toko</label>
                                <textarea class="form-control" id="shop_description" name="shop_description" 
                                          rows="3" 
                                          placeholder="Jelaskan tentang toko Anda, produk yang dijual, keunggulan, dll."><?php echo isset($_POST['shop_description']) ? htmlspecialchars($_POST['shop_description']) : ''; ?></textarea>
                                <div class="form-text">Deskripsi akan tampil di halaman toko Anda</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="shop_address" class="form-label required">Alamat Toko Lengkap</label>
                        <textarea class="form-control" id="shop_address" name="shop_address" 
                                  rows="3" 
                                  required
                                  placeholder="Isi alamat lengkap toko Anda"><?php echo isset($_POST['shop_address']) ? htmlspecialchars($_POST['shop_address']) : ''; ?></textarea>
                        <div class="form-text">Alamat ini akan digunakan untuk pengiriman produk</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="shop_phone" class="form-label required">Telepon/WhatsApp</label>
                            <input type="tel" class="form-control" id="shop_phone" name="shop_phone" 
                                   value="<?php echo isset($_POST['shop_phone']) ? htmlspecialchars($_POST['shop_phone']) : ''; ?>" 
                                   required
                                   placeholder="Contoh: 081234567890">
                            <div class="form-text">Nomor yang dapat dihubungi pelanggan</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="shop_email" class="form-label">Email Toko</label>
                            <input type="email" class="form-control" id="shop_email" name="shop_email" 
                                   value="<?php echo isset($_POST['shop_email']) ? htmlspecialchars($_POST['shop_email']) : ''; ?>" 
                                   placeholder="email@tokokamu.com">
                            <div class="form-text">Email untuk notifikasi dan komunikasi</div>
                        </div>
                    </div>
                </div>
                
                <!-- Dokumen Verifikasi -->
                <div class="form-section">
                    <h4 class="form-section-title"><i class="fas fa-file-alt me-2"></i>Dokumen Verifikasi</h4>
                    
                    <div class="mb-3">
                        <label for="verification_docs" class="form-label required">Dokumen Identitas *</label>
                        <input type="file" class="form-control" id="verification_docs" name="verification_docs[]" 
                               multiple 
                               required
                               accept="image/*,.pdf"
                               onchange="previewFiles(event)">
                        <div class="form-text">
                            Upload dokumen verifikasi (KTP, SIUP, NPWP, dll.)<br>
                            Format: JPG, PNG, PDF, WebP | Maksimal: 5MB per file<br>
                            Minimal 1 dokumen, maksimal 5 dokumen
                        </div>
                    </div>
                    
                    <!-- Preview Uploaded Files -->
                    <div id="filePreviews" class="uploaded-files">
                        <!-- Preview files will appear here -->
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Penting!</h6>
                        <small class="mb-0">
                            • Dokumen harus jelas terbaca dan asli<br>
                            • Data pada dokumen harus sesuai dengan data toko<br>
                            • Proses verifikasi akan ditolak jika dokumen tidak valid<br>
                            • Pastikan semua file yang diupload sesuai dengan format yang ditentukan
                        </small>
                    </div>
                </div>
                
                <!-- Persetujuan -->
                <div class="form-section">
                    <h4 class="form-section-title"><i class="fas fa-check-circle me-2"></i>Persetujuan</h4>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Syarat & Ketentuan</a> 
                                dan <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Kebijakan Privasi</a> Kolosal
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="data_accurate" name="data_accurate" required>
                            <label class="form-check-label" for="data_accurate">
                                Saya menyatakan bahwa data yang saya berikan adalah benar dan akurat
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Ajukan Pendaftaran Toko
                        </button>
                        <a href="<?php echo BASE_URL; ?>/shop/" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Syarat & Ketentuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Syarat & Ketentuan Penggunaan Platform Kolosal</strong></p>
                <p>1. Anda bertanggung jawab penuh atas keaslian data toko yang didaftarkan.</p>
                <p>2. Toko harus mematuhi semua peraturan yang berlaku di Indonesia.</p>
                <p>3. Dilarang menjual produk ilegal, palsu, atau melanggar hak cipta.</p>
                <p>4. Platform berhak menonaktifkan toko yang melanggar ketentuan.</p>
                <!-- Add more terms as needed -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kebijakan Privasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Kebijakan Privasi Kolosal</strong></p>
                <p>1. Data toko Anda akan dijaga kerahasiaannya.</p>
                <p>2. Data hanya digunakan untuk keperluan verifikasi dan operasional platform.</p>
                <p>3. Kami tidak akan membagikan data Anda kepada pihak ketiga tanpa izin.</p>
                <p>4. Data dokumen verifikasi akan diamankan dengan enkripsi.</p>
                <!-- Add more privacy policy as needed -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Preview logo
function previewLogo(event) {
    const input = event.target;
    const preview = document.getElementById('logoPreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview">`;
        }
        
        reader.readAsDataURL(input.files[0]);
        
        // Show file info
        const fileInfo = `
            <div class="file-info">
                File: ${input.files[0].name}<br>
                Size: ${(input.files[0].size / 1024 / 1024).toFixed(2)} MB
            </div>
        `;
        preview.insertAdjacentHTML('afterend', fileInfo);
    }
}

// Preview uploaded files
function previewFiles(event) {
    const input = event.target;
    const previewContainer = document.getElementById('filePreviews');
    previewContainer.innerHTML = '';
    
    if (input.files.length > 0) {
        previewContainer.innerHTML = '<h6>Dokumen yang akan diupload:</h6>';
        
        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const fileType = file.type;
            let iconClass = 'fas fa-file';
            
            if (fileType.includes('image')) {
                iconClass = 'fas fa-image';
            } else if (fileType === 'application/pdf') {
                iconClass = 'fas fa-file-pdf';
            }
            
            const fileItem = `
                <div class="uploaded-file-item">
                    <i class="${iconClass}"></i>
                    <div>
                        <strong>${file.name}</strong><br>
                        <small>${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    </div>
                </div>
            `;
            previewContainer.insertAdjacentHTML('beforeend', fileItem);
        }
        
        // Validate file count
        if (input.files.length > 5) {
            alert('Maksimal 5 dokumen yang dapat diupload');
            input.value = '';
            previewContainer.innerHTML = '';
        }
    }
}

// Form validation
document.getElementById('shopForm').addEventListener('submit', function(e) {
    const requiredCheckboxes = document.querySelectorAll('input[type="checkbox"][required]');
    let allChecked = true;
    
    requiredCheckboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            allChecked = false;
            checkbox.classList.add('is-invalid');
        } else {
            checkbox.classList.remove('is-invalid');
        }
    });
    
    if (!allChecked) {
        e.preventDefault();
        alert('Harap centang semua persetujuan yang diperlukan');
    }
    
    // Validate file sizes
    const logoInput = document.getElementById('shop_logo');
    if (logoInput.files.length > 0) {
        const logoFile = logoInput.files[0];
        if (logoFile.size > 2 * 1024 * 1024) { // 2MB
            e.preventDefault();
            alert('Ukuran logo maksimal 2MB');
        }
    }
    
    const docInput = document.getElementById('verification_docs');
    if (docInput.files.length > 0) {
        for (let i = 0; i < docInput.files.length; i++) {
            if (docInput.files[i].size > 5 * 1024 * 1024) { // 5MB
                e.preventDefault();
                alert('Setiap dokumen verifikasi maksimal 5MB');
                break;
            }
        }
    }
});

// Format phone number
document.getElementById('shop_phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    // Format: 08xxxxxxxxxx
    if (value.length > 2) {
        value = value.replace(/^(\d{2})(\d+)/, '$1 $2');
    }
    
    e.target.value = value;
});

// Click logo preview to trigger file input
document.getElementById('logoPreview').addEventListener('click', function() {
    document.getElementById('shop_logo').click();
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>