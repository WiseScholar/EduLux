<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$group_id = (int)($input['group_id'] ?? 0);
$message = trim($input['message'] ?? '');

if (!$group_id || !$message) {
    echo json_encode(['success' => false]);
    exit;
}

// Security: Verify user is actually in this group
$stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$insert = $pdo->prepare("INSERT INTO group_chats (group_id, user_id, message) VALUES (?, ?, ?)");
$success = $insert->execute([$group_id, $user_id, $message]);

echo json_encode(['success' => $success]);