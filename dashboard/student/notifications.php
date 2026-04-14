<?php
require_once __DIR__ . '/../../includes/config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Mark all as read when entering this page (Optional, but common UX)
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);

// 2. Fetch All Notifications (with pagination or just a limit)
$stmt = $pdo->prepare("
    SELECT id, message, link_url, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$user_id]);
$all_notifications = $stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: { 900: '#002d72', 500: '#eab308' }
                }
            }
        }
    }
</script>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-72">
        <main class="p-6 lg:p-10 flex-1 w-full max-w-4xl mx-auto">

            <div class="flex justify-between items-end mb-12">
                <div>
                    <nav class="flex mb-4 text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">
                        <a href="index.php" class="hover:text-indigo-600 transition">Dashboard</a>
                        <span class="mx-3 opacity-30">/</span>
                        <span class="text-slate-900 dark:text-slate-200 italic">Communications</span>
                    </nav>
                    <h1 class="text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic leading-none">
                        Notification <span class="text-indigo-600">Archives</span>
                    </h1>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Alerts</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white"><?= count($all_notifications) ?></p>
                </div>
            </div>

            <div class="space-y-4">
                <?php if (empty($all_notifications)): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-20 text-center border border-slate-100 dark:border-slate-700/50 shadow-sm">
                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-bell-slash text-3xl text-slate-200 dark:text-slate-700"></i>
                        </div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase italic">Silence is Golden</h2>
                        <p class="text-slate-400 text-sm mt-2">You don't have any notifications at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_notifications as $notif): 
                        $is_new = (strtotime($notif['created_at']) > strtotime('-24 hours'));
                    ?>
                        <a href="<?= BASE_URL . htmlspecialchars($notif['link_url']) ?>" 
                           class="group block bg-white dark:bg-slate-800 p-6 lg:p-8 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm hover:shadow-xl hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
                            
                            <div class="flex items-start gap-6 relative z-10">
                                <div class="shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center 
                                    <?= $is_new ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600' : 'bg-slate-50 dark:bg-slate-900 text-slate-400' ?> group-hover:rotate-12 transition-transform">
                                    <i class="fas <?= $is_new ? 'fa-bolt' : 'fa-check-circle' ?> text-lg"></i>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="text-[9px] font-black uppercase tracking-widest <?= $is_new ? 'text-indigo-500' : 'text-slate-400' ?>">
                                            <?= $is_new ? 'Recent Activity' : 'Archived Alert' ?>
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase italic">
                                            <?= date('M d, Y • h:i A', strtotime($notif['created_at'])) ?>
                                        </p>
                                    </div>
                                    <p class="text-sm lg:text-base font-bold text-slate-700 dark:text-slate-200 leading-relaxed">
                                        <?= htmlspecialchars($notif['message']) ?>
                                    </p>
                                </div>

                                <div class="shrink-0 self-center opacity-0 group-hover:opacity-100 transition-opacity translate-x-4 group-hover:translate-x-0 transition-transform">
                                    <i class="fas fa-chevron-right text-indigo-600"></i>
                                </div>
                            </div>

                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-indigo-500/5 to-transparent rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-700"></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="mt-12 text-center">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-300">Showing last 50 transmissions</p>
            </div>

        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    // Theme Loader
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
</script>
</body>
</html>