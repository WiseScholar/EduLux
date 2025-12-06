<?php
require_once __DIR__ . '/../../includes/config.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($email) || empty($password) || empty($confirm)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = "No account found with that email.";
            } else {
                // Update password
                $new_hash = password_hash($password, PASSWORD_DEFAULT);

                $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ? LIMIT 1");
                $update->execute([$new_hash, $email]);

                $success = "Password reset successfully! You can now log in.";
            }

        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            $errors[] = "Server error. Try again later.";
        }
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .reset-wrapper {
        min-height: calc(100vh - 80px);
        background: linear-gradient(135deg, #0f172a, #1e293b);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
    }

    .reset-card {
        background: rgba(255,255,255,0.95);
        padding: 50px;
        max-width: 500px;
        border-radius: 30px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    }

    .form-control {
        border-radius: 14px;
        height: 55px;
    }

    .btn-reset {
        border-radius: 40px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 16px;
        font-size: 1.2rem;
        border: none;
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="text-center mb-5">
            <h1 class="logo-text">Reset Password</h1>
            <p class="fs-5 fw-light text-dark opacity-75">Enter your email and set a new password</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-4 mb-4 p-4">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-4 mb-4 p-4 text-center">
                <?php echo htmlspecialchars($success); ?>
                <br><br>
                <a href="<?php echo BASE_URL; ?>pages/auth/login.php" class="btn btn-primary mt-2">Go to Login</a>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <input type="email" class="form-control" name="email"
                       placeholder="Email Address" required>
            </div>

            <div class="mb-4">
                <input type="password" class="form-control" name="password"
                       placeholder="New Password" required>
            </div>

            <div class="mb-4">
                <input type="password" class="form-control" name="confirm_password"
                       placeholder="Confirm New Password" required>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Reset Password
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="<?php echo BASE_URL; ?>pages/auth/login.php" class="fw-bold text-primary">
                Back to Login
            </a>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>