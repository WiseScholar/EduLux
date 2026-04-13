<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// 1. Instructor Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// 2. Fetch Course Context
$course_stmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
$course_stmt->execute([$course_id, $instructor_id]);
$course = $course_stmt->fetch();

if (!$course) {
    header("Location: quizzes.php?error=course_not_found");
    exit;
}

// 3. Fetch all Quizzes for this Course with real-time analytics
$stmt = $pdo->prepare("
    SELECT 
        a.id, a.title, a.due_date, a.passing_score, a.duration,
        (SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id = a.id) as attempt_count,
        (SELECT AVG(score) FROM assessment_submissions WHERE assessment_id = a.id) as avg_performance,
        (SELECT MAX(submitted_at) FROM assessment_submissions WHERE assessment_id = a.id) as last_activity
    FROM assessments a
    WHERE a.course_id = ? AND a.type = 'quiz'
    ORDER BY a.created_at DESC
");
$stmt->execute([$course_id]);
$quizzes = $stmt->fetchAll();

if (!function_exists('h')) {
    function h($text)
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

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
            margin-left: 18rem;
        }
    }

    .registry-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .registry-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.1);
    }

    .status-dot {
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-16">
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">
                        <a href="quizzes.php" class="hover:text-indigo-600 transition-colors">Control Center</a>
                        <i class="fas fa-chevron-right text-[7px] opacity-30"></i>
                        <span class="text-indigo-600 dark:text-brand-500">Registry</span>
                    </div>
                    <h1 class="text-3xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic leading-none">
                        <?= h($course['title']) ?>
                    </h1>
                    <div class="flex items-center gap-3">
                        <span class="h-1 w-10 bg-indigo-600 dark:bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-bold text-xs uppercase tracking-widest">
                            Architecture Registry: <?= count($quizzes) ?> Active Modules
                        </p>
                    </div>
                </div>

                <a href="quiz-builder.php?course_id=<?= $course_id ?>" class="group inline-flex items-center gap-3 px-8 py-4 bg-indigo-600 dark:bg-brand-500 text-white dark:text-brand-900 rounded-[2rem] font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 dark:shadow-none hover:scale-105 transition-all">
                    <i class="fas fa-plus-circle group-hover:rotate-90 transition-transform"></i> Create New Quiz
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 md:gap-8">
                <?php if (empty($quizzes)): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-[4rem] border-2 border-dashed border-slate-200 dark:border-slate-700 p-24 text-center">
                        <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-microchip text-2xl text-indigo-300 dark:text-indigo-700"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase italic">Registry is Empty</h3>
                        <p class="text-slate-500 mt-2">No assessment systems yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($quizzes as $q):
                        $avg = round($q['avg_performance'] ?? 0);
                        $has_activity = !is_null($q['last_activity']);
                    ?>
                        <div class="registry-card bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden flex flex-col xl:flex-row xl:items-center p-6 md:p-8 gap-8">

                            <div class="flex items-center gap-6 flex-1">
                                <div class="w-16 h-16 shrink-0 rounded-2xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 border border-slate-100 dark:border-slate-800">
                                    <i class="fas fa-shield-halved text-2xl"></i>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="font-black text-xl text-slate-900 dark:text-white leading-tight uppercase italic"><?= h($q['title']) ?></h3>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                            <i class="far fa-clock text-indigo-500"></i> <?= $q['duration'] ?> MINS
                                        </span>
                                        <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                            <i class="fas fa-bullseye text-emerald-500"></i> PASS: <?= $q['passing_score'] ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 xl:gap-12 xl:px-12 xl:border-x border-slate-100 dark:border-slate-700">
                                <div>
                                    <label class="block text-[8px] font-black uppercase text-slate-400 mb-1 tracking-widest">Total Intake</label>
                                    <span class="text-lg font-black text-slate-800 dark:text-white"><?= $q['attempt_count'] ?></span>
                                </div>
                                <div>
                                    <label class="block text-[8px] font-black uppercase text-slate-400 mb-1 tracking-widest">Avg. Precision</label>
                                    <span class="text-lg font-black <?= $avg >= $q['passing_score'] ? 'text-emerald-500' : 'text-amber-500' ?>"><?= $avg ?>%</span>
                                </div>
                                <div class="hidden sm:block">
                                    <label class="block text-[8px] font-black uppercase text-slate-400 mb-1 tracking-widest">Last Sync</label>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            <?= $has_activity ? date('M d', strtotime($q['last_activity'])) : 'None' ?>
                                        </span>
                                        <span class="text-[9px] text-slate-400 italic"><?= $has_activity ? date('h:i A', strtotime($q['last_activity'])) : '--:--' ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 w-full xl:w-auto shrink-0">
                                <button onclick="deleteQuiz(<?= $q['id'] ?>, '<?= addslashes(h($q['title'])) ?>')"
                                    class="flex-1 xl:flex-none p-4 bg-red-50 dark:bg-red-900/10 text-red-400 hover:text-red-600 dark:hover:text-red-400 rounded-2xl transition-all border border-transparent hover:border-red-100 dark:hover:border-red-900/30 text-center">
                                    <i class="fas fa-trash-alt"></i>
                                </button>

                                <a href="quiz-builder.php?id=<?= $q['id'] ?>&course_id=<?= $course_id ?>" class="flex-1 xl:flex-none p-4 bg-slate-50 dark:bg-slate-900 text-slate-400 hover:text-indigo-600 dark:hover:text-white rounded-2xl transition-all border border-transparent hover:border-indigo-100 dark:hover:border-slate-700 text-center">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="quiz-details.php?id=<?= $q['id'] ?>" class="flex-[3] xl:flex-none inline-flex items-center justify-center gap-2 px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] transition-all hover:opacity-90 shadow-lg active:scale-95 whitespace-nowrap">
                                    <i class="fas fa-clipboard-check text-xs"></i> Audit Results
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }
    });
    async function deleteQuiz(id, title) {
        if (!confirm(`Are you sure you want to permanently delete "${title}"?`)) return;

        try {
            const response = await fetch('actions/delete-quiz.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${id}`
            });

            // Let's check if the response is actually OK before parsing JSON
            if (!response.ok) {
                const text = await response.text();
                console.error("Server Error:", text);
                throw new Error("Server responded with an error status.");
            }

            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                alert(result.message);
            }
        } catch (e) {
            console.error("Delete Error:", e);
            alert("Operation failed. Check the console for details.");
        }
    }
</script>
</body>

</html>