<?php
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../includes/config.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();
$msg = null;
$msg_type = 'info';

function send_web_push_notifications(array $subscriptions, string $payload): int
{
    global $pdo;
    $push_success_count = 0;

    if (empty($subscriptions)) {
        return 0;
    }

    try {
        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth, ['localKeyCache' => false]);

        foreach ($subscriptions as $s) {
            $sub = Subscription::create([
                'endpoint' => $s['endpoint'],
                'publicKey' => $s['p256dh'],
                'authToken' => $s['auth'],
            ]);
            $webPush->queueNotification($sub, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $push_success_count++;
            } else {
                if ($report->isSubscriptionExpired()) {
                    $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")
                        ->execute([$report->getSubscription()->getEndpoint()]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("WebPush Error: " . $e->getMessage());
        return 0;
    }

    return $push_success_count;
}


$courses_stmt = $pdo->prepare("
    SELECT id, title
    FROM courses
    WHERE instructor_id = ? AND status = 'published'
    ORDER BY title
");
$courses_stmt->execute([$instructor_id]);
$courses = $courses_stmt->fetchAll();


// --- 2. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $meeting_link = trim($_POST['meeting_link'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');

    if (empty($course_id) || empty($title) || empty($meeting_link) || empty($start_time)) {
        $msg = "All fields marked with * are required.";
        $msg_type = 'danger';
    } elseif (!filter_var($meeting_link, FILTER_VALIDATE_URL)) {
        $msg = "Please enter a valid meeting URL.";
        $msg_type = 'danger';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d H:i', $start_time);
        $is_valid_date = ($dt && $dt->format('Y-m-d H:i') === $start_time && $dt > new DateTime());

        if (!$is_valid_date) {
            $msg = "Invalid date/time or time is in the past. Use YYYY-MM-DD HH:MM";
            $msg_type = 'danger';
        } else {
            $students_notified = 0;
            $push_success_count = 0;
            $push_attempts_count = 0;

            try {
                $start_time_db = $dt->format('Y-m-d H:i:s');

                $insert_stmt = $pdo->prepare("
                    INSERT INTO live_sessions (course_id, title, notes, meeting_link, start_time, instructor_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([$course_id, $title, $notes, $meeting_link, $start_time_db, $instructor_id]);

                $students_stmt = $pdo->prepare("
                    SELECT DISTINCT user_id 
                    FROM enrollments 
                    WHERE course_id = ? 
                ");
                $students_stmt->execute([$course_id]);
                $student_ids_array = $students_stmt->fetchAll(PDO::FETCH_COLUMN);
                $students_notified = count($student_ids_array);

                if ($students_notified === 0) {
                    $msg = "Live Session scheduled successfully. No students are currently enrolled in this course to notify.";
                    $msg_type = 'info';
                } else {
                    $course_title_stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
                    $course_title_stmt->execute([$course_id]);
                    $course_title = $course_title_stmt->fetchColumn() ?: 'Course';

                    $formatted_time = date('M j, Y \a\t g:i A', $dt->getTimestamp());
                    $notification_message_body = "New Live Session: {$title} in {$course_title} on {$formatted_time}";
                    $notification_link = BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}";

                    $placeholders = implode(',', array_fill(0, $students_notified, '(?, ?, ?, NOW())'));
                    $values = [];
                    foreach ($student_ids_array as $sid) {
                        $values[] = $sid;
                        $values[] = $notification_message_body;
                        $values[] = $notification_link;
                    }

                    $notification_stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, message, link_url, created_at) 
                        VALUES $placeholders
                    ");
                    $notification_stmt->execute($values);

                    $in_placeholders = implode(',', array_fill(0, $students_notified, '?'));
                    $subscriptions_stmt = $pdo->prepare("
                        SELECT endpoint, p256dh, auth
                        FROM push_subscriptions 
                        WHERE user_id IN ($in_placeholders)
                    ");
                    $subscriptions_stmt->execute($student_ids_array);
                    $subscriptions = $subscriptions_stmt->fetchAll();

                    $push_attempts_count = count($subscriptions);

                    if ($push_attempts_count > 0) {
                        $push_payload = json_encode([
                            'title' => 'Live Session!',
                            'body' => $notification_message_body,
                            'url' => $notification_link
                        ]);

                        $push_success_count = send_web_push_notifications($subscriptions, $push_payload);
                    }

                    $msg = "Live Session scheduled successfully. In-app notifications sent to {$students_notified} students. Push notifications delivered to {$push_success_count} of {$push_attempts_count} devices.";
                    $msg_type = 'success';
                }

                header("Location: live-sessions.php?msg=" . urlencode($msg) . "&type=" . $msg_type);
                exit;
            } catch (Exception $e) {
                error_log("Live Session Error: " . $e->getMessage());
                $msg = "A server error occurred. Please check logs for details.";
                $msg_type = 'danger';
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'info';
}

$sessions_stmt = $pdo->prepare("
    SELECT ls.*, c.title as course_title
    FROM live_sessions ls
    JOIN courses c ON ls.course_id = c.id
    WHERE ls.instructor_id = ?
    ORDER BY ls.start_time DESC
");
$sessions_stmt->execute([$instructor_id]);
$upcoming_sessions = $sessions_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Live Sessions | Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
</head>

<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <h2 class="fw-bold mb-4">Live Session Scheduler & Management</h2>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="stat-card p-4 h-100">
                        <h4 class="mb-4 text-primary"> <i class="fas fa-calendar-alt me-2"></i> Schedule New Session</h4>

                        <form method="POST" id="scheduleForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="mb-3">
                                <label for="courseId" class="form-label fw-bold">Select Course *</label>
                                <select name="course_id" id="courseId" class="form-select" required>
                                    <option value="">-- Choose Published Course --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="sessionTitle" class="form-label fw-bold">Session Title *</label>
                                <input type="text" name="title" id="sessionTitle" class="form-control" placeholder="e.g., Q&A on Flip Flops" required>
                            </div>

                            <div class="mb-3">
                                <label for="startTime" class="form-label fw-bold">Start Date & Time *</label>
                                <input type="text" name="start_time" id="startTime" class="form-control" placeholder="YYYY-MM-DD HH:MM" required>
                            </div>

                            <div class="mb-3">
                                <label for="meetingLink" class="form-label fw-bold">Meeting Link (Zoom/Meet URL) *</label>
                                <input type="url" name="meeting_link" id="meetingLink" class="form-control" placeholder="https://zoom.us/j/123456789">
                            </div>

                            <div class="mb-4">
                                <label for="sessionNotes" class="form-label fw-bold">Session Notes (Optional)</label>
                                <textarea name="notes" id="sessionNotes" class="form-control" rows="3" placeholder="Topics to cover, required reading, etc."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-calendar-plus me-2"></i> Schedule & Notify
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="stat-card p-4">
                        <h4 class="mb-4 text-primary">Upcoming & Recent Sessions (<?= count($upcoming_sessions) ?>)</h4>

                        <?php if ($upcoming_sessions): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcoming_sessions as $session):
                                    $is_upcoming = strtotime($session['start_time']) > time();
                                    $status_class = $is_upcoming ? 'success' : 'secondary';
                                ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-start bg-transparent py-3 border-bottom-secondary">
                                        <div>
                                            <h6 class="mb-1 text-white fw-bold">
                                                <?= htmlspecialchars($session['title']) ?>
                                                <span class="badge bg-<?= $status_class ?> ms-2"><?= $is_upcoming ? 'Upcoming' : 'Past' ?></span>
                                            </h6>
                                            <small class="d-block text-muted mb-2">
                                                Course: <?= htmlspecialchars($session['course_title']) ?>
                                            </small>
                                            <small class="d-block text-info">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('M j, Y @ g:i A', strtotime($session['start_time'])) ?>
                                            </small>
                                        </div>
                                        <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-3">
                                            <?= $is_upcoming ? 'View Link' : 'Re-use Link' ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                                <p>No sessions scheduled yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#startTime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                time_24hr: true,
                theme: "material_blue"
            });
        });
    </script>
</body>

</html>