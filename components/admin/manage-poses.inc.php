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

$action = $_POST['action'] ?? '';

// ── Helper: generate slug-style pose_id from name ─────────────────────────
function makePoseId(string $name, mysqli $conn): string {
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
    $base = trim($base, '-');

    // Ensure uniqueness
    $candidate = $base;
    $i = 2;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM poses WHERE pose_id = ? LIMIT 1");
        $stmt->bind_param("s", $candidate);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) break;
        $candidate = $base . '-' . $i++;
    }
    return $candidate;
}

// ════════════════════════════════════════════════════════════════════════════
// ADD POSE
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'add_pose') {

    $name            = trim($_POST['name']                ?? '');
    $description     = trim($_POST['description']         ?? '');
    $category        = trim($_POST['category']            ?? '');
    $gender          = trim($_POST['gender']              ?? '');
    $body_position   = trim($_POST['body_position']       ?? '');
    $difficulty      = trim($_POST['difficulty']          ?? 'easy');
    $mood            = trim($_POST['mood']                ?? '');
    $suitable_for    = trim($_POST['suitable_for']        ?? '');
    $best_body       = trim($_POST['best_for_body_types'] ?? '');
    $best_face       = trim($_POST['best_for_face_shapes']?? '');
    $tags            = trim($_POST['tags']                ?? '');

    // Validate required fields
    if ($name === '') {
        echo json_encode(['success' => false, 'error' => 'Name is required']);
        exit();
    }
    if ($category === '') {
        echo json_encode(['success' => false, 'error' => 'Category is required']);
        exit();
    }
    if ($gender === '') {
        echo json_encode(['success' => false, 'error' => 'Gender is required']);
        exit();
    }

    $allowed_difficulties = ['easy', 'medium', 'hard'];
    if (!in_array($difficulty, $allowed_difficulties, true)) {
        $difficulty = 'easy';
    }

    // ── Image upload ───────────────────────────────────────────────────────
    $image_file = '';
    if (!empty($_FILES['image']['name'])) {
        $file    = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];

        // Validate MIME type
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid image type. Use JPG, PNG, or WEBP.']);
            exit();
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5 MB limit
            echo json_encode(['success' => false, 'error' => 'Image too large. Max 5 MB.']);
            exit();
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/FrameWise/assets/poses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Preserve original filename, sanitise it
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $ext          = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeBase     = preg_replace('/[^a-z0-9\-_\s]/i', '', $originalName);
        $safeBase     = trim($safeBase);

        // Avoid collisions
        $filename  = $safeBase . '.' . $ext;
        $destPath  = $uploadDir . $filename;
        $counter   = 1;
        while (file_exists($destPath)) {
            $filename = $safeBase . '_' . $counter++ . '.' . $ext;
            $destPath = $uploadDir . $filename;
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['success' => false, 'error' => 'Failed to save image. Check folder permissions.']);
            exit();
        }

        $image_file = $filename;
    }

    // ── Generate pose_id ───────────────────────────────────────────────────
    $pose_id = makePoseId($name, $conn);

    // ── Insert into DB ─────────────────────────────────────────────────────
    $stmt = $conn->prepare("
        INSERT INTO poses
            (pose_id, gender, name, description, category, body_position,
             mood, suitable_for, best_for_body_types, best_for_face_shapes,
             difficulty, tags, image_file, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
    ");

    $stmt->bind_param(
        "sssssssssssss",
        $pose_id, $gender, $name, $description, $category, $body_position,
        $mood, $suitable_for, $best_body, $best_face,
        $difficulty, $tags, $image_file
    );

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo json_encode([
            'success'   => true,
            'pose'      => [
                'id'                  => $new_id,
                'pose_id'             => $pose_id,
                'name'                => $name,
                'description'         => $description,
                'category'            => $category,
                'gender'              => $gender,
                'body_position'       => $body_position,
                'difficulty'          => $difficulty,
                'mood'                => $mood,
                'suitable_for'        => $suitable_for,
                'best_for_body_types' => $best_body,
                'best_for_face_shapes'=> $best_face,
                'tags'                => $tags,
                'image_file'          => $image_file,
            ]
        ]);
    } else {
        // Clean up uploaded file if DB insert failed
        if ($image_file && file_exists($uploadDir . $image_file)) {
            unlink($uploadDir . $image_file);
        }
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    }
    exit();
}

// ════════════════════════════════════════════════════════════════════════════
// DELETE POSE
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'delete_pose') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit();
    }

    // Fetch image filename first so we can delete the file
    $check = $conn->prepare("SELECT image_file FROM poses WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Pose not found']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM poses WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Remove image file from disk
        if (!empty($row['image_file'])) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/FrameWise/assets/poses/' . $row['image_file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);