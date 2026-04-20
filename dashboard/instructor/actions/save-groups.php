<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// 2. Parse Input
$input = json_decode(file_get_contents('php://input'), true);
$course_id = (int)($input['course_id'] ?? 0);
$groups_data = $input['groups'] ?? [];
$instructor_id = $_SESSION['user_id'];

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid course context.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 3. Clean Slate Strategy
    // We remove existing groups for this course to rebuild the structure.
    // Foreign Keys (ON DELETE CASCADE) will automatically clean group_members and assessment_groups.
    $clear = $pdo->prepare("DELETE FROM `groups` WHERE course_id = ? AND instructor_id = ?");
    $clear->execute([$course_id, $instructor_id]);

    // 4. Reconstruction Loop
    $group_stmt = $pdo->prepare("
        INSERT INTO `groups` (course_id, name, max_members, instructor_id) 
        VALUES (?, ?, ?, ?)
    ");

    $member_stmt = $pdo->prepare("
        INSERT INTO group_members (group_id, user_id, role) 
        VALUES (?, ?, ?)
    ");

    foreach ($groups_data as $g) {
        $group_name = trim($g['name'] ?? 'Unnamed Group');
        $max_size = (int)($g['max_capacity'] ?? $g['max'] ?? 5);
        
        // Insert the Group Header
        $group_stmt->execute([$course_id, $group_name, $max_size, $instructor_id]);
        $new_group_id = $pdo->lastInsertId();

        // Insert the Members
        if (!empty($g['members']) && is_array($g['members'])) {
            foreach ($g['members'] as $index => $member) {
                $user_id = (int)$member['id'];
                // We set the first student in the list as the 'leader' by default
                $role = ($index === 0) ? 'leader' : 'member';
                
                $member_stmt->execute([$new_group_id, $user_id, $role]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Group architecture successfully deployed to course registry.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("GMS Save Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'System error: ' . $e->getMessage()
    ]);
}