<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Resolve the poses directory relative to THIS file (which lives in includes/)
$posesDir = __DIR__ . '/../assets/poses/';

if (!is_dir($posesDir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Poses directory not found', 'poses' => []]);
    exit;
}

$extensions = ['jpg', 'jpeg', 'png', 'webp'];
$poses      = [];

$files = scandir($posesDir);
sort($files);

foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $extensions)) continue;

    $id    = pathinfo($file, PATHINFO_FILENAME); // e.g. "classic-three-quarter"
    $label = ucwords(str_replace('-', ' ', $id)); // e.g. "Classic Three Quarter"

    $poses[] = [
        'id'    => $id,
        'label' => $label,
        'image' => 'assets/poses/' . $file,
        'file'  => $file,
    ];
}

echo json_encode(['poses' => $poses]);