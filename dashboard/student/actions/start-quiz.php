<?php
require_once __DIR__ . '/../../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$assessment_id = (int)$_POST['assessment_id'];
$user_id = $_SESSION['user_id'];

// Check if a session is already in progress to prevent resetting the timer on refresh
$check = $pdo->prepare("SELECT id FROM assessment_submissions WHERE assessment_id = ? AND user_id = ? AND status = 'in_progress'");
$check->execute([$assessment_id, $user_id]);

if (!$check->fetch()) {
    $init = $pdo->prepare("INSERT INTO assessment_submissions (assessment_id, user_id, started_at, status, score, answers_json) VALUES (?, ?, NOW(), 'in_progress', 0, '{}')");
    $init->execute([$assessment_id, $user_id]);
}

echo json_encode(['success' => true]);