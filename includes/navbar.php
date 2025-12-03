<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-store"></i> MyShopee
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Search Bar -->
            <form class="d-flex mx-auto" style="width: 50%;">
                <input class="form-control me-2" type="search" placeholder="Cari produk..." aria-label="Search">
                <button class="btn btn-warning" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            
            <!-- Navigation Menu -->
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <?php if(($_SESSION['role'] ?? '') === 'customer'): ?>
                        <li class="nav-item d-flex align-items-center me-2">
                            <a class="btn btn-outline-light" href="<?php echo BASE_URL; ?>/shop/buat_toko.php">
                                <i class="fas fa-store"></i> Buat Toko
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if($_SESSION['role'] == 'seller' || $_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/shop/kelola_toko.php">
                                    <i class="fas fa-store"></i> Kelola Toko
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/product/kelola_produk.php">
                                    <i class="fas fa-box"></i> Kelola Produk
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            
                            <?php if($_SESSION['role'] == 'customer'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/shop/buat_toko.php">
                                    <i class="fas fa-store"></i> Buat Toko
                                </a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-shopping-cart"></i> Keranjang
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-heart"></i> Wishlist
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- User is not logged in - show prominent Login button -->
                    <li class="nav-item">
                        <a class="btn btn-outline-light me-2" href="<?php echo BASE_URL; ?>/auth/login.php" role="button">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <!-- Optional: register link as small text -->
                    <li class="nav-item d-flex align-items-center">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/register.php">Daftar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>