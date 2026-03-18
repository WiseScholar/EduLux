<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

$course_id = (int)($_GET['course_id'] ?? 0);
$reference = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_SPECIAL_CHARS);

if (!isset($_SESSION['user_id']) || !$course_id || !$reference) {
    header("Location: " . BASE_URL . "pages/courses");
    exit;
}

$user_id = $_SESSION['user_id'];

$enrollment_stmt = $pdo->prepare("
  SELECT 
    p.amount AS amount_paid, 
    p.transaction_ref, 
    p.status AS payment_status,
    e.enrolled_at, 
    c.title, 
    c.slug, 
    u.first_name,
    u.email
  FROM enrollments e
  JOIN courses c ON e.course_id = c.id
  JOIN users u ON e.user_id = u.id
  JOIN payments p ON p.id = e.payment_id 
  WHERE e.user_id = ? 
    AND e.course_id = ? 
    AND p.transaction_ref = ?      
    AND e.status = 'completed'
");

$enrollment_stmt->execute([$user_id, $course_id, $reference]);
$enrollment = $enrollment_stmt->fetch();

if (!$enrollment) {
    $_SESSION['error'] = "Payment record not found or enrollment incomplete.";
    header("Location: " . BASE_URL . "dashboard/student/my-courses.php");
    exit;
}

require_once ROOT_PATH . 'includes/header.php';
?>

<div class="relative min-h-screen bg-slate-950 flex items-center justify-center py-24 px-6 overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-brand-500/10 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-emerald-500/10 rounded-full blur-[120px] animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative z-10 w-full max-w-2xl">
        <div class="bg-white rounded-[3rem] shadow-2xl overflow-hidden print:shadow-none print:rounded-none">
            
            <div class="bg-brand-900 pt-12 pb-16 px-8 text-center relative">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                
                <div class="relative z-10">
                    <div class="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-emerald-500/20 animate-bounce">
                        <i class="fas fa-check text-3xl text-white"></i>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-[900] text-white tracking-tighter uppercase italic italic">Enrollment Confirmed</h1>
                    <p class="text-brand-500 font-black text-[10px] uppercase tracking-[0.3em] mt-2">Welcome to the program, <?= h($enrollment['first_name']) ?></p>
                </div>
            </div>

            <div class="px-8 md:px-12 py-10 -mt-8 bg-white rounded-t-[3rem] relative z-20">
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-6">
                        <div>
                            <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Program Selection</h2>
                            <p class="text-lg font-bold text-brand-900 leading-tight"><?= h($enrollment['title']) ?></p>
                        </div>
                        <div class="text-right">
                            <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Status</h2>
                            <span class="bg-emerald-50 text-emerald-600 text-[10px] font-black px-3 py-1 rounded-full uppercase">Verified</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 py-2">
                        <div>
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Transaction ID</h3>
                            <p class="text-sm font-mono text-brand-900 break-all"><?= h($enrollment['transaction_ref']) ?></p>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Date & Time</h3>
                            <p class="text-sm font-bold text-brand-900"><?= date('M j, Y • g:i A', strtotime($enrollment['enrolled_at'])) ?></p>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-[2rem] p-8 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-bold text-slate-500">Subtotal</span>
                            <span class="text-sm font-bold text-brand-900">₵<?= number_format($enrollment['amount_paid'], 2) ?></span>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                            <span class="text-base font-black uppercase text-brand-900 tracking-tighter">Total Amount Paid</span>
                            <span class="text-3xl font-[900] text-brand-900 tracking-tighter italic">₵<?= number_format($enrollment['amount_paid'], 2) ?></span>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 no-print pt-4">
                        <a href="<?= BASE_URL ?>dashboard/student/course-player.php?course_id=<?= $course_id ?>" 
                           class="flex items-center justify-center gap-3 bg-brand-900 text-white py-5 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-brand-500 hover:text-brand-900 transition-all shadow-xl shadow-brand-900/10">
                            <i class="fas fa-play-circle text-lg"></i> Start Learning
                        </a>
                        <button onclick="window.print()" 
                                class="flex items-center justify-center gap-3 bg-white border-2 border-slate-100 text-brand-900 py-5 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-slate-50 transition-all">
                            <i class="fas fa-print text-lg"></i> Download PDF
                        </button>
                    </div>

                    <p class="text-center text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] pt-6">
                        Receipt sent to: <span class="text-brand-900"><?= h($enrollment['email']) ?></span>
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-8 no-print">
            <a href="<?= BASE_URL ?>dashboard/student/my-courses.php" class="text-slate-500 hover:text-brand-500 text-xs font-black uppercase tracking-widest transition-colors">
                <i class="fas fa-th-large mr-2"></i> View My Course Library
            </a>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print, nav, footer { display: none !important; }
        body { background: white !important; }
        .bg-slate-950 { background: white !important; }
        .shadow-2xl { box-shadow: none !important; }
    }
</style>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>