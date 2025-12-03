<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['question'])) {

    // 1️⃣ Ambil file gambar
    $tmp_name = $_FILES['image']['tmp_name'];
    $image_data = file_get_contents($tmp_name);
    $base64_image = 'data:image/jpeg;base64,' . base64_encode($image_data);

    // 2️⃣ Ambil pertanyaan user
    $question = $_POST['question'];

    // 3️⃣ Siapkan request JSON
    $postData = [
        "model" => "Qwen 3 30BA3B",
        "messages" => [
            [
                "role" => "user",
                "content" => [
                    ["type" => "input_text", "text" => $question],
                    ["type" => "input_image", "image" => $base64_image]
                ]
            ]
        ]
    ];

    // 4️⃣ Kirim request ke Kolosal
    $ch = curl_init(KOLOSAL_CHAT_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . KOLOSAL_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Curl error: " . curl_error($ch);
        exit;
    }
    curl_close($ch);

    // 5️⃣ Tampilkan hasil JSON
    header('Content-Type: application/json');
    echo $response;
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Chat AI dengan Gambar</title>
</head>
<body>
<h2>Chat AI dengan Gambar</h2>
<form method="POST" enctype="multipart/form-data">
    Pertanyaan: <input type="text" name="question" required><br><br>
    Upload Gambar: <input type="file" name="image" accept="image/*" required><br><br>
    <button type="submit">Kirim ke AI</button>
</form>
</body>
</html>
