<?php
require_once __DIR__ . '/../../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$course_id = (int)$_POST['course_id'];
$instructor_id = $_SESSION['user_id'];

// 1. Security Check
$check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
$check->execute([$course_id, $instructor_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_FILES['resource_file'])) {
    $file = $_FILES['resource_file'];
    $allowed_exts = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'zip'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'message' => 'File type not allowed']);
        exit;
    }

    // Create unique filename
    $new_name = "res_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $upload_dir = ROOT_PATH . "assets/uploads/resources/";
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        // Save to Database
        $stmt = $pdo->prepare("INSERT INTO course_resources (course_id, title, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
        
        $size_mb = round($file['size'] / 1048576, 2) . ' MB';
        $stmt->execute([
            $course_id,
            $file['name'], // Original name as Title
            $new_name,
            $ext,
            $size_mb
        ]);

        echo json_encode([
            'success' => true, 
            'file_name' => $file['name'],
            'file_size' => $size_mb,
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
}