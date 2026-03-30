<?php
require_once __DIR__ . '/../../includes/config.php';

// 1. Instructor Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

// 2. Fetch Courses with Quiz Counts
$stmt = $pdo->prepare("
    SELECT 
        c.id, 
        c.title, 
        c.thumbnail,
        (SELECT COUNT(*) FROM assessments WHERE course_id = c.id AND type = 'quiz') as quiz_count
    FROM courses c
    WHERE c.instructor_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$instructor_id]);
$courses = $stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">
            
            <header class="mb-12 flex flex-col md:flex-row justify-between items-end gap-6">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-amber-500 mb-2 block">Examination Management</span>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter leading-none">
                        Quiz <span class="text-indigo-600 dark:text-indigo-400">Control Center</span>
                    </h1>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($courses as $course): ?>
                    <div class="group bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm hover:shadow-xl transition-all overflow-hidden">
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <i class="fas fa-book text-lg"></i>
                                </div>
                                <span class="px-3 py-1 bg-slate-100 dark:bg-slate-900 text-[9px] font-black text-slate-400 rounded-lg uppercase tracking-widest">
                                    <?= $course['quiz_count'] ?> Quizzes
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-black text-slate-900 dark:text-white mb-8 leading-tight uppercase italic group-hover:text-indigo-600 transition-colors">
                                <?= htmlspecialchars($course['title']) ?>
                            </h3>

                            <div class="grid grid-cols-2 gap-4">
                                <a href="quiz-builder.php?course_id=<?= $course['id'] ?>" 
                                   class="flex flex-col items-center justify-center p-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl transition-all shadow-lg shadow-indigo-200 dark:shadow-none">
                                    <i class="fas fa-plus-circle mb-2"></i>
                                    <span class="text-[9px] font-black uppercase tracking-widest">New Quiz</span>
                                </a>
                                
                                <a href="assignments.php?course_id=<?= $course['id'] ?>" 
                                   class="flex flex-col items-center justify-center p-4 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-2xl transition-all">
                                    <i class="fas fa-list-ul mb-2"></i>
                                    <span class="text-[9px] font-black uppercase tracking-widest">Manage</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($courses)): ?>
                    <div class="col-span-full py-32 text-center bg-white dark:bg-slate-800 rounded-[4rem] border-2 border-dashed border-slate-100 dark:border-slate-700">
                        <i class="fas fa-layer-group text-4xl text-slate-200 mb-4"></i>
                        <p class="text-slate-400 font-bold uppercase tracking-widest italic">No courses found to host quizzes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>