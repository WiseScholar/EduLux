<div class="lg:hidden fixed bottom-0 left-0 right-0 z-[100]">
    <div class="h-[1px] bg-gradient-to-r from-transparent via-indigo-500/20 dark:via-indigo-400/20 to-transparent"></div>
    
    <div class="bg-white/80 dark:bg-slate-900/90 backdrop-blur-xl border-t border-slate-200/50 dark:border-slate-800/50 h-20 px-2 flex justify-around items-center">
        
        <?php 
            $current_page = basename($_SERVER['PHP_SELF']); 
            $student_root = BASE_URL . "dashboard/student/";
        ?>

        <a href="<?= $student_root ?>index.php" class="flex flex-col items-center justify-center w-16 h-full transition-all duration-300 active:scale-90">
            <div class="relative flex items-center justify-center">
                <i class="fas fa-th-large text-xl <?= $current_page == 'index.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
                <?php if($current_page == 'index.php'): ?>
                    <span class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-indigo-600 dark:bg-indigo-400 rounded-full shadow-[0_0_8px_#6366f1]"></span>
                <?php endif; ?>
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1.5 <?= $current_page == 'index.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Home</span>
        </a>

        <a href="<?= $student_root ?>my-courses.php" class="flex flex-col items-center justify-center w-16 h-full transition-all duration-300 active:scale-90">
            <div class="relative flex items-center justify-center">
                <i class="fas fa-play-circle text-xl <?= $current_page == 'my-courses.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
                <?php if($current_page == 'my-courses.php'): ?>
                    <span class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-indigo-600 dark:bg-indigo-400 rounded-full shadow-[0_0_8px_#6366f1]"></span>
                <?php endif; ?>
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1.5 <?= $current_page == 'my-courses.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Learn</span>
        </a>

        <div class="relative -mt-8 flex flex-col items-center">
            <button id="navPanelBtn" 
                    class="w-16 h-16 bg-slate-900 dark:bg-indigo-600 text-white rounded-[1.5rem] shadow-2xl shadow-indigo-500/40 dark:shadow-none flex items-center justify-center transform active:scale-90 transition-all border-4 border-[#f8fafc] dark:border-[#0f172a]">
                <i class="fas fa-plus text-xl transition-transform duration-300" id="navIcon"></i>
            </button>
            <span class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2">Menu</span>
        </div>

        <a href="<?= $student_root ?>timetable.php" class="flex flex-col items-center justify-center w-16 h-full transition-all duration-300 active:scale-90">
            <div class="relative flex items-center justify-center">
                <i class="fas fa-calendar-alt text-xl <?= $current_page == 'timetable.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
                <?php if($current_page == 'timetable.php'): ?>
                    <span class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-indigo-600 dark:bg-indigo-400 rounded-full shadow-[0_0_8px_#6366f1]"></span>
                <?php endif; ?>
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1.5 <?= $current_page == 'timetable.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Class</span>
        </a>

        <a href="<?= BASE_URL ?>dashboard/profile.php" class="flex flex-col items-center justify-center w-16 h-full transition-all duration-300 active:scale-90">
            <div class="w-7 h-7 rounded-full border-2 <?= $current_page == 'profile.php' ? 'border-indigo-600 dark:border-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.3)]' : 'border-white dark:border-slate-800 shadow-sm' ?> overflow-hidden transition-all">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['first_name'] ?? 'User') ?>&background=6366f1&color=fff&bold=true" alt="" class="w-full h-full object-cover">
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1.5 <?= $current_page == 'profile.php' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>">Me</span>
        </a>
    </div>

    <div class="h-[env(safe-area-inset-bottom)] bg-white/80 dark:bg-slate-900/90 backdrop-blur-xl"></div>
</div>