<?php
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../includes/config.php';

require_once ROOT_PATH . 'includes/notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . LOGIN_URL);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();
$msg = null;
$msg_type = 'info';

$courses_stmt = $pdo->prepare("
    SELECT id, title
    FROM courses
    WHERE instructor_id = ? AND status = 'published'
    ORDER BY title
");
$courses_stmt->execute([$instructor_id]);
$courses = $courses_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $meeting_link = trim($_POST['meeting_link'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');

    if (empty($course_id) || empty($title) || empty($meeting_link) || empty($start_time)) {
        $msg = "All required fields must be filled.";
        $msg_type = 'danger';
    } elseif (!filter_var($meeting_link, FILTER_VALIDATE_URL)) {
        $msg = "Invalid meeting URL.";
        $msg_type = 'danger';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d H:i', $start_time);
        $is_valid_date = ($dt && $dt->format('Y-m-d H:i') === $start_time && $dt > new DateTime());

        if (!$is_valid_date) {
            $msg = "Invalid or past date/time. Use YYYY-MM-DD HH:MM format.";
            $msg_type = 'danger';
        } else {
            try {
                $start_time_db = $dt->format('Y-m-d H:i:s');

                // Insert live session
                $insert_stmt = $pdo->prepare("
                    INSERT INTO live_sessions 
                    (course_id, title, notes, meeting_link, start_time, instructor_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([$course_id, $title, $notes, $meeting_link, $start_time_db, $instructor_id]);

                $students_stmt = $pdo->prepare("
                    SELECT DISTINCT user_id 
                    FROM enrollments 
                    WHERE course_id = ?
                ");
                $students_stmt->execute([$course_id]);
                $student_ids = $students_stmt->fetchAll(PDO::FETCH_COLUMN);
                $students_notified = count($student_ids);

                if ($students_notified === 0) {
                    $msg = "Live Session scheduled successfully. No students enrolled to notify.";
                    $msg_type = 'info';
                } else {
                    $course_title_stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
                    $course_title_stmt->execute([$course_id]);
                    $course_title = $course_title_stmt->fetchColumn() ?: 'Course';

                    $formatted_time = date('M j, Y \a\t g:i A', $dt->getTimestamp());
                    $message_body = "New Live Session: {$title} in {$course_title} on {$formatted_time}";
                    $link_url = BASE_URL . "dashboard/student/course-player.php?course_id={$course_id}";

                    send_in_app_notifications($pdo, $student_ids, $message_body, $link_url);

                    $subscriptions = get_push_subscriptions($pdo, $student_ids);
                    $push_total = count($subscriptions);
                    $push_success = 0;

                    if ($push_total > 0) {
                        $push_payload = [
                            'title' => 'Live Session!',
                            'body' => $message_body,
                            'url' => $link_url
                        ];
                        $push_success = send_web_push_notifications($pdo, $subscriptions, $push_payload);
                    }

                    $msg = "Live Session scheduled successfully. In-app notifications sent to {$students_notified} students. Push notifications delivered to {$push_success} of {$push_total} devices.";
                    $msg_type = 'success';
                }

                header("Location: live-sessions.php?msg=" . urlencode($msg) . "&type=" . $msg_type);
                exit;

            } catch (Exception $e) {
                error_log("Live Session Error: " . $e->getMessage());
                $msg = "An error occurred while scheduling. Please try again.";
                $msg_type = 'danger';
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'info';
}

$sessions_stmt = $pdo->prepare("
    SELECT ls.*, c.title as course_title
    FROM live_sessions ls
    JOIN courses c ON ls.course_id = c.id
    WHERE ls.instructor_id = ?
    ORDER BY ls.start_time DESC
");
$sessions_stmt->execute([$instructor_id]);
$upcoming_sessions = $sessions_stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    tailwind.config = { darkMode: 'class' }
</script>

<style>
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.5); }
    
    [x-cloak] { display: none !important; }

    /* Glass Effects */
    .glass {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .dark .glass { background: rgba(15, 23, 42, 0.9); }

    /* Custom Input Style for Scheduler */
    .premium-input {
        width: 100%;
        padding: 1rem 1.5rem;
        border-radius: 1rem;
        border: 1px solid #f1f5f9;
        background-color: #f8fafc;
        font-weight: 600;
        color: #1e293b;
        outline: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Flatpickr Customization */
    .flatpickr-calendar {
        border-radius: 1.5rem !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1) !important;
        border: none !important;
        padding: 10px;
    }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300 flex" x-data="{ activeSession: null }">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <main class="p-6 lg:p-10 pb-32">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 dark:text-indigo-400 mb-2 block">Broadcasting Studio</span>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic leading-none">
                        Live <span class="text-indigo-600">Sessions</span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm italic mt-2">Bridge the gap with real-time academic engagement.</p>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="mb-8 p-6 rounded-[1.5rem] flex items-center gap-4 animate-in <?= $msg_type === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/30' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-100 dark:border-amber-800/30' ?>">
                    <i class="fas <?= $msg_type === 'success' ? 'fa-check-circle' : 'fa-info-circle' ?> text-xl"></i>
                    <p class="text-xs font-black uppercase tracking-widest leading-relaxed"><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                
                <div class="lg:col-span-5">
                    <div class="bg-white dark:bg-slate-800 p-8 lg:p-10 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm sticky top-28">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-100 dark:shadow-none">
                                <i class="fas fa-plus text-sm"></i>
                            </div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic">Schedule Session</h3>
                        </div>

                        <form method="POST" id="scheduleForm" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 ml-1">Select Active Course</label>
                                <select name="course_id" class="premium-input appearance-none" required>
                                    <option value="">-- Choose Target Course --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 ml-1">Session Headline</label>
                                <input type="text" name="title" required class="premium-input" placeholder="e.g., Live Q&A: Module 4 Review">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 ml-1">Launch Date & Time</label>
                                    <div class="relative">
                                        <input type="text" name="start_time" id="startTime" required class="premium-input text-xs" placeholder="Select Time">
                                        <i class="fas fa-calendar-alt absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 ml-1">Meeting Destination</label>
                                    <input type="url" name="meeting_link" required class="premium-input text-xs" placeholder="Zoom / Meet Link">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 ml-1">Instructor Notes (Optional)</label>
                                <textarea name="notes" rows="3" class="premium-input text-sm font-medium" placeholder="Pre-requisites or topics for discussion..."></textarea>
                            </div>

                            <button type="submit" class="w-full py-5 bg-slate-900 dark:bg-indigo-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-[0.3em] shadow-2xl hover:bg-indigo-700 hover:-translate-y-1 transition-all">
                                <i class="fas fa-broadcast-tower mr-3"></i> Initialize & Notify Students
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-7">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden">
                        <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic">Session Registry</h2>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Live & Historical Log</p>
                            </div>
                            <span class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-xl text-[10px] font-black uppercase tracking-widest">
                                Total: <?= count($upcoming_sessions) ?>
                            </span>
                        </div>

                        <div class="divide-y divide-slate-50 dark:divide-slate-700">
                            <?php if ($upcoming_sessions): ?>
                                <?php foreach ($upcoming_sessions as $session):
                                    $is_upcoming = strtotime($session['start_time']) > time();
                                ?>
                                    <div class="p-8 hover:bg-slate-50/50 dark:hover:bg-slate-900 transition-all group">
                                        <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h6 class="text-lg font-black text-slate-800 dark:text-white leading-tight">
                                                        <?= htmlspecialchars($session['title']) ?>
                                                    </h6>
                                                    <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest <?= $is_upcoming ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500' : 'bg-slate-100 dark:bg-slate-700 text-slate-400' ?>">
                                                        <?= $is_upcoming ? 'Upcoming' : 'Archive' ?>
                                                    </span>
                                                </div>
                                                <p class="text-[9px] font-black text-indigo-500 uppercase tracking-widest mb-4">
                                                    Course: <?= htmlspecialchars($session['course_title']) ?>
                                                </p>
                                                
                                                <div class="flex items-center gap-6">
                                                    <div class="flex items-center gap-2">
                                                        <i class="far fa-calendar-check text-slate-300 dark:text-slate-600 text-xs"></i>
                                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400"><?= date('M d, Y', strtotime($session['start_time'])) ?></span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="far fa-clock text-slate-300 dark:text-slate-600 text-xs"></i>
                                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400"><?= date('h:i A', strtotime($session['start_time'])) ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-2 w-full md:w-auto">
                                                <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" 
                                                   class="text-center px-6 py-3 <?= $is_upcoming ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100 dark:shadow-none' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' ?> rounded-2xl text-[10px] font-black uppercase tracking-widest hover:opacity-90 transition-all">
                                                    <?= $is_upcoming ? 'Launch Room' : 'Reuse Link' ?>
                                                </a>
                                                <?php if(!empty($session['notes'])): ?>
                                                    <button @click="activeSession === <?= $session['id'] ?> ? activeSession = null : activeSession = <?= $session['id'] ?>" 
                                                            class="text-[9px] font-black text-slate-400 uppercase tracking-widest hover:text-indigo-600 transition-colors">
                                                        View Notes <i class="fas fa-chevron-down ml-1 text-[8px]"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div x-show="activeSession === <?= $session['id'] ?>" x-cloak x-collapse class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
                                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed italic">
                                                <?= nl2br(htmlspecialchars($session['notes'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-20 text-center">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-video-slash text-slate-200 dark:text-slate-700 text-2xl"></i>
                                    </div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">No sessions scheduled.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#startTime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true,
            theme: "dark" // Flatpickr will match dark theme if needed
        });
    });
</script>

</body>
</html>