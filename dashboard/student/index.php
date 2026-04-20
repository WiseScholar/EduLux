<?php
require_once __DIR__ . '/../../includes/config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . LOGIN_URL);
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Notifications Count
$unread_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_count_stmt->execute([$user_id]);
$unread_count = (int)$unread_count_stmt->fetchColumn();

// Fetch latest 5 notifications
$notif_stmt = $pdo->prepare("
    SELECT id, message, link_url, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$notif_stmt->execute([$user_id]);
$notifications = $notif_stmt->fetchAll();

// 1. Total Enrolled (All active/completed)
$stmt = $pdo->prepare("SELECT COUNT(id) FROM enrollments WHERE user_id = ? AND status != 'dropped'");
$stmt->execute([$user_id]);
$total_enrolled = $stmt->fetchColumn() ?: 0;

// 2. Completed Courses (Where status is literally 'completed')
$stmt = $pdo->prepare("SELECT COUNT(id) FROM enrollments WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_courses = $stmt->fetchColumn() ?: 0;

// 3. In Progress (Active but not yet completed)
$stmt = $pdo->prepare("SELECT COUNT(id) FROM enrollments WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$in_progress = $stmt->fetchColumn() ?: 0;

// 4. Tasks Due (Unsubmitted assessments in enrolled courses)
$stmt = $pdo->prepare("
    SELECT COUNT(a.id) 
    FROM assessments a
    JOIN enrollments e ON a.course_id = e.course_id
    LEFT JOIN assessment_submissions s ON a.id = s.assessment_id AND s.user_id = ?
    WHERE e.user_id = ? AND s.id IS NULL AND e.status != 'dropped'
");
$stmt->execute([$user_id, $user_id]);
$pending_tasks = $stmt->fetchColumn() ?: 0;

// 3. Fetch Enrolled Courses
$courses_stmt = $pdo->prepare("
    SELECT c.id, c.title, c.thumbnail, u.first_name,
    (SELECT COUNT(cp.id) FROM course_progress cp 
     JOIN course_lessons cl ON cp.lesson_id = cl.id 
     JOIN course_sections cs ON cl.section_id = cs.id 
     WHERE cs.course_id = c.id AND cp.user_id = ?) as completed_lessons,
    (SELECT COUNT(cl.id) FROM course_lessons cl 
     JOIN course_sections cs ON cl.section_id = cs.id 
     WHERE cs.course_id = c.id) as total_lessons
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.user_id = ?
    LIMIT 6
");
$courses_stmt->execute([$user_id, $user_id]);
$enrolled_courses = $courses_stmt->fetchAll();

$greeting = date('H') < 12 ? "Good morning" : (date('H') < 17 ? "Good afternoon" : "Good evening");

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: {
                        900: '#002d72',
                        500: '#eab308'
                    }
                }
            }
        }
    }
</script>
<style>
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

    * {
        scrollbar-width: thin;
        scrollbar-color: rgba(99, 102, 241, 0.2) transparent;
    }

    [x-cloak] {
        display: none !important;
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.4);
    }
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.15);
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-72">
        <main class="p-6 lg:p-10 flex-1 w-full max-w-7xl mx-auto">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-6">
                <div>
                    <div id="pushPrompt" class="hidden mb-6 p-5 bg-gradient-to-r from-indigo-600 to-violet-600 rounded-[2rem] flex items-center justify-between shadow-xl shadow-indigo-100 dark:shadow-none animate-in">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-white">
                                <i class="fas fa-bell text-xl"></i>
                            </div>
                            <div>
                                <p class="text-white font-black text-sm uppercase tracking-tight">Stay in the Loop</p>
                                <p class="text-indigo-100 text-xs font-medium">Enable notifications for deadlines & live sessions.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="dismissPushPrompt()" class="px-4 py-2 text-white/70 text-[10px] font-black uppercase tracking-widest hover:text-white">Later</button>
                            <button onclick="initPushSubscription()" class="bg-white text-indigo-600 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm hover:bg-indigo-50 transition-all">Enable Now</button>
                        </div>
                    </div>

                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 mb-2 block">Student Workspace</span>
                    <h1 class="text-3xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-none italic uppercase">
                        <?= $greeting ?>, <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-violet-500"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium mt-3 italic">"Intelligence is the ability to adapt to change." — Let's learn.</p>
                </div>

                <div class="flex items-center gap-4">
                    <button id="themeToggle" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all text-slate-600 dark:text-yellow-400">
                        <i class="fas fa-moon"></i>
                    </button>

                    <div class="relative">
                        <button id="notifBtn" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all relative">
                            <i class="fas fa-bell text-slate-600 dark:text-slate-300"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="absolute top-3 right-3 flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border-2 border-white dark:border-slate-800"></span>
                                </span>
                            <?php endif; ?>
                        </button>
                        <div id="notifMenu" class="hidden absolute right-0 mt-4 w-80 bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-700 z-50 overflow-hidden">
                            <div class="p-6 border-b border-slate-50 dark:border-slate-700 flex justify-between items-center">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Notifications</h3>
                                <?php if ($unread_count > 0): ?>
                                    <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[9px] font-black rounded-full"><?= $unread_count ?> New</span>
                                <?php endif; ?>
                            </div>

                            <div class="max-h-[350px] overflow-y-auto">
                                <?php if (empty($notifications)): ?>
                                    <div class="p-10 text-center">
                                        <i class="fas fa-bell-slash text-slate-200 dark:text-slate-700 text-2xl mb-2"></i>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">All clear for now</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <a href="<?= BASE_URL . $n['link_url'] ?>" class="block p-5 border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors relative">
                                            <?php if (!$n['is_read']): ?>
                                                <span class="absolute top-6 left-2 w-1.5 h-1.5 bg-indigo-600 rounded-full"></span>
                                            <?php endif; ?>
                                            <p class="text-xs font-medium text-slate-600 dark:text-slate-300 leading-relaxed mb-2 <?= !$n['is_read'] ? 'font-bold' : '' ?>">
                                                <?= htmlspecialchars($n['message']) ?>
                                            </p>
                                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter italic">
                                                <?= date('M d, h:i A', strtotime($n['created_at'])) ?>
                                            </p>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <a href="notifications.php" class="block w-full py-4 text-center bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white transition-all">
                                View All History
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-8 mb-16">
                <?php
                $stats = [
                    ['title' => 'Courses', 'val' => $total_enrolled, 'icon' => 'fa-graduation-cap', 'color' => 'indigo'],
                    ['title' => 'Active', 'val' => $in_progress, 'icon' => 'fa-bolt', 'color' => 'amber'],
                    ['title' => 'Finished', 'val' => $completed_courses, 'icon' => 'fa-check-circle', 'color' => 'emerald'],
                    ['title' => 'Deadlines', 'val' => $pending_tasks, 'icon' => 'fa-clock', 'color' => 'rose'],
                ];

                foreach ($stats as $s): ?>
                    <div class="bg-white dark:bg-slate-800 p-6 lg:p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm hover:shadow-xl transition-all duration-500 group">
                        <div class="flex flex-col items-center lg:items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-<?= $s['color'] ?>-50 dark:bg-<?= $s['color'] ?>-500/10 text-<?= $s['color'] ?>-600 dark:text-<?= $s['color'] ?>-400 group-hover:rotate-12 transition-transform">
                                <i class="fas <?= $s['icon'] ?> text-xl"></i>
                            </div>
                            <div class="text-center lg:text-left">
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1"><?= $s['val'] ?></h3>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400"><?= $s['title'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <section class="mb-20">
                <div class="flex justify-between items-center mb-8 px-2">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic">Your Learning <span class="text-indigo-600">Track</span></h2>
                        <p class="text-xs text-slate-400 font-medium">Continue where you left off</p>
                    </div>
                    <a href="my-courses.php" class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest bg-indigo-50 dark:bg-indigo-900/30 px-6 py-3 rounded-xl hover:bg-indigo-600 hover:text-white transition-all">My Courses</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10">
                    <?php foreach ($enrolled_courses as $course):
                        $progress = ($course['total_lessons'] > 0) ? round(($course['completed_lessons'] / $course['total_lessons']) * 100) : 0;
                    ?>
                        <div class="course-card group bg-white dark:bg-slate-800 rounded-[3rem] p-5 border border-slate-100 dark:border-slate-700/50 shadow-sm transition-all duration-500">
                            <div class="relative h-56 mb-6 overflow-hidden rounded-[2.5rem]">
                                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>

                                <div class="absolute bottom-6 left-6 right-6">
                                    <div class="flex justify-between items-center text-white mb-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest opacity-80">Overall Progress</span>
                                        <span class="text-xs font-black"><?= $progress ?>%</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-white/20 backdrop-blur-md rounded-full overflow-hidden">
                                        <div class="h-full bg-white transition-all duration-1000" style="width: <?= $progress ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-3 pb-3">
                                <p class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-2">Instructor: <?= htmlspecialchars($course['first_name']) ?></p>
                                <h3 class="font-black text-xl text-slate-900 dark:text-white mb-6 leading-tight line-clamp-2 min-h-[3.5rem]">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h3>

                                <a href="course-player.php?course_id=<?= $course['id'] ?>"
                                    class="flex items-center justify-center gap-3 w-full py-5 bg-slate-900 dark:bg-indigo-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-indigo-700 transition-all shadow-xl shadow-slate-200 dark:shadow-none">
                                    Continue Module <i class="fas fa-play text-[8px]"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($enrolled_courses)): ?>
                        <div class="col-span-full py-20 text-center bg-white dark:bg-slate-800 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-700">
                            <i class="fas fa-book-open text-4xl text-slate-200 dark:text-slate-700 mb-4 block"></i>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">You haven't enrolled in any courses yet.</p>
                            <a href="../catalog.php" class="mt-6 inline-block text-indigo-600 font-bold hover:underline">Browse the Catalog</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    // Theme Toggle
    const themeBtn = document.getElementById('themeToggle');
    const html = document.documentElement;
    const notifBtn = document.getElementById('notifBtn');
    const notifMenu = document.getElementById('notifMenu');
    const unreadBadge = notifBtn.querySelector('.animate-ping')?.parentElement;

    const PUSH_PUBLIC_KEY = '<?= $_ENV['VAPID_PUBLIC_KEY'] ?? '' ?>';

    notifBtn.addEventListener('click', async (e) => {
        e.stopPropagation();

        const isOpening = notifMenu.classList.contains('hidden');
        notifMenu.classList.toggle('hidden');

        if (isOpening && unreadBadge) {
            try {
                const response = await fetch('<?= BASE_URL ?>ajax/mark_notifications_read.php');
                const result = await response.json();

                if (result.success) {
                    unreadBadge.style.opacity = '0';
                    setTimeout(() => unreadBadge.remove(), 300);
                }
            } catch (error) {
                console.error('Error updating notifications:', error);
            }
        }
    });

    themeBtn.addEventListener('click', () => {
        html.classList.toggle('dark');
        const isDark = html.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        themeBtn.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });

    if (localStorage.getItem('theme') === 'dark') {
        html.classList.add('dark');
        themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
    }

    document.addEventListener('click', (e) => {
        if (!notifMenu.contains(e.target) && !notifBtn.contains(e.target)) {
            notifMenu.classList.add('hidden');
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (window.isSecureContext) {
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                if (Notification.permission === 'default' && !localStorage.getItem('push_prompt_dismissed')) {
                    document.getElementById('pushPrompt').classList.remove('hidden');
                }
            }
        } else {
            console.warn("Push notifications require an HTTPS connection.");
        }
    });

    function dismissPushPrompt() {
        document.getElementById('pushPrompt').classList.add('hidden');
        localStorage.setItem('push_prompt_dismissed', 'true');
    }

    async function initPushSubscription() {
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                // Tell the user why it failed
                document.getElementById('pushPrompt').classList.add('bg-red-500');
                document.getElementById('pushPrompt').innerHTML = `
                    <div class="flex items-center gap-3 text-white font-bold p-1">
                        <i class="fas fa-exclamation-circle"></i> Permissions denied.
                    </div>`;
                setTimeout(() => document.getElementById('pushPrompt').remove(), 3000);
                return;
            }
            const registration = await navigator.serviceWorker.register('<?= BASE_URL ?>push-worker.js');

            // Subscribe the user
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(PUSH_PUBLIC_KEY)
            });

            // Parse subscription for your handler
            const subData = JSON.parse(JSON.stringify(subscription));

            const formData = new FormData();
            formData.append('action', 'subscribe');
            formData.append('endpoint', subData.endpoint);
            formData.append('p256dh', subData.keys.p256dh);
            formData.append('auth', subData.keys.auth);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? "" ?>'); // Ensure you have CSRF set

            const response = await fetch('<?= BASE_URL ?>subscribe_handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                document.getElementById('pushPrompt').innerHTML = `
                <div class="flex items-center gap-3 text-white font-bold p-1">
                    <i class="fas fa-check-circle"></i> Notifications Enabled Successfully!
                </div>`;
                setTimeout(() => document.getElementById('pushPrompt').remove(), 3000);
            }
        } catch (error) {
            console.error('Push Error:', error);
        }
    }

    // Helper function to handle VAPID key encoding
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
</script>
</body>

</html>