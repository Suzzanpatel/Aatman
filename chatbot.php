<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$apiKey = "AIzaSyCs5oZLhERbNwN3-kMNSh-6Lo_99BM_0hY"; // replace with your Gemini API key

$data = json_decode(file_get_contents("php://input"), true);
$userMessage = $data["message"] ?? "";

if (!$userMessage) {
    echo json_encode(["reply" => "Please say something!"]);
    exit;
}

$modelId = "gemini-2.0-flash"; // or whatever is supported per ListModels
$url = "https://generativelanguage.googleapis.com/v1/models/".$modelId.":generateContent?key=".$apiKey;

$payload = json_encode([
    "contents" => [[
        "parts" => [[
            "text" => "You are Aatman's virtual assistant — warm and kind, connecting NGOs, donors, volunteers, and people in need. The user said: $userMessage"
        ]]
    ]]
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => $payload
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["reply" => "⚠️ cURL Error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$result = json_decode($response, true);

if (!isset($result["candidates"])) {
    echo json_encode([
        "reply" => "⚠️ Gemini Error",
        "debug_raw" => $response
    ]);
    exit;
}

$reply = $result["candidates"][0]["content"]["parts"][0]["text"] ?? "⚠️ No text field returned";

echo json_encode(["reply" => $reply]);
?>
