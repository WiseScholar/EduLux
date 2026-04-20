<?php
require_once __DIR__ . '/../../../includes/config.php';

$group_id = (int)($_POST['group_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($group_id > 0) {
    $stmt = $pdo->prepare("UPDATE group_members SET last_seen = NOW() WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group_id, $user_id]);
    echo json_encode(['success' => true]);
}