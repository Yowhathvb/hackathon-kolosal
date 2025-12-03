<?php
include '../includes/config.php';
include '../includes/ai_assistant.php';
include '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// If no image was uploaded, accept optional fallback description or empty string
$image_description = trim($_POST['fallback_description'] ?? '');

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload = uploadFile($_FILES['image'], 'temp');
    if (!$upload['success']) {
        echo json_encode(['success' => false, 'error' => $upload['error']]);
        exit;
    }
    $filename = $upload['filename'];
    $image_url = BASE_URL . '/uploads/temp/' . $filename;

    // Create a simple description from filename for AI (since we don't have vision API)
    $orig_name = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
    $image_description = str_replace(['-', '_'], ' ', $orig_name);
} else {
    $filename = null;
    $image_url = null;
}

$assistant = new KolosalAIAssistant();
$ai_result = $assistant->generateProductDetails($image_description);
$seo = $assistant->generateSeoData($ai_result['product_name'] ?? ($image_description ?: 'Produk'), $ai_result['description'] ?? '');

$tags = [];
if (!empty($ai_result['tags'])) {
    if (is_string($ai_result['tags'])) {
        $tags = array_filter(array_map('trim', explode(',', $ai_result['tags'])));
    } elseif (is_array($ai_result['tags'])) {
        $tags = $ai_result['tags'];
    }
}

// If requested (or when generating name), create a placeholder image from the product name
$field = $_POST['field'] ?? '';
$generate_image_flag = ($_POST['generate_image'] ?? '') === '1';
if (($field === 'name') || $generate_image_flag) {
    $text = $ai_result['product_name'] ?? $image_description ?: 'Produk';
    // create placeholder PNG with text
    $img_name = time() . '_' . uniqid() . '.png';
    $dst_path = UPLOAD_PATH . 'temp/' . $img_name;

    // create image 1000x1000 with light background and centered text
    $w = 1000; $h = 1000;
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, 245, 245, 245);
    $txtc = imagecolorallocate($im, 30, 30, 30);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // wrap text to ~20 chars per line
    $wrap = wordwrap($text, 20, "\n");
    $lines = explode("\n", $wrap);

    $font = 5; // built-in font
    $line_height = imagefontheight($font);
    $text_block_height = count($lines) * $line_height;
    $y = intval(($h - $text_block_height) / 2);
    foreach ($lines as $line) {
        $line = trim($line);
        $text_width = imagefontwidth($font) * strlen($line);
        $x = intval(($w - $text_width) / 2);
        imagestring($im, $font, $x, $y, $line, $txtc);
        $y += $line_height;
    }
    // small branding
    $brand = 'MyShopee';
    imagestring($im, 2, 10, $h - 24, $brand, imagecolorallocate($im, 120,120,120));

    imagepng($im, $dst_path);
    imagedestroy($im);

    $filename = $img_name;
    $image_url = BASE_URL . '/uploads/temp/' . $filename;
}

echo json_encode([
    'success' => true,
    'product_name' => $ai_result['product_name'] ?? '',
    'description' => $ai_result['description'] ?? '',
    'tags' => $tags,
    'seo_title' => $seo['seo_title'] ?? '',
    'seo_description' => $seo['seo_description'] ?? '',
    'seo_keywords' => $seo['seo_keywords'] ?? '',
    'image_url' => $image_url,
    'image_filename' => $filename
]);

?>
