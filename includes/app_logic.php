<?php
/**
 * ERM Institute - Application Logic (Simplified)
 * Handles registration for minimal Section A only
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // 1. Security Check (CSRF)
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security session expired. Please refresh the page.";
    }

    if (empty($errors)) {
        // === Extract Form Data ===
        $first_name      = trim($_POST['first_name'] ?? '');
        $last_name       = trim($_POST['last_name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $contact_number  = trim($_POST['contact_number'] ?? '');
        $location        = trim($_POST['location'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // === Basic Validation ===
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($contact_number) || empty($location)) {
            $errors[] = "All fields are required.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        // === Proceed if no errors ===
        if (empty($errors)) {
            try {
                // Check if email already exists
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                
                if ($check->fetch()) {
                    $errors[] = "An account with this email already exists.";
                } else {
                    $pdo->beginTransaction();

                    $pass_hash = password_hash($password, PASSWORD_BCRYPT);
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                    // Simplified INSERT - only essential fields
                    $sql = "INSERT INTO users (
                        username, 
                        email, 
                        password_hash, 
                        first_name, 
                        last_name, 
                        phone_number,
                        location,
                        otp_code, 
                        otp_expiry, 
                        role, 
                        verified
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 0
                    )";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $email,           // username (using email as username)
                        $email,
                        $pass_hash,
                        $first_name,
                        $last_name,
                        $contact_number,
                        $location,
                        $otp,
                        $otp_expiry
                    ]);

                    $pdo->commit();

                    // === Send Verification Email ===
                    try {
                        if (!defined('ACCESS_GRANTED')) {
                            define('ACCESS_GRANTED', true);
                        }
                        require_once ROOT_PATH . 'includes/mail.php';

                        $subject = "Verify Your ERM Institute Application";
                        $subtitle = "Registration Step: Email Verification";
                        
                        $body_content = "
                            <div style='font-family: sans-serif; color: #1e293b;'>
                                <p>Hello <strong>" . htmlspecialchars($first_name) . "</strong>,</p>
                                <p>Thank you for registering with ERM Institute.</p>
                                <p>To complete your registration, please use the 6-digit verification code below:</p>
                                
                                <div style='margin: 30px 0; text-align: center;'>
                                    <span style='background: #f1f5f9; color: #4f46e5; font-size: 32px; font-weight: 800; letter-spacing: 8px; padding: 15px 30px; border-radius: 12px; border: 2px dashed #cbd5e1;'>
                                        {$otp}
                                    </span>
                                </div>
                                
                                <p style='font-size: 14px; color: #64748b;'>This code will expire in 30 minutes.</p>
                                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                                <p style='font-size: 13px;'>If you didn't request this, please ignore this email.</p>
                            </div>
                        ";

                        $mail_result = send_edulux_email(
                            $email,
                            $first_name,
                            $subject,
                            $body_content,
                            $subtitle,
                            "Verify Now",
                            BASE_URL . "pages/auth/verify.php"
                        );

                        if (!$mail_result['success']) {
                            error_log("Mail delivery failed for $email: " . $mail_result['message']);
                        }

                    } catch (Exception $mailEx) {
                        error_log("Email System Error: " . $mailEx->getMessage());
                    }

                    // Redirect to verification page
                    $_SESSION['verify_email'] = $email;
                    header("Location: verify.php");
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Registration Error: " . $e->getMessage());
                $errors[] = "Internal system error. Please try again later.";
            }
        }
    }
}