<?php
$sidebar_user_id = $_SESSION['user_id'] ?? 0;
$pending_quizzes = 0;

if ($sidebar_user_id > 0) {
    $quiz_count_stmt = $pdo->prepare("
        SELECT COUNT(a.id) 
        FROM assessments a
        JOIN enrollments e ON a.course_id = e.course_id
        LEFT JOIN assessment_submissions s ON a.id = s.assessment_id AND s.user_id = ?
        WHERE e.user_id = ? 
        AND a.type = 'quiz' 
        AND s.id IS NULL 
        AND e.status != 'dropped'
    ");
    $quiz_count_stmt->execute([$sidebar_user_id, $sidebar_user_id]);
    $pending_quizzes = (int)$quiz_count_stmt->fetchColumn();
}
?>
<style>
    /* 1. Ultra-Slim Scrollbar */
    .custom-sidebar-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .custom-sidebar-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-sidebar-scroll::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.1);
        border-radius: 20px;
    }

    .custom-sidebar-scroll:hover::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.3);
    }

    /* 2. Glassmorphism Core */
    .sidebar-glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border-right: 1px solid rgba(0, 0, 0, 0.05);
    }

    .dark .sidebar-glass {
        background: rgba(15, 23, 42, 0.9);
        border-right: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* 3. Refined Active State */
    .nav-link-active {
        background: white !important;
        color: #6366f1 !important;
        /* Indigo-500 */
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.15);
    }

    .dark .nav-link-active {
        background: rgba(99, 102, 241, 0.1) !important;
        color: #818cf8 !important;
        /* Indigo-400 */
        box-shadow: none;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }

    /* 4. Hover Micro-interaction */
    .nav-item-hover {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-item-hover:hover:not(.nav-link-active) {
        transform: translateX(4px);
    }

    @media (min-width: 1024px) {
        #sidebar {
            top: var(--header-height, 80px);
            height: calc(100vh - var(--header-height, 80px));
            position: fixed;
        }
    }
</style>

<aside id="sidebar" class="fixed left-0 top-0 h-full w-72 sidebar-glass flex flex-col z-[90] -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <div class="flex-1 overflow-y-auto custom-sidebar-scroll flex flex-col px-6 py-8">

        <div class="mb-10 lg:hidden flex items-center space-x-3">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                <i class="fas fa-graduation-cap text-xs"></i>
            </div>
            <span class="text-xl font-black tracking-tighter text-slate-900 dark:text-white">Edulux</span>
        </div>

        <div class="space-y-12">
            <div>
                <p class="px-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em] mb-6">Learning Path</p>
                <nav class="space-y-2">
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $student_path = "dashboard/student/";

                    $menu_items = [
                        ['url' => BASE_URL . $student_path . 'index.php', 'match' => 'index.php', 'icon' => 'fa-th-large', 'label' => 'Overview'],
                        ['url' => BASE_URL . $student_path . 'my-courses.php', 'match' => 'my-courses.php', 'icon' => 'fa-play-circle', 'label' => 'My Courses'],
                        ['url' => BASE_URL . $student_path . 'assignments.php', 'match' => 'assignments.php', 'icon' => 'fa-tasks', 'label' => 'Assessments'],
                        ['url' => BASE_URL . $student_path . 'quizzes.php', 'match' => 'quizzes.php', 'icon' => 'fa-bolt', 'label' => 'Quizzes', 'count' => $pending_quizzes],
                        ['url' => BASE_URL . $student_path . 'timetable.php', 'match' => 'timetable.php', 'icon' => 'fa-calendar-alt', 'label' => 'Class Schedule'],
                    ];

                    foreach ($menu_items as $item):
                        $is_active = ($current_page == $item['match']);
                    ?>
                        <a href="<?= $item['url'] ?>"
                            class="flex items-center justify-between px-5 py-3.5 rounded-2xl font-bold nav-item-hover group <?= $is_active ? 'nav-link-active' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white' ?>">
                            <div class="flex items-center space-x-3">
                                <i class="fas <?= $item['icon'] ?> text-lg <?= $is_active ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-500' ?>"></i>
                                <span class="text-sm"><?= $item['label'] ?></span>
                            </div>

                            <?php if (isset($item['count']) && $item['count'] > 0): ?>
                                <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-brand-500 text-[10px] font-black text-brand-900 shadow-lg shadow-brand-500/20">
                                    <?= $item['count'] ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div>
                <p class="px-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em] mb-6">Preferences</p>
                <nav class="space-y-2">
                    <a href="../profile.php" class="flex items-center space-x-3 px-5 py-3.5 rounded-2xl font-bold text-slate-500 dark:text-slate-400 nav-item-hover group hover:text-indigo-600 dark:hover:text-white">
                        <i class="fas fa-user-circle text-lg text-slate-400 group-hover:text-indigo-500"></i>
                        <span class="text-sm">My Profile</span>
                    </a>
                    <a href="<?= BASE_URL ?>pages/auth/logout.php"
                        class="flex items-center space-x-3 px-5 py-3.5 rounded-2xl font-bold text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                        <i class="fas fa-power-off text-lg"></i>
                        <span class="text-sm">Sign Out</span>
                    </a>
                </nav>
            </div>
        </div>

        <div class="mt-auto pt-10">
            <div class="bg-gradient-to-br from-indigo-600 to-violet-700 dark:from-slate-800 dark:to-slate-900 rounded-[2rem] p-6 text-white relative overflow-hidden shadow-xl shadow-indigo-100 dark:shadow-none">
                <div class="relative z-10">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-xs"></i>
                    </div>
                    <p class="text-xs font-bold mb-1">Stuck on a lesson?</p>
                    <p class="text-[10px] opacity-70 mb-4 leading-relaxed">Our support team is active 24/7 for students.</p>
                    <a href="#" class="block w-full py-3 bg-white text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-indigo-50 transition-colors shadow-sm">Contact Help</a>
                </div>
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            </div>
        </div>
    </div>
</aside>

<div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[80] hidden lg:hidden"></div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const header = document.getElementById('main-header');
        const overlay = document.getElementById('sidebarOverlay');

        // 1. Function to handle the Dynamic Header Height
        function updateSidebarPosition() {
            if (window.innerWidth >= 1024 && header) {
                const headerHeight = header.offsetHeight;
                const headerRect = header.getBoundingClientRect();

                const sidebarTop = Math.max(0, headerRect.bottom);
                document.documentElement.style.setProperty('--header-height', sidebarTop + 'px');
            } else {
                document.documentElement.style.setProperty('--header-height', '0px');
            }
        }

        // Run on scroll and resize
        window.addEventListener('scroll', updateSidebarPosition);
        window.addEventListener('resize', updateSidebarPosition);
        updateSidebarPosition(); // Initial run

        // 2. Mobile Toggle Logic (from bottom-nav.php)
        window.toggleSidebar = function() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
            document.body.classList.toggle('overflow-hidden');
        }

        const navPanelBtn = document.getElementById('navPanelBtn');
        if (navPanelBtn) navPanelBtn.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);
    });
</script>