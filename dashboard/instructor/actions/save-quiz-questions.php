<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

// 1. Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$assessment_id = (int)$data['assessment_id'];
$questions = $data['questions'];

if (!$assessment_id || empty($questions)) {
    echo json_encode(['success' => false, 'message' => 'No questions provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. Security Check: Ensure instructor owns the course this assessment belongs to
    $stmt = $pdo->prepare("
        SELECT a.id FROM assessments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$assessment_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Unauthorized access.");
    }

    // 3. Clear existing questions (Standard practice for "Sync" builders)
    $del = $pdo->prepare("DELETE FROM assessment_questions WHERE assessment_id = ?");
    $del->execute([$assessment_id]);

    // 4. Insert the new/updated set of questions
    $ins = $pdo->prepare("
        INSERT INTO assessment_questions 
        (assessment_id, question_text, question_type, options, correct_answer, points) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($questions as $q) {
        $text = trim($q['text']);
        $type = $q['type'];
        $pts  = (int)$q['points'];
        
        // Handle different data structures based on type
        $options = null;
        $correct = '';

        if ($type === 'multiple_choice') {
            // Encode the options array into JSON for the DB
            $options = json_encode($q['options']); 
            // Store the index of the correct option
            $correct = (string)$q['correct']; 
        } 
        elseif ($type === 'true_false') {
            $correct = $q['correct']; // Stores "True" or "False"
        } 
        else {
            $correct = 'MANUAL_GRADING'; // For short answers
        }

        $ins->execute([$assessment_id, $text, $type, $options, $correct, $pts]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}