<?php

require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized access.']));
}

$student_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'fetch':
        $stmt = $pdo->prepare("
            SELECT id, message, link_url, is_read, 
                   DATE_FORMAT(created_at, '%M %D, %Y @ %h:%i %p') AS created_at
            FROM notifications 
            WHERE user_id = ?
            ORDER BY is_read ASC, created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$student_id]);
        $notifications = $stmt->fetchAll();

        exit(json_encode(['success' => true, 'notifications' => $notifications]));
        break;

    case 'mark_one':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            exit(json_encode(['error' => 'Invalid request or CSRF token.']));
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $student_id]);
            exit(json_encode(['success' => true]));
        }
        exit(json_encode(['error' => 'Invalid notification ID.']));
        break;

    case 'mark_all':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            exit(json_encode(['error' => 'Invalid request or CSRF token.']));
        }
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$student_id]);
        exit(json_encode(['success' => true]));
        break;

    default:
        http_response_code(400);
        exit(json_encode(['error' => 'Action not supported.']));
}
?>