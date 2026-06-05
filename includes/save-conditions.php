<?php
session_start();
require 'dbh.inc.php';

$input = json_decode(file_get_contents('php://input'), true);
$id    = intval($input['result_id'] ?? 0);
if (!$id) { http_response_code(400); exit; }

// Build SET clause dynamically — only update non-null fields
$fields = [
    'sun_altitude'       => 's',
    'sun_azimuth'        => 's',
    'shadow_direction'   => 's',
    'shadow_length'      => 's',
    'golden_hour_start'  => 's',
    'golden_hour_end'    => 's',
    'blue_hour_start'    => 's',
    'blue_hour_end'      => 's',
    'is_golden_hour'     => 'i',
    'is_blue_hour'       => 'i',
    'weather_temp'       => 'd',
    'weather_condition'  => 's',
    'weather_humidity'   => 'd',
    'weather_wind'       => 'd',
    'weather_clouds'     => 'd',
    'weather_rain_chance'=> 'd',
    'weather_score'      => 'd',
];

$setClauses = [];
$types      = '';
$values     = [];

foreach ($fields as $field => $type) {
    if (array_key_exists($field, $input) && $input[$field] !== null) {
        $setClauses[] = "$field = ?";
        $types       .= $type;
        $values[]     = $input[$field];
    }
}

if (empty($setClauses)) { http_response_code(200); exit; }

$types   .= 'i';
$values[] = $id;

$sql  = "UPDATE shoot_results SET " . implode(', ', $setClauses) . " WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$values);
$stmt->execute();

http_response_code(200);