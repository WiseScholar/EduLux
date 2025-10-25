<?php
// Debug: Check if config.php is included
$config_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
    echo "<!-- Debug: config.php included successfully -->";
} else {
    die("Error: config.php not found at $config_path");
}

// Start session
session_start();

// Initialize variables for form handling
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email exists in users table
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, approval_status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Check approval status for instructors
                if ($user['role'] === 'instructor' && $user['approval_status'] !== 'approved') {
                    $errors[] = "Your instructor account is pending approval. Please contact the administrator.";
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect based on role
                    if ($user['role'] === 'student') {
                        header("Location: " . BASE_URL . "pages/dashboard/student.php");
                        exit;
                    } elseif ($user['role'] === 'instructor') {
                        header("Location: " . BASE_URL . "pages/dashboard/instructor.php");
                        exit;
                    }
                }
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No account found with that email.";
        }
        $stmt->close();
    }
}
?>

<?php
// Debug: Check if header.php is included
$header_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/header.php';
if (file_exists($header_path)) {
    require_once $header_path;
    echo "<!-- Debug: header.php included successfully -->";
} else {
    die("Error: header.php not found at $header_path");
}
?>

<!-- Login Section -->
<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="glass-card p-5">
                    <h2 class="display-4 fw-bold mb-4 text-center">Login to <span class="gradient-text">EduLux</span></h2>
                    <p class="text-muted text-center mb-5">Access your account as a Student or Instructor</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>pages/auth/register.php" class="text-muted">Don't have an account? Register here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Debug: Check if footer.php is included
$footer_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/footer.php';
if (file_exists($footer_path)) {
    require_once $footer_path;
    echo "<!-- Debug: footer.php included successfully -->";
} else {
    die("Error: footer.php not found at $footer_path");
}
?>