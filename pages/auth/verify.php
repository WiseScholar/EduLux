<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/mail.php';

$email = $_GET['email'] ?? $_SESSION['verify_email'] ?? '';

if (empty($email)) {
    header("Location: login.php");
    exit;
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $otp_array = $_POST['otp'] ?? [];
        $otp = implode('', array_filter($otp_array, 'is_numeric'));

        if (strlen($otp) !== 6) {
            $errors[] = "Please enter the full 6-digit code.";
        } else {
            $stmt = $pdo->prepare("SELECT id, first_name, role, otp_expiry FROM users WHERE email = ? AND otp_code = ? AND verified = 0 LIMIT 1");
            $stmt->execute([$email, $otp]);
            $user = $stmt->fetch();

            if ($user) {
                if (strtotime($user['otp_expiry']) >= time()) {
                    $update = $pdo->prepare("UPDATE users SET verified = 1, otp_code = NULL, otp_expiry = NULL WHERE id = ?");
                    $update->execute([$user['id']]);

                    if ($user['role'] === 'instructor') {
                        $subject = "Application Received - ERM Institute";
                        $subtitle = "Your professional profile is now under review";
                        $body = "
                            <p>Hello <strong>" . htmlspecialchars($user['first_name']) . "</strong>,</p>
                            <p>Your email has been verified. Your instructor application has been officially submitted to our academic board.</p>
                            <div style='background:#f8fafc; padding:20px; border-radius:12px; border-left:4px solid #002d72; margin:25px 0;'>
                                <strong>Status:</strong> Under Administrative Review<br>
                                <strong>Estimated Time:</strong> 24 - 48 Business Hours
                            </div>
                        ";
                        send_edulux_email($email, $user['first_name'], $subject, $body, $subtitle);
                        $_SESSION['success_message'] = "Email verified! Your application is now under review.";
                    } else {
                        $_SESSION['success_message'] = "Account verified successfully! Welcome to the ERM Institute.";
                    }

                    unset($_SESSION['verify_email']);
                    header("Location: login.php");
                    exit;
                } else {
                    $errors[] = "The security code has expired.";
                }
            } else {
                $errors[] = "Invalid verification code.";
            }
        }
    }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    /* ERM Institutional Identity */
    .verify-wrapper {
        min-height: 100vh;
        background: linear-gradient(135deg, #002d72 0%, #0056b3 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .verify-card {
        background: #ffffff;
        padding: 60px 45px;
        border-radius: 24px;
        max-width: 500px;
        width: 100%;
        text-align: center;
        box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    .otp-field {
        width: 50px;
        height: 65px;
        text-align: center;
        font-size: 1.8rem;
        font-weight: 800;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        background: #fcfdfe;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        color: var(--erm-navy);
    }

    .otp-field:focus {
        border-color: var(--erm-blue);
        background: white;
        outline: none;
        box-shadow: 0 0 0 5px rgba(0, 86, 179, 0.1);
        transform: translateY(-2px);
    }

    .btn-verify {
        background: var(--erm-navy);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 50px;
        width: 100%;
        font-weight: 800;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
    }

    .btn-verify:hover {
        background: var(--erm-blue);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="verify-wrapper">
    <div class="verify-card animate__animated animate__fadeInUp">
        <div class="mb-5">
            <img src="<?= BASE_URL ?>assets/images/logos/782334.png" height="45" class="mb-4">
            <h3 class="fw-bold color-navy">Account Activation</h3>
            <p class="text-muted small">A 6-digit secure code has been dispatched to</p>
            <div class="bg-light p-2 rounded-pill d-inline-block px-4">
                <span class="fw-bold text-navy small"><?= htmlspecialchars($email) ?></span>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 small rounded-4 py-3 mb-4 animate__animated animate__shakeX">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $errors[0] ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">

            <div class="d-flex gap-2 justify-content-center mb-5">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" name="otp[]" class="otp-field" maxlength="1" autocomplete="off" inputmode="numeric" required <?= $i === 0 ? 'autofocus' : '' ?>>
                <?php endfor; ?>
            </div>

            <button type="submit" class="btn btn-verify mb-4 shadow-lg">
                <i class="fas fa-shield-check me-2"></i> Verify Account
            </button>
        </form>

        <div class="border-top pt-4">
            <p class="small text-muted mb-3">
                Expecting a code? Check your spam folder or
                <a href="resend-otp.php" class="text-primary fw-bold text-decoration-none">Resend OTP</a>
            </p>
            <a href="register.php" class="extra-small text-secondary text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Use a different email address
            </a>
        </div>
    </div>
</div>

<script>
    const inputs = document.querySelectorAll('.otp-field');
    const form = document.getElementById('otpForm');

    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length > 1) e.target.value = e.target.value.slice(0, 1);
            if (e.target.value !== "" && index < inputs.length - 1) inputs[index + 1].focus();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === "" && index > 0) inputs[index - 1].focus();
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').trim().slice(0, 6).split('');
            pasteData.forEach((char, i) => {
                if (inputs[i] && !isNaN(char)) inputs[i].value = char;
            });
            if (pasteData.length === 6) form.submit();
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>