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

$apiKey = 'AIzaSyAx8DOuaqDY6ijjFDZm9kPxXNVjnAEdZv8'; // reuse same key as detect-colour.php

$prompt = 'You are a professional body analysis AI for photography pose planning. Analyze this image of a person and extract ALL of the following physical attributes for the purpose of recommending the most flattering photography poses.

Return ONLY a valid JSON object — no explanation, no markdown, no backticks. The JSON must have exactly these keys:

{
  "body_type": "one of: hourglass / pear / apple / rectangle / inverted_triangle / athletic",
  "estimated_height": "one of: petite / average / tall",
  "shoulder_width": "one of: narrow / medium / broad",
  "waist_definition": "one of: defined / moderate / minimal",
  "hip_ratio": "one of: narrow / balanced / wide",
  "neck_length": "one of: short / medium / long",
  "leg_proportion": "one of: short / average / long",
  "arm_length": "one of: short / average / long",
  "posture": "one of: upright / slightly_forward / relaxed",
  "face_shape": "one of: oval / round / square / heart / diamond / oblong",
  "face_symmetry": "one of: high / medium / natural",
  "jawline": "one of: sharp / soft / round",
  "forehead": "one of: wide / medium / narrow",
  "skin_tone": "one of: fair / light / medium / tan / deep",
  "hair_length": "one of: bald / very_short / short / medium / long / very_long",
  "hair_texture": "one of: straight / wavy / curly / coily / unknown",
  "overall_presence": "one of: petite / balanced / commanding / statuesque",
  "recommended_angles": ["list of 2-3 best camera angles as short strings, e.g. slightly_above / eye_level / three_quarter"],
  "avoid_angles": ["list of 1-2 angles to avoid as short strings"],
  "confidence": "one of: high / medium / low (how confident you are in this analysis)"
}

If you cannot detect a person in the image, return: {"error": "no_person_detected"}
If a feature is unclear, use your best estimate — never leave a field empty.';

$payload = json_encode([
    'contents' => [[
        'parts' => [
            [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data'      => $base64
                ]
            ],
            ['text' => $prompt]
        ]
    ]],
    'generationConfig' => [
        'temperature'     => 0.1,
        'topP'            => 0.8,
        'maxOutputTokens' => 1024,
    ]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

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
    echo json_encode(['error' => 'cURL error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Gemini API returned HTTP ' . $httpCode]);
    exit;
}

$decoded = json_decode($response, true);
$rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Strip markdown fences if present
$rawText = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
$rawText = preg_replace('/\s*```$/', '', $rawText);
$rawText = trim($rawText);

$analysis = json_decode($rawText, true);

if (!$analysis || isset($analysis['error'])) {
    http_response_code(422);
    echo json_encode(['error' => $analysis['error'] ?? 'Failed to parse body analysis']);
    exit;
}

// Add derived pose hints based on body analysis
$analysis['pose_hints'] = derivePoseHints($analysis);

echo json_encode($analysis);


// ── Derive pose hints from analysis ─────────────────────────────────────
function derivePoseHints(array $a): array {
    $hints = [];

    // Body type hints
    $bodyHints = [
        'hourglass'         => 'Accentuate the waist — hands on hips, S-curve poses work beautifully.',
        'pear'              => 'Draw attention upward — strong shoulder poses, A-line stances.',
        'apple'             => 'Elongate the torso — side angles, slight lean forward.',
        'rectangle'         => 'Create curves — hip pop, twisted torso, diagonal body lines.',
        'inverted_triangle' => 'Balance the frame — hip emphasis, low-angle shots.',
        'athletic'          => 'Show strength and line — power poses, dynamic movement.',
    ];
    if (!empty($a['body_type']) && isset($bodyHints[$a['body_type']])) {
        $hints[] = $bodyHints[$a['body_type']];
    }

    // Face shape hints
    $faceHints = [
        'round'   => 'Tilt chin slightly down and forward to define the jawline.',
        'square'  => 'Soft three-quarter angle softens the jaw — avoid straight-on.',
        'heart'   => 'Eye-level or slightly above — draws balance to forehead.',
        'oblong'  => 'Avoid very high angles — eye-level or slightly low is best.',
        'oval'    => 'Most angles work well — classic three-quarter is universally flattering.',
        'diamond' => 'Highlight cheekbones — three-quarter angle with slight chin tilt.',
    ];
    if (!empty($a['face_shape']) && isset($faceHints[$a['face_shape']])) {
        $hints[] = $faceHints[$a['face_shape']];
    }

    // Leg proportion hints
    if (!empty($a['leg_proportion'])) {
        if ($a['leg_proportion'] === 'short') {
            $hints[] = 'Shoot from a lower angle to elongate the legs.';
        } elseif ($a['leg_proportion'] === 'long') {
            $hints[] = 'Full-length shots will make a dramatic impact — use wide framing.';
        }
    }

    // Neck length hints
    if (!empty($a['neck_length'])) {
        if ($a['neck_length'] === 'short') {
            $hints[] = 'Avoid high necklines in styling — open neckline elongates.';
        } elseif ($a['neck_length'] === 'long') {
            $hints[] = 'Elongated neck reads as elegant — use upward chin tilts.';
        }
    }

    // Shoulder hints
    if (!empty($a['shoulder_width'])) {
        if ($a['shoulder_width'] === 'broad') {
            $hints[] = 'Three-quarter body angle minimizes shoulder width naturally.';
        } elseif ($a['shoulder_width'] === 'narrow') {
            $hints[] = 'Straight-on shoulder poses add presence and strength.';
        }
    }

    return $hints;
}