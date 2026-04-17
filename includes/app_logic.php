<?php
/**
 * ERM Institute - Application Logic (Simplified - No Verification)
 * Handles minimal registration and auto-login
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

                    // Simplified INSERT - No OTP, auto-verified
                    $sql = "INSERT INTO users (
                        username, 
                        email, 
                        password_hash, 
                        first_name, 
                        last_name, 
                        phone_number,
                        location,
                        role, 
                        verified
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, 'student', 1
                    )";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $email,           // username (using email)
                        $email,
                        $pass_hash,
                        $first_name,
                        $last_name,
                        $contact_number,
                        $location
                    ]);

                    $user_id = $pdo->lastInsertId();   // Get the newly created user ID

                    $pdo->commit();

                    // === Auto Login the user ===
                    $_SESSION['user_id']      = $user_id;
                    $_SESSION['email']        = $email;
                    $_SESSION['first_name']   = $first_name;
                    $_SESSION['role']         = 'student';
                    $_SESSION['is_logged_in'] = true;

                    // Success message
                    $_SESSION['success'] = "Account created successfully! Welcome, " . htmlspecialchars($first_name) . ".";

                    // Redirect to dashboard (CHANGE THIS PATH if your dashboard is elsewhere)
                    header("Location: " . BASE_URL . "dashboard.php");
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