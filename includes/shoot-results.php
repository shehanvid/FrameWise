<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

require 'dbh.inc.php';


$location     = trim($_POST['location']     ?? '');
$location_lat = trim($_POST['location_lat'] ?? '');
$location_lng = trim($_POST['location_lng'] ?? '');
$datetime     = trim($_POST['datetime']     ?? '');
$shoot_type   = trim($_POST['shoot_type']   ?? '');
$mood         = trim($_POST['mood']         ?? '');
$outfit       = trim($_POST['outfit']       ?? '');
$gear         = trim($_POST['gear']         ?? '');
$environment  = trim($_POST['environment']  ?? 'outdoor');
$backdrop     = trim($_POST['backdrop']     ?? '');
$gender       = trim($_POST['gender']       ?? 'female');

$errors = [];
if ($location   === '') $errors[] = 'Location is required.';
if ($datetime   === '') $errors[] = 'Date & time is required.';
if ($shoot_type === '') $errors[] = 'Shoot type is required.';
if ($mood       === '') $errors[] = 'Mood is required.';

if (!empty($errors)) {
    header("Location: index.php");
    exit();
}

$equipment_val = isset($_POST['equipment']) ? json_encode($_POST['equipment']) : null;

$uid          = $_SESSION['userid'] ?? null;
$lat          = $location_lat ? (float)$location_lat : null;
$lng          = $location_lng ? (float)$location_lng : null;
$shoot_dt     = (new DateTime($datetime))->format('Y-m-d H:i:s');
$body_json    = $_POST['body_analysis'] ?? null;
$camera       = $_POST['camera_type']   ?? null;
$experience   = $_POST['experience']    ?? null;
$lighting     = $_POST['lighting_style']?? null;
$out_style    = $_POST['output_style']  ?? null;
$orientation  = $_POST['orientation']   ?? null;
$platform     = $_POST['platform']      ?? null;
$ai_notes     = trim($_POST['ai_notes'] ?? '');

$stmt = $conn->prepare("
    INSERT INTO shoot_results
        (user_id, location, location_lat, location_lng, shoot_datetime,
         shoot_type, mood, outfit_colour, gender, environment, backdrop, gear,
         body_analysis, camera_type, experience, lighting_style, output_style,
         equipment, orientation, platform, ai_notes)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issssssssssssssssssss",
    $uid, $location, $lat, $lng, $shoot_dt,
    $shoot_type, $mood, $outfit, $gender, $environment, $backdrop, $gear,
    $body_json, $camera, $experience, $lighting, $out_style,
    $equipment_val, $orientation, $platform, $ai_notes
);

$stmt->execute();
$last_id = $conn->insert_id;

header("Location: view-result.php?id=" . $last_id);
exit();