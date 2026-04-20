<?php
require_once __DIR__ . '/../../includes/config.php';

// 1. Validate Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: " . LOGIN_URL);
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$course_id = (int)($_POST['course_id'] ?? 0);
$lesson_id = (int)($_POST['lesson_id'] ?? 0);

if (!$course_id || !$lesson_id) {
    header("Location: " . BASE_URL . "pages/dashboard/student/index.php");
    exit;
}

try {
    // 2. Mark Progress (UPSERT Logic)
    $check_stmt = $pdo->prepare("SELECT id FROM course_progress WHERE user_id = ? AND lesson_id = ?");
    $check_stmt->execute([$user_id, $lesson_id]);
    
    if ($check_stmt->fetch()) {
        $pdo->prepare("UPDATE course_progress SET is_completed = 1, completed_at = NOW() WHERE user_id = ? AND lesson_id = ?")
            ->execute([$user_id, $lesson_id]);
    } else {
        $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, is_completed, completed_at) VALUES (?, ?, 1, NOW())")
            ->execute([$user_id, $lesson_id]);
    }

    // --- NEW: CHECK IF COURSE IS 100% COMPLETE ---
    
    // Total lessons in course
    $total_stmt = $pdo->prepare("SELECT COUNT(l.id) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = ?");
    $total_stmt->execute([$course_id]);
    $total_lessons = (int)$total_stmt->fetchColumn();

    // Lessons completed by user
    $done_stmt = $pdo->prepare("SELECT COUNT(p.id) FROM course_progress p JOIN lessons l ON p.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE m.course_id = ? AND p.user_id = ? AND p.is_completed = 1");
    $done_stmt->execute([$course_id, $user_id]);
    $done_lessons = (int)$done_stmt->fetchColumn();

    /*
    if ($done_lessons >= $total_lessons) {
        // FLIP ENROLLMENT STATUS TO COMPLETED
        $update_enroll = $pdo->prepare("UPDATE enrollments SET status = 'completed', completed_at = NOW() WHERE user_id = ? AND course_id = ? AND status != 'completed'");
        $update_enroll->execute([$user_id, $course_id]);
    }
    */

    // 3. Find Next Lesson
    $current_info_stmt = $pdo->prepare("
        SELECT l.order_index as l_order, m.order_index as m_order 
        FROM lessons l 
        JOIN modules m ON l.module_id = m.id 
        WHERE l.id = ?
    ");
    $current_info_stmt->execute([$lesson_id]);
    $curr = $current_info_stmt->fetch();

    $next_lesson_stmt = $pdo->prepare("
        SELECT l.id FROM lessons l 
        JOIN modules m ON l.module_id = m.id 
        WHERE m.course_id = ? 
        AND (
            (m.order_index = ? AND l.order_index > ?) 
            OR 
            (m.order_index > ?) 
        )
        ORDER BY m.order_index ASC, l.order_index ASC 
        LIMIT 1
    ");
    $next_lesson_stmt->execute([$course_id, $curr['m_order'], $curr['l_order'], $curr['m_order']]);
    $next_lesson_id = $next_lesson_stmt->fetchColumn();

    // 4. Redirect
    if ($next_lesson_id) {
        header("Location: course-player.php?course_id=$course_id&lesson_id=$next_lesson_id&msg=completed");
    } else {
        header("Location: course-player.php?course_id=$course_id&lesson_id=$lesson_id&course_finished=1");
    }
    exit;

} catch (PDOException $e) {
    error_log("Progress Update DB Error: " . $e->getMessage());
    header("Location: course-player.php?course_id=$course_id&lesson_id=$lesson_id&status=error");
    exit;
}