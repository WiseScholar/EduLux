<?php
require_once 'includes/config.php';

// Set precision for floats
ini_set('precision', 14);

try {
    $assessment_id = 4; // Your target quiz ID
    echo "<h1>📊 Grade Integrity Audit</h1>";
    echo "Starting correlation check for Assessment ID: $assessment_id...<br><hr>";

    // 1. Fetch the Answer Key
    $q_stmt = $pdo->prepare("SELECT id, correct_answer, points, type FROM quiz_questions WHERE assessment_id = ?");
    $q_stmt->execute([$assessment_id]);
    $questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_possible_points = 0;
    $answer_key = [];
    foreach ($questions as $q) {
        $answer_key[$q['id']] = [
            'answer' => trim((string)$q['correct_answer']),
            'points' => (float)$q['points'],
            'is_manual' => ($q['type'] === 'short_answer')
        ];
        $total_possible_points += (float)$q['points'];
    }

    echo "Answer Key loaded. Total possible points: $total_possible_points<br><br>";

    // 2. Fetch all submissions
    $s_stmt = $pdo->prepare("SELECT id, user_id, score, answers_json FROM assessment_submissions WHERE assessment_id = ?");
    $s_stmt->execute([$assessment_id]);
    $submissions = $s_stmt->fetchAll(PDO::FETCH_ASSOC);

    $updates_count = 0;

    foreach ($submissions as $sub) {
        $sub_id = $sub['id'];
        $current_db_score = (float)$sub['score'];
        $student_answers = json_decode($sub['answers_json'] ?? '{}', true);

        if (empty($student_answers)) {
            echo "⚠️ ID $sub_id: No answers found. Skipping.<br>";
            continue;
        }

        // 3. Calculate True Score
        $earned_points = 0;
        foreach ($answer_key as $qid => $data) {
            if ($data['is_manual']) {
                // We skip manual questions in this auto-sync 
                // because we can't "calculate" a short answer.
                continue; 
            }

            $student_val = isset($student_answers[$qid]) ? trim((string)$student_answers[$qid]) : '';
            
            if ($student_val === $data['answer']) {
                $earned_points += $data['points'];
            }
        }

        $calculated_pct = ($total_possible_points > 0) ? ($earned_points / $total_possible_points) * 100 : 0;
        $calculated_pct = round($calculated_pct, 2);

        // 4. Compare and Patch
        // We use a small epsilon check for float comparison
        if (abs($calculated_pct - $current_db_score) > 0.01) {
            echo "🛠️ <b>Mismatch Found (Sub ID $sub_id):</b> DB says $current_db_score%, True score is $calculated_pct%. Updating... ";
            
            $update = $pdo->prepare("UPDATE assessment_submissions SET score = ? WHERE id = ?");
            $update->execute([$calculated_pct, $sub_id]);
            
            echo "<span style='color:green;'>FIXED</span><br>";
            $updates_count++;
        } else {
            echo "✅ ID $sub_id: Integrity verified ($current_db_score%).<br>";
        }
    }

    echo "<hr><b>Audit Complete.</b> Total records patched: $updates_count";

} catch (Exception $e) {
    die("❌ Audit Failed: " . $e->getMessage());
}