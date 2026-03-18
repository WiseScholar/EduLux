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

// --- 2. FETCH FINANCIAL DATA ---
$total_spent_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_id = ? AND status = 'success'");
$total_spent_stmt->execute([$user_id]);
$total_spent = $total_spent_stmt->fetchColumn();

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

// --- 3. HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $bio = trim($_POST['bio'] ?? '');
        $avatar_filename = $user['avatar'];
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed) && $file['size'] <= 2_000_000) {
                $filename = 'user_' . $user_id . '_' . uniqid() . '.' . $ext;
                $target_path = ROOT_PATH . "assets/uploads/avatars/$filename";
                if (!is_dir(dirname($target_path))) mkdir(dirname($target_path), 0777, true);
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    if ($user['avatar'] && $user['avatar'] !== 'default.jpg') {
                         @unlink(ROOT_PATH . "assets/uploads/avatars/{$user['avatar']}");
                    }
                    $avatar_filename = $filename;
                }
            } else {
                $error = "Avatar upload failed. Max 2MB (JPG, PNG, WEBP).";
            }
        }

        if (!$error) {
            $pdo->prepare("UPDATE users SET first_name=?, last_name=?, bio=?, avatar=? WHERE id=?")
                ->execute([$firstName, $lastName, $bio, $avatar_filename, $user_id]);
            
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['user_avatar'] = BASE_URL . "assets/uploads/avatars/" . $avatar_filename;
            $msg = "Profile updated successfully!";
        }
    } 
    elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        $check_pass = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $check_pass->execute([$user_id]);
        $hash = $check_pass->fetchColumn();

        if (!password_verify($currentPassword, $hash)) {
            $error = "Incorrect current password.";
        } elseif (strlen($newPassword) < 8) {
            $error = "New password must be at least 8 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
                ->execute([$newHash, $user_id]);
            $msg = "Password updated successfully!";
        }
    }
    
    if ($msg || $error) {
         $active_tab = $_POST['active_tab'] ?? 'basics';
         header("Location: profile.php?msg=" . urlencode($msg ?? '') . "&error=" . urlencode($error ?? '') . "#{$active_tab}");
         exit;
    }
}

if (isset($_GET['msg'])) $msg = urldecode($_GET['msg']);
if (isset($_GET['error'])) $error = urldecode($_GET['error']);

require_once ROOT_PATH . 'includes/header.php';
?>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: { 900: '#002d72', 500: '#eab308' }
                }
            }
        }
    }
</script>

<style>
    @media (min-width: 1024px) {
        .main-content-wrapper { margin-left: 18rem; }
    }

    /* Standard CSS version of your custom-input */
    .custom-input {
        width: 100%;
        padding: 1rem 1.25rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        outline: none;
        transition: all 0.2s;
        border: 1px solid #e2e8f0; /* slate-200 */
        background-color: #f8fafc; /* slate-50 */
    }

    .dark .custom-input {
        background-color: #0f172a; /* slate-900 */
        border-color: #334155; /* slate-700 */
        color: white;
    }

    .custom-input:focus {
        border-color: #eab308; /* brand-500 */
        box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.2);
    }

    /* Active Tab Logic */
    .tab-btn.active {
        background-color: #002d72; /* brand-900 */
        color: white;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .dark .tab-btn.active {
        background-color: #eab308; /* brand-500 */
        color: #002d72;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-[#0f172a] transition-colors duration-500 flex" x-data="{ activeTab: window.location.hash.replace('#', '') || 'basics' }">
    
    <?php include 'student/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-6xl mx-auto w-full pb-24 lg:pb-12">

            <header class="mb-10">
                <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter">Account Settings</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mt-1">Manage your professional identity and security.</p>
            </header>

            <?php if ($msg): ?>
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-2xl text-xs font-black uppercase flex items-center gap-3">
                    <i class="fas fa-check-circle text-lg"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl text-xs font-black uppercase flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-lg"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <div class="lg:col-span-1 space-y-2">
                    <button @click="activeTab = 'basics'" :class="activeTab === 'basics' ? 'active' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="tab-btn w-full flex items-center gap-3 px-6 py-4 rounded-2xl transition-all font-black text-[10px] uppercase tracking-widest text-left">
                        <i class="fas fa-user-circle text-base"></i> Profile Basics
                    </button>
                    <button @click="activeTab = 'password'" :class="activeTab === 'password' ? 'active' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="tab-btn w-full flex items-center gap-3 px-6 py-4 rounded-2xl transition-all font-black text-[10px] uppercase tracking-widest text-left">
                        <i class="fas fa-shield-alt text-base"></i> Security
                    </button>
                    <button @click="activeTab = 'financial'" :class="activeTab === 'financial' ? 'active' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="tab-btn w-full flex items-center gap-3 px-6 py-4 rounded-2xl transition-all font-black text-[10px] uppercase tracking-widest text-left">
                        <i class="fas fa-wallet text-base"></i> Billing History
                    </button>
                </div>

                <div class="lg:col-span-3">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 lg:p-10 border border-slate-200/60 dark:border-slate-700 shadow-sm min-h-[500px]">
                        
                        <div x-show="activeTab === 'basics'" x-transition>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="active_tab" value="basics">

                                <div class="flex flex-col md:flex-row gap-10 items-start mb-10">
                                    <div class="relative group mx-auto md:mx-0">
                                        <img src="<?= $_SESSION['user_avatar'] ?? BASE_URL . 'assets/uploads/avatars/default.jpg' ?>" 
                                             class="w-32 h-32 rounded-[2rem] object-cover border-4 border-slate-50 dark:border-slate-900 shadow-xl" id="avatarPreview">
                                        <label for="avatarUpload" class="absolute -bottom-2 -right-2 w-10 h-10 bg-brand-500 text-brand-900 rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:scale-110 transition-transform">
                                            <i class="fas fa-camera text-sm"></i>
                                            <input type="file" name="avatar" id="avatarUpload" class="hidden" accept="image/*" onchange="previewAvatar(event)">
                                        </label>
                                    </div>
                                    <div class="flex-1 space-y-4 w-full">
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Email (System Fixed)</label>
                                            <input type="text" value="<?= htmlspecialchars($user['email']) ?>" class="custom-input opacity-60 cursor-not-allowed" disabled>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">First Name</label>
                                                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="custom-input" required>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Last Name</label>
                                                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="custom-input" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-8">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Professional Bio</label>
                                    <textarea name="bio" rows="4" class="custom-input resize-none" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="px-10 py-4 bg-brand-900 dark:bg-brand-500 text-white dark:text-brand-900 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl hover:opacity-90 transition-all">
                                    Save Profile Changes
                                </button>
                            </form>
                        </div>

                        <div x-show="activeTab === 'password'" x-transition>
                            <form method="POST" class="max-w-md space-y-6">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="update_password">
                                <input type="hidden" name="active_tab" value="password">

                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Current Password</label>
                                    <input type="password" name="current_password" class="custom-input" required>
                                </div>
                                <hr class="border-slate-100 dark:border-slate-700">
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">New Password</label>
                                    <input type="password" name="new_password" class="custom-input" required minlength="8">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="custom-input" required>
                                </div>

                                <button type="submit" class="px-10 py-4 bg-red-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl hover:bg-red-700 transition-all">
                                    Change Security Key
                                </button>
                            </form>
                        </div>

                        <div x-show="activeTab === 'financial'" x-transition>
                            <div class="flex flex-col md:flex-row justify-between items-center bg-slate-50 dark:bg-slate-900/50 p-8 rounded-3xl mb-10 border border-slate-100 dark:border-slate-700">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Career Investment</p>
                                    <h2 class="text-4xl font-black text-emerald-500 tracking-tighter">₵<?= number_format($total_spent, 2) ?></h2>
                                </div>
                                <i class="fas fa-piggy-bank text-5xl text-slate-200 dark:text-slate-700 mt-4 md:mt-0"></i>
                            </div>

                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 italic">Transaction Ledger</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="text-[9px] font-black uppercase text-slate-400 border-b border-slate-100 dark:border-slate-700">
                                            <th class="pb-4">Course</th>
                                            <th class="pb-4">Date</th>
                                            <th class="pb-4">Amount</th>
                                            <th class="pb-4 text-right">Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                                        <?php if (!empty($transactions)): foreach ($transactions as $tx): ?>
                                            <tr class="group">
                                                <td class="py-4 font-bold text-xs text-slate-700 dark:text-slate-300"><?= htmlspecialchars($tx['course_title']) ?></td>
                                                <td class="py-4 text-[10px] text-slate-500 font-medium"><?= date('M j, Y', strtotime($tx['paid_at'])) ?></td>
                                                <td class="py-4 text-xs font-black text-emerald-500">₵<?= number_format($tx['amount'], 2) ?></td>
                                                <td class="py-4 text-right">
                                                    <a href="<?= BASE_URL ?>pages/checkout/receipt.php?course_id=<?= $tx['course_id'] ?>&reference=<?= $tx['transaction_ref'] ?>" 
                                                       class="text-[9px] font-black uppercase text-brand-900 dark:text-brand-500 hover:underline" target="_blank">
                                                        Download <i class="fas fa-external-link-alt ml-1"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr>
                                                <td colspan="4" class="py-10 text-center text-slate-400 text-xs font-medium italic">No transactions found in your records.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'student/bottom-nav.php'; ?>

<script>
    // Theme Loader
    (function () {
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();

    function previewAvatar(event) {
        const [file] = event.target.files;
        if (file) {
            document.getElementById('avatarPreview').src = URL.createObjectURL(file);
        }
    }
</script>
</body>
</html>