<?php
/**
 * analyze-model.php  (MediaPipe version)
 *
 * MediaPipe now runs entirely in the browser (WASM).
 * This endpoint receives the already-extracted landmark metrics
 * from the client and returns derived pose hints + structured attributes,
 * so the rest of the app (shoot-results.php, AI chatbot) still gets
 * the same JSON shape it always expected.
 *
 * POST body (JSON):
 * {
 *   "face_shape"        : "oval" | "round" | "square" | "heart" | "diamond" | "oblong",
 *   "face_symmetry"     : "high" | "medium" | "natural",
 *   "jawline"           : "sharp" | "soft" | "round",
 *   "forehead"          : "wide" | "medium" | "narrow",
 *   "shoulder_width"    : "narrow" | "medium" | "broad",
 *   "waist_definition"  : "defined" | "moderate" | "minimal",
 *   "hip_ratio"         : "narrow" | "balanced" | "wide",
 *   "body_type"         : "hourglass" | "pear" | "apple" | "rectangle" | "inverted_triangle" | "athletic",
 *   "leg_proportion"    : "short" | "average" | "long",
 *   "neck_length"       : "short" | "medium" | "long",
 *   "posture"           : "upright" | "slightly_forward" | "relaxed",
 *   "overall_presence"  : "petite" | "balanced" | "commanding" | "statuesque",
 *   "recommended_angles": [...],
 *   "avoid_angles"      : [...],
 *   "confidence"        : "high" | "medium" | "low",
 *   // optionally passed through as-is:
 *   "estimated_height"  : "petite" | "average" | "tall",
 *   "arm_length"        : "short" | "average" | "long",
 *   "skin_tone"         : "fair"|"light"|"medium"|"tan"|"deep",
 *   "hair_length"       : string,
 *   "hair_texture"      : string
 * }
 */

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

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['body_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing landmark data']);
    exit;
}

// ── Derive pose hints ─────────────────────────────────────────────────────
$data['pose_hints'] = derivePoseHints($data);

// ── Echo enriched JSON back ───────────────────────────────────────────────
echo json_encode($data);


// ─────────────────────────────────────────────────────────────────────────
function derivePoseHints(array $a): array
{
    $hints = [];

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

    if (!empty($a['leg_proportion'])) {
        if ($a['leg_proportion'] === 'short')
            $hints[] = 'Shoot from a lower angle to elongate the legs.';
        elseif ($a['leg_proportion'] === 'long')
            $hints[] = 'Full-length shots will make a dramatic impact — use wide framing.';
    }

    if (!empty($a['neck_length'])) {
        if ($a['neck_length'] === 'short')
            $hints[] = 'Avoid high necklines in styling — open neckline elongates.';
        elseif ($a['neck_length'] === 'long')
            $hints[] = 'Elongated neck reads as elegant — use upward chin tilts.';
    }

    if (!empty($a['shoulder_width'])) {
        if ($a['shoulder_width'] === 'broad')
            $hints[] = 'Three-quarter body angle minimizes shoulder width naturally.';
        elseif ($a['shoulder_width'] === 'narrow')
            $hints[] = 'Straight-on shoulder poses add presence and strength.';
    }

    return $hints;
}