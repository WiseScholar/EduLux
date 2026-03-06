<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'includes/dropdowns.php';
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
        // ... (Your existing validation and logic remains the same)
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

define('AUTH_PAGE', true);
require_once ROOT_PATH . 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-20 px-6 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-1/2 h-full bg-brand-900 skew-x-12 translate-x-32 hidden lg:block"></div>
    <div class="absolute inset-0 opacity-5 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>

    <div class="max-w-5xl mx-auto relative z-10">
        <div class="grid lg:grid-cols-12 gap-0 bg-white rounded-[3rem] shadow-2xl overflow-hidden border border-slate-100">
            
            <div class="lg:col-span-4 bg-brand-900 p-12 text-white flex flex-col justify-between">
                <div>
                    <img src="<?= BASE_URL ?>assets/images/logos/erm-logo.jpg" class="h-10 mb-12" alt="ERMI">
                    <h2 class="text-3xl font-[900] tracking-tighter italic uppercase leading-tight mb-6">
                        Start Your <br><span class="text-brand-500">Journey</span>
                    </h2>
                    <ul class="space-y-6 list-none p-0">
                        <li class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0 text-brand-500">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <p class="text-xs font-medium text-slate-300 mb-0 leading-relaxed">Global recognition through CPD UK accreditation.</p>
                        </li>
                        <li class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0 text-brand-500">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <p class="text-xs font-medium text-slate-300 mb-0 leading-relaxed">Secure, encrypted professional member portal.</p>
                        </li>
                    </ul>
                </div>
                
                <div class="mt-12 pt-8 border-t border-white/10">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Support Desk</p>
                    <a href="mailto:info@eduluxcpd.uk" class="text-brand-500 text-xs font-bold decoration-none">info@eduluxcpd.uk</a>
                </div>
            </div>

            <div class="lg:col-span-8 p-10 md:p-16">
                <div class="mb-10 flex justify-between items-end">
                    <div>
                        <h3 class="text-2xl font-[900] text-brand-900 tracking-tighter uppercase italic mb-1">Create Account</h3>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Enrollment Initialization</p>
                    </div>
                    <a href="login.php" class="text-[10px] font-black text-brand-500 uppercase tracking-widest hover:text-brand-900 transition-colors">Sign In Instead</a>
                </div>

                <?php if ($errors): ?>
                    <div class="mb-8 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-700 text-[11px] font-bold uppercase tracking-widest">
                        <?= implode('<br>', $errors) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-4">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="col-md-6">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">First Name *</label>
                        <input type="text" name="first_name" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm" value="<?= h($_POST['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Last Name *</label>
                        <input type="text" name="last_name" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm" value="<?= h($_POST['last_name'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Business Email Address *</label>
                        <input type="email" name="email" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm" value="<?= h($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Access Key *</label>
                        <input type="password" name="password" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Confirm Access Key *</label>
                        <input type="password" name="confirm_password" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm" required>
                    </div>

                    <div class="col-12">
                        <div class="bg-brand-900 text-slate-400 p-4 rounded-xl text-[10px] font-medium leading-relaxed">
                            <span class="text-brand-500 font-black uppercase tracking-widest block mb-1">Security Requirement:</span>
                            Min. 12 chars, uppercase, lowercase, number, and special character.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Country/Region *</label>
                        <select name="country" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm appearance-none" required>
                            <option value="">Select Region</option>
                            <?php foreach ($countries as $code => $name): ?>
                                <option value="<?= $code ?>" <?= (isset($_POST['country']) && $_POST['country'] == $code) ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Industry *</label>
                        <select name="industry" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm appearance-none" required>
                            <option value="">Select Industry</option>
                            <?php foreach ($industries as $ind): ?>
                                <option value="<?= $ind ?>" <?= (isset($_POST['industry']) && $_POST['industry'] == $ind) ? 'selected' : '' ?>><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Organization</label>
                        <input type="text" name="company" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm" placeholder="Current Company" value="<?= h($_POST['company'] ?? '') ?>">
                    </div>

                    <div class="col-md-5">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Code *</label>
                        <select name="phone_code" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm appearance-none" required>
                            <?php foreach ($phone_codes as $code => $info): ?>
                                <option value="<?= $info['code'] ?>" <?= (isset($_POST['phone_code']) && $_POST['phone_code'] == $info['code']) ? 'selected' : '' ?>>
                                    <?= $info['name'] ?> (<?= $info['code'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Phone Number *</label>
                        <input type="tel" name="phone_number" class="w-full bg-slate-50 border-0 rounded-xl py-4 px-6 text-brand-900 font-bold focus:ring-2 focus:ring-brand-500 transition-all text-sm" placeholder="000 000 000" value="<?= h($_POST['phone_number'] ?? '') ?>" required>
                    </div>

                    <div class="col-12 pt-4">
                        <div class="flex gap-3 mb-8 items-start">
                            <input class="w-5 h-5 rounded border-slate-200 mt-1" type="checkbox" id="terms" required>
                            <label class="text-[10px] font-medium text-slate-500 leading-normal" for="terms">
                                I confirm that all information provided is accurate and I agree to the <a href="#" class="text-brand-500 font-bold decoration-none">Terms of Enrollment</a> and Privacy Policy.
                            </label>
                        </div>
                        <button type="submit" class="w-full bg-brand-900 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:bg-brand-500 hover:text-brand-900 transition-all shadow-xl shadow-brand-900/10">
                            Create Member Account <i class="fas fa-chevron-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-8">
            <small class="text-[9px] font-black text-slate-400 uppercase tracking-widest">© <?= date('Y') ?> EduLux Professional Certification Hub</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>