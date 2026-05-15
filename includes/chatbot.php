<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$messages = $data['messages'] ?? [];
$context  = $data['context']  ?? [];

if (empty($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'No messages provided']);
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


// ── Build a rich photography system prompt ────────────────────────────────
$bodyAnalysis = '';
if (!empty($context['body_analysis'])) {
    $ba = $context['body_analysis'];
    $bodyAnalysis = "
MODEL ANALYSIS (MediaPipe):
- Body type: {$ba['body_type']}, Presence: {$ba['overall_presence']}
- Face shape: {$ba['face_shape']}, Jawline: {$ba['jawline']}
- Shoulder width: {$ba['shoulder_width']}, Hip ratio: {$ba['hip_ratio']}
- Leg proportion: {$ba['leg_proportion']}, Neck: {$ba['neck_length']}
- Recommended angles: " . implode(', ', $ba['recommended_angles'] ?? []) . "
- Avoid angles: " . implode(', ', $ba['avoid_angles'] ?? []) . "
- Pose hints: " . implode(' | ', $ba['pose_hints'] ?? []);
}

$systemPrompt = "You are an elite photography director and cinematographer with 20+ years of experience 
across fashion, portrait, wedding, street, and commercial photography. You have deep knowledge of:

SHOOT SESSION CONTEXT:
- Location: {$context['location']}
- Date/Time: {$context['datetime']}
- Shoot type: {$context['shoot_type']}
- Mood/Style: {$context['mood']}
- Outfit color: {$context['outfit']}
- Camera type: {$context['camera_type']}
- Photographer experience: {$context['experience']}
- Target platform: {$context['platform']}
- Preferred lighting: {$context['lighting_style']}
{$bodyAnalysis}

YOUR EXPERTISE:
- Camera settings (aperture, shutter, ISO, white balance) for any lighting condition
- Posing techniques for all body types, face shapes, and shoot styles
- Lighting setups: Rembrandt, butterfly, split, loop, broad, short, clamshell
- Color theory, grading, and outfit-backdrop harmony
- Composition: rule of thirds, leading lines, negative space, framing, perspective
- Golden hour, blue hour, harsh midday — how to work with any natural light
- Flash, reflectors, diffusers, and modifier techniques
- Lens selection: primes vs zooms, focal length compression, bokeh characteristics
- Post-processing: Lightroom presets, tone curves, skin retouching workflow
- Platform-specific requirements: Instagram ratios, editorial spreads, portrait prints

RESPONSE STYLE:
- Be concise and practical — give actionable direction, not theory lectures
- Use specific numbers when relevant (f/2.8, not 'wide aperture')
- When suggesting poses, describe body position clearly (chin forward and down, weight on back foot, etc.)
- Format with **bold** for key terms when helpful
- Keep responses under 200 words unless a detailed breakdown is genuinely needed
- Speak like a director on set — confident, clear, encouraging";

// ── Convert chat history to Gemini format ─────────────────────────────────
// Gemini uses 'user' and 'model' roles (not 'assistant')
$geminiContents = [];

// Inject system prompt as first user turn + model acknowledgement
// (Gemini doesn't have a system role in v1beta, so we prime it this way)
$geminiContents[] = [
    'role' => 'user',
    'parts' => [['text' => "SYSTEM INSTRUCTIONS:\n" . $systemPrompt . "\n\nAcknowledge you understand your role."]]
];
$geminiContents[] = [
    'role' => 'model',
    'parts' => [['text' => "Understood. I'm your AI photography director for this " . 
        $context['shoot_type'] . " shoot. Ready to give you precise, actionable direction."]]
];

// Append actual conversation history
foreach ($messages as $msg) {
    $role = $msg['role'] === 'assistant' ? 'model' : 'user';
    $geminiContents[] = [
        'role'  => $role,
        'parts' => [['text' => $msg['content']]]
    ];
}

// ── Call Gemini API ───────────────────────────────────────────────────────
$payload = json_encode([
    'contents'         => $geminiContents,
    'generationConfig' => [
        'temperature'     => 0.7,   // balanced: creative but grounded
        'topP'            => 0.9,
        'topK'            => 40,
        'maxOutputTokens' => 512,   // enough for detailed answers, not rambling
        'stopSequences'   => []
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
    ]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(502);
    $decoded = json_decode($response, true);
    echo json_encode(['error' => $decoded['error']['message'] ?? "HTTP $httpCode"]);
    exit;
}

// ── Parse and return ──────────────────────────────────────────────────────
$decoded = json_decode($response, true);
$reply   = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$reply) {
    // Check for safety block
    $finishReason = $decoded['candidates'][0]['finishReason'] ?? 'UNKNOWN';
    echo json_encode(['error' => "No response generated. Reason: $finishReason"]);
    exit;
}

echo json_encode(['reply' => trim($reply)]);