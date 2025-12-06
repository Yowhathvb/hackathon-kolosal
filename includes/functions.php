<?php
function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

function validateImage($file) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = MAX_FILE_SIZE;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading file: " . $file['error'];
    }
    
    if ($file['size'] > $max_size) {
        return "File size too large. Maximum: " . ($max_size / 1024 / 1024) . "MB";
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return "Invalid file type. Allowed: JPG, PNG, GIF, WebP";
    }
    
    return true;
}

function uploadFile($file, $directory) {
    $validation = validateImage($file);
    if ($validation !== true) {
        return ['success' => false, 'error' => $validation];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . uniqid() . '.' . $extension;
    $upload_path = UPLOAD_PATH . $directory . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isSeller() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'seller' || $_SESSION['role'] === 'admin');
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getShopStatus($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT status FROM shops WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $shop = $stmt->fetch();
    
    return $shop ? $shop['status'] : null;
}

function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function getCategoryName($category_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    return $category ? $category['name'] : 'Uncategorized';
}


?>