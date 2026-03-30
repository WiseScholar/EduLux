<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

$user_id = $_SESSION['user_id'];
$assessment_id = (int)$_POST['assessment_id'];
$user_answers = json_decode($_POST['answers'], true);

try {
    // 1. Fetch Correct Answers
    $stmt = $pdo->prepare("SELECT id, correct_option FROM quiz_questions WHERE assessment_id = ?");
    $stmt->execute([$assessment_id]);
    $correct_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Grade Quiz
    $score = 0;
    $total_questions = count($correct_data);
    
    foreach ($correct_data as $q_id => $correct_opt) {
        if (isset($user_answers[$q_id]) && $user_answers[$q_id] == $correct_opt) {
            $score++;
        }
    }

    $final_score_pct = ($score / $total_questions) * 100;

    // 3. Save Submission
    $ins = $pdo->prepare("
        INSERT INTO assessment_submissions (assessment_id, user_id, score, status, submitted_at) 
        VALUES (?, ?, ?, 'graded', NOW())
    ");
    $ins->execute([$assessment_id, $user_id, $final_score_pct]);
    
    // 4. (Optional) Auto-generate certificate if passed...
    // logic here...

    echo json_encode(['success' => true, 'score' => $final_score_pct]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}