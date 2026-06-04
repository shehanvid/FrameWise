<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$gender   = $_GET['gender'] ?? 'female';
$gender   = in_array($gender, ['male', 'female']) ? $gender : 'female';
$posesDir = __DIR__ . '/../assets/poses/' . $gender . '/';

if (!is_dir($posesDir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Poses directory not found for gender: ' . $gender, 'poses' => []]);
    exit;
}

$extensions = ['jpg', 'jpeg', 'png', 'webp'];
$poses      = [];
$files      = scandir($posesDir);
sort($files);

foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $extensions)) continue;

    $id    = pathinfo($file, PATHINFO_FILENAME);
    $label = ucwords(str_replace('-', ' ', $id));

    $poses[] = [
        'id'    => $id,
        'label' => $label,
        'image' => 'assets/poses/' . $gender . '/' . $file,
        'file'  => $file,
    ];
}

echo json_encode(['poses' => $poses]);