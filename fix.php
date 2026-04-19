<?php
require_once 'includes/config.php';

try {
    $assessment_id = 4; // The specific quiz ID
    
    // 1. Get the CURRENT correct Question IDs in order
    $q_stmt = $pdo->prepare("SELECT id FROM quiz_questions WHERE assessment_id = ? ORDER BY id ASC");
    $q_stmt->execute([$assessment_id]);
    $current_q_ids = $q_stmt->fetchAll(PDO::FETCH_COLUMN);
    $q_count = count($current_q_ids);

    echo "Current Quiz expects $q_count questions starting from ID: " . $current_q_ids[0] . "<br><br>";

    // 2. Fetch all submissions for this quiz
    $s_stmt = $pdo->prepare("SELECT id, answers_json FROM assessment_submissions WHERE assessment_id = ?");
    $s_stmt->execute([$assessment_id]);
    $submissions = $s_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($submissions as $sub) {
        $sub_id = $sub['id'];
        $decoded = json_decode($sub['answers_json'], true);
        
        if (empty($decoded)) continue;

        $first_key = array_key_first($decoded);

        // CHECK: Does the first key match the first actual Question ID?
        if ((string)$first_key !== (string)$current_q_ids[0]) {
            echo "🛠️ Fixing Submission ID: $sub_id (Found starting key $first_key, expected " . $current_q_ids[0] . ")... ";

            // We build a new JSON object using the CURRENT IDs but the OLD answers
            $new_answers = [];
            $old_answers_values = array_values($decoded); // Get answers in order

            foreach ($current_q_ids as $index => $new_id) {
                if (isset($old_answers_values[$index])) {
                    $new_answers[$new_id] = $old_answers_values[$index];
                }
            }

            // Update the database with the corrected JSON
            $update = $pdo->prepare("UPDATE assessment_submissions SET answers_json = ? WHERE id = ?");
            $update->execute([json_encode($new_answers), $sub_id]);
            
            echo "<span style='color:green;'>DONE</span><br>";
        } else {
            echo "✅ Submission ID: $sub_id is already correct.<br>";
        }
    }

    echo "<br><b>Operation Complete.</b> All student records are now synced with the current question set.";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}