<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Security & Role Validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$instructor_id = $_SESSION['user_id'];

// 2. Collect & Sanitize Data
$course_id       = (int)($_POST['course_id'] ?? 0);
$title           = trim($_POST['title'] ?? '');
$description     = trim($_POST['description'] ?? '');
$assignment_mode = $_POST['assignment_mode'] ?? 'standard';
$max_points      = (int)($_POST['max_points'] ?? 100);
$due_date        = $_POST['due_date'] ?? null;
$distribution_mode = $_POST['distribution_mode'] ?? 'all';
$questions_json  = $_POST['questions'] ?? '[]';
$group_ids       = $_POST['selected_groups'] ?? [];

// Parse questions
$questions = json_decode($questions_json, true);

if (!$title || $course_id === 0 || empty($group_ids)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields or no groups selected.']);
    exit;
}

// Handle document upload if in document mode
$document_path = null;
if ($assignment_mode === 'document' && isset($_FILES['primary_document']) && $_FILES['primary_document']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = ROOT_PATH . 'uploads/assessments/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['primary_document'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'group_assessment_' . time() . '_' . uniqid() . '.' . $ext;
    $target = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $document_path = 'uploads/assessments/' . $filename;
    }
}

try {
    $pdo->beginTransaction();

    // 3. Create the Main Assessment Record
    // Fixed: 9 placeholders, 9 values
    $sql = "INSERT INTO assessments (
                course_id, 
                instructor_id, 
                title, 
                description, 
                type, 
                is_group_assignment, 
                max_points, 
                due_date,
                assignment_mode,
                document_path,
                distribution_mode,
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, 'assignment', 1, ?, ?, ?, ?, ?, 'published', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $course_id,
        $instructor_id,
        $title,
        $description,
        $max_points,
        $due_date,
        $assignment_mode,
        $document_path,
        $distribution_mode
    ]);
    
    $assessment_id = $pdo->lastInsertId();

    // 4. Save Questions
    if (!empty($questions)) {
        $question_sql = "INSERT INTO assessment_questions (
                            assessment_id, 
                            question_text, 
                            points, 
                            assigned_groups,
                            sort_order
                        ) VALUES (?, ?, ?, ?, ?)";
        $question_stmt = $pdo->prepare($question_sql);
        
        foreach ($questions as $index => $question) {
            $question_text = trim($question['text'] ?? '');
            $points = (int)($question['points'] ?? 10);
            $assigned_groups = !empty($question['assigned_groups']) ? json_encode($question['assigned_groups']) : null;
            $sort_order = $index;
            
            if (!empty($question_text)) {
                $question_stmt->execute([
                    $assessment_id,
                    $question_text,
                    $points,
                    $assigned_groups,
                    $sort_order
                ]);
            }
        }
    }

    // 5. Map Selected Groups to this Assessment
    $bridge_sql = "INSERT INTO assessment_groups (assessment_id, group_id) VALUES (?, ?)";
    $bridge_stmt = $pdo->prepare($bridge_sql);

    foreach ($group_ids as $gid) {
        $bridge_stmt->execute([$assessment_id, (int)$gid]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Group assignment deployed successfully.',
        'assessment_id' => $assessment_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Group Assessment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}