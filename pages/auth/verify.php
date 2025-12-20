<?php
require_once __DIR__ . '/../../includes/config.php';

$email = $_GET['email'] ?? $_SESSION['verify_email'] ?? '';

if (empty($email)) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $otp_array = $_POST['otp'] ?? [];
        $otp = implode('', array_filter($otp_array, 'is_numeric')); 

        if (strlen($otp) !== 6) {
            $errors[] = "Please enter the full 6-digit code.";
        } else {
            $stmt = $pdo->prepare("SELECT id, otp_expiry FROM users WHERE email = ? AND otp_code = ? AND verified = 0 LIMIT 1");
            $stmt->execute([$email, $otp]);
            $user = $stmt->fetch();

            if ($user) {
                if (strtotime($user['otp_expiry']) >= time()) {
                    $update = $pdo->prepare("UPDATE users SET verified = 1, otp_code = NULL, otp_expiry = NULL WHERE id = ?");
                    $update->execute([$user['id']]);
                    
                    $_SESSION['success_message'] = "Account verified successfully! Welcome to the elite tier.";
                    unset($_SESSION['verify_email']);
                    header("Location: login.php");
                    exit;
                } else {
                    $errors[] = "The code has expired. Please request a new one.";
                }
            } else {
                $errors[] = "Invalid verification code. Please check and try again.";
            }
        }
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .verify-wrapper { min-height: 100vh; background: #0f172a; display: flex; align-items: center; justify-content: center; padding: 20px; position: relative; overflow: hidden; }
    .verify-wrapper::before { content: ''; position: absolute; width: 200%; height: 200%; background: repeating-conic-gradient(from 30deg at 50% 50%, rgba(99, 102, 241, 0.05) 0deg, transparent 30deg); animation: rotate 60s linear infinite; }
    @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .verify-card { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); padding: 60px 50px; border-radius: 32px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 40px 100px rgba(0,0,0,0.5); position: relative; z-index: 10; border: 1px solid rgba(255,255,255,0.3); }
    .otp-input-group { display: flex; gap: 12px; justify-content: center; margin: 35px 0; }
    .otp-field { width: 52px; height: 65px; text-align: center; font-size: 1.8rem; font-weight: 800; border: 2px solid #e2e8f0; border-radius: 16px; background: #f8fafc; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); color: #1e293b; }
    .otp-field:focus { border-color: #6366f1; background: white; outline: none; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15); transform: translateY(-2px); }
    .btn-verify { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; padding: 18px; border-radius: 50px; width: 100%; font-weight: 700; font-size: 1.1rem; box-shadow: 0 15px 30px rgba(99, 102, 241, 0.3); transition: all 0.3s; }
    .btn-verify:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(99, 102, 241, 0.4); }
</style>

<div class="verify-wrapper">
    <div class="verify-card">
        <div class="mb-4">
            <i class="fas fa-shield-check text-primary fa-3x mb-3"></i>
            <h2 class="fw-bold text-dark">Identity Verification</h2>
            <p class="text-muted">An elite access code was sent to<br><span class="text-dark fw-bold"><?= htmlspecialchars($email) ?></span></p>
        </div>

        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger border-0 small rounded-4"><?= $errors[0] ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['otp_error'])): ?>
            <div class="alert alert-warning border-0 small rounded-4"><?= $_SESSION['otp_error']; unset($_SESSION['otp_error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['otp_success'])): ?>
            <div class="alert alert-success border-0 small rounded-4 text-success"><?= $_SESSION['otp_success']; unset($_SESSION['otp_success']); ?></div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
            
            <div class="otp-input-group">
                <?php for($i=0; $i<6; $i++): ?>
                    <input type="text" name="otp[]" class="otp-field" maxlength="1" autocomplete="off" inputmode="numeric" required <?= $i === 0 ? 'autofocus' : '' ?>>
                <?php endfor; ?>
            </div>
            
            <button type="submit" class="btn btn-verify mb-4">
                Activate Elite Account
            </button>
        </form>
        
        <p class="small text-muted">
            Didn't receive the code? 
            <a href="resend-otp.php" class="text-primary fw-bold text-decoration-none">Resend Code</a>
        </p>

        <a href="register.php" class="small text-secondary text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Use a different email</a>
    </div>
</div>

<script>
    const inputs = document.querySelectorAll('.otp-field');
    const form = document.getElementById('otpForm');

    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length > 1) {
                e.target.value = e.target.value.slice(0, 1);
            }
            if (e.target.value !== "" && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === "" && index > 0) {
                inputs[index - 1].focus();
            }
            if (e.key === 'ArrowLeft' && index > 0) inputs[index - 1].focus();
            if (e.key === 'ArrowRight' && index < inputs.length - 1) inputs[index + 1].focus();
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').slice(0, 6).split('');
            pasteData.forEach((char, i) => {
                if (inputs[i] && !isNaN(char)) {
                    inputs[i].value = char;
                }
            });
            if (pasteData.length === 6) {
                form.submit();
            } else {
                inputs[pasteData.length]?.focus();
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>