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
function fetch_student_schedule(PDO $pdo, int $studentId): array
{
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

        // We need the array 3 times for the 3 UNION parts
        $params = array_merge($enrolled_courses, $enrolled_courses, $enrolled_courses);

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
            UNION ALL
            (
                /* NEW: Automated Assessment Deadlines */
                SELECT UPPER(a.type) AS type, a.id AS entity_id, a.course_id,
                       c.title AS course_title, CONCAT(UPPER(a.type), ': ', a.title) AS event_title,
                       a.due_date AS start_time, NULL AS link, CONCAT('AS-', a.id) AS unique_id
                FROM assessments a
                JOIN courses c ON a.course_id = c.id
                WHERE a.course_id IN ($placeholders) AND a.due_date IS NOT NULL
            )
            ORDER BY start_time ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today_list = [];
        $upcoming_list = [];
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        $seven_days = strtotime('+7 days');

        foreach ($all_events as $event) {
            $event_time = strtotime($event['start_time']);
            $event_date = date('Y-m-d', $event_time);

            if ($event_date === date('Y-m-d')) {
                $today_list[] = $event;
            } elseif ($event_time > time() && $event_time <= $seven_days) {
                $upcoming_list[] = $event;
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
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.2);
        border-radius: 10px;
    }

    @media (min-width: 1024px) {
        .main-content-wrapper {
            margin-left: 18rem;
        }
    }

    @media (max-width: 1024px) {
        main {
            padding-bottom: calc(120px + env(safe-area-inset-bottom)) !important;
        }
    }

    /* FullCalendar Theming (Standard CSS Replacement for @apply) */
    .fc {
        --fc-border-color: rgba(226, 232, 240, 0.1);
        --fc-today-bg-color: rgba(99, 102, 241, 0.05);
    }

    .dark .fc {
        --fc-border-color: rgba(255, 255, 255, 0.05);
    }

    .fc .fc-toolbar-title {
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        font-style: italic;
        letter-spacing: 0.1em;
        color: #1e293b;
    }

    .dark .fc .fc-toolbar-title {
        color: #ffffff;
    }

    .fc .fc-button-primary {
        background-color: #f1f5f9 !important;
        border: none !important;
        color: #475569 !important;
        font-weight: 900;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border-radius: 0.75rem !important;
        padding: 0.5rem 1rem !important;
        transition: all 0.3s;
    }

    .dark .fc .fc-button-primary {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }

    .fc .fc-button-primary:hover {
        background-color: #6366f1 !important;
        color: #ffffff !important;
    }

    .fc .fc-col-header-cell-cushion {
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
        padding: 0.75rem 0;
    }

    .fc-daygrid-day-number {
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
        padding: 0.5rem !important;
    }

    .fc-event {
        border: none !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        border-radius: 0.5rem !important;
        padding: 2px 4px !important;
        transition: transform 0.2s;
    }

    .fc-event:hover {
        transform: scale(1.05);
    }

    .fc-event-live_session {
        background-color: #ef4444 !important;
        color: #ffffff !important;
    }

    .fc-event-quiz {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
    }

    .fc-event-assignment {
        background-color: #10b981 !important;
        color: #ffffff !important;
    }

    /* Custom Radar Line */
    .radar-pulse {
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        border-top-left-radius: 1.5rem;
        border-bottom-left-radius: 1.5rem;
    }

    @keyframes scan {
        0% {
            transform: translateY(-100%);
        }

        100% {
            transform: translateY(200%);
        }
    }

    .animate-scan {
        animation: scan 8s linear infinite;
    }

    #globe-container canvas {
        border-radius: 3rem;
        /* Matches the section rounding */
        outline: none;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">

        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600 dark:text-indigo-400 mb-2 block">Academic Logistics</span>
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic leading-none">
                        Learning <span class="text-indigo-600 dark:text-brand-500">Timeline</span>
                    </h1>
                    <div class="flex items-center gap-3 mt-4">
                        <span class="h-1 w-12 bg-indigo-600 dark:bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                            Synchronizing <span class="text-slate-900 dark:text-slate-200 font-bold"><?= count($schedule_data['all_events']) ?></span> curriculum checkpoints
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 bg-white dark:bg-slate-800 p-3 rounded-[1.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm">
                    <div class="px-6 py-1 text-center border-r border-slate-100 dark:border-slate-700">
                        <p class="text-[8px] font-black uppercase text-indigo-600 dark:text-indigo-400 tracking-[0.2em] mb-1">Due Today</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white"><?= count($schedule_data['today']) ?></p>
                    </div>
                    <div class="px-6 py-1 text-center">
                        <p class="text-[8px] font-black uppercase text-slate-400 tracking-[0.2em] mb-1">Week Ahead</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white"><?= count($schedule_data['upcoming']) ?></p>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-12 gap-10">

                <div class="lg:col-span-5 space-y-12">

                    <section>
                        <div class="flex items-center gap-4 mb-8">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-brand-500">Active Radar</h3>
                            <div class="h-px flex-1 bg-gradient-to-r from-indigo-100 dark:from-slate-800 to-transparent"></div>
                        </div>

                        <div class="space-y-4">
                            <?php if (empty($schedule_data['today'])): ?>
                                <div class="bg-white/50 dark:bg-slate-800/30 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2.5rem] p-12 text-center">
                                    <i class="fas fa-calendar-check text-slate-200 dark:text-slate-700 text-3xl mb-4 block"></i>
                                    <p class="text-slate-400 italic text-[11px] font-black uppercase tracking-widest">No immediate actions</p>
                                </div>
                                <?php else: foreach ($schedule_data['today'] as $event): ?>
                                    <div class="group relative bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-[2rem] p-6 transition-all hover:shadow-2xl hover:-translate-y-1">
                                        <div class="radar-pulse <?= $event['type'] === 'LIVE_SESSION' ? 'bg-red-500 shadow-[0_0_15px_rgba(239,68,68,0.4)]' : 'bg-indigo-600 shadow-[0_0_15px_rgba(99,102,241,0.4)]' ?>"></div>
                                        <div class="flex justify-between items-start gap-4">
                                            <div>
                                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($event['course_title']) ?></span>
                                                <h4 class="text-lg font-black text-slate-900 dark:text-white uppercase italic mt-1 leading-tight"><?= htmlspecialchars($event['event_title']) ?></h4>
                                                <div class="flex items-center gap-4 mt-5">
                                                    <span class="px-3 py-1 bg-slate-50 dark:bg-slate-900 text-[10px] font-black text-indigo-600 dark:text-indigo-400 rounded-lg flex items-center gap-2">
                                                        <i class="far fa-clock"></i> <?= date('g:i A', strtotime($event['start_time'])) ?>
                                                    </span>
                                                    <span class="text-[8px] font-black text-slate-300 dark:text-slate-600 uppercase tracking-widest"><?= $event['type'] ?></span>
                                                </div>
                                            </div>
                                            <a href="<?= $event['link'] ?: 'course-player.php?course_id=' . $event['course_id'] ?>"
                                                class="w-12 h-12 flex items-center justify-center bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl hover:scale-110 transition-transform shadow-lg">
                                                <i class="fas <?= $event['type'] === 'LIVE_SESSION' ? 'fa-broadcast-tower' : 'fa-play' ?> text-xs"></i>
                                            </a>
                                        </div>
                                    </div>
                            <?php endforeach;
                            endif; ?>
                        </div>
                    </section>

                    <section>
                        <div class="flex items-center gap-4 mb-8">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Sequential Events</h3>
                            <div class="h-px flex-1 bg-gradient-to-r from-slate-100 dark:from-slate-800 to-transparent"></div>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($schedule_data['upcoming'] as $event): ?>
                                <div class="flex items-center justify-between p-5 bg-white dark:bg-slate-800/40 border border-slate-100 dark:border-slate-700/50 rounded-[1.5rem] group transition-all hover:border-indigo-500/30">
                                    <div class="flex items-center gap-5">
                                        <div class="text-center min-w-[55px] py-2 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-700">
                                            <p class="text-[8px] font-black text-indigo-600 dark:text-brand-500 uppercase leading-none mb-1"><?= date('M', strtotime($event['start_time'])) ?></p>
                                            <p class="text-lg font-black text-slate-900 dark:text-white leading-none"><?= date('d', strtotime($event['start_time'])) ?></p>
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-black text-slate-800 dark:text-white leading-tight uppercase italic"><?= htmlspecialchars($event['event_title']) ?></h5>
                                            <p class="text-[9px] text-slate-400 uppercase font-bold tracking-widest mt-1"><?= htmlspecialchars($event['course_title']) ?></p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-slate-200 group-hover:text-indigo-600 transition-colors text-[10px]"></i>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <section class="mb-10 group">
                        <div class="relative bg-slate-900 dark:bg-black rounded-[3rem] overflow-hidden border border-slate-800 shadow-2xl h-[350px]">
                            <div id="globe-container" class="absolute inset-0 cursor-grab active:cursor-grabbing"></div>

                            <div class="absolute bottom-8 left-8 z-10 pointer-events-none">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                    <span class="text-[10px] font-black text-white uppercase tracking-[0.4em]">Satellite Link: Active</span>
                                </div>
                                <h3 class="text-white text-lg font-black uppercase italic mt-1 tracking-tighter">Global <span class="text-indigo-400">Sync</span></h3>
                            </div>

                            <div class="absolute inset-0 pointer-events-none border-[1px] border-white/5 rounded-[3rem] overflow-hidden">
                                <div class="w-full h-1/2 bg-gradient-to-b from-indigo-500/10 to-transparent absolute top-0 animate-scan"></div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="lg:col-span-7">
                    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-[3rem] p-8 shadow-sm lg:sticky lg:top-24">
                        <div id='calendar' class="custom-calendar"></div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/earth.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme Loader
        const html = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            html.classList.add('dark');
        }

        const eventsData = <?= json_encode($schedule_data['all_events']) ?>;
        const calendarEl = document.getElementById('calendar');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: 'today'
            },
            height: 'auto',
            events: eventsData.map(e => {
                let targetUrl = e.link;

                // Custom routing based on event type
                if (!targetUrl) {
                    if (e.type === 'QUIZ') {
                        targetUrl = `take-quiz.php?id=${e.entity_id}`;
                    } else if (e.type === 'ASSIGNMENT') {
                        targetUrl = `view-assessment.php?id=${e.entity_id}`;
                    } else {
                        targetUrl = `course-player.php?course_id=${e.course_id}`;
                    }
                }

                return {
                    title: e.event_title,
                    start: e.start_time,
                    className: `fc-event-${e.type.toLowerCase()}`,
                    url: targetUrl
                };
            }),
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
</body>

</html>