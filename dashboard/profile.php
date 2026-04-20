<?php
// dashboard/profile.php - Central User Profile Management
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . LOGIN_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';
$msg = null;
$error = null;

// --- 1. FETCH CURRENT USER DATA ---
// Added 'created_at' to show "Member Since"
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, bio, avatar, created_at FROM users WHERE id = ?");
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
                    if ($user['avatar'] && $user['avatar'] !== 'default.jpg' && file_exists(ROOT_PATH . "assets/uploads/avatars/{$user['avatar']}")) {
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
            $msg = "Identity updated successfully!";
        }
    } elseif ($action === 'update_password') {
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
                    brand: {
                        900: '#002d72',
                        500: '#eab308'
                    }
                }
            }
        }
    }
</script>

<style>
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.2);
        border-radius: 10px;
    }

    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 18rem;
        }
    }

    /* Standard CSS Input */
    .custom-input {
        width: 100%;
        padding: 1rem 1.25rem;
        border-radius: 1.25rem;
        font-size: 0.875rem;
        font-weight: 600;
        outline: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
    }

    .dark .custom-input {
        background-color: #0f172a;
        border-color: #1e293b;
        color: white;
    }

    .custom-input:focus {
        background-color: white;
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .dark .custom-input:focus {
        background-color: #020617;
    }

    /* Tab Button Styles */
    .tab-btn {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-radius: 1.5rem;
        font-weight: 900;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        background-color: #002d72;
        color: white !important;
        box-shadow: 0 15px 30px -5px rgba(0, 45, 114, 0.2);
    }

    .dark .tab-btn.active {
        background-color: #eab308;
        color: #002d72 !important;
        box-shadow: 0 15px 30px -5px rgba(234, 179, 8, 0.2);
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex"
    x-data="{ activeTab: window.location.hash.replace('#', '') || 'basics' }">

    <?php include 'student/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-6xl mx-auto w-full pb-32">

            <header class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600 dark:text-brand-500 mb-2 block">Identity Terminal</span>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter leading-none">Settings</h1>
                </div>
            </header>

            <?php if ($msg): ?>
                <div class="mb-8 p-5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 text-emerald-700 dark:text-emerald-400 rounded-2xl text-xs font-black uppercase flex items-center gap-4">
                    <i class="fas fa-check-circle text-xl"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

                <div class="lg:col-span-3 space-y-3">
                    <button @click="activeTab = 'basics'" :class="activeTab === 'basics' ? 'active' : 'text-slate-400 hover:bg-white dark:hover:bg-slate-800'" class="tab-btn">
                        <span class="flex items-center gap-4"><i class="fas fa-id-card text-base"></i> Basics</span>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </button>
                    <button @click="activeTab = 'password'" :class="activeTab === 'password' ? 'active' : 'text-slate-400 hover:bg-white dark:hover:bg-slate-800'" class="tab-btn">
                        <span class="flex items-center gap-4"><i class="fas fa-shield-halved text-base"></i> Security</span>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </button>
                    <button @click="activeTab = 'financial'" :class="activeTab === 'financial' ? 'active' : 'text-slate-400 hover:bg-white dark:hover:bg-slate-800'" class="tab-btn">
                        <span class="flex items-center gap-4"><i class="fas fa-receipt text-base"></i> Billing</span>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </button>
                </div>

                <div class="lg:col-span-9">
                    <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-8 lg:p-12 border border-slate-100 dark:border-slate-700/50 shadow-sm min-h-[600px]">

                        <div x-show="activeTab === 'basics'" x-transition>
                            <form method="POST" enctype="multipart/form-data" class="space-y-12">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="active_tab" value="basics">

                                <div class="flex flex-col md:flex-row gap-12 items-center md:items-start">
                                    <div class="relative group">
                                        <img src="<?= $_SESSION['user_avatar'] ?? BASE_URL . 'assets/uploads/avatars/default.jpg' ?>"
                                            class="w-40 h-40 rounded-[2.5rem] object-cover border-8 border-slate-50 dark:border-slate-900 shadow-2xl" id="avatarPreview">
                                        <label for="avatarUpload" class="absolute -bottom-2 -right-2 w-12 h-12 bg-slate-900 dark:bg-brand-500 text-white dark:text-brand-900 rounded-2xl flex items-center justify-center cursor-pointer shadow-xl hover:scale-110 active:scale-95 transition-all">
                                            <i class="fas fa-camera text-sm"></i>
                                            <input type="file" name="avatar" id="avatarUpload" class="hidden" accept="image/*" onchange="previewAvatar(event)">
                                        </label>
                                    </div>

                                    <div class="flex-1 w-full space-y-6">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3 block ml-1">First Name</label>
                                                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="custom-input" required>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3 block ml-1">Last Name</label>
                                                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="custom-input" required>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3 block ml-1">Email Address</label>
                                            <input type="text" value="<?= htmlspecialchars($user['email']) ?>" class="custom-input opacity-50 bg-slate-100 dark:bg-slate-900" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3 block ml-1">Bio / About Me</label>
                                    <textarea name="bio" rows="4" class="custom-input resize-none"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                </div>

                                <div class="flex justify-end pt-6 border-t border-slate-50 dark:border-slate-700">
                                    <button type="submit" class="px-12 py-5 bg-slate-900 dark:bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.3em] shadow-2xl hover:bg-indigo-700 transition-all">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div x-show="activeTab === 'password'" x-cloak x-transition>
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-600 flex items-center justify-center">
                                    <i class="fas fa-shield-alt text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase italic tracking-tight">Security Protocol</h3>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last updated: System Managed</p>
                                </div>
                            </div>

                            <form method="POST" class="max-w-md space-y-6">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="update_password">
                                <input type="hidden" name="active_tab" value="password">

                                <div class="space-y-4">
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">Current Access Key</label>
                                        <input type="password" name="current_password" class="custom-input" placeholder="••••••••••••" required>
                                    </div>

                                    <div class="py-4">
                                        <div class="h-px bg-slate-100 dark:bg-slate-700 w-full"></div>
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">New Security Key</label>
                                        <input type="password" name="new_password" class="custom-input" placeholder="Minimum 8 characters" required minlength="8">
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">Confirm New Key</label>
                                        <input type="password" name="confirm_password" class="custom-input" placeholder="Repeat new key" required>
                                    </div>
                                </div>

                                <div class="pt-6">
                                    <button type="submit" class="w-full py-5 bg-red-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.3em] shadow-xl shadow-red-600/20 hover:bg-red-700 transition-all active:scale-[0.98]">
                                        Authorize Key Update
                                    </button>
                                    <p class="text-[9px] text-center text-slate-400 mt-4 uppercase font-bold tracking-tighter">Updating your password will require a new login session.</p>
                                </div>
                            </form>
                        </div>

                        <div x-show="activeTab === 'financial'" x-cloak x-transition>
                            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2.5rem] p-10 text-white shadow-2xl shadow-emerald-500/20 relative overflow-hidden mb-12">
                                <div class="relative z-10">
                                    <div class="flex items-center gap-3 mb-2 opacity-80">
                                        <i class="fas fa-crown text-xs text-yellow-300"></i>
                                        <p class="text-[10px] font-black uppercase tracking-[0.4em]">Total Career Investment</p>
                                    </div>
                                    <h2 class="text-5xl font-black tracking-tighter italic">₵<?= number_format($total_spent, 2) ?></h2>
                                </div>
                                <i class="fas fa-vault absolute -bottom-8 -right-8 text-[12rem] opacity-10"></i>
                            </div>

                            <div class="space-y-6">
                                <div class="flex items-center justify-between px-2">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic flex items-center gap-3">
                                        <span class="w-8 h-px bg-slate-200 dark:bg-slate-700"></span>
                                        Transaction Ledger
                                    </h3>
                                    <span class="text-[9px] font-black text-indigo-600 dark:text-brand-500 uppercase"><?= count($transactions) ?> Records</span>
                                </div>

                                <div class="overflow-hidden rounded-[2.5rem] border border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-white dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700">
                                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Curriculum Item</th>
                                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Date</th>
                                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Amount</th>
                                                <th class="p-6 text-right"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                            <?php if (!empty($transactions)): foreach ($transactions as $tx): ?>
                                                    <tr class="hover:bg-white dark:hover:bg-slate-800 transition-colors group">
                                                        <td class="p-6">
                                                            <p class="font-bold text-sm text-slate-700 dark:text-slate-200"><?= htmlspecialchars($tx['course_title']) ?></p>
                                                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">REF: <?= $tx['transaction_ref'] ?></p>
                                                        </td>
                                                        <td class="p-6 text-xs text-slate-500 font-medium text-center"><?= date('M j, Y', strtotime($tx['paid_at'])) ?></td>
                                                        <td class="p-6">
                                                            <span class="text-sm font-black text-emerald-500 italic">₵<?= number_format($tx['amount'], 2) ?></span>
                                                        </td>
                                                        <td class="p-6 text-right">
                                                            <a href="<?= BASE_URL ?>pages/checkout/receipt.php?reference=<?= $tx['transaction_ref'] ?>"
                                                                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-700 text-[9px] font-black uppercase tracking-widest text-indigo-600 dark:text-brand-500 rounded-xl border border-slate-100 dark:border-slate-600 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm">
                                                                Receipt <i class="fas fa-arrow-down text-[8px]"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;
                                            else: ?>
                                                <tr>
                                                    <td colspan="4" class="p-20 text-center">
                                                        <i class="fas fa-receipt text-4xl text-slate-200 dark:text-slate-700 mb-4 block"></i>
                                                        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest italic">No financial activity found.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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
    (function() {
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