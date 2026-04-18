<?php
define('ACCESS_GRANTED', true);
require_once __DIR__ . '/../../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// Load PHPMailer manually since we are using the direct implementation
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once ROOT_PATH . 'vendor/autoload.php';

// Asynchronous settings: continue even if student closes the browser
ignore_user_abort(true);
set_time_limit(120); 

function mail_worker_log($message) {
    $log_file = ROOT_PATH . 'quiz_debug.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] [WORKER] $message" . PHP_EOL, FILE_APPEND);
}

$submission_id = (int)($_POST['submission_id'] ?? 0);
if (!$submission_id) exit;

try {
    // 1. Fetch Complete Submission Data
    $stmt = $pdo->prepare("
        SELECT s.score, s.assessment_id, a.title as quiz_title, a.course_id, 
               c.title as course_title, u.email, u.first_name, u.last_name
        FROM assessment_submissions s
        JOIN assessments a ON s.assessment_id = a.id
        JOIN courses c ON a.course_id = c.id
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$submission_id]);
    $data = $stmt->fetch();

    if (!$data || empty($data['email'])) exit;

    // 2. Prepare Template Variables
    $q_stmt = $pdo->prepare("SELECT SUM(points) FROM quiz_questions WHERE assessment_id = ?");
    $q_stmt->execute([$data['assessment_id']]);
    $total_points = (int)$q_stmt->fetchColumn() ?: 100;
    
    $score         = round($data['score']);
    $points        = round(($score / 100) * $total_points);
    $quiz_title    = $data['quiz_title'];
    $course_title  = $data['course_title'];
    $course_id     = $data['course_id'];
    $student_name  = trim($data['first_name'] . ' ' . $data['last_name']);
    $student_email = $data['email'];
    $status        = ($score >= 70) ? 'Passed' : 'Completed';

    // 3. Render the Email Body
    ob_start();
    $template_path = ROOT_PATH . 'templates/emails/quiz-result.php';
    if (file_exists($template_path)) {
        include $template_path;
        $email_body = ob_get_clean();
    } else {
        ob_end_clean();
        $email_body = "Hello $student_name, you scored $score% on the $quiz_title.";
    }

    // 4. Direct SMTP Dispatch
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USERNAME'];
    $mail->Password   = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)$_ENV['SMTP_PORT'];

    $mail->setFrom($_ENV['SMTP_USERNAME'], 'EduLux Learning');
    $mail->addReplyTo('support@edulux.com', 'EduLux Support');
    $mail->addAddress($student_email, $student_name);

    $mail->isHTML(true);
    $mail->Subject = "Performance Summary - " . $quiz_title;
    $mail->Body    = $email_body;
    $mail->AltBody = strip_tags($email_body);

    // Optional: Logo Embedding
    $logo_path = ROOT_PATH . 'assets/images/erm.jpg';
    $logo_cid = 'edulux_logo_v1';

    if (file_exists($logo_path)) {
        $mail->addEmbeddedImage($logo_path, $logo_cid, 'logo.jpg');
    }

    $mail->send();
    mail_worker_log("SUCCESS: Grade Report sent to $student_email for Submission $submission_id");

} catch (Exception $e) {
    mail_worker_log("CRITICAL ERROR for Submission $submission_id: " . $e->getMessage());
}