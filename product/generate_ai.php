<?php
include '../includes/config.php';
include '../includes/auth_check.php';

header('Content-Type: application/json');

// Cek role yang diizinkan
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'seller'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Konfigurasi API Kolosal AI
$api_key = 'kol_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoiZWIxOGNkZTQtMjU0YS00ZmY0LThmZjMtODNlYWI3Y2RlZDY0Iiwia2V5X2lkIjoiZGNlMjU4MjktYjA1NC00OWIwLTkzMzEtZjUzMjZjOGZmYmNkIiwia2V5X25hbWUiOiJoYWNrYXRob24ta29sb3NhbC10ZWtub3BlbmEiLCJlbWFpbCI6ImJ3Znpid0BnbWFpbC5jb20iLCJyYXRlX2xpbWl0X3JwcyI6bnVsbCwibWF4X2NyZWRpdF91c2UiOm51bGwsImNyZWF0ZWRfYXQiOjE3NjQ1NTg5MDcsImV4cGlyZXNfYXQiOjE3OTYwOTQ5MDcsImlhdCI6MTc2NDU1ODkwN30.1iHBL0WzkxH6JFrQhYghvBtzcTRQavs_lIAL0U_UU6o'; // GANTI DENGAN API KEY ANDA
$api_url = 'https://api.kolosal.ai/v1/chat/completions';

// Ambil data dari request
$field = $_POST['field'] ?? 'all';
$fallback_description = $_POST['fallback_description'] ?? '';

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    // Handle upload gambar
    $image_base64 = null;
    $ai_image_filename = null;
    $image_url = null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Validasi file
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Format gambar tidak didukung. Gunakan JPG, PNG, atau GIF.');
        }
        
        // Cek ukuran file (max 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Ukuran gambar terlalu besar. Maksimal 5MB.');
        }
        
        // Generate unique filename
    $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $ai_image_filename = 'ai_gen_' . uniqid() . '_' . time() . '.' . $file_ext;
        $temp_path = UPLOAD_PATH . 'temp/' . $ai_image_filename;
        
        // Pastikan folder temp ada
        if (!is_dir(UPLOAD_PATH . 'temp/')) {
            mkdir(UPLOAD_PATH . 'temp/', 0777, true);
        }
        
        // Pindahkan file upload
        if (move_uploaded_file($_FILES['image']['tmp_name'], $temp_path)) {
            // Konversi ke base64
            $image_data = file_get_contents($temp_path);
            $image_base64 = base64_encode($image_data);
            $image_url = BASE_URL . '/uploads/temp/' . $ai_image_filename;
        } else {
            throw new Exception('Gagal menyimpan gambar sementara. Pastikan folder uploads/temp dapat ditulis.');
        }
    }
    
    // Siapkan prompt berdasarkan field yang diminta
    $prompts = [
        'all' => "Anda adalah asisten e-commerce yang ahli. Analisis gambar produk ini dan berikan informasi yang optimal untuk dijual online di Indonesia.

Berdasarkan gambar ini, berikan dalam format berikut:
1. NAMA: [Nama produk yang menarik, max 60 karakter, gunakan kata kunci populer]
2. DESKRIPSI: [Deskripsi produk yang informatif, 100-150 kata, jelaskan fitur, manfaat, dan keunggulan]
3. TAGS: [5-8 tag/keyword relevan, bahasa Indonesia, pisahkan koma]
4. SEO_TITLE: [Judul SEO optimal, 50-60 karakter, termasuk kata kunci utama]
5. SEO_DESC: [Deskripsi SEO, 150-160 karakter, persuasif dan informatif]

Contoh format output:
NAMA: Sepatu Running Pria Anti Slip Nike Air Max
DESKRIPSI: Sepatu running pria terbaru dengan teknologi ... (dst)
TAGS: sepatu running, sepatu pria, nike, olahraga, fitness
SEO_TITLE: Sepatu Running Nike Air Max - Nyaman & Anti Slip
SEO_DESC: Beli sepatu running Nike Air Max pria terbaru. Teknologi ... (dst) Dapatkan harga terbaik!",

        'name' => "Analisis gambar produk ini dan berikan nama produk yang optimal untuk dijual di e-commerce Indonesia.
Nama harus: 
1. Max 60 karakter
2. Mengandung kata kunci populer
3. Menarik dan deskriptif
4. Bahasa Indonesia yang baik",

        'description' => "Berdasarkan gambar produk ini, buat deskripsi produk yang informatif dan persuasif untuk e-commerce.
Deskripsi harus:
1. 100-150 kata
2. Menjelaskan fitur, manfaat, dan keunggulan
3. Menggunakan bahasa pemasaran yang menarik
4. Format paragraf yang rapi",

        'tags' => "Analisis gambar produk ini dan berikan 5-8 tags/keywords yang relevan untuk optimasi e-commerce.
Tags harus:
1. Relevan dengan produk
2. Kata kunci populer di Indonesia
3. Pisahkan dengan koma
4. Gunakan bahasa Indonesia"
    ];
    
    $prompt = $prompts[$field] ?? $prompts['all'];
    
    // Tambahkan konteks jika ada
    if (!empty($fallback_description)) {
        if ($field === 'name') {
            $prompt .= "\n\nKonteks: $fallback_description";
        } elseif ($field === 'description' || $field === 'tags') {
            $prompt .= "\n\nNama produk: $fallback_description";
        }
    }
    
    // Siapkan messages untuk API
    $messages = [
        [
            "role" => "system",
            "content" => "Anda adalah asisten ahli e-commerce yang membantu mengoptimalkan listing produk untuk penjualan online di Indonesia. Berikan respons dalam format yang diminta."
        ],
        [
            "role" => "user",
            "content" => []
        ]
    ];
    
    // Tambahkan teks prompt
    $messages[1]['content'][] = [
        "type" => "text",
        "text" => $prompt
    ];
    
    // Jika ada gambar, tambahkan image_url
    if ($image_base64) {
        $messages[1]['content'][] = [
            "type" => "image_url",
            "image_url" => [
                "url" => "data:image/jpeg;base64," . $image_base64
            ]
        ];
    }
    
    // Data untuk API request
    $data = [
        "model" => "Qwen 3 30BA3B",
        "messages" => $messages,
        "max_tokens" => 15000,
        "temperature" => 0.7,
        "top_p" => 0.9
    ];
    
    // Kirim request ke API Kolosal AI
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        throw new Exception("CURL Error: " . $curl_error);
    }

    if ($http_code === 200) {
        $result = json_decode($api_response, true);

        if ($result === null) {
            throw new Exception('Respons API bukan JSON: ' . substr($api_response, 0, 200));
        }

        // Helper to extract message content safely (handle different shapes)
        $getContent = function($res) {
            // Try common paths
            if (isset($res['choices'][0]['message']['content'])) {
                return $res['choices'][0]['message']['content'];
            }
            if (isset($res['choices'][0]['message'])) {
                $msg = $res['choices'][0]['message'];
                if (is_string($msg)) return $msg;
                if (isset($msg['content'])) return $msg['content'];
            }
            // older/simple format
            if (isset($res['choices'][0]['text'])) return $res['choices'][0]['text'];
            return null;
        };

        $raw_content = $getContent($result);
        if ($raw_content === null) {
            throw new Exception("Format respons API tidak valid");
        }

        // If content is array/structured, convert to string (join text parts)
        if (is_array($raw_content)) {
            // If array of blocks
            $ai_text = '';
            foreach ($raw_content as $block) {
                if (is_string($block)) $ai_text .= $block . "\n";
                elseif (is_array($block) && isset($block['text'])) $ai_text .= $block['text'] . "\n";
                elseif (is_array($block) && isset($block['content'])) $ai_text .= (is_string($block['content']) ? $block['content'] : json_encode($block['content'])) . "\n";
            }
            $ai_text = trim($ai_text);
        } else {
            $ai_text = trim($raw_content);
        }
        
        // Parse response berdasarkan field
        if ($field === 'all') {
            // Parse semua field dari format terstruktur
            $product_name = '';
            $description = '';
            $tags = [];
            $seo_title = '';
            $seo_description = '';
            
            $lines = explode("\n", $ai_text);
            $current_section = '';
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if (empty($line)) continue;
                
                if (strpos($line, 'NAMA:') === 0) {
                    $product_name = trim(substr($line, 5));
                    $current_section = 'name';
                } elseif (strpos($line, 'DESKRIPSI:') === 0) {
                    $description = trim(substr($line, 10));
                    $current_section = 'description';
                } elseif (strpos($line, 'TAGS:') === 0) {
                    $tags_str = trim(substr($line, 5));
                    $tags = array_map('trim', explode(',', $tags_str));
                    $current_section = 'tags';
                } elseif (strpos($line, 'SEO_TITLE:') === 0) {
                    $seo_title = trim(substr($line, 10));
                    $current_section = 'seo_title';
                } elseif (strpos($line, 'SEO_DESC:') === 0) {
                    $seo_description = trim(substr($line, 10));
                    $current_section = 'seo_description';
                } else {
                    // Tambahkan ke section yang sedang aktif
                    switch ($current_section) {
                        case 'description':
                            $description .= "\n" . $line;
                            break;
                        case 'seo_description':
                            $seo_description .= " " . $line;
                            break;
                    }
                }
            }
            
            // Clean up descriptions
            $description = trim($description);
            $seo_description = trim($seo_description);
            
            // Fallback jika parsing gagal
            if (empty($product_name) && !empty($ai_text)) {
                $product_name = "Produk " . date('YmdHis');
            }
            
            // Jika SEO title kosong, buat dari nama produk
            if (empty($seo_title) && !empty($product_name)) {
                // Keep SEO title concise; avoid relying on SITE_NAME constant here
                $seo_title = $product_name;
            }
            
            // Jika SEO description kosong, buat dari deskripsi
            if (empty($seo_description) && !empty($description)) {
                $seo_description = substr($description, 0, 150) . (strlen($description) > 150 ? '...' : '');
            }
            
            $response = [
                'success' => true,
                'product_name' => $product_name,
                'description' => $description,
                'tags' => $tags,
                'seo_title' => $seo_title,
                'seo_description' => $seo_description,
                'image_filename' => $ai_image_filename,
                'image_url' => $image_url
            ];
            
        } else {
            // Untuk single field generation
            $ai_text = trim($ai_text);
            
            // Parse berdasarkan field
            switch ($field) {
                case 'name':
                    $product_name = $ai_text;
                    // Hapus prefix jika ada
                    if (strpos($ai_text, 'NAMA:') === 0) {
                        $product_name = trim(substr($ai_text, 5));
                    }
                    $response = [
                        'success' => true,
                        'product_name' => $product_name,
                        'image_filename' => $ai_image_filename,
                        'image_url' => $image_url
                    ];
                    break;
                    
                case 'description':
                    $description = $ai_text;
                    if (strpos($ai_text, 'DESKRIPSI:') === 0) {
                        $description = trim(substr($ai_text, 10));
                    }
                    $response = [
                        'success' => true,
                        'description' => $description,
                        'image_filename' => $ai_image_filename,
                        'image_url' => $image_url
                    ];
                    break;
                    
                case 'tags':
                    $tags_str = $ai_text;
                    if (strpos($ai_text, 'TAGS:') === 0) {
                        $tags_str = trim(substr($ai_text, 5));
                    }
                    $tags = array_map('trim', explode(',', $tags_str));
                    $response = [
                        'success' => true,
                        'tags' => $tags,
                        'image_filename' => $ai_image_filename,
                        'image_url' => $image_url
                    ];
                    break;
                    
                default:
                    $response = [
                        'success' => true,
                        'message' => $ai_text,
                        'image_filename' => $ai_image_filename,
                        'image_url' => $image_url
                    ];
            }
        }
        
    } else {
        // Handle API error
        error_log("Kolosal AI API Error $http_code: " . $api_response);
        
        if ($http_code === 401) {
            throw new Exception("API Key tidak valid. Silakan periksa konfigurasi API.");
        } elseif ($http_code === 429) {
            throw new Exception("Quota API habis atau terlalu banyak request.");
        } else {
            $error_msg = "API Error ($http_code)";
            if (!empty($api_response)) {
                $error_data = json_decode($api_response, true);
                if (isset($error_data['error']['message'])) {
                    $error_msg .= ": " . $error_data['error']['message'];
                }
            }
            throw new Exception($error_msg);
        }
    }
    
} catch (Exception $e) {
    error_log("AI Generation Error: " . $e->getMessage());
    
    // Fallback response jika AI gagal
    $product_name = !empty($fallback_description) ? $fallback_description : "Produk " . date('YmdHis');
    
    $response = [
        'success' => true,
        'product_name' => $product_name,
        'description' => "Deskripsi untuk " . $product_name . ". Produk berkualitas dengan harga terjangkau. Cocok untuk berbagai kebutuhan.",
        'tags' => ['produk', 'online', 'ecommerce', 'terbaru'],
    'seo_title' => $product_name,
        'seo_description' => 'Beli ' . $product_name . ' dengan kualitas terbaik. Harga murah, bergaransi, dan pengiriman cepat.',
        'image_filename' => $ai_image_filename ?? null,
        'image_url' => $image_url ?? null,
        'note' => 'Fallback response karena AI error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>