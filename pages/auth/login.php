<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

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
    die("<div class='min-h-screen flex items-center justify-center bg-slate-50 font-sans'>
            <div class='bg-white p-12 rounded-[2rem] shadow-xl text-center max-w-md border border-rose-100'>
                <i class='fas fa-user-lock text-rose-500 text-5xl mb-6'></i>
                <h2 class='text-2xl font-black text-slate-900 uppercase tracking-tighter italic mb-4'>Security Lockout</h2>
                <p class='text-slate-500 font-medium mb-0'>Too many failed attempts. For your protection, this portal is locked for {$remaining} minutes.</p>
            </div>
         </div>");
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security token mismatch. Please refresh.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    
                    // === VERIFICATION CHECK REMOVED ===
                    // User can now login even if verified = 0

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
                    $errors[] = "Invalid credential pair.";
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $errors[] = "A cryptographic system error occurred.";
            }
        }
    }
}

// We use a custom minimal header for auth pages to avoid navigation distractions
define('AUTH_PAGE', true);
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 flex items-center justify-center p-6 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-full opacity-5 pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-900 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-500 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-[1100px] grid lg:grid-cols-2 bg-white rounded-[3rem] shadow-2xl overflow-hidden relative z-10 border border-slate-100">
        
        <div class="hidden lg:flex bg-brand-900 p-16 flex-col justify-between relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
            
            <div class="relative z-10">
                <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg" class="h-12 mb-20" alt="ERMI Logo">
                <h2 class="text-4xl font-[900] text-white tracking-tighter uppercase italic leading-none mb-6">
                    Professional <br><span class="text-brand-500">Gateway</span>
                </h2>
                <p class="text-slate-400 font-medium text-lg leading-relaxed max-w-xs">
                    Access your global certifications, exam results, and professional CPD records.
                </p>
            </div>

            <div class="relative z-10 pt-10 border-t border-white/10">
                <div class="flex items-center gap-4">
                    <div class="flex -space-x-3">
                        <img class="w-10 h-10 rounded-full border-2 border-brand-900" src="https://i.pravatar.cc/100?u=1">
                        <img class="w-10 h-10 rounded-full border-2 border-brand-900" src="https://i.pravatar.cc/100?u=2">
                        <img class="w-10 h-10 rounded-full border-2 border-brand-900" src="https://i.pravatar.cc/100?u=3">
                    </div>
                    <p class="text-[10px] font-black text-brand-500 uppercase tracking-widest mb-0">Join 5,000+ Certified Professionals</p>
                </div>
            </div>
        </div>

        <div class="p-10 md:p-20 flex flex-col justify-center">
            <div class="mb-10">
                <h3 class="text-3xl font-[900] text-brand-900 tracking-tighter uppercase italic mb-2">Sign In</h3>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-[0.2em]">Authorized Personnel Only</p>
            </div>

            <?php if($errors): ?>
                <div class="mb-8 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-700 text-xs font-bold uppercase tracking-widest">
                    <?php foreach($errors as $error) echo "<p class='mb-0'>$error</p>"; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                <div class="space-y-2">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.3em]">Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-slate-300">
                            <i class="fas fa-envelope text-xs"></i>
                        </span>
                        <input type="email" name="email" 
                               class="w-full bg-slate-50 border-0 rounded-2xl py-5 pl-12 pr-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all placeholder:text-slate-300" 
                               placeholder="name@organization.com"
                               value="<?= h($email) ?>" required autofocus>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-end">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em]">Password</label>
                        <a href="forgot-password.php" class="text-[9px] font-black text-brand-500 uppercase tracking-widest hover:text-brand-900 transition-colors">Forgot?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-slate-300">
                            <i class="fas fa-shield-alt text-xs"></i>
                        </span>
                        <input type="password" name="password" 
                               class="w-full bg-slate-50 border-0 rounded-2xl py-5 pl-12 pr-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all placeholder:text-slate-300" 
                               placeholder="••••••••"
                               required>
                    </div>
                </div>

                <button type="submit" class="w-full bg-brand-900 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:bg-brand-500 hover:text-brand-900 transition-all shadow-xl shadow-brand-900/10">
                    Login <i class="fas fa-lock-open ms-2"></i>
                </button>

                <div class="relative py-4 flex items-center">
                    <div class="flex-grow border-t border-slate-100"></div>
                    <span class="flex-shrink mx-4 text-[9px] font-black text-slate-300 uppercase tracking-widest">New Member?</span>
                    <div class="flex-grow border-t border-slate-100"></div>
                </div>

                <a href="register.php" class="block w-full text-center border-2 border-slate-100 text-brand-900 py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:bg-slate-50 transition-all">
                    Join us now
                </a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>