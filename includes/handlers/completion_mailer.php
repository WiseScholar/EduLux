<?php
// includes/handlers/completion_mailer.php
if (!defined('ACCESS_GRANTED')) define('ACCESS_GRANTED', true);
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . 'includes/mail.php';

function trigger_completion_email($student_id, $course_id, $cert_code) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT u.email, u.first_name, c.title 
        FROM users u, courses c 
        WHERE u.id = ? AND c.id = ?
    ");
    $stmt->execute([$student_id, $course_id]);
    $info = $stmt->fetch();

    if ($info) {
        $subject = "Mastery Achieved: " . $info['title'];
        $subtitle = "You've earned your Edulux Elite Certification";
        $body = "
            <p>Congratulations <strong>" . htmlspecialchars($info['first_name']) . "</strong>,</p>
            <p>Today, you've joined the elite ranks of learners who have mastered <strong>" . htmlspecialchars($info['title']) . "</strong>.</p>
            <p>Your official digital certificate is ready and has been added to your profile achievements.</p>
            <div style='background:#f8fafc; padding:20px; border-radius:12px; border-left:4px solid #6366f1; margin:25px 0;'>
                <strong>Certification ID:</strong> " . $cert_code . "<br>
                <strong>Status:</strong> Verified & Active
            </div>
            <p>Click the button below to view and download your certificate.</p>
        ";

        $view_url = BASE_URL . "dashboard/student/achievements.php?code=" . $cert_code;

        return send_edulux_email(
            $info['email'], 
            $info['first_name'], 
            $subject, 
            $body, 
            $subtitle, 
            "View My Certificate", 
            $view_url
        );
    }
    return false;
}