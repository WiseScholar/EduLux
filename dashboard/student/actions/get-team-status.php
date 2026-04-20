<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

$group_id = (int)($_GET['group_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT 
        gm.user_id, 
        gm.can_submit, 
        u.first_name as name, 
        u.avatar,
        -- If last_seen was less than 10 seconds ago, they are online
        IF(gm.last_seen > NOW() - INTERVAL 10 SECOND, 1, 0) as is_online
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
");
$stmt->execute([$group_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));