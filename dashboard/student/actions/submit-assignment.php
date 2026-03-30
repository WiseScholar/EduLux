<?php
// Prevent any PHP errors from echoing as HTML to keep JSON valid
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 2. Validation
    if (!isset($_POST['assessment_id']) || empty($_FILES['submission_files'])) {
        throw new Exception("Missing assessment data or files.");
    }

    $assessment_id = (int)$_POST['assessment_id'];
    $files = $_FILES['submission_files']; // This is now an array
    $file_count = count($files['name']);

    // 3. Authorization & Deadline Check
    $stmt = $pdo->prepare("
        SELECT a.id, a.due_date, a.max_attempts
        FROM assessments a
        JOIN enrollments e ON a.course_id = e.course_id
        WHERE a.id = ? AND e.user_id = ?
    ");
    $stmt->execute([$assessment_id, $user_id]);
    $assessment_info = $stmt->fetch();

    if (!$assessment_info) {
        throw new Exception("Unauthorized submission attempt.");
    }

    // 4. File Handling Preparation
    $upload_dir = ROOT_PATH . 'assets/uploads/submissions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_exts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'jpg', 'png', 'jpeg', 'webp'];
    $max_size = 15 * 1024 * 1024; // 15MB total limit

    $pdo->beginTransaction();

    // 5. Create the Master Submission Record
    // If a student submits again, we treat it as a new attempt or update based on your preference.
    // Here, we create a fresh entry.
    $insert_sub = $pdo->prepare("
        INSERT INTO assessment_submissions (assessment_id, user_id, status, submitted_at) 
        VALUES (?, ?, 'pending', NOW())
    ");
    $insert_sub->execute([$assessment_id, $user_id]);
    $submission_id = $pdo->lastInsertId();

    // 6. Process the File Array
    for ($i = 0; $i < $file_count; $i++) {
        $original_name = $files['name'][$i];
        $tmp_name = $files['tmp_name'][$i];
        $size = $files['size'][$i];
        $error = $files['error'][$i];

        if ($error !== UPLOAD_ERR_OK) continue;

        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        // Validate individual file
        if (!in_array($file_ext, $allowed_exts)) {
            throw new Exception("File type (.$file_ext) is restricted.");
        }
        if ($size > $max_size) {
            throw new Exception("One of your files exceeds the 15MB limit.");
        }

        // Generate unique name
        $new_filename = 'sub_' . $submission_id . '_' . $user_id . '_' . $i . '_' . time() . '.' . $file_ext;
        $dest_path = $upload_dir . $new_filename;
        $db_path = 'assets/uploads/submissions/' . $new_filename;

        if (move_uploaded_file($tmp_name, $dest_path)) {
            // Store file reference in the attachments table
            $ins_attachment = $pdo->prepare("
                INSERT INTO submission_attachments (submission_id, file_path, file_name) 
                VALUES (?, ?, ?)
            ");
            $ins_attachment->execute([$submission_id, $db_path, $original_name]);
        } else {
            throw new Exception("Storage failure for: $original_name");
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Your submission has been transmitted successfully.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}