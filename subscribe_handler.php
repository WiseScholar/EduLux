<?php

require_once __DIR__ . '/includes/config.php';

ob_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

$student_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action !== 'subscribe' || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid action or CSRF token']);
    ob_end_flush();
    exit;
}

$endpoint = trim($_POST['endpoint'] ?? '');
$p256dh   = trim($_POST['p256dh'] ?? '');
$auth     = trim($_POST['auth'] ?? '');

if (empty($endpoint) || empty($p256dh) || empty($auth)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing subscription data (endpoint, p256dh, or auth)']);
    ob_end_flush();
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO push_subscriptions 
            (user_id, endpoint, p256dh, auth, created_at) 
        VALUES 
            (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            p256dh = VALUES(p256dh),
            auth = VALUES(auth),
            created_at = NOW()
    ");

    $stmt->execute([$student_id, $endpoint, $p256dh, $auth]);

    echo json_encode([
        'success' => true,
        'message' => 'Subscription saved successfully'
    ]);

} catch (Exception $e) {
    error_log('Push subscription DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save subscription'
    ]);
}

ob_end_flush();
exit;