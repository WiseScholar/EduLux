<?php
require_once __DIR__ . '/../../includes/config.php';

// Auth Check - Advanced Role enforcement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

// 1. Core Analytics
$total_courses_stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
$total_courses_stmt->execute([$instructor_id]);
$total_courses = $total_courses_stmt->fetchColumn();

$total_students_stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ?");
$total_students_stmt->execute([$instructor_id]);
$total_students = $total_students_stmt->fetchColumn();

$total_earnings_stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN courses c ON p.course_id = c.id WHERE c.instructor_id = ? AND p.status = 'completed'");
$total_earnings_stmt->execute([$instructor_id]);
$earnings = $total_earnings_stmt->fetchColumn() ?: 0;

$greeting = date('H') < 12 ? "Good morning" : (date('H') < 17 ? "Good afternoon" : "Good evening");

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    tailwind.config = {
        darkMode: 'class'
    }
</script>

<style>
    ::-webkit-scrollbar {
        width: 6px; /* Slightly wider than sidebar for better usability on main content */
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.2); /* Indigo color matching your theme */
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(99, 102, 241, 0.5);
    }
    
    /* For Firefox */
    * {
        scrollbar-width: thin;
        scrollbar-color: rgba(99, 102, 241, 0.2) transparent;
    }

    html {
        scroll-behavior: smooth;
    }

    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .dark .glass {
        background: rgba(15, 23, 42, 0.9);
    }

    .nav-active {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.1) 0%, transparent 100%);
        color: #6366f1;
        border-left: 4px solid #6366f1;
    }

    @media (max-width: 1024px) {
        main {
            padding-bottom: 100px !important;
        }
    }

    #navPanelBtn {
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 15px rgba(99, 102, 241, 0);
        }

        100% {
            transform: scale(1);
        }
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 flex-1">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        <?= $greeting ?>, <span
                            class="text-indigo-600"><?= htmlspecialchars($_SESSION['first_name']) ?></span>!
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Your instructor portal is up to date.</p>
                </div>

                <div class="flex items-center space-x-3">
                    <button id="themeToggle"
                        class="w-11 h-11 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-yellow-400 shadow-sm">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="create-course.php"
                        class="hidden md:flex items-center space-x-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 dark:shadow-none hover:bg-indigo-700 transition-all">
                        <i class="fas fa-plus"></i> <span>New Course</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                            <i class="fas fa-book-open text-xl"></i>
                        </div>
                        <span
                            class="text-xs font-bold text-emerald-500 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded-lg">+2
                            this month</span>
                    </div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Courses</p>
                    <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($total_courses) ?>
                    </h3>
                </div>

                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <span
                            class="text-xs font-bold text-indigo-500 bg-indigo-100 dark:bg-indigo-900/30 px-2 py-1 rounded-lg">+14%
                            vs last week</span>
                    </div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Students</p>
                    <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($total_students) ?>
                    </h3>
                </div>

                <div
                    class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase">Payout Pending</span>
                    </div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Earnings (GHS)</p>
                    <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($earnings, 2) ?>
                    </h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12 items-start">
                <div
                    class="lg:col-span-2 bg-white dark:bg-slate-800 p-8 rounded-3xl border border-slate-100 dark:border-slate-700/50 flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-xl dark:text-white">Revenue Overview</h3>
                        <select
                            class="bg-slate-50 dark:bg-slate-900 border-none text-sm rounded-lg p-2 dark:text-slate-300">
                            <option>Last 7 Days</option>
                        </select>
                    </div>
                    <div class="relative h-[300px] w-full">
                        <canvas id="earningsChart"></canvas>
                    </div>
                </div>

                <div class="bg-indigo-600 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl">
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold mb-2">Create Content</h3>
                        <p class="text-indigo-100 text-sm mb-8">Ready to expand your reach? Start building your next
                            advanced module.</p>
                        <div class="space-y-4">
                            <a href="create-course.php"
                                class="flex items-center justify-between bg-white/10 hover:bg-white/20 p-4 rounded-2xl transition-all border border-white/10">
                                <span class="font-medium">New Module</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                            <a href="live-sessions.php"
                                class="flex items-center justify-between bg-white/10 hover:bg-white/20 p-4 rounded-2xl transition-all border border-white/10">
                                <span class="font-medium">Go Live Now</span>
                                <i class="fas fa-video"></i>
                            </a>
                        </div>
                    </div>
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                </div>
            </div>

        </main>
    </div>
</div>

<div class="lg:hidden fixed bottom-0 left-0 right-0 z-[60]">
    <div
        class="absolute bottom-0 w-full h-20 bg-white dark:bg-slate-800 shadow-[0_-5px_25px_rgba(0,0,0,0.1)] border-t border-slate-100 dark:border-slate-700">
        <div
            class="absolute left-1/2 -top-8 -translate-x-1/2 w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center">
            <div
                class="w-16 h-16 bg-white dark:bg-slate-800 rounded-full border-t border-slate-100 dark:border-slate-700">
            </div>
        </div>
    </div>
    <div class="relative flex justify-around items-center h-20 px-4">
        <a href="index.php" class="flex flex-col items-center text-indigo-600">
            <i class="fas fa-chart-line text-xl"></i>
            <span class="text-[10px] font-bold mt-1 uppercase">Stats</span>
        </a>
        <a href="my-courses.php" class="flex flex-col items-center text-slate-400">
            <i class="fas fa-layer-group text-xl"></i>
            <span class="text-[10px] font-medium mt-1 uppercase">Courses</span>
        </a>
        <div class="relative -top-10">
            <button id="navPanelBtn"
                class="w-14 h-14 bg-indigo-600 rounded-2xl shadow-xl flex items-center justify-center text-white active:scale-90 transition-all">
                <i class="fas fa-plus text-xl"></i>
            </button>
        </div>
        <a href="students.php" class="flex flex-col items-center text-slate-400">
            <i class="fas fa-user-graduate text-xl"></i>
            <span class="text-[10px] font-medium mt-1 uppercase">Users</span>
        </a>
        <a href="earnings.php" class="flex flex-col items-center text-slate-400">
            <i class="fas fa-wallet text-xl"></i>
            <span class="text-[10px] font-medium mt-1 uppercase">Payout</span>
        </a>
    </div>
</div>

<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

<script>
    // Theme & Sidebar Toggle
    const themeBtn = document.getElementById('themeToggle');
    const html = document.documentElement;
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    }

    themeBtn.addEventListener('click', () => {
        html.classList.toggle('dark');
        const isDark = html.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });

    if (localStorage.getItem('theme') === 'dark') html.classList.add('dark');

    document.addEventListener('DOMContentLoaded', () => {
        const navPanelBtn = document.getElementById('navPanelBtn');
        if (navPanelBtn) navPanelBtn.addEventListener('click', toggleSidebar);

        // Chart.js
        const ctx = document.getElementById('earningsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    data: [120, 190, 300, 250, 400, 350, 500],
                    borderColor: '#6366f1',
                    borderWidth: 4,
                    pointRadius: 0,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: (context) => {
                        const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
                        gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');
                        return gradient;
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // This allows the container height to control it
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            maxTicksLimit: 5
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    });
</script>
</body>

</html>