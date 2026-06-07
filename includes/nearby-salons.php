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

require 'dbh.inc.php';
session_start();

$data      = json_decode(file_get_contents('php://input'), true);
$lat       = $data['lat']       ?? null;
$lng       = $data['lng']       ?? null;
$result_id = $data['result_id'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['error' => 'Missing coordinates']); exit;
}

// ── 1. Check DB cache first ───────────────────────────────────────────────
if ($result_id) {
    $stmt = $conn->prepare("SELECT nearby_salons FROM shoot_results WHERE id = ? AND user_id = ?");
    $uid  = $_SESSION['userid'] ?? 0;
    $stmt->bind_param("ii", $result_id, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && !empty($row['nearby_salons'])) {
        $cached = json_decode($row['nearby_salons'], true);
        if (!empty($cached)) {
            echo json_encode(['salons' => $cached, 'source' => 'cache']);
            exit;
        }
    }
}

// ── 2. No cache — hit Google Places API ──────────────────────────────────
$apiKey = $_ENV['GOOGLE_PLACES_KEY'] ?? '';
if (!$apiKey) {
    echo json_encode(['error' => 'Missing Google Places API key']); exit;
}

$payload = json_encode([
    'includedTypes'       => ['beauty_salon', 'hair_care'],
    'maxResultCount'      => 5,
    'locationRestriction' => [
        'circle' => [
            'center' => ['latitude' => (float)$lat, 'longitude' => (float)$lng],
            'radius' => 3000.0,
        ]
    ]
]);

$ch = curl_init('https://places.googleapis.com/v1/places:searchNearby');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-Goog-Api-Key: ' . $apiKey,
        'X-Goog-FieldMask: places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.rating,places.currentOpeningHours,places.location,places.id',
    ],
]);

$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($resp, true);
error_log('[Salons] HTTP: ' . $httpCode . ' | Response: ' . $resp);

if (empty($result['places'])) {
    // Still save empty array so we don't hammer the API on every reload
    if ($result_id) {
        $empty = json_encode([]);
        $uid   = $_SESSION['userid'] ?? 0;
        $s = $conn->prepare("UPDATE shoot_results SET nearby_salons = ? WHERE id = ? AND user_id = ?");
        $s->bind_param("sii", $empty, $result_id, $uid);
        $s->execute();
    }
    echo json_encode(['salons' => [], 'debug' => $result]);
    exit;
}

// ── 3. Build salon list ───────────────────────────────────────────────────
$salons = [];

foreach ($result['places'] as $place) {
    $placeLat = $place['location']['latitude']  ?? 0;
    $placeLng = $place['location']['longitude'] ?? 0;
    $earthR   = 6371000;
    $dLat     = deg2rad($placeLat - $lat);
    $dLng     = deg2rad($placeLng - $lng);
    $a        = sin($dLat/2) * sin($dLat/2) +
                cos(deg2rad($lat)) * cos(deg2rad($placeLat)) *
                sin($dLng/2) * sin($dLng/2);
    $distance = round($earthR * 2 * atan2(sqrt($a), sqrt(1 - $a)));

    $isOpen  = $place['currentOpeningHours']['openNow'] ?? null;
    $placeId = $place['id'] ?? '';

    $salons[] = [
        'name'     => $place['displayName']['text'] ?? 'Unknown',
        'phone'    => $place['nationalPhoneNumber'] ?? null,
        'address'  => $place['formattedAddress']    ?? '',
        'rating'   => $place['rating']              ?? null,
        'open'     => $isOpen,
        'distance' => $distance,
        'maps_url' => 'https://www.google.com/maps/place/?q=place_id:' . $placeId,
    ];
}

usort($salons, fn($a, $b) => $a['distance'] - $b['distance']);

// ── 4. Save to DB ─────────────────────────────────────────────────────────
if ($result_id) {
    $uid        = $_SESSION['userid'] ?? 0;
    $salons_json = json_encode($salons);
    $s = $conn->prepare("UPDATE shoot_results SET nearby_salons = ? WHERE id = ? AND user_id = ?");
    $s->bind_param("sii", $salons_json, $result_id, $uid);
    $s->execute();
}

echo json_encode(['salons' => $salons, 'source' => 'api']);