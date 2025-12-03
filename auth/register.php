<?php
/**
 * Register Page
 */

// session_start();
require_once '../includes/config.php';

// Jika sudah login, redirect ke home
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

$error = '';
$success = '';
$form_data = ['username' => '', 'email' => '', 'full_name' => ''];

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    $form_data = ['username' => $username, 'email' => $email, 'full_name' => $full_name];
    
    // Validasi
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Semua field harus diisi';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid';
    } else {
        // Cek username sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah terdaftar';
        } else {
            // Insert user baru
            try {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, full_name, role) 
                    VALUES (?, ?, ?, ?, 'customer')
                ");
                $stmt->execute([$username, $email, $hashed_password, $full_name]);
                
                $success = 'Akun berhasil dibuat! Silakan login dengan akun Anda.';
                $form_data = ['username' => '', 'email' => '', 'full_name' => ''];
            } catch(Exception $e) {
                $error = 'Gagal membuat akun: ' . $e->getMessage();
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
    <title>Daftar - Kolosal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B6B;
            --secondary: #4ECDC4;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
            padding: 40px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            color: var(--primary);
            font-weight: 700;
            font-size: 2rem;
        }
        
        .register-header p {
            color: #666;
            margin-top: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px 15px;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
        }
        
        .btn-register {
            background-color: var(--primary);
            border: none;
            border-radius: 5px;
            padding: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            background-color: #E55555;
            color: white;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="<?php echo BASE_URL; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Home
        </a>
        
        <div class="register-header">
            <h1><i class="fas fa-store"></i></h1>
            <h1>Kolosal</h1>
            <p>Daftar Akun Baru</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="alert-link">Login sekarang</a>
        </div>
        <?php endif; ?>
        
        <form method="POST" novalidate>
            <div class="form-group">
                <label class="form-label" for="full_name">Nama Lengkap</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="full_name" 
                    name="full_name"
                    value="<?php echo htmlspecialchars($form_data['full_name']); ?>"
                    placeholder="Masukkan nama lengkap"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username"
                    value="<?php echo htmlspecialchars($form_data['username']); ?>"
                    placeholder="Masukkan username (min 3 karakter)"
                    required
                    minlength="3"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email"
                    value="<?php echo htmlspecialchars($form_data['email']); ?>"
                    placeholder="Masukkan email"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password"
                    placeholder="Masukkan password (min 6 karakter)"
                    required
                    minlength="6"
                >
                <div class="password-requirements">
                    <i class="fas fa-info-circle"></i> Password minimal 6 karakter
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="confirm_password">Konfirmasi Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="confirm_password" 
                    name="confirm_password"
                    placeholder="Ulangi password"
                    required
                    minlength="6"
                >
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">
                        Saya setuju dengan <a href="#" class="text-decoration-none">Syarat & Ketentuan</a>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-register btn-primary">
                <i class="fas fa-user-plus"></i> Daftar
            </button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? 
            <a href="<?php echo BASE_URL; ?>/auth/login.php">Login di sini</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>