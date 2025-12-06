<?php
$responseText = '';
$apiDebug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    // Ambil file gambar
    $fileTmpPath = $_FILES['foto']['tmp_name'];
    $fileName = $_FILES['foto']['name'];

    // Ubah jadi Base64
    $imageData = base64_encode(file_get_contents($fileTmpPath));
    $imageMime = mime_content_type($fileTmpPath);
    $imageBase64 = "data:$imageMime;base64,$imageData";

    // Data request ke Kolosal AI
    $data = [
        "model" => "GLM 4.6",
        "messages" => [
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => "Tolong jelaskan isi gambar ini."
                    ],
                    [
                        "type" => "input_image",  // Kolosal AI terbaru
                        "image" => $imageBase64
                    ]
                ]
            ]
        ]
    ];

    // Curl request
    $ch = curl_init("https://api.kolosal.ai/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer api_kolosal"
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    if(curl_errno($ch)) {
        $responseText = 'Curl error: ' . curl_error($ch);
    }
    curl_close($ch);

    // Debug: tampilkan response asli
    $apiDebug = $response;

    // Ambil teks jawaban AI (jika ada)
    $respJson = json_decode($response, true);
    if(isset($respJson['choices'][0]['message']['content'][0]['text'])){
        $responseText = $respJson['choices'][0]['message']['content'][0]['text'];
    } elseif(isset($respJson['choices'][0]['message']['content'][0]['image'])){
        $responseText = "AI mengirim gambar sebagai balasan.";
    } else {
        $responseText = "Gagal mendapatkan jawaban AI.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload dan Analisa Gambar AI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; }
        img { max-width: 300px; display: block; margin-top: 10px; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Upload Gambar untuk Analisa AI</h1>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="foto" accept="image/*" required>
        <button type="submit">Kirim ke AI</button>
    </form>

    <?php if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])): ?>
        <h2>Preview Gambar:</h2>
        <img src="<?php echo 'data:' . $imageMime . ';base64,' . $imageData; ?>" alt="Preview">

        <h2>Hasil Analisa AI:</h2>
        <p><?php echo nl2br(htmlspecialchars($responseText)); ?></p>

        <h2>Response API (Debug):</h2>
        <pre><?php echo htmlspecialchars($apiDebug); ?></pre>
    <?php endif; ?>
</body>
</html>
