<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . LOGIN_URL);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Quizzes with status and scores
$stmt = $pdo->prepare("
    SELECT 
        a.*, 
        c.title as course_title, 
        s.id as submission_id,
        s.status as sub_status, 
        s.score, 
        s.submitted_at,
        s.started_at,
        (SELECT COUNT(*) FROM quiz_questions WHERE assessment_id = a.id) as real_question_count
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN assessment_submissions s ON a.id = s.assessment_id AND s.user_id = ?
    WHERE e.user_id = ? AND a.type = 'quiz'
    ORDER BY 
        CASE WHEN s.status IS NULL THEN 0 ELSE 1 END,
        a.due_date ASC
");
$stmt->execute([$user_id, $user_id]);
$quizzes = $stmt->fetchAll();

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
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }

    @media (min-width: 1024px) {
        .main-content-wrapper { margin-left: 18rem; }
    }

    .quiz-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .quiz-card:hover {
        transform: scale(1.01) translateY(-2px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
                <div class="space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600 dark:text-brand-500 mb-2 block">Strategic Evaluation</span>
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic leading-none">
                        Examination <span class="text-indigo-600 dark:text-indigo-400">Portal</span>
                    </h1>
                    <div class="flex items-center gap-3 mt-4">
                        <span class="h-1 w-12 bg-indigo-600 dark:bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                            Awaiting <span class="text-slate-900 dark:text-slate-200 font-bold"><?= count($quizzes) ?></span> active assessments
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8">
                <?php if (empty($quizzes)): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-[4rem] border-2 border-dashed border-slate-200 dark:border-slate-700 p-24 text-center">
                        <div class="w-24 h-24 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-8">
                            <i class="fas fa-bolt text-3xl text-indigo-200 dark:text-indigo-800"></i>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter italic mb-4">No Quizzes Scheduled</h3>
                        <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto font-medium text-lg italic">The board is currently clear.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($quizzes as $q): 
                        $is_done = ($q['sub_status'] === 'graded' || $q['sub_status'] === 'submitted');
                        $due_date_raw = $q['due_date'] ?? null;
                        $due_timestamp = $due_date_raw ? strtotime($due_date_raw) : null;
                        $is_urgent = (!$is_done && $due_timestamp && ($due_timestamp - time() < 86400)); // 24h for quizzes
                    ?>
                        <div class="quiz-card bg-white dark:bg-slate-800 p-8 lg:p-10 rounded-[3rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-10">

                            <div class="flex items-center gap-8 flex-1">
                                <div class="w-20 h-20 shrink-0 rounded-[2rem] bg-indigo-50 dark:bg-slate-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner border border-indigo-100 dark:border-slate-800 relative">
                                    <i class="fas fa-bolt text-3xl"></i>
                                    <?php if(!$is_done): ?>
                                        <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-500 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-4 w-4 bg-brand-500 border-2 border-white dark:border-slate-800"></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-500">
                                        <?= htmlspecialchars($q['course_title']) ?>
                                    </p>
                                    <h3 class="font-black text-2xl text-slate-900 dark:text-white leading-tight">
                                        <?= htmlspecialchars($q['title']) ?>
                                    </h3>
                                    <div class="flex items-center gap-4">
                                        <span class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            <i class="far fa-clock"></i> <?= $q['duration'] ?> Minutes
                                        </span>
                                        <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            <?= $q['real_question_count'] ?> Questions
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-10 lg:gap-16">
                                <div class="min-w-[140px]">
                                    <?php if ($is_done): 
                                        $duration_text = '--';
                                        if ($q['started_at'] && $q['submitted_at']) {
                                            $start = new DateTime($q['started_at']);
                                            $end = new DateTime($q['submitted_at']);
                                            $diff = $start->diff($end);
                                            $duration_text = ($diff->i > 0 ? $diff->i . 'm ' : '') . $diff->s . 's';
                                        }
                                    ?>
                                        <label class="block text-[9px] font-black uppercase text-emerald-500 mb-2 tracking-[0.2em]">COMPLETED</p>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-700 dark:text-slate-200">
                                                Spent: <span class="text-indigo-600 dark:text-indigo-400"><?= $duration_text ?></span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 italic"><?= date('M d, h:i A', strtotime($q['submitted_at'])) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <label class="block text-[9px] font-black uppercase <?= $is_urgent ? 'text-red-500' : 'text-slate-400' ?> mb-2 tracking-[0.2em]">
                                            Examination Window
                                        </label>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black <?= $is_urgent ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' ?>">
                                                <?= $due_timestamp ? date('M d, Y', $due_timestamp) : 'Open Entry' ?>
                                            </span>
                                            <span class="text-[10px] text-slate-400 italic"><?= $is_urgent ? 'Closes Soon' : 'Standard Window' ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-6 w-full sm:w-auto">
                                    <?php if ($q['sub_status'] === 'graded'): ?>
                                        <div class="px-6 py-3 bg-brand-500 text-brand-900 rounded-2xl flex flex-col items-center justify-center min-w-[100px] shadow-lg shadow-brand-500/20">
                                            <span class="text-[8px] font-black uppercase tracking-widest opacity-60">Result</span>
                                            <span class="text-lg font-black leading-none"><?= (int) $q['score'] ?>%</span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!$is_done): ?>
                                        <a href="take-quiz.php?id=<?= $q['id'] ?>" class="flex-1 sm:flex-none inline-flex items-center justify-center bg-slate-900 dark:bg-indigo-600 text-white px-10 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.3em] transition-all hover:opacity-90 shadow-xl shadow-slate-900/10">
                                            Begin Exam
                                        </a>
                                    <?php else: ?>
                                        <a href="view-results.php?submission_id=<?= $q['submission_id'] ?>" 
                                            class="flex-1 sm:flex-none inline-flex items-center justify-center bg-indigo-50 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 px-10 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.3em] hover:bg-indigo-100 dark:hover:bg-slate-600 transition-all border border-indigo-100 dark:border-slate-600">
                                            <i class="fas fa-eye mr-2"></i> Review
                                        </a>
                                    <?php endif; ?>
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
    document.addEventListener('DOMContentLoaded', () => {
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }
    });
</script>
</body>
</html>