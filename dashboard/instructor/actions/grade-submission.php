<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

if ($_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $submission_id = (int)$_POST['submission_id'];
    $score = (int)$_POST['score'];
    $feedback = $_POST['feedback'];

    $stmt = $pdo->prepare("
        UPDATE assessment_submissions 
        SET score = ?, feedback = ?, status = 'graded' 
        WHERE id = ?
    ");
    $stmt->execute([$score, $feedback, $submission_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}