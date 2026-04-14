<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Instructor Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// 2. Parse JSON Input
$data = json_decode(file_get_contents('php://input'), true);
$assessment_id = isset($data['id']) ? (int)$data['id'] : 0;
$instructor_id = $_SESSION['user_id'];

if ($assessment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Assessment ID.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 3. Verify Ownership (Security)
    $chk = $pdo->prepare("
        SELECT a.id FROM assessments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.id = ? AND c.instructor_id = ?
    ");
    $chk->execute([$assessment_id, $instructor_id]);
    
    if (!$chk->fetch()) {
        throw new Exception("You do not have permission to delete this assessment.");
    }

    // 4. Cleanup Submissions & Their Attachments
    // Delete files linked to student work
    $pdo->prepare("
        DELETE FROM submission_attachments 
        WHERE submission_id IN (SELECT id FROM assessment_submissions WHERE assessment_id = ?)
    ")->execute([$assessment_id]);

    // Delete the submission records
    $pdo->prepare("DELETE FROM assessment_submissions WHERE assessment_id = ?")->execute([$assessment_id]);

    // 5. Cleanup Instructor Resources
    // Delete the instructor's uploaded prompt files/reference materials
    $pdo->prepare("DELETE FROM assessment_resources WHERE assessment_id = ?")->execute([$assessment_id]);

    // 6. Finally, Delete the Assessment itself
    $pdo->prepare("DELETE FROM assessments WHERE id = ?")->execute([$assessment_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}