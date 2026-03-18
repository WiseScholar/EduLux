<?php
require_once __DIR__ . '/../../../includes/config.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['course_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data structure received.']);
    exit;
}

$course_id = (int)$data['course_id'];
$modules = $data['modules'];
$instructor_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 1. Ownership Check
    $check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
    $check->execute([$course_id, $instructor_id]);
    if (!$check->fetch()) {
        throw new Exception("Unauthorized: Access Denied.");
    }

    $deleteModules = $pdo->prepare("DELETE FROM modules WHERE course_id = ?");
    $deleteModules->execute([$course_id]);

    foreach ($modules as $mIndex => $module) {
        $mStmt = $pdo->prepare("INSERT INTO modules (course_id, title, description, order_index) VALUES (?, ?, ?, ?)");
        $mStmt->execute([
            $course_id, 
            htmlspecialchars($module['title']), 
            $module['description'] ?? null,
            $mIndex
        ]);
        $module_id = $pdo->lastInsertId();

        if (!empty($module['lessons'])) {
            foreach ($module['lessons'] as $lIndex => $lesson) {
                $sql = "INSERT INTO lessons (module_id, title, content_text, video_url, order_index) 
                        VALUES (?, ?, ?, ?, ?)";
                
                $lStmt = $pdo->prepare($sql);
                $lStmt->execute([
                    $module_id,
                    htmlspecialchars($lesson['title']),
                    $lesson['content'] ?? null, 
                    $lesson['video_url'] ?? null,
                    $lIndex
                ]);
            }
        }
    }

    $newStatus = $data['status'] === 'published' ? 'published' : 'draft';

    $updateStatus = $pdo->prepare("UPDATE courses SET status = ? WHERE id = ?");
    $updateStatus->execute([$newStatus, $course_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Course published successfully!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}