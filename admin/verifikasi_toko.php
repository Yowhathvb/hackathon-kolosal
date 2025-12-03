<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Cek apakah user adalah admin
if(($_SESSION['role'] ?? '') != 'admin') {
    error_log('admin/verifikasi_toko.php: access denied, role=' . ($_SESSION['role'] ?? 'NULL'));
    header("Location: ../index.php");
    exit;
}

// Handle aksi verifikasi
if(isset($_GET['action']) && isset($_GET['shop_id'])) {
    $shop_id = $_GET['shop_id'];
    $action = $_GET['action'];
    
    if($action == 'verify') {
        $stmt = $pdo->prepare("UPDATE shops SET status = 'verified', verified_at = NOW() WHERE id = ?");
        $stmt->execute([$shop_id]);
        $_SESSION['success'] = "Toko berhasil diverifikasi!";
    } elseif($action == 'reject') {
        $stmt = $pdo->prepare("UPDATE shops SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$shop_id]);
        $_SESSION['success'] = "Toko ditolak!";
    }
    
    header("Location: verifikasi_toko.php");
    exit;
}

// Ambil data toko yang pending
$stmt = $pdo->prepare("SELECT s.*, u.username, u.full_name, u.email 
                      FROM shops s 
                      JOIN users u ON s.user_id = u.id 
                      WHERE s.status = 'pending' 
                      ORDER BY s.created_at DESC");
$stmt->execute();
$pending_shops = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <h2><i class="fas fa-clipboard-check"></i> Verifikasi Toko</h2>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="fas fa-clock"></i> Toko Menunggu Verifikasi</h5>
        </div>
        <div class="card-body">
            <?php if(count($pending_shops) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama Toko</th>
                                <th>Pemilik</th>
                                <th>Telepon</th>
                                <th>Dokumen</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_shops as $shop): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($shop['shop_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($shop['shop_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($shop['full_name']); ?><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($shop['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($shop['shop_phone']); ?></td>
                                    <td>
                                        <?php 
                                        $docs = json_decode($shop['verification_documents'], true);
                                        if(is_array($docs)): 
                                            foreach($docs as $doc): ?>
                                                <a href="../uploads/verification_docs/<?php echo $doc; ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-1">
                                                    <i class="fas fa-file"></i> Lihat
                                                </a><br>
                                            <?php endforeach; 
                                        endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($shop['created_at'])); ?></td>
                                    <td>
                                        <a href="?action=verify&shop_id=<?php echo $shop['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Setujui
                                        </a>
                                        <a href="?action=reject&shop_id=<?php echo $shop['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin tolak toko ini?')">
                                            <i class="fas fa-times"></i> Tolak
                                        </a>
                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $shop['id']; ?>">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal Detail -->
                                <div class="modal fade" id="detailModal<?php echo $shop['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detail Toko: <?php echo htmlspecialchars($shop['shop_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Informasi Toko</h6>
                                                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>
                                                        <p><strong>Deskripsi:</strong> <?php echo htmlspecialchars($shop['shop_description']); ?></p>
                                                        <p><strong>Alamat:</strong> <?php echo htmlspecialchars($shop['shop_address']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Kontak</h6>
                                                        <p><strong>Telepon:</strong> <?php echo htmlspecialchars($shop['shop_phone']); ?></p>
                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($shop['shop_email']); ?></p>
                                                        <p><strong>Pemilik:</strong> <?php echo htmlspecialchars($shop['full_name']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Tidak ada toko yang menunggu verifikasi.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>