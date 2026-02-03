<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/dropdowns.php'; // Load the new lists
if (!defined('ACCESS_GRANTED')) {
  define('ACCESS_GRANTED', true);
}
require_once ROOT_PATH . 'includes/mail.php';

if (isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "dashboard");
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $errors[] = "Security mismatch. Please refresh.";
  } else {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $country    = $_POST['country'] ?? '';
    $industry   = $_POST['industry'] ?? '';
    $company    = trim($_POST['company'] ?? '');
    $phone_code = $_POST['phone_code'] ?? '';
    $phone_num  = trim($_POST['phone_number'] ?? '');

    // ACAMS Standard Password Regex
    $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($country) || empty($industry) || empty($phone_num)) {
      $errors[] = "All fields marked with * are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Please provide a valid business email.";
    } elseif ($password !== $confirm) {
      $errors[] = "Passwords do not match.";
    } elseif (!preg_match($password_regex, $password)) {
      $errors[] = "Password does not meet the elite security requirements.";
    } else {
      try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
          $errors[] = "This email is already registered.";
        } else {
          $password_hash = password_hash($password, PASSWORD_BCRYPT);
          $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
          $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

          $sql = "INSERT INTO users (username, email, first_name, last_name, country, industry, company, phone_code, phone_number, password_hash, otp_code, otp_expiry, role, verified) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 0)";
          $stmt = $pdo->prepare($sql);
          $stmt->execute([$email, $email, $first_name, $last_name, $country, $industry, $company, $phone_code, $phone_num, $password_hash, $otp, $expiry]);

          $_SESSION['verify_email'] = $email;
          // Trigger mail...
          header("Location: verify.php");
          exit;
        }
      } catch (Exception $e) {
        error_log($e->getMessage());
        $errors[] = "A system error occurred. Please try again.";
      }
    }
  }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
  .acams-card {
    max-width: 700px;
    margin: 140px auto 60px;
    background: #ffffff;
    padding: 45px;
    border-radius: 4px;
    /* ACAMS uses sharper, professional corners */
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    border-top: 5px solid #002d72;
  }

  .form-label {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 5px;
  }

  .form-control,
  .form-select {
    border-radius: 2px;
    border: 1px solid #cbd5e1;
    padding: 10px;
  }

  .form-control:focus {
    border-color: #002d72;
    box-shadow: none;
  }

  .pwd-requirements {
    background: #f8fafc;
    padding: 15px;
    border-radius: 4px;
    font-size: 0.82rem;
    color: #475569;
    border: 1px solid #e2e8f0;
  }

  .btn-acams {
    background: #002d72;
    color: #fff;
    border: none;
    padding: 15px;
    font-weight: 700;
    border-radius: 2px;
    transition: 0.3s;
  }

  .btn-acams:hover {
    background: #001f4d;
    transform: translateY(-1px);
  }
</style>

<div class="container">
  <div class="acams-card">
    <h2 class="fw-bold mb-1">Create Account</h2>
    <p class="text-muted mb-4">Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign In</a></p>

    <?php if ($errors): ?>
      <div class="alert alert-danger py-2 small"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label">First name *</label>
          <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Last name *</label>
          <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Username (Primary Email) *</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <div class="col-12">
          <div class="pwd-requirements">
            Your password must be at least 12 characters long and contain one uppercase letter, one lowercase letter, one number, and one special character.
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Country/Region *</label>
          <select name="country" class="form-select" required>
            <option value="">--None--</option>
            <?php foreach ($countries as $code => $name): ?>
              <option value="<?= $code ?>" <?= (isset($_POST['country']) && $_POST['country'] == $code) ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Industry *</label>
          <select name="industry" class="form-select" required>
            <option value="">--None--</option>
            <?php foreach ($industries as $ind): ?>
              <option value="<?= $ind ?>" <?= (isset($_POST['industry']) && $_POST['industry'] == $ind) ? 'selected' : '' ?>><?= $ind ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Company</label>
          <input type="text" name="company" class="form-control" placeholder="Company Name" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
        </div>

        <div class="col-md-5">
          <label class="form-label">Phone Code *</label>
          <select name="phone_code" class="form-select" required>
            <option value="">--None--</option>
            <?php foreach ($phone_codes as $code => $info): ?>
              <option value="<?= $info['code'] ?>" <?= (isset($_POST['phone_code']) && $_POST['phone_code'] == $info['code']) ? 'selected' : '' ?>>
                <?= $info['name'] ?>(<?= $info['code'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-7">
          <label class="form-label">Phone Number *</label>
          <input type="tel" name="phone_number" class="form-control" placeholder="000 000 000" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" required>
        </div>

        <div class="col-12">
          <div class="form-check small mb-4">
            <input class="form-check-input" type="checkbox" id="terms" required>
            <label class="form-check-label text-muted" for="terms">
              I agree with the Terms and Conditions, and consent to the collection and use of my information.
            </label>
          </div>
          <button type="submit" class="btn-acams w-100">CREATE ACCOUNT</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="text-center pb-5">
  <small class="text-muted">© <?= date('Y') ?> EduLux Professional. All Rights Reserved.</small>
</div>

</body>

</html>