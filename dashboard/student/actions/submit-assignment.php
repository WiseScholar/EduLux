<?php
// Prevent any PHP errors from echoing as HTML to keep JSON valid
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 1. Basic Validation
    if (!isset($_POST['assessment_id']) || empty($_FILES['submission_file'])) {
        throw new Exception("Missing assessment ID or submission file.");
    }

    $assessment_id = (int)$_POST['assessment_id'];
    $file = $_FILES['submission_file'];

    // 2. Enrollment Check
    // REMOVED: e.status = 'active' to ensure students can submit even if course is marked completed
    $stmt = $pdo->prepare("
        SELECT a.due_date, a.max_attempts
        FROM assessments a
        JOIN enrollments e ON a.course_id = e.course_id
        WHERE a.id = ? AND e.user_id = ?
    ");
    $stmt->execute([$assessment_id, $user_id]);
    $assessment_info = $stmt->fetch();

    if (!$assessment_info) {
        throw new Exception("You are not authorized to submit for this assessment.");
    }

    // 3. Optional: Prevent multiple submissions if one is already pending/graded
    // If your system allows re-submissions, you can comment this block out
    $check_sub = $pdo->prepare("SELECT id FROM assessment_submissions WHERE assessment_id = ? AND user_id = ?");
    $check_sub->execute([$assessment_id, $user_id]);
    if ($check_sub->fetch()) {
        // You can decide if you want to allow them to overwrite or block them
        // throw new Exception("You have already submitted this assignment.");
    }

    // 4. File Handling
    $upload_dir = ROOT_PATH . 'uploads/submissions/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Server error: Could not create upload directory.");
        }
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'jpg', 'png', 'jpeg', 'webp'];

    if (!in_array($file_ext, $allowed_exts)) {
        throw new Exception("File type (.$file_ext) not allowed. Use PDF, Word, Zip, or Images.");
    }

    // Security: Check file size (e.g., limit to 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception("File is too large. Maximum size is 10MB.");
    }

    // Rename file: sub_ID_USER_TIMESTAMP.ext
    $new_filename = 'sub_' . $assessment_id . '_' . $user_id . '_' . time() . '.' . $file_ext;
    $dest_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
        // 5. Database Entry
        $db_path = 'uploads/submissions/' . $new_filename;
        
        $insert = $pdo->prepare("
            INSERT INTO assessment_submissions 
            (assessment_id, user_id, file_path, status, submitted_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        
        $insert->execute([$assessment_id, $user_id, $db_path]);

        echo json_encode([
            'success' => true, 
            'message' => 'Assignment submitted successfully!',
            'filename' => $new_filename
        ]);
    } else {
        throw new Exception("Failed to save the file. Check folder permissions.");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}