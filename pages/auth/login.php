<?php
require_once __DIR__ . '/../../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "dashboard");
    exit;
}

// Security: Basic Brute Force Protection Logic
$max_attempts = 5;
$lockout_time = 900;
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

if ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['last_attempt_time']) < $lockout_time) {
    $remaining = ceil(($lockout_time - (time() - $_SESSION['last_attempt_time'])) / 60);
    die("<div class='text-center py-5' style='margin-top:100px;'><div class='alert alert-danger d-inline-block px-5'>Too many failed attempts. Try again in {$remaining} minutes.</div></div>");
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security token mismatch. Please refresh.";
    } else {
        $email  = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Check Verification
                    if ((int)$user['verified'] !== 1) {
                        $_SESSION['verify_email'] = $user['email'];
                        header("Location: verify.php");
                        exit;
                    }

                    // Reset attempts on success
                    $_SESSION['login_attempts'] = 0;
                    session_regenerate_id(true);

                    $_SESSION['user_id']     = $user['id'];
                    $_SESSION['first_name']  = $user['first_name'];
                    $_SESSION['role']        = $user['role'];
                    $_SESSION['user_avatar'] = BASE_URL . 'assets/uploads/avatars/' . ($user['avatar'] ?? 'default.jpg');

                    $dashboard = match ($user['role']) {
                        'admin'      => 'admin',
                        'instructor' => 'instructor',
                        default      => 'student'
                    };

                    header("Location: " . BASE_URL . "dashboard/$dashboard");
                    exit;
                } else {
                    $errors[] = "Invalid email or password.";
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $errors[] = "A system error occurred.";
            }
        }
    }
}

// Minimal header check - No footer include later
require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    body { background-color: #f8fafc; } /* Light corporate gray background */

    .acams-login-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .acams-login-card {
        width: 100%;
        max-width: 480px;
        background: #ffffff;
        padding: 40px;
        border-radius: 4px; /* Professional sharp edges */
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border-top: 6px solid #002d72; /* ACAMS primary navy */
    }

    .form-label {
        font-weight: 700;
        font-size: 0.85rem;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        border-radius: 2px;
        border: 1px solid #cbd5e1;
        padding: 12px 15px;
        font-size: 1rem;
    }

    .form-control:focus {
        border-color: #002d72;
        box-shadow: none;
    }

    .btn-acams {
        background: #002d72;
        color: #fff;
        border: none;
        padding: 14px;
        font-weight: 700;
        border-radius: 2px;
        width: 100%;
        letter-spacing: 1px;
        transition: background 0.3s;
    }

    .btn-acams:hover {
        background: #001a44;
        color: #fff;
    }

    .forgot-link {
        font-size: 0.75rem;
        text-decoration: none;
        color: #64748b;
        font-weight: 700;
    }

    .forgot-link:hover { color: #002d72; }

    .divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 30px 0;
        color: #cbd5e1;
    }
    .divider::before, .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #e2e8f0;
    }
    .divider span { padding: 0 15px; font-size: 0.85rem; color: #94a3b8; font-weight: 600; }

    .register-link {
        font-weight: 700;
        color: #002d72;
        text-decoration: none;
    }
</style>

<div class="acams-login-wrapper">
    <div class="acams-login-card">
        <h2 class="fw-bold mb-1">Sign In</h2>
        <p class="text-muted small mb-4">Access your EduLux professional account</p>

        <?php if($errors): ?>
            <div class="alert alert-danger py-2 small border-0 rounded-1">
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div class="mb-4">
                <label class="form-label">Username (Primary Email)</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="mb-2">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="text-end mb-4">
                <a href="forgot-password.php" class="forgot-link">FORGOT PASSWORD?</a>
            </div>

            <button type="submit" class="btn-acams">SIGN IN</button>

            <div class="divider"><span>OR</span></div>

            <div class="text-center">
                <p class="small text-muted mb-1">New to EduLux?</p>
                <a href="register.php" class="register-link">Create account for free</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>