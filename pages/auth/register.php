<?php
// Debug: Check if config.php is included
$config_path = 'C:/xampp/htdocs/project/E-learning-platform/includes/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
    echo "<!-- Debug: config.php included successfully -->";
} else {
    die("Error: config.php not found at $config_path");
}

// Ensure upload directories exist
$upload_dirs = [
    'C:/xampp/htdocs/project/E-learning-platform/assets/uploads/student_ids',
    'C:/xampp/htdocs/project/E-learning-platform/assets/uploads/certificates',
    'C:/xampp/htdocs/project/E-learning-platform/assets/uploads/instructor_ids',
    'C:/xampp/htdocs/project/E-learning-platform/assets/uploads/credentials'
];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "<!-- Debug: Created directory $dir -->";
    }
}

// Initialize variables for form handling
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common fields
    $role = $_POST['role'] ?? '';
    $first_name = $_POST[$role . '_first_name'] ?? '';
    $middle_name = $_POST[$role . '_middle_name'] ?? '';
    $last_name = $_POST[$role . '_last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $errors[] = "All required fields must be filled.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email is already registered.";
        }
        $stmt->close();

        if (empty($errors)) {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (username, email, first_name, middle_name, last_name, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $username = strtolower($first_name . '.' . $last_name . '_' . time()); // Generate unique username
            $stmt->bind_param("sssssss", $username, $email, $first_name, $middle_name, $last_name, $password_hash, $role);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                // Handle role-specific data
                if ($role === 'instructor') {
                    // Instructor fields
                    $date_of_birth = $_POST['instructor_date_of_birth'] ?? '';
                    $gender = $_POST['instructor_gender'] ?? '';
                    $nationality = $_POST['instructor_nationality'] ?? '';
                    $id_number = $_POST['instructor_id_number'] ?? '';
                    $residential_address = $_POST['instructor_residential_address'] ?? '';
                    $mobile_number = $_POST['instructor_mobile_number'] ?? '';
                    $linkedin_url = $_POST['instructor_linkedin_url'] ?? '';
                    $emergency_contact_name = $_POST['instructor_emergency_contact_name'] ?? '';
                    $emergency_contact_relationship = $_POST['instructor_emergency_contact_relationship'] ?? '';
                    $emergency_contact_phone = $_POST['instructor_emergency_contact_phone'] ?? '';
                    $highest_qualification = $_POST['instructor_highest_qualification'] ?? '';
                    $professional_certifications = $_POST['instructor_professional_certifications'] ?? '';
                    $professional_memberships = $_POST['instructor_professional_memberships'] ?? '';
                    $areas_of_specialization = $_POST['instructor_areas_of_specialization'] ?? '';
                    $modules_qualified = $_POST['instructor_modules_qualified'] ?? '';
                    $teaching_experience_years = $_POST['instructor_teaching_experience_years'] ?? 0;
                    $preferred_teaching_format = $_POST['instructor_preferred_teaching_format'] ?? '';
                    $availability_schedule = $_POST['instructor_availability_schedule'] ?? '';
                    $current_employer = $_POST['instructor_current_employer'] ?? '';
                    $current_role = $_POST['instructor_current_role'] ?? '';
                    $institutional_reference = $_POST['instructor_institutional_reference'] ?? '';
                    $consent_qr_code = isset($_POST['instructor_consent_qr_code']) ? 1 : 0;
                    $consent_cpd_standards = isset($_POST['instructor_consent_cpd_standards']) ? 1 : 0;
                    $consent_recording = isset($_POST['instructor_consent_recording']) ? 1 : 0;
                    $preferred_payment_method = $_POST['instructor_preferred_payment_method'] ?? '';
                    $faith_integration = isset($_POST['instructor_faith_integration']) ? 1 : 0;
                    $teaching_philosophy = $_POST['instructor_teaching_philosophy'] ?? '';
                    $blessing_dedication = $_POST['instructor_blessing_dedication'] ?? '';

                    // Validate instructor required fields
                    if (empty($date_of_birth) || empty($gender) || empty($nationality) || empty($residential_address) || 
                        empty($mobile_number) || empty($linkedin_url) || empty($emergency_contact_name) || 
                        empty($emergency_contact_relationship) || empty($emergency_contact_phone) || 
                        empty($highest_qualification) || empty($professional_certifications) || 
                        empty($areas_of_specialization) || empty($modules_qualified) || 
                        empty($teaching_experience_years) || empty($preferred_teaching_format) || 
                        empty($availability_schedule) || empty($_FILES['instructor_credentials_upload']['name'])) {
                        $errors[] = "All required instructor fields must be filled.";
                    }

                    // Handle file uploads
                    $id_upload = '';
                    $credentials_upload = '';
                    if (!empty($_FILES['instructor_id_upload']['name'])) {
                        $id_upload = 'uploads/instructor_ids/' . time() . '_' . basename($_FILES['instructor_id_upload']['name']);
                        if (!move_uploaded_file($_FILES['instructor_id_upload']['tmp_name'], 'C:/xampp/htdocs/project/E-learning-platform/assets/' . $id_upload)) {
                            $errors[] = "Failed to upload ID file.";
                        }
                    }
                    if (!empty($_FILES['instructor_credentials_upload']['name'])) {
                        $credentials_upload = 'uploads/credentials/' . time() . '_' . basename($_FILES['instructor_credentials_upload']['name']);
                        if (!move_uploaded_file($_FILES['instructor_credentials_upload']['tmp_name'], 'C:/xampp/htdocs/project/E-learning-platform/assets/' . $credentials_upload)) {
                            $errors[] = "Failed to upload credentials file.";
                        }
                    }

                    if (empty($errors)) {
                        // Insert instructor details
                        $stmt = $conn->prepare("INSERT INTO instructor_details (
                            user_id, date_of_birth, gender, nationality, id_number, id_upload, residential_address, 
                            mobile_number, linkedin_url, emergency_contact_name, emergency_contact_relationship, 
                            emergency_contact_phone, highest_qualification, professional_certifications, 
                            professional_memberships, areas_of_specialization, modules_qualified, 
                            teaching_experience_years, preferred_teaching_format, availability_schedule, 
                            current_employer, current_role, institutional_reference, credentials_upload, 
                            consent_qr_code, consent_cpd_standards, consent_recording, preferred_payment_method, 
                            faith_integration, teaching_philosophy, blessing_dedication
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param(
                            "isssssssssssssiisssssssiiisss", // Fixed type string - 31 characters for 31 parameters
                            $user_id, $date_of_birth, $gender, $nationality, $id_number, $id_upload, $residential_address,
                            $mobile_number, $linkedin_url, $emergency_contact_name, $emergency_contact_relationship,
                            $emergency_contact_phone, $highest_qualification, $professional_certifications,
                            $professional_memberships, $areas_of_specialization, $modules_qualified,
                            $teaching_experience_years, $preferred_teaching_format, $availability_schedule,
                            $current_employer, $current_role, $institutional_reference, $credentials_upload,
                            $consent_qr_code, $consent_cpd_standards, $consent_recording, $preferred_payment_method,
                            $faith_integration, $teaching_philosophy, $blessing_dedication
                        );
                        if ($stmt->execute()) {
                            $success = "Instructor registration submitted. Awaiting admin approval.";
                        } else {
                            $errors[] = "Error saving instructor details.";
                        }
                    }
                } elseif ($role === 'student') {
                    // Student fields
                    $date_of_birth = $_POST['student_date_of_birth'] ?? '';
                    $gender = $_POST['student_gender'] ?? '';
                    $nationality = $_POST['student_nationality'] ?? '';
                    $mobile_number = $_POST['student_mobile_number'] ?? '';
                    $residential_address = $_POST['student_residential_address'] ?? '';
                    $emergency_contact_name = $_POST['student_emergency_contact_name'] ?? '';
                    $emergency_contact_relationship = $_POST['student_emergency_contact_relationship'] ?? '';
                    $emergency_contact_phone = $_POST['student_emergency_contact_phone'] ?? '';
                    $highest_qualification = $_POST['student_highest_qualification'] ?? '';
                    $current_occupation = $_POST['student_current_occupation'] ?? '';
                    $employer = $_POST['student_employer'] ?? '';
                    $professional_certifications = $_POST['student_professional_certifications'] ?? '';
                    $experience_years = $_POST['student_experience_years'] ?? 0;
                    $intended_modules = $_POST['student_intended_modules'] ?? '';
                    $preferred_learning_format = $_POST['student_preferred_learning_format'] ?? '';
                    $availability_schedule = $_POST['student_availability_schedule'] ?? '';
                    $enrollment_reason = $_POST['student_enrollment_reason'] ?? '';
                    $consent_digital_verification = isset($_POST['student_consent_digital_verification']) ? 1 : 0;
                    $consent_recording = isset($_POST['student_consent_recording']) ? 1 : 0;
                    $consent_code_of_conduct = isset($_POST['student_consent_code_of_conduct']) ? 1 : 0;
                    $consent_qr_certificate = isset($_POST['student_consent_qr_certificate']) ? 1 : 0;
                    $legacy_statement = $_POST['student_legacy_statement'] ?? '';
                    $blessing_dedication = $_POST['student_blessing_dedication'] ?? '';

                    // Validate student required fields
                    if (empty($date_of_birth) || empty($gender) || empty($nationality) || empty($mobile_number) || 
                        empty($residential_address) || empty($emergency_contact_name) || 
                        empty($emergency_contact_relationship) || empty($emergency_contact_phone) || 
                        empty($highest_qualification) || empty($intended_modules) || 
                        empty($preferred_learning_format) || empty($availability_schedule) || 
                        empty($enrollment_reason) || empty($_FILES['student_id_upload']['name']) || 
                        empty($_FILES['student_certificate_upload']['name'])) {
                        $errors[] = "All required student fields must be filled.";
                    }

                    // Handle file uploads
                    $id_upload = '';
                    $certificate_upload = '';
                    if (!empty($_FILES['student_id_upload']['name'])) {
                        $id_upload = 'uploads/student_ids/' . time() . '_' . basename($_FILES['student_id_upload']['name']);
                        if (!move_uploaded_file($_FILES['student_id_upload']['tmp_name'], 'C:/xampp/htdocs/project/E-learning-platform/assets/' . $id_upload)) {
                            $errors[] = "Failed to upload ID file.";
                        }
                    }
                    if (!empty($_FILES['student_certificate_upload']['name'])) {
                        $certificate_upload = 'uploads/certificates/' . time() . '_' . basename($_FILES['student_certificate_upload']['name']);
                        if (!move_uploaded_file($_FILES['student_certificate_upload']['tmp_name'], 'C:/xampp/htdocs/project/E-learning-platform/assets/' . $certificate_upload)) {
                            $errors[] = "Failed to upload certificate file.";
                        }
                    }

                    if (empty($errors)) {
                        // Insert student details
                        $stmt = $conn->prepare("INSERT INTO student_details (
                            user_id, date_of_birth, gender, nationality, mobile_number, residential_address, 
                            emergency_contact_name, emergency_contact_relationship, emergency_contact_phone, 
                            highest_qualification, current_occupation, employer, professional_certifications, 
                            experience_years, intended_modules, preferred_learning_format, availability_schedule, 
                            enrollment_reason, id_upload, certificate_upload, consent_digital_verification, 
                            consent_recording, consent_code_of_conduct, consent_qr_certificate, legacy_statement, 
                            blessing_dedication
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param(
                            "isssssssssssiissssiiiss", // Fixed type string - 26 characters for 26 parameters
                            $user_id, $date_of_birth, $gender, $nationality, $mobile_number, $residential_address,
                            $emergency_contact_name, $emergency_contact_relationship, $emergency_contact_phone,
                            $highest_qualification, $current_occupation, $employer, $professional_certifications,
                            $experience_years, $intended_modules, $preferred_learning_format, $availability_schedule,
                            $enrollment_reason, $id_upload, $certificate_upload, $consent_digital_verification,
                            $consent_recording, $consent_code_of_conduct, $consent_qr_certificate, $legacy_statement,
                            $blessing_dedication
                        );
                        if ($stmt->execute()) {
                            $success = "Student registration successful! You can now log in.";
                        } else {
                            $errors[] = "Error saving student details.";
                        }
                    }
                }
            } else {
                $errors[] = "Error registering user. Please try again.";
            }
            $stmt->close();
        }
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

<!-- Registration Section -->
<section class="section-padding py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-card p-5 rounded-3 shadow-lg">
                    <h2 class="display-4 fw-bold mb-4 text-center">Join <span class="gradient-text">EduLux</span></h2>
                    <p class="text-muted text-center mb-5">Create your account as a Student or Instructor</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <!-- Role Selection -->
                    <div class="mb-4">
                        <label for="role" class="form-label fw-bold">Select Role *</label>
                        <select id="role" name="role" class="form-select" onchange="toggleForm()" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                        </select>
                    </div>

                    <!-- Student Form -->
                    <form id="student-form" style="display: none;" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="role" value="student">
                        <h4 class="mt-5 mb-4">Section A: Learner Personal Information</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="student_first_name" class="form-label fw-bold">First Name *</label>
                                <input type="text" class="form-control" id="student_first_name" name="student_first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="student_middle_name" class="form-label fw-bold">Middle Name</label>
                                <input type="text" class="form-control" id="student_middle_name" name="student_middle_name">
                            </div>
                            <div class="col-md-4">
                                <label for="student_last_name" class="form-label fw-bold">Last Name *</label>
                                <input type="text" class="form-control" id="student_last_name" name="student_last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_email" class="form-label fw-bold">Email Address *</label>
                                <input type="email" class="form-control" id="student_email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_password" class="form-label fw-bold">Password *</label>
                                <input type="password" class="form-control" id="student_password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_confirm_password" class="form-label fw-bold">Confirm Password *</label>
                                <input type="password" class="form-control" id="student_confirm_password" name="confirm_password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_date_of_birth" class="form-label fw-bold">Date of Birth *</label>
                                <input type="date" class="form-control" id="student_date_of_birth" name="student_date_of_birth" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_gender" class="form-label fw-bold">Gender *</label>
                                <select class="form-select" id="student_gender" name="student_gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="student_nationality" class="form-label fw-bold">Nationality *</label>
                                <input type="text" class="form-control" id="student_nationality" name="student_nationality" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_mobile_number" class="form-label fw-bold">Mobile Number (with country code) *</label>
                                <input type="text" class="form-control" id="student_mobile_number" name="student_mobile_number" required>
                            </div>
                            <div class="col-12">
                                <label for="student_residential_address" class="form-label fw-bold">Residential Address *</label>
                                <textarea class="form-control" id="student_residential_address" name="student_residential_address" rows="4" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="student_emergency_contact_name" class="form-label fw-bold">Emergency Contact Name *</label>
                                <input type="text" class="form-control" id="student_emergency_contact_name" name="student_emergency_contact_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="student_emergency_contact_relationship" class="form-label fw-bold">Relationship *</label>
                                <input type="text" class="form-control" id="student_emergency_contact_relationship" name="student_emergency_contact_relationship" required>
                            </div>
                            <div class="col-md-4">
                                <label for="student_emergency_contact_phone" class="form-label fw-bold">Emergency Contact Phone *</label>
                                <input type="text" class="form-control" id="student_emergency_contact_phone" name="student_emergency_contact_phone" required>
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section B: Academic & Professional Background</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="student_highest_qualification" class="form-label fw-bold">Highest Academic Qualification *</label>
                                <input type="text" class="form-control" id="student_highest_qualification" name="student_highest_qualification" required>
                            </div>
                            <div class="col-md-6">
                                <label for="student_current_occupation" class="form-label fw-bold">Current Occupation</label>
                                <input type="text" class="form-control" id="student_current_occupation" name="student_current_occupation">
                            </div>
                            <div class="col-md-6">
                                <label for="student_employer" class="form-label fw-bold">Employer</label>
                                <input type="text" class="form-control" id="student_employer" name="student_employer">
                            </div>
                            <div class="col-12">
                                <label for="student_professional_certifications" class="form-label fw-bold">Professional Certifications</label>
                                <textarea class="form-control" id="student_professional_certifications" name="student_professional_certifications" rows="4"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="student_experience_years" class="form-label fw-bold">Years of Experience</label>
                                <input type="number" class="form-control" id="student_experience_years" name="student_experience_years" min="0">
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section C: Course Enrollment Details</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="student_intended_modules" class="form-label fw-bold">Intended Module(s) *</label>
                                <textarea class="form-control" id="student_intended_modules" name="student_intended_modules" rows="4" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="student_preferred_learning_format" class="form-label fw-bold">Preferred Learning Format *</label>
                                <select class="form-select" id="student_preferred_learning_format" name="student_preferred_learning_format" required>
                                    <option value="">Select Format</option>
                                    <option value="Live Sessions">Live Sessions</option>
                                    <option value="Recorded Modules">Recorded Modules</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="student_availability_schedule" class="form-label fw-bold">Availability Schedule *</label>
                                <textarea class="form-control" id="student_availability_schedule" name="student_availability_schedule" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <label for="student_enrollment_reason" class="form-label fw-bold">Reason for Enrolling *</label>
                                <textarea class="form-control" id="student_enrollment_reason" name="student_enrollment_reason" rows="4" required></textarea>
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section D: CPD Verification & Consent</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="student_id_upload" class="form-label fw-bold">Upload Valid ID or Passport *</label>
                                <input type="file" class="form-control" id="student_id_upload" name="student_id_upload" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="col-12">
                                <label for="student_certificate_upload" class="form-label fw-bold">Upload Academic/Professional Certificates *</label>
                                <input type="file" class="form-control" id="student_certificate_upload" name="student_certificate_upload" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="student_consent_digital_verification" name="student_consent_digital_verification" required>
                                    <label class="form-check-label" for="student_consent_digital_verification">Consent to Digital Verification of Credentials *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="student_consent_recording" name="student_consent_recording" required>
                                    <label class="form-check-label" for="student_consent_recording">Consent to Recording of Sessions *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="student_consent_code_of_conduct" name="student_consent_code_of_conduct" required>
                                    <label class="form-check-label" for="student_consent_code_of_conduct">Agree to CPD Group’s Code of Conduct *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="student_consent_qr_certificate" name="student_consent_qr_certificate" required>
                                    <label class="form-check-label" for="student_consent_qr_certificate">Consent to QR Code on Certificates *</label>
                                </div>
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section E: Legacy & Impact Statement (Optional)</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="student_legacy_statement" class="form-label fw-bold">Personal Reflection on Professional Legacy</label>
                                <textarea class="form-control" id="student_legacy_statement" name="student_legacy_statement" rows="4"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="student_blessing_dedication" class="form-label fw-bold">Blessing or Dedication for Learning Journey</label>
                                <textarea class="form-control" id="student_blessing_dedication" name="student_blessing_dedication" rows="4"></textarea>
                            </div>
                        </div>

                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-primary w-100">Register as Student</button>
                        </div>
                    </form>

                    <!-- Instructor Form -->
                    <form id="instructor-form" style="display: none;" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="role" value="instructor">
                        <h4 class="mt-5 mb-4">Section A: Instructor Biodata</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="instructor_first_name" class="form-label fw-bold">First Name *</label>
                                <input type="text" class="form-control" id="instructor_first_name" name="instructor_first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="instructor_middle_name" class="form-label fw-bold">Middle Name</label>
                                <input type="text" class="form-control" id="instructor_middle_name" name="instructor_middle_name">
                            </div>
                            <div class="col-md-4">
                                <label for="instructor_last_name" class="form-label fw-bold">Last Name *</label>
                                <input type="text" class="form-control" id="instructor_last_name" name="instructor_last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_email" class="form-label fw-bold">Email Address *</label>
                                <input type="email" class="form-control" id="instructor_email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_password" class="form-label fw-bold">Password *</label>
                                <input type="password" class="form-control" id="instructor_password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_confirm_password" class="form-label fw-bold">Confirm Password *</label>
                                <input type="password" class="form-control" id="instructor_confirm_password" name="confirm_password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_date_of_birth" class="form-label fw-bold">Date of Birth *</label>
                                <input type="date" class="form-control" id="instructor_date_of_birth" name="instructor_date_of_birth" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_gender" class="form-label fw-bold">Gender *</label>
                                <select class="form-select" id="instructor_gender" name="instructor_gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_nationality" class="form-label fw-bold">Nationality *</label>
                                <input type="text" class="form-control" id="instructor_nationality" name="instructor_nationality" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_id_number" class="form-label fw-bold">Passport or National ID Number</label>
                                <input type="text" class="form-control" id="instructor_id_number" name="instructor_id_number">
                            </div>
                            <div class="col-12">
                                <label for="instructor_id_upload" class="form-label fw-bold">Upload Passport or National ID</label>
                                <input type="file" class="form-control" id="instructor_id_upload" name="instructor_id_upload" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-12">
                                <label for="instructor_residential_address" class="form-label fw-bold">Residential Address *</label>
                                <textarea class="form-control" id="instructor_residential_address" name="instructor_residential_address" rows="4" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_mobile_number" class="form-label fw-bold">Mobile Number (with country code) *</label>
                                <input type="text" class="form-control" id="instructor_mobile_number" name="instructor_mobile_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_linkedin_url" class="form-label fw-bold">LinkedIn or Professional Profile URL *</label>
                                <input type="url" class="form-control" id="instructor_linkedin_url" name="instructor_linkedin_url" required>
                            </div>
                            <div class="col-md-4">
                                <label for="instructor_emergency_contact_name" class="form-label fw-bold">Emergency Contact Name *</label>
                                <input type="text" class="form-control" id="instructor_emergency_contact_name" name="instructor_emergency_contact_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="instructor_emergency_contact_relationship" class="form-label fw-bold">Relationship *</label>
                                <input type="text" class="form-control" id="instructor_emergency_contact_relationship" name="instructor_emergency_contact_relationship" required>
                            </div>
                            <div class="col-md-4">
                                <label for="instructor_emergency_contact_phone" class="form-label fw-bold">Emergency Contact Phone *</label>
                                <input type="text" class="form-control" id="instructor_emergency_contact_phone" name="instructor_emergency_contact_phone" required>
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section B: Professional Credentials</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="instructor_highest_qualification" class="form-label fw-bold">Highest Academic Qualification *</label>
                                <input type="text" class="form-control" id="instructor_highest_qualification" name="instructor_highest_qualification" required>
                            </div>
                            <div class="col-12">
                                <label for="instructor_professional_certifications" class="form-label fw-bold">Professional Certifications *</label>
                                <textarea class="form-control" id="instructor_professional_certifications" name="instructor_professional_certifications" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <label for="instructor_professional_memberships" class="form-label fw-bold">Memberships in Professional Bodies</label>
                                <textarea class="form-control" id="instructor_professional_memberships" name="instructor_professional_memberships" rows="4"></textarea>
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section C: Teaching & Subject Expertise</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="instructor_areas_of_specialization" class="form-label fw-bold">Areas of Specialization *</label>
                                <textarea class="form-control" id="instructor_areas_of_specialization" name="instructor_areas_of_specialization" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <label for="instructor_modules_qualified" class="form-label fw-bold">Modules/Topics Qualified to Teach *</label>
                                <textarea class="form-control" id="instructor_modules_qualified" name="instructor_modules_qualified" rows="4" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_teaching_experience_years" class="form-label fw-bold">Years of Teaching Experience *</label>
                                <input type="number" class="form-control" id="instructor_teaching_experience_years" name="instructor_teaching_experience_years" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_preferred_teaching_format" class="form-label fw-bold">Preferred Teaching Format *</label>
                                <select class="form-select" id="instructor_preferred_teaching_format" name="instructor_preferred_teaching_format" required>
                                    <option value="">Select Format</option>
                                    <option value="Live Webinars">Live Webinars</option>
                                    <option value="Recorded Sessions">Recorded Sessions</option>
                                    <option value="Case Studies">Case Studies</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="instructor_availability_schedule" class="form-label fw-bold">Availability Schedule *</label>
                                <textarea class="form-control" id="instructor_availability_schedule" name="instructor_availability_schedule" rows="4" required></textarea>
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section D: Institutional Affiliation</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="instructor_current_employer" class="form-label fw-bold">Current Employer</label>
                                <input type="text" class="form-control" id="instructor_current_employer" name="instructor_current_employer">
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_current_role" class="form-label fw-bold">Role/Title</label>
                                <input type="text" class="form-control" id="instructor_current_role" name="instructor_current_role">
                            </div>
                            <div class="col-12">
                                <label for="instructor_institutional_reference" class="form-label fw-bold">Institutional Reference</label>
                                <textarea class="form-control" id="instructor_institutional_reference" name="instructor_institutional_reference" rows="4"></textarea>
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section E: CPD Accreditation & Digital Verification</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="instructor_credentials_upload" class="form-label fw-bold">Upload Credentials *</label>
                                <input type="file" class="form-control" id="instructor_credentials_upload" name="instructor_credentials_upload" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="instructor_consent_qr_code" name="instructor_consent_qr_code" required>
                                    <label class="form-check-label" for="instructor_consent_qr_code">Consent to Embed QR Code on Certificates *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="instructor_consent_cpd_standards" name="instructor_consent_cpd_standards" required>
                                    <label class="form-check-label" for="instructor_consent_cpd_standards">Agree to CPD Accreditation Standards *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="instructor_consent_recording" name="instructor_consent_recording" required>
                                    <label class="form-check-label" for="instructor_consent_recording">Consent to Recording of Sessions *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_preferred_payment_method" class="form-label fw-bold">Preferred Payment Method</label>
                                <input type="text" class="form-control" id="instructor_preferred_payment_method" name="instructor_preferred_payment_method">
                            </div>
                        </div>

                        <h4 class="mt-5 mb-4">Section F: Faith & Legacy Integration (Optional)</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="instructor_faith_integration" name="instructor_faith_integration">
                                    <label class="form-check-label" for="instructor_faith_integration">Willingness to Integrate Ethical or Faith-Based Reflections</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="instructor_teaching_philosophy" class="form-label fw-bold">Teaching Philosophy</label>
                                <textarea class="form-control" id="instructor_teaching_philosophy" name="instructor_teaching_philosophy" rows="4"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="instructor_blessing_dedication" class="form-label fw-bold">Blessing or Dedication for Learners</label>
                                <textarea class="form-control" id="instructor_blessing_dedication" name="instructor_blessing_dedication" rows="4"></textarea>
                            </div>
                        </div>

                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-primary w-100">Register as Instructor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleForm() {
    const role = document.getElementById('role').value;
    document.getElementById('student-form').style.display = role === 'student' ? 'block' : 'none';
    document.getElementById('instructor-form').style.display = role === 'instructor' ? 'block' : 'none';
}
</script>

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