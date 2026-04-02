<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$assessment_id = (int)$_POST['assessment_id'];
$user_answers = json_decode($_POST['answers'], true);

try {
    // 1. Fetch Question Data (Correct answers and Types)
    // We use 'correct_answer' to match your save-complete-quiz.php logic
    $stmt = $pdo->prepare("SELECT id, type, correct_answer, points FROM quiz_questions WHERE assessment_id = ?");
    $stmt->execute([$assessment_id]);
    $questions_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Intelligence Grading Logic
    $total_earned = 0;
    $total_possible = 0;
    $has_manual_grading = false;

    foreach ($questions_data as $q) {
        $q_id = $q['id'];
        $total_possible += (int)$q['points'];
        $user_val = $user_answers[$q_id] ?? null;

        if ($q['type'] === 'short_answer') {
            // Short answers require manual review
            $has_manual_grading = true;
        } else {
            // Auto-grade Choice and True/False
            // We use (string) cast to ensure '0' (first index) isn't treated as empty/false
            if ($user_val !== null && (string)$user_val === (string)$q['correct_answer']) {
                $total_earned += (int)$q['points'];
            }
        }
    }

    // Calculate percentage based on points
    $final_score_pct = $total_possible > 0 ? ($total_earned / $total_possible) * 100 : 0;

    // Determine status: If manual grading is needed, set to 'submitted', else 'graded'
    $status = $has_manual_grading ? 'submitted' : 'graded';

    // 3. Save Master Submission Record
    $pdo->beginTransaction();

    $upd = $pdo->prepare("
        UPDATE assessment_submissions 
        SET score = ?, status = ?, submitted_at = NOW() 
        WHERE assessment_id = ? AND user_id = ? AND status = 'in_progress'
    ");
    $upd->execute([$final_score_pct, $status, $assessment_id, $user_id]);

    // Fetch the ID of the updated record to use for quiz_answers
    $submission_stmt = $pdo->prepare("SELECT id FROM assessment_submissions WHERE assessment_id = ? AND user_id = ? ORDER BY started_at DESC LIMIT 1");
    $submission_stmt->execute([$assessment_id, $user_id]);
    $submission_id = $submission_stmt->fetchColumn();

    // 4. Save individual answer entities (Crucial for the "Audit" page we discussed)
    $ans_stmt = $pdo->prepare("
        INSERT INTO quiz_answers (submission_id, question_id, answer_text) 
        VALUES (?, ?, ?)
    ");

    foreach ($questions_data as $q) {
        $q_id = $q['id'];
        $student_input = isset($user_answers[$q_id]) ? (string)$user_answers[$q_id] : '';

        $ans_stmt->execute([$submission_id, $q_id, $student_input]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'score' => $final_score_pct,
        'status' => $status,
        'message' => $has_manual_grading ? 'Response transmitted for manual evaluation.' : 'Diagnostic complete.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
