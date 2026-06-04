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

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['body_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing data']);
    exit;
}

$defaults = [
    'estimated_height'   => 'unknown',
    'posture'            => 'unknown',
    'face_symmetry'      => 'unknown',
    'jawline'            => 'unknown',
    'forehead'           => 'unknown',
    'arm_length'         => 'unknown',
    'waist_definition'   => 'unknown',
    'skin_tone'          => 'unknown',
    'recommended_angles' => '',
    'avoid_angles'       => '',
    'shoot_type'         => 'unknown',
    'mood'               => 'unknown',
    'outfit'             => 'unknown',
    'location'           => 'unknown',
    'experience'         => 'unknown',
    'platform'           => 'unknown',
    'lighting_style'     => 'unknown',
    'overall_presence'   => 'unknown',
    'face_shape'         => 'unknown',
    'shoulder_width'     => 'unknown',
    'hip_ratio'          => 'unknown',
    'leg_proportion'     => 'unknown',
    'neck_length'        => 'unknown',
];
$data = array_merge($defaults, $data);

// 1. Build pose data from files
$posesDir     = __DIR__ . '/../assets/poses/';
$poseData     = [];
$availableIds = [];

foreach (scandir($posesDir) as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;

    $id               = pathinfo($file, PATHINFO_FILENAME);
    $availableIds[]   = $id;
    $poseData[$id]    = [
        'name'        => ucwords(str_replace('-', ' ', $id)),
        'description' => '',
        'tag'         => '',
    ];
}

// 2. Build poseLines string
$poseLines = '';
foreach ($availableIds as $id) {
    $label      = ucwords(str_replace('-', ' ', $id));
    $meta       = $poseData[$id] ?? ['description' => '', 'tag' => ''];
    $poseLines .= "{$id} | {$label}" .
        ($meta['description'] ? " | {$meta['description']}" : '') . "\n";
}

// 3. Build the full prompt as one string
$prompt = "You are a professional photography pose director with deep knowledge of body types, face shapes, and shoot styles.

AVAILABLE POSES (these are the ONLY poses you can choose from):
{$poseLines}

SHOOT DETAILS:
- Shoot type: {$data['shoot_type']}
- Mood: {$data['mood']}
- Outfit color: {$data['outfit']}
- Location: {$data['location']}
- Photographer experience: {$data['experience']}
- Target platform: {$data['platform']}
- Lighting style preference: {$data['lighting_style']}

MODEL BODY ANALYSIS (from MediaPipe):
- Body type: {$data['body_type']}
- Overall presence: {$data['overall_presence']}
- Estimated height: {$data['estimated_height']}
- Face shape: {$data['face_shape']}
- Face symmetry: {$data['face_symmetry']}
- Jawline: {$data['jawline']}
- Shoulder width: {$data['shoulder_width']}
- Waist definition: {$data['waist_definition']}
- Hip ratio: {$data['hip_ratio']}
- Leg proportion: {$data['leg_proportion']}
- Neck length: {$data['neck_length']}
- Arm length: {$data['arm_length']}
- Posture: {$data['posture']}
- Recommended angles: {$data['recommended_angles']}
- Angles to avoid: {$data['avoid_angles']}

TASK:
Choose exactly 5 pose IDs from the list above that will produce the most flattering, on-brand results for this specific model and shoot.

Think through:
1. Which poses complement this body type and face shape?
2. Which poses match the shoot type and mood?
3. Which poses suit the photographer's experience level?
4. Which poses work well for the target platform's aspect ratio?
5. Are the 5 poses varied enough (mix of standing, seated, movement, close-up)?

Return ONLY a valid JSON array of exactly 5 pose IDs. No explanation. No markdown. No extra text.
Example output: [\"classic-three-quarter\",\"s-curve\",\"window-light-profile\",\"seated-editorial\",\"profile-silhouette\"]";

// 4. Call Gemini API
$apiKey  = $_ENV['GEMINI_API_KEY'] ?? '';
$payload = json_encode([
    'contents'         => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature'     => 0.2,
        'maxOutputTokens' => 80,
    ]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
curl_close($ch);

$decoded = json_decode($response, true);
$rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
$rawText = trim(preg_replace('/```json|```/', '', $rawText));
$poseIds = json_decode($rawText, true) ?? [];

// 5. Only keep IDs that have an actual image file
$poseIds = array_filter($poseIds, fn($id) => in_array($id, $availableIds));

// 6. Build selected poses response
$selectedPoses = [];
foreach ($poseIds as $id) {
    if (isset($poseData[$id])) {
        $selectedPoses[] = array_merge(
            ['id' => $id, 'image' => "assets/poses/{$id}.jpg"],
            $poseData[$id]
        );
    }
}

// 7. Fallback: if AI returned garbage, send first 5
if (empty($selectedPoses)) {
    $selectedPoses = array_slice(
        array_map(
            fn($id, $meta) => array_merge(['id' => $id, 'image' => "assets/poses/{$id}.jpg"], $meta),
            array_keys($poseData),
            array_values($poseData)
        ),
        0, 5
    );
}

echo json_encode(['poses' => $selectedPoses]);