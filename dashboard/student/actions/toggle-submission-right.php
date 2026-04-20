<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$group_id = (int)($input['group_id'] ?? 0);
$state = $input['state'] ? 1 : 0; // 1 for ON, 0 for OFF

try {
    $pdo->beginTransaction();

    if ($state === 1) {
        // Step A: Strip everyone else in the group of submission rights
        $reset = $pdo->prepare("UPDATE group_members SET can_submit = 0 WHERE group_id = ?");
        $reset->execute([$group_id]);
        
        // Step B: Give rights to this specific user
        $grant = $pdo->prepare("UPDATE group_members SET can_submit = 1 WHERE group_id = ? AND user_id = ?");
        $grant->execute([$group_id, $user_id]);
    } else {
        // Just turn it off for the current user
        $revoke = $pdo->prepare("UPDATE group_members SET can_submit = 0 WHERE group_id = ? AND user_id = ?");
        $revoke->execute([$group_id, $user_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}