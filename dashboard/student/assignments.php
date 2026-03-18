<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Fetch Assignments
$stmt = $pdo->prepare("
    SELECT 
        a.id, 
        a.title, 
        a.due_date, 
        a.type,
        a.description,
        c.title as course_title, 
        s.status as submission_status, 
        s.score,
        s.submitted_at
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN assessment_submissions s ON a.id = s.assessment_id AND s.user_id = ?
    WHERE e.user_id = ?
    ORDER BY 
        CASE WHEN s.status IS NULL THEN 0 ELSE 1 END, -- Show unsubmitted first
        a.due_date ASC
");
$stmt->execute([$user_id, $user_id]);
$all_assignments = $stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

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

    @media (max-width: 1024px) {
        main { 
            padding-bottom: calc(120px + env(safe-area-inset-bottom)) !important; 
        }
    }

    .assignment-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .assignment-card:hover {
        transform: scale(1.01);
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.05);
    }
</style>

<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-500 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
                <div class="space-y-2">
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic">
                        My <span class="text-brand-900 dark:text-brand-500">ASSIGNMENTS</span>
                    </h1>
                    <div class="flex items-center gap-3">
                        <span class="h-1 w-12 bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                            Managing <span class="text-slate-900 dark:text-slate-200 font-bold"><?= count($all_assignments) ?></span> active assignments
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <?php if (empty($all_assignments)): ?>
                    <div class="bg-white dark:bg-slate-800/50 rounded-[4rem] border-2 border-dashed border-slate-200 dark:border-slate-700 p-20 text-center backdrop-blur-sm">
                        <div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
                            <i class="fas fa-clipboard-check text-3xl text-slate-300"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase italic mb-2">All Clear</h3>
                        <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto font-medium">
                            No pending assignments found. Enjoy your free time or explore new courses!
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_assignments as $task): 
                        $due_timestamp = strtotime($task['due_date']);
                        $is_urgent = (!$task['submission_status'] && $due_timestamp && ($due_timestamp - time() < 172800)); // Within 48 hours
                    ?>
                        <div class="assignment-card bg-white dark:bg-slate-800 p-6 lg:p-8 rounded-[2.5rem] border border-slate-200/60 dark:border-slate-700/50 flex flex-col lg:flex-row lg:items-center justify-between gap-8">

                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 rounded-[1.5rem] bg-slate-50 dark:bg-slate-700/50 flex items-center justify-center text-brand-900 dark:text-brand-500 shadow-inner border border-slate-100 dark:border-slate-600">
                                    <i class="fas <?= $task['type'] === 'quiz' ? 'fa-stopwatch' : 'fa-project-diagram' ?> text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-black text-xl text-slate-900 dark:text-white leading-tight mb-1">
                                        <?= htmlspecialchars($task['title']) ?>
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-brand-900 dark:text-brand-500">
                                            <?= htmlspecialchars($task['course_title']) ?>
                                        </span>
                                        <span class="h-1 w-1 bg-slate-300 rounded-full"></span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">
                                            <?= htmlspecialchars($task['type']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-8 lg:gap-16">
                                <div class="min-w-[120px]">
                                    <?php if ($task['submission_status']): ?>
                                        <p class="text-[10px] font-black uppercase text-emerald-500 mb-1 tracking-widest flex items-center gap-1">
                                            <i class="fas fa-check-double"></i> Verified
                                        </p>
                                        <p class="text-sm font-black text-slate-700 dark:text-slate-300">
                                            <?= date('M d, Y', strtotime($task['submitted_at'])) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-[10px] font-black uppercase <?= $is_urgent ? 'text-red-500 animate-pulse' : 'text-slate-400' ?> mb-1 tracking-widest">
                                            <i class="far fa-calendar-alt mr-1"></i> Due Date
                                        </p>
                                        <p class="text-sm font-black <?= $is_urgent ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' ?>">
                                            <?= $task['due_date'] ? date('M d, Y', $due_timestamp) : 'No Deadline' ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="min-w-[120px]">
                                    <?php if ($task['submission_status'] === 'graded'): ?>
                                        <div class="px-5 py-2.5 bg-brand-900 dark:bg-brand-500 text-white dark:text-brand-900 rounded-2xl text-[11px] font-black uppercase tracking-widest text-center shadow-lg shadow-brand-500/20">
                                            Score: <?= (int) $task['score'] ?>%
                                        </div>
                                    <?php elseif ($task['submission_status'] === 'pending' || $task['submission_status'] === 'submitted'): ?>
                                        <div class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 rounded-2xl text-[10px] font-black uppercase tracking-widest text-center border border-slate-200 dark:border-slate-600">
                                            In Review
                                        </div>
                                    <?php else: ?>
                                        <div class="px-5 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-2xl text-[10px] font-black uppercase tracking-widest text-center border border-red-100 dark:border-red-900/30">
                                            Pending
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <a href="view-assessment.php?id=<?= $task['id'] ?>" class="w-full lg:w-auto inline-flex items-center justify-center bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all hover:opacity-90 active:scale-95 shadow-xl shadow-slate-900/10">
                                    View Task
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    // Theme & Sidebar Toggle Logic
    document.addEventListener('DOMContentLoaded', () => {
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }

        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', (e) => {
                e.preventDefault();
                sidebar.classList.toggle('-translate-x-full');
                if(overlay) overlay.classList.toggle('hidden');
            });
        }
    });
</script>

</body>
</html>