<?php
require_once __DIR__ . '/../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php'; // Ensure h() and csrf functions are loaded

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/auth/login.php?return_to=checkout&course_id=" . ($_GET['course_id'] ?? ''));
    exit;
}

$user_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// Fetch User
$user_stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();
if (!$user) { die("User session error."); }

// Fetch Course
$course_stmt = $pdo->prepare("SELECT id, title, price, discount_price, thumbnail FROM courses WHERE id = ? AND status = 'published'");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

if (!$course) {
    http_response_code(404);
    die("Error: Course not found.");
}

// Check Enrollment (Active OR Completed)
$enrolled_stmt = $pdo->prepare("SELECT status FROM enrollments WHERE user_id = ? AND course_id = ? AND status != 'dropped'");
$enrolled_stmt->execute([$user_id, $course_id]);
if ($enrolled_stmt->fetchColumn()) {
    header("Location: " . BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}");
    exit;
}

$final_price = ($course['discount_price'] > 0 && $course['discount_price'] < $course['price']) ? $course['discount_price'] : $course['price'];

require_once ROOT_PATH . 'includes/header.php';
?>

<div class="bg-slate-50 min-h-screen pt-32 pb-20">
    <div class="max-w-5xl mx-auto px-6">
        
        <div class="mb-12">
            <h1 class="text-4xl font-[900] text-brand-900 tracking-tighter uppercase italic italic">Finalize Enrollment</h1>
            <p class="text-slate-500 font-medium">Complete your payment to get instant access to the program.</p>
        </div>

        <div class="grid lg:grid-cols-12 gap-12 items-start">
            
            <div class="lg:col-span-7 space-y-6">
                
                <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-brand-500/10 flex items-center justify-center text-brand-500">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-widest text-brand-900">Student Account</h3>
                            <p class="text-slate-500 text-sm"><?= h($user['email']) ?></p>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Log in as a different user? <a href="<?= BASE_URL ?>pages/auth/logout.php" class="text-brand-500 font-bold">Switch Account</a>
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
                    <h3 class="text-sm font-black uppercase tracking-widest text-brand-900 mb-6">Payment Method</h3>
                    <div class="flex items-center justify-between p-4 border-2 border-brand-500 bg-brand-50/50 rounded-2xl">
                        <div class="flex items-center gap-4">
                            <img src="https://paystack.com/assets/img/login/paystack-logo.png" class="h-5 opacity-80" alt="Paystack">
                            <span class="text-sm font-bold text-brand-900">Cards, Mobile Money, Bank</span>
                        </div>
                        <i class="fas fa-check-circle text-brand-500"></i>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-4">
                        <i class="fas fa-shield-alt text-brand-500 mb-2 block text-xl"></i>
                        <span class="text-[10px] font-black uppercase tracking-tighter text-slate-400">Secure SSL</span>
                    </div>
                    <div class="p-4">
                        <i class="fas fa-bolt text-brand-500 mb-2 block text-xl"></i>
                        <span class="text-[10px] font-black uppercase tracking-tighter text-slate-400">Instant Access</span>
                    </div>
                    <div class="p-4">
                        <i class="fas fa-certificate text-brand-500 mb-2 block text-xl"></i>
                        <span class="text-[10px] font-black uppercase tracking-tighter text-slate-400">CPD Credits</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 sticky top-32">
                <form action="<?= BASE_URL ?>payment/initialize.php" method="POST" class="bg-brand-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
                    <div class="absolute inset-0 opacity-5 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] pointer-events-none"></div>
                    
                    <h3 class="text-xl font-[900] italic uppercase tracking-tighter mb-8 relative z-10">Order Summary</h3>
                    
                    <div class="flex gap-4 mb-8 pb-8 border-b border-white/10 relative z-10">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden shrink-0 border border-white/20">
                            <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h4 class="font-bold text-sm leading-tight mb-1"><?= h($course['title']) ?></h4>
                            <span class="text-[10px] font-black text-brand-500 uppercase tracking-widest">Lifetime Access</span>
                        </div>
                    </div>

                    <input type="hidden" name="first_name" value="<?= h($user['first_name']) ?>">
                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                    <input type="hidden" name="email" value="<?= h($user['email']) ?>">
                    <input type="hidden" name="amount" value="<?= $final_price ?>">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="space-y-4 mb-10 relative z-10">
                        <div class="flex justify-between text-sm font-medium text-slate-400">
                            <span>Original Price</span>
                            <span class="<?= ($course['price'] != $final_price) ? 'line-through' : '' ?>">₵<?= number_format($course['price'], 2) ?></span>
                        </div>
                        
                        <?php if ($course['price'] != $final_price): ?>
                            <div class="flex justify-between text-sm font-black text-emerald-400">
                                <span>Discount Saved</span>
                                <span>- ₵<?= number_format($course['price'] - $final_price, 2) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="pt-4 border-t border-white/10 flex justify-between items-end">
                            <span class="text-sm font-black uppercase tracking-widest">Total Due</span>
                            <span class="text-4xl font-[900] text-brand-500 tracking-tighter">₵<?= number_format($final_price, 0) ?></span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-brand-500 text-brand-900 py-6 rounded-2xl font-black text-[12px] uppercase tracking-[0.2em] hover:bg-white transition-all transform hover:-translate-y-1 shadow-xl relative z-10">
                        Complete Purchase <i class="fas fa-arrow-right ml-2"></i>
                    </button>

                    <p class="text-[9px] text-center text-slate-400 mt-6 leading-relaxed relative z-10 uppercase tracking-widest font-bold">
                        Secure Transaction via Paystack Gateway
                    </p>
                </form>
            </div>

        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>