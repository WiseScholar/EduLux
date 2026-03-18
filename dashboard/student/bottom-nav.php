<div class="lg:hidden fixed bottom-0 left-0 right-0 z-[100]">
    <!-- Subtle gradient line at top for separation -->
    <div class="h-[1px] bg-gradient-to-r from-transparent via-slate-200 dark:via-slate-700 to-transparent"></div>
    
    <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-lg border-t border-slate-200 dark:border-slate-800 h-20 px-4 flex justify-between items-center">
        
        <a href="index.php" class="flex flex-col items-center justify-center flex-1 h-full group">
            <div class="relative">
                <i class="fas fa-home text-xl <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?> transition-colors duration-300"></i>
                <?php if(basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-indigo-600 dark:bg-indigo-400 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-tighter mt-1 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Home</span>
        </a>

        <a href="my-courses.php" class="flex flex-col items-center justify-center flex-1 h-full">
            <i class="fas fa-play-circle text-xl <?= basename($_SERVER['PHP_SELF']) == 'my-courses.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
            <span class="text-[10px] font-bold uppercase tracking-tighter mt-1 <?= basename($_SERVER['PHP_SELF']) == 'my-courses.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Learn</span>
        </a>

        <!-- Center Action Button -->
        <div class="flex items-center justify-center flex-1 h-full">
            <button id="navPanelBtn" class="w-14 h-14 bg-gradient-to-tr from-indigo-600 to-violet-500 rounded-2xl shadow-lg flex items-center justify-center text-white transform active:scale-95 transition-all hover:shadow-xl">
                <i class="fas fa-th text-xl"></i>
            </button>
        </div>

        <a href="timetable.php" class="flex flex-col items-center justify-center flex-1 h-full">
            <i class="fas fa-calendar-alt text-xl <?= basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
            <span class="text-[10px] font-bold uppercase tracking-tighter mt-1 <?= basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Schedule</span>
        </a>

        <a href="../profile.php" class="flex flex-col items-center justify-center flex-1 h-full">
            <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 border-2 border-white dark:border-slate-900 overflow-hidden shadow-sm">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['first_name'] ?? 'User') ?>&background=indigo&color=fff&bold=true" alt="" class="w-full h-full object-cover">
            </div>
            <span class="text-[10px] font-bold uppercase tracking-tighter mt-1 <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Profile</span>
        </a>
    </div>

    <div class="h-[env(safe-area-inset-bottom)] bg-white/95 dark:bg-slate-900/95"></div>
</div>