<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/config.php';

if (!defined('ACCESS_GRANTED')) {
    define('ACCESS_GRANTED', true);
}
require_once ROOT_PATH . 'includes/notifications.php';

try {
    if (!isset($_POST['course_id']) || empty($_POST['title'])) {
        throw new Exception("Missing required fields.");
    }

    $pdo->beginTransaction();
    $mode = ($_POST['assignment_mode'] === 'document') ? 'document' : 'digital';

    // 2. Save Main Assessment
    $stmt = $pdo->prepare("INSERT INTO assessments 
        (course_id, title, type, description, max_points, passing_score, due_date, quiz_mode) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        (int)$_POST['course_id'],
        $_POST['title'],
        $_POST['type'],
        $_POST['description'],
        (int)($_POST['max_points'] ?? 100),
        (int)($_POST['passing_score'] ?? 50),
        !empty($_POST['due_date']) ? $_POST['due_date'] : null,
        $mode
    ]);

    $assessment_id = $pdo->lastInsertId();

    // 3. Handle Files
    if (!empty($_FILES['files']['tmp_name'][0])) {
        $upload_dir = ROOT_PATH . 'uploads/assignments/resources/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                $orig_name = $_FILES['files']['name'][$key];
                $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
                $new_name = uniqid('res_') . '_' . time() . '.' . $ext;

                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $ins = $pdo->prepare("INSERT INTO assessment_resources (assessment_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
                    $ins->execute([$assessment_id, $orig_name, 'uploads/assignments/resources/' . $new_name, $ext]);
                }
            }
        }
    }

    // --- 4. NOTIFICATION LOGIC ---

    // Fetch all students enrolled in this course
    $student_stmt = $pdo->prepare("SELECT user_id FROM enrollments WHERE course_id = ? AND status != 'dropped'");
    $student_stmt->execute([(int)$_POST['course_id']]);
    $student_ids = $student_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($student_ids)) {
        $course_name = $_POST['course_title'] ?? 'your course';
        $task_type = ucfirst($_POST['type']);
        $task_title = $_POST['title'];

        $notif_message = "New $task_type in $course_name: $task_title";
        if ($_POST['type'] === 'assignment') {
            $link_url = "student/view-assessment.php?id=" . $assessment_id;
        } else {
            $link_url = "student/take-quiz.php?id=" . $assessment_id;
        }

        // A. In-App Notification
        send_in_app_notifications($pdo, $student_ids, $notif_message, $link_url);

        // B. Web Push Notification
        $subscriptions = get_push_subscriptions($pdo, $student_ids);
        if (!empty($subscriptions)) {
            send_web_push_notifications($pdo, $subscriptions, [
                'title' => 'New Assignment!',
                'body' => $notif_message,
                'url' => BASE_URL . $link_url,
                'icon' => BASE_URL . 'assets/img/logo-icon.png'
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'assessment_id' => $assessment_id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
