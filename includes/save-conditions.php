<?php
session_start();
require 'dbh.inc.php';

$input = json_decode(file_get_contents('php://input'), true);
$id    = intval($input['result_id'] ?? 0);
if (!$id) { http_response_code(400); exit; }

$stmt = $conn->prepare("
    UPDATE shoot_results SET
        sun_altitude       = ?,
        sun_azimuth        = ?,
        shadow_direction   = ?,
        shadow_length      = ?,
        golden_hour_start  = ?,
        golden_hour_end    = ?,
        blue_hour_start    = ?,
        blue_hour_end      = ?,
        is_golden_hour     = ?,
        is_blue_hour       = ?,
        weather_temp       = ?,
        weather_condition  = ?,
        weather_humidity   = ?,
        weather_wind       = ?,
        weather_clouds     = ?,
        weather_rain_chance= ?,
        weather_score      = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ssssssssiisdsddddi",
    $input['sun_altitude'],
    $input['sun_azimuth'],
    $input['shadow_direction'],
    $input['shadow_length'],
    $input['golden_hour_start'],
    $input['golden_hour_end'],
    $input['blue_hour_start'],
    $input['blue_hour_end'],
    $input['is_golden_hour'],
    $input['is_blue_hour'],
    $input['weather_temp'],
    $input['weather_condition'],
    $input['weather_humidity'],
    $input['weather_wind'],
    $input['weather_clouds'],
    $input['weather_rain_chance'],
    $input['weather_score'],
    $id
);

$stmt->execute();
http_response_code(200);