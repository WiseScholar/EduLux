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
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }

    @media (min-width: 1024px) { .main-content-wrapper { margin-left: 18rem; } }
    
    .stat-card {
        background: white;
        border: 1px solid #f1f5f9;
        border-radius: 2rem;
        padding: 2rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .stat-card { 
        background-color: #1e293b; 
        border-color: #334155;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
    }

    /* Animated Gold Gradient for Certificates */
    .cert-card {
        background: linear-gradient(135deg, #ffffff 0%, #fffcf0 100%);
        border-left: 6px solid #eab308;
        transition: all 0.4s ease;
    }
    .dark .cert-card {
        background: linear-gradient(135deg, #1e293b 0%, #1a2233 100%);
        border-left: 6px solid #eab308;
    }
    .cert-card:hover {
        border-left-width: 12px;
        box-shadow: 0 25px 50px -12px rgba(234, 179, 8, 0.15);
    }

    /* Confetti-like background for celebration */
    .celebration-bg {
        background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23eab308' fill-opacity='0.05' fill-rule='evenodd'%3E%3Ccircle cx='3' cy='3' r='3'/%3E%3Ccircle cx='13' cy='13' r='3'/%3E%3C/g%3E%3C/svg%3E");
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-[#0f172a] transition-colors duration-500 flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full pb-32">

            <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600 dark:text-brand-500 mb-2 block">Academic Records</span>
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter leading-none">
                        Achievement <span class="text-indigo-600 dark:text-indigo-400">Hub</span>
                    </h1>
                </div>
                <div class="flex items-center gap-3 px-6 py-3 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                    <div class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest italic">Status: Elite Scholar</span>
                </div>
            </header>

            <?php if ($celebration_message): ?>
            <div class="mb-16 relative overflow-hidden bg-slate-900 dark:bg-brand-900 rounded-[3rem] p-10 lg:p-16 text-white shadow-2xl celebration-bg">
                <div class="relative z-10 flex flex-col lg:flex-row items-center gap-12">
                    <div class="relative">
                        <div class="w-32 h-32 bg-brand-500 rounded-[2.5rem] flex items-center justify-center rotate-12 shadow-2xl">
                            <i class="fas fa-crown text-brand-900 text-6xl"></i>
                        </div>
                        <div class="absolute -top-4 -right-4 w-12 h-12 bg-white text-brand-900 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    <div class="text-center lg:text-left flex-1 space-y-4">
                        <h2 class="text-3xl lg:text-5xl font-black italic uppercase tracking-tighter">Academic Milestone Reached!</h2>
                        <p class="text-slate-300 text-lg font-medium max-w-2xl">
                            Excellence is not an act, but a habit. You've successfully mastered 
                            <span class="text-brand-500 font-bold underline decoration-brand-500/30"><?= htmlspecialchars($celebration_message['course_title']) ?></span>.
                        </p>
                        <div class="flex flex-wrap justify-center lg:justify-start gap-4 pt-4">
                            <a href="<?= BASE_URL ?>pages/certificate_generator.php?code=<?= $celebration_message['certificate_code'] ?>" 
                               target="_blank"
                               class="px-10 py-5 bg-brand-500 text-brand-900 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl hover:scale-105 transition-all">
                                Download Credential
                            </a>
                            <button class="px-10 py-5 bg-white/10 backdrop-blur-md text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] border border-white/10 hover:bg-white/20 transition-all">
                                Share Achievement
                            </button>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-brand-500/20 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-[100px]"></div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <i class="fas fa-flag-checkered text-xl"></i>
                        </div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Mastery</span>
                    </div>
                    <h3 class="text-6xl font-black text-slate-900 dark:text-white tracking-tighter"><?= $completed_count ?></h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase mt-3 tracking-widest">Finished Modules</p>
                </div>

                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <i class="fas fa-bolt text-xl"></i>
                        </div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Consistency</span>
                    </div>
                    <h3 class="text-6xl font-black text-slate-900 dark:text-white tracking-tighter"><?= $avg_progress ?>%</h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase mt-3 tracking-widest">Average Precision</p>
                </div>

                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 bg-brand-500/10 rounded-2xl flex items-center justify-center text-brand-500">
                            <i class="fas fa-stamp text-xl"></i>
                        </div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Registry</span>
                    </div>
                    <h3 class="text-6xl font-black text-slate-900 dark:text-white tracking-tighter"><?= $total_certificates ?></h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase mt-3 tracking-widest">Verified Diplomas</p>
                </div>
            </div>

            <div class="flex items-center justify-between mb-10">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter">Verified Credentials</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-slate-200 dark:from-slate-700 to-transparent mx-8"></div>
            </div>

            <?php if ($total_certificates > 0): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <?php foreach ($certificates as $cert): ?>
                        <div class="cert-card group rounded-[3rem] p-8 md:p-10 border border-slate-100 dark:border-slate-700/50 flex flex-col sm:flex-row items-center gap-10">
                            <div class="relative shrink-0">
                                <div class="w-24 h-24 bg-white dark:bg-slate-900 rounded-[2rem] flex items-center justify-center text-brand-500 text-4xl shadow-inner border border-slate-100 dark:border-slate-800">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-emerald-500 text-white rounded-xl flex items-center justify-center text-xs border-4 border-white dark:border-[#1a2233]">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            
                            <div class="flex-1 text-center sm:text-left">
                                <p class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-2">Issue Registry: <?= htmlspecialchars($cert['certificate_code']) ?></p>
                                <h4 class="text-xl font-black text-slate-900 dark:text-white leading-tight mb-4"><?= htmlspecialchars($cert['course_title']) ?></h4>
                                
                                <div class="flex flex-wrap justify-center sm:justify-start gap-4 mb-8">
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-500 dark:text-slate-400 rounded-lg uppercase tracking-widest">
                                        <i class="far fa-calendar-alt mr-1"></i> <?= date('M d, Y', strtotime($cert['issued_at'])) ?>
                                    </span>
                                    <span class="text-[9px] font-medium text-slate-400 mt-1 italic">Issued by: <?= htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) ?></span>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row items-center gap-4">
                                    <a href="<?= BASE_URL ?>pages/certificate_generator.php?code=<?= $cert['certificate_code'] ?>" 
                                       target="_blank"
                                       class="w-full sm:w-auto px-8 py-4 bg-slate-900 dark:bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all flex items-center justify-center gap-3 shadow-xl shadow-slate-200 dark:shadow-none">
                                        <i class="fas fa-download text-[10px]"></i> Get PDF Archive
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-24 bg-white dark:bg-slate-800 rounded-[4rem] border-2 border-dashed border-slate-100 dark:border-slate-700">
                    <div class="w-24 h-24 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-inner">
                        <i class="fas fa-award text-4xl text-indigo-200 dark:text-indigo-800"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter mb-4">The Hall of Fame is Awaiting</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm max-w-sm mx-auto mb-10 font-medium leading-relaxed">
                        Complete your current curriculum modules to unlock industry-recognized certifications and build your digital legacy.
                    </p>
                    <a href="my-courses.php" class="inline-flex items-center gap-4 px-10 py-5 bg-indigo-600 dark:bg-brand-500 text-white dark:text-brand-900 rounded-2xl font-black text-xs uppercase tracking-[0.3em] shadow-2xl hover:scale-105 transition-all">
                        Continue Learning <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    // Theme Loader
    (function () {
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>

</body>
</html>