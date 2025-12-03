<?php
/**
 * Login Page
 */

// Load config (session started inside config)
require_once '../includes/config.php';

// Jika sudah login, redirect ke home
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

$error = '';
$username = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Cari user by username or email
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Login query failed: ' . $e->getMessage());
            $user = false;
        }

        if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect ke home
            header('Location: ' . BASE_URL);
            exit;
        } else {
            // provide generic message but log details for debugging
            error_log('Login failed for user: ' . $username . ' - user_exists: ' . (boolval($user) ? '1' : '0'));
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kolosal</title>
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
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--primary);
            font-weight: 700;
            font-size: 2rem;
        }
        
        .login-header p {
            color: #666;
            margin-top: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .btn-login {
            background-color: var(--primary);
            border: none;
            border-radius: 5px;
            padding: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background-color: #E55555;
            color: white;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
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
    </style>
</head>
<body>
    <div class="login-container">
        <a href="<?php echo BASE_URL; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Home
        </a>
        
        <div class="login-header">
            <h1><i class="fas fa-store"></i></h1>
            <h1>Kolosal</h1>
            <p>Marketplace UMKM Indonesia</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" novalidate>
            <div class="form-group">
                <label class="form-label" for="username">Username atau Email</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username"
                    value="<?php echo htmlspecialchars($username); ?>"
                    placeholder="Masukkan username atau email"
                    required
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password"
                    placeholder="Masukkan password"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Ingat saya
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="register-link">
            Belum punya akun? 
            <a href="<?php echo BASE_URL; ?>/auth/register.php">Daftar di sini</a>
        </div>
        
        <div class="register-link mt-4 pt-3 border-top">
            <small class="text-muted">
                Lupa password? 
                <a href="<?php echo BASE_URL; ?>/auth/forgot-password.php">Reset password</a>
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
