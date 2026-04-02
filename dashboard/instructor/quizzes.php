<?php
require_once __DIR__ . '/../../includes/config.php';

// 1. Instructor Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

// 2. Fetch Courses with detailed Quiz Metrics
$stmt = $pdo->prepare("
    SELECT 
        c.id, 
        c.title, 
        c.thumbnail,
        (SELECT COUNT(*) FROM assessments WHERE course_id = c.id AND type = 'quiz') as quiz_count,
        (SELECT COUNT(*) FROM assessment_submissions sub 
         JOIN assessments ass ON sub.assessment_id = ass.id 
         WHERE ass.course_id = c.id AND ass.type = 'quiz') as total_attempts,
        (SELECT ROUND(AVG(CASE WHEN sub.score >= ass.passing_score THEN 100 ELSE 0 END))
         FROM assessment_submissions sub 
         JOIN assessments ass ON sub.assessment_id = ass.id 
         WHERE ass.course_id = c.id AND ass.type = 'quiz' AND sub.status IN ('graded', 'submitted')) as avg_pass_rate
    FROM courses c
    WHERE c.instructor_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$instructor_id]);
$courses = $stmt->fetchAll();

// Calculate Global Metrics
$global_quizzes = array_sum(array_column($courses, 'quiz_count'));
$global_attempts = array_sum(array_column($courses, 'total_attempts'));

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
        width: 6px;
        height: 6px;
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
            margin-left: 16rem;
        }
    }

    .course-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
    }

    .glass-stat {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(10px);
    }

    .dark .glass-stat {
        background: rgba(15, 23, 42, 0.6);
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-12">
                <div class="space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600 dark:text-brand-500 mb-2 block">Curriculum Governance</span>
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic leading-none">
                        Quiz <span class="text-indigo-600 dark:text-indigo-400">Control</span>
                    </h1>
                    <div class="flex items-center gap-3 mt-4">
                        <span class="h-1 w-12 bg-indigo-600 dark:bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                            Managing <span class="text-slate-900 dark:text-slate-200 font-bold"><?= $global_quizzes ?></span> active examinations
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 bg-white dark:bg-slate-800 p-2 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm">
                    <div class="px-6 py-2 text-center border-r border-slate-50 dark:border-slate-700">
                        <p class="text-[8px] font-black uppercase text-slate-400 tracking-widest">Global Quizzes</p>
                        <p class="text-xl font-black text-indigo-600 dark:text-brand-500"><?= $global_quizzes ?></p>
                    </div>
                    <div class="px-6 py-2 text-center">
                        <p class="text-[8px] font-black uppercase text-slate-400 tracking-widest">Total Attempts</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white"><?= $global_attempts ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card bg-white dark:bg-slate-800 rounded-[3rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden flex flex-col group">

                        <div class="p-8 flex-1">
                            <div class="flex justify-between items-start mb-8">
                                <div class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner border border-slate-100 dark:border-slate-800 transition-transform group-hover:scale-110">
                                    <i class="fas fa-microchip text-xl"></i>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] font-black text-indigo-600 dark:text-brand-500 uppercase tracking-widest"><?= $course['quiz_count'] ?> Active</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter italic">Evaluation Units</span>
                                </div>
                            </div>

                            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 leading-tight uppercase italic">
                                <?= htmlspecialchars($course['title']) ?>
                            </h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mb-8">Course Module ID: #<?= $course['id'] ?></p>

                            <div class="glass-stat rounded-2xl p-4 flex justify-around items-center mb-8 border border-white/20 dark:border-slate-700">
                                <div class="text-center">
                                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Student Attempts</p>
                                    <p class="text-sm font-black text-slate-700 dark:text-slate-200"><?= $course['total_attempts'] ?></p>
                                </div>
                                <div class="w-px h-6 bg-slate-200 dark:bg-slate-700"></div>
                                <div class="text-center">
                                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Avg. Pass Rate</p>
                                    <p class="text-sm font-black text-emerald-500">
                                        <?= $course['avg_pass_rate'] !== null ? $course['avg_pass_rate'] . '%' : '0%' ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50/50 dark:bg-slate-900/30 border-t border-slate-50 dark:border-slate-700/50 grid grid-cols-2 gap-3">
                            <a href="quiz-builder.php?course_id=<?= $course['id'] ?>"
                                class="flex items-center justify-center gap-2 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-[1.5rem] font-black text-[9px] uppercase tracking-widest transition-all shadow-lg shadow-indigo-100 dark:shadow-none active:scale-95">
                                <i class="fas fa-plus-circle text-xs"></i> New Entry
                            </a>

                            <a href="course-quizzes.php?course_id=<?= $course['id'] ?>"
                                class="flex items-center justify-center gap-2 py-4 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-[1.5rem] font-black text-[9px] uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-95">
                                <i class="fas fa-layer-group text-xs text-indigo-500"></i> Registry
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($courses)): ?>
                    <div class="col-span-full py-40 text-center bg-white dark:bg-slate-800 rounded-[4rem] border-2 border-dashed border-slate-100 dark:border-slate-700">
                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-folder-open text-3xl text-slate-200 dark:text-slate-700"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase italic tracking-widest">No Environments Detected</h3>
                        <p class="text-slate-400 font-medium text-sm mt-2">You need to create a course before you can architect an assessment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
    // Theme Sync
    document.addEventListener('DOMContentLoaded', () => {
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }
    });
</script>
</body>

</html>