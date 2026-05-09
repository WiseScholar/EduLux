<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/config.php';

$instructor_id = $_SESSION['user_id'];
$course_id = (int)($_POST['course_id'] ?? 0);
$quiz_id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$quiz_mode = $_POST['quiz_mode'] ?? 'digital';
$instructions = $_POST['instructions'] ?? '';

$questions = isset($_POST['questions']) ? json_decode($_POST['questions'], true) : [];

if (empty($_POST['title']) || $course_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required quiz metadata.']);
    exit;
}

// Duration logic
$duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : 30;

try {
    $pdo->beginTransaction();

    // 2. Handle File Upload
    $file_path = null;
    if ($quiz_mode === 'document' && isset($_FILES['quiz_file'])) {
        $file = $_FILES['quiz_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'quiz_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_dir = __DIR__ . '/../../../assets/uploads/assessments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $file_path = 'assets/uploads/assessments/' . $filename;
        }
    }

    if ($quiz_id) {
        // 3. UPDATE Existing
        $chk = $pdo->prepare("SELECT a.id FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.instructor_id = ?");
        $chk->execute([$quiz_id, $instructor_id]);
        if (!$chk->fetch()) throw new Exception("Unauthorized access.");

        $update_sql = "UPDATE assessments SET title = ?, instructions = ?, due_date = ?, passing_score = ?, duration = ?, quiz_mode = ?";
        $params = [
            $_POST['title'],
            $instructions,
            !empty($_POST['due_date']) ? $_POST['due_date'] : null, 
            (int)$_POST['passing_score'], 
            $duration,
            $quiz_mode
        ];

        if ($file_path) {
            $update_sql .= ", file_path = ?";
            $params[] = $file_path;
        }

        $update_sql .= " WHERE id = ?";
        $params[] = $quiz_id;

        $update = $pdo->prepare($update_sql);
        $update->execute($params);
    } else {
        // 3. INSERT New
        // FIXED QUERY: Replaced max_attempts with duration and ensured 8 placeholders for 8 columns
        $insert = $pdo->prepare("INSERT INTO assessments (course_id, instructor_id, title, instructions, type, due_date, passing_score, duration, quiz_mode, file_path) VALUES (?, ?, ?, ?, 'quiz', ?, ?, ?, ?, ?)");
        $insert->execute([
            $course_id,
            $instructor_id,
            $_POST['title'],
            $instructions,
            !empty($_POST['due_date']) ? $_POST['due_date'] : null, 
            (int)$_POST['passing_score'], 
            $duration,
            $quiz_mode,
            $file_path
        ]);
        $quiz_id = $pdo->lastInsertId();
    }

    // 4. Handle Digital Questions
    if ($quiz_mode === 'digital') {
        $pdo->prepare("DELETE FROM quiz_questions WHERE assessment_id = ?")->execute([$quiz_id]);
        $ins = $pdo->prepare("INSERT INTO quiz_questions (assessment_id, section_title, question_text, type, options, correct_answer, points) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($questions as $q) {
            $text = trim($q['text'] ?? '');
            if (empty($text)) continue;

            $type = $q['type'];
            $pts  = (int)($q['points'] ?? 1);
            $section_title = (!empty($q['section_title'])) ? trim($q['section_title']) : null;
            $options = ($type === 'multiple_choice') ? json_encode($q['options']) : null;
            $correct = (string)($q['correct'] ?? '');

            $ins->execute([$quiz_id, $section_title, $text, $type, $options, $correct, $pts]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'assessment_id' => $quiz_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}