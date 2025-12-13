<?php
// pages/auth/register.php
require_once __DIR__ . '/../../includes/config.php';

if (isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "dashboard");
  exit;
}

$errors = [];
$success = '';
$role = $_POST['role'] ?? 'student';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $errors[] = "Invalid request. Please try again.";
  } else {
    $role    = $_POST['role'];
    $first_name = trim($_POST["{$role}_first_name"] ?? '');
    $middle_name = trim($_POST["{$role}_middle_name"] ?? '');
    $last_name  = trim($_POST["{$role}_last_name"] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm)) {
      $errors[] = "All required fields must be filled.";
    } elseif ($password !== $confirm) {
      $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
      $errors[] = "Password must be at least 8 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Invalid email address.";
    } else {
      try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
          $errors[] = "This email is already registered.";
        } else {
          $upload_base = __DIR__ . '/../../assets/uploads/';
          $dirs = ['avatars/', 'student_ids/', 'certificates/', 'credentials/'];
          foreach ($dirs as $dir) {
            if (!is_dir($upload_base . $dir)) mkdir($upload_base . $dir, 0755, true);
          }

          // Handle Avatar Upload (common for both roles)
          $avatar_path = 'avatars/default.jpg';
          if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array(strtolower($ext), $allowed) && $_FILES['profile_picture']['size'] < 5 * 1024 * 1024) {
              $avatar_path = 'avatars/' . time() . '_' . uniqid() . '.' . strtolower($ext);
              move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_base . $avatar_path);
            } else {
              $errors[] = "Invalid profile picture. Use JPG/PNG (max 5MB).";
            }
          }

          if (empty($errors)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $username = strtolower(substr($first_name, 0, 1) . $last_name . '_' . substr(uniqid(), -4));
            $approval_status = ($role === 'instructor') ? 'pending' : 'approved';
                        
                        // Extract new instructor/student fields
                        $bio = trim($_POST['instructor_bio'] ?? $_POST['student_bio'] ?? '');
                        $facebook_url = trim($_POST['instructor_facebook_url'] ?? '');
                        $twitter_url = trim($_POST['instructor_twitter_url'] ?? '');
                        
                        // 1. Insert into USERS table (NOTE: Bio is included here)
            $stmt = $pdo->prepare("INSERT INTO users (username, email, first_name, middle_name, last_name, password_hash, role, approval_status, avatar, bio, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$username, $email, $first_name, $middle_name, $last_name, $password_hash, $role, $approval_status, $avatar_path, $bio]);
            $user_id = $pdo->lastInsertId();

            // 2. Role-specific uploads & details
            if ($role === 'student') {
              $required = ['date_of_birth', 'gender', 'nationality', 'mobile_number', 'residential_address', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone', 'highest_qualification', 'intended_modules', 'preferred_learning_format', 'availability_schedule', 'enrollment_reason'];
              foreach ($required as $f) {
                if (empty($_POST["student_$f"])) $errors[] = "All student fields are required.";
              }

              $id_upload = $cert_upload = '';
              if (empty($errors) && isset($_FILES['student_id_upload']) && $_FILES['student_id_upload']['error'] === 0) {
                $ext = pathinfo($_FILES['student_id_upload']['name'], PATHINFO_EXTENSION);
                $id_upload = 'student_ids/' . time() . '_id_' . uniqid() . '.' . strtolower($ext);
                move_uploaded_file($_FILES['student_id_upload']['tmp_name'], $upload_base . $id_upload);
              } else $errors[] = "Government ID upload required.";

              if (empty($errors) && isset($_FILES['student_certificate_upload']) && $_FILES['student_certificate_upload']['error'] === 0) {
                $ext = pathinfo($_FILES['student_certificate_upload']['name'], PATHINFO_EXTENSION);
                $cert_upload = 'certificates/' . time() . '_cert_' . uniqid() . '.' . strtolower($ext);
                move_uploaded_file($_FILES['student_certificate_upload']['tmp_name'], $upload_base . $cert_upload);
              } else $errors[] = "Certificate upload required.";

              if (empty($errors)) {
                $stmt = $pdo->prepare("INSERT INTO student_details (user_id, date_of_birth, gender, nationality, mobile_number, residential_address, emergency_contact_name, emergency_contact_relationship, emergency_contact_phone, highest_qualification, intended_modules, preferred_learning_format, availability_schedule, enrollment_reason, id_upload, certificate_upload, consent_digital_verification, consent_recording, consent_code_of_conduct, consent_qr_certificate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                  $user_id,
                  $_POST['student_date_of_birth'],
                  $_POST['student_gender'],
                  $_POST['student_nationality'],
                  $_POST['student_mobile_number'],
                  $_POST['student_residential_address'],
                  $_POST['student_emergency_contact_name'],
                  $_POST['student_emergency_contact_relationship'],
                  $_POST['student_emergency_contact_phone'],
                  $_POST['student_highest_qualification'],
                  $_POST['student_intended_modules'],
                  $_POST['student_preferred_learning_format'],
                  $_POST['student_availability_schedule'],
                  $_POST['student_enrollment_reason'],
                  $id_upload,
                  $cert_upload,
                  isset($_POST['student_consent_digital_verification']) ? 1 : 0,
                  isset($_POST['student_consent_recording']) ? 1 : 0,
                  isset($_POST['student_consent_code_of_conduct']) ? 1 : 0,
                  isset($_POST['student_consent_qr_certificate']) ? 1 : 0
                ]);
                $success = "Welcome to EduLux! Your account is ready. <a href='" . BASE_URL . "pages/auth/login.php' class='text-white fw-bold'>Log in now</a>";
              }
            }

            if ($role === 'instructor') {
              $required = ['date_of_birth', 'gender', 'nationality', 'residential_address', 'mobile_number', 'linkedin_url', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone', 'highest_qualification', 'professional_certifications', 'areas_of_specialization', 'modules_qualified', 'teaching_experience_years', 'preferred_teaching_format', 'availability_schedule'];
              foreach ($required as $f) {
                if (empty($_POST["instructor_$f"])) $errors[] = "All instructor fields are required.";
              }
                            
              $cred_upload = '';
              if (empty($errors) && isset($_FILES['instructor_credentials_upload']) && $_FILES['instructor_credentials_upload']['error'] === 0) {
                $ext = pathinfo($_FILES['instructor_credentials_upload']['name'], PATHINFO_EXTENSION);
                $cred_upload = 'credentials/' . time() . '_cred_' . uniqid() . '.' . strtolower($ext);
                move_uploaded_file($_FILES['instructor_credentials_upload']['tmp_name'], $upload_base . $cred_upload);
              } else $errors[] = "Professional credentials upload required.";

              if (empty($errors)) {
                                // 1. Update the main users table with the BIO (common field stored in users)
                                // This is done outside the instructor_details block now.
                                
                                // 2. Insert into instructor_details (CRITICAL FIX: Explicit columns and parameter list)
                $stmt = $pdo->prepare("INSERT INTO instructor_details (
                                    user_id, date_of_birth, gender, nationality, residential_address, mobile_number, 
                                    linkedin_url, facebook_url, twitter_url, 
                                    emergency_contact_name, emergency_contact_relationship, emergency_contact_phone, 
                                    highest_qualification, professional_certifications, areas_of_specialization, 
                                    modules_qualified, teaching_experience_years, preferred_teaching_format, availability_schedule, 
                                    credentials_upload, consent_qr_code, consent_cpd_standards, consent_recording
                                ) VALUES (
                                    ?, ?, ?, ?, ?, ?, 
                                    ?, ?, ?, 
                                    ?, ?, ?, 
                                    ?, ?, ?, 
                                    ?, ?, ?, ?, 
                                    ?, ?, ?, ?
                                )"); 
                $stmt->execute([
                  $user_id,
                  $_POST['instructor_date_of_birth'],
                  $_POST['instructor_gender'],
                  $_POST['instructor_nationality'],
                  $_POST['instructor_residential_address'],
                  $_POST['instructor_mobile_number'],
                  $_POST['instructor_linkedin_url'],
                                    // New Social Fields (9th and 10th parameter)
                                    $facebook_url,
                                    $twitter_url,
                  $_POST['instructor_emergency_contact_name'],
                  $_POST['instructor_emergency_contact_relationship'],
                  $_POST['instructor_emergency_contact_phone'],
                  $_POST['instructor_highest_qualification'],
                  $_POST['instructor_professional_certifications'],
                  $_POST['instructor_areas_of_specialization'],
                  $_POST['instructor_modules_qualified'],
                  $_POST['instructor_teaching_experience_years'],
                  $_POST['instructor_preferred_teaching_format'],
                  $_POST['instructor_availability_schedule'],
                  $cred_upload,
                  isset($_POST['instructor_consent_qr_code']) ? 1 : 0,
                  isset($_POST['instructor_consent_cpd_standards']) ? 1 : 0,
                  isset($_POST['instructor_consent_recording']) ? 1 : 0
                ]);
                $success = "Instructor application submitted successfully! Awaiting admin approval.";
              }
            }
          }
        }
      } catch (Exception $e) {
        error_log("Register error: " . $e->getMessage());
        $errors[] = "An error occurred. Please try again later.";
      }
    }
  }
}

require_once ROOT_PATH . 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

<style>
  :root {
    --gradient-premium: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  }

  .register-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    position: relative;
    overflow: hidden;
    padding: 130px 20px 80px;
    display: flex;
    align-items: flex-start;
    justify-content: center;
  }

  .register-wrapper::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: repeating-conic-gradient(from 30deg at 50% 50%, rgba(99, 102, 241, 0.08) 0deg, transparent 30deg, rgba(139, 92, 246, 0.08) 60deg, transparent 90deg);
    animation: rotate 40s linear infinite;
  }

  @keyframes rotate {
    from {
      transform: rotate(0deg);
    }

    to {
      transform: rotate(360deg);
    }
  }

  .register-card {
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(32px);
    border: 1.5px solid rgba(255, 255, 255, 0.7);
    border-radius: 32px;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
    padding: 50px 60px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    z-index: 10;
    /* HIDE SCROLLBARS BUT KEEP SCROLLING */
    -ms-overflow-style: none;
    scrollbar-width: none;
  }

  .register-card::-webkit-scrollbar {
    display: none;
  }

  .register-card::before {
    content: '';
    position: absolute;
    inset: -3px;
    background: var(--gradient-premium);
    border-radius: 35px;
    z-index: -1;
    opacity: 0.22;
    filter: blur(22px);
    animation: pulse 5s ease-in-out infinite alternate;
  }

  .avatar-preview {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid rgba(139, 92, 246, 0.4);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    transition: all 0.4s;
  }

  .avatar-preview:hover {
    transform: scale(1.08);
  }

  .doc-requirement {
    background: rgba(99, 102, 241, 0.12);
    border-left: 5px solid #6366f1;
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 0.95rem;
    color: #1e293b;
  }

  /* GORGEOUS ACTIVE ROLE BUTTONS */
  .role-btn {
    background: rgba(15, 23, 42, 0.08);
    border: 2px solid transparent;
    border-radius: 24px;
    padding: 2rem 1.5rem;
    transition: all 0.4s ease;
    color: #64748b;
    font-weight: 600;
  }

  .role-btn:hover {
    transform: translateY(-8px);
    background: rgba(99, 102, 241, 0.15);
  }

  .role-btn.active {
    background: var(--gradient-premium);
    color: white !important;
    transform: translateY(-12px) scale(1.05);
    box-shadow: 0 20px 40px rgba(139, 92, 246, 0.5);
    border-color: rgba(255, 255, 255, 0.3);
  }

  .role-btn.active i {
    filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.8));
  }

  .logo-text {
    font-size: 4rem;
    font-weight: 900;
    background: var(--gradient-premium);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
</style>

<div class="register-wrapper">
  <div class="register-card">
    <div class="text-center mb-5">
      <h1 class="logo-text">EduLux</h1>
      <p class="fs-4 fw-light text-dark opacity-75">Join the elite learning revolution</p>
    </div>

        <div class="text-center mb-4">
      <img src="<?php echo BASE_URL; ?>assets/uploads/avatars/default2.webp" id="avatarPreview" class="avatar-preview" alt="Profile Picture">
      <div class="mt-3">
        <label class="btn btn-outline-primary rounded-pill px-4">
          <i class="fas fa-camera me-2"></i> Upload Photo
          <input type="file" name="profile_picture" id="profilePicture" accept="image/*" class="d-none">
        </label>
        <small class="text-muted d-block mt-2">JPG, PNG, GIF • Max 5MB</small>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger rounded-4 border-0 mb-4">
        <?php foreach ($errors as $error): ?><div><?php echo e($error); ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success rounded-4 border-0 mb-4 text-white bg-success bg-opacity-20">
        <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

      <div class="text-center mb-5">
        <div class="btn-group w-100" role="group" style="gap: 20px;">
          <input type="radio" class="btn-check" name="role" id="role_student" value="student" <?php echo $role === 'student' ? 'checked' : ''; ?> required>
          <label class="btn role-btn <?php echo $role === 'student' ? 'active' : ''; ?>" for="role_student">
            <i class="fas fa-user-graduate fa-3x mb-3"></i><br><strong>Student</strong>
          </label>

          <input type="radio" class="btn-check" name="role" id="role_instructor" value="instructor" <?php echo $role === 'instructor' ? 'checked' : ''; ?>>
          <label class="btn role-btn <?php echo $role === 'instructor' ? 'active' : ''; ?>" for="role_instructor">
            <i class="fas fa-chalkboard-teacher fa-3x mb-3"></i><br><strong>Instructor</strong>
          </label>
        </div>
      </div>

            <div class="row g-3 mb-4">
        <div class="col-md-4"><input type="text" name="<?php echo $role; ?>_first_name" class="form-control" placeholder="First Name *" value="<?php echo e($_POST["{$role}_first_name"] ?? ''); ?>" required></div>
        <div class="col-md-4"><input type="text" name="<?php echo $role; ?>_middle_name" class="form-control" placeholder="Middle Name" value="<?php echo e($_POST["{$role}_middle_name"] ?? ''); ?>"></div>
        <div class="col-md-4"><input type="text" name="<?php echo $role; ?>_last_name" class="form-control" placeholder="Last Name *" value="<?php echo e($_POST["{$role}_last_name"] ?? ''); ?>" required></div>
        <div class="col-12"><input type="email" name="email" class="form-control" placeholder="Email Address *" value="<?php echo e($_POST['email'] ?? ''); ?>" required></div>
        <div class="col-md-6"><input type="password" name="password" class="form-control" placeholder="Password (8+ chars) *" minlength="8" required></div>
        <div class="col-md-6"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password *" required></div>
      </div>

            <div id="role-specific-fields"></div>

      <button type="submit" class="btn btn-register w-100 mt-4">
        <i class="fas fa-sparkles me-2"></i>
        Create Elite Account
      </button>

      <div class="text-center mt-4">
        <p class="text-muted mb-0">
          Already have an account?
          <a href="<?php echo BASE_URL; ?>pages/auth/login.php" class="fw-bold text-primary text-decoration-underline">Sign In</a>
        </p>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
  // Avatar Preview
  document.getElementById('profilePicture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('avatarPreview').src = e.target.result;
      }
      reader.readAsDataURL(file);
    }
  });

  // Modern Date Picker
  function initDatePickers() {
    flatpickr(".flatpickr-input", {
      dateFormat: "Y-m-d",
      maxDate: "today",
      allowInput: true,
      theme: "material_blue",
      locale: {
        firstDayOfWeek: 1
      }
    });
  }

  // Role Fields
  const studentFields = `
<h4 class="mt-5 mb-4 text-primary"><i class="fas fa-user-graduate me-2"></i>Student Personal & Academic Details</h4>
<div class="row g-3">
  <div class="col-md-6"><input type="text" class="form-control flatpickr-input" name="student_date_of_birth" placeholder="Date of Birth *" required></div>
  <div class="col-md-6"><select name="student_gender" class="form-select" required><option value="">Gender *</option><option>Male</option><option>Female</option><option>Other</option></select></div>
  <div class="col-12"><input type="text" name="student_nationality" class="form-control" placeholder="Nationality *" required></div>
  <div class="col-12"><input type="text" name="student_mobile_number" class="form-control" placeholder="Mobile Number (e.g. +234...)" required></div>
  <div class="col-12"><textarea name="student_residential_address" class="form-control" rows="3" placeholder="Residential Address *" required></textarea></div>
  <div class="col-md-4"><input type="text" name="student_emergency_contact_name" class="form-control" placeholder="Emergency Contact Name *" required></div>
  <div class="col-md-4"><input type="text" name="student_emergency_contact_relationship" class="form-control" placeholder="Relationship *" required></div>
  <div class="col-md-4"><input type="text" name="student_emergency_contact_phone" class="form-control" placeholder="Emergency Phone *" required></div>
  <div class="col-12"><input type="text" name="student_highest_qualification" class="form-control" placeholder="Highest Qualification *" required></div>
  <div class="col-12"><textarea name="student_intended_modules" class="form-control" rows="3" placeholder="Intended Module(s) *" required></textarea></div>
  <div class="col-md-6"><select name="student_preferred_learning_format" class="form-select" required><option value="">Preferred Format *</option><option>Live Sessions</option><option>Recorded</option><option>Hybrid</option></select></div>
  <div class="col-12"><textarea name="student_availability_schedule" class="form-control" rows="3" placeholder="Availability Schedule *" required></textarea></div>
  <div class="col-12"><textarea name="student_enrollment_reason" class="form-control" rows="4" placeholder="Reason for Enrolling *" required></textarea></div>

  <div class="col-12"><div class="doc-requirement"><strong>Required:</strong> Government-issued ID (Passport, Driver's License, National ID)</div></div>
  <div class="col-12"><input type="file" name="student_id_upload" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>

  <div class="col-12"><div class="doc-requirement"><strong>Required:</strong> Highest Qualification Certificate (Degree, Diploma, etc.)</div></div>
  <div class="col-12"><input type="file" name="student_certificate_upload" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>

  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="student_consent_digital_verification" required><label>Consent to Digital Verification *</label></div></div>
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="student_consent_recording" required><label>Consent to Session Recording *</label></div></div>
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="student_consent_code_of_conduct" required><label>Agree to Code of Conduct *</label></div></div>
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="student_consent_qr_certificate" required><label>Consent to QR on Certificate *</label></div></div>
</div>`;

  const instructorFields = `
<h4 class="mt-5 mb-4 text-primary"><i class="fas fa-chalkboard-teacher me-2"></i>Instructor Professional Application</h4>
<div class="row g-3">
  <div class="col-md-6"><input type="text" class="form-control flatpickr-input" name="instructor_date_of_birth" placeholder="Date of Birth *" required></div>
  <div class="col-md-6"><select name="instructor_gender" class="form-select" required><option value="">Gender *</option><option>Male</option><option>Female</option><option>Other</option></select></div>
  <div class="col-12"><input type="text" name="instructor_nationality" class="form-control" placeholder="Nationality *" required></div>
  <div class="col-12"><textarea name="instructor_residential_address" class="form-control" rows="3" placeholder="Residential Address *" required></textarea></div>
  <div class="col-12"><input type="text" name="instructor_mobile_number" class="form-control" placeholder="Mobile (with country code) *" required></div>
  <div class="col-12"><input type="url" name="instructor_linkedin_url" class="form-control" placeholder="LinkedIn / Professional Profile URL *" required></div>
  
    <div class="col-12"><textarea name="instructor_bio" class="form-control" rows="4" placeholder="Professional Bio/Summary for Public Profile (Max 500 chars)"></textarea></div>
  <div class="col-md-6"><input type="url" name="instructor_facebook_url" class="form-control" placeholder="Facebook Profile URL (Optional)"></div>
    <div class="col-md-6"><input type="url" name="instructor_twitter_url" class="form-control" placeholder="Twitter Profile URL (Optional)"></div>
      <div class="col-md-4"><input type="text" name="instructor_emergency_contact_name" class="form-control" placeholder="Emergency Contact Name *" required></div>
  <div class="col-md-4"><input type="text" name="instructor_emergency_contact_relationship" class="form-control" placeholder="Relationship *" required></div>
  <div class="col-md-4"><input type="text" name="instructor_emergency_contact_phone" class="form-control" placeholder="Emergency Phone *" required></div>
  <div class="col-12"><input type="text" name="instructor_highest_qualification" class="form-control" placeholder="Highest Qualification *" required></div>
  <div class="col-12"><textarea name="instructor_professional_certifications" class="form-control" rows="4" placeholder="Professional Certifications (list all) *" required></textarea></div>
  <div class="col-12"><textarea name="instructor_areas_of_specialization" class="form-control" rows="3" placeholder="Areas of Specialization *" required></textarea></div>
  <div class="col-12"><textarea name="instructor_modules_qualified" class="form-control" rows="4" placeholder="Modules You Are Qualified to Teach *" required></textarea></div>
  <div class="col-md-6"><input type="number" name="instructor_teaching_experience_years" class="form-control" placeholder="Years of Teaching Experience *" min="0" required></div>
  <div class="col-md-6"><select name="instructor_preferred_teaching_format" class="form-select" required><option value="">Preferred Format *</option><option>Live Webinars</option><option>Recorded</option><option>Hybrid</option></select></div>
  <div class="col-12"><textarea name="instructor_availability_schedule" class="form-control" rows="4" placeholder="Weekly Availability (e.g. Mon-Wed 7-9 PM)" required></textarea></div>

  <div class="col-12"><div class="doc-requirement"><strong>Required:</strong> Professional Credentials (CV, Certificates, Licenses, etc.) in one PDF or image</div></div>
  <div class="col-12"><input type="file" name="instructor_credentials_upload" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>

  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="instructor_consent_qr_code" required><label>Consent to QR Code on Certificates *</label></div></div>
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="instructor_consent_cpd_standards" required><label>Agree to CPD Standards *</label></div></div>
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="instructor_consent_recording" required><label>Consent to Recording *</label></div></div>
</div>`;

  function updateFields() {
    const role = document.querySelector('input[name="role"]:checked')?.value || 'student';
    document.getElementById('role-specific-fields').innerHTML = role === 'student' ? studentFields : instructorFields;

    // Update active class on labels
    document.querySelectorAll('.role-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`label[for="role_${role}"]`).classList.add('active');

    initDatePickers();
  }

  document.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', updateFields));
  updateFields();
  initDatePickers();
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>