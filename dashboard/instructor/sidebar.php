<?php
$sidebar_courses_smt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title ASC");
$sidebar_courses_smt->execute([$_SESSION['user_id']]);
$sidebar_courses = $sidebar_courses_smt->fetchAll();
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
       x-data="{ openAssignments: false }"
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
            <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= strpos($_SERVER['PHP_SELF'], 'index.php') !== false ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50' ?>">
                <i class="fas fa-chart-pie text-lg"></i> 
                <span class="font-semibold">Dashboard</span>
            </a>

            <a href="my-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                <i class="fas fa-layer-group text-lg"></i> 
                <span class="font-semibold">Course Manager</span>
            </a>

            <div class="relative">
                <button @click="openAssignments = !openAssignments" 
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all group">
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
                            <a href="assignments.php?course_id=<?= $s_course['id'] ?>" 
                               class="block py-2 text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors truncate">
                                <?= htmlspecialchars($s_course['title']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <a href="earnings.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                <i class="fas fa-wallet text-lg"></i> 
                <span class="font-semibold">Earnings</span>
            </a>
        </nav>

        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-10 mb-6">Instructor Tools</p>
        
        <nav class="space-y-2">
            <a href="live-sessions.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-500 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-all">
                <i class="fas fa-broadcast-tower text-lg"></i> 
                <span class="font-semibold">Live Stream</span>
            </a>
            <a href="grading.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                <i class="fas fa-check-double text-lg"></i> 
                <span class="font-semibold">Grading Desk</span>
            </a>
        </nav>

        <div class="mt-10 mb-10">
            <a href="<?= BASE_URL ?>pages/auth/logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 transition-all">
                <i class="fas fa-sign-out-alt text-lg"></i> 
                <span class="font-semibold">Sign Out</span>
            </a>
        </div>
    </div>
</aside>