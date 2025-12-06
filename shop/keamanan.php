<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$new || $new !== $confirm) {
        $error = 'Password baru tidak cocok atau kosong.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if ($row && password_verify($current, $row['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $user_id]);
            $_SESSION['success'] = 'Password berhasil diubah.';
            header('Location: ' . BASE_URL . '/shop/kelola_toko.php');
            exit;
        } else {
            $error = 'Password saat ini salah.';
        }
    }
}
?>
<div class="container mt-4">
    <h2>Keamanan Akun</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Password Saat Ini</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button class="btn btn-primary">Ubah Password</button>
        <a href="<?php echo BASE_URL; ?>/shop/kelola_toko.php" class="btn btn-link">Batal</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
