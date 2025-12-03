<?php
/**
 * Authentication Middleware
 * Cek session user dan redirect jika belum login
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Optional: Verify user masih ada di database
// global $pdo;
// $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
// $stmt->execute([$_SESSION['user_id']]);
// if (!$stmt->fetch()) {
//     session_destroy();
//     header('Location: ' . BASE_URL . '/auth/login.php');
//     exit;
// }
?>
