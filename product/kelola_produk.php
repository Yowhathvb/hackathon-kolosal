<?php
include '../includes/config.php';
include '../includes/header.php';
include '../includes/auth_check.php';

// Allow sellers to manage their products, admins manage all
$role = $_SESSION['role'] ?? '';

try {
	if ($role === 'seller') {
		// find shop for this seller
		$stmt = $pdo->prepare("SELECT id FROM shops WHERE user_id = ? LIMIT 1");
		$stmt->execute([$_SESSION['user_id']]);
		$shop = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$shop) {
			$_SESSION['error'] = 'Anda belum memiliki toko. Silakan buat toko terlebih dahulu.';
			header('Location: ' . BASE_URL . '/shop/buat_toko.php');
			exit;
		}
		$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.shop_id = ? ORDER BY p.created_at DESC");
		$stmt->execute([$shop['id']]);
	} else {
		// admin or other roles: show all products
		$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, s.shop_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN shops s ON p.shop_id = s.id ORDER BY p.created_at DESC");
		$stmt->execute();
	}

	$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	error_log('product/kelola_produk.php: products query failed: ' . $e->getMessage());
	$products = [];
}
?>

<div class="container mt-4">
	<h2><i class="fas fa-box"></i> Kelola Produk</h2>

	<?php if(isset($_SESSION['success'])): ?>
		<div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
	<?php endif; ?>
	<?php if(isset($_SESSION['error'])): ?>
		<div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
	<?php endif; ?>

	<div class="mb-3">
		<a href="upload_ai.php" class="btn btn-primary"><i class="fas fa-upload"></i> Tambah Produk (AI)</a>
		<a href="create.php" class="btn btn-secondary ms-2"><i class="fas fa-plus"></i> Tambah Produk Manual</a>
	</div>

	<div class="table-responsive">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Gambar</th>
					<th>Nama</th>
					<th>Kategori</th>
					<th>Harga</th>
					<th>Stok</th>
					<th>Status</th>
					<th>Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php if(empty($products)): ?>
					<tr><td colspan="8">Belum ada produk.</td></tr>
				<?php else: ?>
					<?php foreach($products as $p): ?>
						<tr>
							<td><?php echo (int)$p['id']; ?></td>
							<td>
								<?php if(!empty($p['image'])): ?>
									<img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo htmlspecialchars($p['image']); ?>" style="width:64px;height:64px;object-fit:cover;" alt="">
								<?php endif; ?>
							</td>
							<td><?php echo htmlspecialchars($p['product_name']); ?></td>
							<td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
							<td>Rp <?php echo number_format($p['price'] ?? 0,0,',','.'); ?></td>
							<td><?php echo (int)($p['stock'] ?? 0); ?></td>
							<td><?php echo htmlspecialchars($p['status'] ?? ''); ?></td>
							<td>
								<a href="edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
								<a href="delete.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk ini?')">Hapus</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php include '../includes/footer.php'; ?>

