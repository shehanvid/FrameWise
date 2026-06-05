<?php
session_start();
require 'dbh.inc.php'; // your db connection

$input    = json_decode(file_get_contents('php://input'), true);
$id       = intval($input['result_id'] ?? 0);
$poses    = $input['poses'] ?? [];

if (!$id || empty($poses)) { http_response_code(400); exit; }


$ai_plan = json_encode($poses);

$stmt = $conn->prepare("UPDATE shoot_results SET ai_plan = ? WHERE id = ?");
$stmt->bind_param("si", $ai_plan, $id);
$stmt->execute();

http_response_code(200);