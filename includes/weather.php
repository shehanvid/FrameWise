<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$lat = trim($_GET['lat'] ?? '');
$lng = trim($_GET['lng'] ?? '');

if ($lat === '' || $lng === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing lat/lng parameters']);
    exit;
}

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

// ── 1. Fetch weather from Open-Meteo (free, no API key required) ───────────
$weatherUrl = sprintf(
    'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s'
    . '&current=temperature_2m,relative_humidity_2m,apparent_temperature,'
    . 'precipitation_probability,precipitation,weather_code,cloud_cover,wind_speed_10m'
    . '&wind_speed_unit=kmh&temperature_unit=celsius&timezone=auto',
    urlencode($lat),
    urlencode($lng)
);

$ch = curl_init($weatherUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$weatherRaw  = curl_exec($ch);
$weatherCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr     = curl_error($ch);
curl_close($ch);

if ($curlErr || $weatherCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Weather fetch failed: ' . ($curlErr ?: "HTTP $weatherCode")]);
    exit;
}

$weather = json_decode($weatherRaw, true);
if (!isset($weather['current'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Unexpected weather response']);
    exit;
}

$current = $weather['current'];

// ── 2. Reverse-geocode location name via Nominatim (free) ─────────────────
$location = 'Your location';
$geocodeUrl = sprintf(
    'https://nominatim.openstreetmap.org/reverse?lat=%s&lon=%s&format=json&zoom=10',
    urlencode($lat),
    urlencode($lng)
);

$ch2 = curl_init($geocodeUrl);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'User-Agent: PhotoPlanAI/1.0'   // Nominatim requires a User-Agent
    ],
]);
$geoRaw = curl_exec($ch2);
curl_close($ch2);

if ($geoRaw) {
    $geo = json_decode($geoRaw, true);
    $addr = $geo['address'] ?? [];
    $parts = array_filter([
        $addr['suburb']   ?? $addr['neighbourhood'] ?? $addr['village'] ?? '',
        $addr['city']     ?? $addr['town']          ?? $addr['county']  ?? '',
        $addr['country']  ?? '',
    ]);
    if ($parts) {
        $location = implode(', ', $parts);
    }
}

// ── 3. Decode WMO weather code → description ──────────────────────────────
function decodeWMO(int $code): string {
    $map = [
        0  => 'Clear sky',
        1  => 'Mainly clear',
        2  => 'Partly cloudy',
        3  => 'Overcast',
        45 => 'Foggy',
        48 => 'Icy fog',
        51 => 'Light drizzle',
        53 => 'Moderate drizzle',
        55 => 'Dense drizzle',
        61 => 'Slight rain',
        63 => 'Moderate rain',
        65 => 'Heavy rain',
        71 => 'Slight snow',
        73 => 'Moderate snow',
        75 => 'Heavy snow',
        77 => 'Snow grains',
        80 => 'Slight showers',
        81 => 'Moderate showers',
        82 => 'Violent showers',
        85 => 'Slight snow showers',
        86 => 'Heavy snow showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm w/ hail',
        99 => 'Thunderstorm w/ heavy hail',
    ];
    return $map[$code] ?? 'Unknown conditions';
}

// ── 4. Photography suitability hint ───────────────────────────────────────
function shootSuitability(int $wmoCode, int $clouds, float $wind, int $rainChance): string {
    if ($wmoCode >= 95)        return 'Avoid shooting — storm risk';
    if ($wmoCode >= 61)        return 'Poor — rain likely';
    if ($wmoCode >= 51)        return 'Marginal — drizzle possible';
    if ($rainChance > 60)      return 'Risky — high rain probability';
    if ($clouds < 20 && $wind < 20) return 'Excellent for outdoor shoot';
    if ($clouds < 50)          return 'Good — soft natural light';
    if ($clouds < 80)          return 'Decent — diffused light';
    return 'Overcast — even, flat light';
}

// ── 5. Build response ──────────────────────────────────────────────────────
$wmoCode    = (int)($current['weather_code']             ?? 0);
$clouds     = (int)($current['cloud_cover']              ?? 0);
$wind       = (float)($current['wind_speed_10m']         ?? 0);
$rainChance = (int)($current['precipitation_probability'] ?? 0);
$humidity   = (int)($current['relative_humidity_2m']     ?? 0);
$temp       = (float)($current['temperature_2m']         ?? 0);

echo json_encode([
    'location'    => $location,
    'temp'        => $temp,
    'description' => decodeWMO($wmoCode),
    'suitability' => shootSuitability($wmoCode, $clouds, $wind, $rainChance),
    'humidity'    => $humidity,
    'wind'        => round($wind, 1),
    'clouds'      => $clouds,
    'rain_chance' => $rainChance,
]);
