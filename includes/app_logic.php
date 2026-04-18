<?php
/**
 * ERM Institute - Application Logic
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security session expired. Please refresh the page.";
    }

    if (empty($errors)) {
        $first_name       = trim($_POST['first_name'] ?? '');
        $last_name        = trim($_POST['last_name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $contact_number   = trim($_POST['contact_number'] ?? '');
        $location         = trim($_POST['location'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $errors[] = "Required fields are missing.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        if (empty($errors)) {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                
                if ($check->fetch()) {
                    $errors[] = "An account with this email already exists.";
                } else {
                    $pdo->beginTransaction();

                    $pass_hash = password_hash($password, PASSWORD_BCRYPT);

                    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, phone_number, location, role, verified) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'student', 1)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $email, $email, $pass_hash, $first_name, $last_name, $contact_number, $location
                    ]);

                    $user_id = $pdo->lastInsertId();
                    $pdo->commit();

                    // === Populate Session for the whole app ===
                    $_SESSION['user_id']      = $user_id;
                    $_SESSION['email']        = $email;
                    $_SESSION['first_name']   = $first_name;
                    $_SESSION['last_name']    = $last_name; // Added for completeness
                    $_SESSION['role']         = 'student';
                    $_SESSION['is_logged_in'] = true;

                    $_SESSION['success'] = "Welcome to ERM Institute, " . htmlspecialchars($first_name) . "!";

                    header("Location: " . BASE_URL . "dashboard/student/");
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Registration Error: " . $e->getMessage());
                $errors[] = "Internal system error.";
            }
        }
    }
}