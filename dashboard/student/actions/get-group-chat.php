<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

$group_id = (int)($_GET['group_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Security: Verify membership
$stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if (!$stmt->fetch()) exit(json_encode([]));

$query = $pdo->prepare("
    SELECT c.*, u.first_name as user_name 
    FROM group_chats c
    JOIN users u ON c.user_id = u.id
    WHERE c.group_id = ?
    ORDER BY c.created_at ASC 
    LIMIT 50
");
$query->execute([$group_id]);
echo json_encode($query->fetchAll(PDO::FETCH_ASSOC));