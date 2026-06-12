<?php
header('Content-Type: application/json');

error_log("=== ai-pose-match.php START ===");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERROR: Not a POST request");
    http_response_code(405); exit;
}

require 'dbh.inc.php';
error_log("DB connected OK");


$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
    error_log(".env loaded OK");
} else {
    error_log("WARNING: .env file not found at: " . $envPath);
}

$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
error_log("Gemini API key present: " . (!empty($apiKey) ? 'YES (starts with ' . substr($apiKey, 0, 8) . '...)' : 'NO - MISSING'));

$data = json_decode(file_get_contents('php://input'), true);
error_log("Raw input: " . file_get_contents('php://input'));

if (!$data || !isset($data['body_type'])) {
    error_log("ERROR: Invalid or missing data. data=" . json_encode($data));
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing data']);
    exit;
}

error_log("Input data OK. body_type=" . ($data['body_type'] ?? 'null') . " gender=" . ($data['gender'] ?? 'null'));

$defaults = [
    'gender'             => 'female',
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

$gender = $data['gender'] ?? 'female';
error_log("Gender: " . $gender);

$stmt = $conn->prepare("
    SELECT * FROM poses WHERE gender = ? OR gender = 'unisex'
");
$stmt->bind_param("s", $gender);
$stmt->execute();
$result = $stmt->get_result();
$poses = $result->fetch_all(MYSQLI_ASSOC);

error_log("Poses fetched from DB: " . count($poses));

if (count($poses) === 0) {
    error_log("ERROR: No poses found in DB for gender=" . $gender);
    echo json_encode(['error' => 'No poses found in database', 'poses' => []]);
    exit;
}

$poseData    = [];
$availableIds = [];
$poseLines   = '';

foreach ($poses as $pose) {
    $availableIds[]            = $pose['pose_id'];
    $poseData[$pose['pose_id']] = $pose;
    $poseLines .= "
    ID: {$pose['pose_id']}
    Name: {$pose['name']}
    Description: {$pose['description']}
    Category: {$pose['category']}
    Position: {$pose['body_position']}
    Mood: {$pose['mood']}
    Suitable For: {$pose['suitable_for']}
    Difficulty: {$pose['difficulty']}
    Tags: {$pose['tags']}
    ";
}

error_log("Available pose IDs: " . implode(', ', $availableIds));

$prompt = "You are a professional photography pose director with deep knowledge of body types, face shapes, and shoot styles.

AVAILABLE POSES:

{$poseLines}

IMPORTANT:
- You MUST ONLY choose pose IDs from the list above.
- Never invent pose IDs.
- Return exactly 7 unique pose IDs.

SHOOT DETAILS:
- Shoot type: {$data['shoot_type']}
- Model gender: {$data['gender']}
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
Choose exactly 7 pose IDs from the list above that will produce the most flattering, on-brand results for this specific model and shoot.

Think through:
1. Which poses complement this body type and face shape?
2. Which poses match the shoot type and mood?
3. Which poses suit the photographer's experience level?
4. Which poses work well for the target platform's aspect ratio?
5. Are the 7 poses varied enough (mix of standing, seated, movement, close-up)?

Return ONLY a valid JSON array of exactly 7 pose IDs. No explanation. No markdown. No extra text.
Example output: [\"classic-three-quarter\",\"s-curve\",\"window-light-profile\",\"seated-editorial\",\"profile-silhouette\"]";

error_log("Calling Gemini API...");

$payload = json_encode([
    'contents'         => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature'     => 0.2,
        'maxOutputTokens' => 20*7,
    ]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
]);
$response   = curl_exec($ch);
$curlError  = curl_error($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("Gemini HTTP status: " . $httpCode);
error_log("Gemini cURL error: " . ($curlError ?: 'none'));
error_log("Gemini raw response: " . $response);

if ($curlError) {
    error_log("ERROR: cURL failed - " . $curlError);
    echo json_encode(['error' => 'Gemini cURL error: ' . $curlError, 'poses' => []]);
    exit;
}

$decoded = json_decode($response, true);

if (!$decoded) {
    error_log("ERROR: Could not decode Gemini response as JSON");
    echo json_encode(['error' => 'Gemini response not valid JSON', 'poses' => []]);
    exit;
}

if (isset($decoded['error'])) {
    error_log("ERROR: Gemini API error - " . json_encode($decoded['error']));
    echo json_encode(['error' => 'Gemini API error: ' . ($decoded['error']['message'] ?? 'unknown'), 'poses' => []]);
    exit;
}

$rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
$rawText = trim(preg_replace('/```json|```/', '', $rawText));
error_log("Gemini parsed text: " . $rawText);

$poseIds = json_decode($rawText, true) ?? [];
error_log("Decoded pose IDs from Gemini: " . json_encode($poseIds));


$validPoseIds = array_filter($poseIds, fn($id) => in_array($id, $availableIds));
error_log("Valid pose IDs after filter: " . json_encode(array_values($validPoseIds)));
error_log("Invalid IDs rejected: " . json_encode(array_values(array_diff($poseIds, $availableIds))));

$selectedPoses = [];
foreach ($validPoseIds as $id) {
    if (!isset($poseData[$id])) continue;
    $selectedPoses[] = [
        'id'          => $id,
        'name'        => $poseData[$id]['name'],
        'description' => $poseData[$id]['description'],
        'category'    => $poseData[$id]['category'],
        'tags'        => $poseData[$id]['tags'],
        'image'       => "/FrameWise/assets/poses/" . $poseData[$id]['image_file']
    ];
}

error_log("Selected poses after validation: " . count($selectedPoses));


if (count($selectedPoses) < 7) {
    error_log("WARNING: Not enough valid poses (" . count($selectedPoses) . ") — using fallback shuffle");
    shuffle($poses);
    $selectedPoses = [];
    foreach (array_slice($poses, 0, 7) as $pose) {
        $selectedPoses[] = [
            'id'          => $pose['pose_id'],
            'name'        => $pose['name'],
            'description' => $pose['description'],
            'category'    => $pose['category'],
            'tags'        => $pose['tags'],
            'image'       => "/FrameWise/assets/poses/" . $pose['image_file']
        ];
    }
    error_log("Fallback poses: " . json_encode(array_column($selectedPoses, 'id')));
}

error_log("=== ai-pose-match.php END — returning " . count($selectedPoses) . " poses ===");

echo json_encode(['poses' => $selectedPoses]);