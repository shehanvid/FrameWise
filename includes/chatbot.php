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

// ── Predicted camera settings based on sun altitude & shoot context ───────────
function predictCameraSettings(array $ctx): array {
    $sunAltitude  = (float)($ctx['sun_altitude_deg'] ?? -1);
    $shootType    = strtolower($ctx['shoot_type']    ?? 'portrait');
    $mood         = strtolower($ctx['mood']          ?? '');
    $cameraType   = strtolower($ctx['camera_type']   ?? 'dslr');
    $experience   = strtolower($ctx['experience']    ?? 'intermediate');
    $lightStyle   = strtolower($ctx['lighting_style'] ?? 'natural');
    $platform     = strtolower($ctx['platform']      ?? 'instagram');
    $orientation  = strtolower($ctx['orientation']   ?? 'portrait');

    // ── Base settings by sun altitude ─────────────────────────────────────
    if ($sunAltitude < 0) {
        $aperture = 'f/2.0'; $shutter = '1/200s'; $iso = '800'; $wb = '3200K';
        $lightNote = 'Sun below horizon — use flash or continuous artificial light';
        $lightQuality = 'night';
    } elseif ($sunAltitude < 6) {
        $aperture = 'f/1.8'; $shutter = '1/60s';  $iso = '1600'; $wb = '7500K';
        $lightNote = 'Blue hour — cool ethereal light, diffused and low-contrast';
        $lightQuality = 'blue_hour';
    } elseif ($sunAltitude < 15) {
        $aperture = 'f/2.0'; $shutter = '1/250s'; $iso = '200';  $wb = '4000K';
        $lightNote = 'Golden hour — warmest and most flattering natural light of the day';
        $lightQuality = 'golden_hour';
    } elseif ($sunAltitude < 30) {
        $aperture = 'f/2.8'; $shutter = '1/500s'; $iso = '200';  $wb = '5200K';
        $lightNote = 'Low-mid sun — soft shadows, balanced contrast, great for natural work';
        $lightQuality = 'low_sun';
    } elseif ($sunAltitude < 60) {
        $aperture = 'f/4.0'; $shutter = '1/1000s'; $iso = '100'; $wb = '6000K';
        $lightNote = 'High sun — harsh overhead shadows; seek open shade or use a diffuser';
        $lightQuality = 'high_sun';
    } else {
        $aperture = 'f/5.6'; $shutter = '1/2000s'; $iso = '100'; $wb = '6500K';
        $lightNote = 'Near-overhead sun — most unflattering; use shade, reflector, or flash-fill';
        $lightQuality = 'overhead';
    }

    // ── Focal length by shoot type ─────────────────────────────────────────
    $focalMap = [
        'portrait'  => '85mm',
        'fashion'   => '85mm',
        'product'   => '100mm (macro)',
        'street'    => '35mm',
        'landscape' => '24mm',
        'wedding'   => '50mm',
        'boudoir'   => '50mm',
        'newborn'   => '50mm',
        'sports'    => '200mm',
    ];
    $focal = $focalMap[$shootType] ?? '50mm';

    // ── Aperture override by shoot type ───────────────────────────────────
    if ($shootType === 'landscape' || $shootType === 'product') {
        $aperture = ($lightQuality === 'overhead' || $lightQuality === 'high_sun') ? 'f/11' : 'f/8';
    }
    if ($shootType === 'street') {
        $aperture = 'f/5.6'; // zone focus range
    }

    // ── Mood nudges ───────────────────────────────────────────────────────
    if (in_array($mood, ['dramatic', 'moody'])) {
        $iso = (string)min(3200, (int)$iso * 2);
        $wb  = (int)$wb > 4000 ? ((int)$wb - 500) . 'K' : $wb; // cooler WB
    }
    if (in_array($mood, ['airy', 'natural'])) {
        $iso = (string)max(100, (int)$iso / 2);
        $wb  = (int)$wb < 6000 ? ((int)$wb + 300) . 'K' : $wb; // warmer/neutral WB
    }
    if ($mood === 'warm') {
        $wb = (int)$wb < 5500 ? ((int)$wb + 500) . 'K' : $wb;
    }
    if ($mood === 'cool') {
        $wb = (int)$wb > 4500 ? ((int)$wb - 500) . 'K' : $wb;
    }

    // ── Lighting style nudges ─────────────────────────────────────────────
    if ($lightStyle === 'studio') {
        $iso = '100'; $wb = '5500K';
        $lightNote .= '. Studio lighting assumed — use sync speed ≤1/200s if using flash.';
        $shutter = '1/160s';
    }
    if ($lightStyle === 'rembrandt' || $lightStyle === 'dramatic') {
        $aperture = 'f/2.8';
        $lightNote .= '. For Rembrandt: position key light ~45° to side and ~45° above eye level.';
    }
    if ($lightStyle === 'butterfly') {
        $lightNote .= '. Butterfly: key light directly above camera axis, pointed down at 45°.';
    }

    // ── Camera type adjustments ───────────────────────────────────────────
    if (str_contains($cameraType, 'phone') || str_contains($cameraType, 'mobile')) {
        $iso     = (string)min(800, (int)$iso);   // phones struggle with high ISO
        $shutter = '1/500s';                       // avoid motion blur on phone sensors
        $focal   = '24–52mm eq. (native lens)';
    }
    if (str_contains($cameraType, 'mirrorless')) {
        $lightNote .= '. Mirrorless: leverage IBIS — you can push shutter 1–2 stops slower hand-held.';
    }
    if (str_contains($cameraType, 'film')) {
        $iso = 'ISO 400 film';
        $lightNote .= '. Film: rate it 1 stop under box speed for richer shadows.';
    }

    // ── Experience level adjustments ─────────────────────────────────────
    $experienceTips = '';
    if ($experience === 'beginner') {
        $experienceTips = 'Tip for beginners: shoot in Aperture Priority (Av/A) mode — set aperture and let the camera choose shutter. Use Auto-ISO with a max of ' . $iso . '.';
    } elseif ($experience === 'intermediate') {
        $experienceTips = 'Use Manual or Aperture Priority. Meter off the subject\'s skin, not the background.';
    } else {
        $experienceTips = 'Full Manual recommended. Use spot metering on the catchlights, bracket ±1 stop.';
    }

    // ── Platform / output style ───────────────────────────────────────────
    $platformNote = '';
    if (str_contains($platform, 'instagram')) {
        $platformNote = 'Instagram: shoot 4:5 ratio (portrait) or 1:1 (square). Leave breathing room on sides for Stories crop.';
    } elseif (str_contains($platform, 'print') || str_contains($platform, 'editorial')) {
        $platformNote = 'Print/editorial: shoot at minimum 300 DPI equivalent; use tripod for sharpness; shoot tethered if possible.';
    } elseif (str_contains($platform, 'youtube') || str_contains($platform, 'video')) {
        $platformNote = 'Video: set shutter to 2× your frame rate (e.g. 1/50s for 25fps). Use ND filters in bright light.';
    } elseif (str_contains($platform, 'tiktok')) {
        $platformNote = 'TikTok: shoot 9:16 vertical. Keep the subject in the upper-center third.';
    }

    // ── Orientation notes ─────────────────────────────────────────────────
    $orientationNote = '';
    if ($orientation === 'landscape') {
        $orientationNote = 'Landscape/horizontal framing: use the lower third for subject placement to emphasise environment.';
    } elseif ($orientation === 'square') {
        $orientationNote = 'Square framing: centre-compose or place subject at the intersection of thirds.';
    }

    return [
        'aperture'       => $aperture,
        'shutter'        => $shutter,
        'iso'            => $iso,
        'focal_length'   => $focal,
        'white_balance'  => $wb,
        'light_quality'  => $lightQuality,
        'light_note'     => $lightNote,
        'experience_tip' => $experienceTips,
        'platform_note'  => $platformNote,
        'orientation_note' => $orientationNote,
    ];
}

// ── Build body analysis section ───────────────────────────────────────────
$bodyAnalysisSection = '';
$bodyAnalysis = $context['body_analysis'] ?? null;
if (is_string($bodyAnalysis)) $bodyAnalysis = json_decode($bodyAnalysis, true);
if (!empty($bodyAnalysis) && is_array($bodyAnalysis)) {
    $ba = $bodyAnalysis;
    $recAngles  = implode(', ', $ba['recommended_angles'] ?? []);
    $avdAngles  = implode(', ', $ba['avoid_angles']       ?? []);
    $poseHints  = implode(' | ', $ba['pose_hints']        ?? []);
    $bodyAnalysisSection = "
MODEL PHYSICAL ANALYSIS (MediaPipe):
- Body type: {$ba['body_type']}  |  Overall presence: {$ba['overall_presence']}
- Face shape: {$ba['face_shape']}  |  Jawline: {$ba['jawline']}  |  Forehead: {$ba['forehead']}
- Face symmetry: {$ba['face_symmetry']}  |  Posture: {$ba['posture']}
- Shoulder width: {$ba['shoulder_width']}  |  Waist definition: {$ba['waist_definition']}
- Hip ratio: {$ba['hip_ratio']}  |  Leg proportion: {$ba['leg_proportion']}
- Neck length: {$ba['neck_length']}  |  Arm length: {$ba['arm_length']}
- Estimated height: {$ba['estimated_height']}
- Skin tone: {$ba['skin_tone']}  |  Hair length: {$ba['hair_length']}  |  Hair texture: {$ba['hair_texture']}
- Analysis confidence: {$ba['confidence']}
- Recommended angles: {$recAngles}
- Angles to avoid:    {$avdAngles}
- Pose coaching hints: {$poseHints}";
}

// ── Build equipment list ──────────────────────────────────────────────────
$equipmentList = '';
if (!empty($context['equipment']) && is_array($context['equipment'])) {
    $equipmentList = implode(', ', $context['equipment']);
} elseif (!empty($context['equipment'])) {
    $equipmentList = $context['equipment'];
}

// ── Predict camera settings ───────────────────────────────────────────────
$cam = predictCameraSettings($context);

// ── Assemble full system prompt ───────────────────────────────────────────
$systemPrompt = "You are an elite photography director and cinematographer with 20+ years of experience 
across fashion, portrait, wedding, street, and commercial photography.

══════════════════════════════════════════
SHOOT SESSION — COMPLETE BRIEF
══════════════════════════════════════════

BASIC INFO (from planner form):
- Location:       " . ($context['location']    ?? '—') . "
- Date & Time:    " . ($context['datetime']    ?? '—') . "
- Shoot type:     " . ($context['shoot_type']  ?? '—') . "
- Mood / style:   " . ($context['mood']        ?? '—') . "
- Outfit colour:  " . ($context['outfit']      ?? '—') . "
- Environment:    " . ($context['environment'] ?? '—') . "
- Backdrop:       " . ($context['backdrop']    ?? '—') . "

PHOTOGRAPHER PREFERENCES (from advanced form):
- Camera type:       " . ($context['camera_type']    ?? '—') . "
- Experience level:  " . ($context['experience']     ?? '—') . "
- Lighting style:    " . ($context['lighting_style'] ?? '—') . "
- Output style:      " . ($context['output_style']   ?? '—') . "
- Shot orientation:  " . ($context['orientation']    ?? '—') . "
- Target platform:   " . ($context['platform']       ?? '—') . "
- Available equipment: " . ($equipmentList            ?: '—') . "
- Additional notes:  " . ($context['ai_notes']       ?? '—') . "

PREDICTED CAMERA SETTINGS (computed from sun position, shoot type, mood, camera & experience):
- Aperture:      " . $cam['aperture']      . "
- Shutter speed: " . $cam['shutter']       . "
- ISO:           " . $cam['iso']           . "
- Focal length:  " . $cam['focal_length']  . "
- White balance: " . $cam['white_balance'] . "
- Light quality: " . $cam['light_quality'] . " — " . $cam['light_note'] . "
- Experience tip: " . $cam['experience_tip']   . "
" . ($cam['platform_note']    ? "- Platform note: "    . $cam['platform_note']    . "\n" : '')
  . ($cam['orientation_note'] ? "- Framing note: "     . $cam['orientation_note'] . "\n" : '') . "
SUN DATA AT SHOOT TIME:
- Sun altitude:  " . ($context['sun_altitude_deg'] ?? 'unknown') . "°
- Sun azimuth:   " . ($context['sun_azimuth_deg']  ?? 'unknown') . "°
- Shadow length: " . ($context['shadow_length']    ?? 'unknown') . "
- Golden hour:   " . ($context['golden_hour_start'] ?? '—') . " → " . ($context['golden_hour_end'] ?? '—') . ($context['is_golden_hour'] ? ' ← ACTIVE NOW' : '') . "
- Blue hour:     " . ($context['blue_hour_start']   ?? '—') . " → " . ($context['blue_hour_end']   ?? '—') . ($context['is_blue_hour']   ? ' ← ACTIVE NOW' : '') . "
- Shoot during golden hour: " . (!empty($context['is_golden_hour']) ? 'YES — exploit warm directional light' : 'No') . "
- Shoot during blue hour:   " . (!empty($context['is_blue_hour'])   ? 'YES — cool diffused ethereal light'   : 'No') . "
{$bodyAnalysisSection}

══════════════════════════════════════════
YOUR ROLE & EXPERTISE
══════════════════════════════════════════
You know everything about:
- Camera settings for every lighting condition — explain why a setting is chosen, not just what it is
- Posing: tailored to this model's body type, face shape, and the shoot style above
- Lighting setups: Rembrandt, butterfly, split, loop, broad, short, clamshell — and which suits this session
- Color theory: how the outfit colour interacts with backdrop and mood
- Composition: rule of thirds, leading lines, negative space, framing, perspective
- Golden & blue hour: how to exploit or compensate for the current sun angle
- Flash, reflectors, diffusers, ND filters, and modifier techniques
- Lens selection: prime vs zoom, compression, bokeh characteristics
- Post-processing: Lightroom curves, HSL, skin retouching, mood-grade presets
- Platform-specific output: aspect ratios, resolution, storytelling formats

RESPONSE STYLE:
- Practical and on-set direct — confident, clear, encouraging
- Use specific values (f/2.8 not 'wide aperture'; 'chin forward 5°' not 'tilt a little')
- Reference the predicted camera settings above when relevant — explain *why* they suit this session
- **Bold** key terms for scannability
- Under 200 words unless a full breakdown is explicitly requested
- Always tie advice back to THIS specific shoot context (location, mood, model analysis, platform)";

// ── Convert chat history to Gemini format ─────────────────────────────────
$geminiContents = [];

$geminiContents[] = [
    'role'  => 'user',
    'parts' => [['text' => "SYSTEM INSTRUCTIONS:\n" . $systemPrompt . "\n\nAcknowledge you understand your role and briefly summarise the shoot brief."]]
];
$geminiContents[] = [
    'role'  => 'model',
    'parts' => [['text' => "Understood. I have the full brief for this "
        . ($context['shoot_type'] ?? 'photography') . " shoot at "
        . ($context['location']   ?? 'your location') . " with a "
        . ($context['mood']       ?? 'chosen') . " mood. "
        . "Predicted settings are " . $cam['aperture'] . " · " . $cam['shutter'] . " · ISO " . $cam['iso'] . " · " . $cam['focal_length'] . " · " . $cam['white_balance'] . ". "
        . "Ready for your questions."]]
];

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
        'temperature'     => 0.7,
        'topP'            => 0.9,
        'topK'            => 40,
        'maxOutputTokens' => 600,
        'stopSequences'   => []
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
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
    echo json_encode(['error' => $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(502);
    $decoded = json_decode($response, true);
    echo json_encode(['error' => $decoded['error']['message'] ?? "HTTP $httpCode"]);
    exit;
}

$decoded = json_decode($response, true);
$reply   = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$reply) {
    $finishReason = $decoded['candidates'][0]['finishReason'] ?? 'UNKNOWN';
    // Temporary debug — remove after fixing
    echo json_encode([
        'error' => "No response generated. Reason: $finishReason",
        'debug_raw' => $decoded
    ]);
    exit;
}

echo json_encode(['reply' => trim($reply)]);