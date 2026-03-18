<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

if ($_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$instructor_id = $_SESSION['user_id'];
$scales = $data['scales'] ?? [];

try {
    $pdo->beginTransaction();

    // 1. Delete existing scales for this instructor
    $del = $pdo->prepare("DELETE FROM grading_scales WHERE instructor_id = ?");
    $del->execute([$instructor_id]);

    // 2. Insert new scales
    $ins = $pdo->prepare("INSERT INTO grading_scales (instructor_id, grade_letter, min_score, max_score, color_hex) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($scales as $s) {
        $ins->execute([
            $instructor_id,
            $s['grade_letter'],
            (int)$s['min_score'],
            (int)$s['max_score'],
            $s['color_hex']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}