<?php
// Câu hỏi từ người dùng
$question = isset($_POST['question']) ? $_POST['question'] : '';

// Tích hợp OpenAI GPT
$apiKey = "your-openai-api-key";  // Thay thế bằng API key thực tế của bạn
$data = [
    'prompt' => "Dữ liệu hàng hóa trong kho:\n" . getInventoryData() . "\nCâu hỏi: " . $question,
    'max_tokens' => 100
];

$options = [
    'http' => [
        'header' => "Content-Type: application/json\r\n" .
                    "Authorization: Bearer $apiKey\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$response = file_get_contents('https://api.openai.com/v1/engines/davinci/completions', false, $context);
$result = json_decode($response, true);

echo "AI Trả lời: " . $result['choices'][0]['text'];

// Hàm để lấy dữ liệu kho từ inventory.php
function getInventoryData() {
    $inventoryData = file_get_contents('http://localhost/inventory.php');  // Đường dẫn đến API inventory.php
    return $inventoryData;
}

