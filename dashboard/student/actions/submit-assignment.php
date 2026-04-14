<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if (!isset($_POST['assessment_id']) || empty($_FILES['submission_files'])) {
        throw new Exception("Please select at least one file to upload.");
    }

    $assessment_id = (int)$_POST['assessment_id'];
    $files = $_FILES['submission_files'];
    $file_count = count($files['name']);

    // 1. Authorization & Deadline Check Only (Removed max_attempts logic)
    $stmt = $pdo->prepare("
        SELECT a.id, a.due_date
        FROM assessments a
        JOIN enrollments e ON a.course_id = e.course_id
        WHERE a.id = ? AND e.user_id = ?
    ");
    $stmt->execute([$assessment_id, $user_id]);
    $info = $stmt->fetch();

    if (!$info) {
        throw new Exception("Unauthorized attempt.");
    }

    // 2. Storage Setup
    $upload_dir = ROOT_PATH . 'assets/uploads/submissions/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $allowed_exts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'jpg', 'png', 'jpeg', 'webp'];
    $max_size = 25 * 1024 * 1024; 

    $pdo->beginTransaction();

    // 3. Create Submission Record
    $insert_sub = $pdo->prepare("
        INSERT INTO assessment_submissions (assessment_id, user_id, status, submitted_at) 
        VALUES (?, ?, 'submitted', NOW())
    ");
    $insert_sub->execute([$assessment_id, $user_id]);
    $submission_id = $pdo->lastInsertId();

    // 4. Process Files
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $original_name = $files['name'][$i];
        $tmp_name = $files['tmp_name'][$i];
        $size = $files['size'][$i];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_exts)) {
            throw new Exception("File type (.$file_ext) is not allowed.");
        }
        if ($size > $max_size) {
            throw new Exception("File '$original_name' exceeds the size limit.");
        }

        $new_filename = 'sub_' . $submission_id . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;

        if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
            $ins_attachment = $pdo->prepare("
                INSERT INTO submission_attachments (submission_id, file_path, file_name) 
                VALUES (?, ?, ?)
            ");
            $ins_attachment->execute([$submission_id, 'assets/uploads/submissions/' . $new_filename, $original_name]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Assignment submitted successfully!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}