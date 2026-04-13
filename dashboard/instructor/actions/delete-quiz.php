<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$quiz_id = (int)($_POST['id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

if ($quiz_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Quiz ID.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Verify Ownership: Ensure this instructor actually owns the course this quiz belongs to
    $stmt = $pdo->prepare("
        SELECT a.id FROM assessments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$quiz_id, $instructor_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Permission denied or quiz not found.");
    }

    // 2. Manual Cleanup (If your DB doesn't have ON DELETE CASCADE set up)
    // Delete answers linked to submissions of this quiz
    $pdo->prepare("DELETE FROM assessment_answers WHERE submission_id IN (SELECT id FROM assessment_submissions WHERE assessment_id = ?)")->execute([$quiz_id]);
    
    // Delete submissions
    $pdo->prepare("DELETE FROM assessment_submissions WHERE assessment_id = ?")->execute([$quiz_id]);

    // Delete questions
    $pdo->prepare("DELETE FROM quiz_questions WHERE assessment_id = ?")->execute([$quiz_id]);

    // 3. Finally, delete the assessment itself
    $pdo->prepare("DELETE FROM assessments WHERE id = ?")->execute([$quiz_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // This will help you see the REAL error in the Network Tab
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}