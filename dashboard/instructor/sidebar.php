<?php
$sidebar_courses_smt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title ASC");
$sidebar_courses_smt->execute([$_SESSION['user_id']]);
$sidebar_courses = $sidebar_courses_smt->fetchAll();

// Helper to check active state
function isActive($pageName) {
    return strpos($_SERVER['PHP_SELF'], $pageName) !== false;
}

$activeClass = 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20';
$inactiveClass = 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50';
?>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.min.js"></script>

<style>
    /* Sleek Scrollbar for Sidebar */
    #sidebar::-webkit-scrollbar {
        width: 4px;
    }
    #sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    #sidebar::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.2);
        border-radius: 10px;
    }
    #sidebar:hover::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.5);
    }
</style>

<aside id="sidebar" 
       x-data="{ openAssignments: <?= isActive('assignments.php') ? 'true' : 'false' ?> }"
       class="fixed left-0 top-[80px] h-[calc(100vh-80px)] w-64 glass flex flex-col z-50 overflow-y-auto -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out border-r border-slate-200/50 dark:border-slate-700/50">

    <div class="p-8">
        <div class="flex items-center space-x-3 mb-10">
            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200 dark:shadow-none">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">Edulux</span>
        </div>

        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-6">Main Menu</p>
        
        <nav class="space-y-2">
            <a href="<?= BASE_URL ?>dashboard/instructor/index.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= isActive('index.php') ? $activeClass : $inactiveClass ?>">
                <i class="fas fa-chart-pie text-lg"></i> 
                <span class="font-semibold">Dashboard</span>
            </a>

            <a href="<?= BASE_URL ?>dashboard/instructor/my-courses.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= isActive('my-courses.php') ? $activeClass : $inactiveClass ?>">
                <i class="fas fa-layer-group text-lg"></i> 
                <span class="font-semibold">Course Manager</span>
            </a>

            <a href="<?= BASE_URL ?>dashboard/instructor/quizzes.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= isActive('quizzes.php') ? $activeClass : $inactiveClass ?>">
                <i class="fa-solid fa-bolt-lightning text-lg"></i> 
                <span class="font-semibold">Quiz</span>
            </a>

            <a href="<?= BASE_URL ?>dashboard/instructor/manage-groups.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= isActive('quizzes.php') ? $activeClass : $inactiveClass ?>">
                <i class="fas fa-people-group text-lg"></i> 
                <span class="font-semibold">Group Management System (GMS)</span>
            </a>

            <div class="relative">
                <button @click="openAssignments = !openAssignments" 
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all <?= isActive('assignments.php') ? 'text-indigo-600' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50' ?> group">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-tasks text-lg"></i>
                        <span class="font-semibold">Assignments</span>
                    </div>
                    <i class="fas fa-chevron-down text-[10px] transition-transform duration-300" :class="openAssignments ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openAssignments" 
                     x-collapse
                     class="mt-2 ml-4 space-y-1 border-l-2 border-slate-100 dark:border-slate-800 pl-4">
                    <?php if(empty($sidebar_courses)): ?>
                        <p class="text-[10px] text-slate-400 py-2 italic">No active courses</p>
                    <?php else: ?>
                        <?php foreach($sidebar_courses as $s_course): ?>
                            <a href="<?= BASE_URL ?>dashboard/instructor/assignments.php?course_id=<?= $s_course['id'] ?>" 
                               class="block py-2 text-xs font-medium <?= (isset($_GET['course_id']) && $_GET['course_id'] == $s_course['id']) ? 'text-indigo-600' : 'text-slate-500 hover:text-indigo-600' ?> transition-colors truncate">
                                <?= htmlspecialchars($s_course['title']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-10 mb-6">Instructor Tools</p>
        
        <nav class="space-y-2">
            <a href="<?= BASE_URL ?>dashboard/instructor/live-sessions.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= isActive('live-sessions.php') ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/10' : 'text-slate-500 hover:text-emerald-500 hover:bg-emerald-50' ?>">
                <i class="fas fa-broadcast-tower text-lg"></i> 
                <span class="font-semibold">Live Stream</span>
            </a>
            
            <a href="<?= BASE_URL ?>dashboard/instructor/grading-system.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= isActive('grading-system.php') ? $activeClass : $inactiveClass ?>">
                <i class="fas fa-check-double text-lg"></i> 
                <span class="font-semibold">Grading Desk</span>
            </a>
        </nav>

        <div class="mt-10 mb-10">
            <a href="<?= BASE_URL ?>pages/auth/logout.php" 
               class="flex items-center space-x-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 transition-all">
                <i class="fas fa-sign-out-alt text-lg"></i> 
                <span class="font-semibold">Sign Out</span>
            </a>
        </div>
    </div>
</aside>