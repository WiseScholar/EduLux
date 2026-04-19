<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// SET THE ID MANUALLY HERE FOR THE STUDENT NOT SHOWING
$test_submission_id = 37; 

echo "<h1>🔍 Deep Audit Debugger</h1>";
echo "<pre>";

try {
    // 1. RAW SUBMISSION CHECK
    $stmt = $pdo->prepare("SELECT * FROM assessment_submissions WHERE id = ?");
    $stmt->execute([$test_submission_id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub) {
        die("❌ ERROR: Submission ID $test_submission_id not found in database.");
    }
    echo "✅ [1/4] Submission record found for User ID: " . $sub['user_id'] . "\n";

    // 2. JSON DECODE CHECK
    $raw_json = $sub['answers_json'];
    $decoded = json_decode($raw_json, true);

    echo "✅ [2/4] Raw JSON from DB: " . htmlspecialchars($raw_json) . "\n";
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON DECODE ERROR: " . json_last_error_msg() . "\n";
    } else {
        echo "✅ JSON decoded successfully. Found " . count($decoded) . " answers.\n";
        print_r($decoded); // See exactly what the keys look like
    }

    // 3. QUESTION MATCHING CHECK
    $q_stmt = $pdo->prepare("SELECT id, question_text FROM quiz_questions WHERE assessment_id = ?");
    $q_stmt->execute([$sub['assessment_id']]);
    $questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n✅ [3/4] Found " . count($questions) . " questions for Assessment ID: " . $sub['assessment_id'] . "\n";

    $match_count = 0;
    foreach ($questions as $q) {
        $qid = $q['id'];
        // This is the CRITICAL point: checking if the ID exists in the JSON
        if (array_key_exists((string)$qid, $decoded)) {
            $match_count++;
        } else {
            echo "⚠️ MISMATCH: Question ID $qid exists in quiz but NOT in this student's submission.\n";
        }
    }
    echo "📊 MATCH SUMMARY: $match_count out of " . count($questions) . " questions matched the student's data.\n";

    // 4. DATA TYPES CHECK
    if (count($questions) > 0) {
        echo "\n✅ [4/4] TYPE CHECK:\n";
        echo "Question ID type: " . gettype($questions[0]['id']) . " (Value: " . $questions[0]['id'] . ")\n";
        $first_key = array_key_first($decoded);
        echo "JSON Key type: " . gettype($first_key) . " (Value: " . $first_key . ")\n";
    }

} catch (Exception $e) {
    echo "❌ CRITICAL SCRIPT ERROR: " . $e->getMessage();
}

echo "</pre>";