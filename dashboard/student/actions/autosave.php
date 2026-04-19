<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// 1. Validate Session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$user_id = $_SESSION['user_id'];
$assessment_id = (int)($_POST['assessment_id'] ?? 0);
$user_answers = $_POST['answers'] ?? '{}'; // This is already a JSON string from the frontend

try {
    // 2. Security Check: Only update if the quiz is actually "in_progress"
    // This prevents autosaves from overwriting a finished exam if a tab was left open
    $check = $pdo->prepare("
        SELECT id FROM assessment_submissions 
        WHERE assessment_id = ? AND user_id = ? AND status = 'in_progress'
        LIMIT 1
    ");
    $check->execute([$assessment_id, $user_id]);
    $submission_id = $check->fetchColumn();

    if (!$submission_id) {
        echo json_encode(['success' => false, 'message' => 'No active session found.']);
        exit;
    }

    // 3. Simple Update (Strategy A)
    // We don't need a transaction here because it's a single, non-critical update
    $stmt = $pdo->prepare("
        UPDATE assessment_submissions 
        SET answers_json = ? 
        WHERE id = ?
    ");
    $success = $stmt->execute([$user_answers, $submission_id]);

    echo json_encode([
        'success' => $success,
        'timestamp' => date('H:i:s'),
        'message' => 'Progress synced'
    ]);

} catch (Exception $e) {
    error_log("Autosave Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error during sync']);
}