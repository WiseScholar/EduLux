<?php
require_once __DIR__ . '/../../includes/config.php';

define('ACCESS_GRANTED', true);
require_once ROOT_PATH . 'includes/mail.php';

$email = $_SESSION['verify_email'] ?? '';

if (empty($email)) {
    header("Location: login.php");
    exit;
}

$current_time = time();
if (isset($_SESSION['last_otp_resend']) && ($current_time - $_SESSION['last_otp_resend']) < 60) {
    $wait = 60 - ($current_time - $_SESSION['last_otp_resend']);
    $_SESSION['otp_error'] = "Please wait {$wait} seconds before requesting a new code.";
    header("Location: verify.php");
    exit;
}

try {
    $new_otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ? AND verified = 0");
    $stmt->execute([$new_otp, $expiry, $email]);

    if ($stmt->rowCount() > 0) {
        $user_stmt = $pdo->prepare("SELECT first_name FROM users WHERE email = ?");
        $user_stmt->execute([$email]);
        $first_name = $user_stmt->fetchColumn() ?: 'Elite Learner';

        $subject = "Your New Verification Code";
        $subtitle = "Security Update";
        $body = "
            <p>Hello <strong>" . htmlspecialchars($first_name) . "</strong>,</p>
            <p>We received a request for a new verification code. Use the code below to activate your account:</p>
            <div style='background:#f8fafc; padding:30px; border-radius:16px; text-align:center; margin:30px 0;'>
                <span style='font-size:42px; font-weight:900; letter-spacing:12px; color:#6366f1;'>" . $new_otp . "</span>
            </div>
            <p style='font-size:14px; color:#64748b;'>Note: This code expires in 30 minutes.</p>
        ";

        send_edulux_email($email, $first_name, $subject, $body, $subtitle, "Verify Now", BASE_URL . "pages/auth/verify.php?email=" . urlencode($email));

        $_SESSION['last_otp_resend'] = $current_time;
        $_SESSION['otp_success'] = "A fresh verification code has been sent to your email.";
    } else {
        $_SESSION['otp_error'] = "Account not found or already verified.";
    }

} catch (Exception $e) {
    error_log("Resend OTP Error: " . $e->getMessage());
    $_SESSION['otp_error'] = "A server error occurred. Please try again later.";
}

header("Location: verify.php");
exit;