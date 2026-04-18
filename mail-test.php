<?php
// 1. Setup Environment
define('ACCESS_GRANTED', true);
require_once __DIR__ . '/includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/mail.php';

// Force show all errors to the screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>EduLux Institutional Mail System: Debug Mode</h2>";
echo "<hr>";

// 2. Prepare Dummy Data for the Template
$student_name = "Debug Tester";
$student_email = "eben23713@gmail.com"; // <-- CHANGE THIS TO YOUR EMAIL
$quiz_title = "Mail Test";
$course_title = "Infrastructure 101";
$score = 95;
$points = 19;
$total_points = 20;
$status_label = "Passed";

echo "Attempting to generate template... ";
try {
    ob_start();
    // Path check: make sure this path matches your file location
    include ROOT_PATH . 'templates/emails/quiz-result.php';
    $email_body = ob_get_clean();
    echo "<span style='color:green'>SUCCESS</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
}

echo "Initializing SMTP Handshake...<br><br>";

// 3. Inject SMTP Debugging directly into the PHPMailer instance
// We are going to "hack" into the send function for a moment to see the logs
function send_debug_email($to, $name, $subject, $body) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // --- Visible Debugging ---
        $mail->SMTPDebug = 3; // Level 3: Connection + Commands + Data
        $mail->Debugoutput = function($str, $level) {
            echo "<pre style='background:#000; color:#0f0; padding:10px; border-radius:5px;'>DEBUG: $str</pre>";
        };

        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);

        $mail->setFrom($_ENV['SMTP_USERNAME'], 'ERM Institute');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        echo "<h3 style='color:green'>SUCCESS: Mail sent to $to! Check your inbox.</h3>";
    } catch (Exception $e) {
        echo "<h3 style='color:red'>CRITICAL FAILURE: {$mail->ErrorInfo}</h3>";
    }
}

// 4. Execute
send_debug_email($student_email, $student_name, "Test: " . $quiz_title, $email_body);