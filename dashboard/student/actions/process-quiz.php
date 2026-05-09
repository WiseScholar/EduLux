<?php
header('Content-Type: application/json');
define('ACCESS_GRANTED', true);

require_once __DIR__ . '/../../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// Check Authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. To save your progress, please open the login page in a NEW tab, log in, then return here and click submit again.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$assessment_id = (int)($_POST['assessment_id'] ?? 0);
$user_answers = json_decode($_POST['answers'] ?? '{}', true);

try {
    // 1. Fetch Question Data
    $stmt = $pdo->prepare("SELECT id, type, correct_answer, points FROM quiz_questions WHERE assessment_id = ?");
    $stmt->execute([$assessment_id]);
    $questions_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions_data)) {
        throw new Exception("Assessment data not found.");
    }

    // 2. Intelligence Grading Logic
    $total_earned = 0;
    $total_possible = 0;
    $has_manual_grading = false;

    foreach ($questions_data as $q) {
        $q_id = $q['id'];
        $total_possible += (int)$q['points'];
        $user_val = $user_answers[$q_id] ?? null;

        if ($q['type'] === 'short_answer') {
            $has_manual_grading = true;
        } else {
            // Added trim() to avoid mismatch from accidental leading/trailing spaces
            if ($user_val !== null && trim((string)$user_val) === trim((string)$q['correct_answer'])) {
                $total_earned += (int)$q['points'];
            }
        }
    }

    $final_score_pct = $total_possible > 0 ? ($total_earned / $total_possible) * 100 : 0;
    $status = $has_manual_grading ? 'submitted' : 'graded';

    // 3. Database Transaction
    $pdo->beginTransaction();

    // Combined UPDATE: We save score, status, and the JSON answers in one go.
    // This is safer and faster for the database.
    $upd = $pdo->prepare("
        UPDATE assessment_submissions 
        SET score = ?, 
            status = ?, 
            answers_json = ?, 
            submitted_at = NOW() 
        WHERE assessment_id = ? AND user_id = ? AND status = 'in_progress'
    ");

    $upd->execute([
        $final_score_pct,
        $status,
        json_encode($user_answers),
        $assessment_id,
        $user_id
    ]);

    // 4. Verification & ID Retrieval
    // Even though we updated, we still need the submission_id for the success response/email
    $submission_stmt = $pdo->prepare("
        SELECT id FROM assessment_submissions 
        WHERE assessment_id = ? AND user_id = ? 
        ORDER BY started_at DESC LIMIT 1
    ");
    $submission_stmt->execute([$assessment_id, $user_id]);
    $submission_id = $submission_stmt->fetchColumn();

    if (!$submission_id) {
        throw new Exception("Critical: No submission record found.");
    }

    $pdo->commit();

    // 5. Success Response
    echo json_encode([
        'success' => true,
        'submission_id' => (int)$submission_id,
        'score' => round($final_score_pct, 2),
        'status' => $status,
        'message' => $has_manual_grading ? 'Response transmitted for evaluation.' : 'Diagnostic complete.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Quiz Engine Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Processing error: ' . $e->getMessage()]);
}