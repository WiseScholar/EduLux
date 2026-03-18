<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Get the combined data package
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['settings']) || !isset($data['questions'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data structure received.']);
    exit;
}

$set = $data['settings'];
$questions = $data['questions'];
$instructor_id = $_SESSION['user_id'];
$course_id = (int)$set['course_id'];

try {
    $pdo->beginTransaction();

    // 2. Step One: Handle the Assessment (The "Container")
    if (!empty($set['id'])) {
        // We are EDITING an existing quiz
        $quiz_id = (int)$set['id'];
        
        // Security check for edit
        $chk = $pdo->prepare("SELECT a.id FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.instructor_id = ?");
        $chk->execute([$quiz_id, $instructor_id]);
        if (!$chk->fetch()) throw new Exception("Unauthorized edit attempt.");

        $update = $pdo->prepare("UPDATE assessments SET title = ?, due_date = ?, passing_score = ?, max_attempts = ? WHERE id = ?");
        $update->execute([
            $set['title'], 
            !empty($set['due_date']) ? $set['due_date'] : null, 
            (int)$set['passing_score'], 
            (int)$set['max_attempts'], 
            $quiz_id
        ]);
    } else {
        // We are CREATING a new quiz
        $insert = $pdo->prepare("INSERT INTO assessments (course_id, title, type, due_date, passing_score, max_attempts) VALUES (?, ?, 'quiz', ?, ?, ?)");
        $insert->execute([
            $course_id, 
            $set['title'], 
            !empty($set['due_date']) ? $set['due_date'] : null, 
            (int)$set['passing_score'], 
            (int)$set['max_attempts']
        ]);
        $quiz_id = $pdo->lastInsertId();
    }

    // 3. Step Two: Clear old questions if any (to prevent duplicates/ghost questions)
    $pdo->prepare("DELETE FROM assessment_questions WHERE assessment_id = ?")->execute([$quiz_id]);

    // 4. Step Three: Insert the new set of questions
    $ins = $pdo->prepare("INSERT INTO assessment_questions (assessment_id, question_text, question_type, options, correct_answer, points) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($questions as $q) {
        $text = trim($q['text']);
        if (empty($text)) continue; // Skip empty questions

        $type = $q['type'];
        $pts  = (int)($q['points'] ?? 1);
        $options = null;
        $correct = (string)($q['correct'] ?? '');

        if ($type === 'multiple_choice') {
            $options = json_encode($q['options']);
        }

        $ins->execute([$quiz_id, $text, $type, $options, $correct, $pts]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'assessment_id' => $quiz_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}