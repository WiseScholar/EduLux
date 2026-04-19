<?php
require_once 'includes/config.php';

try {
    // --- INPUT SECTION ---
    $target_sub_id = 34; // REPLACE 0 WITH THE ACTUAL SUBMISSION ID
    $assessment_id = 4;
    $target_score_percent = 97; 
    // ---------------------

    if ($target_sub_id === 0) die("Error: Please set a valid Submission ID in the script.");

    echo "<h1>🎯 Targeted Data Correction</h1>";

    // 1. Get the Answer Key
    $q_stmt = $pdo->prepare("SELECT id, correct_answer, options, type FROM quiz_questions WHERE assessment_id = ? ORDER BY id ASC");
    $q_stmt->execute([$assessment_id]);
    $questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
    $q_count = count($questions);

    // 2. Calculate necessary correct answers
    $required_correct = round(($target_score_percent / 100) * $q_count);
    $final_calculated_score = round(($required_correct / $q_count) * 100, 2);

    echo "Targeting Sub ID: $target_sub_id<br>";
    echo "Calculated: $required_correct correct answers will result in a score of $final_calculated_score%<br><hr>";

    // 3. Generate the answers
    $generated_answers = [];
    // Randomly pick which questions will be correct
    $correct_indices = (array)array_rand($questions, $required_correct);
    
    foreach ($questions as $index => $q) {
        $qid = $q['id'];
        
        if (in_array($index, $correct_indices)) {
            $generated_answers[$qid] = (string)$q['correct_answer'];
        } else {
            // Assign a wrong answer
            if ($q['type'] === 'multiple_choice') {
                $opts = json_decode($q['options'], true);
                $wrong_options = array_keys($opts);
                $key = array_search($q['correct_answer'], $wrong_options);
                if ($key !== false) unset($wrong_options[$key]);
                $generated_answers[$qid] = (string)array_values($wrong_options)[0];
            } else {
                $generated_answers[$qid] = ($q['correct_answer'] === 'True') ? 'False' : 'True';
            }
        }
    }

    // 4. Update Database
    $update = $pdo->prepare("UPDATE assessment_submissions SET answers_json = ?, score = ?, status = 'graded' WHERE id = ?");
    $update->execute([json_encode($generated_answers), $final_calculated_score, $target_sub_id]);

    echo "<span style='color:green; font-weight:bold;'>SUCCESS:</span> Submission $target_sub_id has been adjusted to $final_calculated_score%.";

} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}