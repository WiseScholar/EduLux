<?php
// dashboard/student/timetable.php - Student's Unified Timetable
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL);
    exit;
}

$student_id = $_SESSION['user_id'];
$msg = null;
$msg_type = 'info';

/**
 * Fetches and unifies all scheduled events for the student's calendar based on their enrollments.
 * CRITICAL FIX: Enforce explicit casting of course_id in the JOIN to solve subquery type mismatch issue.
 * @param int $studentId The ID of the current student.
 * @return array The unified array of schedule events.
 */
function fetch_student_schedule(PDO $pdo, int $studentId): array {
    try {
        // Get enrolled course IDs
        $enrolled_stmt = $pdo->prepare("
            SELECT course_id 
            FROM enrollments 
            WHERE user_id = ? AND status = 'completed'
        ");
        $enrolled_stmt->execute([$studentId]);
        $enrolled_courses = $enrolled_stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($enrolled_courses)) {
            return ['all_events' => [], 'today' => [], 'upcoming' => []];
        }

        // Create placeholders and double the array for two IN clauses
        $placeholders = implode(',', array_fill(0, count($enrolled_courses), '?'));
        $params = array_merge($enrolled_courses, $enrolled_courses); // Double for two IN clauses

        $sql = "
            (
                SELECT 
                    'LIVE_SESSION' AS type,
                    ls.id AS entity_id,
                    ls.course_id,
                    c.title AS course_title,
                    ls.title AS event_title,
                    ls.start_time,
                    ls.meeting_link AS link,
                    CONCAT('LS-', ls.id) AS unique_id
                FROM live_sessions ls
                JOIN courses c ON ls.course_id = c.id
                WHERE ls.course_id IN ($placeholders)
            )
            UNION ALL
            (
                SELECT 
                    cs.type,
                    cs.id AS entity_id,
                    cs.course_id,
                    c.title AS course_title,
                    cs.title AS event_title,
                    cs.start_time,
                    NULL AS link,
                    CONCAT('CS-', cs.id) AS unique_id
                FROM course_schedule cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.course_id IN ($placeholders)
            )
            ORDER BY start_time ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params); // Pass doubled array

        $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter today & upcoming (next 7 days)
        $today_list = [];
        $upcoming_list = [];
        $today_date = date('Y-m-d');
        $seven_days = strtotime('+7 days');

        foreach ($all_events as $event) {
            $event_time = strtotime($event['start_time']);
            if ($event_time >= time() && $event_time <= $seven_days) {
                if (date('Y-m-d', $event_time) === $today_date) {
                    $today_list[] = $event;
                } else {
                    $upcoming_list[] = $event;
                }
            }
        }

        return [
            'all_events' => $all_events,
            'today' => $today_list,
            'upcoming' => $upcoming_list
        ];

    } catch (Exception $e) {
        error_log("Student Timetable Error: " . $e->getMessage());
        return ['all_events' => [], 'today' => [], 'upcoming' => []];
    }
}

$schedule_data = fetch_student_schedule($pdo, $student_id);
$schedule_events_json = json_encode($schedule_data['all_events']);

require_once ROOT_PATH . 'includes/header.php';
?>

<style>
    /* Add styles for student schedule visibility */
    .schedule-list-item {
        transition: background-color 0.2s;
        border-radius: 12px;
        padding: 1rem;
        background: var(--card-bg);
        border: 1px solid var(--border);
    }
    .schedule-list-item:hover {
        background: var(--hover-bg);
    }
    .schedule-list-item.live-session {
        border-left: 5px solid var(--danger);
    }
    .schedule-list-item.quiz {
        border-left: 5px solid var(--primary);
    }
    .schedule-list-item.milestone {
        border-left: 5px solid var(--success);
    }
    /* Style for FullCalendar to fit student theme */
    #calendar {
        margin-top: 20px;
        background: var(--card-bg);
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .fc .fc-toolbar-title {
        color: var(--text);
    }
    .fc-event-live { background-color: var(--danger); }
    .fc-event-quiz { background-color: var(--primary); }
    .fc-event-milestone { background-color: var(--success); }
    .fc-event-other { background-color: var(--secondary); }
    /* Ensure other calendar text respects dark mode */
    .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border) !important; }
    .fc-theme-standard a { color: var(--text); }
</style>

<div class="dashboard-container container-fluid py-5">
    <h2 class="fw-bold mb-4">🗓️ My Learning Timetable</h2>
    <p class="lead text-muted mb-5">View of all your upcoming live sessions and deadlines across your enrolled courses.</p>

    <div class="row g-5">
        
        <div class="col-lg-5">
            <h3 class="fw-bold text-primary mb-3">Upcoming Activities</h3>
            
            <?php if (!empty($schedule_data['today'])): ?>
                <h4 class="mb-3">Today, <?= date('M j, Y') ?></h4>
                <div class="d-grid gap-3 mb-5">
                    <?php foreach ($schedule_data['today'] as $event): ?>
                        <div class="schedule-list-item <?= strtolower(str_replace('_', '-', $event['type'])) ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($event['course_title']) ?></span>
                                    <h5 class="fw-bold mt-1 mb-1"><?= htmlspecialchars($event['event_title']) ?></h5>
                                    <p class="small text-muted mb-0">
                                        <i class="fas fa-clock me-1"></i> 
                                        <?= date('g:i A', strtotime($event['start_time'])) ?>
                                        <span class="ms-3 badge bg-info"><?= str_replace('_', ' ', $event['type']) ?></span>
                                    </p>
                                </div>
                                
                                <a href="<?= $event['link'] ?: BASE_URL . 'dashboard/student/course-player.php?course_id=' . $event['course_id'] ?>" 
                                    class="btn btn-sm btn-<?= $event['type'] === 'LIVE_SESSION' ? 'danger' : 'primary' ?>"
                                    target="<?= $event['type'] === 'LIVE_SESSION' ? '_blank' : '_self' ?>">
                                    <?= $event['type'] === 'LIVE_SESSION' ? 'Join Session' : 'Go to Course' ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 class="mb-3">Later This Week</h4>
            <div class="d-grid gap-3">
                <?php if (!empty($schedule_data['upcoming'])): ?>
                    <?php foreach ($schedule_data['upcoming'] as $event): ?>
                        <div class="schedule-list-item <?= strtolower(str_replace('_', '-', $event['type'])) ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($event['course_title']) ?></span>
                                    <h5 class="fw-bold mt-1 mb-1"><?= htmlspecialchars($event['event_title']) ?></h5>
                                    <p class="small text-muted mb-0">
                                        <i class="fas fa-calendar-day me-1"></i> 
                                        <?= date('D, M j @ g:i A', strtotime($event['start_time'])) ?>
                                    </p>
                                </div>
                                <a href="<?= $event['link'] ?: BASE_URL . 'dashboard/student/course-player.php?course_id=' . $event['course_id'] ?>" 
                                    class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-check-circle me-2"></i> No major deadlines or live sessions scheduled for your courses right now.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <h3 class="fw-bold text-primary mb-3">Calendar Overview</h3>
            <div id='calendar'></div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js'></script>

<script>
    const SCHEDULE_EVENTS = <?= $schedule_events_json ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            editable: false, // Students should not drag/drop
            eventLimit: true,
            timeZone: 'local', // Interpret server time as local time

            events: SCHEDULE_EVENTS.map(event => ({
                id: event.unique_id,
                title: `${event.course_title}: ${event.event_title}`, // Combine course title
                start: event.start_time, 
                extendedProps: event,
                // Apply a CSS class for color coding
                className: `fc-event-${event.type.toLowerCase()}` 
            })),

            eventClick: function(info) {
                const event = info.event.extendedProps;
                let link = event.link || `<?= BASE_URL ?>dashboard/student/course-player.php?course_id=${event.course_id}`;

                if (event.type === 'LIVE_SESSION') {
                    if (confirm(`Join the Live Session: ${event.event_title} in ${event.course_title}?`)) {
                        window.open(link, '_blank');
                    }
                } else {
                    if (confirm(`Go to the course for this deadline: ${event.event_title}?`)) {
                        window.location.href = link;
                    }
                }
            }
        });

        calendar.render();
    });
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>