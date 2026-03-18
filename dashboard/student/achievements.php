<?php
// dashboard/student/achievements.php - Comprehensive Achievement Hub
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL);
    exit;
}

$student_id = $_SESSION['user_id'];

// --- HANDLE CELEBRATION REDIRECT ---
$celebration_data = null;
if (isset($_GET['celebrate']) && isset($_GET['code'])) {
    $code = $_GET['code'];
    $cert_stmt = $pdo->prepare("
        SELECT c.title AS course_title, ce.certificate_code
        FROM certificates ce
        JOIN courses c ON ce.course_id = c.id
        WHERE ce.certificate_code = ? AND ce.user_id = ?
    ");
    $cert_stmt->execute([$code, $student_id]);
    $celebration_data = $cert_stmt->fetch();
    
    if ($celebration_data) {
        $_SESSION['achievement_celebrate'] = $celebration_data;
        header("Location: achievements.php");
        exit;
    }
}

$celebration_message = $_SESSION['achievement_celebrate'] ?? null;
unset($_SESSION['achievement_celebrate']);

// --- 1. FETCH OVERALL STATS ---
$stats_stmt = $pdo->prepare("
    SELECT COUNT(id) as enrolled_count FROM enrollments 
    WHERE user_id = ? AND status = 'completed'
");
$stats_stmt->execute([$student_id]);
$enrolled = $stats_stmt->fetchColumn() ?? 0;

// Progress Calculation logic
$courses_stmt = $pdo->prepare("
    SELECT c.id, 
    COALESCE(ROUND((SELECT COUNT(p.id) FROM course_progress p JOIN course_lessons l ON p.lesson_id = l.id JOIN course_sections s ON l.section_id = s.id WHERE s.course_id = c.id AND p.user_id = e.user_id AND p.is_completed = 1) * 100 / NULLIF((SELECT COUNT(l.id) FROM course_sections s JOIN course_lessons l ON l.section_id = s.id WHERE s.course_id = c.id), 0)), 0) AS progress_percentage
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.status = 'completed'
");
$courses_stmt->execute([$student_id]);
$all_courses_progress = $courses_stmt->fetchAll();

$completed_count = 0;
$total_progress_sum = 0;
foreach ($all_courses_progress as $course) {
    if ($course['progress_percentage'] >= 100) $completed_count++;
    $total_progress_sum += $course['progress_percentage'];
}
$avg_progress = ($enrolled > 0) ? round($total_progress_sum / $enrolled) : 0;

// --- 2. FETCH CERTIFICATES ---
$certificates_stmt = $pdo->prepare("
    SELECT ce.certificate_code, ce.issued_at, c.title AS course_title, u.first_name, u.last_name
    FROM certificates ce
    JOIN courses c ON ce.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE ce.user_id = ?
    ORDER BY ce.issued_at DESC
");
$certificates_stmt->execute([$student_id]);
$certificates = $certificates_stmt->fetchAll();
$total_certificates = count($certificates);

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    tailwind.config = { 
        darkMode: 'class',
        theme: { extend: { colors: { brand: { 900: '#002d72', 500: '#eab308' } } } }
    }
</script>

<style>
    @media (min-width: 1024px) { .main-content-wrapper { margin-left: 18rem; } }
    
    .stat-card {
        background: white;
        @apply border border-slate-100 dark:border-slate-700 dark:bg-slate-800 rounded-[2rem] p-8 transition-all duration-300;
    }
    .dark .stat-card { background-color: #1e293b; }

    /* Animated Gold Gradient for Certificates */
    .cert-gradient {
        background: linear-gradient(135deg, #ffffff 0%, #fefce8 100%);
        border-left: 6px solid #eab308;
    }
    .dark .cert-gradient {
        background: linear-gradient(135deg, #1e293b 0%, #161e2e 100%);
        border-left: 6px solid #eab308;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-[#0f172a] transition-colors duration-500 flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full pb-24 lg:pb-12">

            <header class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter">Achievement Hub</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mt-1">Your journey of excellence, documented.</p>
                </div>
                <div class="hidden md:block">
                    <span class="px-4 py-2 bg-brand-500/10 text-brand-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-brand-500/20">
                        <i class="fas fa-award mr-2"></i> Verified Learner
                    </span>
                </div>
            </header>

            <?php if ($celebration_message): ?>
            <div class="mb-12 relative overflow-hidden bg-brand-900 rounded-[2.5rem] p-8 lg:p-12 text-white shadow-2xl shadow-brand-900/20">
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                    <div class="w-24 h-24 bg-brand-500 rounded-3xl flex items-center justify-center rotate-12 shadow-lg">
                        <i class="fas fa-medal text-brand-900 text-5xl"></i>
                    </div>
                    <div class="text-center md:text-left flex-1">
                        <h2 class="text-2xl lg:text-3xl font-black italic uppercase tracking-tighter mb-2">Incredible Work!</h2>
                        <p class="text-slate-300 font-medium mb-6">You've officially conquered <span class="text-brand-500 font-bold"><?= htmlspecialchars($celebration_message['course_title']) ?></span>. Your certificate is ready for the world to see.</p>
                        <a href="<?= BASE_URL ?>pages/certificate_generator.php?code=<?= $celebration_message['certificate_code'] ?>" 
                           target="_blank"
                           class="inline-block px-8 py-4 bg-brand-500 text-brand-900 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl hover:scale-105 transition-transform">
                            Claim Certificate
                        </a>
                    </div>
                </div>
                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="stat-card group hover:border-brand-500/50">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center text-blue-600">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Completed</span>
                    </div>
                    <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter"><?= $completed_count ?></h3>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-2">Courses Finished</p>
                </div>

                <div class="stat-card group hover:border-brand-500/50">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center text-emerald-600">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Efficiency</span>
                    </div>
                    <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter"><?= $avg_progress ?>%</h3>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-2">Average Progress</p>
                </div>

                <div class="stat-card group hover:border-brand-500/50">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center text-amber-500">
                            <i class="fas fa-award"></i>
                        </div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Verified</span>
                    </div>
                    <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter"><?= $total_certificates ?></h3>
                    <p class="text-xs font-bold text-slate-500 uppercase mt-2">Total Certificates</p>
                </div>
            </div>

            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter">Your Verified Credentials</h2>
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700 mx-6 hidden sm:block"></div>
            </div>

            <?php if ($total_certificates > 0): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach ($certificates as $cert): ?>
                        <div class="cert-gradient group rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-700/50 flex flex-col sm:flex-row items-center gap-8 hover:shadow-xl hover:shadow-brand-500/5 transition-all">
                            <div class="relative">
                                <div class="w-20 h-20 bg-brand-500/10 rounded-full flex items-center justify-center text-brand-500 text-3xl">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="absolute -top-1 -right-1 w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-[10px] border-4 border-white dark:border-slate-800">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            
                            <div class="flex-1 text-center sm:text-left">
                                <h4 class="text-lg font-black text-slate-900 dark:text-white leading-tight mb-1"><?= htmlspecialchars($cert['course_title']) ?></h4>
                                <div class="flex flex-wrap justify-center sm:justify-start gap-4 mb-4">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                        <i class="far fa-calendar-alt mr-1"></i> <?= date('M Y', strtotime($cert['issued_at'])) ?>
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                        <i class="fas fa-fingerprint mr-1"></i> <?= htmlspecialchars($cert['certificate_code']) ?>
                                    </span>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row items-center gap-4">
                                    <a href="<?= BASE_URL ?>pages/certificate_generator.php?code=<?= $cert['certificate_code'] ?>" 
                                       target="_blank"
                                       class="px-6 py-3 bg-slate-900 dark:bg-white dark:text-slate-900 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:opacity-90 transition-all flex items-center gap-2">
                                        <i class="fas fa-download"></i> Download PDF
                                    </a>
                                    <span class="text-[10px] font-medium text-slate-400">Instructor: <?= htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20 bg-white dark:bg-slate-800 rounded-[3rem] border-2 border-dashed border-slate-100 dark:border-slate-700">
                    <div class="w-24 h-24 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-trophy text-4xl text-slate-200 dark:text-slate-700"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter mb-2">The Wall is Empty</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm max-w-sm mx-auto mb-8 font-medium">Complete your first course to unlock your verified certificate and start building your professional portfolio.</p>
                    <a href="<?= BASE_URL ?>dashboard/student/my-courses.php" class="inline-flex items-center gap-3 px-8 py-4 bg-brand-900 dark:bg-brand-500 text-white dark:text-brand-900 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl">
                        Back to My Courses <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    // Simple theme switcher persistence
    (function () {
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>