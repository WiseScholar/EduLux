<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access denied.']);
    exit;
}

try {
    $submission_id = (int)($_POST['submission_id'] ?? 0);
    $score = (float)($_POST['score'] ?? 0); 
    $feedback = trim($_POST['feedback'] ?? '');

    if (!$submission_id) {
        throw new Exception("Invalid submission identity.");
    }

    $pdo->beginTransaction();

    // 1. Update the Primary Submission (The one the student actually uploaded)
    $stmt = $pdo->prepare("
        UPDATE assessment_submissions 
        SET score = ?, 
            feedback = ?, 
            status = 'graded'
        WHERE id = ?
    ");
    $stmt->execute([$score, $feedback, $submission_id]);

    // 2. Fetch context for the ripple effect
    $check_stmt = $pdo->prepare("
        SELECT a.is_group_assignment, a.id as assessment_id, s.user_id, a.course_id 
        FROM assessment_submissions s 
        JOIN assessments a ON s.assessment_id = a.id 
        WHERE s.id = ?
    ");
    $check_stmt->execute([$submission_id]);
    $info = $check_stmt->fetch();

    if ($info && $info['is_group_assignment']) {
        // Find the group ID
        $group_stmt = $pdo->prepare("
            SELECT group_id FROM group_members 
            WHERE user_id = ? AND group_id IN (SELECT id FROM `groups` WHERE course_id = ?)
        ");
        $group_stmt->execute([$info['user_id'], $info['course_id']]);
        $group_id = $group_stmt->fetchColumn();

        if ($group_id) {
            // Find all teammates (EXCLUDING the person who actually submitted)
            $members_stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?");
            $members_stmt->execute([$group_id, $info['user_id']]);
            $teammates = $members_stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($teammates)) {
                // CLEANUP: Delete any previous synced records for these teammates for this specific assessment
                // This prevents the duplication issue entirely.
                $placeholders = implode(',', array_fill(0, count($teammates), '?'));
                $delete_sql = "DELETE FROM assessment_submissions WHERE assessment_id = ? AND user_id IN ($placeholders)";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute(array_merge([$info['assessment_id']], $teammates));

                // SYNC: Insert fresh graded records for everyone else
                $insert_stmt = $pdo->prepare("
                    INSERT INTO assessment_submissions (assessment_id, user_id, score, feedback, status, submitted_at)
                    VALUES (?, ?, ?, ?, 'graded', NOW())
                ");

                foreach ($teammates as $t_id) {
                    $insert_stmt->execute([
                        $info['assessment_id'],
                        $t_id,
                        $score, // This is exactly what you typed in the box
                        "[Group Grade] " . $feedback
                    ]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Grades synchronized successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Grading Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}