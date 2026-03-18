<?php
$sidebar_courses_smt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title ASC");
$sidebar_courses_smt->execute([$_SESSION['user_id']]);
$sidebar_courses = $sidebar_courses_smt->fetchAll();
?>

<aside id="sidebar" class="fixed left-0 dashboard-sidebar w-64 glass flex flex-col z-50 overflow-y-auto -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="p-6">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Instructor Hub</p>
        <nav class="space-y-1">
            <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg nav-active">
                <i class="fas fa-chart-pie"></i> <span class="font-medium">Analytics</span>
            </a>
            <a href="my-courses.php" class="flex items-center space-x-3 p-3 rounded-lg text-slate-500 hover:text-indigo-600 transition-all">
                <i class="fas fa-layer-group"></i> <span class="font-medium">Course Manager</span>
            </a>
            <a href="students.php" class="flex items-center space-x-3 p-3 rounded-lg text-slate-500 hover:text-indigo-600 transition-all">
                <i class="fas fa-user-graduate"></i> <span class="font-medium">My Students</span>
            </a>
            <div class="relative">
                <button @click="openAssignments = !openAssignments" 
                        class="w-full flex items-center justify-between p-3 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50/50 transition-all group">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-tasks"></i>
                        <span class="font-medium">Assignment Library</span>
                    </div>
                    <i class="fas fa-chevron-down text-[10px] transition-transform duration-300" :class="openAssignments ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openAssignments" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     @click.away="openAssignments = false"
                     class="mt-1 ml-9 space-y-1 border-l-2 border-slate-100 pl-4">
                    
                    <?php if(empty($sidebar_courses)): ?>
                        <p class="text-[10px] text-slate-400 py-2">No courses found</p>
                    <?php else: ?>
                        <?php foreach($sidebar_courses as $s_course): ?>
                            <a href="assignments.php?course_id=<?= $s_course['id'] ?>" 
                               class="block py-2 text-xs font-bold text-slate-500 hover:text-indigo-600 transition-colors truncate">
                                <?= htmlspecialchars($s_course['title']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if($_SESSION['role'] === 'instructor'): ?>
                <a href="<?= BASE_URL ?>dashboard/instructor/view-submissions.php" 
                    class="flex items-center gap-4 px-6 py-4 text-sm font-black uppercase tracking-widest <?= strpos($_SERVER['PHP_SELF'], 'view-submissions.php') !== false ? 'bg-indigo-600 text-white' : 'text-slate-400' ?> rounded-2xl transition-all">
                    <i class="fas fa-graduation-cap"></i> Grading Desk
                </a>
            <?php endif; ?>
            <a href="earnings.php" class="flex items-center space-x-3 p-3 rounded-lg text-slate-500 hover:text-indigo-600 transition-all">
                <i class="fas fa-wallet"></i> <span class="font-medium">Earnings</span>
            </a>
        </nav>

        <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-8 mb-4">Advanced Toolset</p>
        <nav class="space-y-1">
            <a href="live-sessions.php" class="flex items-center space-x-3 p-3 rounded-lg text-slate-500 hover:text-emerald-500 transition-all">
                <i class="fas fa-broadcast-tower"></i> <span class="font-medium">Live Stream</span>
            </a>
            <a href="timetable.php" class="flex items-center space-x-3 p-3 rounded-lg text-slate-500 hover:text-indigo-600 transition-all">
                <i class="fas fa-calendar-alt"></i> <span class="font-medium">Scheduling</span>
            </a>
        </nav>

        <div class="mt-auto pt-10">
            <a href="<?= BASE_URL ?>pages/auth/logout.php" class="flex items-center space-x-3 p-3 rounded-lg text-red-500 hover:bg-red-50 transition-all">
                <i class="fas fa-sign-out-alt"></i> <span class="font-medium">Sign Out</span>
            </a>
        </div>
    </div>
</aside>