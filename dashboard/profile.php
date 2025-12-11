<?php
// dashboard/profile.php - Central User Profile Management
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';
$msg = null;
$error = null;

// --- 1. FETCH CURRENT USER DATA ---
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, bio, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: " . BASE_URL . "pages/auth/login.php");
    exit;
}

// --- 2. FETCH FINANCIAL DATA (New Block) ---
// Total Spent
$total_spent_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_id = ? AND status = 'success'");
$total_spent_stmt->execute([$user_id]);
$total_spent = $total_spent_stmt->fetchColumn();

// Transaction History
$transactions_stmt = $pdo->prepare("
    SELECT p.transaction_ref, p.amount, p.paid_at, c.title AS course_title, c.id AS course_id 
    FROM payments p
    JOIN courses c ON p.course_id = c.id
    WHERE p.user_id = ? AND p.status = 'success'
    ORDER BY p.paid_at DESC
");
$transactions_stmt->execute([$user_id]);
$transactions = $transactions_stmt->fetchAll();


$csrf_token = generate_csrf_token();

// --- 3. HANDLE FORM SUBMISSIONS (Unified POST Handler) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    
    $action = $_POST['action'] ?? '';

    // --- A. Update Basic Profile Info ---
    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $bio = trim($_POST['bio'] ?? '');
        
        $avatar_filename = $user['avatar'];
        
        // Handle Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed) && $file['size'] <= 2_000_000) { // Max 2MB
                $filename = 'user_' . $user_id . '_' . uniqid() . '.' . $ext;
                $target_path = ROOT_PATH . "assets/uploads/avatars/$filename";
                
                if (!is_dir(dirname($target_path))) mkdir(dirname($target_path), 0777, true);
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Delete old avatar if it exists and is not the default
                    if ($user['avatar'] && $user['avatar'] !== 'default.jpg') {
                         @unlink(ROOT_PATH . "assets/uploads/avatars/{$user['avatar']}");
                    }
                    $avatar_filename = $filename;
                }
            } else {
                $error = "Avatar upload failed. Max size 2MB, allowed formats: JPG, PNG, WEBP.";
            }
        }

        if (!$error) {
            $pdo->prepare("UPDATE users SET first_name=?, last_name=?, bio=?, avatar=? WHERE id=?")
                ->execute([$firstName, $lastName, $bio, $avatar_filename, $user_id]);
            
            // Update session variables
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['user_avatar'] = BASE_URL . "assets/uploads/avatars/" . $avatar_filename;
            
            $msg = "Profile updated successfully!";
        }
    } 
    
    // --- B. Update Password ---
    elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        $check_pass = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $check_pass->execute([$user_id]);
        $hash = $check_pass->fetchColumn();

        if (!password_verify($currentPassword, $hash)) {
            $error = "The current password you entered is incorrect.";
        } elseif (strlen($newPassword) < 8) {
            $error = "New password must be at least 8 characters long.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New password and confirmation do not match.";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
                ->execute([$newHash, $user_id]);
            $msg = "Password updated successfully!";
        }
    }
    
    // Refresh user data after submission (PRG pattern)
    if ($msg || $error) {
         // Preserve which tab the user was on
         $active_tab = $_POST['active_tab'] ?? 'basics';
         header("Location: profile.php?msg=" . urlencode($msg ?? '') . "&error=" . urlencode($error ?? '') . "#{$active_tab}");
         exit;
    }
}

// Retrieve message/error from GET parameters after redirect
if (isset($_GET['msg'])) $msg = urldecode($_GET['msg']);
if (isset($_GET['error'])) $error = urldecode($_GET['error']);

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    .profile-page-container { padding-top: 140px; padding-bottom: 80px; }
    .profile-tabs .nav-link { color: #6c757d; font-weight: 600; }
    .profile-tabs .nav-link.active { color: var(--primary); border-color: var(--primary) !important; border-bottom: 2px solid var(--primary) !important; }
    
    .profile-card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .profile-avatar-display { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 4px solid var(--primary); }
</style>

<section class="profile-page-container">
    <div class="container">
        <h1 class="fw-bold mb-4">Account Settings</h1>
        <p class="lead text-muted">Manage your profile information and security settings.</p>

        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="profile-card mt-5">
            <ul class="nav nav-tabs profile-tabs" id="profileTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" data-bs-target="#basics" type="button">Account Basics</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-bs-target="#password" type="button">Security & Password</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-bs-target="#financial" type="button">Financial History</a></li>
            </ul>

            <div class="tab-content pt-4" id="profileTabContent">
                
                <div class="tab-pane fade show active" id="basics" role="tabpanel">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="active_tab" value="basics">

                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <label for="avatarUpload" class="d-block mb-3 cursor-pointer">
                                    <img src="<?= $_SESSION['user_avatar'] ?? BASE_URL . 'assets/uploads/avatars/default.jpg' ?>" 
                                         class="profile-avatar-display" id="avatarPreview" alt="Profile Avatar">
                                </label>
                                <input type="file" name="avatar" id="avatarUpload" class="form-control" accept="image/*" onchange="previewAvatar(event)">
                                <small class="text-muted">Max 2MB. Click image to change.</small>
                            </div>
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <label class="form-label">Email Address (Non-Editable)</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="firstName" class="form-label">First Name</label>
                                        <input type="text" name="first_name" id="firstName" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lastName" class="form-label">Last Name</label>
                                        <input type="text" name="last_name" id="lastName" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="bio" class="form-label">Biography/About Me</label>
                            <textarea name="bio" id="bio" class="form-control" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <small class="text-muted">A short professional description (especially useful if you are an Instructor).</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="password" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="update_password">
                        <input type="hidden" name="active_tab" value="password">
                        
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="currentPassword" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="8">
                            <small class="text-muted">Must be at least 8 characters long.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-danger btn-lg">Update Password</button>
                    </form>
                </div>
                
                <div class="tab-pane fade" id="financial" role="tabpanel">
                    <div class="mb-4">
                        <h4 class="fw-bold mb-3">Total Investment:</h4>
                        <h1 class="display-4 fw-bolder text-success">₵<?= number_format($total_spent, 2) ?></h1>
                        <p class="text-muted">This represents the total amount spent on course enrollments to date.</p>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h4 class="fw-bold mb-3">Recent Transactions</h4>
                    <?php if (!empty($transactions)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Date Paid</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tx['course_title']) ?></td>
                                    <td><?= date('M j, Y', strtotime($tx['paid_at'])) ?></td>
                                    <td><span class="fw-bold text-success">₵<?= number_format($tx['amount'], 2) ?></span></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>pages/checkout/receipt.php?course_id=<?= $tx['course_id'] ?? '' ?>&reference=<?= $tx['transaction_ref'] ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                           View Receipt
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No successful payments recorded yet.</div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
</section>

<script>
    function previewAvatar(event) {
        const [file] = event.target.files;
        if (file) {
            document.getElementById('avatarPreview').src = URL.createObjectURL(file);
        }
    }
    
    // Retain active tab state after page reload (due to form submission)
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        if (hash) {
            const tabElement = document.querySelector(`.profile-tabs a[data-bs-target="${hash}"]`);
            if (tabElement) {
                const tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }
    });
</script>

<?php
require_once ROOT_PATH . 'includes/footer.php';
?>