<?php
require_once __DIR__ . '/../includes/config.php';

// Security: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];

try {
    // Update all unread notifications for this user
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}