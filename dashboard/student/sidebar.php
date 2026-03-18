<style>
    /* 1. The Scrollbar - Ultra Slim & Custom */
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
        background: rgba(99, 102, 241, 0.4);
    }

    /* 2. Glassmorphism & Logic - Now with dark mode support */
    .sidebar-glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-right: 1px solid rgba(226, 232, 240, 0.8);
    }
    
    .dark .sidebar-glass {
        background: rgba(15, 23, 42, 0.8);
        border-right: 1px solid rgba(51, 65, 85, 0.8);
    }

    /* 3. Desktop Positioning Logic */
    @media (min-width: 1024px) {
        #sidebar {
            top: var(--header-height, 80px); 
            height: calc(100vh - var(--header-height, 80px));
            position: fixed;
        }
    }

    /* Active State - Add dark mode support */
    .nav-link-active {
        background: linear-gradient(135deg, #002d72 0%, #001e4d 100%);
        color: #eab308 !important;
        box-shadow: 0 10px 20px -5px rgba(0, 45, 114, 0.2);
    }
    
    .dark .nav-link-active {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #eab308 !important;
        box-shadow: 0 10px 20px -5px rgba(234, 179, 8, 0.2);
    }
    
    /* Dark mode text colors */
    .dark .text-slate-400 {
        color: #94a3b8;
    }
    
    .dark .text-slate-500 {
        color: #cbd5e1;
    }
    
    .dark .hover\:bg-slate-50:hover {
        background-color: rgba(30, 41, 59, 0.5);
    }
</style>

<aside id="sidebar" class="fixed left-0 top-0 h-full w-72 sidebar-glass flex flex-col z-[90] -translate-x-full lg:translate-x-0 transition-transform duration-300">
    
    <div class="flex-1 overflow-y-auto custom-sidebar-scroll flex flex-col px-6 py-8">
        
        <div class="mb-8 lg:hidden">
            <span class="text-2xl font-[900] tracking-tighter text-brand-900 dark:text-white">ERM<span class="text-brand-500 italic">I</span></span>
        </div>

        <div class="space-y-10">
            <div>
                <p class="px-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Command Center</p>
                <nav class="space-y-1.5">
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $menu_items = [
                        ['url' => 'index.php', 'icon' => 'fa-th-large', 'label' => 'Dashboard'],
                        ['url' => 'my-courses.php', 'icon' => 'fa-play-circle', 'label' => 'My Courses'],
                        ['url' => 'assignments.php', 'icon' => 'fa-tasks', 'label' => 'Assignments'],
                        ['url' => 'timetable.php', 'icon' => 'fa-calendar-alt', 'label' => 'Schedule'],
                    ];

                    foreach ($menu_items as $item):
                        $is_active = ($current_page == $item['url']);
                    ?>
                    <a href="<?= $item['url'] ?>" 
                       class="flex items-center space-x-3 px-4 py-3 rounded-2xl font-bold transition-all duration-300 group <?= $is_active ? 'nav-link-active' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-brand-900 dark:hover:text-white' ?>">
                        <i class="fas <?= $item['icon'] ?> text-lg <?= $is_active ? 'text-brand-500' : 'text-slate-400 dark:text-slate-500 group-hover:text-brand-900 dark:group-hover:text-white' ?>"></i>
                        <span class="text-sm"><?= $item['label'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div>
                <p class="px-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Account</p>
                <nav class="space-y-1.5">
                    <a href="../profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-2xl font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all group">
                        <i class="fas fa-user-cog text-lg text-slate-400 dark:text-slate-500 group-hover:text-brand-900 dark:group-hover:text-white"></i>
                        <span class="text-sm">Settings</span>
                    </a>
                    <a href="<?= BASE_URL ?>pages/auth/logout.php" 
                        class="flex items-center space-x-3 px-4 py-3 rounded-2xl font-bold text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                            <i class="fas fa-power-off text-lg"></i>
                            <span class="text-sm">Logout</span>
                    </a>
                </nav>
            </div>
        </div>

        <div class="mt-12 pb-4">
            <div class="bg-brand-900 dark:bg-slate-800 rounded-[2rem] p-6 text-white relative overflow-hidden group">
                <div class="relative z-10 text-center">
                    <p class="text-[10px] font-black uppercase opacity-60 mb-1 tracking-widest">Support Desk</p>
                    <p class="text-xs font-bold mb-4">Need help?</p>
                    <a href="#" class="block w-full py-2.5 bg-brand-500 text-brand-900 dark:text-white rounded-xl text-[10px] font-black uppercase tracking-tighter hover:bg-white dark:hover:bg-slate-700 transition-colors">Chat Now</a>
                </div>
                <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
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
        if(navPanelBtn) navPanelBtn.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', toggleSidebar);
    });
</script>