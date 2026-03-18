<?php
/**
 * ERM Institute - Application Logic (ERMI 001)
 * Handles multi-step validation, security, and database insertion.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // 1. Security Check (CSRF)
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security session expired. Please refresh the page.";
    }

    if (empty($errors)) {
        // --- SECTION A: PERSONAL INFORMATION ---
        $first_name     = trim($_POST['first_name'] ?? '');
        $last_name      = trim($_POST['last_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $gender         = $_POST['gender'] ?? '';
        $nationality    = trim($_POST['nationality'] ?? '');
        $contact_num    = trim($_POST['contact_number'] ?? '');
        $postal_address = trim($_POST['postal_address'] ?? '');
        $gps_address    = trim($_POST['gps_address'] ?? '');
        $whatsapp       = trim($_POST['whatsapp_number'] ?? '');
        $social_media   = trim($_POST['social_media'] ?? '');

        // --- SECTION B: PROFESSIONAL DETAILS ---
        $occupation       = trim($_POST['occupation'] ?? '');
        $pro_background   = trim($_POST['professional_background'] ?? '');
        $job_title        = trim($_POST['job_title'] ?? '');
        $organization     = trim($_POST['organization'] ?? '');
        $department       = trim($_POST['department'] ?? '');
        $years_experience = intval($_POST['years_experience'] ?? 0);
        $memberships      = trim($_POST['professional_memberships'] ?? '');

        // --- SECTION C & D: PROGRAM & STUDY ---
        $program_level = $_POST['program_level'] ?? '';
        $study_mode    = $_POST['study_mode'] ?? '';
        $residence     = $_POST['country_of_residence'] ?? '';

        // --- SECTION E: PAYMENT ---
        $payment_method = $_POST['payment_method'] ?? '';
        $trans_ref      = trim($_POST['transaction_reference'] ?? '');

        // --- SECTION G: EMERGENCY CONTACT ---
        $em_name    = trim($_POST['emergency_name'] ?? '');
        $em_rel     = trim($_POST['emergency_relationship'] ?? '');
        $em_phone   = trim($_POST['emergency_phone'] ?? '');
        $em_email   = trim($_POST['emergency_email'] ?? '');
        $em_address = trim($_POST['emergency_address'] ?? '');

        // --- SECTION H: SPONSORSHIP ---
        $sponsorship_type = $_POST['sponsorship_type'] ?? 'Self-Sponsored';
        $sponsor_org      = trim($_POST['sponsor_organization'] ?? '');
        $sponsor_contact  = trim($_POST['sponsor_contact_person'] ?? '');
        $sponsor_email    = trim($_POST['sponsor_email'] ?? '');

        // --- ACCOUNT SECURITY ---
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // 2. Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($program_level)) {
            $errors[] = "Critical fields (First Name, Last Name, Email, Password, Program Level) are required.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid business email format.";
        }

        if ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        }

        // 3. Database Operation
        if (empty($errors)) {
            try {
                // Check if user exists
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                
                if ($check->fetch()) {
                    $errors[] = "An application with this email already exists.";
                } else {
                    $pdo->beginTransaction();

                    $pass_hash = password_hash($password, PASSWORD_BCRYPT);
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                    $sql = "INSERT INTO users (
                        username, email, password_hash, first_name, last_name, gender, nationality, 
                        phone_number, postal_address, gps_address, whatsapp_number, social_media,
                        occupation, professional_background, current_position, company, department, 
                        years_experience, professional_memberships, program_level, study_mode, 
                        country_of_residence, payment_method, transaction_ref,
                        emergency_contact_name, emergency_contact_rel, emergency_contact_phone,
                        sponsorship_type, sponsoring_org_name, otp_code, otp_expiry, role, verified
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 0
                    )";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $email, $email, $pass_hash, $first_name, $last_name, $gender, $nationality,
                        $contact_num, $postal_address, $gps_address, $whatsapp, $social_media,
                        $occupation, $pro_background, $job_title, $organization, $department,
                        $years_experience, $memberships, $program_level, $study_mode,
                        $residence, $payment_method, $trans_ref,
                        $em_name, $em_rel, $em_phone,
                        $sponsorship_type, $sponsor_org, $otp, $otp_expiry
                    ]);

                    $pdo->commit();

                    // --- TRIGGER VERIFICATION EMAIL ---
                    try {
                        // 1. Grant access to mail.php and include it
                        if (!defined('ACCESS_GRANTED')) {
                            define('ACCESS_GRANTED', true);
                        }
                        require_once ROOT_PATH . 'includes/mail.php';

                        // 2. Prepare Email Content
                        $subject = "Verify Your ERM Institute Application";
                        $subtitle = "Registration Step: Email Verification";
                        
                        $body_content = "
                            <div style='font-family: sans-serif; color: #1e293b;'>
                                <p>Hello <strong>" . htmlspecialchars($first_name) . "</strong>,</p>
                                <p>Thank you for applying for the <strong>" . htmlspecialchars($program_level) . "</strong> program at ERM Institute.</p>
                                <p>To proceed with your application, please use the 6-digit verification code below:</p>
                                
                                <div style='margin: 30px 0; text-align: center;'>
                                    <span style='background: #f1f5f9; color: #4f46e5; font-size: 32px; font-weight: 800; letter-spacing: 8px; padding: 15px 30px; border-radius: 12px; border: 2px dashed #cbd5e1;'>
                                        {$otp}
                                    </span>
                                </div>
                                
                                <p style='font-size: 14px; color: #64748b;'>This code will expire in 30 minutes for security purposes.</p>
                                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                                <p style='font-size: 13px;'>If you didn't create an account, you can safely ignore this email.</p>
                            </div>
                        ";

                        // 3. Send Email
                        $mail_result = send_edulux_email(
                            $email,
                            $first_name,
                            $subject,
                            $body_content,
                            $subtitle,
                            "Complete Verification",
                            BASE_URL . "pages/auth/verify.php"
                        );

                        // Optional: Log if email fails but keep the user moving
                        if (!$mail_result['success']) {
                            error_log("Mail delivery failed for $email: " . $mail_result['message']);
                        }

                    } catch (Exception $mailEx) {
                        // We log the error but don't stop the redirect because the user is already in the DB
                        error_log("Email System Error: " . $mailEx->getMessage());
                    }

                    // Set session and redirect
                    $_SESSION['verify_email'] = $email;
                    header("Location: verify.php");
                    exit;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Registration Error: " . $e->getMessage());
                $errors[] = "Internal system error. Our technical team has been notified.";
            }
        }
    }
}