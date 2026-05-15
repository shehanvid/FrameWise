<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$base64 = $data['base64'] ?? '';
$mime   = $data['mime']   ?? 'image/jpeg';

if (!$base64) {
    http_response_code(400);
    echo json_encode(['error' => 'No image data']);
    exit;
}

$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';

$payload = json_encode([
    'contents' => [[
        'parts' => [
            [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data'      => $base64
                ]
            ],
            [
                'text' => 'You are a colour detection system. Your ONLY job is to output the dominant colour of the clothing item in the image.

                            STRICT RULES:
                            - Output ONLY the colour name. Nothing else. No sentences. No explanation. No punctuation. No quotes.
                            - Use 1 to 3 words maximum (e.g. forest green, navy blue, cream, burgundy red, olive green, dusty rose, charcoal grey)
                            - If the item has a pattern (e.g. plaid, floral), name the dominant background colour
                            - You MUST always give a colour. Even if unsure, give your closest guess (e.g. "dark blue" instead of "unknown")
                            - Only output "unknown" if the image contains absolutely no clothing item at all
                            - Never say things like "I cannot", "the colour is", "it appears", "approximately" — just the colour name

                            Examples of correct output:
                            cream
                            forest green
                            navy blue
                            dusty rose
                            charcoal grey
                            burnt orange'
            ]
        ]
    ]]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => $curlError]);
    exit;
}

// Parse Gemini response and return in a simple format
$decoded = json_decode($response, true);
$text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? 'unknown';
$text = strtolower(trim($text));

echo json_encode(['colour' => $text]);