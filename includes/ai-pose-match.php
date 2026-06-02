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

$prompt = "You are a professional photography pose director with deep knowledge of body types, face shapes, and shoot styles.

AVAILABLE POSES (these are the ONLY poses you can choose from):
1. classic-three-quarter     | Body angled 45° to camera, weight on back foot | suits: portrait, fashion, wedding | moods: warm, natural, airy | good for: most body types, oval/diamond faces
2. over-shoulder-look        | Body away from camera, head turns back          | suits: fashion, portrait          | moods: dramatic, moody, cool | good for: all body types, oval/heart/square faces
3. power-stance              | Feet apart, hands on hips, chin forward         | suits: fashion, street, portrait  | moods: dramatic, cool | good for: athletic, inverted_triangle, rectangle body
4. s-curve                   | Hip pop to one side, slight torso twist         | suits: portrait, fashion, wedding | moods: warm, natural, airy | good for: hourglass, pear body | great for petite/average height
5. seated-editorial          | Seated on floor/chair, legs to side, arm prop  | suits: portrait, fashion          | moods: moody, airy, natural | good for: all body types
6. walking-shot              | Mid-stride, natural movement, slight arm swing  | suits: street, fashion            | moods: natural, cool | good for: long legs, athletic body
7. crossed-arms              | Arms crossed at chest, slight body angle        | suits: portrait, street           | moods: dramatic, moody, cool | good for: broad shoulders, rectangle body
8. lean-on-wall              | Back or shoulder against wall, one foot up      | suits: street, portrait           | moods: cool, moody, natural | good for: all body types, tall/average height
9. hands-in-hair             | One or both hands in hair, chin slightly up     | suits: portrait, fashion, wedding | moods: warm, airy, natural | good for: long neck, oval/heart face
10. ground-pose              | Seated/lying on ground, legs extended           | suits: fashion, portrait          | moods: dramatic, moody | good for: long legs, hourglass, pear body
11. profile-silhouette       | Pure side profile, strong jawline visible       | suits: portrait, fashion          | moods: dramatic, moody | good for: sharp jawline, long neck, oval face
12. candid-laugh             | Natural laugh/smile, body relaxed, slight turn  | suits: wedding, portrait          | moods: warm, natural | good for: round/heart face, all body types
13. arms-raised              | One or both arms raised above head              | suits: fashion, beach             | moods: airy, warm, natural | good for: long torso, narrow shoulders
14. back-to-camera           | Full back to camera, looks into distance        | suits: street, landscape, fashion | moods: moody, dramatic, cool | good for: all body types, shows outfit back
15. chin-tilt-close          | Close-up, chin angled down toward camera        | suits: portrait, wedding          | moods: warm, natural, airy | good for: round/square face, short neck
16. dynamic-jump             | Jumping, energy, movement blur optional         | suits: fashion, street            | moods: cool, natural | good for: athletic, petite, long legs
17. seated-knee-up           | One knee raised, arm resting on it              | suits: portrait, street           | moods: natural, cool, moody | good for: rectangle, athletic body
18. lying-overhead           | Model lies flat, camera shoots from above       | suits: fashion, portrait          | moods: dramatic, airy | good for: hourglass, pear body | shows outfit flat
19. mirror-reflection        | Shot through mirror or reflective surface       | suits: fashion, portrait          | moods: moody, dramatic, cool | good for: any body type, creative shoot
20. window-light-profile     | Standing near window, soft side lighting        | suits: portrait, wedding          | moods: airy, natural, warm | good for: all face shapes, defined jawline

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

$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature'     => 0.2,
        'maxOutputTokens' => 80,
    ]
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $_ENV['GEMINI_API_KEY'];
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

// Pose metadata map — same data as the prompt, structured for display
$poseData = [
    'classic-three-quarter' => ['name'=>'Classic Three-Quarter',  'description'=>'Body angled 45° to camera, weight on back foot, relaxed arms.',    'tag'=>'Universally flattering'],
    'over-shoulder-look'    => ['name'=>'Over-Shoulder Look',      'description'=>'Body faces away, head turns back toward camera, slight chin tuck.', 'tag'=>'Editorial'],
    'power-stance'          => ['name'=>'Power Stance',            'description'=>'Feet shoulder-width apart, hands on hips, chin forward.',           'tag'=>'Bold & confident'],
    's-curve'               => ['name'=>'S-Curve',                 'description'=>'Hip pop to one side, slight torso twist, weight on one leg.',       'tag'=>'Feminine & fluid'],
    'seated-editorial'      => ['name'=>'Seated Editorial',        'description'=>'Seated on floor or chair, legs to side, arm used as prop.',         'tag'=>'Relaxed editorial'],
    'walking-shot'          => ['name'=>'Walking Shot',            'description'=>'Mid-stride, natural movement, slight arm swing toward camera.',     'tag'=>'Dynamic & natural'],
    'crossed-arms'          => ['name'=>'Crossed Arms',            'description'=>'Arms crossed at chest, slight body angle, strong gaze.',            'tag'=>'Powerful & edgy'],
    'lean-on-wall'          => ['name'=>'Lean on Wall',            'description'=>'Back or shoulder against wall, one foot slightly raised.',          'tag'=>'Effortlessly cool'],
    'hands-in-hair'         => ['name'=>'Hands in Hair',           'description'=>'One or both hands in hair, chin slightly lifted, relaxed.',         'tag'=>'Playful & carefree'],
    'ground-pose'           => ['name'=>'Ground Pose',             'description'=>'Seated or lying on ground, legs extended, confident eye contact.',  'tag'=>'Fashion editorial'],
    'profile-silhouette'    => ['name'=>'Profile Silhouette',      'description'=>'Pure side profile, strong jawline visible, gaze forward.',          'tag'=>'Dramatic & artistic'],
    'candid-laugh'          => ['name'=>'Candid Laugh',            'description'=>'Natural laugh, body relaxed and slightly turned, genuine emotion.',  'tag'=>'Warm & authentic'],
    'arms-raised'           => ['name'=>'Arms Raised',             'description'=>'One or both arms raised above head, elongates the torso.',          'tag'=>'Expressive & free'],
    'back-to-camera'        => ['name'=>'Back to Camera',          'description'=>'Full back facing camera, subject looks into the distance.',         'tag'=>'Mysterious & cinematic'],
    'chin-tilt-close'       => ['name'=>'Chin Tilt Close-Up',      'description'=>'Close portrait, chin angled down slightly toward camera.',          'tag'=>'Intimate portrait'],
    'dynamic-jump'          => ['name'=>'Dynamic Jump',            'description'=>'Jumping mid-air, energy and movement, optional motion blur.',       'tag'=>'High energy'],
    'seated-knee-up'        => ['name'=>'Seated Knee Up',          'description'=>'One knee raised, arm resting on it, relaxed but intentional.',     'tag'=>'Casual editorial'],
    'lying-overhead'        => ['name'=>'Lying Overhead',          'description'=>'Model lies flat, camera shoots straight down from above.',          'tag'=>'Avant-garde'],
    'mirror-reflection'     => ['name'=>'Mirror Reflection',       'description'=>'Shot captured through a mirror or reflective surface.',             'tag'=>'Creative & conceptual'],
    'window-light-profile'  => ['name'=>'Window Light Profile',    'description'=>'Standing near a window, soft side lighting sculpts the face.',     'tag'=>'Soft & cinematic'],
];

// Build response with only the selected poses
$selectedPoses = [];
foreach ($poseIds as $id) {
    if (isset($poseData[$id])) {
        $selectedPoses[] = array_merge(
            ['id' => $id, 'image' => "assets/poses/{$id}.jpg"],
            $poseData[$id]
        );
    }
}

// Fallback: if AI returned garbage, send first 5
if (empty($selectedPoses)) {
    $selectedPoses = array_slice(
        array_map(fn($id, $meta) => array_merge(['id'=>$id, 'image'=>"assets/poses/{$id}.jpg"], $meta),
            array_keys($poseData), array_values($poseData)),
        0, 5
    );
}

echo json_encode(['poses' => $selectedPoses]);