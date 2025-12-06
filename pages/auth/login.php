<?php
require_once __DIR__ . '/../../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "dashboard");
    exit;
}

// Brute-force protection
$max_attempts = 5;
$lockout_time = 900;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

if ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['last_attempt_time']) < $lockout_time) {
    $remaining = ceil(($lockout_time - (time() - $_SESSION['last_attempt_time'])) / 60);
    die("<div class='text-center py-5 text-white' style='margin-top:120px;'><div class='alert alert-danger d-inline-block px-5 py-4 rounded-4'>Too many failed attempts. Try again in {$remaining} minute(s).</div></div>");
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    SELECT id, username, first_name, last_name, email, password_hash, role, approval_status, avatar 
                    FROM users 
                    WHERE email = ? AND verified = 1 
                    LIMIT 1
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['role'] === 'instructor' && $user['approval_status'] !== 'approved') {
                        $errors[] = "Your instructor account is pending approval. Please wait for admin review.";
                    } else {
                        // Reset login attempts
                        $_SESSION['login_attempts'] = 0;
                        session_regenerate_id(true);

                        // CRITICAL: Save name and avatar for header display
                        $_SESSION['user_id']      = $user['id'];
                        $_SESSION['username']     = $user['username'];
                        $_SESSION['first_name']   = $user['first_name'] ?? '';
                        $_SESSION['last_name']    = $user['last_name'] ?? '';
                        $_SESSION['email']        = $user['email'];
                        $_SESSION['role']         = $user['role'];
                        $_SESSION['user_avatar']  = !empty($user['avatar'])
                            ? BASE_URL . 'assets/uploads/' . $user['avatar']
                            : BASE_URL . 'assets/uploads/avatars/default.jpg';
                        $_SESSION['initiated']    = true;

                        // Redirect to correct dashboard
                        $dashboard = match ($user['role']) {
                            'admin'      => 'admin',
                            'instructor' => 'instructor',
                            default      => 'student'
                        };

                        header("Location: " . BASE_URL . "dashboard/$dashboard");
                        exit;
                    }
                } else {
                    $errors[] = "Invalid email or password.";
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $errors[] = "Server error. Please try again later.";
            }
        }
    }
}

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    :root { --gradient-premium: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }

    .login-wrapper {
        min-height: calc(100vh - 80px);
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        padding: 140px 20px 80px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .login-wrapper::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: repeating-conic-gradient(from 30deg at 50% 50%,
            rgba(99,102,241,0.08) 0deg, transparent 30deg,
            rgba(139,92,246,0.08) 60deg, transparent 90deg);
        animation: rotate 40s linear infinite;
    }

    @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .login-card {
        background: rgba(255,255,255,0.96);
        backdrop-filter: blur(32px);
        border: 1.5px solid rgba(255,255,255,0.7);
        border-radius: 32px;
        box-shadow: 0 30px 80px rgba(0,0,0,0.35);
        padding: 60px 70px;
        max-width: 520px;
        width: 100%;
        position: relative;
        z-index: 10;
        animation: float 8s ease-in-out infinite;
    }

    .login-card::before {
        content: ''; position: absolute; inset: -3px;
        background: var(--gradient-premium);
        border-radius: 35px; z-index: -1;
        opacity: 0.22; filter: blur(22px);
        animation: pulse 5s ease-in-out infinite alternate;
    }

    @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
    @keyframes pulse { from{opacity:0.18} to{opacity:0.28} }

    .logo-text {
        font-size: 4.2rem; font-weight: 900;
        background: var(--gradient-premium);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -1.5px;
    }

    .form-control {
        background: rgba(15,23,42,0.08) !important;
        border: 1.8px solid rgba(15,23,42,0.15);
        border-radius: 16px;
        padding: 1rem 3rem 1rem 1.3rem;
        font-size: 1.1rem;
        height: 58px;
    }

    .form-control:focus {
        background: white !important;
        border-color: #6366f1;
        box-shadow: 0 0 0 0.3rem rgba(99,102,241,0.22);
    }

    .btn-login {
        background: var(--gradient-premium);
        border: none;
        border-radius: 50px;
        padding: 18px;
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
    }

    .btn-login:hover {
        transform: translateY(-6px);
        box-shadow: 0 25px50px rgba(139,92,246,0.5);
    }

    /* Eye Toggle - Perfectly Visible */
    .password-wrapper {
        position: relative;
    }
    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #64748b;
        font-size: 1.3rem;
        z-index: 10;
        transition: color 0.3s;
    }
    .password-toggle:hover { color: #6366f1; }
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="text-center mb-5">
            <h1 class="logo-text">EduLux</h1>
            <p class="fs-4 fw-light text-dark opacity-75">Welcome back, elite learner</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-4 border-0 mb-4 p-4">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success rounded-4 mb-4">
                <?php echo e($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="mb-4">
                <input type="email" class="form-control" name="email" value="<?php echo e($email); ?>"
                       placeholder="Email Address" required autocomplete="email">
            </div>

            <div class="mb-4 password-wrapper">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Password" required autocomplete="current-password" required>
                <span class="password-toggle" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label text-muted" for="remember">Remember me</label>
                </div>
                <a href="<?php echo BASE_URL; ?>pages/auth/reset-password.php" class="fw-semibold text-primary">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Access Your Dashboard
            </button>

            <div class="text-center mt-4">
                <p class="text-muted mb-0">
                    New to EduLux?
                    <a href="<?php echo BASE_URL; ?>pages/auth/register.php" class="fw-bold text-primary text-decoration-underline">
                        Join the Elite
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
    // Password toggle - 100% working
    document.getElementById('togglePassword').addEventListener('click', function () {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>