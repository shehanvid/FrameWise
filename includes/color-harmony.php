<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit;
}

// Load .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
if (!$apiKey) {
    echo json_encode(['error' => 'Missing Gemini API key']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['outfit'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing outfit colour']);
    exit;
}

$outfit    = $data['outfit']    ?? 'unknown';
$mood      = $data['mood']      ?? 'unknown';
$shoot_type = $data['shoot_type'] ?? 'unknown';
$location  = $data['location']  ?? 'unknown';
$gender    = $data['gender']    ?? 'unknown';

$prompt = "You are a professional photography color consultant.

SHOOT DETAILS:
- Outfit color: {$outfit}
- Shoot type: {$shoot_type}
- Mood: {$mood}
- Location: {$location}
- Model gender: {$gender}

TASK:
Generate a color harmony analysis for this photography shoot based on the outfit color.

Return ONLY a valid JSON object with exactly this structure. No explanation, no markdown, no extra text:
{
  \"outfit_hex\": \"#hexcode\",
  \"accent_hex\": \"#hexcode\",
  \"shadow_hex\": \"#hexcode\",
  \"backdrop_hex\": \"#hexcode\",
  \"outfit_label\": \"short color name e.g. Dusty Rose\",
  \"accent_label\": \"short color name\",
  \"shadow_label\": \"short color name\",
  \"backdrop_label\": \"short color name\",
  \"harmony_type\": \"e.g. Complementary / Analogous / Triadic\",
  \"tip\": \"2 sentence practical photography tip about using this outfit color in this specific shoot type and mood.\"
}";

$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature'     => 0.3,
        'maxOutputTokens' => 2048,
    ]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
]);
$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

error_log("Color harmony raw response length: " . strlen($response));
error_log("Color harmony raw response: " . $response);

if ($curlError) {
    echo json_encode(['error' => 'cURL error: ' . $curlError]);
    exit;
}

$decoded = json_decode($response, true);

if (!$decoded || isset($decoded['error'])) {
    echo json_encode(['error' => 'Gemini API error: ' . ($decoded['error']['message'] ?? 'unknown')]);
    exit;
}

$rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
$rawText = trim($rawText);
$rawText = preg_replace('/```json|```/i', '', $rawText);
$rawText = trim($rawText);

// Extract just the JSON object if there's extra text around it
if (preg_match('/\{.*\}/s', $rawText, $matches)) {
    $rawText = $matches[0];
}

$result = json_decode($rawText, true);

if (!$result || !isset($result['outfit_hex'])) {
    error_log("Color harmony JSON parse failed. rawText: " . $rawText);
    error_log("Color harmony full decoded: " . json_encode($decoded));
    echo json_encode(['error' => 'Invalid response from Gemini', 'raw' => $rawText, 'full' => $decoded]);
    exit;
}

echo json_encode($result);