<?php
require_once __DIR__ . '/../../includes/config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL . "login.php");
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
                    brand: { 900: '#002d72', 500: '#eab308' }
                }
            }
        }
    }
</script>

<style>
    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 18rem;
        }
    }

    @media (max-width: 1024px) {
        main {
            padding-bottom: 90px !important;
        }
    }

    /* Simple fade-in for cards */
    .course-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-300 flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-4 lg:p-10 flex-1 max-w-7xl mx-auto w-full">

            <div class="flex justify-between items-center mb-10">
                <div>
                    <div id="pushPrompt"
                        class="hidden mb-6 p-4 bg-indigo-600 rounded-3xl flex items-center justify-between shadow-lg shadow-indigo-200 dark:shadow-none transition-all duration-500">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <p class="text-white font-bold text-sm">Don't miss assignment deadlines!</p>
                                <p class="text-indigo-100 text-xs">Enable desktop notifications to stay updated.</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="dismissPushPrompt()"
                                class="px-4 py-2 text-white/70 text-xs font-bold uppercase tracking-wider">Later</button>
                            <button onclick="initPushSubscription()"
                                class="bg-white text-indigo-600 px-6 py-2 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-indigo-50 transition-colors">Enable</button>
                        </div>
                    </div>
                    <h1 class="text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                        <?= $greeting ?>, <span
                            class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-violet-500"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">It's a great day to learn something new.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button id="themeToggle"
                        class="p-3 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm hover:ring-2 ring-indigo-500/20 transition-all">
                        <i class="fas fa-moon dark:text-yellow-400"></i>
                    </button>
                    <div class="relative" id="notificationDropdownContainer">
                        <button id="notifBtn"
                            class="p-3 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm transition-all hover:bg-slate-50 dark:hover:bg-slate-700">
                            <i class="fas fa-bell text-slate-600 dark:text-slate-300"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="absolute top-2 right-2 flex h-3 w-3">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                </span>
                            <?php endif; ?>
                        </button>

                        <div id="notifMenu"
                            class="hidden absolute right-0 mt-3 w-80 bg-white dark:bg-slate-800 rounded-[2rem] shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50">
                            <div
                                class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                                <span
                                    class="font-black text-slate-900 dark:text-white uppercase text-xs tracking-wider">Notifications</span>
                                <?php if ($unread_count > 0): ?>
                                    <span
                                        class="text-[10px] bg-indigo-600 text-white px-2 py-0.5 rounded-full"><?= $unread_count ?>
                                        New</span>
                                <?php endif; ?>
                            </div>
                            <div class="max-h-[350px] overflow-y-auto">
                                <?php if (empty($notifications)): ?>
                                    <div class="p-8 text-center">
                                        <i class="fas fa-bell-slash text-slate-300 mb-2 block text-2xl"></i>
                                        <p class="text-xs text-slate-500">All caught up!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <a href="<?= htmlspecialchars($n['link_url']) ?>"
                                            class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors border-b border-slate-50 dark:border-slate-700/30">
                                            <p
                                                class="text-sm <?= $n['is_read'] ? 'text-slate-500' : 'text-slate-900 dark:text-white font-semibold' ?>">
                                                <?= htmlspecialchars($n['message']) ?>
                                            </p>
                                            <span class="text-[10px] text-slate-400 mt-1 block">
                                                <?= date('M d, h:i A', strtotime($n['created_at'])) ?>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <a href="notifications.php"
                                class="block p-3 text-center text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-colors">
                                VIEW ALL NOTIFICATIONS
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-12">
                <?php
                $stats = [
                    ['title' => 'Enrolled', 'val' => $total_enrolled, 'icon' => 'fa-graduation-cap', 'color' => 'indigo'],
                    ['title' => 'In Progress', 'val' => $in_progress, 'icon' => 'fa-bolt', 'color' => 'amber'],
                    ['title' => 'Completed', 'val' => $completed_courses, 'icon' => 'fa-check-circle', 'color' => 'emerald'],
                    ['title' => 'Tasks Due', 'val' => $pending_tasks, 'icon' => 'fa-tasks', 'color' => 'rose'],
                ];

                foreach ($stats as $s): ?>
                    <div
                        class="group bg-white dark:bg-slate-800/50 backdrop-blur-sm p-5 lg:p-6 rounded-[2rem] border border-slate-200/60 dark:border-slate-700/50 hover:border-<?= $s['color'] ?>-500/50 transition-all duration-300">
                        <div class="flex flex-col gap-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center 
                    bg-<?= $s['color'] ?>-50 dark:bg-<?= $s['color'] ?>-500/10 
                    text-<?= $s['color'] ?>-600 dark:text-<?= $s['color'] ?>-400 
                    group-hover:scale-110 transition-transform">
                                <i class="fas <?= $s['icon'] ?> text-xl"></i>
                            </div>

                            <div>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                                    <?= $s['val'] ?>
                                </h3>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                    <?= $s['title'] ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <section class="mb-12">
                <div class="flex justify-between items-center mb-6 px-2">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Recent
                        Learning</h2>
                    <a href="my-courses.php"
                        class="text-xs font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-4 py-2 rounded-full hover:bg-indigo-100 transition-all">VIEW
                        ALL</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                    <?php foreach ($enrolled_courses as $course):
                        $progress = ($course['total_lessons'] > 0) ? round(($course['completed_lessons'] / $course['total_lessons']) * 100) : 0;
                        ?>
                        <div
                            class="group relative bg-white dark:bg-slate-800 rounded-[2.5rem] p-4 border border-slate-200/60 dark:border-slate-700/50 hover:shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] transition-all duration-500">
                            <div class="relative h-48 mb-4 overflow-hidden rounded-[2rem]">
                                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <div class="absolute bottom-4 left-4 right-4 flex justify-between items-center text-white">
                                    <span
                                        class="text-[10px] font-black bg-white/20 backdrop-blur-md px-3 py-1 rounded-full uppercase"><?= $progress ?>%
                                        Done</span>
                                </div>
                            </div>

                            <div class="px-2 pb-2">
                                <h3 class="font-black text-lg text-slate-900 dark:text-white mb-2 leading-tight">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h3>
                                <div class="flex items-center gap-2 mb-6">
                                    <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full"
                                            style="width: <?= $progress ?>%"></div>
                                    </div>
                                </div>
                                <a href="course-player.php?course_id=<?= $course['id'] ?>"
                                    class="flex items-center justify-center gap-2 w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-sm hover:opacity-90 transition-all">
                                    CONTINUE LEARNING <i class="fas fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
        for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
        return outputArray;
    }
</script>
</body>

</html>