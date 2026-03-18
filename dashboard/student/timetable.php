<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

/**
 * Fetches unified events from Live Sessions and Course Schedules
 */
function fetch_student_schedule(PDO $pdo, int $studentId): array {
    try {
        $enrolled_stmt = $pdo->prepare("
            SELECT course_id 
            FROM enrollments 
            WHERE user_id = ? AND status IN ('active', 'in-progress', 'enrolled')
        ");
        $enrolled_stmt->execute([$studentId]);
        $enrolled_courses = $enrolled_stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($enrolled_courses)) {
            return ['all_events' => [], 'today' => [], 'upcoming' => []];
        }

        $placeholders = implode(',', array_fill(0, count($enrolled_courses), '?'));
        $params = array_merge($enrolled_courses, $enrolled_courses);

        $sql = "
            (
                SELECT 'LIVE_SESSION' AS type, ls.id AS entity_id, ls.course_id,
                       c.title AS course_title, ls.title AS event_title,
                       ls.start_time, ls.meeting_link AS link, CONCAT('LS-', ls.id) AS unique_id
                FROM live_sessions ls
                JOIN courses c ON ls.course_id = c.id
                WHERE ls.course_id IN ($placeholders)
            )
            UNION ALL
            (
                SELECT cs.type, cs.id AS entity_id, cs.course_id,
                       c.title AS course_title, cs.title AS event_title,
                       cs.start_time, NULL AS link, CONCAT('CS-', cs.id) AS unique_id
                FROM course_schedule cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.course_id IN ($placeholders)
            )
            ORDER BY start_time ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today_list = [];
        $upcoming_list = [];
        $today_date = date('Y-m-d');
        $seven_days = strtotime('+7 days');

        foreach ($all_events as $event) {
            $event_time = strtotime($event['start_time']);
            if ($event_time >= strtotime('today')) {
                if (date('Y-m-d', $event_time) === $today_date) {
                    $today_list[] = $event;
                } elseif ($event_time <= $seven_days) {
                    $upcoming_list[] = $event;
                }
            }
        }

        return ['all_events' => $all_events, 'today' => $today_list, 'upcoming' => $upcoming_list];
    } catch (Exception $e) {
        return ['all_events' => [], 'today' => [], 'upcoming' => []];
    }
}

$schedule_data = fetch_student_schedule($pdo, $student_id);
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
        .main-content-wrapper { margin-left: 18rem; }
    }

    @media (max-width: 1024px) {
        main { 
            padding-bottom: calc(120px + env(safe-area-inset-bottom)) !important; 
        }
    }

    /* FullCalendar Theming Overrides to match your Library theme */
    :root {
        --fc-border-color: rgba(255,255,255,0.05);
        --fc-today-bg-color: rgba(234, 179, 8, 0.1);
    }
    .dark :root {
        --fc-border-color: rgba(255,255,255,0.05);
    }
    .light :root {
        --fc-border-color: rgba(0,0,0,0.05);
        --fc-today-bg-color: rgba(0, 45, 114, 0.05);
    }

    .custom-calendar { font-family: inherit; }
    .fc .fc-toolbar-title { font-size: 1.1rem !important; font-weight: 900 !important; text-transform: uppercase; font-style: italic; letter-spacing: 0.05em; }
    .fc .fc-button-primary { 
        background: rgba(125,125,125,0.1) !important; 
        border: 1px solid rgba(125,125,125,0.2) !important; 
        color: inherit !important;
        text-transform: uppercase; 
        font-size: 9px !important; 
        font-weight: 900 !important; 
        border-radius: 12px !important;
    }
    .fc .fc-button-primary:hover { background: #eab308 !important; color: #000 !important; }
    .fc-event { border: none !important; border-radius: 6px !important; font-size: 9px !important; font-weight: 800 !important; text-transform: uppercase; }
    .fc-event-live_session { background: #ef4444 !important; color: white !important; }
    .fc-event-quiz { background: #002d72 !important; color: white !important; }
</style>

<div class="min-h-screen bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-500 flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
                <div class="space-y-2">
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic">
                        Learning <span class="text-brand-900 dark:text-brand-500">Timeline</span>
                    </h1>
                    <div class="flex items-center gap-3">
                        <span class="h-1 w-12 bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                            Tracking <span class="text-slate-900 dark:text-slate-200 font-bold"><?= count($schedule_data['all_events']) ?></span> scheduled activities
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 bg-white dark:bg-slate-800 p-2 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                    <div class="px-4 py-1 text-center border-r border-slate-100 dark:border-slate-700">
                        <p class="text-[9px] font-black uppercase text-brand-500 tracking-widest">Today</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white"><?= count($schedule_data['today']) ?></p>
                    </div>
                    <div class="px-4 py-1 text-center">
                        <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Upcoming</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white"><?= count($schedule_data['upcoming']) ?></p>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-12 gap-10">
                
                <div class="lg:col-span-5 space-y-10">
                    
                    <section>
                        <div class="flex items-center gap-4 mb-6">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-brand-900 dark:text-brand-500">On The Radar: Today</h3>
                            <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                        </div>

                        <div class="space-y-4">
                            <?php if (empty($schedule_data['today'])): ?>
                                <div class="bg-white dark:bg-slate-800/40 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2rem] p-8 text-center">
                                    <p class="text-slate-400 italic text-sm font-medium">Clear schedule for today.</p>
                                </div>
                            <?php else: foreach ($schedule_data['today'] as $event): ?>
                                <div class="group relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl p-6 transition-all hover:shadow-xl hover:-translate-y-1">
                                    <div class="absolute top-0 left-0 w-1.5 h-full rounded-l-3xl <?= $event['type'] === 'LIVE_SESSION' ? 'bg-red-500' : 'bg-brand-500' ?>"></div>
                                    <div class="flex justify-between items-start gap-4">
                                        <div>
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($event['course_title']) ?></span>
                                            <h4 class="text-lg font-black text-slate-900 dark:text-white uppercase italic mt-1"><?= htmlspecialchars($event['event_title']) ?></h4>
                                            <div class="flex items-center gap-3 mt-4">
                                                <span class="text-[10px] font-bold text-brand-900 dark:text-brand-500 uppercase flex items-center gap-1.5">
                                                    <i class="far fa-clock"></i> <?= date('g:i A', strtotime($event['start_time'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <a href="<?= $event['link'] ?: 'course-player.php?course_id=' . $event['course_id'] ?>" 
                                           class="w-10 h-10 flex items-center justify-center bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl hover:scale-110 transition-transform">
                                            <i class="fas <?= $event['type'] === 'LIVE_SESSION' ? 'fa-video' : 'fa-play' ?> text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </section>

                    <section>
                        <div class="flex items-center gap-4 mb-6">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Later This Week</h3>
                            <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                        </div>
                        <div class="grid gap-3">
                            <?php foreach ($schedule_data['upcoming'] as $event): ?>
                                <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl group transition-all hover:border-brand-500/50">
                                    <div class="flex items-center gap-4">
                                        <div class="text-center min-w-[50px] py-1 bg-slate-100 dark:bg-slate-700 rounded-xl">
                                            <p class="text-[8px] font-black text-brand-500 uppercase leading-none mb-1"><?= date('M', strtotime($event['start_time'])) ?></p>
                                            <p class="text-md font-black text-slate-900 dark:text-white"><?= date('d', strtotime($event['start_time'])) ?></p>
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-bold text-slate-900 dark:text-white leading-tight"><?= htmlspecialchars($event['event_title']) ?></h5>
                                            <p class="text-[9px] text-slate-400 uppercase font-black tracking-tighter mt-0.5"><?= htmlspecialchars($event['course_title']) ?></p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-slate-300 dark:text-slate-700 group-hover:text-brand-500 transition-colors"></i>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>

                <div class="lg:col-span-7">
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[3rem] p-8 shadow-sm">
                        <div id='calendar' class="custom-calendar text-slate-900 dark:text-slate-200"></div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme Inheritance logic
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }

        // Sidebar Toggle Logic (Handles the "Middle Button" in bottom-nav.php)
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', (e) => {
                e.preventDefault();
                sidebar.classList.toggle('-translate-x-full');
                if(overlay) overlay.classList.toggle('hidden');
            });
        }

        // Calendar Initialization
        const eventsData = <?= json_encode($schedule_data['all_events']) ?>;
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
            events: eventsData.map(e => ({
                title: e.event_title,
                start: e.start_time,
                className: `fc-event-${e.type.toLowerCase()}`,
                url: e.link || `course-player.php?course_id=${e.course_id}`
            })),
            eventClick: function(info) {
                if (info.event.url) {
                    info.jsEvent.preventDefault();
                    window.location.href = info.event.url;
                }
            }
        });
        calendar.render();
    });
</script>

<?php include 'bottom-nav.php'; ?>
</body>
</html>