<?php
ob_start();

require_once __DIR__ . '/../../vendor/autoload.php'; 
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'An error occurred.'];

try {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        throw new Exception("Invalid CSRF token.");
    }

    $action = $_POST['action'] ?? '';
    $course_id = (int)($_POST['course_id'] ?? 0);
    $type = strtoupper(trim($_POST['type'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $start_time_str = trim($_POST['start_time'] ?? '');
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);
    $unique_id = trim($_POST['unique_id'] ?? '');

    if ($action === 'add' || $action === 'update') {
        if (empty($course_id) || empty($type) || empty($title) || empty($start_time_str)) {
            throw new Exception("Missing required fields for ADD/UPDATE.");
        }
        $valid_types = ['LESSON', 'QUIZ', 'MILESTONE', 'OTHER'];
        if (!in_array($type, $valid_types)) {
            throw new Exception("Invalid activity type selected.");
        }
    } elseif ($action === 'drag_update') {
        if (empty($schedule_id) || empty($unique_id) || empty($start_time_str)) {
            throw new Exception("Missing required IDs or start time for drag update.");
        }
    } else {
        throw new Exception("Invalid action specified.");
    }

    $db_start_time = str_replace('T', ' ', $start_time_str) . ':00';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $db_start_time);
    if (!$dt || $dt->format('Y-m-d H:i:s') !== $db_start_time || $dt < new DateTime()) {
        throw new Exception("Invalid or past date/time specified.");
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO course_schedule (course_id, instructor_id, type, title, start_time, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$course_id, $instructor_id, $type, $title, $db_start_time, $notes]);
        $new_id = $pdo->lastInsertId();

        $event_data = [
            'id' => 'CS-' . $new_id,
            'entity_id' => $new_id,
            'course_id' => $course_id,
            'type' => $type,
            'title' => $title,
            'start_time' => $db_start_time,
            'notes' => $notes,
        ];

    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("
            UPDATE course_schedule
            SET course_id = ?, type = ?, title = ?, start_time = ?, notes = ?
            WHERE id = ? AND instructor_id = ?
        ");
        $stmt->execute([$course_id, $type, $title, $db_start_time, $notes, $schedule_id, $instructor_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Schedule item not found or no changes were made.");
        }

        $event_data = [
            'id' => 'CS-' . $schedule_id,
            'entity_id' => $schedule_id,
            'course_id' => $course_id,
            'type' => $type,
            'title' => $title,
            'start_time' => $db_start_time,
            'notes' => $notes,
        ];

    } elseif ($action === 'drag_update') {
        $unique_id_prefix = substr($unique_id, 0, 3);
        if ($unique_id_prefix === 'LS-') {
            throw new Exception("Live Session times must be updated on the Live Session page.");
        }

        $stmt = $pdo->prepare("
            UPDATE course_schedule
            SET start_time = ?
            WHERE id = ? AND instructor_id = ?
        ");
        $stmt->execute([$db_start_time, $schedule_id, $instructor_id]);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Schedule time updated successfully via drag-and-drop.']);
        exit;
    }

    if ($action === 'add' || $action === 'update') {
        $students_stmt = $pdo->prepare("SELECT DISTINCT user_id FROM enrollments WHERE course_id = ?");
        $students_stmt->execute([$course_id]);
        $student_ids = $students_stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($student_ids)) {
            $course_title_stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
            $course_title_stmt->execute([$course_id]);
            $course_title = $course_title_stmt->fetchColumn() ?: 'Course';

            $action_text = $action === 'add' ? 'New' : 'Updated';
            $message = "{$action_text} schedule: {$title} ({$type}) in {$course_title} on " . date('M j \a\t g:i A', $dt->getTimestamp());
            $link = BASE_URL . "dashboard/student/timetable.php";

            send_in_app_notifications($pdo, $student_ids, $message, $link);

            $subscriptions = get_push_subscriptions($pdo, $student_ids);
            if (!empty($subscriptions)) {
                $push_payload = [
                    'title' => $action_text . ' Schedule Item!',
                    'body' => $message,
                    'url' => $link
                ];
                send_web_push_notifications($pdo, $subscriptions, $push_payload);
            }
        }
    }

    $response['success'] = true;
    $response['message'] = "Schedule item {$action}ed successfully.";
    $response['event'] = $event_data;

} catch (Exception $e) {
    error_log("Schedule Handler Error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;