<?php
require_once 'includes/config.php';

try {
    $pdo->beginTransaction();

    // 1. Get all unique submission IDs from the old table
    $stmt = $pdo->query("SELECT DISTINCT submission_id FROM quiz_answers");
    $submissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found " . count($submissions) . " submissions to migrate...<br>";

    $updateStmt = $pdo->prepare("UPDATE assessment_submissions SET answers_json = ? WHERE id = ?");
    $oldDataStmt = $pdo->prepare("SELECT question_id, answer_text FROM quiz_answers WHERE submission_id = ?");

    foreach ($submissions as $sub_id) {
        // 2. Fetch all answers for this specific submission
        $oldDataStmt->execute([$sub_id]);
        $rows = $oldDataStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Reconstruct the key-value pair (question_id => answer)
        $mappedAnswers = [];
        foreach ($rows as $row) {
            $mappedAnswers[$row['question_id']] = $row['answer_text'];
        }

        // 4. Update the new column
        $updateStmt->execute([json_encode($mappedAnswers), $sub_id]);
    }

    $pdo->commit();
    echo "Migration successful! You can now safely truncate the quiz_answers table.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Migration failed: " . $e->getMessage());
}