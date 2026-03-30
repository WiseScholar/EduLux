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

    @media (max-width: 1024px) {
        main {
            padding-bottom: calc(120px + env(safe-area-inset-bottom)) !important;
        }
    }

    .assignment-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .assignment-card:hover {
        transform: scale(1.01) translateY(-2px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
    }

    /* Urgent deadline pulse glow */
    .urgent-glow {
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
                <div class="space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600 dark:text-brand-500 mb-2 block">Performance Registry</span>
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic leading-none">
                        My <span class="text-indigo-600 dark:text-indigo-400">Assignments</span>
                    </h1>
                    <div class="flex items-center gap-3 mt-4">
                        <span class="h-1 w-12 bg-indigo-600 dark:bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                            Tracking <span class="text-slate-900 dark:text-slate-200 font-bold"><?= count($all_assignments) ?></span> academic evaluations
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8">
                <?php if (empty($all_assignments)): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-[4rem] border-2 border-dashed border-slate-200 dark:border-slate-700 p-24 text-center">
                        <div class="w-24 h-24 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-inner">
                            <i class="fas fa-clipboard-check text-3xl text-indigo-200 dark:text-indigo-800"></i>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter italic mb-4">All Systems Clear</h3>
                        <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto font-medium text-lg leading-relaxed">
                            No pending tasks. You're completely up to date with your curriculum.
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_assignments as $task):
                        $due_date_raw = $task['due_date'] ?? null;
                        $due_timestamp = $due_date_raw ? strtotime($due_date_raw) : null;
                        $is_urgent = (!$task['submission_status'] && $due_timestamp && ($due_timestamp - time() < 172800) && ($due_timestamp > time()));
                        $is_late = (!$task['submission_status'] && $due_timestamp && ($due_timestamp < time()));
                    ?>
                        <div class="assignment-card bg-white dark:bg-slate-800 p-8 lg:p-10 rounded-[3rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-10">

                            <div class="flex items-center gap-8 flex-1">
                                <div class="w-20 h-20 shrink-0 rounded-[2rem] bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner border border-slate-100 dark:border-slate-800">
                                    <i class="fas <?= $task['type'] === 'quiz' ? 'fa-bolt' : 'fa-scroll' ?> text-3xl"></i>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-500">
                                        <?= htmlspecialchars($task['course_title']) ?>
                                    </p>
                                    <h3 class="font-black text-2xl text-slate-900 dark:text-white leading-tight">
                                        <?= htmlspecialchars($task['title']) ?>
                                    </h3>
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-200 dark:border-slate-600">
                                            <?= strtoupper(htmlspecialchars($task['type'])) ?>
                                        </span>
                                        <?php if ($is_urgent): ?>
                                            <span class="flex items-center gap-1.5 text-[9px] font-black uppercase text-red-500 animate-pulse">
                                                <i class="fas fa-exclamation-triangle"></i> Urgent Action
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-10 lg:gap-16">
                                <div class="min-w-[140px]">
                                    <?php if ($task['submission_status']): ?>
                                        <label class="block text-[9px] font-black uppercase text-emerald-500 mb-2 tracking-[0.2em]">Submission Status</label>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-700 dark:text-slate-200">Verified</span>
                                            <span class="text-[10px] text-slate-400 italic"><?= date('M d, Y', strtotime($task['submitted_at'])) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <label class="block text-[9px] font-black uppercase <?= ($is_urgent || $is_late) ? 'text-red-500' : 'text-slate-400' ?> mb-2 tracking-[0.2em]">
                                            <?= $is_late ? 'Overdue Since' : 'Target Deadline' ?>
                                        </label>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black <?= ($is_urgent || $is_late) ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' ?>">
                                                <?= $task['due_date'] ? date('M d, Y', $due_timestamp) : 'No Deadline' ?>
                                            </span>
                                            <span class="text-[10px] text-slate-400 italic"><?= $task['due_date'] ? date('h:i A', $due_timestamp) : '--:--' ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-6 w-full sm:w-auto">
                                    <?php if ($task['submission_status'] === 'graded'): ?>
                                        <div class="px-6 py-3 bg-indigo-600 dark:bg-indigo-500 text-white rounded-2xl flex flex-col items-center justify-center min-w-[100px] shadow-lg shadow-indigo-200 dark:shadow-none">
                                            <span class="text-[8px] font-black uppercase tracking-widest opacity-80">Grade</span>
                                            <span class="text-lg font-black leading-none"><?= (int) $task['score'] ?>%</span>
                                        </div>
                                    <?php elseif ($task['submission_status']): ?>
                                        <div class="px-6 py-3 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-2xl border border-slate-200 dark:border-slate-600 text-[10px] font-black uppercase tracking-widest text-center">
                                            In Review
                                        </div>
                                    <?php endif; ?>

                                    <a href="view-assessment.php?id=<?= $task['id'] ?>" class="flex-1 sm:flex-none inline-flex items-center justify-center bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-10 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.3em] transition-all hover:opacity-90 shadow-xl shadow-slate-900/10">
                                        <?= ($task['submission_status']) ? 'Review Work' : 'View Task' ?>
                                    </a>
                                </div>
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
                if (overlay) overlay.classList.toggle('hidden');
            });
        }
    });
</script>

</body>

</html>