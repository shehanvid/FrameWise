<?php
if (!isset($_SESSION['isAdmin'])) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

ob_start();
include $_SERVER['DOCUMENT_ROOT'] . '/FrameWise/includes/dbh.inc.php';
ob_end_clean();

$action   = $_POST['action']   ?? '';
$category = trim($_POST['category'] ?? '');
$label    = trim($_POST['label']    ?? '');
$emoji    = trim($_POST['emoji']    ?? '');
$id       = (int)($_POST['id']      ?? 0);

function getTableForCategory(string $cat): string {
    if ($cat === 'mood')       return 'moods';
    if ($cat === 'equipment')  return 'equipment_options';
    if ($cat === 'shoot_type') return 'shoot_types';
    return 'lookup_options';
}

function makeValue(string $label): string {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($label)));
}

$table = getTableForCategory($category);

if ($action === 'delete_user') {
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit();
    }

    if ($id === (int)($_SESSION['usersId'] ?? $_SESSION['userid'] ?? 0)) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete your own account']);
        exit();
    }


    $check = $conn->prepare("SELECT isAdmin FROM users WHERE usersId = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $target = $check->get_result()->fetch_assoc();

    if (!$target) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    if ($target['isAdmin']) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete admin accounts']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE usersId = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed or user not found']);
    }
    exit();
}

$allowed_categories = ['shoot_type','mood','camera_type','experience','lighting_style','output_style','platform','equipment','orientation'];
if (!in_array($category, $allowed_categories, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid category']);
    exit();
}

if ($action === 'add') {
    if ($label === '') {
        echo json_encode(['success' => false, 'error' => 'Label is required']);
        exit();
    }

    $value = makeValue($label);

    if ($table === 'lookup_options') {
        $r = $conn->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM lookup_options WHERE category = ?");
        $r->bind_param("s", $category);
        $r->execute();
        $sort = $r->get_result()->fetch_row()[0];
        $stmt = $conn->prepare("INSERT INTO lookup_options (category, value, label, sort_order) VALUES (?,?,?,?)");
        $stmt->bind_param("sssi", $category, $value, $label, $sort);
    } elseif ($table === 'moods') {
        $colors = [
            '#c084fc','#818cf8','#60a5fa','#34d399','#f472b6',
            '#fb923c','#facc15','#a78bfa','#38bdf8','#4ade80',
            '#f87171','#e879f9','#2dd4bf','#fb7185','#a3e635',
        ];
        $color = $colors[array_rand($colors)];


        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $bg_gradient = "linear-gradient(135deg, rgba($r,$g,$b,0.15) 0%, rgba($r,$g,$b,0.05) 100%)";

        $stmt = $conn->prepare("INSERT INTO moods (value, label, emoji, color, bg_gradient) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $value, $label, $emoji, $color, $bg_gradient);
    } elseif ($table === 'shoot_types') {
        $stmt = $conn->prepare("INSERT INTO shoot_types (value, label) VALUES (?,?)");
        $stmt->bind_param("ss", $value, $label);
    } else {
        $stmt = $conn->prepare("INSERT INTO equipment_options (value, label) VALUES (?,?)");
        $stmt->bind_param("ss", $value, $label);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id, 'value' => $value]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Insert failed: ' . $conn->error]);
    }
    exit();
}

if ($action === 'delete') {
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit();
    }

    if ($table === 'lookup_options') {
        $stmt = $conn->prepare("DELETE FROM lookup_options WHERE id = ? AND category = ?");
        $stmt->bind_param("is", $id, $category);
    } else {
        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
    }

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed or row not found']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);