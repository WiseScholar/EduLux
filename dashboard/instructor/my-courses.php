<?php
require_once __DIR__ . '/../../includes/config.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? null;
$active_tab = $_GET['status'] ?? 'published';

// Fetching courses with module/lesson counts for that "Advanced" feel
$courses_stmt = $pdo->prepare("
    SELECT c.*, 
    (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) as total_modules,
    (SELECT COUNT(l.id) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) as total_lessons
    FROM courses c 
    WHERE c.instructor_id = ?
    ORDER BY c.created_at DESC
");
$courses_stmt->execute([$instructor_id]);
$courses = $courses_stmt->fetchAll();

// Grouping logic
$grouped = ['draft' => [], 'pending' => [], 'published' => [], 'rejected' => []];
foreach ($courses as $course) {
    $status = $course['status'] ?: 'draft';
    if(isset($grouped[$status])) $grouped[$status][] = $course;
}

require_once ROOT_PATH . 'includes/header.php'; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class' }
</script>
<style>
    /* Global Sleek Scrollbar */
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
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(99, 102, 241, 0.5);
    }
    
    /* Firefox Support */
    * {
        scrollbar-width: thin;
        scrollbar-color: rgba(99, 102, 241, 0.2) transparent;
    }

    /* Glass Effect for Sidebar (Matching dashboard) */
    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .dark .glass {
        background: rgba(15, 23, 42, 0.9);
    }

    /* Smooth tab switching transition */
    main {
        scroll-behavior: smooth;
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex transition-colors duration-300">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-24">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Course Inventory</h1>
                    <p class="text-slate-500 dark:text-slate-400">Manage, build, and track your educational content.</p>
                </div>
                <a href="create-course.php" class="flex items-center space-x-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 dark:shadow-none hover:bg-indigo-700 transition-all transform hover:-translate-y-1">
                    <i class="fas fa-plus"></i> <span>Create New Course</span>
                </a>
            </div>

            <?php if ($msg): ?>
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center">
                    <i class="fas fa-check-circle mr-3"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-wrap items-center gap-2 mb-8 bg-white dark:bg-slate-800 p-2 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm w-fit">
                <?php foreach(['published', 'pending', 'draft', 'rejected'] as $status): ?>
                    <a href="?status=<?= $status ?>" 
                       class="px-5 py-2 rounded-xl text-sm font-bold transition-all <?= $active_tab === $status ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                        <?= ucfirst($status) ?> 
                        <span class="ml-2 opacity-60"><?= count($grouped[$status]) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($grouped[$active_tab])): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                    <?php foreach ($grouped[$active_tab] as $course): ?>
                        <div class="group bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 overflow-hidden flex flex-col">
                            
                            <div class="relative aspect-video overflow-hidden">
                                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?? 'default.jpg' ?>" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                                    <span class="text-white text-xs font-medium"><i class="far fa-calendar-alt mr-1"></i> Created <?= date('M Y', strtotime($course['created_at'])) ?></span>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <span class="px-3 py-1 bg-white/90 backdrop-blur-md dark:bg-slate-900/90 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm">
                                        <?= $course['total_modules'] ?> Modules
                                    </span>
                                </div>
                            </div>

                            <div class="p-6 flex-1 flex flex-col">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 line-clamp-1 group-hover:text-indigo-600 transition-colors">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h3>
                                
                                <div class="flex items-center text-slate-400 text-sm mb-6 space-x-4">
                                    <span class="flex items-center"><i class="fas fa-play-circle mr-1.5 text-indigo-500"></i> <?= $course['total_lessons'] ?> Lessons</span>
                                    <span class="flex items-center"><i class="fas fa-user-friends mr-1.5 text-emerald-500"></i> 0 Students</span>
                                </div>

                                <div class="mt-auto pt-4 border-t border-slate-50 dark:border-slate-700/50 grid grid-cols-2 gap-3">
                                    <a href="create-course.php?id=<?= $course['id'] ?>" class="flex items-center justify-center p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 transition-all font-bold text-xs">
                                        <i class="fas fa-edit mr-2"></i> Settings
                                    </a>
                                    <a href="curriculum-builder.php?course_id=<?= $course['id'] ?>" class="flex items-center justify-center p-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition-all font-bold text-xs">
                                        <i class="fas fa-stream mr-2"></i> Curriculum
                                    </a>
                                    
                                    <?php if($active_tab === 'published'): ?>
                                        <a href="<?= BASE_URL ?>pages/courses/detail.php?id=<?= $course['id'] ?>" target="_blank" class="col-span-2 flex items-center justify-center p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900 transition-all font-bold text-xs">
                                            <i class="fas fa-external-link-alt mr-2"></i> View Live Course
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-20 text-center border border-dashed border-slate-200 dark:border-slate-700">
                    <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center mx-auto mb-6 text-indigo-600">
                        <i class="fas fa-layer-group text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No courses found</h3>
                    <p class="text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-8">You don't have any courses in "<?= $active_tab ?>" status yet. Ready to start teaching?</p>
                    <a href="create-course.php" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-all">
                        Create your first course
                    </a>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

</body>
</html>