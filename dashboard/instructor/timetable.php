<?php
require_once __DIR__ . '/../../includes/config.php';

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

function fetch_unified_schedule(PDO $pdo, int $instructorId): array
{
    try {
        $sql = "
            (
                SELECT 
                    ls.id AS entity_id,
                    ls.course_id,
                    'LIVE_SESSION' AS type,
                    ls.title,
                    ls.start_time,
                    ls.meeting_link AS link,
                    CONCAT('LS-', ls.id) AS unique_id
                FROM live_sessions ls
                WHERE ls.instructor_id = ? AND DATE(ls.start_time) >= CURDATE()
            )
            UNION ALL
            (
                SELECT 
                    cs.id AS entity_id,
                    cs.course_id,
                    cs.type,
                    cs.title,
                    cs.start_time,
                    NULL AS link,
                    CONCAT('CS-', cs.id) AS unique_id
                FROM course_schedule cs
                WHERE cs.instructor_id = ? AND DATE(cs.start_time) >= CURDATE()
            )
            ORDER BY start_time ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructorId, $instructorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Timetable Fetch Error: " . $e->getMessage());
        return [];
    }
}

$schedule_events = fetch_unified_schedule($pdo, $instructor_id);
$schedule_events_json = json_encode($schedule_events);

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Timetable | Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/instructor-styles.css?v=<?= time() ?>">

    <style>
        .fc-event-live {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }

        .fc-event-lesson {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        .fc-event-milestone {
            background-color: #198754 !important;
            border-color: #198754 !important;
        }

        .fc-event-other {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }

        .fc-event {
            color: white !important;
            font-weight: bold !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }
    </style>
</head>

<body class="instructor-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <h2 class="fw-bold mb-4">🗓️ Course Timetable & Schedule Management</h2>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="card p-4 shadow-sm stat-card">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0 text-primary">Schedule Overview</h4>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            <i class="fas fa-plus me-2"></i> Add Schedule Item
                        </button>
                    </div>
                </div>

                <div id='calendar'></div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Schedule New Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" id="modalAction" value="add">
                        <input type="hidden" name="schedule_id" id="scheduleId">

                        <div class="mb-3">
                            <label for="courseId" class="form-label">Course *</label>
                            <select name="course_id" id="modalCourseId" class="form-select text-dark" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Activity Type *</label>
                            <select name="type" id="modalType" class="form-select text-dark" required>
                                <option value="LESSON">Lesson Due Date</option>
                                <option value="QUIZ">Quiz Deadline</option>
                                <option value="MILESTONE">Major Milestone/Exam</option>
                                <option value="OTHER">Other Schedule Item</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" name="title" id="modalTitle" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="startTime" class="form-label">Date & Time *</label>
                            <input type="datetime-local" name="start_time" id="modalStartTime" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Internal)</label>
                            <textarea name="notes" id="modalNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="scheduleForm" class="btn btn-primary" id="modalSaveBtn">Save Schedule</button>
                </div>
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
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                editable: true,
                eventLimit: true,
                events: SCHEDULE_EVENTS.map(event => ({
                    id: event.unique_id,
                    title: event.title,
                    start: event.start_time,
                    className: 'fc-event-' + event.type.toLowerCase(),
                    extendedProps: event,
                    editable: event.type !== 'LIVE_SESSION'
                })),

                eventClick: function(info) {
                    if (info.event.extendedProps.type !== 'LIVE_SESSION') {
                        const eventData = info.event.extendedProps;
                        document.getElementById('scheduleModalLabel').textContent = 'Edit Schedule Item';
                        document.getElementById('modalAction').value = 'update';
                        document.getElementById('scheduleId').value = eventData.entity_id;
                        document.getElementById('modalCourseId').value = eventData.course_id;
                        document.getElementById('modalType').value = eventData.type;
                        document.getElementById('modalTitle').value = eventData.title;
                        document.getElementById('modalStartTime').value = eventData.start_time.substring(0, 16);
                        document.getElementById('modalNotes').value = eventData.notes || '';

                        var myModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
                        myModal.show();
                    } else {
                        alert('This is a Live Session. Manage it in the Live Session Scheduler.');
                        window.open(info.event.extendedProps.link, '_blank');
                    }
                },

                eventDrop: function(info) {
                    const event = info.event;
                    const eventData = event.extendedProps;

                    if (eventData.type === 'LIVE_SESSION') {
                        info.revert();
                        alert('Live Sessions cannot be moved here. Use the Live Session Scheduler.');
                        return;
                    }

                    const newStartTime = event.start.toISOString().substring(0, 16); // YYYY-MM-DDTHH:MM

                    const formData = new FormData();
                    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                    formData.append('action', 'drag_update');
                    formData.append('schedule_id', eventData.entity_id);
                    formData.append('unique_id', event.id); // e.g., CS-123
                    formData.append('start_time', newStartTime);

                    fetch('schedule_handler.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {

                            } else {
                                info.revert();
                                alert('Update failed: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('AJAX drop error:', error);
                            info.revert();
                            alert('A critical error occurred while saving the schedule.');
                        });
                }
            });

            calendar.render();

            const scheduleModal = document.getElementById('scheduleModal');
            const scheduleForm = document.getElementById('scheduleForm');
            const modalSaveBtn = document.getElementById('modalSaveBtn');

            function resetModal() {
                scheduleForm.reset();
                document.getElementById('scheduleModalLabel').textContent = 'Schedule New Activity';
                document.getElementById('modalAction').value = 'add';
                document.getElementById('scheduleId').value = '';
                document.getElementById('modalCourseId').disabled = false;
                document.getElementById('modalType').disabled = false;
                modalSaveBtn.textContent = 'Save Schedule';
            }

            scheduleModal.addEventListener('hidden.bs.modal', resetModal);

            scheduleForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(scheduleForm);
                const action = formData.get('action');

                modalSaveBtn.disabled = true;
                modalSaveBtn.textContent = (action === 'add' ? 'Adding...' : 'Updating...');

                fetch('schedule_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);

                            if (action === 'add') {
                                calendar.addEvent({
                                    id: data.event.id,
                                    title: data.event.title,
                                    start: data.event.start_time,
                                    className: 'fc-event-' + data.event.type.toLowerCase(),
                                    extendedProps: data.event,
                                    editable: true
                                });
                            } else if (action === 'update') {
                                const currentEvent = calendar.getEventById(data.event.id);
                                if (currentEvent) {
                                    currentEvent.setProp('title', data.event.title);
                                    currentEvent.setStart(data.event.start_time);
                                    currentEvent.setExtendedProp('notes', data.event.notes);
                                    currentEvent.setExtendedProp('course_id', data.event.course_id);
                                }
                            }

                            bootstrap.Modal.getInstance(scheduleModal).hide();

                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('AJAX error:', error);
                        alert('A connection error occurred. Check the console for details.');
                    })
                    .finally(() => {
                        modalSaveBtn.disabled = false;
                        modalSaveBtn.textContent = action === 'add' ? 'Save Schedule' : 'Update Schedule';
                    });
            });

            calendar.setOption('eventClick', function(info) {
                const eventData = info.event.extendedProps;

                if (eventData.type === 'LIVE_SESSION') {
                    alert('This is a Live Session. Manage it in the Live Session Scheduler.');
                    window.open(eventData.link, '_blank');
                    return;
                }

                document.getElementById('scheduleModalLabel').textContent = 'Edit Schedule Item';
                document.getElementById('modalAction').value = 'update';
                document.getElementById('scheduleId').value = eventData.entity_id;
                document.getElementById('modalCourseId').value = eventData.course_id;
                document.getElementById('modalType').value = eventData.type;
                document.getElementById('modalTitle').value = eventData.title;

                const formattedStartTime = eventData.start_time ? eventData.start_time.substring(0, 16).replace(' ', 'T') : '';
                document.getElementById('modalStartTime').value = formattedStartTime;

                document.getElementById('modalNotes').value = eventData.notes || '';

                document.getElementById('modalCourseId').disabled = true;
                document.getElementById('modalType').disabled = true;
                modalSaveBtn.textContent = 'Update Schedule';

                var myModal = new bootstrap.Modal(scheduleModal);
                myModal.show();
            });
        });
    </script>
</body>

</html>