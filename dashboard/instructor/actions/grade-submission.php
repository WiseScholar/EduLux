<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Enhanced Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access denied.']);
    exit;
}

try {
    $submission_id = (int)($_POST['submission_id'] ?? 0);
    // Use float to keep percentage precision (e.g., 88.5%)
    $score = (float)($_POST['score'] ?? 0); 
    $feedback = trim($_POST['feedback'] ?? '');

    if (!$submission_id) {
        throw new Exception("Invalid submission identity.");
    }

    // 2. Atomic Update
    // We update the status to 'graded' and record the timestamp.
    $stmt = $pdo->prepare("
        UPDATE assessment_submissions 
        SET score = ?, 
            feedback = ?, 
            status = 'graded',
            submitted_at = IFNULL(submitted_at, NOW()) 
        WHERE id = ?
    ");
    
    // Note: If you have a 'graded_at' column in your DB, add it here.
    // Otherwise, this ensures the record is fully finalized.
    $success = $stmt->execute([$score, $feedback, $submission_id]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Audit finalized.']);
    } else {
        throw new Exception("Database rejection. Please try again.");
    }

} catch (Exception $e) {
    error_log("Grading Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}