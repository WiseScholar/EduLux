<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Security Check
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

    // 2. Verify Ownership: Ensure this instructor actually owns the course this quiz belongs to
    $stmt = $pdo->prepare("
        SELECT a.id FROM assessments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$quiz_id, $instructor_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Permission denied or quiz not found.");
    }

    // 3. Sequential Deletion (Child tables first)
    
    // Delete any student submissions for this quiz
    $pdo->prepare("DELETE FROM assessment_submissions WHERE assessment_id = ?")->execute([$quiz_id]);

    // Delete all questions belonging to this quiz
    $pdo->prepare("DELETE FROM quiz_questions WHERE assessment_id = ?")->execute([$quiz_id]);

    // 4. Finally, delete the actual quiz record
    $pdo->prepare("DELETE FROM assessments WHERE id = ?")->execute([$quiz_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Providing detailed database error info for debugging
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}