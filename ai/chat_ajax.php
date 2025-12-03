<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['question'])) {

    $tmp_name = $_FILES['image']['tmp_name'];
    $image_data = file_get_contents($tmp_name);
    $base64_image = 'data:image/jpeg;base64,' . base64_encode($image_data);

    $question = $_POST['question'];

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
        echo json_encode(['error' => curl_error($ch)]);
        exit;
    }
    curl_close($ch);

    header('Content-Type: application/json');
    echo $response;

} else {
    echo json_encode(['error' => 'No image or question']);
}
?>
