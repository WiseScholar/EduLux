<?php
require_once 'includes/config.php';

// Increase execution time for cPanel environments if the dataset is large
set_time_limit(300);

try {
    // 1. Get all unique submission IDs from the old table
    $stmt = $pdo->query("SELECT DISTINCT submission_id FROM quiz_answers");
    $submissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Migration Intelligence Report</h2>";
    echo "Found <b>" . count($submissions) . "</b> unique submission IDs in the answers table.<br><br>";

    $updateStmt  = $pdo->prepare("UPDATE assessment_submissions SET answers_json = ? WHERE id = ?");
    $oldDataStmt = $pdo->prepare("SELECT question_id, answer_text FROM quiz_answers WHERE submission_id = ?");
    $deleteStmt  = $pdo->prepare("DELETE FROM quiz_answers WHERE submission_id = ?");
    $checkStmt   = $pdo->prepare("SELECT id FROM assessment_submissions WHERE id = ?");

    $migratedCount = 0;
    $orphanCount = 0;

    foreach ($submissions as $sub_id) {
        // A. Verify this submission ID actually exists in the submissions table
        $checkStmt->execute([$sub_id]);
        if (!$checkStmt->fetch()) {
            echo "<span style='color:red;'>[Orphan Found]</span> ID: $sub_id has no parent record in assessment_submissions. Skipping...<br>";
            $orphanCount++;
            continue;
        }

        // B. Start a transaction for THIS specific submission (Atomic Move)
        $pdo->beginTransaction();

        try {
            // C. Fetch all answers for this ID
            $oldDataStmt->execute([$sub_id]);
            $rows = $oldDataStmt->fetchAll(PDO::FETCH_ASSOC);

            $mappedAnswers = [];
            foreach ($rows as $row) {
                $mappedAnswers[$row['question_id']] = $row['answer_text'];
            }

            // D. "Paste" - Update the new JSON column
            $updateStmt->execute([json_encode($mappedAnswers), $sub_id]);

            // E. "Cut" - Remove from the old table
            $deleteStmt->execute([$sub_id]);

            $pdo->commit();
            $migratedCount++;
            echo "<span style='color:green;'>[Success]</span> Submission ID: $sub_id migrated and cleared.<br>";

        } catch (Exception $subEx) {
            $pdo->rollBack();
            echo "Error processing ID $sub_id: " . $subEx->getMessage() . "<br>";
        }
    }

    echo "<hr>";
    echo "<b>Final Summary:</b><br>";
    echo "Successfully Migrated: $migratedCount<br>";
    echo "Orphans Remaining (Manual check needed): $orphanCount<br>";
    
    if ($orphanCount > 0) {
        echo "<br><b>Recommendation:</b> Check quiz_answers for submission IDs that don't match your records. These might belong to deleted users or test attempts.";
    }

} catch (Exception $e) {
    echo "Critical Failure: " . $e->getMessage();
}